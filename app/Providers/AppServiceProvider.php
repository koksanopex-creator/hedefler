<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

use Illuminate\Support\Facades\Gate;
use App\Services\PermissionS;

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
    }
}
