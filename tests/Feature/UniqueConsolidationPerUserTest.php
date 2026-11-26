<?php

namespace Tests\Feature;

use App\Models\FormEntryM;
use App\Models\FormFieldM;
use App\Models\FormTemplateM;
use App\Models\RoleM;
use App\Models\UserM;
use App\Services\FormEntryS;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class UniqueConsolidationPerUserTest extends TestCase
{
    private UserM $owner;
    private UserM $otherUser;
    private FormTemplateM $template;
    private FormFieldM $partitionField;
    private FormFieldM $valueField;

    protected function setUp(): void
    {
        parent::setUp();

        $this->createMinimalSqliteSchema();

        $role = RoleM::create([
            'name' => 'Sistem Yöneticisi',
            'slug' => 'sistem_yoneticisi',
        ]);

        $this->owner = UserM::create([
            'name' => 'Kullanıcı 1',
            'email' => 'user1@koksan.com',
            'password' => bcrypt('password'),
            'role_id' => $role->id,
            'is_active' => true,
            'approval_status' => 'approved',
        ]);

        $this->otherUser = UserM::create([
            'name' => 'Kullanıcı 2',
            'email' => 'user2@koksan.com',
            'password' => bcrypt('password'),
            'role_id' => $role->id,
            'is_active' => true,
            'approval_status' => 'approved',
        ]);

        $this->template = FormTemplateM::create([
            'name' => 'HEDEFLER ŞABLONU',
            'slug' => 'hedefler-sablonu',
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
            'label' => 'HEDEF',
            'field_key' => 'hedef',
            'type' => 'text',
            'is_required' => true,
            'sort_order' => 2,
        ]);

        $this->template->update(['partition_field_id' => $this->partitionField->id]);
    }

    public function test_second_consolidation_appends_to_existing_unique_consolidated_entry_without_creating_new_id(): void
    {
        $service = app(FormEntryS::class);

        $part1 = $service->saveEntry($this->template, $this->owner, '2026-11-26', [
            [$this->partitionField->id => 'KAPAK', $this->valueField->id => '100'],
        ], 'draft', null, 'KAPAK');

        $part2 = $service->saveEntry($this->template, $this->owner, '2026-11-26', [
            [$this->partitionField->id => 'PREFORM', $this->valueField->id => '200'],
        ], 'draft', null, 'PREFORM');

        // 1. İlk birleştirme -> Tek benzersiz konsolide kayıt oluşturulur
        $response1 = $this->actingAs($this->owner)
            ->post(route('form-entries.consolidate'), [
                'entry_ids' => [$part1->id, $part2->id],
            ]);

        $consolidated = FormEntryM::where('user_id', $this->owner->id)
            ->where('is_consolidated', true)
            ->first();

        $this->assertNotNull($consolidated);
        $this->assertEquals('draft', $consolidated->status);
        $response1->assertRedirect(route('form-entries.show', $consolidated));
        $this->assertEquals(2, $consolidated->rows()->count());

        // 2. Aynı kullanıcı için ekstra tek bir parça daha eklenmek istendiğinde aynı konsolide ID korunmalı
        $part3 = $service->saveEntry($this->template, $this->owner, '2026-11-26', [
            [$this->partitionField->id => 'RESIN', $this->valueField->id => '300'],
        ], 'draft', null, 'RESIN');

        $response2 = $this->actingAs($this->owner)
            ->post(route('form-entries.consolidate'), [
                'entry_ids' => [$part3->id],
            ]);

        $response2->assertRedirect(route('form-entries.show', $consolidated));
        $response2->assertSessionHasNoErrors();

        $allConsolidatedCount = FormEntryM::where('user_id', $this->owner->id)
            ->where('is_consolidated', true)
            ->count();

        $this->assertEquals(1, $allConsolidatedCount);
        $this->assertEquals($consolidated->id, $part3->fresh()->parent_id);
        $this->assertEquals(3, $consolidated->fresh()->rows()->count());
        $this->assertStringContainsString('RESIN', $consolidated->fresh()->title);
    }

    public function test_cannot_append_new_part_when_existing_consolidated_entry_is_approved(): void
    {
        $service = app(FormEntryS::class);

        $part1 = $service->saveEntry($this->template, $this->owner, '2026-11-26', [
            [$this->partitionField->id => 'KAPAK', $this->valueField->id => '100'],
        ], 'submitted', null, 'KAPAK');

        $part2 = $service->saveEntry($this->template, $this->owner, '2026-11-26', [
            [$this->partitionField->id => 'PREFORM', $this->valueField->id => '200'],
        ], 'submitted', null, 'PREFORM');

        $part1->update(['status' => 'approved']);
        $part2->update(['status' => 'approved']);

        $consolidated = $service->consolidateEntries([$part1->id, $part2->id], $this->owner);
        $this->assertEquals('approved', $consolidated->status);

        $extraPart = $service->saveEntry($this->template, $this->owner, '2026-11-26', [
            [$this->partitionField->id => 'LEVHA', $this->valueField->id => '400'],
        ], 'draft', null, 'LEVHA');

        $response = $this->actingAs($this->owner)
            ->from(route('form-entries.index'))
            ->post(route('form-entries.consolidate'), [
                'entry_ids' => [$extraPart->id],
            ]);

        $response->assertSessionHas('error');
        $this->assertNull($extraPart->fresh()->parent_id);
        $this->assertEquals(1, FormEntryM::where('user_id', $this->owner->id)->where('is_consolidated', true)->count());
    }

    public function test_different_user_can_create_own_unique_consolidated_entry(): void
    {
        $service = app(FormEntryS::class);

        $u1Part1 = $service->saveEntry($this->template, $this->owner, '2026-11-26', [
            [$this->partitionField->id => 'KAPAK', $this->valueField->id => '100'],
        ], 'draft', null, 'KAPAK');
        $u1Part2 = $service->saveEntry($this->template, $this->owner, '2026-11-26', [
            [$this->partitionField->id => 'PREFORM', $this->valueField->id => '200'],
        ], 'draft', null, 'PREFORM');

        $u1Cons = $service->consolidateEntries([$u1Part1->id, $u1Part2->id], $this->owner);

        $u2Part1 = $service->saveEntry($this->template, $this->otherUser, '2026-11-26', [
            [$this->partitionField->id => 'RESIN', $this->valueField->id => '500'],
        ], 'draft', null, 'RESIN');
        $u2Part2 = $service->saveEntry($this->template, $this->otherUser, '2026-11-26', [
            [$this->partitionField->id => 'LEVHA', $this->valueField->id => '600'],
        ], 'draft', null, 'LEVHA');

        $u2Cons = $service->consolidateEntries([$u2Part1->id, $u2Part2->id], $this->otherUser);

        $this->assertNotEquals($u1Cons->id, $u2Cons->id);
        $this->assertEquals(1, FormEntryM::where('user_id', $this->owner->id)->where('is_consolidated', true)->count());
        $this->assertEquals(1, FormEntryM::where('user_id', $this->otherUser->id)->where('is_consolidated', true)->count());
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
    }
}
