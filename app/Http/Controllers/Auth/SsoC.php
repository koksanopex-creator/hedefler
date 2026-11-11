<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\UserM;
use App\Models\SsoApplicationM;
use App\Services\SettingS;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SsoC extends Controller
{
    /**
     * Merkezi API SSO'dan gelen token ile giriş yapar.
     */
    public function login(Request $request)
    {
        $token = $request->query('token');
        $centralUrl = rtrim(config('services.central_sso.url', env('CENTRAL_SSO_URL', 'https://kys.koksan.com/merkezi_yonetim_sistemi')), '/');
        $internalHttpUrl = str_replace('localhost', '127.0.0.1', $centralUrl);
        $appCode = config('services.central_sso.app_code', env('CENTRAL_SSO_APP_CODE', 'hedefler'));

        if (!$token) {
            return redirect($centralUrl . '/sso-entry?app_code=' . $appCode);
        }

        // Token Doğrulama
        try {
            $response = Http::withoutVerifying()->timeout(6)->get($internalHttpUrl . '/api/auth/verify-sso-token', [
                'token' => $token
            ]);
        } catch (\Exception $e) {
            Log::error('SSO Doğrulama isteği hatası: ' . $e->getMessage());
            return redirect($centralUrl)->with('error', 'Merkezi oturum sunucusuna bağlanılamadı: ' . $e->getMessage());
        }

        if ($response->failed()) {
            return redirect($centralUrl)->with('error', 'Geçersiz veya süresi dolmuş merkezi oturum tokeni.');
        }

        $centralUser = $response->json('user');
        $centralSessionId = $response->json('central_session_id');

        if (!$centralUser || empty($centralUser['email'])) {
            return redirect($centralUrl)->with('error', 'Kullanıcı bilgisi alınamadı.');
        }

        $centralDb = env('CENTRAL_DB_DATABASE') ?: (
            \Illuminate\Support\Facades\DB::select("SHOW DATABASES LIKE 'merkezi_app'") ? 'merkezi_app' : 'merkezi_auth_db'
        );
        $deptName = $centralUser['department']['name'] ?? null;
        if (!$deptName && !empty($centralUser['department_id'])) {
            try {
                $deptRow = \Illuminate\Support\Facades\DB::table("{$centralDb}.departments")->where('id', $centralUser['department_id'])->first();
                $deptName = $deptRow?->name;
            } catch (\Throwable $e) {
            }
        }

        $centralUser['department_name'] = $deptName;

        session([
            'sso_user_data' => $centralUser,
            'central_user_id' => $centralUser['id'] ?? null,
            'central_session_id' => $centralSessionId,
        ]);

        if ($request->filled('intended')) {
            session(['url.intended' => $request->query('intended')]);
        }

        $localUser = UserM::where('central_user_id', $centralUser['id'])
            ->orWhere('email', $centralUser['email'])
            ->first();

        // MERKEZİ APİ YETKİ KONTROLÜ: Kullanıcı Merkezi API'de Hedefler uygulamasına atanmış ve onaylı mı?
        $centralDb = env('CENTRAL_DB_DATABASE') ?: (
            \Illuminate\Support\Facades\DB::select("SHOW DATABASES LIKE 'merkezi_app'") ? 'merkezi_app' : 'merkezi_auth_db'
        );
        $hedeflerAppId = null;
        try {
            $hedeflerAppId = \Illuminate\Support\Facades\DB::table("{$centralDb}.applications")
                ->where(function ($q) {
                    $q->whereIn('code', ['hedefler', 'hedefler-uygulamasi'])
                        ->orWhere('code', 'like', '%hedef%');
                })
                ->value('id');
        } catch (\Throwable $e) {
        }
        $hedeflerAppId = $hedeflerAppId ?? 14;

        $centralAppUser = null;
        try {
            $centralAppUser = \Illuminate\Support\Facades\DB::table("{$centralDb}.app_users")
                ->where('user_id', $centralUser['id'])
                ->where('application_id', $hedeflerAppId)
                ->whereNull('deleted_at')
                ->first();
        } catch (\Throwable $e) {
        }

        $isCentralApproved = $centralAppUser && $centralAppUser->status === 'approved' && $centralAppUser->is_active;

        // Süperadminler asla merkezi portal yetkisizliği nedeniyle kilitlenemez
        $isSuperAdminUser = $localUser && ($localUser->id === 1 || $localUser->isSuperAdmin() || $localUser->role_id === 1 || in_array($localUser->email, ['ahmet.gulluce@koksan.com', 'celal.karaman@koksan.com']));

        if (!$isCentralApproved && !$isSuperAdminUser) {
            // Merkezi API'de bu uygulama atanmamış veya yetkisi kaldırılmış
            if ($localUser) {
                $localUser->update([
                    'is_active' => false,
                    'approval_status' => 'rejected',
                    'rejection_type' => 'permanent',
                    'rejection_reason' => 'Merkezi Yönetim Portalında Hedefler uygulaması yetkiniz bulunmamaktadır.',
                    'rejected_at' => now(),
                ]);
                try {
                    \Illuminate\Support\Facades\DB::table('sessions')
                        ->where('user_id', $localUser->id)
                        ->where('id', '!=', session()->getId())
                        ->delete();
                } catch (\Exception $e) {
                }
            }

            session([
                'sso_user_data' => $centralUser,
                'account_passive' => true,
            ]);
            session()->save();

            return redirect()->route('sso.onay_bekliyor')->with('account_passive', true);
        }

        // Eğer Merkezi API'de onaylıysa ve daha önce Merkezi çıkarılma gerekçesiyle reddedilmişse kilidi aç
        if ($localUser && $localUser->approval_status === 'rejected' && str_contains($localUser->rejection_reason ?? '', 'Merkezi')) {
            $localUser->update([
                'is_active' => !empty($localUser->role_id),
                'approval_status' => !empty($localUser->role_id) ? 'approved' : 'pending_role',
                'rejection_type' => null,
                'rejection_reason' => null,
                'rejected_at' => null,
            ]);
            $localUser->refresh();
        }

        // SENARYO 1: Kullanıcı HEDEFLER veritabanında mevcut
        if ($localUser) {
            // İsim, E-posta, Bölüm ve Merkezi ID senkronizasyonu
            $fullName = trim(($centralUser['first_name'] ?? '') . ' ' . ($centralUser['last_name'] ?? ''));
            $updates = [
                'central_user_id' => $centralUser['id'],
                'central_department' => $deptName,
            ];
            if (!empty($fullName) && $localUser->name !== $fullName) {
                $updates['name'] = $fullName;
            }
            if (!empty($centralUser['email']) && $localUser->email !== $centralUser['email']) {
                $updates['email'] = $centralUser['email'];
            }
            $localUser->update($updates);

            // 1) Pasif Kullanıcı Kontrolü (Öncelikli)
            if (!$localUser->is_active) {
                session([
                    'sso_user_data' => $centralUser,
                    'account_passive' => true,
                ]);
                session()->save();
                return redirect()->route('sso.onay_bekliyor')->with('account_passive', true);
            }

            // A) Kesin Red Durumu
            if ($localUser->approval_status === 'rejected' && $localUser->rejection_type === 'permanent') {
                return redirect()->route('sso.onay_bekliyor');
            }

            // B) Düzeltilebilir Gerekçeli Red Durumu
            if ($localUser->approval_status === 'rejected' && $localUser->rejection_type === 'correctable') {
                return redirect()->route('sso.onay_bekliyor');
            }

            // C) Rolü henüz verilmemiş veya Onay Bekliyor Durumu
            if (empty($localUser->role_id) || in_array($localUser->approval_status, ['pending_role', 'pending_application'])) {
                return redirect()->route('sso.onay_bekliyor');
            }

            // D) Rolü atanmış ve aktif -> GİRİŞ YAP
            $localUser->update(['approval_status' => 'approved']);
            Auth::login($localUser);

            \App\Services\ActivityLogS::logAction(
                'auth.login',
                "{$localUser->name} ({$localUser->email}) Merkezi SSO ile Hedefler uygulamasına giriş yaptı.",
                UserM::class,
                $localUser->id
            );

            $targetUrl = session()->pull('url.intended', route('dashboard'));
            return redirect()->to($targetUrl);
        }

        // SENARYO 2: Kullanıcı HEDEFLER veritabanında yok
        // Önce sso_applications tablosunda kayıt var mı kontrol et
        $lastApp = SsoApplicationM::where('email', $centralUser['email'])->latest()->first();

        if ($lastApp) {
            // Kesin red almışsa
            if ($lastApp->status === 'rejected' && $lastApp->rejection_type === 'permanent') {
                return redirect()->route('sso.onay_bekliyor');
            }
            // Düzeltilebilir red almışsa
            if ($lastApp->status === 'rejected' && $lastApp->rejection_type === 'correctable') {
                return redirect()->route('sso.onay_bekliyor');
            }
            // Hala beklemede ise
            if ($lastApp->status === 'pending') {
                return redirect()->route('sso.onay_bekliyor');
            }
        }

        // SENARYO 3: İlk defa geliyor -> Başvuru ekranı
        $basvurularAcik = SettingS::get('basvurular_acik', true);
        if (!$basvurularAcik) {
            return redirect()->route('sso.onay_bekliyor')->with('applications_closed', true);
        }

        return redirect()->route('sso.basvuru');
    }

    /**
     * Saf HTML Başvuru Ekranı
     */
    public function basvuru()
    {
        $centralUser = session('sso_user_data');
        $centralUrl = rtrim(config('services.central_sso.url', env('CENTRAL_SSO_URL', 'https://kys.koksan.com/merkezi_yonetim_sistemi')), '/');

        $appCode = config('services.central_sso.app_code', env('CENTRAL_SSO_APP_CODE', 'hedefler'));
        if (!$centralUser) {
            return redirect($centralUrl . '/sso-entry?app_code=' . $appCode);
        }

        // Başvurular kapalı mı?
        $basvurularAcik = SettingS::get('basvurular_acik', true);
        if (!$basvurularAcik) {
            return redirect()->route('sso.onay_bekliyor')->with('applications_closed', true);
        }

        // Kesin red kontrolü
        $localUser = UserM::where('central_user_id', $centralUser['id'])
            ->orWhere('email', $centralUser['email'])
            ->first();
        if ($localUser && $localUser->approval_status === 'rejected' && $localUser->rejection_type === 'permanent') {
            return redirect()->route('sso.onay_bekliyor');
        }

        $lastApp = SsoApplicationM::where('email', $centralUser['email'])->latest()->first();
        if ($lastApp && $lastApp->status === 'rejected' && $lastApp->rejection_type === 'permanent') {
            return redirect()->route('sso.onay_bekliyor');
        }

        return view('auth.sso_basvuru', compact('centralUser', 'lastApp', 'centralUrl'));
    }

    /**
     * Başvuruyu kaydeder
     */
    public function basvuruKaydet(Request $request)
    {
        $centralUser = session('sso_user_data');
        $centralUrl = rtrim(config('services.central_sso.url', env('CENTRAL_SSO_URL', 'https://kys.koksan.com/merkezi_yonetim_sistemi')), '/');

        $appCode = config('services.central_sso.app_code', env('CENTRAL_SSO_APP_CODE', 'hedefler'));
        if (!$centralUser) {
            return redirect($centralUrl . '/sso-entry?app_code=' . $appCode);
        }

        $basvurularAcik = SettingS::get('basvurular_acik', true);
        if (!$basvurularAcik) {
            return redirect()->route('sso.onay_bekliyor')->with('applications_closed', true);
        }

        $request->validate([
            'application_note' => 'nullable|string|max:1000',
        ]);

        $fullName = trim(($centralUser['first_name'] ?? '') . ' ' . ($centralUser['last_name'] ?? ''));
        $deptName = $centralUser['department_name'] ?? null;

        // Yerel user kaydı oluştur veya güncelle (rolsüz olarak)
        $user = UserM::firstOrNew(['email' => $centralUser['email']]);
        $user->name = $fullName;
        $user->central_user_id = $centralUser['id'] ?? null;
        $user->central_department = $deptName;
        $user->approval_status = 'pending_application';
        $user->rejection_type = null;
        $user->rejection_reason = null;
        $user->rejected_at = null;
        $user->is_active = false;
        if (!$user->exists) {
            $user->password = bcrypt(\Illuminate\Support\Str::random(24));
        }
        $user->save();

        // Başvuru kaydı oluştur
        SsoApplicationM::create([
            'user_id' => $user->id,
            'central_user_id' => $centralUser['id'] ?? null,
            'name' => $fullName,
            'email' => $centralUser['email'],
            'department' => $deptName,
            'application_note' => $request->application_note,
            'status' => 'pending',
        ]);

        // Merkezi API'ye webhook ile bildir
        $apiKey = env('CENTRAL_SSO_API_KEY', 'N15T2CN4PdhJjVLQhusCuW3GTb9Kt1kfi3mW5jOZo0tjdtuTQVbotJIwPfVKHCLQ');
        $internalHttpUrl = str_replace('localhost', '127.0.0.1', $centralUrl);

        try {
            Http::withoutVerifying()->timeout(5)->withHeaders([
                'X-App-Key' => $apiKey,
                'Accept' => 'application/json'
            ])->post($internalHttpUrl . '/api/internal/uygulama-basvuru-durum', [
                        'email' => $user->email,
                        'status' => 'pending'
                    ]);
        } catch (\Exception $e) {
            Log::warning('Merkezi API webhook uyarısı: ' . $e->getMessage());
        }

        return redirect()->route('sso.onay_bekliyor')->with('success', 'Başvurunuz başarıyla iletildi. Sistem yöneticisi onayından sonra giriş yapabilirsiniz.');
    }

    /**
     * Durum Bilgilendirme Ekranı (Rol Bekliyor, İnceleniyor, Düzeltilebilir Red, Kesin Red, Başvurular Kapalı)
     */
    public function onayBekliyor()
    {
        $centralUser = session('sso_user_data');
        $centralUrl = rtrim(config('services.central_sso.url', env('CENTRAL_SSO_URL', 'https://kys.koksan.com/merkezi_yonetim_sistemi')), '/');

        if (!$centralUser) {
            // Oturumu olan yerel kullanıcı varsa onu baz al
            if (Auth::check()) {
                $user = Auth::user();
                $centralUser = [
                    'first_name' => $user->name,
                    'last_name' => '',
                    'email' => $user->email,
                    'department_name' => $user->central_department,
                ];
            } else {
                $centralUser = [
                    'first_name' => 'Kullanıcı',
                    'last_name' => '',
                    'email' => '-',
                    'department_name' => 'Tanımlı Değil',
                ];
                $state = 'account_passive';
                $rejectionReason = 'Hedefler uygulamasına erişim yetkiniz pasif duruma getirilmiştir.';
                $basvurularAcik = SettingS::get('basvurular_acik', true);
                return view('auth.sso_onay_bekliyor', compact('centralUser', 'state', 'rejectionReason', 'centralUrl', 'basvurularAcik'));
            }
        }

        $email = $centralUser['email'];
        $localUser = UserM::where('email', $email)->first();
        $lastApp = SsoApplicationM::where('email', $email)->latest()->first();

        $basvurularAcik = SettingS::get('basvurular_acik', true);
        $applicationsClosed = session('applications_closed') || (!$basvurularAcik && !$lastApp && (!$localUser || empty($localUser->role_id)));

        // Durumu belirle:
        // 1. account_passive (Hesap Pasif)
        // 2. permanent_rejection (Kesin Red)
        // 3. correctable_rejection (Düzeltilebilir Gerekçeli Red)
        // 4. pending_role (Merkezi'den yetki verilmiş ama HEDEFLER'de rolü atanmamış)
        // 5. pending_application (Başvuru yapmış inceleniyor)
        // 6. applications_closed (Başvurular kapalı)

        $state = 'pending_application';
        $rejectionReason = null;

        if (session('account_passive') || ($localUser && !$localUser->is_active)) {
            $state = 'account_passive';
        } elseif ($applicationsClosed && (!$lastApp || $lastApp->status !== 'pending')) {
            $state = 'applications_closed';
        } elseif (
            ($localUser && $localUser->approval_status === 'rejected' && $localUser->rejection_type === 'permanent') ||
            ($lastApp && $lastApp->status === 'rejected' && $lastApp->rejection_type === 'permanent')
        ) {
            $state = 'permanent_rejection';
            $rejectionReason = $localUser?->rejection_reason ?? $lastApp?->rejection_reason;
        } elseif (
            ($localUser && $localUser->approval_status === 'rejected' && $localUser->rejection_type === 'correctable') ||
            ($lastApp && $lastApp->status === 'rejected' && $lastApp->rejection_type === 'correctable')
        ) {
            $state = 'correctable_rejection';
            $rejectionReason = $localUser?->rejection_reason ?? $lastApp?->rejection_reason;
        } elseif ($localUser && empty($localUser->role_id) && $localUser->approval_status === 'pending_role') {
            $state = 'pending_role';
        } elseif ($localUser && empty($localUser->role_id)) {
            $state = 'pending_role';
        }

        return view('auth.sso_onay_bekliyor', compact('centralUser', 'state', 'rejectionReason', 'centralUrl', 'basvurularAcik'));
    }

    /**
     * Çıkış Yap (Merkezi Yönetim Portalı'na yönlendirir)
     */
    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        $centralUrl = rtrim(config('services.central_sso.url', env('CENTRAL_SSO_URL', 'https://kys.koksan.com/merkezi_yonetim_sistemi')), '/');
        return redirect($centralUrl . '/dashboard');
    }
}

