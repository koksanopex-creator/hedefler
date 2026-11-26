<?php

namespace Tests\Feature;

use App\Models\FormEntryM;
use App\Models\FormFieldM;
use App\Models\FormRevisionRequestM;
use App\Models\FormTemplateM;
use App\Models\RoleM;
use App\Models\UserM;
use App\Services\FormEntryS;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class PartialRevisionSyncTest extends TestCase
{
    private UserM $owner;
    private UserM $approver;
    private FormTemplateM $template;
    private FormFieldM $partitionField;
    private FormFieldM $valueField;

    protected function setUp(): void
    {
        parent::setUp();

        $this->createMinimalSqliteSchema();

        $adminRole = RoleM::create([
            'name' => 'Sistem Yöneticisi',
            'slug' => 'sistem_yoneticisi',
        ]);

        $this->owner = UserM::create([
            'name' => 'CELAL KARAMAN',
            'email' => 'celal-rev@koksan.com',
            'password' => bcrypt('password'),
            'role_id' => $adminRole->id,
            'is_active' => true,
            'approval_status' => 'approved',
        ]);

        $this->approver = UserM::create([
            'name' => 'YÖNETİCİ',
            'email' => 'manager-rev@koksan.com',
            'password' => bcrypt('password'),
            'role_id' => $adminRole->id,
            'is_active' => true,
            'approval_status' => 'approved',
        ]);

        $this->template = FormTemplateM::create([
            'name' => 'HEDEFLER ŞABLONU',
            'slug' => 'hedefler-rev-test',
            'created_by' => $this->owner->id,
            'is_active' => true,
            'allow_partition_entry' => true,
        ]);

        $this->partitionField = FormFieldM::create([
            'form_template_id' => $this->template->id,
            'label' => 'İŞYERİ',
            'field_key' => 'isyeri',
            'type' => 'text',
            'is_required' => true,
            'sort_order' => 1,
        ]);

        $this->valueField = FormFieldM::create([
            'form_template_id' => $this->template->id,
            'label' => 'TONAJ',
            'field_key' => 'tonaj',
            'type' => 'number',
            'is_required' => true,
            'sort_order' => 2,
        ]);

        $this->template->update(['partition_field_id' => $this->partitionField->id]);
    }

    public function test_partial_revision_only_unlocks_target_child_and_reapproval_syncs_consolidated_parent(): void
    {
        $service = app(FormEntryS::class);

        $kapak = $service->saveEntry($this->template, $this->owner, '2026-11-26', [
            [$this->partitionField->id => 'KAPAK', $this->valueField->id => '100'],
        ], 'submitted', null, 'KAPAK');

        $preform = $service->saveEntry($this->template, $this->owner, '2026-11-26', [
            [$this->partitionField->id => 'PREFORM', $this->valueField->id => '200'],
        ], 'submitted', null, 'PREFORM');

        $parent = $service->consolidateEntries([$kapak->id, $preform->id], $this->owner);

        // Yönetici konsolide formu onaylar -> parent ve tüm parçalar 'approved' olur
        $this->actingAs($this->approver)
            ->post(route('form-entries.review', $parent), ['status' => 'approved'])
            ->assertRedirect();

        $this->assertEquals('approved', $parent->fresh()->status);
        $this->assertEquals('approved', $kapak->fresh()->status);
        $this->assertEquals('approved', $preform->fresh()->status);

        // Sadece KAPAK parçası için düzenleme talebi oluştur ve yönetici onayla
        $this->actingAs($this->owner)
            ->post(route('form-entries.request-revision', $kapak), ['reason' => 'Kapak bölümü düzeltme'])
            ->assertRedirect();

        $rev = FormRevisionRequestM::where('form_entry_id', $kapak->id)->firstOrFail();

        $this->actingAs($this->approver)
            ->post(route('form-entries.revision-requests.review', $rev), ['action' => 'approve'])
            ->assertRedirect();

        // Sadece KAPAK düzenlemeye açılmış olmalı; PREFORM ve konsolide ana form onaylı kalmalı
        $this->assertEquals('submitted', $kapak->fresh()->status);
        $this->assertEquals('approved', $preform->fresh()->status);
        $this->assertEquals('approved', $parent->fresh()->status);

        // KAPAK güncellenip tekrar onaya gönderilir ve yönetici yeniden onaylar
        $service->saveEntry($this->template, $this->owner, '2026-11-26', [
            [$this->partitionField->id => 'KAPAK', $this->valueField->id => '999'],
        ], 'submitted', $kapak->fresh(), 'KAPAK');

        $this->actingAs($this->approver)
            ->post(route('form-entries.review', $kapak->fresh()), ['status' => 'approved'])
            ->assertRedirect();

        $this->assertEquals('approved', $kapak->fresh()->status);

        // Konsolide ana formun satırları güncel KAPAK (999) + PREFORM (200) verisiyle yenilenmiş olmalı
        $parentValues = $parent->fresh()->rows->map(function ($row) {
            return [
                'part' => $row->values->firstWhere('form_field_id', $this->partitionField->id)?->value,
                'val' => $row->values->firstWhere('form_field_id', $this->valueField->id)?->value,
            ];
        })->values()->toArray();

        $this->assertCount(2, $parentValues);
        $this->assertSame(['part' => 'KAPAK', 'val' => '999'], $parentValues[0]);
        $this->assertSame(['part' => 'PREFORM', 'val' => '200'], $parentValues[1]);
    }

    public function test_consolidated_parent_revision_approval_unlocks_parent_and_all_children(): void
    {
        $service = app(FormEntryS::class);

        $kapak = $service->saveEntry($this->template, $this->owner, '2026-11-26', [
            [$this->partitionField->id => 'KAPAK', $this->valueField->id => '100'],
        ], 'submitted', null, 'KAPAK');

        $preform = $service->saveEntry($this->template, $this->owner, '2026-11-26', [
            [$this->partitionField->id => 'PREFORM', $this->valueField->id => '200'],
        ], 'submitted', null, 'PREFORM');

        $parent = $service->consolidateEntries([$kapak->id, $preform->id], $this->owner);

        $this->actingAs($this->approver)
            ->post(route('form-entries.review', $parent), ['status' => 'approved'])
            ->assertRedirect();

        $this->actingAs($this->owner)
            ->post(route('form-entries.request-revision', $parent), ['reason' => 'Tüm form revizyonu'])
            ->assertRedirect();

        $rev = FormRevisionRequestM::where('form_entry_id', $parent->id)->firstOrFail();

        $this->actingAs($this->approver)
            ->post(route('form-entries.revision-requests.review', $rev), ['action' => 'approve'])
            ->assertRedirect();

        $this->assertEquals('submitted', $parent->fresh()->status);
        $this->assertEquals('submitted', $kapak->fresh()->status);
        $this->assertEquals('submitted', $preform->fresh()->status);
    }

    public function test_user_can_cancel_pending_revision_request_and_withdraw_notifications(): void
    {
        $service = app(FormEntryS::class);

        $kapak = $service->saveEntry($this->template, $this->owner, '2026-11-26', [
            [$this->partitionField->id => 'KAPAK', $this->valueField->id => '100'],
        ], 'submitted', null, 'KAPAK');

        $preform = $service->saveEntry($this->template, $this->owner, '2026-11-26', [
            [$this->partitionField->id => 'PREFORM', $this->valueField->id => '200'],
        ], 'submitted', null, 'PREFORM');

        $parent = $service->consolidateEntries([$kapak->id, $preform->id], $this->owner);

        $this->actingAs($this->approver)
            ->post(route('form-entries.review', $parent), ['status' => 'approved'])
            ->assertRedirect();

        $this->actingAs($this->owner)
            ->post(route('form-entries.request-revision', $kapak), ['reason' => 'Geri çekilecek talep'])
            ->assertRedirect();

        $rev = FormRevisionRequestM::where('form_entry_id', $kapak->id)->firstOrFail();

        // İlgili zil bildirimi kaydını simüle et (ayrıca ilgisiz bir bildirim de kalsın)
        $targetNotifId = \Illuminate\Support\Str::uuid()->toString();
        $otherNotifId = \Illuminate\Support\Str::uuid()->toString();

        \Illuminate\Support\Facades\DB::table('notifications')->insert([
            [
                'id' => $targetNotifId,
                'type' => \App\Notifications\DynamicNotification::class,
                'notifiable_type' => UserM::class,
                'notifiable_id' => $this->approver->id,
                'data' => json_encode([
                    'subject' => 'Düzenleme Talebi',
                    'body' => 'Talep geldi',
                    'actionUrl' => route('form-entries.show', $kapak),
                ]),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => $otherNotifId,
                'type' => \App\Notifications\DynamicNotification::class,
                'notifiable_type' => UserM::class,
                'notifiable_id' => $this->approver->id,
                'data' => json_encode([
                    'subject' => 'Başka Form',
                    'body' => 'Başka bildirim',
                    'actionUrl' => route('form-entries.show', $preform),
                ]),
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $this->actingAs($this->owner)
            ->post(route('form-entries.revision-requests.cancel', $rev))
            ->assertRedirect(route('form-entries.show', $kapak));

        $this->assertNull(FormRevisionRequestM::find($rev->id));
        $this->assertEquals('approved', $kapak->fresh()->status);
        $this->assertEquals('approved', $parent->fresh()->status);
        $this->assertNull(\Illuminate\Support\Facades\DB::table('notifications')->where('id', $targetNotifId)->first());
        $this->assertNotNull(\Illuminate\Support\Facades\DB::table('notifications')->where('id', $otherNotifId)->first());
    }

    private function createMinimalSqliteSchema(): void
    {
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
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
