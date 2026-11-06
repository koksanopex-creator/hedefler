<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // 1. users.manage açıklamasını güncelle
        DB::table('permissions')
            ->where('slug', 'users.manage')
            ->update([
                'name' => 'Kullanıcı Yönetimi (Ekleme, Düzenleme, Rol/Durum Değiştirme)',
                'updated_at' => now(),
            ]);

        // 2. users.view yetkisini ekle
        $viewPerm = DB::table('permissions')->where('slug', 'users.view')->first();
        if (!$viewPerm) {
            $viewPermId = DB::table('permissions')->insertGetId([
                'module'     => 'users',
                'action'     => 'view',
                'slug'       => 'users.view',
                'name'       => 'Kullanıcı Listesi & İletişim Rehberini Görüntüleme',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } else {
            $viewPermId = $viewPerm->id;
        }

        // 3. Varsayılan Rol Atamaları:
        // Sistem Yöneticisi (1): users.view ve users.manage
        DB::table('role_permission')->updateOrInsert([
            'role_id'       => 1,
            'permission_id' => $viewPermId,
        ]);

        // Yönetim (2): users.view (Madde 19 gereği salt okunur izleme)
        DB::table('role_permission')->updateOrInsert([
            'role_id'       => 2,
            'permission_id' => $viewPermId,
        ]);

        // Veri Giriş Yöneticisi (3): users.view (Kendi personellerini ve rehberi görebilir)
        DB::table('role_permission')->updateOrInsert([
            'role_id'       => 3,
            'permission_id' => $viewPermId,
        ]);

        // users.manage yetkisini sadece Sistem Yöneticisinde (1) tut, diğer rollerden kaldır
        $managePerm = DB::table('permissions')->where('slug', 'users.manage')->first();
        if ($managePerm) {
            DB::table('role_permission')
                ->where('permission_id', $managePerm->id)
                ->where('role_id', '!=', 1)
                ->delete();
        }
    }

    public function down(): void
    {
        $perm = DB::table('permissions')->where('slug', 'users.view')->first();
        if ($perm) {
            DB::table('role_permission')->where('permission_id', $perm->id)->delete();
            DB::table('permissions')->where('id', $perm->id)->delete();
        }
    }
};

