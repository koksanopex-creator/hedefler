<?php

namespace Tests\Feature;

use App\Models\FormFieldM;
use App\Models\FormTemplateM;
use App\Models\RoleM;
use App\Models\UserM;
use App\Services\FormEntryS;
use App\Services\FormExportS;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Tests\TestCase;

class FormExportNumberFormatTest extends TestCase
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
            'email' => 'export-test@koksan.com',
            'password' => bcrypt('password'),
            'role_id' => $role->id,
            'is_active' => true,
            'approval_status' => 'approved',
        ]);
    }

    public function test_exported_excel_formats_numeric_fields_and_totals_with_thousand_separator(): void
    {
        $template = FormTemplateM::create([
            'name' => 'HEDEFLER ŞABLONU',
            'slug' => 'hedefler-export-test',
            'created_by' => $this->owner->id,
            'is_active' => true,
            'show_row_totals' => true,
        ]);

        $textField = FormFieldM::create([
            'form_template_id' => $template->id,
            'label' => 'BELGE TÜRÜ',
            'field_key' => 'belge_turu',
            'type' => 'text',
            'is_required' => false,
            'sort_order' => 1,
        ]);

        $qtyField = FormFieldM::create([
            'form_template_id' => $template->id,
            'label' => 'TOPLAM TESLİMAT MİKTARI',
            'field_key' => 'miktar',
            'type' => 'number',
            'is_summable' => true,
            'sort_order' => 2,
        ]);

        $tonField = FormFieldM::create([
            'form_template_id' => $template->id,
            'label' => 'TONAJ',
            'field_key' => 'tonaj',
            'type' => 'number',
            'is_summable' => true,
            'sort_order' => 3,
        ]);

        $entry = app(FormEntryS::class)->saveEntry($template, $this->owner, '2026-11-26', [
            [
                $textField->id => 'Y.dışı Sip.',
                $qtyField->id => '961920',
                $tonField->id => '10.1',
            ],
            [
                $textField->id => 'Y.dışı Sip.',
                $qtyField->id => '31872',
                $tonField->id => '0,51',
            ],
        ], 'submitted');

        $response = app(FormExportS::class)->exportToExcel($entry);
        $this->assertInstanceOf(BinaryFileResponse::class, $response);

        $filePath = $response->getFile()->getPathname();
        $this->assertFileExists($filePath);

        $spreadsheet = IOFactory::load($filePath);
        $sheet = $spreadsheet->getActiveSheet();

        // A=BELGE TÜRÜ, B=TOPLAM TESLİMAT MİKTARI, C=TONAJ (Sıra No sütunu yok)
        $this->assertSame('BELGE TÜRÜ', $sheet->getCell('A1')->getValue());

        $this->assertSame(961920.0, (float) $sheet->getCell('B2')->getValue());
        $this->assertSame(NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1, $sheet->getStyle('B2')->getNumberFormat()->getFormatCode());

        $this->assertSame(10.1, (float) $sheet->getCell('C2')->getValue());
        $this->assertSame(NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1, $sheet->getStyle('C2')->getNumberFormat()->getFormatCode());

        $this->assertSame(0.51, (float) $sheet->getCell('C3')->getValue());
        $this->assertSame(NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1, $sheet->getStyle('C3')->getNumberFormat()->getFormatCode());

        // Alt toplam (=SUM) hücreleri de binlik ayraç formatında olmalı
        $this->assertSame('=SUM(B2:B3)', $sheet->getCell('B4')->getValue());
        $this->assertSame(NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1, $sheet->getStyle('B4')->getNumberFormat()->getFormatCode());
        $this->assertSame('=SUM(C2:C3)', $sheet->getCell('C4')->getValue());
        $this->assertSame(NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1, $sheet->getStyle('C4')->getNumberFormat()->getFormatCode());

        // Metin sütunu (BELGE TÜRÜ) (#,##0.00) olmamalı
        $this->assertNotSame(NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1, $sheet->getStyle('A2')->getNumberFormat()->getFormatCode());

        @unlink($filePath);
    }

    public function test_empty_template_filters_sales_group_dropdown_to_self_for_regular_user(): void
    {
        $regularRole = RoleM::create([
            'name' => 'Veri Girişi',
            'slug' => 'veri_girisi',
        ]);

        $regularUser = UserM::create([
            'name' => 'CELAL KARAMAN',
            'email' => 'celal@koksan.com',
            'password' => bcrypt('password'),
            'role_id' => $regularRole->id,
            'is_active' => true,
            'approval_status' => 'approved',
        ]);

        UserM::create([
            'name' => 'SERHAT TÜMER',
            'email' => 'serhat@koksan.com',
            'password' => bcrypt('password'),
            'role_id' => $regularRole->id,
            'is_active' => true,
            'approval_status' => 'approved',
        ]);

        $template = FormTemplateM::create([
            'name' => 'HEDEFLER ŞABLONU',
            'slug' => 'hedefler-template-self-only',
            'created_by' => $this->owner->id,
            'is_active' => true,
        ]);

        FormFieldM::create([
            'form_template_id' => $template->id,
            'label' => 'SATIŞ GRUBU TANIMI',
            'field_key' => 'satis_grubu_tanimi',
            'type' => 'user_list',
            'filter_by_user_group' => true,
            'sort_order' => 1,
        ]);

        $response = app(FormExportS::class)->exportEmptyTemplate($template, null, $regularUser);
        $this->assertInstanceOf(BinaryFileResponse::class, $response);

        $filePath = $response->getFile()->getPathname();
        $spreadsheet = IOFactory::load($filePath);
        $listSheet = $spreadsheet->getSheetByName('_LISTELER');

        $this->assertNotNull($listSheet);
        $this->assertSame('SATIŞ GRUBU TANIMI', $listSheet->getCell('A1')->getValue());
        $this->assertSame('CELAL KARAMAN', $listSheet->getCell('A2')->getValue());
        $this->assertNull($listSheet->getCell('A3')->getValue());

        @unlink($filePath);
    }

    public function test_empty_template_keeps_full_sales_group_dropdown_for_approver_user(): void
    {
        $approverRole = RoleM::create([
            'name' => 'Onaylayıcı',
            'slug' => 'onaylayici',
        ]);

        $permId = \Illuminate\Support\Facades\DB::table('permissions')->insertGetId([
            'name' => 'Form Onaylama',
            'slug' => 'form_entries.approve',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        \Illuminate\Support\Facades\DB::table('role_permission')->insert([
            'role_id' => $approverRole->id,
            'permission_id' => $permId,
        ]);

        $approver = UserM::create([
            'name' => 'ONAYLAYAN MÜDÜR',
            'email' => 'approver@koksan.com',
            'password' => bcrypt('password'),
            'role_id' => $approverRole->id,
            'is_active' => true,
            'approval_status' => 'approved',
        ]);

        UserM::create([
            'name' => 'SERHAT TÜMER',
            'email' => 'serhat2@koksan.com',
            'password' => bcrypt('password'),
            'role_id' => $approverRole->id,
            'is_active' => true,
            'approval_status' => 'approved',
        ]);

        $template = FormTemplateM::create([
            'name' => 'HEDEFLER ŞABLONU',
            'slug' => 'hedefler-template-approver',
            'created_by' => $this->owner->id,
            'is_active' => true,
        ]);

        FormFieldM::create([
            'form_template_id' => $template->id,
            'label' => 'SATIŞ GRUBU TANIMI',
            'field_key' => 'satis_grubu_tanimi',
            'type' => 'user_list',
            'filter_by_user_group' => true,
            'sort_order' => 1,
        ]);

        $response = app(FormExportS::class)->exportEmptyTemplate($template, null, $approver);
        $this->assertInstanceOf(BinaryFileResponse::class, $response);

        $filePath = $response->getFile()->getPathname();
        $spreadsheet = IOFactory::load($filePath);
        $listSheet = $spreadsheet->getSheetByName('_LISTELER');

        $this->assertNotNull($listSheet);
        $values = [];
        for ($r = 2; $r <= 10; $r++) {
            $v = $listSheet->getCell('A' . $r)->getValue();
            if ($v !== null && $v !== '') {
                $values[] = $v;
            }
        }

        $this->assertContains('ONAYLAYAN MÜDÜR', $values);
        $this->assertContains('SERHAT TÜMER', $values);
        $this->assertGreaterThanOrEqual(3, count($values));

        @unlink($filePath);
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
