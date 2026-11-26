<?php

namespace Tests\Feature;

use App\Models\FormEntryM;
use App\Models\FormFieldM;
use App\Models\FormTemplateM;
use App\Models\MasterDataTypeM;
use App\Models\PermissionM;
use App\Models\RoleM;
use App\Models\UserM;
use App\Services\FormEntryS;
use App\Services\KpiTargetS;
use App\Services\ReportEngineS;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class GeoMapFormEntriesAndYearFilterTest extends TestCase
{
    private UserM $salesUser;
    private UserM $otherUser;
    private FormTemplateM $template;
    private FormFieldM $ulkeField;
    private FormFieldM $isyeriField;
    private FormFieldM $sehirField;
    private FormFieldM $tonajField;
    private FormFieldM $tutarField;

    protected function setUp(): void
    {
        parent::setUp();
        app()->setLocale('tr');

        $this->createMinimalSqliteSchema();

        $adminRole = RoleM::create([
            'name' => 'Sistem Yöneticisi',
            'slug' => 'sistem_yoneticisi',
        ]);

        UserM::create([
            'name' => 'SİSTEM YÖNETİCİSİ',
            'email' => 'admin-geo@koksan.com',
            'password' => bcrypt('password'),
            'role_id' => $adminRole->id,
            'is_active' => true,
            'approval_status' => 'approved',
        ]);

        $salesRole = RoleM::create([
            'name' => 'Satış Temsilcisi',
            'slug' => 'satis_temsilcisi',
        ]);

        $permViewOwn = PermissionM::create([
            'name' => 'Kendi Hedeflerini Gör',
            'slug' => 'kpi_targets.view_own',
            'group' => 'kpi_targets',
        ]);
        $salesRole->permissions()->attach([$permViewOwn->id]);

        $this->salesUser = UserM::create([
            'name' => 'CELAL KARAMAN',
            'email' => 'celal-geo@koksan.com',
            'password' => bcrypt('password'),
            'role_id' => $salesRole->id,
            'is_active' => true,
            'approval_status' => 'approved',
        ]);

        $this->otherUser = UserM::create([
            'name' => 'DİĞER SATIŞÇI',
            'email' => 'other-geo@koksan.com',
            'password' => bcrypt('password'),
            'role_id' => $salesRole->id,
            'is_active' => true,
            'approval_status' => 'approved',
        ]);

        $ulkeType = MasterDataTypeM::create([
            'name' => 'Ülke',
            'slug' => 'ulke',
        ]);

        $isyeriType = MasterDataTypeM::create([
            'name' => 'İŞYERİ',
            'slug' => 'isyeri',
        ]);

        $this->template = FormTemplateM::create([
            'name' => 'HEDEFLER ŞABLONU',
            'slug' => 'hedefler-geo-test',
            'created_by' => $this->salesUser->id,
            'is_active' => true,
            'allow_partition_entry' => true,
        ]);

        $this->ulkeField = FormFieldM::create([
            'form_template_id' => $this->template->id,
            'label' => 'ÜLKE',
            'field_key' => 'ulke',
            'type' => 'master_data',
            'master_data_type_id' => $ulkeType->id,
            'sort_order' => 1,
        ]);

        $this->isyeriField = FormFieldM::create([
            'form_template_id' => $this->template->id,
            'label' => 'İŞYERİ',
            'field_key' => 'isyeri',
            'type' => 'master_data',
            'master_data_type_id' => $isyeriType->id,
            'sort_order' => 2,
        ]);

        $this->sehirField = FormFieldM::create([
            'form_template_id' => $this->template->id,
            'label' => 'ŞEHİR',
            'field_key' => 'sehir',
            'type' => 'master_data',
            'sort_order' => 3,
        ]);

        $this->tonajField = FormFieldM::create([
            'form_template_id' => $this->template->id,
            'label' => 'TONAJ',
            'field_key' => 'tonaj',
            'type' => 'number',
            'sort_order' => 4,
        ]);

        $this->tutarField = FormFieldM::create([
            'form_template_id' => $this->template->id,
            'label' => 'TOPLAM TUTAR',
            'field_key' => 'toplam_tutar',
            'type' => 'number',
            'sort_order' => 5,
        ]);

        $this->template->update(['partition_field_id' => $this->isyeriField->id]);
    }

    public function test_country_iso_code_resolves_turkish_names_including_cyprus_and_iraq(): void
    {
        $engine = app(ReportEngineS::class);

        $this->assertSame('TR', $engine->getCountryIsoCode('Türkiye'));
        $this->assertSame('CY', $engine->getCountryIsoCode('Kıbrıs'));
        $this->assertSame('IQ', $engine->getCountryIsoCode('Irak'));
        $this->assertSame('DE', $engine->getCountryIsoCode('Almanya'));
    }

    public function test_geo_map_connects_master_data_countries_and_workplaces_per_user(): void
    {
        $service = app(FormEntryS::class);

        // Kullanıcı 1 için 2026 KAPAK parçalı giriş (Türkiye ve Kıbrıs)
        $service->saveEntry($this->template, $this->salesUser, '2026-11-26', [
            [
                $this->ulkeField->id => 'Türkiye',
                $this->sehirField->id => 'Gaziantep',
                $this->isyeriField->id => 'KAPAK',
                $this->tonajField->id => '150,5',
                $this->tutarField->id => '300000',
            ],
            [
                $this->ulkeField->id => 'Kıbrıs',
                $this->sehirField->id => 'Gaziantep',
                $this->isyeriField->id => 'KAPAK',
                $this->tonajField->id => '50',
                $this->tutarField->id => '100000',
            ],
        ], 'submitted', null, 'KAPAK');

        // Kullanıcı 1 için 2026 PREFORM giriş (Türkiye)
        $service->saveEntry($this->template, $this->salesUser, '2026-11-26', [
            [
                $this->ulkeField->id => 'Türkiye',
                $this->sehirField->id => 'Gümüşhane',
                $this->isyeriField->id => 'PREFORM',
                $this->tonajField->id => '200',
                $this->tutarField->id => '400000',
            ],
        ], 'approved', null, 'PREFORM');

        // Başka kullanıcıya ait Irak girişi -> Kullanıcı 1 haritasına karışmamalı
        $service->saveEntry($this->template, $this->otherUser, '2026-11-26', [
            [
                $this->ulkeField->id => 'Irak',
                $this->sehirField->id => 'Gaziantep',
                $this->isyeriField->id => 'KAPAK',
                $this->tonajField->id => '999',
                $this->tutarField->id => '999999',
            ],
        ], 'approved', null, 'KAPAK');

        $kpiService = app(KpiTargetS::class);
        $geo2026 = $kpiService->getGeographicDistributionData($this->salesUser->id, 2026);

        $countries = $geo2026['countries'];

        // Sadece kullanıcının veri girdiği ülkeler olmalı (Almanya/Amerika/Irak vb. hayalet ülke yok)
        $this->assertArrayHasKey('TR', $countries);
        $this->assertArrayHasKey('CY', $countries);
        $this->assertArrayNotHasKey('IQ', $countries);
        $this->assertArrayNotHasKey('DE', $countries);

        // Türkiye verisi + işyeri kırılımı (PREFORM + KAPAK)
        $tr = $countries['TR'];
        $this->assertSame(2, $tr['entry_rows']);
        $this->assertEqualsWithDelta(350.5, $tr['total_tonaj'], 0.01);
        $this->assertNotEmpty($tr['color']);
        $this->assertNotSame('#e2e8f0', $tr['color']);

        $wpNames = array_column($tr['workplaces'], 'name');
        $this->assertContains('KAPAK', $wpNames);
        $this->assertContains('PREFORM', $wpNames);
    }

    public function test_my_targets_includes_2027_entry_year_and_filters_geo_map_by_selected_year(): void
    {
        $service = app(FormEntryS::class);

        $service->saveEntry($this->template, $this->salesUser, '2026-06-15', [
            [
                $this->ulkeField->id => 'Türkiye',
                $this->isyeriField->id => 'KAPAK',
                $this->tonajField->id => '120',
                $this->tutarField->id => '240000',
            ],
        ], 'submitted', null, 'KAPAK');

        $service->saveEntry($this->template, $this->salesUser, '2027-01-10', [
            [
                $this->ulkeField->id => 'Irak',
                $this->isyeriField->id => 'LEVHA',
                $this->tonajField->id => '500',
                $this->tutarField->id => '850000',
            ],
        ], 'submitted', null, 'LEVHA');

        // 2026 görünümü: 2027 seçeneği sunulmalı ve haritada Türkiye görünmeli (Irak değil)
        $resp2026 = $this->actingAs($this->salesUser)
            ->get(route('my-targets.index', ['year' => 2026]));

        $resp2026->assertOk();
        $resp2026->assertSee('2027 Dönemi');
        $resp2026->assertSee('Türkiye');
        $resp2026->assertSee('KAPAK');

        // 2027 görünümü: Sadece Irak / LEVHA görünmeli
        $resp2027 = $this->actingAs($this->salesUser)
            ->get(route('my-targets.index', ['year' => 2027]));

        $resp2027->assertOk();
        $resp2027->assertSee('2027 Dönemi');
        $resp2027->assertSee('Irak');
        $resp2027->assertSee('LEVHA');
        $resp2027->assertViewHas('geoData', function ($geoData) {
            return $geoData['year'] === 2027
                && isset($geoData['countries']['IQ'])
                && !isset($geoData['countries']['TR']);
        });
    }

    private function createMinimalSqliteSchema(): void
    {
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->timestamps();
        });

        Schema::create('permissions', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('group')->nullable();
            $table->timestamps();
        });

        Schema::create('role_permission', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('role_id');
            $table->unsignedBigInteger('permission_id');
            $table->timestamps();
        });

        Schema::create('user_permission', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('permission_id');
            $table->boolean('allowed')->default(true);
            $table->timestamps();
        });

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->unsignedBigInteger('role_id')->nullable();
            $table->unsignedBigInteger('group_id')->nullable();
            $table->boolean('is_active')->default(true);
            $table->string('approval_status')->default('approved');
            $table->json('preferences')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('groups', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('group_manager', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('group_id');
            $table->unsignedBigInteger('user_id');
            $table->timestamps();
        });

        Schema::create('master_data_types', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->nullable();
            $table->unsignedBigInteger('parent_id')->nullable();
            $table->timestamps();
        });

        Schema::create('master_data_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('type_id');
            $table->unsignedBigInteger('parent_id')->nullable();
            $table->string('name');
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('form_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->nullable();
            $table->text('description')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('show_row_totals')->default(false);
            $table->boolean('allow_partition_entry')->default(false);
            $table->unsignedBigInteger('partition_field_id')->nullable();
            $table->timestamps();
        });

        Schema::create('form_fields', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('form_template_id');
            $table->string('label');
            $table->string('field_key');
            $table->string('type');
            $table->string('value_type')->nullable();
            $table->text('options')->nullable();
            $table->unsignedBigInteger('master_data_type_id')->nullable();
            $table->boolean('is_required')->default(false);
            $table->boolean('is_disabled')->default(false);
            $table->boolean('is_summable')->default(false);
            $table->integer('sort_order')->default(0);
            $table->unsignedBigInteger('condition_field_id')->nullable();
            $table->string('condition_value')->nullable();
            $table->boolean('is_disabled_on_condition')->default(false);
            $table->string('disabled_condition_value')->nullable();
            $table->boolean('is_calculated')->default(false);
            $table->string('calculation_formula')->nullable();
            $table->unsignedBigInteger('calc_condition_field_id')->nullable();
            $table->string('calc_condition_value')->nullable();
            $table->boolean('filter_by_user_group')->default(false);
            $table->timestamps();
        });

        Schema::create('form_entries', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('form_template_id');
            $table->unsignedBigInteger('parent_id')->nullable();
            $table->unsignedBigInteger('partition_field_id')->nullable();
            $table->string('partition_value')->nullable();
            $table->string('title')->nullable();
            $table->boolean('is_consolidated')->default(false);
            $table->unsignedBigInteger('user_id');
            $table->date('entry_date');
            $table->string('status')->default('draft');
            $table->text('rejection_reason')->nullable();
            $table->json('template_snapshot')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamps();
        });

        Schema::create('form_entry_rows', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('form_entry_id');
            $table->integer('row_order')->default(0);
            $table->timestamps();
        });

        Schema::create('form_entry_values', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('form_entry_row_id');
            $table->unsignedBigInteger('form_field_id');
            $table->text('value')->nullable();
            $table->timestamps();
        });

        Schema::create('form_entry_versions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('form_entry_id');
            $table->integer('version_number');
            $table->string('status')->nullable();
            $table->string('action_type')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->integer('row_count')->default(0);
            $table->json('rows_data')->nullable();
            $table->string('summary')->nullable();
            $table->timestamps();
        });

        Schema::create('kpi_targets', function (Blueprint $table) {
            $table->id();
            $table->string('title')->nullable();
            $table->unsignedBigInteger('form_template_id')->nullable();
            $table->unsignedBigInteger('target_field_id')->nullable();
            $table->string('assignee_type')->nullable();
            $table->unsignedBigInteger('assignee_id')->nullable();
            $table->integer('target_year')->default(2026);
            $table->decimal('target_value', 18, 2)->default(0);
            $table->string('unit')->nullable();
            $table->json('criteria_json')->nullable();
            $table->string('status')->default('active');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
        });

        Schema::create('kpi_target_periods', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('kpi_target_id');
            $table->integer('period_month')->default(0);
            $table->decimal('target_value', 18, 2)->default(0);
            $table->timestamps();
        });

        Schema::create('kpi_target_realizations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('kpi_target_id');
            $table->integer('realization_month')->default(0);
            $table->integer('realization_year')->default(2026);
            $table->decimal('actual_value', 18, 2)->default(0);
            $table->decimal('achievement_rate', 8, 2)->default(0);
            $table->timestamps();
        });

        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('action');
            $table->text('description')->nullable();
            $table->string('model_type')->nullable();
            $table->unsignedBigInteger('model_id')->nullable();
            $table->string('ip_address')->nullable();
            $table->timestamps();
        });

        Schema::create('notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('type');
            $table->string('notifiable_type');
            $table->unsignedBigInteger('notifiable_id');
            $table->text('data');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });
    }
}
