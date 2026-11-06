<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // 1. form_revision_requests tablosu
        if (!Schema::hasTable('form_revision_requests')) {
            Schema::create('form_revision_requests', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('form_entry_id')->index();
                $table->unsignedBigInteger('user_id')->index(); // Talep eden kullanıcı
                $table->text('reason'); // Düzenleme gerekçesi
                $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
                $table->unsignedBigInteger('reviewed_by')->nullable(); // Onaylayan/reddeden yönetici
                $table->text('rejection_reason')->nullable(); // Red gerekçesi
                $table->timestamp('reviewed_at')->nullable();
                $table->boolean('is_dismissed_by_user')->default(false); // Kullanıcının red uyarısını kapatması
                $table->timestamps();

                $table->foreign('form_entry_id')->references('id')->on('form_entries')->onDelete('cascade');
                $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
                $table->foreign('reviewed_by')->references('id')->on('users')->onDelete('set null');
            });
        }

        // 2. Yeni Yetkileri permissions tablosuna ekle
        $newPermissions = [
            [
                'module' => 'form_entries',
                'action' => 'approve_group',
                'slug'   => 'form_entries.approve_group',
                'name'   => 'Grup Personellerinin Formlarını Onaylama/Reddetme (Kendi Grubu)',
            ],
            [
                'module' => 'form_entries',
                'action' => 'manage_revision_requests',
                'slug'   => 'form_entries.manage_revision_requests',
                'name'   => 'Form Revizyon / Düzenleme Taleplerini Onaylama/Reddetme',
            ],
            [
                'module' => 'dashboard',
                'action' => 'kpi_cockpit',
                'slug'   => 'dashboard.kpi_cockpit',
                'name'   => 'Dashboard: KPI ve Hedef Başarı Kokpiti',
            ],
            [
                'module' => 'dashboard',
                'action' => 'date_cockpit',
                'slug'   => 'dashboard.date_cockpit',
                'name'   => 'Dashboard: Dönem ve Zaman Filtre Çubuğu',
            ],
        ];

        foreach ($newPermissions as $p) {
            $existing = DB::table('permissions')->where('slug', $p['slug'])->first();
            if (!$existing) {
                $permId = DB::table('permissions')->insertGetId([
                    'module'     => $p['module'],
                    'action'     => $p['action'],
                    'slug'       => $p['slug'],
                    'name'       => $p['name'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                // Varsayılan rol atamaları:
                // Sistem Yöneticisi (1): Hepsini alır
                DB::table('role_permission')->updateOrInsert(['role_id' => 1, 'permission_id' => $permId]);

                // Yönetim (2): Dashboard ve revizyon yetkilerini alır
                if (in_array($p['slug'], ['dashboard.kpi_cockpit', 'dashboard.date_cockpit', 'form_entries.manage_revision_requests'])) {
                    DB::table('role_permission')->updateOrInsert(['role_id' => 2, 'permission_id' => $permId]);
                }

                // Veri Giriş Yöneticisi (3): Grup onayı, dashboard ve revizyon yetkilerini alır
                if (in_array($p['slug'], ['form_entries.approve_group', 'form_entries.manage_revision_requests', 'dashboard.kpi_cockpit', 'dashboard.date_cockpit'])) {
                    DB::table('role_permission')->updateOrInsert(['role_id' => 3, 'permission_id' => $permId]);
                }

                // Veri Girişi (4): Sadece Dashboard kısımlarını varsayılan olarak alır (yönetici matristen kapatabilir)
                if (in_array($p['slug'], ['dashboard.kpi_cockpit', 'dashboard.date_cockpit'])) {
                    DB::table('role_permission')->updateOrInsert(['role_id' => 4, 'permission_id' => $permId]);
                }
            }
        }

        // 3. Bildirim Olaylarını notification_events ve notification_templates tablosuna ekle
        $events = [
            [
                'slug' => 'form.revision_requested',
                'name' => 'Form Düzenleme (Revizyon) Talebi İletildi',
                'subject' => '{user_name} Form Düzenleme Talebinde Bulundu - {form_name}',
                'body' => "Merhaba,\n\n{user_name} adlı kullanıcı, {entry_date} tarihli '{form_name}' formu için düzenleme (revizyon) talebinde bulunmuştur.\n\nTalep Gerekçesi:\n{revision_reason}\n\nTalebi değerlendirmek için sisteme giriş yapabilirsiniz:\n{action_url}\n\nİyi çalışmalar.",
                'in_app_subject' => 'Form Düzenleme Talebi: {user_name}',
                'in_app_body' => "{user_name}, '{form_name}' formu için düzenleme talep etti. Gerekçe: {revision_reason}",
            ],
            [
                'slug' => 'form.revision_approved',
                'name' => 'Form Düzenleme Talebi Onaylandı',
                'subject' => "Düzenleme Talebiniz Onaylandı: '{form_name}'",
                'body' => "Merhaba {user_name},\n\n'{form_name}' formu için ilettiğiniz düzenleme (revizyon) talebi yöneticiniz tarafından ONAYLANMIŞTIR.\n\nFormunuz düzenlemeye açılmıştır, gerekli düzeltmeleri yaparak tekrar kaydedebilirsiniz:\n{action_url}\n\nİyi çalışmalar.",
                'in_app_subject' => 'Düzenleme Talebiniz Onaylandı',
                'in_app_body' => "'{form_name}' formu için ilettiğiniz düzenleme talebi onaylandı. Formu güncelleyebilirsiniz.",
            ],
            [
                'slug' => 'form.revision_rejected',
                'name' => 'Form Düzenleme Talebi Reddedildi',
                'subject' => "Düzenleme Talebiniz Reddedildi: '{form_name}'",
                'body' => "Merhaba {user_name},\n\n'{form_name}' formu için ilettiğiniz düzenleme (revizyon) talebi yöneticiniz tarafından REDDEDİLMİŞTİR.\n\nRed Gerekçesi:\n{rejection_reason}\n\nDetayları görüntülemek için tıklayınız:\n{action_url}\n\nİyi çalışmalar.",
                'in_app_subject' => 'Düzenleme Talebiniz Reddedildi',
                'in_app_body' => "'{form_name}' formu için düzenleme talebiniz reddedildi. Gerekçe: {rejection_reason}",
            ],
        ];

        foreach ($events as $ev) {
            $existingEv = DB::table('notification_events')->where('slug', $ev['slug'])->first();
            if (!$existingEv) {
                $evId = DB::table('notification_events')->insertGetId([
                    'name'       => $ev['name'],
                    'slug'       => $ev['slug'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                DB::table('notification_templates')->insert([
                    'notification_event_id' => $evId,
                    'subject'               => $ev['subject'],
                    'body'                  => $ev['body'],
                    'in_app_subject'        => $ev['in_app_subject'],
                    'in_app_body'           => $ev['in_app_body'],
                    'role_templates'        => json_encode([]),
                    'created_at'            => now(),
                    'updated_at'            => now(),
                ]);

                // Varsayılan bildirim matrisi kuralları
                if ($ev['slug'] === 'form.revision_requested') {
                    // Yöneticilere bildir
                    DB::table('notification_matrix')->insert([
                        'notification_event_id' => $evId,
                        'target_type'           => 'special:group_manager',
                        'send_in_app'           => 1,
                        'send_email'            => 1,
                        'created_at'            => now(),
                        'updated_at'            => now(),
                    ]);
                    DB::table('notification_matrix')->insert([
                        'notification_event_id' => $evId,
                        'target_type'           => 'role:sistem_yoneticisi',
                        'send_in_app'           => 1,
                        'send_email'            => 1,
                        'created_at'            => now(),
                        'updated_at'            => now(),
                    ]);
                } else {
                    // Kullanıcıya bildir
                    DB::table('notification_matrix')->insert([
                        'notification_event_id' => $evId,
                        'target_type'           => 'special:creator',
                        'send_in_app'           => 1,
                        'send_email'            => 1,
                        'created_at'            => now(),
                        'updated_at'            => now(),
                    ]);
                }
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('form_revision_requests');
    }
};
