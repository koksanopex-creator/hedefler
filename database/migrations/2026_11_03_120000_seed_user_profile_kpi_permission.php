<?php

use Illuminate\Database\Migrations\Migration;
use App\Models\PermissionM;
use App\Models\RoleM;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Profilde KPI Karnesini İnceleme İzni
        $profileKpiPerm = PermissionM::firstOrCreate(
            ['slug' => 'kpi_targets.view_profile_kpi'],
            [
                'module' => 'kpi_targets',
                'action' => 'view_profile_kpi',
                'name' => 'Personel KPI Karnesini Profilde İnceleme',
            ]
        );

        // Kendi hedeflerini görme izni
        $viewOwnPerm = PermissionM::where('slug', 'kpi_targets.view_own')->first();
        $viewGroupPerm = PermissionM::where('slug', 'kpi_targets.view_group')->first();
        $viewAllPerm = PermissionM::where('slug', 'kpi_targets.view_all')->first();

        // 2. Sistem Yöneticisi Rolüne yeni izni ekle
        $adminRole = RoleM::where('slug', 'sistem_yoneticisi')->first();
        if ($adminRole && $profileKpiPerm) {
            $adminRole->permissions()->syncWithoutDetaching([$profileKpiPerm->id]);
        }

        // 3. Yönetim (Read Only) Rolü: view_all, view_group, view_own, view_profile_kpi
        $yonetimRole = RoleM::where('slug', 'yonetim')->first();
        if ($yonetimRole) {
            $yonetimPerms = array_filter([
                $viewAllPerm?->id,
                $viewGroupPerm?->id,
                $viewOwnPerm?->id,
                $profileKpiPerm->id,
            ]);
            $yonetimRole->permissions()->syncWithoutDetaching($yonetimPerms);
        }

        // 4. Veri Giriş Yöneticisi Rolü: view_group, view_own, view_profile_kpi
        $vgYoneticiRole = RoleM::where('slug', 'veri_giris_yoneticisi')->first();
        if ($vgYoneticiRole) {
            $vgYoneticiPerms = array_filter([
                $viewGroupPerm?->id,
                $viewOwnPerm?->id,
                $profileKpiPerm->id,
            ]);
            $vgYoneticiRole->permissions()->syncWithoutDetaching($vgYoneticiPerms);
        }

        // 5. Veri Girişi Rolü (İbrahim Çelik vb.): view_own (Kendi hedeflerini görme hakkı)
        $veriGirisiRole = RoleM::where('slug', 'veri_girisi')->first();
        if ($veriGirisiRole && $viewOwnPerm) {
            $veriGirisiRole->permissions()->syncWithoutDetaching([$viewOwnPerm->id]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        PermissionM::where('slug', 'kpi_targets.view_profile_kpi')->delete();
    }
};

