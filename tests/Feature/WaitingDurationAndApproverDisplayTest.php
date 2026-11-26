<?php

namespace Tests\Feature;

use App\Http\Controllers\FormEntryC;
use App\Models\FormEntryM;
use App\Models\FormFieldM;
use App\Models\FormRevisionRequestM;
use App\Models\FormTemplateM;
use App\Models\GroupM;
use App\Models\PermissionM;
use App\Models\RoleM;
use App\Models\UserM;
use App\Services\FormEntryS;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class WaitingDurationAndApproverDisplayTest extends TestCase
{
    private RoleM $userRole;
    private RoleM $managerRole;
    private RoleM $adminRole;
    private GroupM $salesGroup;
    private UserM $owner;
    private UserM $groupManager;
    private UserM $superAdmin;
    private FormTemplateM $template;
    private FormFieldM $valueField;

    protected function setUp(): void
    {
        parent::setUp();
        app()->setLocale('tr');

        $this->createMinimalSqliteSchema();

        $this->userRole = RoleM::create([
            'name' => 'Satış Personeli',
            'slug' => 'satis_personeli',
        ]);

        $this->managerRole = RoleM::create([
            'name' => 'Satış Yöneticisi',
            'slug' => 'satis_yoneticisi',
        ]);

        $this->adminRole = RoleM::create([
            'name' => 'Sistem Yöneticisi',
            'slug' => 'sistem_yoneticisi',
        ]);

        $permApproveGroup = PermissionM::create([
            'name' => 'Grup Onayla',
            'slug' => 'form_entries.approve_group',
            'group' => 'form_entries',
        ]);
        $permManageRev = PermissionM::create([
            'name' => 'Revizyon Yönet',
            'slug' => 'form_entries.manage_revision_requests',
            'group' => 'form_entries',
        ]);
        $permViewGroup = PermissionM::create([
            'name' => 'Grup Gör',
            'slug' => 'form_entries.view_group',
            'group' => 'form_entries',
        ]);

        $this->managerRole->permissions()->attach([
            $permApproveGroup->id,
            $permManageRev->id,
            $permViewGroup->id,
        ]);

        $this->salesGroup = GroupM::create([
            'name' => 'YURT İÇİ',
            'is_active' => true,
        ]);

        $this->superAdmin = UserM::create([
            'name' => 'GİZLİ SÜPER ADMİN',
            'email' => 'admin-wait@koksan.com',
            'password' => bcrypt('password'),
            'role_id' => $this->adminRole->id,
            'is_active' => true,
            'approval_status' => 'approved',
        ]);

        $this->owner = UserM::create([
            'name' => 'CELAL KARAMAN',
            'email' => 'celal-wait@koksan.com',
            'password' => bcrypt('password'),
            'role_id' => $this->userRole->id,
            'group_id' => $this->salesGroup->id,
            'is_active' => true,
            'approval_status' => 'approved',
        ]);

        $this->groupManager = UserM::create([
            'name' => 'MEHMET MÜDÜR',
            'email' => 'mudur-wait@koksan.com',
            'password' => bcrypt('password'),
            'role_id' => $this->managerRole->id,
            'group_id' => $this->salesGroup->id,
            'is_active' => true,
            'approval_status' => 'approved',
        ]);

        DB::table('group_manager')->insert([
            'group_id' => $this->salesGroup->id,
            'user_id' => $this->groupManager->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->template = FormTemplateM::create([
            'name' => 'HEDEFLER ŞABLONU',
            'slug' => 'hedefler-wait-test',
            'created_by' => $this->superAdmin->id,
            'is_active' => true,
        ]);

        $this->valueField = FormFieldM::create([
            'form_template_id' => $this->template->id,
            'label' => 'TONAJ',
            'field_key' => 'tonaj',
            'type' => 'number',
            'is_required' => true,
            'sort_order' => 1,
        ]);
    }

    public function test_pending_revision_shows_single_header_badge_approver_and_wait_duration_and_resets_on_cancel(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-11-26 12:00:00'));

        try {
            $service = app(FormEntryS::class);
            $entry = $service->saveEntry($this->template, $this->owner, '2026-11-26', [
                [$this->valueField->id => '250'],
            ], 'submitted');

            $entry->status = 'approved';
            $entry->approved_at = Carbon::parse('2026-11-26 08:00:00');
            $entry->approved_by = $this->groupManager->id;
            $entry->save();

            // 2 saat 15 dakika önce oluşturulmuş revizyon talebi
            $rev = FormRevisionRequestM::create([
                'form_entry_id' => $entry->id,
                'user_id' => $this->owner->id,
                'reason' => 'Tutar düzeltmesi',
                'status' => 'pending',
            ]);
            FormRevisionRequestM::where('id', $rev->id)->update([
                'created_at' => Carbon::parse('2026-11-26 09:45:00'),
                'updated_at' => Carbon::parse('2026-11-26 09:45:00'),
            ]);

            $response = $this->actingAs($this->owner)
                ->get(route('form-entries.show', $entry));

            $response->assertOk();
            $html = $response->getContent();

            // Üstte tek bir "Düzenleme Talebi Bekliyor" rozeti olmalı (2 tane değil)
            $this->assertSame(1, substr_count($html, '<span>Düzenleme Talebi Bekliyor</span>'));
            $this->assertStringNotContainsString('bg-amber-500 rounded-full mr-1.5 animate-pulse"></span>Düzenleme Talebi Bekliyor', $html);

            // Onay beklenen yönetici ve bekleme süresi görünmeli
            $response->assertSee('Onay Beklenen:', false);
            $response->assertSee('MEHMET MÜDÜR');
            $response->assertSee('2 saat 15 dakikadır bekliyor');

            // Kullanıcı talebi geri çeker ve 1 saat sonra yeni talep gönderir -> süre sıfırlanır
            $this->actingAs($this->owner)
                ->post(route('form-entries.revision-requests.cancel', $rev))
                ->assertRedirect(route('form-entries.show', $entry));

            Carbon::setTestNow(Carbon::parse('2026-11-26 13:00:00'));

            $this->actingAs($this->owner)
                ->post(route('form-entries.request-revision', $entry), ['reason' => 'Yeni talep'])
                ->assertRedirect();

            Carbon::setTestNow(Carbon::parse('2026-11-26 13:05:00'));

            $responseAfter = $this->actingAs($this->owner)
                ->get(route('form-entries.show', $entry));

            $responseAfter->assertOk();
            $responseAfter->assertSee('5 dakikadır bekliyor');
            $responseAfter->assertDontSee('3 saat');
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_submitted_entry_shows_approver_and_wait_duration_and_resets_on_withdraw_and_draft(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-11-26 10:00:00'));

        try {
            $service = app(FormEntryS::class);
            $entry = $service->saveEntry($this->template, $this->owner, '2026-11-26', [
                [$this->valueField->id => '500'],
            ], 'submitted');

            // 1 gün 3 saat geçmiş gibi ilerlet
            Carbon::setTestNow(Carbon::parse('2026-11-27 13:00:00'));

            $response = $this->actingAs($this->owner)
                ->get(route('form-entries.show', $entry));

            $response->assertOk();
            $response->assertSee('MEHMET MÜDÜR');
            $response->assertSee('1 gün 3 saattir bekliyor');

            // Geri çekme (taslağa alma) -> submitted_at null olmalı
            $this->actingAs($this->owner)
                ->post(route('form-entries.withdraw', $entry))
                ->assertRedirect(route('form-entries.show', $entry));

            $this->assertNull($entry->fresh()->submitted_at);

            // Tekrar onaya gönderme -> süre sıfırdan başlamalı
            Carbon::setTestNow(Carbon::parse('2026-11-27 14:00:00'));
            $this->actingAs($this->owner)
                ->post(route('form-entries.submit-approval', $entry))
                ->assertRedirect(route('form-entries.show', $entry));

            Carbon::setTestNow(Carbon::parse('2026-11-27 14:12:00'));

            $responseReSubmitted = $this->actingAs($this->owner)
                ->get(route('form-entries.show', $entry));

            $responseReSubmitted->assertOk();
            $responseReSubmitted->assertSee('12 dakikadır bekliyor');
            $responseReSubmitted->assertDontSee('1 gün');

            // saveEntry ile taslak olarak kaydedilirse de submitted_at sıfırlanmalı
            $service->saveEntry($this->template, $this->owner, '2026-11-26', [
                [$this->valueField->id => '600'],
            ], 'draft', $entry->fresh());

            $this->assertEquals('draft', $entry->fresh()->status);
            $this->assertNull($entry->fresh()->submitted_at);
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_superadmin_approver_is_masked_as_yonetim_for_non_superadmin(): void
    {
        // Grup yöneticisini pasif bırak -> sadece Sistem Yöneticisi kalır
        $this->groupManager->update(['is_active' => false]);

        $service = app(FormEntryS::class);
        $entry = $service->saveEntry($this->template, $this->owner, '2026-11-26', [
            [$this->valueField->id => '100'],
        ], 'submitted');

        $controller = app(FormEntryC::class);

        $namesForUser = $controller->resolvePendingApproverNames($entry->fresh('user'), false, false);
        $this->assertSame(['Yönetim'], $namesForUser);

        $namesForSuperAdmin = $controller->resolvePendingApproverNames($entry->fresh('user'), false, true);
        $this->assertSame(['GİZLİ SÜPER ADMİN'], $namesForSuperAdmin);
    }

    public function test_approved_entry_shows_approver_name_and_elapsed_approval_duration(): void
    {
        $service = app(FormEntryS::class);
        $entry = $service->saveEntry($this->template, $this->owner, '2026-11-26', [
            [$this->valueField->id => '400'],
        ], 'submitted');

        $entry->submitted_at = Carbon::parse('2026-11-24 10:00:00');
        $entry->status = 'approved';
        $entry->approved_at = Carbon::parse('2026-11-26 14:00:00');
        $entry->approved_by = $this->groupManager->id;
        $entry->save();

        $response = $this->actingAs($this->owner)
            ->get(route('form-entries.show', $entry));

        $response->assertOk();
        $response->assertSee('Onaylayan:', false);
        $response->assertSee('MEHMET MÜDÜR');
        $response->assertSee('2 gün 4 saatte onaylandı');

        // Sistem Yöneticisi onayladıysa normal kullanıcıya "Yönetim" görünmeli
        $entry->approved_by = $this->superAdmin->id;
        $entry->save();

        $responseMasked = $this->actingAs($this->owner)
            ->get(route('form-entries.show', $entry));

        $responseMasked->assertOk();
        $responseMasked->assertSee('Yönetim');
        $responseMasked->assertDontSee('GİZLİ SÜPER ADMİN');
    }

    public function test_group_isolation_blocks_other_group_manager_even_with_approve_permission_and_excludes_from_pending_approvers(): void
    {
        $foreignGroup = GroupM::create([
            'name' => 'YURT DIŞI',
            'is_active' => true,
        ]);

        // YURT DIŞI yöneticisine approve_group yanında yanlışlıkla/ek olarak approve yetkisi tanımlansa bile
        // form_entries.view_all yetkisi yoksa YURT İÇİ personelinin formunu göremez, onaylayamaz ve listede yer almaz.
        $permApproveGlobal = PermissionM::create([
            'name' => 'Genel Onay',
            'slug' => 'form_entries.approve',
            'group' => 'form_entries',
        ]);
        $this->managerRole->permissions()->attach([$permApproveGlobal->id]);

        $foreignManager = UserM::create([
            'name' => 'MUSTAFA AKDOĞAN',
            'email' => 'mustafa-iso@koksan.com',
            'password' => bcrypt('password'),
            'role_id' => $this->managerRole->id,
            'group_id' => $foreignGroup->id,
            'is_active' => true,
            'approval_status' => 'approved',
        ]);

        DB::table('group_manager')->insert([
            'group_id' => $foreignGroup->id,
            'user_id' => $foreignManager->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $service = app(FormEntryS::class);
        $entry = $service->saveEntry($this->template, $this->owner, '2026-11-26', [
            [$this->valueField->id => '300'],
        ], 'submitted');

        $controller = app(FormEntryC::class);
        $approvalApprovers = $controller->resolvePendingApproverNames($entry->fresh('user'), false, false);
        $revisionApprovers = $controller->resolvePendingApproverNames($entry->fresh('user'), true, false);

        $this->assertSame(['MEHMET MÜDÜR'], $approvalApprovers);
        $this->assertSame(['MEHMET MÜDÜR'], $revisionApprovers);

        // Farklı grup yöneticisi YURT İÇİ formuna erişememeli / onaylayamamalı (403)
        $this->actingAs($foreignManager)
            ->get(route('form-entries.show', $entry))
            ->assertForbidden();

        $this->actingAs($foreignManager)
            ->post(route('form-entries.review', $entry), ['status' => 'approved'])
            ->assertForbidden();

        // Kendi grup yöneticisi görebilmeli ve revizyonu değerlendirebilmeli
        $this->actingAs($this->groupManager)
            ->get(route('form-entries.show', $entry))
            ->assertOk()
            ->assertSee('MEHMET MÜDÜR')
            ->assertDontSee('MUSTAFA AKDOĞAN');

        $rev = FormRevisionRequestM::create([
            'form_entry_id' => $entry->id,
            'user_id' => $this->owner->id,
            'reason' => 'Grup izolasyon testi',
            'status' => 'pending',
        ]);

        $this->actingAs($foreignManager)
            ->post(route('form-entries.revision-requests.review', $rev), ['action' => 'approve'])
            ->assertForbidden();
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

        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
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

        Schema::create('notification_events', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
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
