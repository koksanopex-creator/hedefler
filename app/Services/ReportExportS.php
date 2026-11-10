<?php

namespace App\Services;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportExportS
{
    /**
     * Rapor sonucunu (ReportEngineS çıktısı) PhpSpreadsheet nesnesine dönüştürür.
     */
    public function exportPivotToSpreadsheet(array $reportResult, string $reportTitle = 'Dinamik Analiz Raporu'): Spreadsheet
    {
        $spreadsheet = new Spreadsheet();
        
        // -------------------------------------------------------------
        // SAYFA 1: ÖZET VE PİVOT MATRİSİ
        // -------------------------------------------------------------
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Özet ve Matris');
        $sheet->setShowGridLines(true);

        $summary = $reportResult['summary'] ?? [];
        $pivot = $reportResult['pivotTable'] ?? [];

        // 1. Rapor Başlık Alanı
        $sheet->setCellValue('A1', 'HEDEFLER SİSTEMİ - ' . mb_strtoupper($reportTitle, 'UTF-8'));
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(15)->getColor()->setARGB('FF1E3A8A');

        $templateName = $summary['template_name'] ?? 'Genel';
        $metricTitle = $summary['full_metric_title'] ?? 'Metrik Toplamı';
        $generatedAt = $summary['generated_at'] ?? now()->format('d.m.Y H:i');

        $sheet->setCellValue('A2', "Form Şablonu: {$templateName} | Analiz Metriği: {$metricTitle} | Rapor Tarihi: {$generatedAt}");
        $sheet->getStyle('A2')->getFont()->setItalic(true)->setSize(10)->getColor()->setARGB('FF6B7280');

        $statusScopeDesc = $reportResult['status_scope']['description_text'] ?? '';
        if (!empty($statusScopeDesc)) {
            $sheet->setCellValue('A3', "Rapor Kapsamı: {$statusScopeDesc}");
            $sheet->getStyle('A3')->getFont()->setBold(true)->setSize(9)->getColor()->setARGB('FFB45309');
        }

        // 2. İstatistik Bilgi Blokları (Satır 4)
        $sheet->setCellValue('A4', 'İncelenen Form Sayısı:');
        $sheet->setCellValue('B4', $summary['total_entries'] ?? 0);
        $sheet->getStyle('A4')->getFont()->setBold(true);

        $sheet->setCellValue('C4', 'Toplam Kayıt/Satır:');
        $sheet->setCellValue('D4', $summary['total_rows'] ?? 0);
        $sheet->getStyle('C4')->getFont()->setBold(true);

        $sheet->setCellValue('E4', 'Genel Toplam:');
        $sheet->setCellValue('F4', $summary['grand_total'] ?? 0);
        $sheet->getStyle('E4')->getFont()->setBold(true);
        $sheet->getStyle('F4')->getNumberFormat()->setFormatCode('#,##0.00');

        $sheet->getStyle('A4:F4')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFF3F4F6');

        // 3. Pivot Tablo Başlıkları (Satır 6)
        $startRow = 6;
        $dimXName = $pivot['dim_x_name'] ?? 'Kategori';
        $seriesList = $pivot['series_list'] ?? ['Genel Toplam'];
        $categories = $pivot['categories'] ?? [];
        $matrix = $pivot['matrix'] ?? [];
        $rowTotals = $pivot['row_totals'] ?? [];
        $columnTotals = $pivot['column_totals'] ?? [];
        $grandTotal = $pivot['grand_total'] ?? 0;

        $colIndex = 1; // 1 = A
        $sheet->setCellValue([$colIndex++, $startRow], $dimXName);

        $isMultiSeries = (count($seriesList) > 1 || ($seriesList[0] ?? '') !== 'Genel Toplam');

        if ($isMultiSeries) {
            foreach ($seriesList as $ser) {
                $sheet->setCellValue([$colIndex++, $startRow], $ser);
            }
        }

        // Satır Toplamı Sütunu
        $sheet->setCellValue([$colIndex, $startRow], 'TOPLAM');
        $totalColIndex = $colIndex;

        // Başlık Satırını Biçimlendir
        $highestColLetter = Coordinate::stringFromColumnIndex($totalColIndex);
        $headerRange = "A{$startRow}:{$highestColLetter}{$startRow}";
        $sheet->getStyle($headerRange)->getFont()->setBold(true)->getColor()->setARGB('FFFFFFFF');
        $sheet->getStyle($headerRange)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FF1E3A8A');
        $sheet->getStyle($headerRange)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // 4. Veri Satırları
        $currentRow = $startRow + 1;
        foreach ($categories as $cat) {
            $colIndex = 1;
            // X Boyut Değeri
            $sheet->setCellValue([$colIndex++, $currentRow], $cat);
            $sheet->getStyle([1, $currentRow])->getFont()->setBold(true);

            // Seri Değerleri
            if ($isMultiSeries) {
                foreach ($seriesList as $ser) {
                    $val = $matrix[$cat][$ser] ?? 0.0;
                    $sheet->setCellValue([$colIndex, $currentRow], (float)$val);
                    $sheet->getStyle([$colIndex, $currentRow])->getNumberFormat()->setFormatCode('#,##0.00');
                    $colIndex++;
                }
            }

            // Satır Toplamı
            $rTotal = $rowTotals[$cat] ?? 0.0;
            $sheet->setCellValue([$totalColIndex, $currentRow], (float)$rTotal);
            $sheet->getStyle([$totalColIndex, $currentRow])->getFont()->setBold(true);
            $sheet->getStyle([$totalColIndex, $currentRow])->getNumberFormat()->setFormatCode('#,##0.00');
            $sheet->getStyle([$totalColIndex, $currentRow])->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFF9FAFB');

            $currentRow++;
        }

        // 5. Alt Toplam (Dip Toplam) Satırı
        $sheet->setCellValue([1, $currentRow], 'GENEL TOPLAM');
        $sheet->getStyle([1, $currentRow])->getFont()->setBold(true);

        $colIndex = 2;
        if ($isMultiSeries) {
            foreach ($seriesList as $ser) {
                $cTotal = $columnTotals[$ser] ?? 0.0;
                $sheet->setCellValue([$colIndex, $currentRow], (float)$cTotal);
                $sheet->getStyle([$colIndex, $currentRow])->getFont()->setBold(true);
                $sheet->getStyle([$colIndex, $currentRow])->getNumberFormat()->setFormatCode('#,##0.00');
                $colIndex++;
            }
        }

        // Dip Sağ Köşe (Grand Total)
        $sheet->setCellValue([$totalColIndex, $currentRow], (float)$grandTotal);
        $sheet->getStyle([$totalColIndex, $currentRow])->getFont()->setBold(true);
        $sheet->getStyle([$totalColIndex, $currentRow])->getNumberFormat()->setFormatCode('#,##0.00');

        // Dip toplam satırını renklendir
        $totalRowRange = "A{$currentRow}:{$highestColLetter}{$currentRow}";
        $sheet->getStyle($totalRowRange)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFE0E7FF'); // Light indigo
        $sheet->getStyle($totalRowRange)->getBorders()->getTop()->setBorderStyle(Border::BORDER_DOUBLE);
        $sheet->getStyle($totalRowRange)->getBorders()->getBottom()->setBorderStyle(Border::BORDER_DOUBLE);

        // Tablo Kenarlıkları
        $tableRange = "A{$startRow}:{$highestColLetter}{$currentRow}";
        $sheet->getStyle($tableRange)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->getColor()->setARGB('FFD1D5DB');

        // Sütun Genişliklerini Otomatik Ayarla
        for ($i = 1; $i <= $totalColIndex; $i++) {
            $colLetter = Coordinate::stringFromColumnIndex($i);
            $sheet->getColumnDimension($colLetter)->setAutoSize(true);
        }

        // -------------------------------------------------------------
        // SAYFA 2: DETAY KAYITLAR LİSTESİ
        // -------------------------------------------------------------
        $detailRows = $reportResult['detailRows'] ?? [];
        if (!empty($detailRows)) {
            $detailSheet = $spreadsheet->createSheet();
            $detailSheet->setTitle('Detay Kayıtlar (Örneklem)');
            $detailSheet->setShowGridLines(true);

            $detailHeaders = ['Giriş ID', 'Personel', 'Departman', 'Kayıt Tarihi', $dimXName, 'Seri / Kırılım', 'Metrik Değeri', 'Durum'];
            $dCol = 1;
            foreach ($detailHeaders as $dh) {
                $detailSheet->setCellValue([$dCol++, 1], $dh);
            }

            $detailHeaderRange = 'A1:H1';
            $detailSheet->getStyle($detailHeaderRange)->getFont()->setBold(true)->getColor()->setARGB('FFFFFFFF');
            $detailSheet->getStyle($detailHeaderRange)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FF4338CA');

            $dRow = 2;
            foreach ($detailRows as $row) {
                $detailSheet->setCellValue([1, $dRow], $row['entry_id'] ?? '');
                $detailSheet->setCellValue([2, $dRow], $row['user_name'] ?? '');
                $detailSheet->setCellValue([3, $dRow], $row['group_name'] ?? '');
                $detailSheet->setCellValue([4, $dRow], $row['entry_date'] ?? '');
                $detailSheet->setCellValue([5, $dRow], $row['dim_x'] ?? '');
                $detailSheet->setCellValue([6, $dRow], $row['dim_series'] ?? '');
                $detailSheet->setCellValue([7, $dRow], (float)($row['metric_value'] ?? 0));
                $detailSheet->getStyle([7, $dRow])->getNumberFormat()->setFormatCode('#,##0.00');
                $detailSheet->setCellValue([8, $dRow], $row['status'] ?? '');
                $dRow++;
            }

            // Sütun genişlikleri
            foreach (range('A', 'H') as $colLetter) {
                $detailSheet->getColumnDimension($colLetter)->setAutoSize(true);
            }
        }

        $spreadsheet->setActiveSheetIndex(0);
        return $spreadsheet;
    }

    /**
     * Çok Boyutlu Pivot Matrisini Excel Dosyasına Dönüştürür
     */
    public function exportMultiPivotToSpreadsheet(array $reportResult, string $reportTitle = 'Çok Boyutlu Pivot Analizi'): Spreadsheet
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Çok Boyutlu Pivot');
        $sheet->setShowGridLines(true);

        $rowDimNames = $reportResult['row_dimension_names'] ?? ['Kategori'];
        $colDimName = $reportResult['col_dimension_name'] ?? 'Sütun';
        $columns = $reportResult['columns'] ?? ['Genel Toplam'];
        $rows = $reportResult['rows'] ?? [];
        $columnTotals = $reportResult['column_totals'] ?? [];
        $grandTotal = $reportResult['grand_total'] ?? 0;
        $metricTitle = $reportResult['full_metric_title'] ?? 'Metrik';

        // Başlık
        $sheet->setCellValue('A1', 'HEDEFLER SİSTEMİ - ' . mb_strtoupper($reportTitle, 'UTF-8'));
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14)->getColor()->setARGB('FF1E3A8A');
        $sheet->setCellValue('A2', "Analiz Metriği: {$metricTitle} | Satır Boyutları: " . implode(' > ', $rowDimNames) . " | Sütun Boyutu: {$colDimName}");
        $sheet->getStyle('A2')->getFont()->setItalic(true)->setSize(10)->getColor()->setARGB('FF6B7280');

        $statusScopeDesc = $reportResult['status_scope']['description_text'] ?? '';
        if (!empty($statusScopeDesc)) {
            $sheet->setCellValue('A3', "Rapor Kapsamı: {$statusScopeDesc}");
            $sheet->getStyle('A3')->getFont()->setBold(true)->setSize(9)->getColor()->setARGB('FFB45309');
        }

        $startRow = 5;
        $colIdx = 1;

        // Satır Boyut Başlıkları
        foreach ($rowDimNames as $rDim) {
            $sheet->setCellValue([$colIdx++, $startRow], $rDim);
        }

        // Sütun Başlıkları
        foreach ($columns as $cVal) {
            $sheet->setCellValue([$colIdx++, $startRow], $cVal);
        }

        // Toplam ve Penetrasyon
        $sheet->setCellValue([$colIdx++, $startRow], 'GENEL TOPLAM');
        $sheet->setCellValue([$colIdx, $startRow], '% PENETRASYON');

        $lastColLetter = Coordinate::stringFromColumnIndex($colIdx);
        $sheet->getStyle("A{$startRow}:{$lastColLetter}{$startRow}")->getFont()->setBold(true)->getColor()->setARGB('FFFFFFFF');
        $sheet->getStyle("A{$startRow}:{$lastColLetter}{$startRow}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FF1E3A8A');
        $sheet->getStyle("A{$startRow}:{$lastColLetter}{$startRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $currRow = $startRow + 1;
        $numRowDims = count($rowDimNames);

        foreach ($rows as $r) {
            $cIdx = 1;
            // Boyut değerleri
            $dimVals = $r['dim_values'] ?? [];
            for ($i = 0; $i < $numRowDims; $i++) {
                $sheet->setCellValue([$cIdx++, $currRow], $dimVals[$i] ?? '');
            }

            // Sütun değerleri
            foreach ($columns as $cVal) {
                $v = $r['values'][$cVal] ?? 0;
                $sheet->setCellValue([$cIdx, $currRow], $v);
                $sheet->getStyle([$cIdx, $currRow])->getNumberFormat()->setFormatCode('#,##0.00');
                $cIdx++;
            }

            // Toplam
            $sheet->setCellValue([$cIdx, $currRow], $r['row_total'] ?? 0);
            $sheet->getStyle([$cIdx, $currRow])->getNumberFormat()->setFormatCode('#,##0.00');
            $sheet->getStyle([$cIdx, $currRow])->getFont()->setBold(true);
            $cIdx++;

            // Penetrasyon %
            $sheet->setCellValue([$cIdx, $currRow], ($r['penetration_share'] ?? 0) / 100);
            $sheet->getStyle([$cIdx, $currRow])->getNumberFormat()->setFormatCode('0.00%');
            $sheet->getStyle([$cIdx, $currRow])->getFont()->setBold(true);

            $currRow++;
        }

        // Genel Toplam Satırı
        $cIdx = 1;
        $sheet->setCellValue([1, $currRow], 'GENEL TOPLAM');
        $sheet->getStyle([1, $currRow])->getFont()->setBold(true);
        if ($numRowDims > 1) {
            $sheet->mergeCells("A{$currRow}:" . Coordinate::stringFromColumnIndex($numRowDims) . "{$currRow}");
        }
        $cIdx = $numRowDims + 1;

        foreach ($columns as $cVal) {
            $colTot = $columnTotals[$cVal] ?? 0;
            $sheet->setCellValue([$cIdx, $currRow], $colTot);
            $sheet->getStyle([$cIdx, $currRow])->getNumberFormat()->setFormatCode('#,##0.00');
            $sheet->getStyle([$cIdx, $currRow])->getFont()->setBold(true);
            $cIdx++;
        }

        $sheet->setCellValue([$cIdx, $currRow], $grandTotal);
        $sheet->getStyle([$cIdx, $currRow])->getNumberFormat()->setFormatCode('#,##0.00');
        $sheet->getStyle([$cIdx, $currRow])->getFont()->setBold(true);
        $cIdx++;

        $sheet->setCellValue([$cIdx, $currRow], 1.0);
        $sheet->getStyle([$cIdx, $currRow])->getNumberFormat()->setFormatCode('0.00%');
        $sheet->getStyle([$cIdx, $currRow])->getFont()->setBold(true);

        $sheet->getStyle("A{$currRow}:{$lastColLetter}{$currRow}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFE0E7FF');

        // Otomatik genişlik
        for ($c = 1; $c <= $colIdx; $c++) {
            $sheet->getColumnDimension(Coordinate::stringFromColumnIndex($c))->setAutoSize(true);
        }

        return $spreadsheet;
    }

    /**
     * Yıllık / Dönemsel Kıyaslama Raporunu Excel Dosyasına Dönüştürür
     */
    public function exportYoYToSpreadsheet(array $reportResult, string $reportTitle = 'Yıllık Kıyaslama Raporu'): Spreadsheet
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Yıllık Kıyaslama (YoY)');
        $sheet->setShowGridLines(true);

        $y1 = $reportResult['year_1'] ?? 'Yıl 1';
        $y2 = $reportResult['year_2'] ?? 'Yıl 2';
        $entityName = $reportResult['entity_dim_name'] ?? 'Varlık';
        $metricTitle = $reportResult['full_metric_title'] ?? 'Metrik';
        $summary = $reportResult['summary'] ?? [];
        $items = $reportResult['items'] ?? [];

        // Başlık
        $sheet->setCellValue('A1', 'HEDEFLER SİSTEMİ - ' . mb_strtoupper($reportTitle, 'UTF-8'));
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14)->getColor()->setARGB('FF1E3A8A');
        $sheet->setCellValue('A2', "Dönemler: {$y1} vs {$y2} | Kıyaslanan Boyut: {$entityName} | Analiz Metriği: {$metricTitle}");
        $sheet->getStyle('A2')->getFont()->setItalic(true)->setSize(10)->getColor()->setARGB('FF6B7280');

        $statusScopeDesc = $reportResult['status_scope']['description_text'] ?? '';
        if (!empty($statusScopeDesc)) {
            $sheet->setCellValue('A3', "Rapor Kapsamı: {$statusScopeDesc}");
            $sheet->getStyle('A3')->getFont()->setBold(true)->setSize(9)->getColor()->setARGB('FFB45309');
        }

        // Özet Kartları Satır 4
        $sheet->setCellValue('A4', "{$y1} Toplamı:");
        $sheet->setCellValue('B4', $summary['total_y1'] ?? 0);
        $sheet->getStyle('B4')->getNumberFormat()->setFormatCode('#,##0.00');

        $sheet->setCellValue('C4', "{$y2} Toplamı:");
        $sheet->setCellValue('D4', $summary['total_y2'] ?? 0);
        $sheet->getStyle('D4')->getNumberFormat()->setFormatCode('#,##0.00');

        $sheet->setCellValue('E4', 'Net Değişim (Fark):');
        $sheet->setCellValue('F4', $summary['total_delta'] ?? 0);
        $sheet->getStyle('F4')->getNumberFormat()->setFormatCode('#,##0.00');

        $sheet->setCellValue('G4', 'Net Büyüme:');
        $sheet->setCellValue('H4', ($summary['total_growth_rate'] ?? 0) / 100);
        $sheet->getStyle('H4')->getNumberFormat()->setFormatCode('0.0%');

        $sheet->getStyle('A4:H4')->getFont()->setBold(true);
        $sheet->getStyle('A4:H4')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFF3F4F6');

        // Tablo Başlıkları (Satır 6)
        $headers = [
            $entityName,
            "{$y1} Değeri",
            "{$y2} Değeri",
            "Fark (Değişim)",
            "% Büyüme / Değişim",
            "{$y1} Portföy Payı (%)",
            "{$y2} Portföy Payı (%)",
        ];

        $startRow = 6;
        foreach ($headers as $idx => $h) {
            $sheet->setCellValue([$idx + 1, $startRow], $h);
        }
        $sheet->getStyle("A{$startRow}:G{$startRow}")->getFont()->setBold(true)->getColor()->setARGB('FFFFFFFF');
        $sheet->getStyle("A{$startRow}:G{$startRow}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FF1E3A8A');
        $sheet->getStyle("A{$startRow}:G{$startRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $currRow = $startRow + 1;
        foreach ($items as $it) {
            $sheet->setCellValue([1, $currRow], $it['name']);
            $sheet->setCellValue([2, $currRow], $it['val_1']);
            $sheet->setCellValue([3, $currRow], $it['val_2']);
            $sheet->setCellValue([4, $currRow], $it['delta']);
            $sheet->setCellValue([5, $currRow], ($it['growth_rate'] ?? 0) / 100);
            $sheet->setCellValue([6, $currRow], ($it['share_1'] ?? 0) / 100);
            $sheet->setCellValue([7, $currRow], ($it['share_2'] ?? 0) / 100);

            $sheet->getStyle([2, $currRow])->getNumberFormat()->setFormatCode('#,##0.00');
            $sheet->getStyle([3, $currRow])->getNumberFormat()->setFormatCode('#,##0.00');
            $sheet->getStyle([4, $currRow])->getNumberFormat()->setFormatCode('#,##0.00');
            $sheet->getStyle([5, $currRow])->getNumberFormat()->setFormatCode('+0.0%;-0.0%;0.0%');
            $sheet->getStyle([6, $currRow])->getNumberFormat()->setFormatCode('0.0%');
            $sheet->getStyle([7, $currRow])->getNumberFormat()->setFormatCode('0.0%');

            // Büyüme renklendirme
            if ($it['delta'] > 0) {
                $sheet->getStyle([4, $currRow])->getFont()->getColor()->setARGB('FF059669');
                $sheet->getStyle([5, $currRow])->getFont()->getColor()->setARGB('FF059669');
            } elseif ($it['delta'] < 0) {
                $sheet->getStyle([4, $currRow])->getFont()->getColor()->setARGB('FFDC2626');
                $sheet->getStyle([5, $currRow])->getFont()->getColor()->setARGB('FFDC2626');
            }

            $currRow++;
        }

        // Toplam Satırı
        $sheet->setCellValue([1, $currRow], 'GENEL TOPLAM');
        $sheet->setCellValue([2, $currRow], $summary['total_y1'] ?? 0);
        $sheet->setCellValue([3, $currRow], $summary['total_y2'] ?? 0);
        $sheet->setCellValue([4, $currRow], $summary['total_delta'] ?? 0);
        $sheet->setCellValue([5, $currRow], ($summary['total_growth_rate'] ?? 0) / 100);
        $sheet->setCellValue([6, $currRow], 1.0);
        $sheet->setCellValue([7, $currRow], 1.0);

        $sheet->getStyle("A{$currRow}:G{$currRow}")->getFont()->setBold(true);
        $sheet->getStyle("A{$currRow}:G{$currRow}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFE0E7FF');
        $sheet->getStyle([2, $currRow])->getNumberFormat()->setFormatCode('#,##0.00');
        $sheet->getStyle([3, $currRow])->getNumberFormat()->setFormatCode('#,##0.00');
        $sheet->getStyle([4, $currRow])->getNumberFormat()->setFormatCode('#,##0.00');
        $sheet->getStyle([5, $currRow])->getNumberFormat()->setFormatCode('+0.0%;-0.0%;0.0%');
        $sheet->getStyle([6, $currRow])->getNumberFormat()->setFormatCode('0.0%');
        $sheet->getStyle([7, $currRow])->getNumberFormat()->setFormatCode('0.0%');

        for ($c = 1; $c <= 7; $c++) {
            $sheet->getColumnDimension(Coordinate::stringFromColumnIndex($c))->setAutoSize(true);
        }

        return $spreadsheet;
    }

    /**
     * Coğrafi Dünya Haritası ve Ülke Sıralamasını Excel Dosyasına Dönüştürür
     */
    public function exportGeoToSpreadsheet(array $reportResult, string $reportTitle = 'Coğrafi Ülke Analizi'): Spreadsheet
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Ülke Dağılımı');
        $sheet->setShowGridLines(true);

        $countryFieldName = $reportResult['country_field_name'] ?? 'Ülke';
        $metricTitle = $reportResult['full_metric_title'] ?? 'Metrik';
        $rankings = $reportResult['rankings'] ?? [];
        $summary = $reportResult['summary'] ?? [];

        // Başlık
        $sheet->setCellValue('A1', 'HEDEFLER SİSTEMİ - ' . mb_strtoupper($reportTitle, 'UTF-8'));
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14)->getColor()->setARGB('FF1E3A8A');
        $sheet->setCellValue('A2', "Coğrafi Dağılım | Alan: {$countryFieldName} | Analiz Metriği: {$metricTitle}");
        $sheet->getStyle('A2')->getFont()->setItalic(true)->setSize(10)->getColor()->setARGB('FF6B7280');

        $statusScopeDesc = $reportResult['status_scope']['description_text'] ?? '';
        if (!empty($statusScopeDesc)) {
            $sheet->setCellValue('A3', "Rapor Kapsamı: {$statusScopeDesc}");
            $sheet->getStyle('A3')->getFont()->setBold(true)->setSize(9)->getColor()->setARGB('FFB45309');
        }

        // Özet
        $sheet->setCellValue('A4', 'Ülke Sayısı:');
        $sheet->setCellValue('B4', $summary['total_countries'] ?? 0);
        $sheet->setCellValue('C4', 'Toplam Satır:');
        $sheet->setCellValue('D4', $summary['total_rows'] ?? 0);
        $sheet->setCellValue('E4', 'Global Toplam:');
        $sheet->setCellValue('F4', $summary['global_total'] ?? 0);
        $sheet->getStyle('F4')->getNumberFormat()->setFormatCode('#,##0.00');
        $sheet->getStyle('A4:F4')->getFont()->setBold(true);
        $sheet->getStyle('A4:F4')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFF3F4F6');

        $headers = [
            'Sıra',
            'Ülke Adı',
            'ISO Kodu',
            'Toplam Metrik Değeri',
            'Dünya Pazar Payı (%)',
            'Kayıt / Sipariş Adedi',
            'İlgili Personeller',
            'En Çok Sipariş Veren Müşteriler',
            'En Çok Satılan Malzemeler'
        ];

        $startRow = 6;
        foreach ($headers as $idx => $h) {
            $sheet->setCellValue([$idx + 1, $startRow], $h);
        }
        $sheet->getStyle("A{$startRow}:I{$startRow}")->getFont()->setBold(true)->getColor()->setARGB('FFFFFFFF');
        $sheet->getStyle("A{$startRow}:I{$startRow}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FF1E3A8A');

        $currRow = $startRow + 1;
        foreach ($rankings as $r) {
            $sheet->setCellValue([1, $currRow], '#' . ($r['rank'] ?? ''));
            $sheet->setCellValue([2, $currRow], $r['name']);
            $sheet->setCellValue([3, $currRow], $r['code']);
            $sheet->setCellValue([4, $currRow], $r['total']);
            $sheet->setCellValue([5, $currRow], ($r['share'] ?? 0) / 100);
            $sheet->setCellValue([6, $currRow], $r['count']);
            $sheet->setCellValue([7, $currRow], implode(', ', $r['top_users'] ?? []));
            $sheet->setCellValue([8, $currRow], implode(', ', $r['top_customers'] ?? []));
            $sheet->setCellValue([9, $currRow], implode(', ', $r['top_materials'] ?? []));

            $sheet->getStyle([4, $currRow])->getNumberFormat()->setFormatCode('#,##0.00');
            $sheet->getStyle([5, $currRow])->getNumberFormat()->setFormatCode('0.0%');
            $sheet->getStyle([6, $currRow])->getNumberFormat()->setFormatCode('#,##0');

            $currRow++;
        }

        // Genel Toplam Satırı
        $sheet->setCellValue([1, $currRow], 'TOPLAM');
        $sheet->setCellValue([2, $currRow], count($rankings) . ' Ülke');
        $sheet->setCellValue([4, $currRow], $summary['global_total'] ?? 0);
        $sheet->setCellValue([5, $currRow], 1.0);
        $sheet->setCellValue([6, $currRow], $summary['total_rows'] ?? 0);

        $sheet->getStyle("A{$currRow}:I{$currRow}")->getFont()->setBold(true);
        $sheet->getStyle("A{$currRow}:I{$currRow}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFE0E7FF');
        $sheet->getStyle([4, $currRow])->getNumberFormat()->setFormatCode('#,##0.00');
        $sheet->getStyle([5, $currRow])->getNumberFormat()->setFormatCode('0.0%');

        for ($c = 1; $c <= 9; $c++) {
            $sheet->getColumnDimension(Coordinate::stringFromColumnIndex($c))->setAutoSize(true);
        }

        return $spreadsheet;
    }

    /**
     * Kümeler Arası A/B Segment Kıyaslamasını Excel Dosyasına Dönüştürür
     */
    public function exportSegmentToSpreadsheet(array $reportResult, string $reportTitle = 'Segment Kıyaslama Raporu'): Spreadsheet
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Segment A vs B');
        $sheet->setShowGridLines(true);

        $segA = $reportResult['segment_a'] ?? [];
        $segB = $reportResult['segment_b'] ?? [];
        $titleA = $segA['title'] ?? 'Segment A';
        $titleB = $segB['title'] ?? 'Segment B';
        $metricTitle = $reportResult['full_metric_title'] ?? 'Metrik';
        $matrix = $reportResult['personnel_matrix'] ?? [];

        // Başlık
        $sheet->setCellValue('A1', 'HEDEFLER SİSTEMİ - ' . mb_strtoupper($reportTitle, 'UTF-8'));
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14)->getColor()->setARGB('FF1E3A8A');
        $sheet->setCellValue('A2', "Segment Kıyaslama: [{$titleA}] vs [{$titleB}] | Analiz Metriği: {$metricTitle}");
        $sheet->getStyle('A2')->getFont()->setItalic(true)->setSize(10)->getColor()->setARGB('FF6B7280');

        $statusScopeDesc = $reportResult['status_scope']['description_text'] ?? '';
        if (!empty($statusScopeDesc)) {
            $sheet->setCellValue('A3', "Rapor Kapsamı: {$statusScopeDesc}");
            $sheet->getStyle('A3')->getFont()->setBold(true)->setSize(9)->getColor()->setARGB('FFB45309');
        }

        // Özet Blokları (Satır 4 & 5)
        $sheet->setCellValue('A4', "{$titleA} Toplamı:");
        $sheet->setCellValue('B4', $segA['total'] ?? 0);
        $sheet->setCellValue('C4', "{$titleA} Sipariş Sayısı:");
        $sheet->setCellValue('D4', $segA['count'] ?? 0);
        $sheet->setCellValue('E4', "{$titleA} Filtreleri:");
        $sheet->setCellValue('F4', implode('; ', $segA['filters_summary'] ?? []));

        $sheet->setCellValue('A5', "{$titleB} Toplamı:");
        $sheet->setCellValue('B5', $segB['total'] ?? 0);
        $sheet->setCellValue('C5', "{$titleB} Sipariş Sayısı:");
        $sheet->setCellValue('D5', $segB['count'] ?? 0);
        $sheet->setCellValue('E5', "{$titleB} Filtreleri:");
        $sheet->setCellValue('F5', implode('; ', $segB['filters_summary'] ?? []));

        $sheet->getStyle('B4:B5')->getNumberFormat()->setFormatCode('#,##0.00');
        $sheet->getStyle('A4:F5')->getFont()->setBold(true);
        $sheet->getStyle('A4:F5')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFF3F4F6');

        $headers = [
            'Personel Adı',
            "{$titleA} Değeri",
            "{$titleB} Değeri",
            "{$titleA} Payı (%)",
            "{$titleB} Payı (%)",
            'Segment Tercihi',
        ];

        $startRow = 7;
        foreach ($headers as $idx => $h) {
            $sheet->setCellValue([$idx + 1, $startRow], $h);
        }
        $sheet->getStyle("A{$startRow}:F{$startRow}")->getFont()->setBold(true)->getColor()->setARGB('FFFFFFFF');
        $sheet->getStyle("A{$startRow}:F{$startRow}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FF1E3A8A');

        $currRow = $startRow + 1;
        foreach ($matrix as $pm) {
            $sheet->setCellValue([1, $currRow], $pm['user_name']);
            $sheet->setCellValue([2, $currRow], $pm['val_a']);
            $sheet->setCellValue([3, $currRow], $pm['val_b']);
            $sheet->setCellValue([4, $currRow], ($pm['share_a'] ?? 0) / 100);
            $sheet->setCellValue([5, $currRow], ($pm['share_b'] ?? 0) / 100);
            $sheet->setCellValue([6, $currRow], $pm['preference']);

            $sheet->getStyle([2, $currRow])->getNumberFormat()->setFormatCode('#,##0.00');
            $sheet->getStyle([3, $currRow])->getNumberFormat()->setFormatCode('#,##0.00');
            $sheet->getStyle([4, $currRow])->getNumberFormat()->setFormatCode('0.0%');
            $sheet->getStyle([5, $currRow])->getNumberFormat()->setFormatCode('0.0%');

            $currRow++;
        }

        // Toplam Satırı
        $sheet->setCellValue([1, $currRow], 'GENEL TOPLAM');
        $sheet->setCellValue([2, $currRow], $segA['total'] ?? 0);
        $sheet->setCellValue([3, $currRow], $segB['total'] ?? 0);
        $sheet->setCellValue([4, $currRow], 1.0);
        $sheet->setCellValue([5, $currRow], 1.0);

        $sheet->getStyle("A{$currRow}:F{$currRow}")->getFont()->setBold(true);
        $sheet->getStyle("A{$currRow}:F{$currRow}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFE0E7FF');
        $sheet->getStyle([2, $currRow])->getNumberFormat()->setFormatCode('#,##0.00');
        $sheet->getStyle([3, $currRow])->getNumberFormat()->setFormatCode('#,##0.00');
        $sheet->getStyle([4, $currRow])->getNumberFormat()->setFormatCode('0.0%');
        $sheet->getStyle([5, $currRow])->getNumberFormat()->setFormatCode('0.0%');

        for ($c = 1; $c <= 6; $c++) {
            $sheet->getColumnDimension(Coordinate::stringFromColumnIndex($c))->setAutoSize(true);
        }

        return $spreadsheet;
    }


    /**
     * Spreadsheet'i geçici bir dosyaya kaydeder ve yolunu döner (Mail eki vb. için).
     */
    public function saveToTempFile(Spreadsheet $spreadsheet, string $prefix = 'rapor'): string
    {
        $dir = storage_path('app/temp');
        if (!file_exists($dir)) {
            mkdir($dir, 0755, true);
        }

        $filename = $prefix . '_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.xlsx';
        $fullPath = $dir . DIRECTORY_SEPARATOR . $filename;

        $writer = new Xlsx($spreadsheet);
        $writer->save($fullPath);

        return $fullPath;
    }

    /**
     * Spreadsheet'i tarayıcıya indirme yanıtı (StreamedResponse) olarak döner.
     */
    public function streamDownload(Spreadsheet $spreadsheet, string $filename = 'Rapor.xlsx'): StreamedResponse
    {
        return new StreamedResponse(function() use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'Cache-Control' => 'max-age=0',
        ]);
    }
}

