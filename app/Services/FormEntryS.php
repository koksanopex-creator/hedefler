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
    public function saveEntry(FormTemplateM $template, UserM $user, string $entryDate, array $rowsData, string $status = 'draft', ?FormEntryM $existingEntry = null): FormEntryM
    {
        return DB::transaction(function () use ($template, $user, $entryDate, $rowsData, $status, $existingEntry) {
            
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
}
