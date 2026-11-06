<?php

namespace App\Services;

use App\Models\UserM;
use Illuminate\Support\Facades\Cache;

class PermissionS
{
    /**
     * Kullanıcının belirtilen yetkiye sahip olup olmadığını kontrol eder.
     * role_permission ve user_permission (override) tablolarını hesaba katar.
     */
    public function hasPermission(UserM $user, string $slug): bool
    {
        if ($user->role && $user->role->slug === 'sistem_yoneticisi') {
            return true;
        }

        $cacheKey = "user_{$user->id}_permission_{$slug}";

        return Cache::remember($cacheKey, 60, function () use ($user, $slug) {
            $userPermission = $user->permissions()->where('slug', $slug)->first();
            
            if ($userPermission) {
                return (bool) $userPermission->pivot->allowed;
            }

            if ($user->role) {
                return $user->role->permissions()->where('slug', $slug)->exists();
            }

            return false;
        });
    }
}
