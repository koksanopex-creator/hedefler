<?php

namespace App\Services;

use App\Models\KpiTargetM;
use App\Models\KpiTargetPeriodM;
use App\Models\KpiTargetRealizationM;
use App\Models\FormEntryM;
use App\Models\FormEntryRowM;
use App\Models\FormEntryValueM;
use App\Models\FormTemplateM;
use App\Models\FormFieldM;
use App\Models\UserM;
use App\Models\GroupM;
use App\Models\MasterDataTypeM;
use App\Models\MasterDataItemM;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class KpiTargetS
{
    /**
     * Filtrelenmiş hedef sorgusu döner. Kullanıcının yetkilerine göre sınırlar.
     */
    public function getTargetsQuery(array $filters = [], ?UserM $user = null)
    {
        $query = KpiTargetM::with([
            'template',
            'targetField',
            'creator',
            'periods',
            'realizations' => function ($q) {
                $q->where('realization_month', 0); // Yıllık özet gerçekleşme
            }
        ]);

        // Yetki bazlı filtreleme
        if ($user && !app(PermissionS::class)->hasPermission($user, 'kpi_targets.view_all')) {
            $canViewGroup = app(PermissionS::class)->hasPermission($user, 'kpi_targets.view_group') || $user->managedGroups->isNotEmpty();
            $canViewOwn = app(PermissionS::class)->hasPermission($user, 'kpi_targets.view_own');

            $query->where(function ($q) use ($user, $canViewGroup, $canViewOwn) {
                $applied = false;

                if ($canViewOwn) {
                    $q->where(function ($sub) use ($user) {
                        $sub->where('assignee_type', 'user')
                            ->where('assignee_id', $user->id);
                    });
                    $applied = true;
                }

                if ($canViewGroup && ($user->group_id || $user->managedGroups->isNotEmpty())) {
                    // Kullanıcının grubu veya yöneticisi olduğu gruplar
                    $managedGroupIds = $user->managedGroups->pluck('id')->toArray();
                    $allGroupIds = array_unique(array_filter(array_merge([$user->group_id], $managedGroupIds)));

                    $groupUserIds = UserM::whereIn('group_id', $allGroupIds)->pluck('id')->toArray();

                    $groupCondition = function ($sub) use ($allGroupIds, $groupUserIds) {
                        $sub->where(function ($g) use ($allGroupIds) {
                            $g->whereIn('assignee_type', ['group', 'manager'])
                              ->whereIn('assignee_id', $allGroupIds);
                        })->orWhere(function ($u) use ($groupUserIds) {
                            $u->where('assignee_type', 'user')
                              ->whereIn('assignee_id', $groupUserIds);
                        });
                    };

                    if ($applied) {
                        $q->orWhere($groupCondition);
                    } else {
                        $q->where($groupCondition);
                        $applied = true;
                    }
                }

                if (!$applied) {
                    $q->whereRaw('1 = 0'); // Hiçbir yetki yoksa boş döner
                }
            });
        }

        // Filtreler
        if (!empty($filters['year'])) {
            $query->where('target_year', $filters['year']);
        }

        if (!empty($filters['template_id'])) {
            $query->where('form_template_id', $filters['template_id']);
        }

        if (!empty($filters['target_field_id'])) {
            $query->where('target_field_id', $filters['target_field_id']);
        }

        if (!empty($filters['assignee_type'])) {
            $query->where('assignee_type', $filters['assignee_type']);
        }

        if (!empty($filters['assignee_id'])) {
            $query->where('assignee_id', $filters['assignee_id']);
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['search'])) {
            $s = trim($filters['search']);
            $query->where(function ($q) use ($s) {
                $q->where('title', 'like', "%{$s}%")
                  ->orWhere('description', 'like', "%{$s}%");
            });
        }

        return $query->orderBy('target_year', 'desc')->orderBy('created_at', 'desc');
    }

    /**
     * Birden fazla kullanıcı veya grup için toplu olarak aynı parametrelerle KPI hedefi oluşturur.
     * 
     * @return array<KpiTargetM>
     */
    public function createTargetsBatch(array $data, array $assigneeIds, UserM $creator): array
    {
        $created = [];
        foreach ($assigneeIds as $id) {
            $itemData = $data;
            $itemData['assignee_id'] = $id;
            $created[] = $this->createTarget($itemData, $creator);
        }
        return $created;
    }

    /**
     * Yeni bir KPI Hedefi oluşturur.
     */
    public function createTarget(array $data, UserM $creator): KpiTargetM
    {
        return DB::transaction(function () use ($data, $creator) {
            $criteria = $this->normalizeCriteria($data['criteria'] ?? ($data['criteria_json'] ?? []));

            $target = KpiTargetM::create([
                'form_template_id' => $data['form_template_id'],
                'target_field_id' => $data['target_field_id'],
                'title' => $data['title'],
                'description' => $data['description'] ?? null,
                'target_year' => $data['target_year'],
                'assignee_type' => $data['assignee_type'],
                'assignee_id' => $data['assignee_id'],
                'target_value' => $data['target_value'],
                'unit' => $data['unit'] ?? 'Ton',
                'criteria_json' => !empty($criteria) ? $criteria : null,
                'parent_target_id' => $data['parent_target_id'] ?? null,
                'status' => $data['status'] ?? 'active',
                'created_by' => $creator->id,
            ]);

            // 12 aylık periyotları varsayılan olarak eşit veya boş oluştur
            $distributeEvenly = !empty($data['distribute_evenly']);
            $monthlyTarget = $distributeEvenly ? round($target->target_value / 12, 2) : 0;

            for ($m = 1; $m <= 12; $m++) {
                KpiTargetPeriodM::create([
                    'kpi_target_id' => $target->id,
                    'period_year' => $target->target_year,
                    'period_month' => $m,
                    'period_target_value' => $monthlyTarget,
                ]);
            }

            // Gerçekleşmeleri hesapla
            $this->calculateTargetRealization($target);

            // Log kaydı
            ActivityLogS::logAction(
                'kpi.target.created',
                "Yeni KPI Hedefi tanımlandı: {$target->title} ({$target->target_value} {$target->unit})",
                KpiTargetM::class,
                $target->id
            );

            // Bildirim tetikle
            $this->dispatchNotification('kpi.assigned', $target, $creator);

            return $target;
        });
    }

    /**
     * Mevcut hedefi günceller.
     */
    public function updateTarget(KpiTargetM $target, array $data, UserM $user): KpiTargetM
    {
        return DB::transaction(function () use ($target, $data, $user) {
            $criteria = $this->normalizeCriteria($data['criteria'] ?? ($data['criteria_json'] ?? []));

            $oldValue = $target->target_value;

            $target->update([
                'form_template_id' => $data['form_template_id'] ?? $target->form_template_id,
                'target_field_id' => $data['target_field_id'] ?? $target->target_field_id,
                'title' => $data['title'] ?? $target->title,
                'description' => $data['description'] ?? $target->description,
                'target_year' => $data['target_year'] ?? $target->target_year,
                'assignee_type' => $data['assignee_type'] ?? $target->assignee_type,
                'assignee_id' => $data['assignee_id'] ?? $target->assignee_id,
                'target_value' => $data['target_value'] ?? $target->target_value,
                'unit' => $data['unit'] ?? $target->unit,
                'criteria_json' => isset($data['criteria']) || isset($data['criteria_json']) ? (!empty($criteria) ? $criteria : null) : $target->criteria_json,
                'parent_target_id' => $data['parent_target_id'] ?? $target->parent_target_id,
                'status' => $data['status'] ?? $target->status,
            ]);

            // Eğer hedef değeri değiştiyse ve eşit dağıtılmışsa periyotları orantıla
            if ($oldValue != $target->target_value && !empty($data['distribute_evenly'])) {
                $monthlyTarget = round($target->target_value / 12, 2);
                for ($m = 1; $m <= 12; $m++) {
                    KpiTargetPeriodM::updateOrCreate(
                        ['kpi_target_id' => $target->id, 'period_year' => $target->target_year, 'period_month' => $m],
                        ['period_target_value' => $monthlyTarget]
                    );
                }
            }

            // Gerçekleşmeyi yeniden hesapla
            $this->calculateTargetRealization($target);

            // Log
            ActivityLogS::logAction(
                'kpi.target.updated',
                "KPI Hedefi güncellendi: {$target->title}",
                KpiTargetM::class,
                $target->id
            );

            // Bildirim
            $this->dispatchNotification('kpi.updated', $target, $user);

            return $target;
        });
    }

    /**
     * Hedefi siler veya iptal eder.
     */
    public function deleteTarget(KpiTargetM $target, UserM $user): bool
    {
        return DB::transaction(function () use ($target, $user) {
            $title = $target->title;
            $id = $target->id;

            $target->delete();

            ActivityLogS::logAction(
                'kpi.target.deleted',
                "KPI Hedefi silindi: {$title}",
                KpiTargetM::class,
                $id
            );

            return true;
        });
    }

    /**
     * Hedefe kullanıcı veya grup atar.
     */
    public function assignTarget(KpiTargetM $target, string $assigneeType, int $assigneeId, UserM $user): KpiTargetM
    {
        return DB::transaction(function () use ($target, $assigneeType, $assigneeId, $user) {
            $target->update([
                'assignee_type' => $assigneeType,
                'assignee_id' => $assigneeId,
            ]);

            $this->calculateTargetRealization($target);

            ActivityLogS::logAction(
                'kpi.target.assigned',
                "KPI Hedefi atandı: {$target->title} -> {$target->assignee_name}",
                KpiTargetM::class,
                $target->id
            );

            $this->dispatchNotification('kpi.assigned', $target, $user);

            return $target;
        });
    }

    /**
     * 12 aylık periyot hedeflerini kaydeder.
     */
    public function distributePeriods(KpiTargetM $target, array $monthlyValues, UserM $user): void
    {
        DB::transaction(function () use ($target, $monthlyValues, $user) {
            for ($m = 1; $m <= 12; $m++) {
                $val = floatval($monthlyValues[$m] ?? 0);
                KpiTargetPeriodM::updateOrCreate(
                    [
                        'kpi_target_id' => $target->id,
                        'period_year' => $target->target_year,
                        'period_month' => $m,
                    ],
                    [
                        'period_target_value' => $val,
                    ]
                );
            }

            // Gerçekleşmeleri güncelle
            $this->calculateTargetRealization($target);

            ActivityLogS::logAction(
                'kpi.target.distributed',
                "KPI Hedefi aylık dağılımı güncellendi: {$target->title}",
                KpiTargetM::class,
                $target->id
            );

            $this->dispatchNotification('kpi.updated', $target, $user);
        });
    }

    /**
     * Yıllık hedef değerini 12 aya eşit olarak böler.
     */
    public function distributeEvenly(KpiTargetM $target, UserM $user): void
    {
        $monthlyTarget = round($target->target_value / 12, 2);
        $monthlyValues = [];
        for ($m = 1; $m <= 12; $m++) {
            $monthlyValues[$m] = $monthlyTarget;
        }

        $this->distributePeriods($target, $monthlyValues, $user);
    }

    /**
     * Tek bir hedef için form girişlerinden gerçekleşme hesaplar.
     * Hoshin Kanri mantığı: Bireysel, Grup ve Yönetici kümülasyonunu doğru bağlar.
     */
    public function calculateTargetRealization(KpiTargetM $target): void
    {
        try {
            // 1. Hedefe dahil kullanıcı ID'lerini belirle
            $userIds = $this->resolveAssigneeUserIds($target);

            if (empty($userIds)) {
                // Atanan kullanıcı yoksa gerçekleşme 0
                $this->saveRealization($target->id, $target->target_year, 0, null, 0, 0);
                for ($m = 1; $m <= 12; $m++) {
                    $this->saveRealization($target->id, $target->target_year, $m, null, 0, 0);
                }
                return;
            }

            // 2. Form şablonuna ait onaylanmış form kayıtlarını al
            $entriesQuery = FormEntryM::with(['rows.values'])
                ->where('form_template_id', $target->form_template_id)
                ->whereIn('user_id', $userIds)
                ->where('status', 'approved')
                ->whereYear('entry_date', $target->target_year);

            $entries = $entriesQuery->get();

            $targetFieldId = $target->target_field_id;
            $criteria = $target->criteria_json ?? [];

            // Aylık gerçekleşen toplamları tutacak dizi (1..12)
            $monthlyActuals = array_fill(1, 12, 0.0);
            $yearlyTotal = 0.0;

            foreach ($entries as $entry) {
                $month = intval($entry->entry_date->format('n'));

                foreach ($entry->rows as $row) {
                    // Satırın kriterlere uyup uymadığını kontrol et
                    if (!$this->rowMatchesCriteria($row, $criteria)) {
                        continue;
                    }

                    // Hedef alanının değerini al
                    $fieldValObj = $row->values->firstWhere('form_field_id', $targetFieldId);
                    if ($fieldValObj && is_numeric($fieldValObj->value)) {
                        $numVal = floatval($fieldValObj->value);
                        $monthlyActuals[$month] += $numVal;
                        $yearlyTotal += $numVal;
                    }
                }
            }

            // 3. Yıllık toplam gerçekleşmeyi kaydet (month = 0)
            $yearlyTargetVal = floatval($target->target_value);
            $yearlyRate = $yearlyTargetVal > 0 ? round(($yearlyTotal / $yearlyTargetVal) * 100, 2) : 0;

            $this->saveRealization($target->id, $target->target_year, 0, null, $yearlyTotal, $yearlyRate);

            // 4. Aylık gerçekleşmeleri kaydet (month = 1..12)
            $periodMap = $target->periods()->where('period_year', $target->target_year)->pluck('period_target_value', 'period_month');

            foreach ($monthlyActuals as $m => $actual) {
                $periodTarget = floatval($periodMap[$m] ?? 0);
                // Eğer aylık hedef girilmişse ona göre oranla, yoksa yıllık hedefin 1/12'sine oranla
                $effectiveTarget = $periodTarget > 0 ? $periodTarget : ($yearlyTargetVal / 12);
                $rate = $effectiveTarget > 0 ? round(($actual / $effectiveTarget) * 100, 2) : 0;

                $this->saveRealization($target->id, $target->target_year, $m, null, $actual, $rate);
            }

            // 5. Başarı eşiği kontrolü (%80 veya %100)
            if ($yearlyRate >= 80) {
                $this->checkThresholdNotification($target, $yearlyTotal, $yearlyRate);
            }

        } catch (\Exception $e) {
            Log::error("KPI Realization calculation error for Target #{$target->id}: " . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * Tüm aktif hedefleri yeniden hesaplar.
     */
    public function recalculateAll(?int $year = null): int
    {
        $query = KpiTargetM::where('status', 'active');
        if ($year) {
            $query->where('target_year', $year);
        }

        $targets = $query->get();
        $count = 0;

        foreach ($targets as $target) {
            $this->calculateTargetRealization($target);
            $count++;
        }

        return $count;
    }

    /**
     * Bir form onaylandığında o şablonla ilişkili tüm hedefleri yeniden hesaplar.
     */
    public function recalculateForEntry(FormEntryM $entry): void
    {
        if ($entry->status !== 'approved') {
            return;
        }

        $year = intval($entry->entry_date->format('Y'));

        $targets = KpiTargetM::where('form_template_id', $entry->form_template_id)
            ->where('target_year', $year)
            ->where('status', 'active')
            ->get();

        foreach ($targets as $target) {
            $this->calculateTargetRealization($target);
        }
    }

    /**
     * Chart.js ve ilerleme ekranı için zaman serisi verisi hazırlar.
     */
    public function getProgressData(KpiTargetM $target): array
    {
        $periods = $target->periods()->where('period_year', $target->target_year)->pluck('period_target_value', 'period_month')->toArray();
        $realizations = $target->realizations()->where('realization_year', $target->target_year)->where('realization_month', '>', 0)->pluck('actual_value', 'realization_month')->toArray();

        $monthNames = ['Ocak', 'Şubat', 'Mart', 'Nisan', 'Mayıs', 'Haziran', 'Temmuz', 'Ağustos', 'Eylül', 'Ekim', 'Kasım', 'Aralık'];

        $targetSeries = [];
        $actualSeries = [];
        $cumulativeTargetSeries = [];
        $cumulativeActualSeries = [];

        $cumTarget = 0;
        $cumActual = 0;

        for ($m = 1; $m <= 12; $m++) {
            $tVal = floatval($periods[$m] ?? ($target->target_value / 12));
            $aVal = floatval($realizations[$m] ?? 0);

            $targetSeries[] = $tVal;
            $actualSeries[] = $aVal;

            $cumTarget += $tVal;
            $cumActual += $aVal;

            $cumulativeTargetSeries[] = round($cumTarget, 2);
            $cumulativeActualSeries[] = round($cumActual, 2);
        }

        $latestRealization = $target->realizations()->where('realization_month', 0)->first();
        $totalActual = $latestRealization ? floatval($latestRealization->actual_value) : 0;
        $achievementRate = $latestRealization ? floatval($latestRealization->achievement_rate) : 0;

        return [
            'labels' => $monthNames,
            'target_series' => $targetSeries,
            'actual_series' => $actualSeries,
            'cumulative_target' => $cumulativeTargetSeries,
            'cumulative_actual' => $cumulativeActualSeries,
            'total_target' => floatval($target->target_value),
            'total_actual' => $totalActual,
            'achievement_rate' => $achievementRate,
            'remaining_value' => max(0, floatval($target->target_value) - $totalActual),
            'unit' => $target->unit,
        ];
    }

    /**
     * criteria_json eşleştirmesi: Bir satırın hedefin tüm kriterlerine uyup uymadığını denetler.
     */
    private function rowMatchesCriteria(FormEntryRowM $row, array $criteria): bool
    {
        if (empty($criteria)) {
            return true;
        }

        $valuesByField = $row->values->pluck('value', 'form_field_id')->toArray();

        foreach ($criteria as $fieldId => $expectedValue) {
            if ($expectedValue === null || $expectedValue === '') {
                continue;
            }

            $actualValue = $valuesByField[$fieldId] ?? null;

            // Çoklu değer eşleşmesi (array veya virgülle ayrılmış)
            if (is_array($expectedValue)) {
                if (!in_array($actualValue, $expectedValue)) {
                    return false;
                }
            } else {
                // Büyük/küçük harf duyarsız string karşılaştırma
                if (mb_strtolower(trim((string)$actualValue)) !== mb_strtolower(trim((string)$expectedValue))) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * criteria input'unu normalize eder (field_id => value map'ine dönüştürür)
     */
    private function normalizeCriteria($criteria): array
    {
        if (empty($criteria)) {
            return [];
        }

        if (is_string($criteria)) {
            $decoded = json_decode($criteria, true);
            $criteria = is_array($decoded) ? $decoded : [];
        }

        $normalized = [];
        foreach ($criteria as $key => $val) {
            if ($val === null || $val === '') {
                continue;
            }
            // "field_5" veya "5" formatlarını integer field_id'ye çevir
            $cleanKey = is_numeric($key) ? intval($key) : intval(str_replace('field_', '', $key));
            if ($cleanKey > 0) {
                $normalized[$cleanKey] = is_string($val) ? trim($val) : $val;
            }
        }

        return $normalized;
    }

    /**
     * Hedefe dahil kullanıcı ID'lerini döner
     */
    private function resolveAssigneeUserIds(KpiTargetM $target): array
    {
        if ($target->assignee_type === 'user') {
            return [$target->assignee_id];
        }

        if ($target->assignee_type === 'group') {
            return UserM::where('group_id', $target->assignee_id)->where('is_active', true)->pluck('id')->toArray();
        }

        if ($target->assignee_type === 'manager') {
            $group = GroupM::find($target->assignee_id);
            if (!$group) return [];

            $memberIds = UserM::where('group_id', $target->assignee_id)->where('is_active', true)->pluck('id')->toArray();
            $managerIds = $group->managers->pluck('id')->toArray();

            return array_unique(array_merge($memberIds, $managerIds));
        }

        return [];
    }

    /**
     * Gerçekleşme tablosuna yazar
     */
    private function saveRealization(int $targetId, int $year, int $month, ?int $week, float $actual, float $rate): void
    {
        KpiTargetRealizationM::updateOrCreate(
            [
                'kpi_target_id' => $targetId,
                'realization_year' => $year,
                'realization_month' => $month,
                'realization_week' => $week,
            ],
            [
                'actual_value' => $actual,
                'achievement_rate' => $rate,
                'calculated_at' => now(),
            ]
        );
    }

    /**
     * Kriterlerin metinsel özetini üretir (Zil ve E-Posta bildirim şablonlarında {kriter_ozeti} için)
     */
    public function formatCriteriaSummary(?array $criteria): string
    {
        if (empty($criteria)) {
            return 'Genel (Filtresiz Tüm Satışlar / Girişler)';
        }

        $fieldIds = array_keys($criteria);
        $fields = FormFieldM::whereIn('id', $fieldIds)->pluck('label', 'id');

        $parts = [];
        foreach ($criteria as $fieldId => $val) {
            $label = $fields[$fieldId] ?? "Alan #{$fieldId}";
            $valStr = is_array($val) ? implode(', ', $val) : (string)$val;
            $parts[] = "{$label}: {$valStr}";
        }

        return implode(', ', $parts);
    }

    /**
     * KPI Bildirimi gönderir
     */
    private function dispatchNotification(string $eventSlug, KpiTargetM $target, ?UserM $contextUser = null): void
    {
        try {
            $template = $target->template;
            $targetField = $target->targetField;
            $assigneeUsers = UserM::whereIn('id', $this->resolveAssigneeUserIds($target))->get();

            $latestRealization = $target->latest_realization;
            $actualVal = $latestRealization ? $latestRealization->actual_value : 0;
            $rateVal = $latestRealization ? $latestRealization->achievement_rate : 0;

            $actionUrl = route('kpi-targets.progress', $target->id);

            $variables = [
                'hedef_basligi' => $target->title,
                'target_title' => $target->title,
                'sablon_adi' => $template?->name ?? 'Dinamik Form',
                'template_name' => $template?->name ?? 'Dinamik Form',
                'hedef_alani' => $targetField?->label ?? 'Hedef Alanı',
                'target_field' => $targetField?->label ?? 'Hedef Alanı',
                'hedef_degeri' => number_format($target->target_value, 2, ',', '.'),
                'target_value' => number_format($target->target_value, 2, ',', '.'),
                'gerceklesen_deger' => number_format($actualVal, 2, ',', '.'),
                'actual_value' => number_format($actualVal, 2, ',', '.'),
                'basari_orani' => '%' . number_format($rateVal, 1, ',', '.'),
                'achievement_rate' => '%' . number_format($rateVal, 1, ',', '.'),
                'birim' => $target->unit,
                'unit' => $target->unit,
                'hedef_yili' => $target->target_year,
                'target_year' => $target->target_year,
                'kriter_ozeti' => $this->formatCriteriaSummary($target->criteria_json),
                'criteria_summary' => $this->formatCriteriaSummary($target->criteria_json),
                'atayan_kisi' => $target->creator?->name ?? 'Sistem Yöneticisi',
                'assigned_by' => $target->creator?->name ?? 'Sistem Yöneticisi',
                'action_url' => $actionUrl,
                'target_users' => $assigneeUsers,
                'group_id' => $target->assignee_type === 'group' || $target->assignee_type === 'manager' ? $target->assignee_id : null,
            ];

            NotificationS::dispatch($eventSlug, $variables, $contextUser, $actionUrl);

        } catch (\Exception $e) {
            Log::error("KPI Notification dispatch error ({$eventSlug}): " . $e->getMessage());
        }
    }

    /**
     * Başarı eşiği bildirimini kontrol eder
     */
    private function checkThresholdNotification(KpiTargetM $target, float $actual, float $rate): void
    {
        $cacheKey = "kpi_notified_threshold_{$target->id}_{$target->target_year}";
        $lastNotified = cache()->get($cacheKey, 0);

        if ($rate >= 100 && $lastNotified < 100) {
            cache()->put($cacheKey, 100, now()->addDays(30));
            $this->dispatchNotification('kpi.achieved_threshold', $target);
        } elseif ($rate >= 80 && $lastNotified < 80) {
            cache()->put($cacheKey, 80, now()->addDays(30));
            $this->dispatchNotification('kpi.achieved_threshold', $target);
        }
    }

    /**
     * Kriterlerin zengin rozet (badge pill) listesini üretir
     */
    public function getFormattedCriteriaBadges(?array $criteria): array
    {
        if (empty($criteria)) {
            return [];
        }

        $fieldIds = array_keys($criteria);
        $fields = FormFieldM::whereIn('id', $fieldIds)->get()->keyBy('id');

        $badges = [];
        foreach ($criteria as $fieldId => $val) {
            if ($val === null || $val === '') {
                continue;
            }

            $field = $fields[$fieldId] ?? null;
            $label = $field ? $field->label : "Kriter #{$fieldId}";
            $norm = str_replace(
                ['İ', 'I', 'ı', 'i', 'Ş', 'ş', 'Ğ', 'ğ', 'Ü', 'ü', 'Ö', 'ö', 'Ç', 'ç', 'i̇'],
                ['i', 'i', 'i', 'i', 's', 's', 'g', 'g', 'u', 'u', 'o', 'o', 'c', 'c', 'i'],
                mb_strtolower($label, 'UTF-8')
            );
            $valStr = is_array($val) ? implode(', ', $val) : (string)$val;

            if (str_contains($norm, 'ulke')) {
                $icon = '🌍';
                $bgClass = 'bg-blue-50/80 text-blue-800 border-blue-200/80';
                $dotColor = 'bg-blue-500';
            } elseif (str_contains($norm, 'sehir')) {
                $icon = '🏙️';
                $bgClass = 'bg-sky-50/80 text-sky-800 border-sky-200/80';
                $dotColor = 'bg-sky-500';
            } elseif (str_contains($norm, 'isyeri')) {
                $icon = '🏢';
                $bgClass = 'bg-indigo-50/80 text-indigo-800 border-indigo-200/80';
                $dotColor = 'bg-indigo-500';
            } elseif (str_contains($norm, 'renk')) {
                $icon = '🎨';
                $bgClass = 'bg-pink-50/80 text-pink-800 border-pink-200/80';
                $dotColor = 'bg-pink-500';
            } elseif (str_contains($norm, 'gramaj')) {
                $icon = '⚖️';
                $bgClass = 'bg-emerald-50/80 text-emerald-800 border-emerald-200/80';
                $dotColor = 'bg-emerald-500';
            } elseif (str_contains($norm, 'kalinlik')) {
                $icon = '📏';
                $bgClass = 'bg-teal-50/80 text-teal-800 border-teal-200/80';
                $dotColor = 'bg-teal-500';
            } elseif (str_contains($norm, 'iv')) {
                $icon = '🧪';
                $bgClass = 'bg-amber-50/80 text-amber-800 border-amber-200/80';
                $dotColor = 'bg-amber-500';
            } elseif (str_contains($norm, 'versiyon')) {
                $icon = '🏷️';
                $bgClass = 'bg-purple-50/80 text-purple-800 border-purple-200/80';
                $dotColor = 'bg-purple-500';
            } elseif (str_contains($norm, 'tip')) {
                $icon = '📦';
                $bgClass = 'bg-violet-50/80 text-violet-800 border-violet-200/80';
                $dotColor = 'bg-violet-500';
            } elseif (str_contains($norm, 'cene')) {
                $icon = '⚙️';
                $bgClass = 'bg-cyan-50/80 text-cyan-800 border-cyan-200/80';
                $dotColor = 'bg-cyan-500';
            } elseif (str_contains($norm, 'belge')) {
                $icon = '📄';
                $bgClass = 'bg-orange-50/80 text-orange-800 border-orange-200/80';
                $dotColor = 'bg-orange-500';
            } elseif (str_contains($norm, 'ay')) {
                $icon = '📅';
                $bgClass = 'bg-yellow-50/80 text-yellow-800 border-yellow-200/80';
                $dotColor = 'bg-yellow-500';
            } elseif (str_contains($norm, 'birim')) {
                $icon = '📐';
                $bgClass = 'bg-zinc-50 text-zinc-800 border-zinc-200';
                $dotColor = 'bg-zinc-500';
            }

            $badges[] = [
                'field_id' => $fieldId,
                'label' => $label,
                'value' => $valStr,
                'icon' => $icon,
                'bg_class' => $bgClass,
                'dot_color' => $dotColor,
            ];
        }

        return $badges;
    }

    /**
     * jsVectorMap için coğrafi ülke ve şehir kırılım verilerini üretir
     */
    public function getGeographicDistributionData(?int $userId = null, ?int $year = null, ?int $groupId = null): array
    {
        $year = $year ?? intval(date('Y'));

        // ISO Kodları Eşleştirmesi (jsVectorMap dünya haritası ile tam uyumlu)
        $countryIsoMap = [
            'Türkiye' => 'TR',
            'Turkiye' => 'TR',
            'Almanya' => 'DE',
            'Amerika' => 'US',
            'Kazakistan' => 'KZ',
            'Libya' => 'LY',
            'Irak' => 'IQ',
            'Hollanda' => 'NL',
            'Belçika' => 'BE',
            'Belcika' => 'BE',
        ];

        // Şehir Koordinatları
        $cityCoords = [
            'Almatı' => [43.2389, 76.8897],
            'Almati' => [43.2389, 76.8897],
            'Astana' => [51.1694, 71.4491],
            'Çimkent' => [42.3417, 69.5901],
            'Aktau' => [43.6481, 51.1722],
            'New York' => [40.7128, -74.0060],
            'Los Angeles' => [34.0522, -118.2437],
            'Chicago' => [41.8781, -87.6298],
            'Houston' => [29.7604, -95.3698],
            'Berlin' => [52.5200, 13.4050],
            'Hamburg' => [53.5511, 9.9937],
            'Münih' => [48.1351, 11.5820],
            'Köln' => [50.9375, 6.9603],
            'Frankfurt' => [50.1109, 8.6821],
            'Adana' => [37.0000, 35.3213],
            'Gaziantep' => [37.0662, 37.3833],
            'İstanbul' => [41.0082, 28.9784],
            'Ankara' => [39.9334, 32.8597],
            'İzmir' => [38.4237, 27.1428],
            'Bursa' => [40.1885, 29.0610],
            'Mersin' => [36.8121, 34.6415],
            'Bağdat' => [33.3152, 44.3661],
            'Erbil' => [36.1901, 44.0091],
            'Trablus' => [32.8872, 13.1913],
            'Amsterdam' => [52.3676, 4.9041],
            'Brüksel' => [50.8503, 4.3517],
        ];

        // İlgili hedefleri çek
        $targetsQuery = KpiTargetM::with(['template', 'targetField', 'realizations'])
            ->where('target_year', $year)
            ->where('status', 'active');

        if ($userId) {
            $user = UserM::find($userId);
            $uGroupId = $user?->group_id;
            $targetsQuery->where(function ($q) use ($userId, $uGroupId) {
                $q->where(function ($sub) use ($userId) {
                    $sub->where('assignee_type', 'user')->where('assignee_id', $userId);
                });
                if ($uGroupId) {
                    $q->orWhere(function ($sub) use ($uGroupId) {
                        $sub->whereIn('assignee_type', ['group', 'manager'])->where('assignee_id', $uGroupId);
                    });
                }
            });
        } elseif ($groupId) {
            $group = GroupM::with('users')->find($groupId);
            $groupUserIds = $group ? $group->users->pluck('id')->toArray() : [];
            $targetsQuery->where(function ($q) use ($groupId, $groupUserIds) {
                $q->where(function ($sub) use ($groupId) {
                    $sub->whereIn('assignee_type', ['group', 'manager'])->where('assignee_id', $groupId);
                });
                if (!empty($groupUserIds)) {
                    $q->orWhere(function ($sub) use ($groupUserIds) {
                        $sub->where('assignee_type', 'user')->whereIn('assignee_id', $groupUserIds);
                    });
                }
            });
        }

        $targets = $targetsQuery->get();

        $countryStats = [];
        $cityStats = [];

        $ulkeField = FormFieldM::where(function($q) {
            $q->where('field_key', 'ulke')->orWhere('label', 'like', '%Ülke%');
        })->first();
        $sehirField = FormFieldM::where(function($q) {
            $q->where('field_key', 'sehir')->orWhere('label', 'like', '%Şehir%');
        })->first();

        $ulkeFieldId = $ulkeField?->id ?? 11;
        $sehirFieldId = $sehirField?->id ?? 12;

        foreach ($targets as $target) {
            $criteria = $target->criteria_json ?? [];
            $countryName = $criteria[$ulkeFieldId] ?? null;
            $cityName = $criteria[$sehirFieldId] ?? null;

            $realization = $target->realizations->firstWhere('realization_month', 0);
            $actual = $realization ? floatval($realization->actual_value) : 0;
            $targetVal = floatval($target->target_value);

            if ($countryName) {
                $iso = $countryIsoMap[$countryName] ?? null;
                if ($iso) {
                    if (!isset($countryStats[$iso])) {
                        $countryStats[$iso] = [
                            'name' => $countryName,
                            'iso' => $iso,
                            'target' => 0.0,
                            'actual' => 0.0,
                            'unit' => $target->unit ?? 'Ton',
                        ];
                    }
                    $countryStats[$iso]['target'] += $targetVal;
                    $countryStats[$iso]['actual'] += $actual;
                }
            }

            if ($cityName) {
                if (!isset($cityStats[$cityName])) {
                    $cityStats[$cityName] = [
                        'name' => $cityName,
                        'country' => $countryName ?? 'Global',
                        'coords' => $cityCoords[$cityName] ?? [39.0, 35.0],
                        'target' => 0.0,
                        'actual' => 0.0,
                        'unit' => $target->unit ?? 'Ton',
                    ];
                }
                $cityStats[$cityName]['target'] += $targetVal;
                $cityStats[$cityName]['actual'] += $actual;
            }
        }

        // Eğer henüz hedef tanımlanmamışsa Master Data'daki ana pazarları hazırla
        if (empty($countryStats)) {
            foreach (['TR' => 'Türkiye', 'KZ' => 'Kazakistan', 'DE' => 'Almanya', 'US' => 'Amerika'] as $iso => $name) {
                $countryStats[$iso] = [
                    'name' => $name,
                    'iso' => $iso,
                    'target' => 0,
                    'actual' => 0,
                    'rate' => 0,
                    'unit' => 'Ton',
                    'color' => '#e2e8f0',
                ];
            }
        } else {
            foreach ($countryStats as $iso => &$c) {
                $c['rate'] = $c['target'] > 0 ? round(($c['actual'] / $c['target']) * 100, 1) : 0;
                if ($c['rate'] >= 90) {
                    $c['color'] = '#10b981'; // Zümrüt Yeşili
                } elseif ($c['rate'] >= 70) {
                    $c['color'] = '#f59e0b'; // Amber Sarı
                } elseif ($c['rate'] > 0) {
                    $c['color'] = '#ef4444'; // Kırmızı
                } else {
                    $c['color'] = '#94a3b8'; // Gri / Henüz Başlamadı
                }
            }
            unset($c);
        }

        foreach ($cityStats as $name => &$ct) {
            $ct['rate'] = $ct['target'] > 0 ? round(($ct['actual'] / $ct['target']) * 100, 1) : 0;
            if ($ct['rate'] >= 90) {
                $ct['color'] = '#10b981';
            } elseif ($ct['rate'] >= 70) {
                $ct['color'] = '#f59e0b';
            } else {
                $ct['color'] = '#ef4444';
            }
        }
        unset($ct);

        return [
            'countries' => $countryStats,
            'cities' => array_values($cityStats),
            'year' => $year,
        ];
    }

    /**
     * Çok boyutlu Radar (Örümcek) Grafik performans dağılım verilerini üretir
     */
    public function getRadarPerformanceData(?int $userId = null, ?int $year = null, ?int $groupId = null): array
    {
        $year = $year ?? intval(date('Y'));

        // Master Data'dan dinamik İŞYERİ kayıtlarını çek
        $isyeriType = MasterDataTypeM::where('slug', 'like', 'isyeri%')
            ->orWhere('name', 'like', '%İŞYERİ%')
            ->first();

        $dynamicCategories = [];
        if ($isyeriType) {
            $dynamicCategories = MasterDataItemM::where('type_id', $isyeriType->id)
                ->orderBy('id')
                ->pluck('name')
                ->map(fn($item) => mb_strtoupper(trim($item), 'UTF-8'))
                ->toArray();
        }

        if (empty($dynamicCategories)) {
            $dynamicCategories = ['PREFORM', 'KAPAK', 'KULP', 'LEVHA', 'STRECH', 'SHRINK', 'RESIN', 'TİCARİ PET RESIN'];
        }

        $isyeriField = FormFieldM::where(function($q) {
            $q->where('field_key', 'isyeri')->orWhere('label', 'like', '%İŞYERİ%');
        })->first();
        $isyeriFieldId = $isyeriField?->id ?? 20;

        $targetsQuery = KpiTargetM::with(['realizations'])
            ->where('target_year', $year)
            ->where('status', 'active');

        if ($userId) {
            $user = UserM::find($userId);
            $uGroupId = $user?->group_id;
            $targetsQuery->where(function ($q) use ($userId, $uGroupId) {
                $q->where(function ($sub) use ($userId) {
                    $sub->where('assignee_type', 'user')->where('assignee_id', $userId);
                });
                if ($uGroupId) {
                    $q->orWhere(function ($sub) use ($uGroupId) {
                        $sub->whereIn('assignee_type', ['group', 'manager'])->where('assignee_id', $uGroupId);
                    });
                }
            });
        } elseif ($groupId) {
            $group = GroupM::with('users')->find($groupId);
            $groupUserIds = $group ? $group->users->pluck('id')->toArray() : [];
            $targetsQuery->where(function ($q) use ($groupId, $groupUserIds) {
                $q->where(function ($sub) use ($groupId) {
                    $sub->whereIn('assignee_type', ['group', 'manager'])->where('assignee_id', $groupId);
                });
                if (!empty($groupUserIds)) {
                    $q->orWhere(function ($sub) use ($groupUserIds) {
                        $sub->where('assignee_type', 'user')->whereIn('assignee_id', $groupUserIds);
                    });
                }
            });
        }

        $targets = $targetsQuery->get();

        $catStats = [];
        foreach ($dynamicCategories as $cat) {
            $catStats[$cat] = ['target' => 0.0, 'actual' => 0.0];
        }

        foreach ($targets as $target) {
            $criteria = $target->criteria_json ?? [];
            $cat = !empty($criteria[$isyeriFieldId]) ? mb_strtoupper(trim($criteria[$isyeriFieldId]), 'UTF-8') : null;

            if ($cat && isset($catStats[$cat])) {
                $realization = $target->realizations->firstWhere('realization_month', 0);
                $actual = $realization ? floatval($realization->actual_value) : 0;
                $targetVal = floatval($target->target_value);

                $catStats[$cat]['target'] += $targetVal;
                $catStats[$cat]['actual'] += $actual;
            }
        }

        $labels = [];
        $targetScores = [];
        $actualRates = [];
        $rawTargets = [];
        $rawActuals = [];

        foreach ($catStats as $cat => $stats) {
            $labels[] = $cat;
            $targetScores[] = 100;
            $rate = $stats['target'] > 0 ? round(($stats['actual'] / $stats['target']) * 100, 1) : 0;
            $actualRates[] = $rate;
            $rawTargets[] = $stats['target'];
            $rawActuals[] = $stats['actual'];
        }

        return [
            'labels' => $labels,
            'target_scores' => $targetScores,
            'actual_rates' => $actualRates,
            'raw_targets' => $rawTargets,
            'raw_actuals' => $rawActuals,
        ];
    }
}
