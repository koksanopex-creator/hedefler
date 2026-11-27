<?php

namespace App\Services;

use App\Models\FormEntryM;
use App\Models\FormEntryVersionM;
use App\Models\FormTemplateM;
use App\Models\MasterDataItemM;
use App\Models\UserM;
use Illuminate\Support\Facades\DB;

class FormEntryS
{
    /**
     * Veri girişini veya güncellemesini gerçekleştirir
     */
    public function saveEntry(FormTemplateM $template, UserM $user, string $entryDate, array $rowsData, string $status = 'draft', ?FormEntryM $existingEntry = null, ?string $partitionValue = null): FormEntryM
    {
        return DB::transaction(function () use ($template, $user, $entryDate, $rowsData, $status, $existingEntry, $partitionValue) {
            
            // Mevcut bir kayıt varsa onu kullan, yoksa yeni oluştur
            $entry = $existingEntry;
            if (!$entry) {
                $entry = new FormEntryM([
                    'form_template_id' => $template->id,
                    'user_id' => $user->id,
                    'entry_date' => $entryDate,
                ]);
            } else {
                $entry->entry_date = $entryDate;
            }

            // Bölümlenmiş (Partition) bilgi ve Başlık ataması
            if ($partitionValue !== null && $partitionValue !== '') {
                $entry->partition_value = $partitionValue;
                $entry->partition_field_id = $template->partition_field_id;
                $dateFormatted = \Carbon\Carbon::parse($entryDate)->format('d.m.Y');
                $entry->title = "{$partitionValue} - {$template->name} ({$dateFormatted})";
            } elseif (empty($entry->title)) {
                $dateFormatted = \Carbon\Carbon::parse($entryDate)->format('d.m.Y');
                $entry->title = "{$template->name} ({$dateFormatted})";
            }

            // Snapshot mantığı (Eğer ilk defa oluşturuluyorsa veya daha snapshot alınmadıysa)
            // Bu sayede şablon değişse bile bu kayıttaki kolonlar bozulmaz.
            if (empty($entry->template_snapshot)) {
                $template->load('fields');
                $entry->template_snapshot = $template->fields->toArray();
            }

            // Status ve Submitted Date mantığı
            if (in_array($status, ['draft', 'submitted'])) {
                $entry->status = $status;
                if ($status === 'submitted') {
                    $entry->submitted_at = now();
                } else {
                    $entry->submitted_at = null;
                }
            }
            
            $entry->save();

            // Güvenlik Doğrulaması & Otomatik Satışçı Ataması: filter_by_user_group alanları
            $template->loadMissing('fields');
            $restrictedFields = $template->fields->where('filter_by_user_group', true);
            if ($restrictedFields->isNotEmpty()) {
                $formEntryC = app(\App\Http\Controllers\FormEntryC::class);
                $userScope = $formEntryC->getUserScope($user);

                if (!$userScope['is_admin']) {
                    $allowedNames = array_values(array_filter(array_map('trim', $userScope['allowed_user_names'] ?? [])));
                    $singleAllowedName = count($allowedNames) === 1 ? $allowedNames[0] : null;
                    $allowedNamesLower = array_map(fn($n) => mb_strtolower($n, 'UTF-8'), $allowedNames);

                    $checkRowNum = 0;
                    foreach ($rowsData as $rowKey => $rowData) {
                        $checkRowNum++;
                        if (empty(array_filter($rowData))) continue;

                        foreach ($restrictedFields as $field) {
                            // Yetki kapsamı sadece kullanıcının kendisi ise, hücrede ne yazarsa yazsın kendi adını otomatik ata
                            if ($singleAllowedName !== null) {
                                $rowsData[$rowKey][$field->id] = $singleAllowedName;
                                continue;
                            }

                            $val = $rowData[$field->id] ?? null;
                            if (!empty($val)) {
                                $valLower = mb_strtolower(trim($val), 'UTF-8');
                                $isPermitted = false;
                                foreach ($allowedNamesLower as $allowed) {
                                    if ($valLower === $allowed || str_contains($valLower, $allowed) || str_contains($allowed, $valLower)) {
                                        $isPermitted = true;
                                        break;
                                    }
                                }
                                if (!$isPermitted) {
                                    throw new \Exception("Yetkisiz veri girişi (Satır {$checkRowNum}): '{$field->label}' alanında '{$val}' seçme yetkiniz bulunmamaktadır.");
                                }
                            }
                        }
                    }
                }
            }

            // Mevcut satırları temizle ve baştan yaz
            $entry->rows()->delete();

            $fieldTypes = $template->fields->pluck('type', 'id');
            $partitionFieldId = $template->partition_field_id;

            // Otomatik veya varsayılan doldurulan alanlar (kullanıcının girdiği asıl veri sayılmayanlar)
            $nonUserDataFieldIds = $template->fields
                ->filter(fn($f) => in_array($f->type, ['auto_user', 'year']) || (!empty($partitionValue) && $partitionFieldId && $f->id == $partitionFieldId))
                ->pluck('id')
                ->map(fn($id) => (int)$id)
                ->toArray();

            $rowOrder = 0;
            foreach ($rowsData as $rowData) {
                // Eğer satırda otomatik/bölüm alanları haricinde girilmiş hiçbir gerçek veri yoksa bu boş şablon satırını atla
                $hasRealUserInput = false;
                foreach ($rowData as $fieldId => $fVal) {
                    if (in_array((int)$fieldId, $nonUserDataFieldIds)) {
                        continue;
                    }
                    if ($fVal !== null && $fVal !== '' && trim((string)$fVal) !== '') {
                        $hasRealUserInput = true;
                        break;
                    }
                }

                if (!$hasRealUserInput) {
                    continue;
                }

                // Bölümlenmiş form ise kilitli kriter alanını (Örn: İŞYERİ) backend seviyesinde kesinleştir
                if ($partitionValue && $partitionFieldId) {
                    $rowData[$partitionFieldId] = $partitionValue;
                }

                $row = $entry->rows()->create(['row_order' => $rowOrder++]);

                foreach ($rowData as $fieldId => $value) {
                    // Sayısal alansa ve binlik formatı varsa temizle
                    if (isset($fieldTypes[$fieldId]) && $fieldTypes[$fieldId] === 'number' && is_string($value) && $value !== '') {
                        $trimmed = trim($value);
                        if (str_contains($trimmed, ',')) {
                            $value = str_replace(',', '.', str_replace('.', '', $trimmed));
                        } elseif (str_contains($trimmed, '.')) {
                            $parts = explode('.', $trimmed);
                            if (!(count($parts) === 2 && strlen($parts[1]) <= 2)) {
                                $value = str_replace('.', '', $trimmed);
                            }
                        }
                    }

                    $row->values()->create([
                        'form_field_id' => $fieldId,
                        'value' => is_array($value) ? json_encode($value) : $value
                    ]);
                }
            }

            // Satırları ve değerleri yeni halinden JSON snapshot formatında derle
            $entry->load('rows.values');
            $snapshotRows = [];
            foreach ($entry->rows as $r) {
                $vals = [];
                foreach ($r->values as $v) {
                    $vals[(int) $v->form_field_id] = $v->value;
                }
                $snapshotRows[] = [
                    'row_order' => $r->row_order,
                    'values' => $vals
                ];
            }

            if (!empty($snapshotRows)) {
                $this->createVersionSnapshot($entry, $user, $status, $snapshotRows);
            }

            return $entry;
        });
    }

    /**
     * Form entry için versiyon snapshot'ı oluşturur (Değişiklik yoksa mükerrer oluşturmaz, en fazla 10 sürüm saklar)
     */
    public function createVersionSnapshot(FormEntryM $entry, UserM $user, string $status, array $snapshotRows, ?string $actionType = null): ?FormEntryVersionM
    {
        $rowCount = count($snapshotRows);
        if ($rowCount === 0) {
            return null;
        }

        $lastVersion = FormEntryVersionM::where('form_entry_id', $entry->id)
            ->orderBy('version_number', 'desc')
            ->first();

        $newJson = json_encode($snapshotRows);

        // Eğer son versiyon varsa ve satır sayısı + içeriği tamamen aynıysa gereksiz mükerrer versiyon oluşturma
        if ($lastVersion) {
            $lastJson = is_array($lastVersion->rows_data) ? json_encode($lastVersion->rows_data) : $lastVersion->rows_data;
            if ($lastJson === $newJson && $lastVersion->status === $status) {
                return $lastVersion;
            }
        }

        $nextVersionNum = $lastVersion ? ($lastVersion->version_number + 1) : 1;

        if (!$actionType) {
            $actionType = $status === 'submitted' ? 'submit' : ($lastVersion ? 'update' : 'initial_draft');
        }

        $summary = "{$rowCount} satır veri kaydedildi.";
        if ($lastVersion) {
            $diffCount = $rowCount - $lastVersion->row_count;
            if ($diffCount > 0) {
                $summary .= " (+{$diffCount} yeni satır)";
            } elseif ($diffCount < 0) {
                $summary .= " (" . abs($diffCount) . " satır silindi)";
            }
        }

        $newVersion = FormEntryVersionM::create([
            'form_entry_id' => $entry->id,
            'version_number' => $nextVersionNum,
            'status' => $status,
            'action_type' => $actionType,
            'user_id' => $user->id,
            'row_count' => $rowCount,
            'rows_data' => $snapshotRows,
            'summary' => $summary,
        ]);

        // Versiyon geçmişi en fazla 10 adet tutulur
        $allVersionsCount = FormEntryVersionM::where('form_entry_id', $entry->id)->count();
        if ($allVersionsCount > 10) {
            $excess = $allVersionsCount - 10;
            $oldestIds = FormEntryVersionM::where('form_entry_id', $entry->id)
                ->orderBy('version_number', 'asc')
                ->take($excess)
                ->pluck('id');
            FormEntryVersionM::whereIn('id', $oldestIds)->delete();
        }

        return $newVersion;
    }

    /**
     * Kullanıcının görebileceği entry'leri yetkilerine göre filtreler
     */
    public function getVisibleEntriesQuery(UserM $user)
    {
        $permissionS = app(PermissionS::class);
        $query = FormEntryM::with(['template', 'user']);

        if ($permissionS->hasPermission($user, 'form_entries.view_all')) {
            return $query; // Herkesin verisi
        }

        if ($permissionS->hasPermission($user, 'form_entries.view_group')) {
            // Yöneticisi olduğu gruplardaki kullanıcıların verileri
            $managedGroupIds = $user->managedGroups()->pluck('groups.id');
            $userIdsInGroups = UserM::whereIn('group_id', $managedGroupIds)->pluck('id')->toArray();
            
            // Kendi verilerini de görebilmeli
            $userIdsInGroups[] = $user->id;

            return $query->whereIn('user_id', array_unique($userIdsInGroups));
        }

        if ($permissionS->hasPermission($user, 'form_entries.view_own')) {
            return $query->where('user_id', $user->id);
        }

        // Hiçbir yetkisi yoksa
        return $query->where('id', 0);
    }

    /**
     * Seçilen parçalı form kayıtlarını tek bir ana (konsolide) form altında birleştirir
     * veya kullanıcının aynı şablon için mevcut konsolide formuna ekler.
     */
    public function consolidateEntries(array $entryIds, UserM $performedBy): FormEntryM
    {
        return DB::transaction(function () use ($entryIds, $performedBy) {
            $entries = FormEntryM::with(['template.fields', 'rows.values', 'user'])
                ->whereIn('id', $entryIds)
                ->orderBy('entry_date', 'asc')
                ->get();

            if ($entries->isEmpty()) {
                throw new \Exception('Birleştirilecek form parçası bulunamadı.');
            }

            if ($entries->contains(fn($e) => $e->is_consolidated || $e->parent_id !== null)) {
                throw new \Exception('Seçilen kayıtlardan bazıları zaten bir konsolide forma bağlı veya konsolide ana formdur.');
            }

            $templateIds = $entries->pluck('form_template_id')->unique();
            if ($templateIds->count() > 1) {
                throw new \Exception('Yalnızca aynı şablona ait formlar birleştirilebilir.');
            }

            $userIds = $entries->pluck('user_id')->unique();
            if ($userIds->count() > 1) {
                throw new \Exception('Farklı personellere ait form parçaları birleştirilemez. Yalnızca aynı personelin girdiği parçalar birleştirilebilir.');
            }

            $template = $entries->first()->template;
            $firstEntry = $entries->first();

            // Aynı kullanıcı + aynı şablon için mevcut benzersiz konsolide kaydı kontrol et
            $existingConsolidated = FormEntryM::with(['children'])
                ->where('form_template_id', $template->id)
                ->where('user_id', $firstEntry->user_id)
                ->where('is_consolidated', true)
                ->orderBy('id', 'asc')
                ->first();

            if ($existingConsolidated) {
                if ($existingConsolidated->status === 'approved') {
                    throw new \Exception("#{$existingConsolidated->id} numaralı konsolide form onaylandığı için yeni parça eklenemez. Önce onay geri alma veya revizyon işlemi yapılmalıdır.");
                }

                $startOrder = ($existingConsolidated->rows()->max('row_order') ?? -1) + 1;
                $this->appendEntriesToConsolidatedParent($existingConsolidated, $entries, $startOrder);

                $existingConsolidated->load('children');
                $allPartLabels = $existingConsolidated->children->map(function ($e) {
                    return $e->partition_value ?: "#{$e->id}";
                })->unique()->filter()->values()->toArray();

                $allPartsStr = implode(', ', $allPartLabels);
                $dateFormatted = $existingConsolidated->entry_date
                    ? $existingConsolidated->entry_date->format('d.m.Y')
                    : now()->format('d.m.Y');

                $existingConsolidated->title = "Konsolide Form ({$allPartsStr}) - {$template->name} ({$dateFormatted})";
                if ($entries->contains(fn($e) => $e->status === 'draft')) {
                    $existingConsolidated->status = 'draft';
                    $existingConsolidated->submitted_at = null;
                }
                $existingConsolidated->save();

                return $existingConsolidated;
            }

            if ($entries->count() < 2) {
                throw new \Exception('İlk birleştirme işlemi için en az 2 adet parçalı form seçilmelidir.');
            }

            // Bölüm isimlerini topla (Örn: KAPAK, PREFORM, LEVHA)
            $partitionLabels = $entries->map(function ($e) {
                return $e->partition_value ?: "#{$e->id}";
            })->unique()->filter()->values()->toArray();

            $partsStr = implode(', ', $partitionLabels);
            $entryDate = $firstEntry->entry_date ? $firstEntry->entry_date->format('Y-m-d') : now()->format('Y-m-d');
            $dateFormatted = \Carbon\Carbon::parse($entryDate)->format('d.m.Y');

            // Durum tespiti: Tüm parçalar 'approved' ise 'approved', en az bir parça 'draft' ise 'draft', aksi halde 'submitted'
            $allApproved = $entries->every(fn($e) => $e->status === 'approved');
            $hasDraft = $entries->contains(fn($e) => $e->status === 'draft');
            $status = $allApproved ? 'approved' : ($hasDraft ? 'draft' : 'submitted');

            // Konsolide Parent Entry Oluştur
            $parent = new FormEntryM([
                'form_template_id' => $template->id,
                'user_id' => $firstEntry->user_id,
                'entry_date' => $entryDate,
                'status' => $status,
                'is_consolidated' => true,
                'parent_id' => null,
                'partition_value' => null,
                'partition_field_id' => $template->partition_field_id,
                'title' => "Konsolide Form ({$partsStr}) - {$template->name} ({$dateFormatted})",
                'submitted_at' => $status === 'draft' ? null : now(),
            ]);

            if ($allApproved) {
                $latestApprovedChild = $entries
                    ->filter(fn($e) => !empty($e->approved_by))
                    ->sortByDesc('approved_at')
                    ->first();

                $parent->approved_by = $latestApprovedChild?->approved_by ?? $performedBy->id;
                $parent->approved_at = $latestApprovedChild?->approved_at ?? now();
                $parent->submitted_at = $latestApprovedChild?->submitted_at
                    ?? $entries->pluck('submitted_at')->filter()->min()
                    ?? now();
            }

            $template->loadMissing('fields');
            $parent->template_snapshot = $template->fields->toArray();
            $parent->save();

            $this->appendEntriesToConsolidatedParent($parent, $entries, 0);

            return $parent;
        });
    }

    /**
     * Seçilen parçalı kayıtların satırlarını konsolide ana forma kopyalar ve parent_id bağını kurar.
     */
    private function appendEntriesToConsolidatedParent(FormEntryM $parent, $entries, int $startOrder = 0): void
    {
        $parentRowOrder = $startOrder;
        foreach ($entries as $childEntry) {
            foreach ($childEntry->rows as $childRow) {
                $newRow = $parent->rows()->create([
                    'row_order' => $parentRowOrder++
                ]);

                foreach ($childRow->values as $childVal) {
                    $newRow->values()->create([
                        'form_field_id' => $childVal->form_field_id,
                        'value' => $childVal->value
                    ]);
                }
            }

            $childEntry->parent_id = $parent->id;
            $childEntry->save();
        }
    }

    /**
     * Konsolide ana form düzenlendiğinde/onaya gönderildiğinde güncel satırları ve statüyü bağlı parçalara senkronize eder.
     */
    public function syncConsolidatedParentToChildren(FormEntryM $parent, string $status): void
    {
        if (!$parent->is_consolidated) {
            return;
        }

        DB::transaction(function () use ($parent, $status) {
            $parent->load(['children', 'rows.values', 'template']);
            $partitionFieldId = $parent->partition_field_id ?: $parent->template?->partition_field_id;

            $groupedRows = collect();
            if ($partitionFieldId) {
                $groupedRows = $parent->rows->groupBy(function ($row) use ($partitionFieldId) {
                    $valObj = $row->values->firstWhere('form_field_id', $partitionFieldId);
                    return mb_strtolower(trim((string) ($valObj?->value ?? '')), 'UTF-8');
                });
            }

            foreach ($parent->children as $child) {
                if ($partitionFieldId) {
                    $childKey = mb_strtolower(trim((string) $child->partition_value), 'UTF-8');
                    $matchingRows = $groupedRows->get($childKey, collect());

                    if ($matchingRows->isNotEmpty()) {
                        $child->rows()->delete();
                        $rowOrder = 0;
                        foreach ($matchingRows as $pRow) {
                            $newChildRow = $child->rows()->create(['row_order' => $rowOrder++]);
                            foreach ($pRow->values as $pVal) {
                                $newChildRow->values()->create([
                                    'form_field_id' => $pVal->form_field_id,
                                    'value' => $pVal->value,
                                ]);
                            }
                        }
                    }
                }

                if ($status === 'submitted' && $child->status === 'draft') {
                    $child->status = 'submitted';
                    $child->submitted_at = now();
                    $child->save();
                } elseif ($status === 'draft' && $child->status === 'submitted') {
                    $child->status = 'draft';
                    $child->submitted_at = null;
                    $child->save();
                }
            }
        });
    }

    /**
     * Bir alt parça yeniden onaylandığında bağlı olduğu konsolide ana formun satırlarını
     * tüm alt parçaların güncel satırlarından yeniden derler.
     */
    public function rebuildConsolidatedParentRows(FormEntryM $parent): void
    {
        if (!$parent->is_consolidated) {
            return;
        }

        DB::transaction(function () use ($parent) {
            $parent->load([
                'children' => fn($q) => $q->orderBy('id', 'asc'),
                'children.rows.values',
            ]);

            $parent->rows()->delete();

            $parentRowOrder = 0;
            foreach ($parent->children as $child) {
                foreach ($child->rows as $childRow) {
                    $newRow = $parent->rows()->create([
                        'row_order' => $parentRowOrder++,
                    ]);

                    foreach ($childRow->values as $childVal) {
                        $newRow->values()->create([
                            'form_field_id' => $childVal->form_field_id,
                            'value' => $childVal->value,
                        ]);
                    }
                }
            }

            $parent->touch();
        });
    }

    /**
     * Konsolide formu dağıtır (çözer) ve parçaları bağımsız hale getirir.
     */
    public function dissolveConsolidation(FormEntryM $parentEntry, UserM $performedBy): bool
    {
        return DB::transaction(function () use ($parentEntry) {
            if (!$parentEntry->is_consolidated) {
                throw new \Exception('Bu form bir konsolide ana form değildir.');
            }

            // Alt parçaların parent_id bağını kopar
            $parentEntry->children()->update(['parent_id' => null]);

            // Ana formun satırlarını ve kendisini sil
            $parentEntry->rows()->delete();
            $parentEntry->delete();

            return true;
        });
    }

    /**
     * Form kaydının onaya gönderilmeden önce tüm zorunlu alanlarının (koşullu zorunluluk dahil)
     * ve hiyerarşik (cascading) master_data eşleşmelerinin eksiksiz/geçerli olup olmadığını doğrular.
     *
     * @return string[] Hata mesajları listesi (boşsa geçerlidir)
     */
    public function validateEntryForSubmission(FormEntryM $entry): array
    {
        $entry->loadMissing(['template.fields.masterDataType', 'rows.values']);

        $partLabel = !empty($entry->partition_value) ? $entry->partition_value : null;
        $partitionFieldId = $entry->partition_field_id ?: $entry->template?->partition_field_id;

        $rows = [];
        foreach ($entry->rows as $row) {
            $rows[] = $row->values->pluck('value', 'form_field_id')->toArray();
        }

        if (!$entry->template) {
            return ['Form şablonu bulunamadı.'];
        }

        return $this->validateRowsForSubmission(
            $entry->template,
            $rows,
            $partLabel,
            $partitionFieldId,
            $partLabel
        );
    }

    /**
     * Ham satır dizisi üzerinde zorunlu alan ve bağlı (cascading) master_data
     * (örn. ÜLKE -> ŞEHİR, İŞYERİ -> RENK) tutarlılık kontrollerini çalıştırır.
     *
     * @return string[] Hata mesajları listesi (boşsa geçerlidir)
     */
    public function validateRowsForSubmission(
        FormTemplateM $template,
        array $rows,
        ?string $partitionValue = null,
        ?int $partitionFieldId = null,
        ?string $partLabel = null
    ): array {
        $template->loadMissing('fields.masterDataType');

        $fields = $template->fields ?? collect();
        $prefix = $partLabel ? "[{$partLabel}] Parçası - " : '';

        if (empty($rows)) {
            return [
                $partLabel
                    ? "[{$partLabel}] Parçası: Onaya gönderilecek kayıtlı veri satırı bulunmamaktadır."
                    : "Formda onaya gönderilecek kayıtlı veri satırı bulunmamaktadır."
            ];
        }

        // Bağlı (cascading) master_data alanları için tip öğelerini tek sorguda önden yükle (N+1 önlemi)
        $masterTypeIds = $fields->where('type', 'master_data')
            ->pluck('master_data_type_id')
            ->filter()
            ->unique();
        $parentTypeIds = $fields->where('type', 'master_data')
            ->map(fn($f) => $f->masterDataType?->parent_id)
            ->filter()
            ->unique();
        $allNeededTypeIds = $masterTypeIds->merge($parentTypeIds)->unique()->values();

        $itemsByType = $allNeededTypeIds->isNotEmpty()
            ? MasterDataItemM::whereIn('type_id', $allNeededTypeIds)
                ->get(['id', 'type_id', 'parent_id', 'name'])
                ->groupBy('type_id')
            : collect();

        $effectivePartitionFieldId = $partitionFieldId ?: $template->partition_field_id;
        $errors = [];
        $rowCount = count($rows);
        $rowNum = 0;

        foreach ($rows as $rawRow) {
            $rowNum++;
            $rowValues = is_array($rawRow) && isset($rawRow['values']) && is_array($rawRow['values'])
                ? $rawRow['values']
                : (is_array($rawRow) ? $rawRow : []);

            // Satır tamamen boşsa ve birden fazla satır varsa atla
            $hasVal = false;
            foreach ($rowValues as $val) {
                if ($val !== null && trim((string) $val) !== '') {
                    $hasVal = true;
                    break;
                }
            }
            if (!$hasVal && $rowCount > 1) {
                continue;
            }

            foreach ($fields as $field) {
                if ($this->isFieldDisabledForRow($field, $rowValues, $partitionValue, $effectivePartitionFieldId)) {
                    continue;
                }

                if ($this->isFieldCalculatedForRow($field, $rowValues)) {
                    continue;
                }

                $val = $this->resolveRowFieldValue((int) $field->id, $rowValues, $partitionValue, $effectivePartitionFieldId);
                $valTrimmed = $val !== null ? trim((string) $val) : '';

                // Bağlı (cascading) master_data tutarlılık kontrolü (Örn: ÜLKE -> ŞEHİR)
                if (($field->type ?? null) === 'master_data' && !empty($field->master_data_type_id) && $valTrimmed !== '') {
                    $masterType = $field->masterDataType;
                    if ($masterType && !empty($masterType->parent_id)) {
                        $parentField = $fields->first(
                            fn($f) => ($f->type ?? null) === 'master_data' && (int) $f->master_data_type_id === (int) $masterType->parent_id
                        );

                        if ($parentField) {
                            $parentVal = $this->resolveRowFieldValue((int) $parentField->id, $rowValues, $partitionValue, $effectivePartitionFieldId);
                            $parentTrimmed = $parentVal !== null ? trim((string) $parentVal) : '';

                            if ($parentTrimmed === '') {
                                $errors[] = "{$prefix}Satır {$rowNum}: [{$field->label}] alanı için önce [{$parentField->label}] seçilmelidir.";
                                continue;
                            }

                            $parentLower = mb_strtolower($parentTrimmed, 'UTF-8');
                            $parentItems = $itemsByType->get($masterType->parent_id, collect());
                            $selectedParentItem = $parentItems->first(
                                fn($i) => mb_strtolower(trim((string) $i->name), 'UTF-8') === $parentLower
                            );

                            if (!$selectedParentItem) {
                                $errors[] = "{$prefix}Satır {$rowNum}: [{$parentField->label}] alanında geçersiz değer ({$parentTrimmed}) bulunmaktadır.";
                                continue;
                            }

                            $valLower = mb_strtolower($valTrimmed, 'UTF-8');
                            $typeItems = $itemsByType->get($field->master_data_type_id, collect());
                            $matchedChild = $typeItems->first(
                                fn($i) => (int) $i->parent_id === (int) $selectedParentItem->id
                                    && mb_strtolower(trim((string) $i->name), 'UTF-8') === $valLower
                            );

                            if (!$matchedChild) {
                                $errors[] = "{$prefix}Satır {$rowNum}: [{$field->label}] değeri ({$valTrimmed}), seçili [{$parentField->label}] ({$selectedParentItem->name}) ile eşleşmemektedir.";
                                continue;
                            }
                        }
                    }
                }

                if ($this->isMasterDataDisabledForRow($field, $fields, $rowValues, $partitionValue, $effectivePartitionFieldId, $itemsByType)) {
                    continue;
                }

                if (!$this->isFieldRequiredForRow($field, $rowValues, $partitionValue, $effectivePartitionFieldId)) {
                    continue;
                }

                if ($valTrimmed === '') {
                    $errors[] = "{$prefix}Satır {$rowNum}: [{$field->label}] alanı zorunludur ve boş bırakılamaz.";
                }
            }
        }

        return $errors;
    }

    /**
     * Doğrulama hata listesini kullanıcıya gösterilecek okunabilir formata dönüştürür.
     *
     * @param string[] $errors
     * @return string[]
     */
    public function formatValidationErrorsForFlash(array $errors, bool $isConsolidated = false): array
    {
        $maxErrors = 15;
        $total = count($errors);
        $displayErrors = array_slice($errors, 0, $maxErrors);

        if ($total > $maxErrors) {
            $remaining = $total - $maxErrors;
            $displayErrors[] = "...ve {$remaining} adet daha eksik veya hatalı alan bulunmaktadır.";
        }

        $displayErrors[] = $isConsolidated
            ? "Onaya göndermeden önce ilgili parçaları açıp 'Düzenle' butonunu kullanarak eksik veya hatalı alanları düzeltiniz."
            : "Onaya göndermeden önce 'Düzenle' butonunu kullanarak eksik veya hatalı alanları düzeltiniz.";

        return $displayErrors;
    }

    private function isFieldDisabledForRow($field, array $rowValues, ?string $partitionValue, ?int $partitionFieldId): bool
    {
        if (!empty($field->is_disabled)) {
            return true;
        }

        if (empty($field->is_disabled_on_condition) || empty($field->condition_field_id)) {
            return false;
        }

        $targetVal = $this->resolveRowFieldValue((int) $field->condition_field_id, $rowValues, $partitionValue, $partitionFieldId);
        if ($targetVal === null || trim((string) $targetVal) === '') {
            return false;
        }

        $targetValStr = mb_strtolower(trim((string) $targetVal), 'UTF-8');

        if (!empty($field->disabled_condition_value)) {
            $disabledList = array_map(
                fn($s) => mb_strtolower(trim($s), 'UTF-8'),
                explode(',', (string) $field->disabled_condition_value)
            );
            return in_array($targetValStr, $disabledList, true);
        }

        if (!empty($field->condition_value)) {
            $conditionList = array_map(
                fn($s) => mb_strtolower(trim($s), 'UTF-8'),
                explode(',', (string) $field->condition_value)
            );
            return !in_array($targetValStr, $conditionList, true);
        }

        return false;
    }

    private function isFieldCalculatedForRow($field, array $rowValues): bool
    {
        if (empty($field->is_calculated)) {
            return false;
        }

        if (!empty($field->calc_condition_field_id) && !empty($field->calc_condition_value)) {
            $val = $rowValues[$field->calc_condition_field_id] ?? null;
            if ($val === null || trim((string) $val) === '') {
                return false;
            }
            $valStr = mb_strtolower(trim((string) $val), 'UTF-8');
            $calcList = array_map(
                fn($s) => mb_strtolower(trim($s), 'UTF-8'),
                explode(',', (string) $field->calc_condition_value)
            );
            return in_array($valStr, $calcList, true);
        }

        return true;
    }

    private function isFieldRequiredForRow($field, array $rowValues, ?string $partitionValue, ?int $partitionFieldId): bool
    {
        if (!empty($field->is_required)) {
            return true;
        }

        if (!empty($field->condition_field_id) && !empty($field->condition_value)) {
            $targetVal = $this->resolveRowFieldValue((int) $field->condition_field_id, $rowValues, $partitionValue, $partitionFieldId);
            if ($targetVal !== null && trim((string) $targetVal) !== '') {
                $targetValStr = mb_strtolower(trim((string) $targetVal), 'UTF-8');
                $conditionList = array_map(
                    fn($s) => mb_strtolower(trim($s), 'UTF-8'),
                    explode(',', (string) $field->condition_value)
                );
                return in_array($targetValStr, $conditionList, true);
            }
        }

        return false;
    }

    private function isMasterDataDisabledForRow($field, $allFields, array $rowValues, ?string $partitionValue, ?int $partitionFieldId, $itemsByType): bool
    {
        if (($field->type ?? null) !== 'master_data' || empty($field->master_data_type_id)) {
            return false;
        }

        $masterType = $field->masterDataType;
        if (!$masterType) {
            return true;
        }

        $typeItems = $itemsByType->get($field->master_data_type_id, collect());

        if (!empty($masterType->parent_id)) {
            $parentField = $allFields->first(
                fn($f) => ($f->type ?? null) === 'master_data' && (int) $f->master_data_type_id === (int) $masterType->parent_id
            );

            if ($parentField) {
                $selectedParentName = $this->resolveRowFieldValue((int) $parentField->id, $rowValues, $partitionValue, $partitionFieldId);
                if ($selectedParentName === null || trim((string) $selectedParentName) === '') {
                    return true;
                }

                $parentLower = mb_strtolower(trim((string) $selectedParentName), 'UTF-8');
                $parentItems = $itemsByType->get($masterType->parent_id, collect());
                $selectedParentItem = $parentItems->first(
                    fn($i) => mb_strtolower(trim((string) $i->name), 'UTF-8') === $parentLower
                );
                if (!$selectedParentItem) {
                    return true;
                }

                return $typeItems->where('parent_id', $selectedParentItem->id)->isEmpty();
            }
        }

        return $typeItems->isEmpty();
    }

    private function resolveRowFieldValue(int $fieldId, array $rowValues, ?string $partitionValue, ?int $partitionFieldId): mixed
    {
        if ($partitionFieldId && $fieldId === (int) $partitionFieldId && !empty($partitionValue)) {
            return $partitionValue;
        }

        return $rowValues[$fieldId] ?? null;
    }
}
