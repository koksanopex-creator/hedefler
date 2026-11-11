<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\ConfirmablePasswordController;
use App\Http\Controllers\Auth\EmailVerificationNotificationController;
use App\Http\Controllers\Auth\EmailVerificationPromptController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\Auth\VerifyEmailController;
use App\Http\Controllers\Auth\SsoC;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    // Standart login iptal edildi -> Merkezi SSO'ya yönlendirme
    Route::get('login', function () {
        $centralUrl = rtrim(config('services.central_sso.url', env('CENTRAL_SSO_URL', 'https://kys.koksan.com/merkezi_yonetim_sistemi')), '/');
        $appCode = config('services.central_sso.app_code', env('CENTRAL_SSO_APP_CODE', 'hedefler'));
        $params = ['app_code' => $appCode];
        if (request()->filled('intended')) {
            $params['intended'] = request('intended');
        }
        return redirect($centralUrl . '/sso-entry?' . http_build_query($params));
    })->name('login');
});

// SSO Karşılama Rotaları (Hem misafir hem auth erişebilmeli)
Route::get('sso/login', [SsoC::class, 'login'])->name('sso.login');
Route::get('sso/basvuru', [SsoC::class, 'basvuru'])->name('sso.basvuru');
Route::post('sso/basvuru', [SsoC::class, 'basvuruKaydet'])->name('sso.basvuru.kaydet');
Route::get('sso/onay-bekliyor', [SsoC::class, 'onayBekliyor'])->name('sso.onay_bekliyor');
Route::match(['get', 'post'], 'logout', [SsoC::class, 'logout'])->name('logout');
