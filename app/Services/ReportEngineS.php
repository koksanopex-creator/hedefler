<?php

namespace App\Services;

use App\Models\FormTemplateM;
use App\Models\FormFieldM;
use App\Models\FormEntryM;
use App\Models\UserM;
use App\Models\GroupM;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ReportEngineS
{
    /**
     * Şablon için seçilebilecek tüm boyutları (Dimensions) döner.
     */
    public function getAvailableDimensions(FormTemplateM $template): array
    {
        $dimensions = [
            [
                'id' => 'user',
                'name' => '👤 Veri Girişi Yapan Personel',
                'type' => 'user',
                'group' => 'Sistem Alanları',
            ],
            [
                'id' => 'group',
                'name' => '🏢 Departman / Satış Grubu',
                'type' => 'group',
                'group' => 'Sistem Alanları',
            ],
            [
                'id' => 'entry_date_month',
                'name' => '📅 Kayıt Ayı (Yıl-Ay)',
                'type' => 'entry_date_month',
                'group' => 'Zaman Boyutları',
            ],
            [
                'id' => 'entry_date_year',
                'name' => '📅 Kayıt Yılı',
                'type' => 'entry_date_year',
                'group' => 'Zaman Boyutları',
            ],
            [
                'id' => 'entry_date_day',
                'name' => '📅 Günlük Tarih',
                'type' => 'entry_date_day',
                'group' => 'Zaman Boyutları',
            ],
        ];

        // Şablonun dinamik alanları
        $fields = $template->fields()->orderBy('sort_order')->get();
        foreach ($fields as $field) {
            // Master Data, Select, Text, User List, Year boyut olarak kullanılabilir
            if (in_array($field->type, ['master_data', 'select', 'text', 'user_list', 'year']) || ($field->type === 'number' && !$field->is_summable)) {
                $icon = match ($field->type) {
                    'master_data' => '🏷️ ',
                    'select' => '📋 ',
                    'user_list' => '👥 ',
                    'year' => '📆 ',
                    default => '🔤 ',
                };

                $dimensions[] = [
                    'id' => 'field_' . $field->id,
                    'field_id' => $field->id,
                    'name' => $icon . $field->label . ($field->type === 'master_data' ? ' (Master Data)' : ''),
                    'type' => 'field',
                    'field_type' => $field->type,
                    'group' => 'Form Alanları (Dinamik)',
                ];
            }
        }

        return $dimensions;
    }

    /**
     * Şablon için hesaplanabilecek metrikleri (Metrics / Aggregations) döner.
     */
    public function getAvailableMetrics(FormTemplateM $template): array
    {
        $metrics = [
            [
                'id' => 'count',
                'name' => '🔢 Kayıt / Satır Adedi (COUNT)',
                'type' => 'count',
                'field_id' => null,
                'unit' => 'Adet',
            ],
        ];

        // Sayısal dinamik alanlar (Tonaj, Tutar, Miktar vb.)
        $numericFields = $template->fields()
            ->where(function ($q) {
                $q->where('type', 'number')
                    ->orWhere('is_summable', true);
            })
            ->orderBy('sort_order')
            ->get();

        foreach ($numericFields as $nf) {
            $metrics[] = [
                'id' => 'field_' . $nf->id,
                'field_id' => $nf->id,
                'name' => '📊 ' . $nf->label,
                'type' => 'field',
                'unit' => '',
            ];
        }

        return $metrics;
    }

    /**
     * Dinamik Rapor Analizini Gerçekleştirir.
     */
    public function generateReport(array $params, ?UserM $currentUser = null): array
    {
        $templateId = (int) ($params['form_template_id'] ?? 0);
        $template = FormTemplateM::with('fields')->findOrFail($templateId);

        $dimXType = $params['dimension_x_type'] ?? 'field';
        $dimXFieldId = !empty($params['dimension_x_field_id']) ? (int) $params['dimension_x_field_id'] : null;

        $dimSeriesType = $params['dimension_series_type'] ?? 'none';
        $dimSeriesFieldId = !empty($params['dimension_series_field_id']) ? (int) $params['dimension_series_field_id'] : null;

        $metricFieldId = !empty($params['metric_field_id']) ? (int) $params['metric_field_id'] : null;
        $aggregation = strtolower($params['metric_aggregation'] ?? 'sum');
        if (!in_array($aggregation, ['sum', 'avg', 'count', 'min', 'max'])) {
            $aggregation = 'sum';
        }

        $chartType = $params['chart_type'] ?? 'bar';
        $filters = $params['filters'] ?? [];

        // 1. Form Girişleri Ana Sorgusu
        $entries = $this->buildBaseEntriesQuery($templateId, $currentUser, $filters)->get();

        // 2. Dinamik Alan Filtreleri (field_filters)
        $fieldFilters = $filters['field_filters'] ?? [];

        // 3. Veri Matrisi Kümeleme
        // $matrix[dimX][dimSeries] = [value1, value2, ...]
        $matrix = [];
        $uniqueDimX = [];
        $uniqueDimSeries = [];
        $totalAnalyzedRows = 0;
        $allCollectedValues = [];
        $detailRows = [];

        // Hızlı arama için template alan haritası
        $fieldsMap = $template->fields->keyBy('id');

        $metricField = $metricFieldId ? $fieldsMap->get($metricFieldId) : null;
        $dimXField = ($dimXType === 'field' && $dimXFieldId) ? $fieldsMap->get($dimXFieldId) : null;
        $dimSeriesField = ($dimSeriesType === 'field' && $dimSeriesFieldId) ? $fieldsMap->get($dimSeriesFieldId) : null;

        foreach ($entries as $entry) {
            $entryUser = $entry->user;
            $entryGroup = $entryUser?->group;
            $entryDate = Carbon::parse($entry->entry_date);

            foreach ($entry->rows as $row) {
                // Row değerlerini field_id ile eşle
                $rowValues = [];
                foreach ($row->values as $v) {
                    $rowValues[$v->form_field_id] = $v->value;
                }

                // Dinamik alan filtrelerini kontrol et
                $passesFieldFilters = true;
                foreach ($fieldFilters as $fId => $filterVal) {
                    if ($filterVal === null || $filterVal === '')
                        continue;
                    $rowVal = $rowValues[$fId] ?? '';
                    if (is_array($filterVal)) {
                        if (!in_array($rowVal, $filterVal)) {
                            $passesFieldFilters = false;
                            break;
                        }
                    } else {
                        if (trim((string) $rowVal) !== trim((string) $filterVal)) {
                            $passesFieldFilters = false;
                            break;
                        }
                    }
                }

                if (!$passesFieldFilters) {
                    continue;
                }

                $totalAnalyzedRows++;

                // A) X Boyutunu Çöz (Dimension X)
                $xVal = $this->resolveDimensionValue($dimXType, $dimXField, $rowValues, $entryUser, $entryGroup, $entryDate);
                if ($xVal === '')
                    $xVal = 'Tanımsız / Belirtilmemiş';

                // B) Seri Boyutunu Çöz (Dimension Series)
                $seriesVal = 'Genel Toplam';
                if ($dimSeriesType !== 'none') {
                    $seriesVal = $this->resolveDimensionValue($dimSeriesType, $dimSeriesField, $rowValues, $entryUser, $entryGroup, $entryDate);
                    if ($seriesVal === '')
                        $seriesVal = 'Diğer / Tanımsız';
                }

                // C) Metrik Değerini Çöz
                $numericVal = 1.0;
                if ($metricFieldId) {
                    $rawMetric = $rowValues[$metricFieldId] ?? 0;
                    $numericVal = $this->parseNumericValue($rawMetric);
                }

                if (!isset($matrix[$xVal])) {
                    $matrix[$xVal] = [];
                    $uniqueDimX[$xVal] = true;
                }
                if (!isset($matrix[$xVal][$seriesVal])) {
                    $matrix[$xVal][$seriesVal] = [];
                    $uniqueDimSeries[$seriesVal] = true;
                }

                $matrix[$xVal][$seriesVal][] = $numericVal;
                $allCollectedValues[] = $numericVal;

                // İlk 100 detay satırını önizleme için sakla
                if (count($detailRows) < 100) {
                    // Satırdan anlamlı operasyonel bilgileri topla (Sipariş Veren, Malzeme, Tonaj vb.)
                    $rowInfo = [];
                    foreach ($template->fields as $f) {
                        $fVal = trim((string) ($rowValues[$f->id] ?? ''));
                        if ($fVal !== '') {
                            $rowInfo[] = "<strong>{$f->label}:</strong> {$fVal}";
                        }
                    }
                    $rowSummary = implode(' &bull; ', array_slice($rowInfo, 0, 4));

                    $effectiveStatus = $entry->hasPendingRevision() ? 'revision_pending' : $entry->status;
                    $statusMeta = self::getStatusMeta()[$effectiveStatus] ?? [
                        'key' => $effectiveStatus,
                        'label' => ucfirst($effectiveStatus),
                        'row_label' => ucfirst($effectiveStatus),
                        'bg_class' => 'bg-slate-100',
                        'text_class' => 'text-slate-700',
                        'border_class' => 'border-slate-300',
                        'dot_class' => 'bg-slate-500',
                        'hex' => '#64748b',
                    ];

                    $detailRows[] = [
                        'entry_id' => $entry->id,
                        'row_id' => $row->id,
                        'user_name' => $entryUser?->name ?? 'Bilinmiyor',
                        'group_name' => $entryGroup?->name ?? '-',
                        'entry_date' => $entryDate->format('d.m.Y'),
                        'dim_x' => $xVal,
                        'dim_series' => $seriesVal,
                        'metric_value' => $numericVal,
                        'status' => $effectiveStatus,
                        'status_label' => $statusMeta['row_label'],
                        'status_bg' => $statusMeta['bg_class'],
                        'status_text' => $statusMeta['text_class'],
                        'status_border' => $statusMeta['border_class'],
                        'status_dot' => $statusMeta['dot_class'],
                        'row_summary' => $rowSummary,
                    ];
                }
            }
        }

        $categories = array_keys($uniqueDimX);
        $seriesList = array_keys($uniqueDimSeries);

        // 4. Agregasyon Hesaplama (Matrix Reduction)
        // $reducedMatrix[dimX][series] = computed_value
        $reducedMatrix = [];
        $rowTotals = [];
        $columnTotals = array_fill_keys($seriesList, 0.0);
        $columnCounts = array_fill_keys($seriesList, 0);

        foreach ($categories as $cat) {
            $reducedMatrix[$cat] = [];
            $rowVals = [];

            foreach ($seriesList as $ser) {
                $values = $matrix[$cat][$ser] ?? [];
                $aggVal = $this->computeAggregation($values, $aggregation);
                $reducedMatrix[$cat][$ser] = $aggVal;

                if (!empty($values)) {
                    $rowVals = array_merge($rowVals, $values);
                    $columnTotals[$ser] += ($aggregation === 'avg' ? array_sum($values) : $aggVal);
                    $columnCounts[$ser] += count($values);
                }
            }

            // Satır Özeti
            $rowTotals[$cat] = $this->computeAggregation($rowVals, $aggregation);
        }

        // Eğer ortalama (avg) ise sütun toplamlarını ağırlıklı ortalama yap
        if ($aggregation === 'avg') {
            foreach ($seriesList as $ser) {
                $columnTotals[$ser] = $columnCounts[$ser] > 0 ? round($columnTotals[$ser] / $columnCounts[$ser], 2) : 0.0;
            }
        }

        // Kategorileri (X Eksenini) satır toplamına göre azalan sırala (Top N veya en yüksekten düşüğe)
        uasort($categories, function ($a, $b) use ($rowTotals) {
            return ($rowTotals[$b] ?? 0) <=> ($rowTotals[$a] ?? 0);
        });
        $categories = array_values($categories);

        // Top N sınırı varsa kes (Varsayılan tümü veya 50)
        $topN = !empty($params['top_n']) ? (int) $params['top_n'] : 0;
        if ($topN > 0 && count($categories) > $topN) {
            $categories = array_slice($categories, 0, $topN);
        }

        // 5. Chart.js Veri Yapısını İnşa Et
        $colorPalette = [
            '#4F46E5',
            '#06B6D4',
            '#10B981',
            '#F59E0B',
            '#EF4444',
            '#8B5CF6',
            '#EC4899',
            '#3B82F6',
            '#14B8A6',
            '#84CC16',
            '#F97316',
            '#6366F1',
            '#A855F7',
            '#D946EF',
            '#0EA5E9',
            '#22C55E',
            '#EAB308',
            '#64748B',
            '#0284C7',
            '#7C3AED'
        ];

        $datasets = [];

        // Tek boyutlu rapor mu (Series yok) yoksa çok boyutlu mu?
        if ($dimSeriesType === 'none' || count($seriesList) <= 1) {
            $chartDataValues = [];
            foreach ($categories as $cat) {
                $chartDataValues[] = $rowTotals[$cat] ?? 0.0;
            }

            if (in_array($chartType, ['pie', 'doughnut'])) {
                $datasets[] = [
                    'label' => $metricField ? $metricField->label : 'Kayıt Adedi',
                    'data' => $chartDataValues,
                    'backgroundColor' => array_slice(array_merge($colorPalette, $colorPalette), 0, count($chartDataValues)),
                    'borderWidth' => 1,
                ];
            } else {
                $datasets[] = [
                    'label' => $metricField ? $metricField->label : 'Toplam Kayıt',
                    'data' => $chartDataValues,
                    'backgroundColor' => '#4F46E5',
                    'borderColor' => '#4338CA',
                    'borderWidth' => 1,
                    'borderRadius' => 6,
                ];
            }
        } else {
            // Çok boyutlu kırılım (Her seri için ayrı dataset)
            $colorIdx = 0;
            foreach ($seriesList as $ser) {
                $serData = [];
                foreach ($categories as $cat) {
                    $serData[] = $reducedMatrix[$cat][$ser] ?? 0.0;
                }

                $color = $colorPalette[$colorIdx % count($colorPalette)];
                $datasets[] = [
                    'label' => $ser,
                    'data' => $serData,
                    'backgroundColor' => $color,
                    'borderColor' => $color,
                    'borderWidth' => 1,
                    'borderRadius' => 4,
                ];
                $colorIdx++;
            }
        }

        // Genel Toplam
        $grandTotal = $this->computeAggregation($allCollectedValues, $aggregation);

        // 6. Okunabilir Başlıklar
        $dimXName = match ($dimXType) {
            'user' => 'Personel',
            'group' => 'Departman / Satış Grubu',
            'entry_date_month' => 'Dönem (Ay)',
            'entry_date_year' => 'Yıl',
            'entry_date_day' => 'Tarih',
            'field' => ($dimXField ? $dimXField->label : 'Alan'),
            default => 'Kategori',
        };

        $dimSeriesName = match ($dimSeriesType) {
            'none' => 'Kırılım Yok',
            'user' => 'Personel',
            'group' => 'Departman',
            'entry_date_month' => 'Dönem (Ay)',
            'field' => ($dimSeriesField ? $dimSeriesField->label : 'Seri'),
            default => 'Seri',
        };

        $metricName = $metricField ? $metricField->label : 'Kayıt Adedi';
        $aggName = match ($aggregation) {
            'sum' => 'Toplam',
            'avg' => 'Ortalama',
            'count' => 'Adet',
            'min' => 'En Düşük (Min)',
            'max' => 'En Yüksek (Max)',
            default => 'Toplam',
        };

        return [
            'mode' => 'standard',
            'chartData' => [
                'type' => $chartType,
                'labels' => $categories,
                'datasets' => $datasets,
            ],
            'pivotTable' => [
                'dim_x_name' => $dimXName,
                'dim_series_name' => $dimSeriesName,
                'series_list' => $seriesList,
                'categories' => $categories,
                'matrix' => $reducedMatrix,
                'row_totals' => $rowTotals,
                'column_totals' => $columnTotals,
                'grand_total' => $grandTotal,
            ],
            'summary' => [
                'template_name' => $template->name,
                'total_entries' => $entries->count(),
                'total_rows' => $totalAnalyzedRows,
                'grand_total' => $grandTotal,
                'aggregation' => $aggregation,
                'aggregation_name' => $aggName,
                'metric_name' => $metricName,
                'full_metric_title' => "{$aggName} {$metricName}",
                'dim_x_name' => $dimXName,
                'dim_series_name' => $dimSeriesName,
                'generated_at' => now()->format('d.m.Y H:i'),
            ],
            'status_scope' => self::buildStatusScope($filters),
            'detailRows' => $detailRows,
        ];
    }

    /**
     * Boyut Değerini Çözümler
     */
    protected function resolveDimensionValue(string $type, ?FormFieldM $field, array $rowValues, ?UserM $user, ?GroupM $group, Carbon $date): string
    {
        if ($type === 'user') {
            return $user ? $user->name : 'Bilinmeyen Kullanıcı';
        }

        if ($type === 'group') {
            return $group ? $group->name : ($user && $user->group ? $user->group->name : 'Grubu Yok');
        }

        if ($type === 'entry_date_month') {
            $months = [
                1 => 'Ocak',
                2 => 'Şubat',
                3 => 'Mart',
                4 => 'Nisan',
                5 => 'Mayıs',
                6 => 'Haziran',
                7 => 'Temmuz',
                8 => 'Ağustos',
                9 => 'Eylül',
                10 => 'Ekim',
                11 => 'Kasım',
                12 => 'Aralık'
            ];
            return ($months[$date->month] ?? $date->month) . ' ' . $date->year;
        }

        if ($type === 'entry_date_year') {
            return (string) $date->year;
        }

        if ($type === 'entry_date_day') {
            return $date->format('d.m.Y');
        }

        if ($type === 'field' && $field) {
            $val = $rowValues[$field->id] ?? '';
            return trim((string) $val);
        }

        return '';
    }

    /**
     * Dizi üzerindeki matematiksel agregasyonu hesaplar.
     */
    protected function computeAggregation(array $values, string $func): float
    {
        if (empty($values)) {
            return 0.0;
        }

        return match ($func) {
            'count' => (float) count($values),
            'avg' => round(array_sum($values) / count($values), 2),
            'min' => round(min($values), 2),
            'max' => round(max($values), 2),
            default => round(array_sum($values), 2), // 'sum'
        };
    }

    /**
     * Sayısal metin formatlarını float'a dönüştürür.
     */
    public function parseNumericValue($val): float
    {
        if ($val === null || $val === '') {
            return 0.0;
        }

        if (is_numeric($val)) {
            return (float) $val;
        }

        $clean = trim((string) $val);

        // Türkçe binlik ve ondalık formatı: "1.257.870,69"
        if (str_contains($clean, ',') && str_contains($clean, '.')) {
            $clean = str_replace('.', '', $clean);
            $clean = str_replace(',', '.', $clean);
        } elseif (str_contains($clean, ',')) {
            $clean = str_replace(',', '.', $clean);
        }

        return is_numeric($clean) ? (float) $clean : 0.0;
    }

    /**
     * Raporlama Merkezi Form Durumu (Statü) Meta Verileri ve Renkleri
     */
    public static function getStatusMeta(): array
    {
        return [
            'approved' => [
                'key' => 'approved',
                'label' => 'Onaylananlar',
                'row_label' => 'Onaylandı',
                'desc' => 'Kesinleşmiş Satış ve Hedefler',
                'bg_class' => 'bg-emerald-100',
                'text_class' => 'text-emerald-800',
                'border_class' => 'border-emerald-200',
                'dot_class' => 'bg-emerald-500',
                'hex' => '#059669',
            ],
            'submitted' => [
                'key' => 'submitted',
                'label' => 'Onay Bekleyenler',
                'row_label' => 'Onay Bekliyor',
                'desc' => 'Yönetici İncelemesinde Olanlar',
                'bg_class' => 'bg-amber-100',
                'text_class' => 'text-amber-800',
                'border_class' => 'border-amber-200',
                'dot_class' => 'bg-amber-500',
                'hex' => '#d97706',
            ],
            'revision_pending' => [
                'key' => 'revision_pending',
                'label' => 'Düzenleme Bekleyenler',
                'row_label' => 'Düzenleme Bekliyor',
                'desc' => 'Revizyon Talebi Gönderilmiş Olanlar',
                'bg_class' => 'bg-violet-100',
                'text_class' => 'text-violet-800',
                'border_class' => 'border-violet-200',
                'dot_class' => 'bg-violet-500',
                'hex' => '#7c3aed',
            ],
            'draft' => [
                'key' => 'draft',
                'label' => 'Taslaklar',
                'row_label' => 'Taslak',
                'desc' => 'Henüz Onaya Gönderilmemiş Kayıtlar',
                'bg_class' => 'bg-slate-100',
                'text_class' => 'text-slate-700',
                'border_class' => 'border-slate-300',
                'dot_class' => 'bg-slate-500',
                'hex' => '#64748b',
            ],
            'rejected' => [
                'key' => 'rejected',
                'label' => 'Reddedilenler',
                'row_label' => 'Reddedildi',
                'desc' => 'İptal / Red Edilmiş Kayıtlar',
                'bg_class' => 'bg-rose-100',
                'text_class' => 'text-rose-800',
                'border_class' => 'border-rose-200',
                'dot_class' => 'bg-rose-500',
                'hex' => '#e11d48',
            ],
        ];
    }

    /**
     * Seçili statülere göre rapor üst bilgi bildirimini (scope) üretir
     */
    public static function buildStatusScope(array $filters): array
    {
        $rawStatuses = $filters['status'] ?? ['approved'];
        if (is_string($rawStatuses)) {
            $rawStatuses = array_filter(array_map('trim', explode(',', $rawStatuses)));
        }
        $selectedStatuses = array_values(array_filter((array) $rawStatuses));
        if (empty($selectedStatuses)) {
            $selectedStatuses = ['approved'];
        }

        $allMeta = self::getStatusMeta();
        $badges = [];
        $labelNames = [];

        foreach ($selectedStatuses as $st) {
            if (isset($allMeta[$st])) {
                $badges[] = $allMeta[$st];
                $labelNames[] = $allMeta[$st]['label'];
            }
        }

        $count = count($selectedStatuses);
        $isAll = ($count >= 5);
        $isDefault = ($count === 1 && in_array('approved', $selectedStatuses));

        if ($isAll) {
            $text = "Bu rapor tüm durumlardaki (Onaylananlar, Onay Bekleyenler, Düzenleme Bekleyenler, Taslaklar, Reddedilenler) kayıtlardan derlenmiştir.";
        } elseif ($isDefault) {
            $text = "Bu rapor yalnızca Onaylanan durumundaki kesinleşmiş kayıtlardan derlenmiştir (Taslak ve inceleme bekleyen kayıtlar hariçtir).";
        } elseif ($count === 1) {
            $text = "Bu rapor yalnızca " . ($labelNames[0] ?? 'seçili durum') . " durumundaki kayıtlardan derlenmiştir.";
        } else {
            $lastLabel = array_pop($labelNames);
            $text = "Bu rapor " . implode(', ', $labelNames) . " ve " . $lastLabel . " durumundaki kayıtlardan oluşmaktadır.";
        }

        return [
            'selected_keys' => $selectedStatuses,
            'badges' => $badges,
            'count' => $count,
            'is_multi' => ($count > 1),
            'is_all' => $isAll,
            'is_default' => $isDefault,
            'description_text' => $text,
        ];
    }

    /**
     * Ortak Temel Form Girişleri Sorgusunu Oluşturur
     */
    public function buildBaseEntriesQuery(int $templateId, ?UserM $currentUser = null, array $filters = [])
    {
        $query = FormEntryM::query()
            ->where('form_template_id', $templateId)
            ->with(['user.group', 'rows.values', 'revisionRequests']);

        if ($currentUser) {
            $permService = app(PermissionS::class);
            $canViewAll = $permService->hasPermission($currentUser, 'form_entries.view_all') ||
                ($permService->hasPermission($currentUser, 'reports.view') && ($currentUser->isSuperAdmin() || $currentUser->isYonetim()));

            if (!$canViewAll) {
                $canViewGroup = $permService->hasPermission($currentUser, 'form_entries.view_group');
                if ($canViewGroup && $currentUser->group_id) {
                    $query->whereHas('user', fn($q) => $q->where('group_id', $currentUser->group_id));
                } else {
                    $query->where('user_id', $currentUser->id);
                }
            }
        }

        if (!empty($filters['user_ids'])) {
            $userIds = array_filter(array_map('intval', (array) $filters['user_ids']));
            if (!empty($userIds)) {
                $query->whereIn('user_id', $userIds);
            }
        }

        if (!empty($filters['group_ids'])) {
            $groupIds = array_filter(array_map('intval', (array) $filters['group_ids']));
            if (!empty($groupIds)) {
                $query->whereHas('user', fn($q) => $q->whereIn('group_id', $groupIds));
            }
        }

        // Filtre: Form Onay Durumu (Varsayılan: SADECE onaylananlar 'approved')
        $rawStatuses = $filters['status'] ?? ['approved'];
        if (is_string($rawStatuses)) {
            $rawStatuses = array_filter(array_map('trim', explode(',', $rawStatuses)));
        }
        $selectedStatuses = array_values(array_filter((array) $rawStatuses));
        if (empty($selectedStatuses)) {
            $selectedStatuses = ['approved'];
        }

        $query->where(function ($q) use ($selectedStatuses) {
            $hasRevisionPending = in_array('revision_pending', $selectedStatuses);
            $hasApproved = in_array('approved', $selectedStatuses);
            $directStatuses = array_values(array_intersect($selectedStatuses, ['submitted', 'draft', 'rejected']));

            $firstCondition = true;

            if ($hasRevisionPending) {
                $q->whereHas('revisionRequests', fn($rq) => $rq->where('status', 'pending'));
                $firstCondition = false;
            }

            if ($hasApproved) {
                $approvedClosure = function ($aq) use ($hasRevisionPending) {
                    $aq->where('status', 'approved');
                    if (!$hasRevisionPending) {
                        $aq->whereDoesntHave('revisionRequests', fn($rq) => $rq->where('status', 'pending'));
                    }
                };

                if ($firstCondition) {
                    $q->where($approvedClosure);
                    $firstCondition = false;
                } else {
                    $q->orWhere($approvedClosure);
                }
            }

            if (!empty($directStatuses)) {
                if ($firstCondition) {
                    $q->whereIn('status', $directStatuses);
                } else {
                    $q->orWhereIn('status', $directStatuses);
                }
            }
        });

        if (!empty($filters['start_date'])) {
            $query->whereDate('entry_date', '>=', $filters['start_date']);
        }
        if (!empty($filters['end_date'])) {
            $query->whereDate('entry_date', '<=', $filters['end_date']);
        }

        return $query;
    }

    /**
     * Satırın dinamik alan filtrelerine uyup uymadığını denetler
     */
    public function rowPassesFilters(array $rowValues, array $filters): bool
    {
        $fieldFilters = $filters['field_filters'] ?? [];
        foreach ($fieldFilters as $fId => $filterVal) {
            if ($filterVal === null || $filterVal === '')
                continue;
            $rowVal = $rowValues[$fId] ?? '';
            if (is_array($filterVal)) {
                if (!in_array($rowVal, $filterVal)) {
                    return false;
                }
            } else {
                if (trim((string) $rowVal) !== trim((string) $filterVal)) {
                    return false;
                }
            }
        }
        return true;
    }

    /**
     * Boyut anahtarından (örn: 'field:20', 'user:0') etiketi ve satırdaki değeri çözer
     */
    public function resolveDimensionFromKey(string $dimKey, array $rowValues, ?UserM $user, ?GroupM $group, Carbon $date, $fieldsMap): array
    {
        $parts = explode(':', $dimKey);
        $type = $parts[0] ?? 'none';
        $fieldId = isset($parts[1]) ? (int) $parts[1] : null;
        $field = ($type === 'field' && $fieldId) ? $fieldsMap->get($fieldId) : null;
        $val = $this->resolveDimensionValue($type, $field, $rowValues, $user, $group, $date);

        $label = match ($type) {
            'user' => 'Personel',
            'group' => 'Departman',
            'entry_date_month' => 'Dönem (Ay)',
            'entry_date_year' => 'Yıl',
            'entry_date_day' => 'Tarih',
            'field' => ($field ? $field->label : 'Alan'),
            default => 'Kategori',
        };

        return [
            'val' => $val !== '' ? $val : 'Tanımsız / Belirtilmemiş',
            'label' => $label,
            'type' => $type,
            'field_id' => $fieldId,
        ];
    }

    /**
     * Ülke adını ISO-2 ülke koduna eşler (jsVectorMap dünya haritası ile tam uyumlu)
     */
    public function getCountryIsoCode(string $countryName): ?string
    {
        $normalized = mb_strtolower(trim($countryName), 'UTF-8');
        $map = [
            'türkiye' => 'TR',
            'turkiye' => 'TR',
            'turkey' => 'TR',
            'tr' => 'TR',
            'almanya' => 'DE',
            'germany' => 'DE',
            'deutschland' => 'DE',
            'de' => 'DE',
            'amerika' => 'US',
            'abd' => 'US',
            'usa' => 'US',
            'united states' => 'US',
            'us' => 'US',
            'ingiltere' => 'GB',
            'birleşik krallık' => 'GB',
            'united kingdom' => 'GB',
            'uk' => 'GB',
            'gb' => 'GB',
            'fransa' => 'FR',
            'france' => 'FR',
            'fr' => 'FR',
            'italya' => 'IT',
            'italy' => 'IT',
            'italia' => 'IT',
            'it' => 'IT',
            'ispanya' => 'ES',
            'spain' => 'ES',
            'es' => 'ES',
            'hollanda' => 'NL',
            'netherlands' => 'NL',
            'nl' => 'NL',
            'belçika' => 'BE',
            'belcika' => 'BE',
            'belgium' => 'BE',
            'be' => 'BE',
            'libya' => 'LY',
            'ly' => 'LY',
            'ırak' => 'IQ',
            'irak' => 'IQ',
            'iraq' => 'IQ',
            'iq' => 'IQ',
            'kazakistan' => 'KZ',
            'kazakhstan' => 'KZ',
            'kz' => 'KZ',
            'rusya' => 'RU',
            'russia' => 'RU',
            'ru' => 'RU',
            'ukrayna' => 'UA',
            'ukraine' => 'UA',
            'ua' => 'UA',
            'mısır' => 'EG',
            'misir' => 'EG',
            'egypt' => 'EG',
            'eg' => 'EG',
            'cezayir' => 'DZ',
            'algeria' => 'DZ',
            'dz' => 'DZ',
            'fas' => 'MA',
            'morocco' => 'MA',
            'ma' => 'MA',
            'tunis' => 'TN',
            'tunisia' => 'TN',
            'tn' => 'TN',
            'martinik' => 'MQ',
            'martinique' => 'MQ',
            'mq' => 'MQ',
            'anguilla' => 'AI',
            'ai' => 'AI',
            'azerbaycan' => 'AZ',
            'azerbaijan' => 'AZ',
            'az' => 'AZ',
            'gürcistan' => 'GE',
            'georgia' => 'GE',
            'ge' => 'GE',
            'özbekistan' => 'UZ',
            'uzbekistan' => 'UZ',
            'uz' => 'UZ',
            'türkmenistan' => 'TM',
            'turkmenistan' => 'TM',
            'tm' => 'TM',
            'kanada' => 'CA',
            'canada' => 'CA',
            'ca' => 'CA',
            'meksika' => 'MX',
            'mexico' => 'MX',
            'mx' => 'MX',
            'brezilya' => 'BR',
            'brazil' => 'BR',
            'br' => 'BR',
            'suudi arabistan' => 'SA',
            'saudi arabia' => 'SA',
            'sa' => 'SA',
            'bae' => 'AE',
            'birleşik arap emirlikleri' => 'AE',
            'uae' => 'AE',
            'ae' => 'AE',
            'katar' => 'QA',
            'qatar' => 'QA',
            'qa' => 'QA',
            'kuveyt' => 'KW',
            'kuwait' => 'KW',
            'kw' => 'KW',
            'yunanistan' => 'GR',
            'greece' => 'GR',
            'gr' => 'GR',
            'bulgaristan' => 'BG',
            'bulgaria' => 'BG',
            'bg' => 'BG',
            'romanya' => 'RO',
            'romania' => 'RO',
            'ro' => 'RO',
            'polonya' => 'PL',
            'poland' => 'PL',
            'pl' => 'PL',
            'isviçre' => 'CH',
            'switzerland' => 'CH',
            'ch' => 'CH',
            'avusturya' => 'AT',
            'austria' => 'AT',
            'at' => 'AT',
            'isveç' => 'SE',
            'sweden' => 'SE',
            'se' => 'SE',
            'norveç' => 'NO',
            'norway' => 'NO',
            'no' => 'NO',
            'danimarka' => 'DK',
            'denmark' => 'DK',
            'dk' => 'DK',
            'çin' => 'CN',
            'china' => 'CN',
            'cn' => 'CN',
            'japonya' => 'JP',
            'japan' => 'JP',
            'jp' => 'JP',
            'güney kore' => 'KR',
            'south korea' => 'KR',
            'kr' => 'KR',
            'hindistan' => 'IN',
            'india' => 'IN',
            'in' => 'IN',
            'avustralya' => 'AU',
            'australia' => 'AU',
            'au' => 'AU',
            'güney afrika' => 'ZA',
            'south africa' => 'ZA',
            'za' => 'ZA',
            'israil' => 'IL',
            'israel' => 'IL',
            'il' => 'IL',
            'ürdün' => 'JO',
            'jordan' => 'JO',
            'jo' => 'JO',
            'lübnan' => 'LB',
            'lebanon' => 'LB',
            'lb' => 'LB',
            'iran' => 'IR',
            'ir' => 'IR',
            'pakistan' => 'PK',
            'pk' => 'PK',
            'surinam' => 'SR',
            'suriname' => 'SR',
            'kolombiya' => 'CO',
            'colombia' => 'CO',
            'şili' => 'CL',
            'chile' => 'CL',
            'peru' => 'PE',
            'portekiz' => 'PT',
            'portugal' => 'PT',
            'çekya' => 'CZ',
            'czech republic' => 'CZ',
            'macaristan' => 'HU',
            'hungary' => 'HU',
            'sırbistan' => 'RS',
            'serbia' => 'RS',
            'hırvatistan' => 'HR',
            'croatia' => 'HR',
            'bosna hersek' => 'BA',
            'bosnia' => 'BA',
            'arnavutluk' => 'AL',
            'albania' => 'AL',
            'kuzey makedonya' => 'MK',
            'macedonia' => 'MK',
            'kosova' => 'XK',
            'kosovo' => 'XK',
            'karadağ' => 'ME',
            'montenegro' => 'ME',
            'irlanda' => 'IE',
            'ireland' => 'IE',
            'yeni zelanda' => 'NZ',
            'new zealand' => 'NZ',
            'singapur' => 'SG',
            'singapore' => 'SG',
            'malezya' => 'MY',
            'malaysia' => 'MY',
            'endonezya' => 'ID',
            'indonesia' => 'ID',
            'tayland' => 'TH',
            'thailand' => 'TH',
            'vietnam' => 'VN',
            'filipinler' => 'PH',
            'philippines' => 'PH',
            'nijerya' => 'NG',
            'nigeria' => 'NG',
            'gana' => 'GH',
            'ghana' => 'GH',
            'kenya' => 'KE',
            'etiyopya' => 'ET',
            'ethiopia' => 'ET',
            'sudan' => 'SD',
            'umman' => 'OM',
            'oman' => 'OM',
            'bahreyn' => 'BH',
            'bahrain' => 'BH',
            'yemen' => 'YE',
        ];

        return $map[$normalized] ?? (strlen($countryName) === 2 ? strtoupper($countryName) : null);
    }

    /**
     * 1. ÇOK BOYUTLU SERBEST PİVOT MATRİSİ (N-Boyutlu Kırılım & Penetrasyon)
     */
    public function generateMultiPivotReport(array $params, ?UserM $currentUser = null): array
    {
        $templateId = (int) ($params['form_template_id'] ?? 0);
        $template = FormTemplateM::with('fields')->findOrFail($templateId);
        $fieldsMap = $template->fields->keyBy('id');

        $rowDimKeys = (array) ($params['row_dimensions'] ?? ['field:20']);
        if (empty($rowDimKeys)) {
            $rowDimKeys = ['field:20'];
        }
        $colDimKey = $params['col_dimension'] ?? 'none:0';

        $metricFieldId = !empty($params['metric_field_id']) ? (int) $params['metric_field_id'] : null;
        $aggregation = strtolower($params['metric_aggregation'] ?? 'sum');
        $filters = $params['filters'] ?? [];
        $chartType = $params['chart_type'] ?? 'bar';

        $entries = $this->buildBaseEntriesQuery($templateId, $currentUser, $filters)->get();

        $metricField = $metricFieldId ? $fieldsMap->get($metricFieldId) : null;
        $metricName = $metricField ? $metricField->label : 'Kayıt Adedi';
        $aggName = match ($aggregation) {
            'sum' => 'Toplam', 'avg' => 'Ortalama', 'count' => 'Adet', 'min' => 'En Düşük', 'max' => 'En Yüksek', default => 'Toplam'
        };

        // Satır ve sütun boyut etiketleri
        $rowDimLabels = [];
        foreach ($rowDimKeys as $rKey) {
            $parts = explode(':', $rKey);
            $type = $parts[0] ?? 'none';
            $fId = isset($parts[1]) ? (int) $parts[1] : null;
            $f = ($type === 'field' && $fId) ? $fieldsMap->get($fId) : null;
            $rowDimLabels[] = match ($type) {
                'user' => 'Personel',
                'group' => 'Departman',
                'entry_date_month' => 'Dönem (Ay)',
                'entry_date_year' => 'Yıl',
                'field' => ($f ? $f->label : 'Alan'),
                default => 'Kategori'
            };
        }

        $colDimParts = explode(':', $colDimKey);
        $colDimType = $colDimParts[0] ?? 'none';
        $colDimFieldId = isset($colDimParts[1]) ? (int) $colDimParts[1] : null;
        $colDimField = ($colDimType === 'field' && $colDimFieldId) ? $fieldsMap->get($colDimFieldId) : null;
        $colDimLabel = match ($colDimType) {
            'none' => 'Kırılım Yok',
            'user' => 'Personel',
            'group' => 'Departman',
            'entry_date_month' => 'Dönem (Ay)',
            'entry_date_year' => 'Yıl',
            'field' => ($colDimField ? $colDimField->label : 'Sütun'),
            default => 'Sütun'
        };

        $matrix = []; // matrix[compositeKey][colKey] = [vals]
        $rowDimValuesMap = []; // compositeKey => [dim1, dim2, ...]
        $allUniqueCols = [];
        $allCollectedValues = [];
        $totalAnalyzedRows = 0;

        foreach ($entries as $entry) {
            $entryUser = $entry->user;
            $entryGroup = $entryUser?->group;
            $entryDate = Carbon::parse($entry->entry_date);

            foreach ($entry->rows as $row) {
                $rowValues = [];
                foreach ($row->values as $v) {
                    $rowValues[$v->form_field_id] = $v->value;
                }

                if (!$this->rowPassesFilters($rowValues, $filters)) {
                    continue;
                }

                $totalAnalyzedRows++;

                // Satır boyut değerlerini birleştir
                $rowValuesList = [];
                foreach ($rowDimKeys as $rKey) {
                    $res = $this->resolveDimensionFromKey($rKey, $rowValues, $entryUser, $entryGroup, $entryDate, $fieldsMap);
                    $rowValuesList[] = $res['val'];
                }
                $compositeKey = implode(' ➔ ', $rowValuesList);
                $rowDimValuesMap[$compositeKey] = $rowValuesList;

                // Sütun boyutunu çöz
                $colVal = 'Genel Toplam';
                if ($colDimType !== 'none') {
                    $colRes = $this->resolveDimensionFromKey($colDimKey, $rowValues, $entryUser, $entryGroup, $entryDate, $fieldsMap);
                    $colVal = $colRes['val'];
                }
                $allUniqueCols[$colVal] = true;

                // Metrik değeri
                $numericVal = 1.0;
                if ($metricFieldId) {
                    $numericVal = $this->parseNumericValue($rowValues[$metricFieldId] ?? 0);
                }

                if (!isset($matrix[$compositeKey])) {
                    $matrix[$compositeKey] = [];
                }
                if (!isset($matrix[$compositeKey][$colVal])) {
                    $matrix[$compositeKey][$colVal] = [];
                }
                $matrix[$compositeKey][$colVal][] = $numericVal;
                $allCollectedValues[] = $numericVal;
            }
        }

        $grandTotal = $this->computeAggregation($allCollectedValues, $aggregation);
        $colList = array_keys($allUniqueCols);
        sort($colList);

        $rowTotals = [];
        $columnTotals = array_fill_keys($colList, 0.0);
        $columnCounts = array_fill_keys($colList, 0);
        $reducedMatrix = [];

        foreach ($matrix as $compKey => $cols) {
            $reducedMatrix[$compKey] = [];
            $rowVals = [];

            foreach ($colList as $col) {
                $vals = $cols[$col] ?? [];
                $aggVal = $this->computeAggregation($vals, $aggregation);
                $reducedMatrix[$compKey][$col] = $aggVal;

                if (!empty($vals)) {
                    $rowVals = array_merge($rowVals, $vals);
                    $columnTotals[$col] += ($aggregation === 'avg' ? array_sum($vals) : $aggVal);
                    $columnCounts[$col] += count($vals);
                }
            }

            $rowTotals[$compKey] = $this->computeAggregation($rowVals, $aggregation);
        }

        if ($aggregation === 'avg') {
            foreach ($colList as $col) {
                $columnTotals[$col] = $columnCounts[$col] > 0 ? round($columnTotals[$col] / $columnCounts[$col], 2) : 0.0;
            }
        }

        // Satırları toplamına göre azalan sırala
        arsort($rowTotals);

        // Satır veri listesi inşa et
        $rowsData = [];
        foreach ($rowTotals as $compKey => $totalVal) {
            $penetration = ($grandTotal > 0) ? round(($totalVal / $grandTotal) * 100, 2) : 0.0;
            $rowsData[] = [
                'composite_key' => $compKey,
                'dim_values' => $rowDimValuesMap[$compKey] ?? [$compKey],
                'values' => $reducedMatrix[$compKey] ?? [],
                'row_total' => $totalVal,
                'penetration_share' => $penetration,
            ];
        }

        // Chart.js verisi (Top 15 satır)
        $topCategories = array_slice(array_keys($rowTotals), 0, 15);
        $colorPalette = [
            '#4F46E5',
            '#06B6D4',
            '#10B981',
            '#F59E0B',
            '#EF4444',
            '#8B5CF6',
            '#EC4899',
            '#3B82F6',
            '#14B8A6',
            '#84CC16',
        ];

        $chartDatasets = [];
        if ($colDimType === 'none' || count($colList) <= 1) {
            $dValues = [];
            foreach ($topCategories as $cat) {
                $dValues[] = $rowTotals[$cat] ?? 0.0;
            }
            $chartDatasets[] = [
                'label' => "{$aggName} {$metricName}",
                'data' => $dValues,
                'backgroundColor' => '#4F46E5',
                'borderRadius' => 6,
            ];
        } else {
            $colorIdx = 0;
            foreach ($colList as $col) {
                $dValues = [];
                foreach ($topCategories as $cat) {
                    $dValues[] = $reducedMatrix[$cat][$col] ?? 0.0;
                }
                $color = $colorPalette[$colorIdx % count($colorPalette)];
                $chartDatasets[] = [
                    'label' => $col,
                    'data' => $dValues,
                    'backgroundColor' => $color,
                    'borderRadius' => 4,
                ];
                $colorIdx++;
            }
        }

        return [
            'mode' => 'multi_pivot',
            'row_dimension_names' => $rowDimLabels,
            'col_dimension_name' => $colDimLabel,
            'columns' => $colList,
            'rows' => $rowsData,
            'column_totals' => $columnTotals,
            'grand_total' => $grandTotal,
            'metric_name' => $metricName,
            'aggregation_name' => $aggName,
            'full_metric_title' => "{$aggName} {$metricName}",
            'summary' => [
                'total_rows' => count($rowsData),
                'grand_total' => $grandTotal,
                'analyzed_records' => $totalAnalyzedRows,
                'total_columns' => count($colList),
            ],
            'chartData' => [
                'type' => $chartType,
                'labels' => $topCategories,
                'datasets' => $chartDatasets,
            ],
            'status_scope' => self::buildStatusScope($filters),
        ];
    }

    /**
     * 2. YILLIK / DÖNEMSEL KIYASLAMA (2026 vs 2027 YoY Delta & Büyüme Analizi)
     */
    public function generateYoYReport(array $params, ?UserM $currentUser = null): array
    {
        $templateId = (int) ($params['form_template_id'] ?? 0);
        $template = FormTemplateM::with('fields')->findOrFail($templateId);
        $fieldsMap = $template->fields->keyBy('id');

        $year1 = (string) ($params['year_1'] ?? '2026');
        $year2 = (string) ($params['year_2'] ?? '2027');

        $entityDimKey = $params['entity_dim_key'] ?? 'user:0';
        $metricFieldId = !empty($params['metric_field_id']) ? (int) $params['metric_field_id'] : null;
        $aggregation = strtolower($params['metric_aggregation'] ?? 'sum');
        $filters = $params['filters'] ?? [];

        $entries = $this->buildBaseEntriesQuery($templateId, $currentUser, $filters)->get();

        $yearField = $template->fields->first(fn($f) => $f->type === 'year' || mb_strtoupper($f->label) === 'YIL');
        $metricField = $metricFieldId ? $fieldsMap->get($metricFieldId) : null;
        $metricName = $metricField ? $metricField->label : 'Kayıt Adedi';
        $aggName = match ($aggregation) {
            'sum' => 'Toplam', 'avg' => 'Ortalama', 'count' => 'Adet', 'min' => 'En Düşük', 'max' => 'En Yüksek', default => 'Toplam'
        };

        $entityValuesY1 = [];
        $entityValuesY2 = [];
        $allEntities = [];
        $entityDimName = 'Personel';

        foreach ($entries as $entry) {
            $entryUser = $entry->user;
            $entryGroup = $entryUser?->group;
            $entryDate = Carbon::parse($entry->entry_date);

            foreach ($entry->rows as $row) {
                $rowValues = [];
                foreach ($row->values as $v) {
                    $rowValues[$v->form_field_id] = $v->value;
                }

                if (!$this->rowPassesFilters($rowValues, $filters)) {
                    continue;
                }

                // Satırın yılını belirle
                $rowYear = '';
                if ($yearField && !empty($rowValues[$yearField->id])) {
                    $rowYear = trim((string) $rowValues[$yearField->id]);
                } else {
                    $rowYear = (string) $entryDate->year;
                }

                // Yalnızca seçilen iki yılı değerlendir (veya tek yıl girilmişse onu al)
                if ($rowYear !== $year1 && $rowYear !== $year2) {
                    continue;
                }

                $res = $this->resolveDimensionFromKey($entityDimKey, $rowValues, $entryUser, $entryGroup, $entryDate, $fieldsMap);
                $entityName = $res['val'];
                $entityDimName = $res['label'];

                $numericVal = 1.0;
                if ($metricFieldId) {
                    $numericVal = $this->parseNumericValue($rowValues[$metricFieldId] ?? 0);
                }

                $allEntities[$entityName] = true;

                if ($rowYear === $year1) {
                    $entityValuesY1[$entityName][] = $numericVal;
                } elseif ($rowYear === $year2) {
                    $entityValuesY2[$entityName][] = $numericVal;
                }
            }
        }

        // Genel toplamlar
        $allValsY1 = [];
        $allValsY2 = [];
        foreach ($entityValuesY1 as $vals)
            $allValsY1 = array_merge($allValsY1, $vals);
        foreach ($entityValuesY2 as $vals)
            $allValsY2 = array_merge($allValsY2, $vals);

        $totalY1 = $this->computeAggregation($allValsY1, $aggregation);
        $totalY2 = $this->computeAggregation($allValsY2, $aggregation);
        $totalDelta = round($totalY2 - $totalY1, 2);
        $totalGrowthRate = ($totalY1 > 0) ? round(($totalDelta / $totalY1) * 100, 1) : (($totalY2 > 0) ? 100.0 : 0.0);

        // Varlık bazlı delta ve büyüme oranları
        $items = [];
        $topGrower = null;
        $topDecliner = null;
        $maxGrowth = -999999;
        $minGrowth = 999999;

        foreach (array_keys($allEntities) as $eName) {
            $vals1 = $entityValuesY1[$eName] ?? [];
            $vals2 = $entityValuesY2[$eName] ?? [];

            $v1 = $this->computeAggregation($vals1, $aggregation);
            $v2 = $this->computeAggregation($vals2, $aggregation);
            $delta = round($v2 - $v1, 2);

            $growth = 0.0;
            if ($v1 > 0) {
                $growth = round(($delta / $v1) * 100, 1);
            } elseif ($v2 > 0) {
                $growth = 100.0;
            }

            $share1 = ($totalY1 > 0) ? round(($v1 / $totalY1) * 100, 1) : 0.0;
            $share2 = ($totalY2 > 0) ? round(($v2 / $totalY2) * 100, 1) : 0.0;

            if ($v1 > 0 && $growth > $maxGrowth) {
                $maxGrowth = $growth;
                $topGrower = ['name' => $eName, 'growth' => $growth, 'delta' => $delta];
            }
            if ($v1 > 0 && $growth < $minGrowth) {
                $minGrowth = $growth;
                $topDecliner = ['name' => $eName, 'growth' => $growth, 'delta' => $delta];
            }

            $items[] = [
                'name' => $eName,
                'val_1' => $v1,
                'val_2' => $v2,
                'delta' => $delta,
                'growth_rate' => $growth,
                'share_1' => $share1,
                'share_2' => $share2,
            ];
        }

        // Sıralama: En yüksek 2. Yıl değerine (veya 1. yıla) göre
        usort($items, function ($a, $b) {
            return ($b['val_2'] + $b['val_1']) <=> ($a['val_2'] + $a['val_1']);
        });

        // Grafik verisi (Top 20 varlık)
        $chartLabels = [];
        $chartDataY1 = [];
        $chartDataY2 = [];

        foreach (array_slice($items, 0, 20) as $it) {
            $chartLabels[] = $it['name'];
            $chartDataY1[] = $it['val_1'];
            $chartDataY2[] = $it['val_2'];
        }

        return [
            'mode' => 'yoy',
            'year_1' => $year1,
            'year_2' => $year2,
            'entity_dim_name' => $entityDimName,
            'metric_name' => $metricName,
            'aggregation_name' => $aggName,
            'full_metric_title' => "{$aggName} {$metricName}",
            'items' => $items,
            'summary' => [
                'total_y1' => $totalY1,
                'total_y2' => $totalY2,
                'total_delta' => $totalDelta,
                'total_growth_rate' => $totalGrowthRate,
                'top_grower' => $topGrower,
                'top_decliner' => $topDecliner,
                'total_entities' => count($items),
            ],
            'chartData' => [
                'type' => 'bar',
                'labels' => $chartLabels,
                'datasets' => [
                    [
                        'label' => "{$year1} ({$aggName} {$metricName})",
                        'data' => $chartDataY1,
                        'backgroundColor' => '#3b82f6',
                        'borderColor' => '#2563eb',
                        'borderWidth' => 1,
                        'borderRadius' => 6,
                    ],
                    [
                        'label' => "{$year2} ({$aggName} {$metricName})",
                        'data' => $chartDataY2,
                        'backgroundColor' => '#10b981',
                        'borderColor' => '#059669',
                        'borderWidth' => 1,
                        'borderRadius' => 6,
                    ],
                ],
            ],
            'status_scope' => self::buildStatusScope($filters),
        ];
    }

    /**
     * 3. COĞRAFİ DÜNYA HARİTASI ANALİZİ (jsVectorMap Isı Haritası & Ülke Sıralaması)
     */
    public function generateGeoReport(array $params, ?UserM $currentUser = null): array
    {
        $templateId = (int) ($params['form_template_id'] ?? 0);
        $template = FormTemplateM::with('fields')->findOrFail($templateId);
        $fieldsMap = $template->fields->keyBy('id');

        // Ülke alanını bul
        $countryFieldId = !empty($params['country_field_id']) ? (int) $params['country_field_id'] : null;
        if (!$countryFieldId) {
            $cField = $template->fields->first(fn($f) => mb_stripos($f->label, 'Ülke') !== false || mb_stripos($f->label, 'Ulke') !== false || mb_stripos($f->label, 'Country') !== false);
            $countryFieldId = $cField?->id;
        }
        $countryField = $countryFieldId ? $fieldsMap->get($countryFieldId) : null;

        $cityField = $template->fields->first(fn($f) => mb_stripos($f->label, 'Şehir') !== false || mb_stripos($f->label, 'Sehir') !== false);
        $customerField = $template->fields->first(fn($f) => mb_stripos($f->label, 'Sipariş Veren') !== false || mb_stripos($f->label, 'Müşteri') !== false);
        $materialField = $template->fields->first(fn($f) => mb_stripos($f->label, 'Malzeme Tanımı') !== false || mb_stripos($f->label, 'Malzeme') !== false);

        $metricFieldId = !empty($params['metric_field_id']) ? (int) $params['metric_field_id'] : null;
        $aggregation = strtolower($params['metric_aggregation'] ?? 'sum');
        $filters = $params['filters'] ?? [];

        $entries = $this->buildBaseEntriesQuery($templateId, $currentUser, $filters)->get();

        $metricField = $metricFieldId ? $fieldsMap->get($metricFieldId) : null;
        $metricName = $metricField ? $metricField->label : 'Kayıt Adedi';
        $aggName = match ($aggregation) {
            'sum' => 'Toplam', 'avg' => 'Ortalama', 'count' => 'Adet', 'min' => 'En Düşük', 'max' => 'En Yüksek', default => 'Toplam'
        };

        $countryGroups = []; // iso => ['name' => ..., 'values' => [], 'users' => [], 'customers' => [], 'materials' => [], 'cities' => []]
        $allValues = [];
        $totalRows = 0;

        foreach ($entries as $entry) {
            $entryUser = $entry->user;

            foreach ($entry->rows as $row) {
                $rowValues = [];
                foreach ($row->values as $v) {
                    $rowValues[$v->form_field_id] = $v->value;
                }

                if (!$this->rowPassesFilters($rowValues, $filters)) {
                    continue;
                }

                // Ülke adını al
                $rawCountry = '';
                if ($countryField) {
                    $rawCountry = trim((string) ($rowValues[$countryField->id] ?? ''));
                }
                if ($rawCountry === '') {
                    $rawCountry = 'Türkiye'; // Varsayılan ülke
                }

                $iso = $this->getCountryIsoCode($rawCountry);
                if (!$iso) {
                    $iso = 'TR'; // Bilinmeyen durumunda Türkiye fallback
                }

                $numericVal = 1.0;
                if ($metricFieldId) {
                    $numericVal = $this->parseNumericValue($rowValues[$metricFieldId] ?? 0);
                }

                if (!isset($countryGroups[$iso])) {
                    $countryGroups[$iso] = [
                        'name' => $rawCountry,
                        'code' => $iso,
                        'values' => [],
                        'users' => [],
                        'customers' => [],
                        'materials' => [],
                        'cities' => [],
                    ];
                }

                $countryGroups[$iso]['values'][] = $numericVal;
                $allValues[] = $numericVal;
                $totalRows++;

                // Personel
                $uName = $entryUser?->name ?? 'Bilinmeyen Personel';
                $countryGroups[$iso]['users'][$uName] = ($countryGroups[$iso]['users'][$uName] ?? 0.0) + $numericVal;

                // Müşteri
                if ($customerField && !empty($rowValues[$customerField->id])) {
                    $cName = trim((string) $rowValues[$customerField->id]);
                    $countryGroups[$iso]['customers'][$cName] = ($countryGroups[$iso]['customers'][$cName] ?? 0.0) + $numericVal;
                }

                // Malzeme
                if ($materialField && !empty($rowValues[$materialField->id])) {
                    $mName = trim((string) $rowValues[$materialField->id]);
                    $countryGroups[$iso]['materials'][$mName] = ($countryGroups[$iso]['materials'][$mName] ?? 0.0) + $numericVal;
                }

                // Şehir
                if ($cityField && !empty($rowValues[$cityField->id])) {
                    $cityName = trim((string) $rowValues[$cityField->id]);
                    $countryGroups[$iso]['cities'][$cityName] = ($countryGroups[$iso]['cities'][$cityName] ?? 0) + 1;
                }
            }
        }

        $globalTotal = $this->computeAggregation($allValues, $aggregation);

        // Maksimum ülke toplamını bul (ısı haritası renk skalası için)
        $maxCountryTotal = 0.0;
        $geoCountries = [];
        $rankings = [];

        foreach ($countryGroups as $iso => $data) {
            $cTotal = $this->computeAggregation($data['values'], $aggregation);
            if ($cTotal > $maxCountryTotal) {
                $maxCountryTotal = $cTotal;
            }

            // Top personeller
            arsort($data['users']);
            $topUsers = [];
            foreach (array_slice($data['users'], 0, 3, true) as $u => $val) {
                $pShare = ($cTotal > 0) ? round(($val / $cTotal) * 100, 1) : 0;
                $topUsers[] = "{$u} (%{$pShare})";
            }

            // Top müşteriler
            arsort($data['customers']);
            $topCustomers = array_keys(array_slice($data['customers'], 0, 3, true));

            // Top malzemeler
            arsort($data['materials']);
            $topMaterials = array_keys(array_slice($data['materials'], 0, 3, true));

            $share = ($globalTotal > 0) ? round(($cTotal / $globalTotal) * 100, 1) : 0.0;

            $item = [
                'name' => $data['name'],
                'code' => $iso,
                'total' => $cTotal,
                'count' => count($data['values']),
                'share' => $share,
                'top_users' => $topUsers,
                'top_customers' => $topCustomers,
                'top_materials' => $topMaterials,
                'cities' => array_keys($data['cities']),
            ];

            $geoCountries[$iso] = $item;
            $rankings[] = $item;
        }

        // Sıralama tablosu
        usort($rankings, fn($a, $b) => $b['total'] <=> $a['total']);
        foreach ($rankings as $idx => &$r) {
            $r['rank'] = $idx + 1;
        }

        // Isı haritası renkleri (Choropleth Scale: açık indigo -> koyu lacivert)
        $countryColors = [];
        foreach ($geoCountries as $iso => $c) {
            $ratio = ($maxCountryTotal > 0) ? ($c['total'] / $maxCountryTotal) : 0.5;
            // 0.2 ile 1.0 arası opaklık / tonlama
            if ($ratio > 0.8) {
                $countryColors[$iso] = '#312e81'; // Koyu İndigo
            } elseif ($ratio > 0.5) {
                $countryColors[$iso] = '#4338ca'; // İndigo
            } elseif ($ratio > 0.25) {
                $countryColors[$iso] = '#6366f1'; // Orta İndigo
            } elseif ($ratio > 0.1) {
                $countryColors[$iso] = '#818cf8'; // Açık İndigo
            } else {
                $countryColors[$iso] = '#a5b4fc'; // Çok Açık İndigo
            }
            $geoCountries[$iso]['color'] = $countryColors[$iso];
        }

        return [
            'mode' => 'geo',
            'country_field_name' => $countryField?->label ?? 'Ülke',
            'metric_name' => $metricName,
            'aggregation_name' => $aggName,
            'full_metric_title' => "{$aggName} {$metricName}",
            'geoCountries' => $geoCountries,
            'countryColors' => $countryColors,
            'rankings' => $rankings,
            'summary' => [
                'total_countries' => count($rankings),
                'global_total' => $globalTotal,
                'total_rows' => $totalRows,
                'top_country' => $rankings[0] ?? null,
            ],
            'status_scope' => self::buildStatusScope($filters),
        ];
    }

    /**
     * 4. KÜMELER ARASI A/B SEGMENT KIYASLAMA (Scenario & Cluster Benchmark)
     */
    public function generateSegmentBenchmarkReport(array $params, ?UserM $currentUser = null): array
    {
        $templateId = (int) ($params['form_template_id'] ?? 0);
        $template = FormTemplateM::with('fields')->findOrFail($templateId);
        $fieldsMap = $template->fields->keyBy('id');

        $segA = $params['segment_a'] ?? [];
        $segB = $params['segment_b'] ?? [];
        $titleA = !empty($segA['title']) ? $segA['title'] : 'Segment A';
        $titleB = !empty($segB['title']) ? $segB['title'] : 'Segment B';

        $metricFieldId = !empty($params['metric_field_id']) ? (int) $params['metric_field_id'] : null;
        $aggregation = strtolower($params['metric_aggregation'] ?? 'sum');

        $filters = $params['filters'] ?? [];
        $entries = $this->buildBaseEntriesQuery($templateId, $currentUser, $filters)->get();

        $metricField = $metricFieldId ? $fieldsMap->get($metricFieldId) : null;
        $metricName = $metricField ? $metricField->label : 'Kayıt Adedi';
        $aggName = match ($aggregation) {
            'sum' => 'Toplam', 'avg' => 'Ortalama', 'count' => 'Adet', 'min' => 'En Düşük', 'max' => 'En Yüksek', default => 'Toplam'
        };

        $valsA = [];
        $valsB = [];
        $userValsA = [];
        $userValsB = [];
        $allUsers = [];

        foreach ($entries as $entry) {
            $entryUser = $entry->user;
            $userName = $entryUser?->name ?? 'Bilinmeyen Personel';

            foreach ($entry->rows as $row) {
                $rowValues = [];
                foreach ($row->values as $v) {
                    $rowValues[$v->form_field_id] = $v->value;
                }

                $numericVal = 1.0;
                if ($metricFieldId) {
                    $numericVal = $this->parseNumericValue($rowValues[$metricFieldId] ?? 0);
                }

                // Segment A Kontrolü
                if ($this->rowPassesFilters($rowValues, $segA['filters'] ?? [])) {
                    $valsA[] = $numericVal;
                    $userValsA[$userName][] = $numericVal;
                    $allUsers[$userName] = true;
                }

                // Segment B Kontrolü
                if ($this->rowPassesFilters($rowValues, $segB['filters'] ?? [])) {
                    $valsB[] = $numericVal;
                    $userValsB[$userName][] = $numericVal;
                    $allUsers[$userName] = true;
                }
            }
        }

        $totalA = $this->computeAggregation($valsA, $aggregation);
        $totalB = $this->computeAggregation($valsB, $aggregation);
        $countA = count($valsA);
        $countB = count($valsB);

        // Personel karşılaştırma matrisi
        $personnelMatrix = [];
        foreach (array_keys($allUsers) as $u) {
            $uValsA = $userValsA[$u] ?? [];
            $uValsB = $userValsB[$u] ?? [];
            $uTotalA = $this->computeAggregation($uValsA, $aggregation);
            $uTotalB = $this->computeAggregation($uValsB, $aggregation);

            $shareA = ($totalA > 0) ? round(($uTotalA / $totalA) * 100, 1) : 0.0;
            $shareB = ($totalB > 0) ? round(($uTotalB / $totalB) * 100, 1) : 0.0;

            $pref = 'Dengeli';
            if ($uTotalA > $uTotalB * 1.5) {
                $pref = "{$titleA} Ağırlıklı";
            } elseif ($uTotalB > $uTotalA * 1.5) {
                $pref = "{$titleB} Ağırlıklı";
            }

            $personnelMatrix[] = [
                'user_name' => $u,
                'val_a' => $uTotalA,
                'val_b' => $uTotalB,
                'share_a' => $shareA,
                'share_b' => $shareB,
                'preference' => $pref,
            ];
        }

        usort($personnelMatrix, fn($a, $b) => ($b['val_a'] + $b['val_b']) <=> ($a['val_a'] + $a['val_b']));

        // Filtre özetleri metinleri
        $filtersSummaryA = [];
        foreach (($segA['filters']['field_filters'] ?? []) as $fId => $fVal) {
            if ($fVal !== '' && $fVal !== null) {
                $fLabel = $fieldsMap->get($fId)?->label ?? "Alan #{$fId}";
                $filtersSummaryA[] = "{$fLabel}: {$fVal}";
            }
        }
        $filtersSummaryB = [];
        foreach (($segB['filters']['field_filters'] ?? []) as $fId => $fVal) {
            if ($fVal !== '' && $fVal !== null) {
                $fLabel = $fieldsMap->get($fId)?->label ?? "Alan #{$fId}";
                $filtersSummaryB[] = "{$fLabel}: {$fVal}";
            }
        }

        // Chart.js verisi (Top 10 personel yan yana bar)
        $chartLabels = [];
        $chartDataA = [];
        $chartDataB = [];
        foreach (array_slice($personnelMatrix, 0, 10) as $pm) {
            $chartLabels[] = $pm['user_name'];
            $chartDataA[] = $pm['val_a'];
            $chartDataB[] = $pm['val_b'];
        }

        return [
            'mode' => 'segment',
            'segment_a' => [
                'title' => $titleA,
                'total' => $totalA,
                'count' => $countA,
                'avg' => $countA > 0 ? round($totalA / $countA, 2) : 0,
                'filters_summary' => $filtersSummaryA,
            ],
            'segment_b' => [
                'title' => $titleB,
                'total' => $totalB,
                'count' => $countB,
                'avg' => $countB > 0 ? round($totalB / $countB, 2) : 0,
                'filters_summary' => $filtersSummaryB,
            ],
            'kpis' => [
                'metric_diff' => round($totalA - $totalB, 2),
                'metric_ratio' => $totalB > 0 ? round(($totalA / $totalB), 2) : null,
                'dominant_segment' => $totalA >= $totalB ? $titleA : $titleB,
            ],
            'metric_name' => $metricName,
            'aggregation_name' => $aggName,
            'full_metric_title' => "{$aggName} {$metricName}",
            'personnel_matrix' => $personnelMatrix,
            'chartData' => [
                'type' => 'bar',
                'labels' => $chartLabels,
                'datasets' => [
                    [
                        'label' => $titleA,
                        'data' => $chartDataA,
                        'backgroundColor' => '#4f46e5',
                        'borderRadius' => 6,
                    ],
                    [
                        'label' => $titleB,
                        'data' => $chartDataB,
                        'backgroundColor' => '#f59e0b',
                        'borderRadius' => 6,
                    ],
                ],
            ],
            'status_scope' => self::buildStatusScope($filters),
        ];
    }
}


