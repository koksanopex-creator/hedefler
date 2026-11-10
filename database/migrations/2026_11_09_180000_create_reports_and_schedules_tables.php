<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Kayıtlı Rapor Kuralları & Varyantları Tablosu
        if (!Schema::hasTable('report_rules')) {
            Schema::create('report_rules', function (Blueprint $table) {
                $table->id();
                $table->string('title');
                $table->text('description')->nullable();
                $table->foreignId('form_template_id')->constrained('form_templates')->onDelete('cascade');
                
                // Birincil Kırılım (X Ekseni / Ana Boyut)
                $table->unsignedBigInteger('dimension_x_field_id')->nullable();
                $table->string('dimension_x_type', 50)->default('field'); // 'field', 'user', 'entry_date_month', 'entry_date_year', 'entry_date_day'
                
                // İkincil Kırılım (Karşılaştırma / Seri Boyutu - Opsiyonel)
                $table->unsignedBigInteger('dimension_series_field_id')->nullable();
                $table->string('dimension_series_type', 50)->default('none'); // 'none', 'field', 'user', 'entry_date_month', 'group'
                
                // Hesaplanacak Metrik & Agregasyon Fonksiyonu
                $table->unsignedBigInteger('metric_field_id')->nullable(); // null ise kayıt adedi (count)
                $table->string('metric_aggregation', 20)->default('sum'); // 'sum', 'avg', 'count', 'min', 'max'
                
                // Grafik & Görselleştirme Türü
                $table->string('chart_type', 30)->default('bar'); // 'bar', 'horizontal_bar', 'stacked_bar', 'line', 'pie', 'doughnut', 'radar', 'table'
                
                // Kapsamlı Filtreler (Kullanıcılar, Gruplar, Tarih Aralığı, Dinamik Değer Filtreleri)
                $table->json('filters_json')->nullable();
                
                $table->boolean('is_public')->default(true);
                $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
                $table->timestamps();
            });
        }

        // 2. Zamanlanmış Otomatik Rapor Gönderim Kuralları Tablosu
        if (!Schema::hasTable('report_schedules')) {
            Schema::create('report_schedules', function (Blueprint $table) {
                $table->id();
                $table->foreignId('report_rule_id')->constrained('report_rules')->onDelete('cascade');
                $table->string('title');
                $table->string('frequency', 20)->default('weekly'); // 'hourly', 'daily', 'weekly', 'monthly'
                $table->string('execution_time', 10)->default('09:00'); // '09:00'
                $table->integer('execution_day')->nullable(); // 1=Pazartesi, 7=Pazar (weekly) veya 1-31 (monthly)
                
                // Alıcı Tanımları (Roller, Grup Yöneticileri, Belirli Kullanıcılar, Manuel E-Postalar)
                $table->json('recipient_roles')->nullable();
                $table->json('recipient_groups')->nullable();
                $table->json('recipient_users')->nullable();
                $table->text('custom_emails')->nullable();
                
                $table->boolean('include_excel')->default(true);
                $table->boolean('is_active')->default(true);
                $table->timestamp('last_run_at')->nullable();
                $table->timestamp('next_run_at')->nullable();
                $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
                $table->timestamps();
            });
        }

        // 3. Zamanlanmış Gönderim Logları Tablosu
        if (!Schema::hasTable('report_schedule_logs')) {
            Schema::create('report_schedule_logs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('report_schedule_id')->constrained('report_schedules')->onDelete('cascade');
                $table->string('status', 20)->default('success'); // 'success', 'failed'
                $table->integer('recipients_count')->default(0);
                $table->text('recipients_summary')->nullable();
                $table->text('error_message')->nullable();
                $table->timestamp('executed_at');
                $table->timestamp('created_at')->nullable();
            });
        }

        // 4. Yetki Matrisi Tanımları
        $permissions = [
            [
                'slug' => 'reports.view',
                'name' => 'Raporlama Motorunu ve Raporları Görüntüleme',
                'module' => 'reports',
                'action' => 'view',
            ],
            [
                'slug' => 'reports.create',
                'name' => 'Dinamik Rapor Kuralı / Varyantı Oluşturma ve Kaydetme',
                'module' => 'reports',
                'action' => 'create',
            ],
            [
                'slug' => 'reports.export',
                'name' => 'Raporları Excel ve PDF Olarak Dışa Aktarma',
                'module' => 'reports',
                'action' => 'export',
            ],
            [
                'slug' => 'reports.share',
                'name' => 'Raporu Anlık Olarak E-Posta ile Paylaşma',
                'module' => 'reports',
                'action' => 'share',
            ],
            [
                'slug' => 'reports.schedule',
                'name' => 'Zamanlanmış Rapor Gönderim Kuralı Oluşturma ve Yönetme',
                'module' => 'reports',
                'action' => 'schedule',
            ],
        ];

        $permIds = [];
        foreach ($permissions as $p) {
            $existing = DB::table('permissions')->where('slug', $p['slug'])->first();
            if ($existing) {
                $permIds[$p['slug']] = $existing->id;
            } else {
                $permIds[$p['slug']] = DB::table('permissions')->insertGetId([
                    'name' => $p['name'],
                    'slug' => $p['slug'],
                    'module' => $p['module'],
                    'action' => $p['action'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        // Sistem Yöneticisi rolüne tüm rapor yetkilerini ata
        $adminRole = DB::table('roles')->where('slug', 'sistem_yoneticisi')->orWhere('id', 1)->first();
        if ($adminRole) {
            foreach ($permIds as $pId) {
                $has = DB::table('role_permission')
                    ->where('role_id', $adminRole->id)
                    ->where('permission_id', $pId)
                    ->exists();
                if (!$has) {
                    DB::table('role_permission')->insert([
                        'role_id' => $adminRole->id,
                        'permission_id' => $pId,
                    ]);
                }
            }
        }

        // Yönetim (Read Only) rolüne görüntüleme ve dışa aktarma yetkilerini ata
        $yonetimRole = DB::table('roles')->where('slug', 'yonetim')->first();
        if ($yonetimRole) {
            foreach (['reports.view', 'reports.export'] as $slug) {
                if (isset($permIds[$slug])) {
                    $has = DB::table('role_permission')
                        ->where('role_id', $yonetimRole->id)
                        ->where('permission_id', $permIds[$slug])
                        ->exists();
                    if (!$has) {
                        DB::table('role_permission')->insert([
                            'role_id' => $yonetimRole->id,
                            'permission_id' => $permIds[$slug],
                        ]);
                    }
                }
            }
        }

        // Veri Giriş Yöneticisi rolüne görüntüleme, oluşturma ve dışa aktarma ata
        $mgrRole = DB::table('roles')->where('slug', 'veri_giris_yoneticisi')->first();
        if ($mgrRole) {
            foreach (['reports.view', 'reports.create', 'reports.export', 'reports.share'] as $slug) {
                if (isset($permIds[$slug])) {
                    $has = DB::table('role_permission')
                        ->where('role_id', $mgrRole->id)
                        ->where('permission_id', $permIds[$slug])
                        ->exists();
                    if (!$has) {
                        DB::table('role_permission')->insert([
                            'role_id' => $mgrRole->id,
                            'permission_id' => $permIds[$slug],
                        ]);
                    }
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('report_schedule_logs');
        Schema::dropIfExists('report_schedules');
        Schema::dropIfExists('report_rules');

        $slugs = ['reports.view', 'reports.create', 'reports.export', 'reports.share', 'reports.schedule'];
        $permIds = DB::table('permissions')->whereIn('slug', $slugs)->pluck('id');
        DB::table('role_permission')->whereIn('permission_id', $permIds)->delete();
        DB::table('permissions')->whereIn('id', $permIds)->delete();
    }
};

