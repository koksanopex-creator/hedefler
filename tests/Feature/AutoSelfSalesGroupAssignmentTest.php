<?php

namespace Tests\Feature;

use App\Models\FormFieldM;
use App\Models\FormTemplateM;
use App\Models\RoleM;
use App\Models\UserM;
use App\Services\FormEntryS;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AutoSelfSalesGroupAssignmentTest extends TestCase
{
    private UserM $salesUser;
    private UserM $adminUser;
    private FormTemplateM $template;
    private FormFieldM $salesGroupField;
    private FormFieldM $qtyField;

    protected function setUp(): void
    {
        parent::setUp();

        $this->createMinimalSqliteSchema();

        $salesRole = RoleM::create([
            'name' => 'Veri Girişi',
            'slug' => 'veri_girisi',
        ]);

        $adminRole = RoleM::create([
            'name' => 'Sistem Yöneticisi',
            'slug' => 'sistem_yoneticisi',
        ]);

        $this->salesUser = UserM::create([
            'name' => 'CELAL KARAMAN',
            'email' => 'celal@koksan.com',
            'password' => bcrypt('password'),
            'role_id' => $salesRole->id,
            'is_active' => true,
            'approval_status' => 'approved',
        ]);

        $this->adminUser = UserM::create([
            'name' => 'ADMIN USER',
            'email' => 'admin@koksan.com',
            'password' => bcrypt('password'),
            'role_id' => $adminRole->id,
            'is_active' => true,
            'approval_status' => 'approved',
        ]);

        $this->template = FormTemplateM::create([
            'name' => 'HEDEFLER ŞABLONU',
            'slug' => 'hedefler-auto-self-test',
            'created_by' => $this->adminUser->id,
            'is_active' => true,
        ]);

        $this->salesGroupField = FormFieldM::create([
            'form_template_id' => $this->template->id,
            'label' => 'SATIŞ GRUBU TANIMI',
            'field_key' => 'satis_grubu_tanimi',
            'type' => 'user_list',
            'is_required' => true,
            'filter_by_user_group' => true,
            'sort_order' => 1,
        ]);

        $this->qtyField = FormFieldM::create([
            'form_template_id' => $this->template->id,
            'label' => 'MİKTAR',
            'field_key' => 'miktar',
            'type' => 'number',
            'is_required' => true,
            'sort_order' => 2,
        ]);
    }

    public function test_restricted_user_entry_automatically_replaces_other_sales_person_with_own_name(): void
    {
        $entry = app(FormEntryS::class)->saveEntry($this->template, $this->salesUser, '2026-11-26', [
            [
                $this->salesGroupField->id => 'SERHAT TÜMER',
                $this->qtyField->id => '280000',
            ],
            [
                $this->salesGroupField->id => 'AHMET MEHMET',
                $this->qtyField->id => '140000',
            ],
        ], 'submitted');

        $entry->load('rows.values');
        $this->assertCount(2, $entry->rows);

        foreach ($entry->rows as $row) {
            $val = $row->values->firstWhere('form_field_id', $this->salesGroupField->id)?->value;
            $this->assertSame('CELAL KARAMAN', $val);
        }
    }

    public function test_admin_user_keeps_selected_sales_person_name_unchanged(): void
    {
        $entry = app(FormEntryS::class)->saveEntry($this->template, $this->adminUser, '2026-11-26', [
            [
                $this->salesGroupField->id => 'SERHAT TÜMER',
                $this->qtyField->id => '280000',
            ],
        ], 'submitted');

        $entry->load('rows.values');
        $val = $entry->rows->first()->values->firstWhere('form_field_id', $this->salesGroupField->id)?->value;
        $this->assertSame('SERHAT TÜMER', $val);
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
            $table->timestamps();
        });

        Schema::create('role_permission', function (Blueprint $table) {
            $table->unsignedBigInteger('role_id');
            $table->unsignedBigInteger('permission_id');
        });

        Schema::create('user_permission', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('permission_id');
            $table->boolean('allowed')->default(true);
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
    }
}
