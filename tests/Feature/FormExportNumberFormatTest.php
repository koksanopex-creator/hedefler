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

        // A=Sıra No, B=BELGE TÜRÜ, C=TOPLAM TESLİMAT MİKTARI, D=TONAJ
        $this->assertSame(961920.0, (float) $sheet->getCell('C2')->getValue());
        $this->assertSame(NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1, $sheet->getStyle('C2')->getNumberFormat()->getFormatCode());

        $this->assertSame(10.1, (float) $sheet->getCell('D2')->getValue());
        $this->assertSame(NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1, $sheet->getStyle('D2')->getNumberFormat()->getFormatCode());

        $this->assertSame(0.51, (float) $sheet->getCell('D3')->getValue());
        $this->assertSame(NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1, $sheet->getStyle('D3')->getNumberFormat()->getFormatCode());

        // Alt toplam (=SUM) hücreleri de binlik ayraç formatında olmalı
        $this->assertSame('=SUM(C2:C3)', $sheet->getCell('C4')->getValue());
        $this->assertSame(NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1, $sheet->getStyle('C4')->getNumberFormat()->getFormatCode());
        $this->assertSame('=SUM(D2:D3)', $sheet->getCell('D4')->getValue());
        $this->assertSame(NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1, $sheet->getStyle('D4')->getNumberFormat()->getFormatCode());

        // Sıra No sütunu (#,##0.00) olmamalı
        $this->assertNotSame(NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1, $sheet->getStyle('A2')->getNumberFormat()->getFormatCode());

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
