<?php

namespace App\Http\Controllers;

use App\Models\UserM;
use App\Models\ActivityLogM;
use App\Services\ActivityLogS;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class SessionMonitorC extends Controller
{
    /**
     * Aktif oturumlar ve anlık giriş izleme ekranı
     */
    public function index(Request $request)
    {
        // 1. sessions tablosundaki oturumları çek (kullanıcısı olanlar)
        $rawSessions = DB::table('sessions')
            ->whereNotNull('user_id')
            ->orderBy('last_activity', 'desc')
            ->get();

        $userIds = $rawSessions->pluck('user_id')->unique()->filter()->toArray();
        $users = UserM::with(['role', 'group'])->whereIn('id', $userIds)->get()->keyBy('id');

        $now = time();
        $currentSessionId = session()->getId();

        $activeSessions = $rawSessions->map(function ($s) use ($users, $now, $currentSessionId) {
            $user = $users[$s->user_id] ?? null;
            $diffSeconds = $now - (int)$s->last_activity;

            // Durum belirleme: 5 dk altı Canlı, 30 dk altı Boşta, üzeri Beklemede
            if ($diffSeconds <= 300) {
                $status = 'online';
                $statusLabel = 'Çevrimiçi';
                $statusBadge = 'bg-emerald-100 text-emerald-800 border-emerald-200';
            } elseif ($diffSeconds <= 1800) {
                $status = 'idle';
                $statusLabel = 'Boşta';
                $statusBadge = 'bg-amber-100 text-amber-800 border-amber-200';
            } else {
                $status = 'offline';
                $statusLabel = 'Beklemede';
                $statusBadge = 'bg-slate-100 text-slate-700 border-slate-200';
            }

            $uaInfo = self::parseUserAgent($s->user_agent);

            return (object)[
                'id' => $s->id,
                'user_id' => $s->user_id,
                'user' => $user,
                'ip_address' => $s->ip_address,
                'user_agent' => $s->user_agent,
                'browser' => $uaInfo['browser'],
                'platform' => $uaInfo['platform'],
                'last_activity' => $s->last_activity,
                'last_active_at' => Carbon::createFromTimestamp($s->last_activity),
                'diff_seconds' => $diffSeconds,
                'status' => $status,
                'status_label' => $statusLabel,
                'status_badge' => $statusBadge,
                'is_current' => $s->id === $currentSessionId,
            ];
        });

        // 2. İstatistikler
        $onlineCount = $activeSessions->where('status', 'online')->pluck('user_id')->unique()->count();
        $totalSessions = $activeSessions->count();
        $todayStart = Carbon::today()->startOfDay();
        $todayLoginCount = DB::table('activity_logs')
            ->whereIn('action', ['auth.login', 'login'])
            ->where('created_at', '>=', $todayStart)
            ->distinct('user_id')
            ->count('user_id');

        $latestLogin = DB::table('activity_logs')
            ->whereIn('action', ['auth.login', 'login'])
            ->latest('created_at')
            ->first();

        // 3. Giriş Geçmişi Logları (activity_logs)
        $loginLogs = ActivityLogM::with(['user.role', 'user.group'])
            ->whereIn('action', ['auth.login', 'auth.logout', 'login', 'logout', 'session.terminate'])
            ->latest('created_at')
            ->paginate(25);

        return view('system.active_sessions', compact(
            'activeSessions',
            'onlineCount',
            'totalSessions',
            'todayLoginCount',
            'latestLogin',
            'loginLogs'
        ));
    }

    /**
     * Belirtilen aktif oturumu sonlandırır
     */
    public function destroy(string $sessionId)
    {
        // Yonetim rolü salt okunurdur
        if (auth()->user()?->isYonetim()) {
            return back()->with('error', 'Yönetim rolü salt okunur yetkiye sahiptir. Oturum sonlandıramaz.');
        }

        // Kendi aktif oturumunu sonlandırmayı engelle
        if ($sessionId === session()->getId()) {
            return back()->with('error', 'Şu anda kullanmakta olduğunuz aktif oturumu bu ekrandan sonlandıramazsınız. Çıkış yapmak için üst menüdeki Çıkış butonunu kullanınız.');
        }

        $sessionRow = DB::table('sessions')->where('id', $sessionId)->first();

        if (!$sessionRow) {
            return back()->with('error', 'Oturum bulunamadı veya zaten sonlandırılmış.');
        }

        $user = $sessionRow->user_id ? UserM::find($sessionRow->user_id) : null;
        $userName = $user ? $user->name : 'Bilinmeyen Kullanıcı';
        $userEmail = $user ? $user->email : '-';

        // Süperadmin oturumunu başkası sonlandıramaz
        if ($user && ($user->id === 1 || $user->isSuperAdmin()) && !auth()->user()->isSuperAdmin()) {
            return back()->with('error', 'Sistem Yöneticisinin oturumunu sonlandıramazsınız.');
        }

        DB::table('sessions')->where('id', $sessionId)->delete();

        // Denetim kaydı oluştur
        ActivityLogS::logAction(
            'session.terminate',
            "{$userName} ({$userEmail}) kullanıcısının [{$sessionRow->ip_address}] IP adresli aktif oturumu yönetici tarafından sonlandırıldı.",
            $user ? UserM::class : null,
            $user?->id
        );

        return back()->with('success', "'{$userName}' kullanıcısının aktif oturumu başarıyla sonlandırıldı.");
    }

    /**
     * User Agent dizesini tarayıcı ve işletim sistemine ayrıştırır
     */
    public static function parseUserAgent(?string $ua): array
    {
        if (empty($ua)) {
            return ['browser' => 'Bilinmiyor', 'platform' => 'Bilinmiyor'];
        }

        $platform = 'Bilinmiyor';
        if (preg_match('/windows nt 10/i', $ua)) $platform = 'Windows 10/11';
        elseif (preg_match('/windows nt 6\.3/i', $ua)) $platform = 'Windows 8.1';
        elseif (preg_match('/windows nt 6\.1/i', $ua)) $platform = 'Windows 7';
        elseif (preg_match('/windows/i', $ua)) $platform = 'Windows';
        elseif (preg_match('/macintosh|mac os x/i', $ua)) $platform = 'macOS';
        elseif (preg_match('/iphone/i', $ua)) $platform = 'iPhone';
        elseif (preg_match('/ipad/i', $ua)) $platform = 'iPad';
        elseif (preg_match('/android/i', $ua)) $platform = 'Android';
        elseif (preg_match('/linux/i', $ua)) $platform = 'Linux';

        $browser = 'Bilinmiyor';
        if (preg_match('/edg/i', $ua)) $browser = 'Edge';
        elseif (preg_match('/chrome/i', $ua) && !preg_match('/edg/i', $ua)) $browser = 'Chrome';
        elseif (preg_match('/firefox/i', $ua)) $browser = 'Firefox';
        elseif (preg_match('/safari/i', $ua) && !preg_match('/chrome/i', $ua)) $browser = 'Safari';
        elseif (preg_match('/opera|opr/i', $ua)) $browser = 'Opera';

        return [
            'browser' => $browser,
            'platform' => $platform,
        ];
    }
}

