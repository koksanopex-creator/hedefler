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
        $centralUrl = rtrim(env('CENTRAL_SSO_URL', 'http://localhost:8001'), '/');
        $params = ['app_code' => 'hedefler'];
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
Route::post('logout', [SsoC::class, 'logout'])->name('logout');
