<?php

namespace Database\Seeders;

use App\Models\RoleM;
use App\Models\PermissionM;
use Illuminate\Database\Seeder;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        $map = [
            'sistem_yoneticisi' => PermissionM::query()->pluck('slug')->all(),

            'yonetim' => [
                'users.view',
                'users.view_all',
                'form_entries.view_all',
                'form_entries.view_approver',
                'form_entries.view_logs',
                'form_entries.view_history',
                'form_templates.view_targets',
                'logs.view',
                'kpi_targets.view_all',
                'dashboard.date_cockpit',
                'dashboard.kpi_cockpit',
                'dashboard.summary_cards',
                'dashboard.status_chart',
                'dashboard.trend_chart',
                'dashboard.template_chart',
                'dashboard.group_chart',
                'dashboard.leaderboard',
                'dashboard.pending_approvals',
                'dashboard.recent_activity',
                'dashboard.my_stats',
                'dashboard.view_group_users',
                'dashboard.view_group_manager',
            ],

            'veri_giris_yoneticisi' => [
                'users.view',
                'users.view_group',
                'form_entries.view_own',
                'form_entries.view_group',
                'form_entries.view_approver',
                'form_entries.view_logs',
                'form_entries.view_history',
                'form_templates.view_targets',
                'form_entries.create',
                'form_entries.edit_own',
                'form_entries.edit_group',
                'form_entries.approve',
                'form_entries.approve_group',
                'form_entries.manage_revision_requests',
                'exports.manage',
                'kpi_targets.create',
                'kpi_targets.edit',
                'kpi_targets.distribute',
                'kpi_targets.view_group',
                'kpi_targets.view_own',
                'kpi_targets.recalculate',
                'dashboard.date_cockpit',
                'dashboard.kpi_cockpit',
                'dashboard.summary_cards',
                'dashboard.status_chart',
                'dashboard.trend_chart',
                'dashboard.template_chart',
                'dashboard.group_chart',
                'dashboard.leaderboard',
                'dashboard.pending_approvals',
                'dashboard.recent_activity',
                'dashboard.my_stats',
                'dashboard.view_group_users',
                'dashboard.view_group_manager',
            ],

            'veri_girisi' => [
                'form_entries.view_own',
                'form_entries.create',
                'form_entries.edit_own',
                'kpi_targets.view_own',
                'dashboard.date_cockpit',
                'dashboard.kpi_cockpit',
                'dashboard.summary_cards',
                'dashboard.my_stats',
                'dashboard.view_group_users',
                'dashboard.view_group_manager',
            ],
        ];

        foreach ($map as $roleSlug => $permissionSlugs) {
            $role = RoleM::where('slug', $roleSlug)->firstOrFail();
            $permissionIds = PermissionM::whereIn('slug', $permissionSlugs)->pluck('id');
            $role->permissions()->sync($permissionIds);
        }
    }
}
