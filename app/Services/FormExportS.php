<?php

namespace App\Services;

use App\Models\FormEntryM;
use App\Models\FormTemplateM;
use App\Models\MasterDataTypeM;
use App\Models\MasterDataItemM;
use App\Models\UserM;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
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
                $fieldObj = is_array($field) ? (object)$field : $field;
                $valObj = $row->values->where('form_field_id', $fieldObj->id)->first();
                $val = $valObj ? $valObj->value : '';

                // Pasiflik kontrolü
                $isPassive = false;
                if (!empty($fieldObj->condition_field_id)) {
                    $condValObj = $row->values->where('form_field_id', $fieldObj->condition_field_id)->first();
                    $condVal = $condValObj ? $condValObj->value : null;
                    $isPassive = $this->isFieldPassiveForValue($fieldObj, $condVal);
                }

                $cellCoord = [$colIndex, $currentRow];
                $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIndex);

                if ($isPassive) {
                    $sheet->setCellValue($cellCoord, '-');
                    if (!file_exists($templatePath)) {
                        $sheet->getStyle($colLetter . $currentRow)->getFill()->setFillType(Fill::FILL_SOLID)
                            ->getStartColor()->setARGB('FFF1F5F9');
                        $sheet->getStyle($colLetter . $currentRow)->getFont()->getColor()->setARGB('FF94A3B8');
                        $sheet->getStyle($colLetter . $currentRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    }
                } else {
                    // Sayısal değerse sayı olarak yaz ki excel'de toplama yapılabilsin
                    if ($fieldObj->type === 'number' && is_numeric($val)) {
                        $sheet->setCellValue($cellCoord, (float) $val);
                    } else {
                        $sheet->setCellValue($cellCoord, (string) $val);
                    }
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
     * Parçalı form ise ilgili kriter hücresini kilitler (Data Validation) ve pasif sütunları biçimlendirir.
     * Tüm dropdown (master_data, select, year, user_list) alanlarını Excel Veri Doğrulama listesi olarak ekler.
     */
    public function exportEmptyTemplate(FormTemplateM $formTemplate, ?string $partitionValue = null)
    {
        $templateId = $formTemplate->id;
        $templatePath = storage_path("app/templates/template_{$templateId}.xlsx");
        
        $fieldsToExport = $formTemplate->fields;
        $partitionField = $formTemplate->partition_field_id ? $formTemplate->fields->firstWhere('id', $formTemplate->partition_field_id) : null;

        if (file_exists($templatePath)) {
            // Var olan tasarım şablonunu doğrudan indir
            $spreadsheet = IOFactory::load($templatePath);
            $sheet = $spreadsheet->getActiveSheet();
        } else {
            // Şablon yoksa sıfırdan başlıkları oluştur
            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setTitle(substr($formTemplate->name, 0, 30));

            // Sütun Başlıkları 1. satır
            $colIndex = 1;
            $sheet->setCellValue([$colIndex++, 1], 'Sıra No');
            
            $columnMeta = []; // colIndex => ['is_partition' => bool, 'is_passive' => bool, 'field' => field]

            foreach ($fieldsToExport as $field) {
                $isPartitionCol = ($partitionField && $field->id == $partitionField->id && !empty($partitionValue));
                $isPassive = (!empty($partitionValue) && $partitionField && $field->condition_field_id == $partitionField->id && $this->isFieldPassiveForValue($field, $partitionValue));

                $sheet->setCellValue([$colIndex, 1], $field->label);
                $columnMeta[$colIndex] = [
                    'is_partition' => $isPartitionCol,
                    'is_passive' => $isPassive,
                    'field' => $field,
                ];

                $colIndex++;
            }
            
            // Başlık Stili
            $headerRange = 'A1:' . $sheet->getHighestColumn() . '1';
            $sheet->getStyle($headerRange)->getFont()->setBold(true)->getColor()->setARGB('FFFFFFFF');
            $sheet->getStyle($headerRange)->getFont()->setSize(12);
            $sheet->getStyle($headerRange)->getFill()->setFillType(Fill::FILL_SOLID)
                ->getStartColor()->setARGB('FF1E3A8A');

            // Eğer özel parçalama ve pasif sütunlar varsa başlıklarını özelleştir
            foreach ($columnMeta as $cIdx => $meta) {
                $colLetter = Coordinate::stringFromColumnIndex($cIdx);
                if ($meta['is_partition']) {
                    // Parçalama başlığı (Amber/Sarı Vurgulu)
                    $sheet->getStyle($colLetter . '1')->getFill()->setFillType(Fill::FILL_SOLID)
                        ->getStartColor()->setARGB('FFD97706'); // amber-600
                } elseif ($meta['is_passive']) {
                    // Pasif sütun başlığı (Gri)
                    $sheet->getStyle($colLetter . '1')->getFill()->setFillType(Fill::FILL_SOLID)
                        ->getStartColor()->setARGB('FF64748B'); // slate-500
                    $sheet->setCellValue([$cIdx, 1], $meta['field']->label . ' (KİLİTLİ)');
                }
            }

            // Satırları Doldur (2 ile 50 arası başlangıç satırları)
            for ($r = 2; $r <= 50; $r++) {
                $sheet->setCellValue([1, $r], $r - 1);
                $sheet->getStyle('A' . $r)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                foreach ($columnMeta as $cIdx => $meta) {
                    $cellCoordinate = [$cIdx, $r];
                    $cellLetter = Coordinate::stringFromColumnIndex($cIdx);
                    
                    if ($meta['is_partition']) {
                        // Kilitli değer
                        $sheet->setCellValue($cellCoordinate, $partitionValue);
                        $sheet->getStyle($cellLetter . $r)->getFill()->setFillType(Fill::FILL_SOLID)
                            ->getStartColor()->setARGB('FFFEF3C7'); // amber-100
                        $sheet->getStyle($cellLetter . $r)->getFont()->getColor()->setARGB('FF92400E'); // amber-800
                        $sheet->getStyle($cellLetter . $r)->getFont()->setBold(true);
                    } elseif ($meta['is_passive']) {
                        // Pasif hücre
                        $sheet->setCellValue($cellCoordinate, '-');
                        $sheet->getStyle($cellLetter . $r)->getFill()->setFillType(Fill::FILL_SOLID)
                            ->getStartColor()->setARGB('FFF1F5F9'); // slate-100
                        $sheet->getStyle($cellLetter . $r)->getFont()->getColor()->setARGB('FF94A3B8'); // slate-400
                        $sheet->getStyle($cellLetter . $r)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    }
                }
            }

            // Master data ve kullanıcı listelerini hazırla
            $masterDataTree = MasterDataTypeM::with('items')->get()->keyBy('id');
            $usersList = UserM::where('is_active', true)->orderBy('name', 'asc')->get();

            $helperSheet = null;
            $helperColIdx = 1;
            $maxValidationRow = 10000; // Kullanıcı satır eklese dahi tüm sütun boyunca kural geçerli olsun

            // Şablon içerisindeki master_data sütunlarını indeksle (Kademeli bağımlılıklar için, örn: Şehir -> Ülke)
            $masterDataColByTypeId = [];
            foreach ($columnMeta as $cIdx => $meta) {
                $f = $meta['field'];
                if ($f->type === 'master_data' && $f->master_data_type_id) {
                    $masterDataColByTypeId[$f->master_data_type_id] = [
                        'col_index' => $cIdx,
                        'col_letter' => Coordinate::stringFromColumnIndex($cIdx),
                        'field' => $f,
                        'is_partition' => $meta['is_partition'],
                    ];
                }
            }

            foreach ($columnMeta as $cIdx => $meta) {
                $colLetter = Coordinate::stringFromColumnIndex($cIdx);
                $field = $meta['field'];

                if ($meta['is_partition']) {
                    // Tüm sütun boyunca (2..10000) sadece bu işyeri girilebilecek şekilde kilitle
                    $validation = new DataValidation();
                    $validation->setType(DataValidation::TYPE_LIST);
                    $validation->setErrorStyle(DataValidation::STYLE_STOP);
                    $validation->setAllowBlank(false);
                    $validation->setShowInputMessage(true);
                    $validation->setShowErrorMessage(true);
                    $validation->setShowDropDown(true);
                    $validation->setErrorTitle('Kilitli Alan');
                    $validation->setError("Bu form sadece '{$partitionValue}' girişi için kilitlenmiştir. Bu değer değiştirilemez.");
                    $validation->setPromptTitle('Kilitli Kriter Değeri');
                    $validation->setPrompt("Bu form sadece '{$partitionValue}' için geçerlidir.");
                    $validation->setFormula1('"' . $partitionValue . '"');
                    $sheet->setDataValidation("{$colLetter}2:{$colLetter}{$maxValidationRow}", $validation);
                    continue;
                }

                if ($meta['is_passive']) {
                    // Pasif sütunu tüm satırlar boyunca (2..10000) veri girişine kapat
                    $validation = new DataValidation();
                    $validation->setType(DataValidation::TYPE_CUSTOM);
                    $validation->setErrorStyle(DataValidation::STYLE_STOP);
                    $validation->setAllowBlank(true);
                    $validation->setShowInputMessage(true);
                    $validation->setShowErrorMessage(true);
                    $validation->setErrorTitle('Kilitli / Pasif Alan');
                    $validation->setError("Bu sütun '{$partitionValue}' için girişe kapalıdır. Veri girilemez.");
                    $validation->setPromptTitle('Pasif Alan');
                    $validation->setPrompt("Bu alan bu işyeri için pasiftir.");
                    $validation->setFormula1('="-"');
                    $sheet->setDataValidation("{$colLetter}2:{$colLetter}{$maxValidationRow}", $validation);
                    continue;
                }

                // 1. Kademeli Master Data Bağımlılığı Kontrolü (Örn: Şehir alanı Ülke alanına bağlıysa)
                if ($field->type === 'master_data' && $field->master_data_type_id) {
                    $mType = $masterDataTree[$field->master_data_type_id] ?? null;
                    if ($mType && $mType->parent_id && isset($masterDataColByTypeId[$mType->parent_id])) {
                        $parentColInfo = $masterDataColByTypeId[$mType->parent_id];
                        
                        // Eğer üst kategori parçalama alanı DEĞİLSE (yani tabloda satır bazında seçilen bir kolonsa, örn: Ülke):
                        if (!$parentColInfo['is_partition']) {
                            $parentType = $masterDataTree[$mType->parent_id] ?? null;
                            if ($parentType) {
                                $parentItemsMap = $parentType->items->keyBy('id');
                                
                                // Alt verileri üst kategori ismine göre grupla
                                $grouped = [];
                                foreach ($mType->items as $childItem) {
                                    if ($childItem->parent_id && isset($parentItemsMap[$childItem->parent_id])) {
                                        $pName = trim((string)$parentItemsMap[$childItem->parent_id]->name);
                                        $grouped[$pName][] = trim((string)$childItem->name);
                                    }
                                }
                                ksort($grouped);

                                $pairRows = [];
                                foreach ($grouped as $pName => $children) {
                                    sort($children);
                                    foreach ($children as $cName) {
                                        $pairRows[] = [$pName, $cName];
                                    }
                                }

                                if (!empty($pairRows)) {
                                    if (!$helperSheet) {
                                        $helperSheet = $spreadsheet->createSheet();
                                        $helperSheet->setTitle('_LISTELER');
                                    }

                                    $pColLetter = Coordinate::stringFromColumnIndex($helperColIdx);
                                    $cColLetter = Coordinate::stringFromColumnIndex($helperColIdx + 1);
                                    $helperColIdx += 2;

                                    $helperSheet->setCellValue("{$pColLetter}1", "EŞLEŞME_" . ($parentType->name ?? 'UST'));
                                    $helperSheet->setCellValue("{$cColLetter}1", "EŞLEŞME_" . ($mType->name ?? 'ALT'));

                                    $helperSheet->fromArray($pairRows, null, "{$pColLetter}2");
                                    $lastPairRow = count($pairRows) + 1;

                                    $parentLetter = $parentColInfo['col_letter'];
                                    // Dinamik formül: Üst hücreye göre eşleşen satır aralığını kaydırarak listele
                                    $formula = "=OFFSET('_LISTELER'!\${$cColLetter}\$1, MATCH({$parentLetter}2, '_LISTELER'!\${$pColLetter}\$2:\${$pColLetter}\${$lastPairRow}, 0), 0, COUNTIF('_LISTELER'!\${$pColLetter}\$2:\${$pColLetter}\${$lastPairRow}, {$parentLetter}2), 1)";

                                    $validation = new DataValidation();
                                    $validation->setType(DataValidation::TYPE_LIST);
                                    $validation->setErrorStyle(DataValidation::STYLE_STOP);
                                    $validation->setAllowBlank(true);
                                    $validation->setShowDropDown(true);
                                    $validation->setShowErrorMessage(true);
                                    $validation->setErrorTitle('Geçersiz ' . $field->label);
                                    $validation->setError("Lütfen seçilen {$parentColInfo['field']->label} için geçerli bir {$field->label} seçiniz.");
                                    $validation->setFormula1($formula);
                                    $sheet->setDataValidation("{$colLetter}2:{$colLetter}{$maxValidationRow}", $validation);
                                    continue;
                                }
                            }
                        }
                    }
                }

                // 2. Standart / Bağımsız Dropdown Seçeneklerini Hesapla
                $options = $this->resolveFieldDropdownOptions($field, $partitionValue, $partitionField, $masterDataTree, $usersList);

                if (!empty($options)) {
                    if (!$helperSheet) {
                        $helperSheet = $spreadsheet->createSheet();
                        $helperSheet->setTitle('_LISTELER');
                    }

                    $listColLetter = Coordinate::stringFromColumnIndex($helperColIdx);
                    $helperSheet->setCellValue("{$listColLetter}1", $field->label);
                    
                    $optRows = array_map(fn($v) => [$v], $options);
                    $helperSheet->fromArray($optRows, null, "{$listColLetter}2");
                    $lastOptRow = count($optRows) + 1;

                    $validation = new DataValidation();
                    $validation->setType(DataValidation::TYPE_LIST);
                    $validation->setErrorStyle(DataValidation::STYLE_STOP);
                    $validation->setAllowBlank(true);
                    $validation->setShowDropDown(true);
                    $validation->setShowErrorMessage(true);
                    $validation->setErrorTitle('Geçersiz ' . $field->label);
                    $validation->setError("Lütfen listedeki tanımlı {$field->label} değerlerinden birini seçiniz.");
                    $validation->setFormula1("='_LISTELER'!\${$listColLetter}\$2:\${$listColLetter}\${$lastOptRow}");
                    $sheet->setDataValidation("{$colLetter}2:{$colLetter}{$maxValidationRow}", $validation);

                    $helperColIdx++;
                }
            }

            // Autofit Tüm Sütunlar
            $highestColumn = $sheet->getHighestDataColumn();
            $highestColumnIndex = Coordinate::columnIndexFromString($highestColumn);
            for ($i = 1; $i <= $highestColumnIndex; $i++) {
                $colLetter = Coordinate::stringFromColumnIndex($i);
                $sheet->getColumnDimension($colLetter)->setAutoSize(true);
            }

            // Helper sheet'i gizle ve aktif sekmeyi ana sayfa yap
            if ($helperSheet) {
                $helperSheet->setSheetState(Worksheet::SHEETSTATE_HIDDEN);
            }
            $spreadsheet->setActiveSheetIndex(0);
        }

        $cleanPart = !empty($partitionValue) ? '_' . preg_replace('/[^A-Za-z0-9_\-]/', '_', $partitionValue) : '';
        $fileName = 'Bos_Sablon_' . $formTemplate->id . $cleanPart . '.xlsx';
        $tempPath = sys_get_temp_dir() . '/' . $fileName;

        $writer = new Xlsx($spreadsheet);
        $writer->save($tempPath);

        return response()->download($tempPath, $fileName)->deleteFileAfterSend(true);
    }

    /**
     * Alanın tipine ve parçalama kriterine göre geçerli dropdown seçeneklerini döndürür.
     */
    private function resolveFieldDropdownOptions($field, ?string $partitionValue, $partitionField, $masterDataTree, $usersList): array
    {
        $options = [];

        if ($field->type === 'master_data' && $field->master_data_type_id) {
            $mType = $masterDataTree[$field->master_data_type_id] ?? null;
            if ($mType) {
                // Eğer bu master_data tipinin üst kategorisi parçalama alanına bağlıysa ve partitionValue varsa:
                if ($partitionField && $mType->parent_id && $mType->parent_id == $partitionField->master_data_type_id) {
                    if (!empty($partitionValue)) {
                        $partType = $masterDataTree[$partitionField->master_data_type_id] ?? null;
                        $partItem = $partType ? $partType->items->first(function($item) use ($partitionValue) {
                            return mb_strtolower(trim($item->name), 'UTF-8') === mb_strtolower(trim($partitionValue), 'UTF-8');
                        }) : null;

                        if ($partItem) {
                            $options = $mType->items->where('parent_id', $partItem->id)->pluck('name')->values()->toArray();
                        } else {
                            $options = [];
                        }
                    } else {
                        $options = $mType->items->pluck('name')->values()->toArray();
                    }
                } else {
                    // Parçalama alanına bağlı olmayan bağımsız master data (Örn: Aylar, Belge Türü, Ülke vb.)
                    $options = $mType->items->pluck('name')->values()->toArray();
                }
            }
        } elseif ($field->type === 'select') {
            $opts = is_string($field->options) ? json_decode($field->options, true) : ($field->options ?? []);
            if (is_array($opts)) {
                foreach ($opts as $opt) {
                    $options[] = is_array($opt) ? ($opt['value'] ?? $opt['label'] ?? '') : (string)$opt;
                }
            }
        } elseif ($field->type === 'year') {
            $currentYear = (int)date('Y');
            $opts = is_string($field->options) ? json_decode($field->options, true) : ($field->options ?? []);
            $past = isset($opts['year_past']) ? (int)$opts['year_past'] : 1;
            $future = isset($opts['year_future']) ? (int)$opts['year_future'] : 2;
            if (($opts['year_mode'] ?? '') === 'custom' && !empty($opts['year_custom_list'])) {
                $options = array_filter(array_map('trim', explode(',', $opts['year_custom_list'])));
            } else {
                for ($y = $currentYear - $past; $y <= $currentYear + $future; $y++) {
                    $options[] = (string)$y;
                }
            }
        } elseif ($field->type === 'user_list') {
            $opts = is_string($field->options) ? json_decode($field->options, true) : ($field->options ?? []);
            $excluded = $opts['excluded_user_ids'] ?? [];
            $options = $usersList->whereNotIn('id', $excluded)->pluck('name')->values()->toArray();
        }

        // Boş olmayan ve tekil değerler
        return array_values(array_unique(array_filter($options, fn($v) => trim((string)$v) !== '')));
    }

    /**
     * Bir alanın verilen kriter değerine göre pasif olup olmadığını denetler
     */
    public function isFieldPassiveForValue($field, ?string $val): bool
    {
        $fieldObj = is_array($field) ? (object)$field : $field;
        if (!$val || empty($fieldObj->is_disabled_on_condition) || empty($fieldObj->condition_field_id)) {
            return false;
        }

        $valStr = mb_strtolower(trim($val), 'UTF-8');

        if (!empty($fieldObj->disabled_condition_value)) {
            $disabledList = array_map(fn($s) => mb_strtolower(trim($s), 'UTF-8'), explode(',', (string)$fieldObj->disabled_condition_value));
            return in_array($valStr, $disabledList, true);
        }

        if (!empty($fieldObj->condition_value)) {
            $reqList = array_map(fn($s) => mb_strtolower(trim($s), 'UTF-8'), explode(',', (string)$fieldObj->condition_value));
            return !in_array($valStr, $reqList, true);
        }

        return false;
    }
}
