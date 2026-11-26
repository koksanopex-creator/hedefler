<?php

namespace Tests\Feature;

use App\Models\FormFieldM;
use App\Models\FormTemplateM;
use App\Models\GroupM;
use App\Models\PermissionM;
use App\Models\RoleM;
use App\Models\UserM;
use App\Services\FormEntryS;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class FormEntriesUserGroupingTest extends TestCase
{
    private UserM $admin;
    private UserM $userAlpha;
    private UserM $userBeta;
    private FormTemplateM $template;
    private FormFieldM $valueField;

    protected function setUp(): void
    {
        parent::setUp();
        app()->setLocale('tr');

        $this->createMinimalSqliteSchema();

        $adminRole = RoleM::create([
            'name' => 'Sistem Yöneticisi',
            'slug' => 'sistem_yoneticisi',
        ]);

        $userRole = RoleM::create([
            'name' => 'Satış Temsilcisi',
            'slug' => 'satis_temsilcisi',
        ]);

        $permViewOwn = PermissionM::create([
            'name' => 'Kendi Kayıtlarını Gör',
            'slug' => 'form_entries.view_own',
            'group' => 'form_entries',
        ]);
        $userRole->permissions()->attach([$permViewOwn->id]);

        $groupA = GroupM::create(['name' => 'YURT İÇİ', 'is_active' => true]);
        $groupB = GroupM::create(['name' => 'YURT DIŞI', 'is_active' => true]);

        $this->admin = UserM::create([
            'name' => 'YÖNETİCİ',
            'email' => 'admin-grp@koksan.com',
            'password' => bcrypt('password'),
            'role_id' => $adminRole->id,
            'is_active' => true,
            'approval_status' => 'approved',
        ]);

        $this->userAlpha = UserM::create([
            'name' => 'HASSAN HUSSIEN',
            'email' => 'hassan-grp@koksan.com',
            'password' => bcrypt('password'),
            'role_id' => $userRole->id,
            'group_id' => $groupB->id,
            'is_active' => true,
            'approval_status' => 'approved',
        ]);

        $this->userBeta = UserM::create([
            'name' => 'SERHAT TÜMER',
            'email' => 'serhat-grp@koksan.com',
            'password' => bcrypt('password'),
            'role_id' => $userRole->id,
            'group_id' => $groupA->id,
            'is_active' => true,
            'approval_status' => 'approved',
        ]);

        $this->template = FormTemplateM::create([
            'name' => 'HEDEFLER ŞABLONU',
            'slug' => 'hedefler-grp-test',
            'created_by' => $this->admin->id,
            'is_active' => true,
        ]);

        $this->valueField = FormFieldM::create([
            'form_template_id' => $this->template->id,
            'label' => 'TONAJ',
            'field_key' => 'tonaj',
            'type' => 'number',
            'sort_order' => 1,
        ]);
    }

    public function test_drafts_page_groups_entries_by_user_for_admin_viewers(): void
    {
        $service = app(FormEntryS::class);

        // Kullanıcıları karışık zamanlarda kaydet -> en son gönderen/güncelleyen (userBeta) en üstte ve kendi 2 kaydı yan yana gruplanmalı
        $b1 = $service->saveEntry($this->template, $this->userBeta, '2026-11-24', [[$this->valueField->id => '10']], 'draft');
        $a1 = $service->saveEntry($this->template, $this->userAlpha, '2026-11-25', [[$this->valueField->id => '20']], 'draft');
        $b2 = $service->saveEntry($this->template, $this->userBeta, '2026-11-26', [[$this->valueField->id => '30']], 'draft');

        \App\Models\FormEntryM::where('id', $b1->id)->update(['updated_at' => '2026-11-24 10:00:00']);
        \App\Models\FormEntryM::where('id', $a1->id)->update(['updated_at' => '2026-11-25 11:00:00']);
        \App\Models\FormEntryM::where('id', $b2->id)->update(['updated_at' => '2026-11-26 12:00:00']);

        $response = $this->actingAs($this->admin)
            ->get(route('form-entries.index', ['status' => 'draft']));

        $response->assertOk();
        $response->assertSee('HASSAN HUSSIEN');
        $response->assertSee('SERHAT TÜMER');
        $response->assertSee('1 Kayıt');
        $response->assertSee('2 Kayıt');
        $response->assertSee('Hepsini Aç');
        $response->assertSee('Hepsini Kapat');

        // En son işlem yapan SERHAT TÜMER (2 kaydı yan yana) en üstte, sonra HASSAN HUSSIEN olmalı
        $response->assertViewHas('entries', function ($entries) {
            $userIds = $entries->pluck('user_id')->values()->toArray();
            return $userIds === [$this->userBeta->id, $this->userBeta->id, $this->userAlpha->id];
        });
    }

    public function test_regular_user_does_not_get_multi_user_group_headers(): void
    {
        $service = app(FormEntryS::class);

        $service->saveEntry($this->template, $this->userBeta, '2026-11-24', [[$this->valueField->id => '10']], 'draft');
        $service->saveEntry($this->template, $this->userAlpha, '2026-11-25', [[$this->valueField->id => '20']], 'draft');

        $response = $this->actingAs($this->userBeta)
            ->get(route('form-entries.index', ['status' => 'draft']));

        $response->assertOk();
        $response->assertSee('SERHAT TÜMER');
        $response->assertDontSee('HASSAN HUSSIEN');
        $response->assertViewHas('canSeeMultipleUsers', false);
    }

    public function test_group_manager_without_direct_group_shows_managed_group_and_manager_badge(): void
    {
        $managerRole = RoleM::create([
            'name' => 'Veri Giriş Yöneticisi',
            'slug' => 'veri_giris_yoneticisi',
        ]);

        $groupOverseas = GroupM::create(['name' => 'YURT DIŞI', 'is_active' => true]);

        // Serhat Tümer has group_id = null but is group manager of YURT DIŞI
        $managerUser = UserM::create([
            'name' => 'SERHAT TÜMER',
            'email' => 'serhat-mgr@koksan.com',
            'password' => bcrypt('password'),
            'role_id' => $managerRole->id,
            'group_id' => null,
            'is_active' => true,
            'approval_status' => 'approved',
        ]);
        $managerUser->managedGroups()->attach($groupOverseas->id);

        $service = app(FormEntryS::class);
        $service->saveEntry($this->template, $managerUser, '2026-11-26', [
            [$this->valueField->id => '777'],
        ], 'draft');

        $response = $this->actingAs($this->admin)->get(route('form-entries.index'));
        $response->assertOk();
        $response->assertSee('SERHAT TÜMER');
        $response->assertSee('YURT DIŞI (Yönettiği Grup)');
        $response->assertSee('GRUP YÖNETİCİSİ');
        $response->assertSee('Veri Giriş Yöneticisi');
        $response->assertSee('26 Kas 2026, Perşembe');
        $response->assertDontSee('26 Nov 2026');
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

        Schema::create('form_revision_requests', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('form_entry_id');
            $table->unsignedBigInteger('user_id');
            $table->text('reason');
            $table->string('status')->default('pending');
            $table->text('rejection_reason')->nullable();
            $table->unsignedBigInteger('reviewed_by')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->boolean('is_dismissed_by_user')->default(false);
            $table->timestamps();
        });

        Schema::create('kpi_targets', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('form_template_id')->nullable();
            $table->unsignedBigInteger('target_field_id')->nullable();
            $table->string('assignee_type')->nullable();
            $table->unsignedBigInteger('assignee_id')->nullable();
            $table->decimal('target_value', 18, 2)->default(0);
            $table->decimal('achieved_value', 18, 2)->default(0);
            $table->boolean('is_active')->default(true);
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
