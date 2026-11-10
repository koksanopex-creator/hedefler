<?php

namespace Database\Seeders;

use App\Models\PermissionM;
use Illuminate\Database\Seeder;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        $matrix = [
            ['users',           'view_all',      'Tüm Şirket Kullanıcılarını ve Grupları Görüntüleme (Genel Rehber)'],
            ['users',           'view_group',    'Sadece Kendi Grubundaki Kullanıcıları Görüntüleme (Grup İzolasyonu)'],
            ['users',           'manage',        'Kullanıcı Yönetimi (Ekleme, Düzenleme, Rol/Durum Değiştirme)'],
            ['groups',          'manage',        'Grup Yönetimi'],
            ['roles',           'manage',        'Rol / Yetki Matrisi Yönetimi'],
            ['form_templates',  'manage',        'Form Tasarımı Yönetimi'],
            ['form_schedules',  'manage',        'Form Açma/Kapama Takvimi Yönetimi'],
            ['form_entries',    'view_own',      'Sadece Kendi Verisini Görüntüleme'],
            ['form_entries',    'view_group',    'Grup Verisini Görüntüleme'],
            ['form_entries',    'view_all',      'Tüm Verileri Görüntüleme'],
            ['form_entries',    'view_approver', 'Onaylayan Kişiyi Görüntüleme'],
            ['form_entries',    'view_logs',     'Form İşlem Geçmişini (Log) Görüntüleme'],
            ['form_entries',    'view_history',  'Form Tarihçesini ve Eski Kayıtları Görüntüleme'],
            ['form_entries',    'create',        'Veri Girişi Yapma'],
            ['form_entries',    'edit_own',      'Kendi Verisini Düzenleme'],
            ['form_entries',    'edit_group',    'Grup Verisini Düzenleme'],
            ['form_entries',    'approve',       'Veri Onaylama/Reddetme'],
            ['form_entries',    'consolidate',   'Parçalı Formları Birleştirme ve Dağıtma (Konsolidasyon)'],
            ['form_entries',    'delete',        'Veri (Form Kaydı) Silme'],
            ['form_templates',  'view_targets',  'Form Hedef Kitlesini Görüntüleme'],
            ['form_templates',  'delete',        'Form Tasarımı Silme'],
            ['exports',         'manage',        'Excel Export Alma'],
            ['logs',            'view',          'Sistem Loglarını Görüntüleme'],
            ['dashboard',       'date_cockpit',     'Dashboard: Üst Filtre Barı (Dönem & Tarih Kokpiti)'],
            ['dashboard',       'kpi_cockpit',      'Dashboard: KPI Başarı Kokpiti (Bireysel / Grup Performans Kartı)'],
            ['dashboard',       'summary_cards',    'Dashboard: Özet Kartları'],
            ['dashboard',       'status_chart',     'Dashboard: Durum Dağılımı Grafiği'],
            ['dashboard',       'trend_chart',      'Dashboard: Aylık Trend Grafiği'],
            ['dashboard',       'template_chart',   'Dashboard: Form Bazlı Dağılım'],
            ['dashboard',       'group_chart',      'Dashboard: Grup Bazlı Karşılaştırma'],
            ['dashboard',       'leaderboard',      'Dashboard: Liderlik Tablosu'],
            ['dashboard',       'pending_approvals','Dashboard: Onay Bekleyen Formlar'],
            ['dashboard',       'recent_activity',  'Dashboard: Son Aktiviteler'],
            ['dashboard',       'my_stats',         'Dashboard: Kişisel İstatistiklerim'],
            ['dashboard',       'view_group_users', 'Dashboard: Grubundaki Kullanıcıları Görüntüleme'],
            ['dashboard',       'view_group_manager','Dashboard: Grup Yöneticisini Görüntüleme'],
        ];

        foreach ($matrix as [$module, $action, $name]) {
            PermissionM::updateOrCreate(
                ['slug' => "{$module}.{$action}"],
                ['module' => $module, 'action' => $action, 'name' => $name]
            );
        }
    }
}
