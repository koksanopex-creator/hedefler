<?php

namespace App\Services;

use App\Models\FormEntryM;
use App\Models\FormTemplateM;
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
                }
            }
            
            $entry->save();

            // Güvenlik Doğrulaması: filter_by_user_group alanları için yetkisiz değer gönderilmiş mi?
            $template->loadMissing('fields');
            $restrictedFields = $template->fields->where('filter_by_user_group', true);
            if ($restrictedFields->isNotEmpty()) {
                $formEntryC = app(\App\Http\Controllers\FormEntryC::class);
                $userScope = $formEntryC->getUserScope($user);
                
                if (!$userScope['is_admin']) {
                    $allowedNamesLower = array_map(fn($n) => mb_strtolower(trim($n)), $userScope['allowed_user_names']);

                    foreach ($rowsData as $rowData) {
                        if (empty(array_filter($rowData))) continue;

                        foreach ($restrictedFields as $field) {
                            $val = $rowData[$field->id] ?? null;
                            if (!empty($val)) {
                                $valLower = mb_strtolower(trim($val));
                                $isPermitted = false;
                                foreach ($allowedNamesLower as $allowed) {
                                    if ($valLower === $allowed || str_contains($valLower, $allowed) || str_contains($allowed, $valLower)) {
                                        $isPermitted = true;
                                        break;
                                    }
                                }
                                if (!$isPermitted) {
                                    throw new \Exception("Yetkisiz veri girişi: '{$field->label}' alanında '{$val}' seçme yetkiniz bulunmamaktadır.");
                                }
                            }
                        }
                    }
                }
            }

            // Mevcut satırları temizle ve baştan yaz
            $entry->rows()->delete();

            $fieldTypes = $template->fields->pluck('type', 'id');
            $rowOrder = 0;
            foreach ($rowsData as $rowData) {
                // Eğer satır tamamen boşsa atla
                if (empty(array_filter($rowData))) {
                    continue;
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

            return $entry;
        });
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
     * Seçilen parçalı form kayıtlarını tek bir ana (konsolide) form altında birleştirir.
     */
    public function consolidateEntries(array $entryIds, UserM $performedBy): FormEntryM
    {
        return DB::transaction(function () use ($entryIds, $performedBy) {
            $entries = FormEntryM::with(['template.fields', 'rows.values', 'user'])
                ->whereIn('id', $entryIds)
                ->orderBy('entry_date', 'asc')
                ->get();

            if ($entries->count() < 2) {
                throw new \Exception('Birleştirme işlemi için en az 2 adet parçalı form seçilmelidir.');
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

            // Bölüm isimlerini topla (Örn: KAPAK, PREFORM, LEVHA)
            $partitionLabels = $entries->map(function ($e) {
                return $e->partition_value ?: "#{$e->id}";
            })->unique()->filter()->values()->toArray();

            $partsStr = implode(', ', $partitionLabels);
            $entryDate = $firstEntry->entry_date ? $firstEntry->entry_date->format('Y-m-d') : now()->format('Y-m-d');
            $dateFormatted = \Carbon\Carbon::parse($entryDate)->format('d.m.Y');

            // Durum tespiti: Eğer tüm parçalar 'approved' ise ana form da 'approved', aksi halde 'submitted'
            $allApproved = $entries->every(fn($e) => $e->status === 'approved');
            $status = $allApproved ? 'approved' : 'submitted';

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
                'submitted_at' => now(),
            ]);

            if ($allApproved) {
                $parent->approved_at = now();
                $parent->approved_by = $performedBy->id;
            }

            $template->loadMissing('fields');
            $parent->template_snapshot = $template->fields->toArray();
            $parent->save();

            // Tüm parçaların satırlarını parent'a kopyala
            $parentRowOrder = 0;
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

                // Child entry'yi parent'a bağla
                $childEntry->parent_id = $parent->id;
                $childEntry->save();
            }

            return $parent;
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
}
