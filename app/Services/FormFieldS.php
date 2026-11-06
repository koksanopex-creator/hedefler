<?php

namespace App\Services;

use App\Models\FormFieldM;
use App\Models\FormTemplateM;
use Illuminate\Support\Facades\DB;

class FormFieldS
{
    /**
     * Şablona yeni alan(lar) ekler veya günceller
     */
    public function syncFields(FormTemplateM $template, array $fieldsData): void
    {
        DB::transaction(function () use ($template, $fieldsData) {
            $incomingIds = [];
            $sortOrder = 0;
            
            foreach ($fieldsData as $fieldData) {
                $fieldId = $fieldData['id'] ?? null;
                $options = null;
                if (isset($fieldData['options']) && $fieldData['options'] !== '') {
                    $options = is_array($fieldData['options']) ? $fieldData['options'] : json_decode($fieldData['options'], true);
                }
                
                $dataToSave = [
                    'label' => $fieldData['label'],
                    'field_key' => str()->slug($fieldData['label']),
                    'type' => $fieldData['type'],
                    'value_type' => $fieldData['value_type'],
                    'options' => $options,
                    'master_data_type_id' => isset($fieldData['master_data_type_id']) && $fieldData['type'] === 'master_data' ? $fieldData['master_data_type_id'] : null,
                    'is_required' => $fieldData['is_required'] ?? false,
                    'is_summable' => $fieldData['is_summable'] ?? false,
                    'is_calculated' => $fieldData['is_calculated'] ?? false,
                    'condition_field_id' => $fieldData['condition_field_id'] ?? null,
                    'condition_value' => isset($fieldData['condition_value']) ? (is_array($fieldData['condition_value']) ? implode(',', $fieldData['condition_value']) : $fieldData['condition_value']) : null,
                    'calculation_formula' => $fieldData['calculation_formula'] ?? null,
                    'calc_condition_field_id' => $fieldData['calc_condition_field_id'] ?? null,
                    'calc_condition_value' => isset($fieldData['calc_condition_value']) ? (is_array($fieldData['calc_condition_value']) ? implode(',', $fieldData['calc_condition_value']) : $fieldData['calc_condition_value']) : null,
                    'filter_by_user_group' => $fieldData['filter_by_user_group'] ?? false,
                    'sort_order' => $sortOrder++
                ];

                if ($fieldId) {
                    // Mevcut alanı güncelle
                    $template->fields()->where('id', $fieldId)->update($dataToSave);
                    $incomingIds[] = (int)$fieldId;
                } else {
                    // Yeni alan ekle
                    $newField = $template->fields()->create($dataToSave);
                    $incomingIds[] = $newField->id;
                }
            }
            
            // Silinen alanları tespit et
            $fieldsToDelete = $template->fields()->whereNotIn('id', $incomingIds)->get();
            
            foreach ($fieldsToDelete as $field) {
                try {
                    $field->delete();
                } catch (\Illuminate\Database\QueryException $e) {
                    if ($e->getCode() == 23000) { // Foreign Key Constraint Violation
                        throw new \Exception("'" . $field->label . "' alanı daha önce doldurulmuş formlarda kullanıldığı için silinemez. Silmek yerine form girişlerini iptal etmeniz veya bu alanın adını değiştirmeniz gerekir.");
                    }
                    throw $e;
                }
            }
        });
    }
}
