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

class ConsolidatedDraftSubmitValidationTest extends TestCase
{
    private UserM $owner;

    protected function setUp(): void
    {
        parent::setUp();

        $this->createMinimalSqliteSchema();

        $role = RoleM::create([
            'name' => 'Sistem Yöneticisi',
            'slug' => 'sistem_yoneticisi',
        ]);

        $this->owner = UserM::create([
            'name' => 'Test User',
            'email' => 'test@koksan.com',
            'password' => bcrypt('password'),
            'role_id' => $role->id,
            'is_active' => true,
            'approval_status' => 'approved',
        ]);
    }

    public function test_consolidated_draft_children_cannot_be_submitted_when_required_field_is_empty(): void
    {
        $template = FormTemplateM::create([
            'name' => 'HEDEFLER ŞABLONU',
            'slug' => 'hedefler-sablonu',
            'created_by' => $this->owner->id,
            'is_active' => true,
            'allow_partition_entry' => true,
        ]);

        $partitionField = FormFieldM::create([
            'form_template_id' => $template->id,
            'label' => 'İŞYERİ',
            'field_key' => 'isyeri',
            'type' => 'text',
            'is_required' => true,
            'sort_order' => 1,
        ]);

        $template->update(['partition_field_id' => $partitionField->id]);

        $requiredField = FormFieldM::create([
            'form_template_id' => $template->id,
            'label' => 'SATIŞ GRUBU TANIMI',
            'field_key' => 'satis_grubu',
            'type' => 'text',
            'is_required' => true,
            'sort_order' => 2,
        ]);

        $disabledRequiredField = FormFieldM::create([
            'form_template_id' => $template->id,
            'label' => 'MALZEME',
            'field_key' => 'malzeme',
            'type' => 'text',
            'is_required' => true,
            'is_disabled' => true,
            'sort_order' => 3,
        ]);

        $service = app(FormEntryS::class);

        $childValid = $service->saveEntry($template, $this->owner, '2026-11-25', [
            [
                $partitionField->id => 'RESİN',
                $requiredField->id => 'Ahmet Yılmaz',
                $disabledRequiredField->id => '',
            ],
        ], 'draft', null, 'RESİN');

        $childInvalid = $service->saveEntry($template, $this->owner, '2026-11-25', [
            [
                $partitionField->id => 'TİCARİ PET RESIN',
                $requiredField->id => '',
                $disabledRequiredField->id => '',
            ],
        ], 'draft', null, 'TİCARİ PET RESIN');

        // Boş satır olarak filtrelenmesini önlemek için en az bir opsiyonel/gerçek veri değeri ekle
        $optionalField = FormFieldM::create([
            'form_template_id' => $template->id,
            'label' => 'ÜLKE',
            'field_key' => 'ulke',
            'type' => 'text',
            'is_required' => false,
            'sort_order' => 4,
        ]);

        $childInvalid = $service->saveEntry($template, $this->owner, '2026-11-25', [
            [
                $partitionField->id => 'TİCARİ PET RESIN',
                $requiredField->id => '',
                $optionalField->id => 'Afganistan',
            ],
        ], 'draft', $childInvalid, 'TİCARİ PET RESIN');

        $parent = $service->consolidateEntries([$childValid->id, $childInvalid->id], $this->owner);

        $response = $this->actingAs($this->owner)
            ->from(route('form-entries.show', $parent))
            ->post(route('form-entries.submit-children-approval', $parent));

        $response->assertRedirect(route('form-entries.show', $parent));
        $response->assertSessionHasErrors();

        $this->assertEquals('draft', $childValid->fresh()->status);
        $this->assertEquals('draft', $childInvalid->fresh()->status);
    }

    public function test_consolidated_draft_children_can_be_submitted_when_required_fields_are_filled(): void
    {
        $template = FormTemplateM::create([
            'name' => 'HEDEFLER ŞABLONU',
            'slug' => 'hedefler-sablonu-2',
            'created_by' => $this->owner->id,
            'is_active' => true,
            'allow_partition_entry' => true,
        ]);

        $partitionField = FormFieldM::create([
            'form_template_id' => $template->id,
            'label' => 'İŞYERİ',
            'field_key' => 'isyeri',
            'type' => 'text',
            'is_required' => true,
            'sort_order' => 1,
        ]);

        $template->update(['partition_field_id' => $partitionField->id]);

        $requiredField = FormFieldM::create([
            'form_template_id' => $template->id,
            'label' => 'SATIŞ GRUBU TANIMI',
            'field_key' => 'satis_grubu',
            'type' => 'text',
            'is_required' => true,
            'sort_order' => 2,
        ]);

        $disabledField = FormFieldM::create([
            'form_template_id' => $template->id,
            'label' => 'MALZEME',
            'field_key' => 'malzeme',
            'type' => 'text',
            'is_required' => true,
            'is_disabled' => true,
            'sort_order' => 3,
        ]);

        $service = app(FormEntryS::class);

        $child1 = $service->saveEntry($template, $this->owner, '2026-11-25', [
            [
                $partitionField->id => 'RESİN',
                $requiredField->id => 'Ahmet Yılmaz',
                $disabledField->id => '',
            ],
        ], 'draft', null, 'RESİN');

        $child2 = $service->saveEntry($template, $this->owner, '2026-11-25', [
            [
                $partitionField->id => 'TİCARİ PET RESIN',
                $requiredField->id => 'Mehmet Demir',
                $disabledField->id => '',
            ],
        ], 'draft', null, 'TİCARİ PET RESIN');

        $parent = $service->consolidateEntries([$child1->id, $child2->id], $this->owner);

        $response = $this->actingAs($this->owner)
            ->from(route('form-entries.show', $parent))
            ->post(route('form-entries.submit-children-approval', $parent));

        $response->assertRedirect(route('form-entries.show', $parent));
        $response->assertSessionHasNoErrors();

        $this->assertEquals('submitted', $child1->fresh()->status);
        $this->assertEquals('submitted', $child2->fresh()->status);
    }

    public function test_conditional_required_field_is_enforced_only_when_condition_matches(): void
    {
        $template = FormTemplateM::create([
            'name' => 'KOŞULLU ŞABLON',
            'slug' => 'kosullu-sablon',
            'created_by' => $this->owner->id,
            'is_active' => true,
            'allow_partition_entry' => true,
        ]);

        $partitionField = FormFieldM::create([
            'form_template_id' => $template->id,
            'label' => 'İŞYERİ',
            'field_key' => 'isyeri',
            'type' => 'text',
            'is_required' => true,
            'sort_order' => 1,
        ]);

        $template->update(['partition_field_id' => $partitionField->id]);

        $docTypeField = FormFieldM::create([
            'form_template_id' => $template->id,
            'label' => 'BELGE TÜRÜ',
            'field_key' => 'belge_turu',
            'type' => 'select',
            'is_required' => true,
            'sort_order' => 2,
        ]);

        $conditionalField = FormFieldM::create([
            'form_template_id' => $template->id,
            'label' => 'SÖZLEŞME NO',
            'field_key' => 'sozlesme_no',
            'type' => 'text',
            'is_required' => false,
            'condition_field_id' => $docTypeField->id,
            'condition_value' => 'Y.İçi Sip.',
            'sort_order' => 3,
        ]);

        $service = app(FormEntryS::class);

        $childMatchingCond = $service->saveEntry($template, $this->owner, '2026-11-25', [
            [
                $partitionField->id => 'RESİN',
                $docTypeField->id => 'Y.İçi Sip.',
                $conditionalField->id => '',
            ],
        ], 'draft', null, 'RESİN');

        $childNonMatchingCond = $service->saveEntry($template, $this->owner, '2026-11-25', [
            [
                $partitionField->id => 'KAPAK',
                $docTypeField->id => 'İhracat',
                $conditionalField->id => '',
            ],
        ], 'draft', null, 'KAPAK');

        $parent = $service->consolidateEntries([$childMatchingCond->id, $childNonMatchingCond->id], $this->owner);

        // Koşul sağlanıp SÖZLEŞME NO boş bırakıldığında onaya gönderilememeli
        $failResponse = $this->actingAs($this->owner)
            ->from(route('form-entries.show', $parent))
            ->post(route('form-entries.submit-children-approval', $parent));

        $failResponse->assertSessionHasErrors();
        $this->assertEquals('draft', $childMatchingCond->fresh()->status);

        // Koşul dışı değere geçildiğinde boş olsa dahi onaya gönderilebilmeli
        $childMatchingCond = $service->saveEntry($template, $this->owner, '2026-11-25', [
            [
                $partitionField->id => 'RESİN',
                $docTypeField->id => 'İhracat',
                $conditionalField->id => '',
            ],
        ], 'draft', $childMatchingCond, 'RESİN');

        $passResponse = $this->actingAs($this->owner)
            ->from(route('form-entries.show', $parent))
            ->post(route('form-entries.submit-children-approval', $parent));

        $passResponse->assertSessionHasNoErrors();
        $this->assertEquals('submitted', $childMatchingCond->fresh()->status);
        $this->assertEquals('submitted', $childNonMatchingCond->fresh()->status);
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

        Schema::create('master_data_types', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->unsignedBigInteger('parent_id')->nullable();
            $table->string('sort_mode')->default('alphabetical');
            $table->timestamps();
        });

        Schema::create('master_data_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('type_id');
            $table->string('name');
            $table->unsignedBigInteger('parent_id')->nullable();
            $table->integer('sort_order')->default(0);
            $table->unsignedBigInteger('user_id')->nullable();
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

        Schema::create('notification_events', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->timestamps();
        });
    }
}
