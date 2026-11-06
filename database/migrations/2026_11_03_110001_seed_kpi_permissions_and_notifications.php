<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use App\Models\PermissionM;
use App\Models\RoleM;
use App\Models\NotificationEventM;
use App\Models\NotificationTemplateM;
use App\Models\NotificationMatrixM;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. KPI İzinlerini permissions tablosuna ekle
        $permissions = [
            [
                'module' => 'kpi_targets',
                'action' => 'manage',
                'slug' => 'kpi_targets.manage',
                'name' => 'KPI Hedefleri Tam Yönetimi',
            ],
            [
                'module' => 'kpi_targets',
                'action' => 'create',
                'slug' => 'kpi_targets.create',
                'name' => 'Yeni Hedef Tanımlama',
            ],
            [
                'module' => 'kpi_targets',
                'action' => 'edit',
                'slug' => 'kpi_targets.edit',
                'name' => 'Hedef Düzenleme',
            ],
            [
                'module' => 'kpi_targets',
                'action' => 'delete',
                'slug' => 'kpi_targets.delete',
                'name' => 'Hedef Silme',
            ],
            [
                'module' => 'kpi_targets',
                'action' => 'assign',
                'slug' => 'kpi_targets.assign',
                'name' => 'Hedef Atama',
            ],
            [
                'module' => 'kpi_targets',
                'action' => 'distribute',
                'slug' => 'kpi_targets.distribute',
                'name' => 'Aylara Dağıtım Yapma',
            ],
            [
                'module' => 'kpi_targets',
                'action' => 'view_all',
                'slug' => 'kpi_targets.view_all',
                'name' => 'Tüm Hedefleri ve Karneleri Görüntüleme',
            ],
            [
                'module' => 'kpi_targets',
                'action' => 'view_group',
                'slug' => 'kpi_targets.view_group',
                'name' => 'Grup Hedeflerini Görüntüleme',
            ],
            [
                'module' => 'kpi_targets',
                'action' => 'view_own',
                'slug' => 'kpi_targets.view_own',
                'name' => 'Kendi Hedeflerini Görüntüleme',
            ],
            [
                'module' => 'kpi_targets',
                'action' => 'recalculate',
                'slug' => 'kpi_targets.recalculate',
                'name' => 'Gerçekleşmeleri Yeniden Hesaplatma',
            ],
        ];

        $createdPermissionIds = [];
        foreach ($permissions as $p) {
            $perm = PermissionM::firstOrCreate(
                ['slug' => $p['slug']],
                [
                    'module' => $p['module'],
                    'action' => $p['action'],
                    'name' => $p['name'],
                ]
            );
            $createdPermissionIds[] = $perm->id;
        }

        // Sistem Yöneticisi Rolüne tüm KPI izinlerini ata
        $adminRole = RoleM::where('slug', 'sistem_yoneticisi')->first();
        if ($adminRole) {
            $adminRole->permissions()->syncWithoutDetaching($createdPermissionIds);
        }

        // Yönetici Rolüne yönetim ve izleme yetkilerini ata
        $managerRole = RoleM::where('slug', 'yonetici')->first();
        if ($managerRole) {
            $managerPermSlugs = [
                'kpi_targets.view_all',
                'kpi_targets.view_group',
                'kpi_targets.view_own',
                'kpi_targets.distribute',
                'kpi_targets.create',
                'kpi_targets.edit',
            ];
            $managerPermIds = PermissionM::whereIn('slug', $managerPermSlugs)->pluck('id')->toArray();
            $managerRole->permissions()->syncWithoutDetaching($managerPermIds);
        }

        // Standart Kullanıcı Rolüne kendi hedeflerini görme yetkisini ata
        $userRole = RoleM::where('slug', 'standart_kullanici')->first();
        if ($userRole) {
            $ownPerm = PermissionM::where('slug', 'kpi_targets.view_own')->first();
            if ($ownPerm) {
                $userRole->permissions()->syncWithoutDetaching([$ownPerm->id]);
            }
        }

        // 2. KPI Bildirim Olaylarını notification_events tablosuna ekle
        $events = [
            [
                'slug' => 'kpi.assigned',
                'name' => 'Yeni KPI Hedefi Atandı',
                'subject' => 'Yeni KPI Hedefi Atandı: {hedef_basligi} ({hedef_yili})',
                'in_app_subject' => 'Yeni KPI Hedefi: {hedef_basligi}',
                'body' => '<p>Merhaba {alici_adi},</p><p>Tarafınıza <strong>{hedef_yili}</strong> dönemi için <strong>{hedef_basligi}</strong> kapsamında yeni bir KPI hedefi tanımlanmıştır.</p><p><strong>Hedef Detayları:</strong></p><ul><li>Form Şablonu: <strong>{sablon_adi}</strong></li><li>Hedef Alanı: <strong>{hedef_alani}</strong></li><li>Hedef Değeri: <strong>{hedef_degeri} {birim}</strong></li><li>Kriter / Kırılım: <strong>{kriter_ozeti}</strong></li><li>Hedefi Belirleyen: <strong>{atayan_kisi}</strong></li></ul><p>{islem_linki}</p>',
                'in_app_body' => '{hedef_yili} yılı için {hedef_degeri} {birim} tutarında {hedef_basligi} hedefiniz tanımlandı.',
                'matrix' => [
                    ['target_type' => 'special:target_assignee', 'send_email' => true, 'send_in_app' => true],
                    ['target_type' => 'special:group_manager', 'send_email' => true, 'send_in_app' => true],
                ]
            ],
            [
                'slug' => 'kpi.updated',
                'name' => 'KPI Hedefi Güncellendi',
                'subject' => 'KPI Hedefi Güncellendi: {hedef_basligi}',
                'in_app_subject' => 'KPI Hedefi Güncellendi: {hedef_basligi}',
                'body' => '<p>Merhaba {alici_adi},</p><p><strong>{hedef_basligi}</strong> başlıklı KPI hedefinizde veya aylık dağılımında güncelleme yapılmıştır.</p><p><strong>Güncel Hedef Değeri:</strong> <strong>{hedef_degeri} {birim}</strong> ({hedef_yili})</p><p>{islem_linki}</p>',
                'in_app_body' => '{hedef_basligi} hedefinizin detayları güncellendi. Yeni Hedef: {hedef_degeri} {birim}',
                'matrix' => [
                    ['target_type' => 'special:target_assignee', 'send_email' => false, 'send_in_app' => true],
                    ['target_type' => 'special:group_manager', 'send_email' => false, 'send_in_app' => true],
                ]
            ],
            [
                'slug' => 'kpi.achieved_threshold',
                'name' => 'KPI Hedef Başarısı Eşiğe Ulaştı (%80 / %100)',
                'subject' => 'Tebrikler! KPI Hedef Başarısı: {hedef_basligi} ({basari_orani})',
                'in_app_subject' => 'KPI Hedef Başarısı: {basari_orani}',
                'body' => '<p>Merhaba {alici_adi},</p><p>Tebrik ederiz! <strong>{hedef_basligi}</strong> hedefinizde gerçekleşme oranı <strong>{basari_orani}</strong> seviyesine ulaşmıştır.</p><ul><li>Hedef Değer: <strong>{hedef_degeri} {birim}</strong></li><li>Gerçekleşen: <strong>{gerceklesen_deger} {birim}</strong></li><li>Başarı Oranı: <strong>{basari_orani}</strong></li></ul><p>{islem_linki}</p>',
                'in_app_body' => '{hedef_basligi} hedefinizde {gerceklesen_deger} {birim} gerçekleşme ile {basari_orani} başarıya ulaştınız.',
                'matrix' => [
                    ['target_type' => 'special:target_assignee', 'send_email' => true, 'send_in_app' => true],
                    ['target_type' => 'special:group_manager', 'send_email' => true, 'send_in_app' => true],
                    ['target_type' => 'role:sistem_yoneticisi', 'send_email' => false, 'send_in_app' => true],
                ]
            ],
        ];

        foreach ($events as $eventData) {
            $event = NotificationEventM::firstOrCreate(
                ['slug' => $eventData['slug']],
                ['name' => $eventData['name']]
            );

            NotificationTemplateM::updateOrCreate(
                ['notification_event_id' => $event->id],
                [
                    'subject' => $eventData['subject'],
                    'in_app_subject' => $eventData['in_app_subject'],
                    'body' => $eventData['body'],
                    'in_app_body' => $eventData['in_app_body'],
                ]
            );

            foreach ($eventData['matrix'] as $m) {
                NotificationMatrixM::updateOrCreate(
                    [
                        'notification_event_id' => $event->id,
                        'target_type' => $m['target_type'],
                    ],
                    [
                        'send_email' => $m['send_email'],
                        'send_in_app' => $m['send_in_app'],
                    ]
                );
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $slugs = [
            'kpi_targets.manage',
            'kpi_targets.create',
            'kpi_targets.edit',
            'kpi_targets.delete',
            'kpi_targets.assign',
            'kpi_targets.distribute',
            'kpi_targets.view_all',
            'kpi_targets.view_group',
            'kpi_targets.view_own',
            'kpi_targets.recalculate',
        ];

        PermissionM::whereIn('slug', $slugs)->delete();

        $eventSlugs = ['kpi.assigned', 'kpi.updated', 'kpi.achieved_threshold'];
        $eventIds = NotificationEventM::whereIn('slug', $eventSlugs)->pluck('id');

        NotificationMatrixM::whereIn('notification_event_id', $eventIds)->delete();
        NotificationTemplateM::whereIn('notification_event_id', $eventIds)->delete();
        NotificationEventM::whereIn('id', $eventIds)->delete();
    }
};

