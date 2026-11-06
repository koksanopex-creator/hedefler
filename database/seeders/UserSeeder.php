<?php

namespace Database\Seeders;

use App\Models\UserM;
use App\Models\RoleM;
use App\Models\GroupM;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $roleAdmin   = RoleM::where('slug', 'sistem_yoneticisi')->firstOrFail();
        $roleYonetim = RoleM::where('slug', 'yonetim')->firstOrFail();
        $roleManager = RoleM::where('slug', 'veri_giris_yoneticisi')->firstOrFail();
        $roleOperator= RoleM::where('slug', 'veri_girisi')->firstOrFail();

        $groupA = GroupM::where('name', 'Grup A')->firstOrFail();
        $groupB = GroupM::where('name', 'Grup B')->firstOrFail();

        // 1) Sistem Yöneticisi
        UserM::updateOrCreate(
            ['email' => 'admin@example.com'],
            ['name' => 'Sistem Yöneticisi', 'password' => Hash::make('password'), 'role_id' => $roleAdmin->id, 'is_active' => true]
        );

        // 2) Yönetim (read only)
        UserM::updateOrCreate(
            ['email' => 'yonetim@example.com'],
            ['name' => 'Yönetim', 'password' => Hash::make('password'), 'role_id' => $roleYonetim->id, 'is_active' => true]
        );

        // 3) Grup Yöneticileri
        $managerA = UserM::updateOrCreate(
            ['email' => 'yonetici.a@example.com'],
            ['name' => 'Grup A Yöneticisi', 'password' => Hash::make('password'), 'role_id' => $roleManager->id, 'is_active' => true]
        );
        $managerB = UserM::updateOrCreate(
            ['email' => 'yonetici.b@example.com'],
            ['name' => 'Grup B Yöneticisi', 'password' => Hash::make('password'), 'role_id' => $roleManager->id, 'is_active' => true]
        );

        $managerA->managedGroups()->sync([$groupA->id]);
        $managerB->managedGroups()->sync([$groupB->id]);

        // 4) Operatörler
        foreach (range(1, 2) as $i) {
            UserM::updateOrCreate(
                ['email' => "operator.a{$i}@example.com"],
                ['name' => "Grup A Operatör {$i}", 'password' => Hash::make('password'),
                 'role_id' => $roleOperator->id, 'group_id' => $groupA->id, 'is_active' => true]
            );

            UserM::updateOrCreate(
                ['email' => "operator.b{$i}@example.com"],
                ['name' => "Grup B Operatör {$i}", 'password' => Hash::make('password'),
                 'role_id' => $roleOperator->id, 'group_id' => $groupB->id, 'is_active' => true]
            );
        }
    }
}
