<?php

namespace App\Http\Controllers;

use App\Models\SsoApplicationM;
use App\Models\UserM;
use App\Models\RoleM;
use App\Models\GroupM;
use App\Services\SettingS;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SsoApplicationC extends Controller
{
    /**
     * Başvuru Yönetim Listesi
     */
    public function index()
    {
        $basvurularAcik = SettingS::get('basvurular_acik', true);
        
        $pendingApplications = SsoApplicationM::with(['user', 'reviewer'])
            ->where('status', 'pending')
            ->latest()
            ->get();

        $rejectedApplications = SsoApplicationM::with(['user', 'reviewer'])
            ->where('status', 'rejected')
            ->latest()
            ->get();

        $approvedApplications = SsoApplicationM::with(['user', 'reviewer'])
            ->where('status', 'approved')
            ->latest()
            ->take(20)
            ->get();

        $roles = RoleM::all();
        $groups = GroupM::where('is_active', true)->get();

        return view('users.applications', compact(
            'basvurularAcik',
            'pendingApplications',
            'rejectedApplications',
            'approvedApplications',
            'roles',
            'groups'
        ));
    }

    /**
     * Başvuruları Aç / Kapat Toggle
     */
    public function toggleApplications(Request $request)
    {
        $current = SettingS::get('basvurular_acik', true);
        $newStatus = !$current;
        SettingS::set('basvurular_acik', $newStatus);

        $msg = $newStatus ? 'Hedefler uygulaması başvuruları açıldı.' : 'Hedefler uygulaması başvuruları kapatıldı.';
        return back()->with('success', $msg);
    }

    /**
     * Başvuruyu Onayla ve Kullanıcıya Rol Ata
     */
    public function approve(Request $request, SsoApplicationM $application)
    {
        $request->validate([
            'role_id' => 'required|exists:roles,id',
            'group_id' => 'nullable|exists:groups,id',
        ]);

        // Kullanıcıyı güncelle veya oluştur
        $user = UserM::firstOrNew(['email' => $application->email]);
        $user->name = $application->name;
        $user->central_user_id = $application->central_user_id ?? $user->central_user_id;
        $user->central_department = $application->department ?? $user->central_department;
        $user->role_id = $request->role_id;
        $user->group_id = $request->group_id;
        $user->approval_status = 'approved';
        $user->rejection_type = null;
        $user->rejection_reason = null;
        $user->rejected_at = null;
        $user->is_active = true;

        if (!$user->exists) {
            $user->password = bcrypt(\Illuminate\Support\Str::random(24));
        }
        $user->save();

        // Başvuru kaydını onayla
        $application->update([
            'user_id' => $user->id,
            'status' => 'approved',
            'rejection_type' => null,
            'rejection_reason' => null,
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
        ]);

        // Merkezi API'ye Onay Durumunu Gönder (Webhook)
        $this->notifyCentralApi($application->email, 'approved');

        return back()->with('success', "{$application->name} adlı kullanıcının başvurusu onaylandı ve rolü tanımlandı.");
    }

    /**
     * Başvuruyu Reddet (Gerekçeli Düzeltilebilir veya Kesin Red)
     */
    public function reject(Request $request, SsoApplicationM $application)
    {
        $request->validate([
            'rejection_type' => 'required|in:correctable,permanent',
            'rejection_reason' => 'required|string|max:1000',
        ]);

        $application->update([
            'status' => 'rejected',
            'rejection_type' => $request->rejection_type,
            'rejection_reason' => $request->rejection_reason,
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
        ]);

        $user = UserM::where('email', $application->email)->first();
        if ($user) {
            $user->update([
                'approval_status' => 'rejected',
                'rejection_type' => $request->rejection_type,
                'rejection_reason' => $request->rejection_reason,
                'rejected_at' => now(),
                'is_active' => false,
            ]);
        }

        // Merkezi API'ye Red Durumunu Gönder (Webhook)
        $this->notifyCentralApi($application->email, 'rejected');

        $typeLabel = $request->rejection_type === 'permanent' ? 'Kesin Olarak' : 'Gerekçeli Olarak';
        return back()->with('success', "{$application->name} adlı kullanıcının başvurusu {$typeLabel} reddedildi.");
    }

    /**
     * Red Kararını Sil / Kaldır (Kullanıcının Tekrar Başvuru Hakkını Açar)
     */
    public function destroyRejection(SsoApplicationM $application)
    {
        $user = UserM::where('email', $application->email)->first();
        if ($user) {
            $user->update([
                'approval_status' => 'pending_application',
                'rejection_type' => null,
                'rejection_reason' => null,
                'rejected_at' => null,
            ]);
        }

        $userName = $application->name;
        $application->delete();

        return back()->with('success', "{$userName} adlı kullanıcının ret kararı silindi. Kullanıcı artık tekrar başvuru yapabilir.");
    }

    /**
     * Merkezi API'ye Webhook ile Durum Bildirir
     */
    private function notifyCentralApi(string $email, string $status)
    {
        $centralUrl = rtrim(config('services.central_sso.url', env('CENTRAL_SSO_URL', 'https://kys.koksan.com/merkezi_yonetim_sistemi')), '/');
        $internalHttpUrl = str_replace('localhost', '127.0.0.1', $centralUrl);
        $apiKey = env('CENTRAL_SSO_API_KEY', 'N15T2CN4PdhJjVLQhusCuW3GTb9Kt1kfi3mW5jOZo0tjdtuTQVbotJIwPfVKHCLQ');

        try {
            Http::timeout(5)->withHeaders([
                'X-App-Key' => $apiKey,
                'Accept' => 'application/json'
            ])->post($internalHttpUrl . '/api/internal/uygulama-basvuru-durum', [
                'email' => $email,
                'status' => $status,
            ]);
        } catch (\Exception $e) {
            Log::warning("Merkezi API webhook hatası ({$email} -> {$status}): " . $e->getMessage());
        }
    }
}

