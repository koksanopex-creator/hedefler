<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Bildirim Olayları: application.approved ve application.rejected
        $events = [
            [
                'slug' => 'application.approved',
                'name' => 'Uygulama Başvurusu Onaylandı',
                'subject' => 'Hedefler Uygulaması - Başvurunuz Onaylandı',
                'body' => "Sayın <strong>{kullanici_adi}</strong>,<br><br>Hedefler uygulaması için yapmış olduğunuz erişim başvurusu onaylanmıştır.<br><br><strong>Tanımlanan Rol:</strong> {rol_adi}<br><strong>Atanan Grup:</strong> {grup_adi}<br><br>Aşağıdaki bağlantıya tıklayarak sisteme giriş yapabilirsiniz.<br><br>{islem_linki}",
            ],
            [
                'slug' => 'application.rejected',
                'name' => 'Uygulama Başvurusu Reddedildi',
                'subject' => 'Hedefler Uygulaması - Başvuru Sonucu',
                'body' => "Sayın <strong>{kullanici_adi}</strong>,<br><br>Hedefler uygulaması erişim başvurunuz değerlendirilmiş olup <strong>{red_turu}</strong> olarak reddedilmiştir.<br><br><strong>Ret Gerekçesi:</strong> {red_nedeni}<br><br>Bilgilerinize sunarız.",
            ]
        ];

        foreach ($events as $evt) {
            $existing = DB::table('notification_events')->where('slug', $evt['slug'])->first();
            $eventId = $existing?->id;

            if (!$existing) {
                $eventId = DB::table('notification_events')->insertGetId([
                    'name' => $evt['name'],
                    'slug' => $evt['slug'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            // Şablon kontrolü ve ekleme
            $tplExists = DB::table('notification_templates')->where('notification_event_id', $eventId)->exists();
            if (!$tplExists) {
                DB::table('notification_templates')->insert([
                    'notification_event_id' => $eventId,
                    'subject' => $evt['subject'],
                    'body' => $evt['body'],
                    'in_app_subject' => $evt['name'],
                    'in_app_body' => $evt['body'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            // Bildirim Matrisi kuralı ('applicant' hedefi için e-posta açık olarak eklenir)
            $matrixExists = DB::table('notification_matrix')
                ->where('notification_event_id', $eventId)
                ->where('target_type', 'applicant')
                ->exists();

            if (!$matrixExists) {
                DB::table('notification_matrix')->insert([
                    'notification_event_id' => $eventId,
                    'target_type' => 'applicant',
                    'send_email' => 1,
                    'send_in_app' => 0,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        // 2. Yetki: system.active_sessions
        $perm = DB::table('permissions')->where('slug', 'system.active_sessions')->first();
        $permId = $perm?->id;

        if (!$perm) {
            $permId = DB::table('permissions')->insertGetId([
                'name' => 'Aktif Oturumlar ve Giriş İzleme (Uygulama Login Takibi)',
                'slug' => 'system.active_sessions',
                'module' => 'system',
                'action' => 'active_sessions',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Sistem Yöneticisi rolüne (ID: 1 veya slug: 'sistem_yoneticisi') bu yetkiyi ata
        $adminRole = DB::table('roles')->where('slug', 'sistem_yoneticisi')->orWhere('id', 1)->first();
        if ($adminRole && $permId) {
            $hasRolePerm = DB::table('role_permission')
                ->where('role_id', $adminRole->id)
                ->where('permission_id', $permId)
                ->exists();

            if (!$hasRolePerm) {
                DB::table('role_permission')->insert([
                    'role_id' => $adminRole->id,
                    'permission_id' => $permId,
                ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $perm = DB::table('permissions')->where('slug', 'system.active_sessions')->first();
        if ($perm) {
            DB::table('role_permission')->where('permission_id', $perm->id)->delete();
            DB::table('user_permission')->where('permission_id', $perm->id)->delete();
            DB::table('permissions')->where('id', $perm->id)->delete();
        }

        $eventIds = DB::table('notification_events')
            ->whereIn('slug', ['application.approved', 'application.rejected'])
            ->pluck('id');

        if ($eventIds->isNotEmpty()) {
            DB::table('notification_matrix')->whereIn('notification_event_id', $eventIds)->delete();
            DB::table('notification_templates')->whereIn('notification_event_id', $eventIds)->delete();
            DB::table('notification_events')->whereIn('id', $eventIds)->delete();
        }
    }
};
