<?php

namespace Database\Seeders;

use App\Models\RoleM;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            ['name' => 'Sistem Yöneticisi',     'slug' => 'sistem_yoneticisi',     'is_system' => true],
            ['name' => 'Yönetim (Read Only)',   'slug' => 'yonetim',               'is_system' => true],
            ['name' => 'Veri Giriş Yöneticisi', 'slug' => 'veri_giris_yoneticisi', 'is_system' => true],
            ['name' => 'Veri Girişi',           'slug' => 'veri_girisi',           'is_system' => true],
        ];

        foreach ($roles as $role) {
            RoleM::updateOrCreate(['slug' => $role['slug']], $role);
        }
    }
}
