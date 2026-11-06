<?php

namespace App\Services;

use App\Models\FormEntryM;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Illuminate\Support\Facades\Storage;

class FormExportS
{
    /**
     * Form girişini Excel olarak dışa aktarır.
     * Eğer storage/app/templates/{template_id}.xlsx varsa şablon olarak kullanır.
     * Yoksa sıfırdan dinamik bir Excel tablosu oluşturur.
     */
    public function exportToExcel(FormEntryM $formEntry, array $filters = [])
    {
        $formEntry->load(['template.fields', 'rows.values', 'user']);
        $templateId = $formEntry->template->id;
        
        $templatePath = storage_path("app/templates/template_{$templateId}.xlsx");

        // Snapshot veya canlı şablon alanları
        $fieldsToExport = $formEntry->template_snapshot 
            ? json_decode(json_encode($formEntry->template_snapshot)) 
            : $formEntry->template->fields;

        if (file_exists($templatePath)) {
            // Şablona göre dışa aktar
            $spreadsheet = IOFactory::load($templatePath);
            $sheet = $spreadsheet->getActiveSheet();
        } else {
            // Şablon yoksa sıfırdan oluştur
            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setTitle(substr($formEntry->template->name, 0, 30));

            // Sütun Başlıkları 1. satır
            $colIndex = 1;
            $sheet->setCellValue([$colIndex++, 1], 'Sıra No');
            foreach ($fieldsToExport as $field) {
                $sheet->setCellValue([$colIndex++, 1], $field->label);
            }
            
            // Başlık Stili (Mavi Arkaplan, Beyaz Yazı, Daha Büyük Boyut)
            $headerRange = 'A1:' . $sheet->getHighestColumn() . '1';
            $sheet->getStyle($headerRange)->getFont()->setBold(true)->getColor()->setARGB('FFFFFFFF');
            $sheet->getStyle($headerRange)->getFont()->setSize(13);
            $sheet->getStyle($headerRange)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                ->getStartColor()->setARGB('FF1E3A8A'); // Koyu Mavi (Tailwind indigo-900 civarı)
        }

        // Verileri Yaz
        $startRow = file_exists($templatePath) ? 6 : 2; // Dinamikse 2. satırdan başlar
        
        // Gelen filtrelere göre satırları süz
        $rowsToExport = collect($formEntry->rows)->filter(function($row) use ($filters) {
            foreach ($filters as $fieldId => $allowedValues) {
                if (empty($allowedValues)) continue;
                
                $valObj = $row->values->where('form_field_id', $fieldId)->first();
                $val = trim((string)($valObj->value ?? ''));
                
                if (!in_array($val, $allowedValues)) {
                    return false;
                }
            }
            return true;
        })->values(); // Reset array keys
        
        foreach ($rowsToExport as $rowIndex => $row) {
            $currentRow = $startRow + $rowIndex;
            $colIndex = 1;
            
            // Sıra No
            $sheet->setCellValue([$colIndex++, $currentRow], $rowIndex + 1);

            foreach ($fieldsToExport as $field) {
                $valObj = $row->values->where('form_field_id', $field->id)->first();
                $val = $valObj ? $valObj->value : '';
                
                // Sayısal değerse sayı olarak yaz ki excel'de toplama yapılabilsin
                if ($field->type === 'number' && is_numeric($val)) {
                    $sheet->setCellValue([$colIndex, $currentRow], (float) $val);
                } else {
                    $sheet->setCellValue([$colIndex, $currentRow], (string) $val);
                }
                
                $colIndex++;
            }
        }

        $lastDataRow = $startRow + $rowsToExport->count();

        // Alt Toplam (Eğer açıksa ve sıfırdan oluşturulmuşsa)
        if (!file_exists($templatePath) && $formEntry->template->show_row_totals) {
            $sheet->setCellValue('A' . $lastDataRow, 'TOPLAM:');
            $sheet->getStyle('A' . $lastDataRow)->getFont()->setBold(true);

            $colIndex = 2; // İlk sütun Sıra No
            foreach ($fieldsToExport as $field) {
                if ($field->is_summable && $field->type === 'number') {
                    $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIndex);
                    $formula = '=SUM(' . $colLetter . $startRow . ':' . $colLetter . ($lastDataRow - 1) . ')';
                    $sheet->setCellValue($colLetter . $lastDataRow, $formula);
                    $sheet->getStyle($colLetter . $lastDataRow)->getFont()->setBold(true);
                }
                $colIndex++;
            }
            $lastDataRow++; // Meta bilgiler için 1 satır daha aşağı kaydır
        }

        // Genel Bilgiler (Meta data) en alta ekleniyor
        if (!file_exists($templatePath)) {
            $metaRow = $lastDataRow + 1;
            
            $sheet->setCellValue('A' . $metaRow, 'Form Adı:');
            $sheet->setCellValue('B' . $metaRow, $formEntry->template->name);
            
            $sheet->setCellValue('A' . ($metaRow + 1), 'Kullanıcı:');
            $sheet->setCellValue('B' . ($metaRow + 1), $formEntry->user->name);
            
            $sheet->setCellValue('A' . ($metaRow + 2), 'Tarih:');
            $sheet->setCellValue('B' . ($metaRow + 2), $formEntry->entry_date->format('d.m.Y'));
            
            $statusText = $formEntry->status === 'draft' ? 'TASLAK' : strtoupper($formEntry->status);
            $sheet->setCellValue('A' . ($metaRow + 3), 'Durum:');
            $sheet->setCellValue('B' . ($metaRow + 3), $statusText);

            $sheet->getStyle("A{$metaRow}:A" . ($metaRow + 3))->getFont()->setBold(true);

            // Autofit Tüm Sütunlar
            $highestColumn = $sheet->getHighestDataColumn();
            $highestColumnIndex = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($highestColumn);
            for ($i = 1; $i <= $highestColumnIndex; $i++) {
                $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($i);
                $sheet->getColumnDimension($colLetter)->setAutoSize(true);
            }
        }

        // Dosyayı oluştur ve indir
        $writer = new Xlsx($spreadsheet);
        $fileName = 'Form_' . $formEntry->id . '_' . date('Ymd_His') . '.xlsx';
        $tempPath = sys_get_temp_dir() . '/' . $fileName;
        $writer->save($tempPath);

        return response()->download($tempPath, $fileName)->deleteFileAfterSend(true);
    }

    /**
     * Sadece form şablonunun başlıklarını içeren boş bir Excel dosyası oluşturur.
     */
    public function exportEmptyTemplate(\App\Models\FormTemplateM $formTemplate)
    {
        $templateId = $formTemplate->id;
        $templatePath = storage_path("app/templates/template_{$templateId}.xlsx");
        
        $fieldsToExport = $formTemplate->fields;

        if (file_exists($templatePath)) {
            // Var olan tasarım şablonunu doğrudan indir
            $spreadsheet = IOFactory::load($templatePath);
        } else {
            // Şablon yoksa sıfırdan başlıkları oluştur
            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setTitle(substr($formTemplate->name, 0, 30));

            // Sütun Başlıkları 1. satır
            $colIndex = 1;
            $sheet->setCellValue([$colIndex++, 1], 'Sıra No');
            foreach ($fieldsToExport as $field) {
                $sheet->setCellValue([$colIndex++, 1], $field->label);
            }
            
            // Başlık Stili
            $headerRange = 'A1:' . $sheet->getHighestColumn() . '1';
            $sheet->getStyle($headerRange)->getFont()->setBold(true)->getColor()->setARGB('FFFFFFFF');
            $sheet->getStyle($headerRange)->getFont()->setSize(13);
            $sheet->getStyle($headerRange)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                ->getStartColor()->setARGB('FF1E3A8A');

            // Autofit Tüm Sütunlar
            $highestColumn = $sheet->getHighestDataColumn();
            $highestColumnIndex = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($highestColumn);
            for ($i = 1; $i <= $highestColumnIndex; $i++) {
                $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($i);
                $sheet->getColumnDimension($colLetter)->setAutoSize(true);
            }
        }

        $writer = new Xlsx($spreadsheet);
        $fileName = 'Bos_Sablon_' . $formTemplate->id . '.xlsx';
        $tempPath = sys_get_temp_dir() . '/' . $fileName;
        $writer->save($tempPath);

        return response()->download($tempPath, $fileName)->deleteFileAfterSend(true);
    }
}
