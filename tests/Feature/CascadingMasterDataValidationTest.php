<?php

namespace Tests\Feature;

use App\Models\FormEntryM;
use App\Models\FormFieldM;
use App\Models\FormTemplateM;
use App\Models\MasterDataItemM;
use App\Models\MasterDataTypeM;
use App\Models\RoleM;
use App\Models\UserM;
use App\Services\FormEntryS;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class CascadingMasterDataValidationTest extends TestCase
{
    private UserM $owner;
    private FormTemplateM $template;
    private FormFieldM $countryField;
    private FormFieldM $cityField;

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

        $countryType = MasterDataTypeM::create([
            'name' => 'ÜLKE',
            'parent_id' => null,
        ]);

        $cityType = MasterDataTypeM::create([
            'name' => 'ŞEHİR',
            'parent_id' => $countryType->id,
        ]);

        $turkey = MasterDataItemM::create([
            'type_id' => $countryType->id,
            'name' => 'Türkiye',
        ]);

        $cyprus = MasterDataItemM::create([
            'type_id' => $countryType->id,
            'name' => 'Kıbrıs',
        ]);

        $iraq = MasterDataItemM::create([
            'type_id' => $countryType->id,
            'name' => 'Irak',
        ]);

        MasterDataItemM::create([
            'type_id' => $cityType->id,
            'parent_id' => $turkey->id,
            'name' => 'Gaziantep',
        ]);

        MasterDataItemM::create([
            'type_id' => $cityType->id,
            'parent_id' => $cyprus->id,
            'name' => 'Nicosia',
        ]);

        MasterDataItemM::create([
            'type_id' => $cityType->id,
            'parent_id' => $iraq->id,
            'name' => 'Erbil',
        ]);

        $this->template = FormTemplateM::create([
            'name' => 'HEDEFLER ŞABLONU',
            'slug' => 'hedefler-cascading-test',
            'created_by' => $this->owner->id,
            'is_active' => true,
        ]);

        $this->countryField = FormFieldM::create([
            'form_template_id' => $this->template->id,
            'label' => 'ÜLKE',
            'field_key' => 'ulke',
            'type' => 'master_data',
            'master_data_type_id' => $countryType->id,
            'is_required' => true,
            'sort_order' => 1,
        ]);

        $this->cityField = FormFieldM::create([
            'form_template_id' => $this->template->id,
            'label' => 'ŞEHİR',
            'field_key' => 'sehir',
            'type' => 'master_data',
            'master_data_type_id' => $cityType->id,
            'is_required' => true,
            'sort_order' => 2,
        ]);
    }

    public function test_store_submit_rejects_mismatched_country_and_city_and_accepts_valid_pairs(): void
    {
        $badResponse = $this->actingAs($this->owner)
            ->from(route('form-entries.create', $this->template))
            ->post(route('form-entries.store', $this->template), [
                'entry_date' => '2026-11-27',
                'action' => 'submit',
                'rows' => [
                    [
                        $this->countryField->id => 'Kıbrıs',
                        $this->cityField->id => 'Gaziantep',
                    ],
                ],
            ]);

        $badResponse->assertRedirect(route('form-entries.create', $this->template));
        $badResponse->assertSessionHasErrors(['rows']);
        $this->assertEquals(0, FormEntryM::where('status', 'submitted')->count());

        $goodResponse = $this->actingAs($this->owner)
            ->post(route('form-entries.store', $this->template), [
                'entry_date' => '2026-11-27',
                'action' => 'submit',
                'rows' => [
                    [
                        $this->countryField->id => 'Türkiye',
                        $this->cityField->id => 'Gaziantep',
                    ],
                    [
                        $this->countryField->id => 'Kıbrıs',
                        $this->cityField->id => 'Nicosia',
                    ],
                ],
            ]);

        $goodResponse->assertSessionHasNoErrors();
        $this->assertEquals(1, FormEntryM::where('status', 'submitted')->count());
    }

    public function test_update_submit_rejects_mismatched_country_and_city(): void
    {
        $service = app(FormEntryS::class);
        $draft = $service->saveEntry($this->template, $this->owner, '2026-11-27', [
            [
                $this->countryField->id => 'Türkiye',
                $this->cityField->id => 'Gaziantep',
            ],
        ], 'draft');

        $response = $this->actingAs($this->owner)
            ->from(route('form-entries.edit', $draft))
            ->put(route('form-entries.update', $draft), [
                'entry_date' => '2026-11-27',
                'action' => 'submit',
                'rows' => [
                    [
                        $this->countryField->id => 'Irak',
                        $this->cityField->id => 'Gaziantep',
                    ],
                ],
            ]);

        $response->assertRedirect(route('form-entries.edit', $draft));
        $response->assertSessionHasErrors(['rows']);
        $this->assertEquals('draft', $draft->fresh()->status);
    }

    public function test_submit_approval_rejects_mismatched_draft_country_and_city(): void
    {
        $service = app(FormEntryS::class);
        $draft = $service->saveEntry($this->template, $this->owner, '2026-11-27', [
            [
                $this->countryField->id => 'Kıbrıs',
                $this->cityField->id => 'Gaziantep',
            ],
        ], 'draft');

        $response = $this->actingAs($this->owner)
            ->from(route('form-entries.show', $draft))
            ->post(route('form-entries.submit-approval', $draft));

        $response->assertRedirect(route('form-entries.show', $draft));
        $response->assertSessionHasErrors();
        $this->assertEquals('draft', $draft->fresh()->status);
    }

    public function test_store_draft_allows_saving_rows_even_before_cascading_fix(): void
    {
        $response = $this->actingAs($this->owner)
            ->post(route('form-entries.store', $this->template), [
                'entry_date' => '2026-11-27',
                'action' => 'draft',
                'rows' => [
                    [
                        $this->countryField->id => 'Irak',
                        $this->cityField->id => 'Gaziantep',
                    ],
                ],
            ]);

        $response->assertSessionHasNoErrors();
        $this->assertEquals(1, FormEntryM::where('status', 'draft')->count());
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
