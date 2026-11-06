<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Mail;
use App\Services\PermissionS;
use App\Mail\Transports\MicrosoftGraphTransport;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Laravel Gate:before ile tüm permission kontrollerini yakala
        Gate::before(function ($user, $ability) {
            $permissionService = app(PermissionS::class);
            return $permissionService->hasPermission($user, $ability) ? true : null;
        });

        // Microsoft Graph API Mail Sürücüsü (kys@koksan.com)
        Mail::extend('microsoft-graph', function () {
            return new MicrosoftGraphTransport(
                (string) config('services.microsoft.tenant_id'),
                (string) config('services.microsoft.client_id'),
                (string) config('services.microsoft.client_secret'),
                (string) config('services.microsoft.from_address', 'kys@koksan.com'),
            );
        });
    }
}
