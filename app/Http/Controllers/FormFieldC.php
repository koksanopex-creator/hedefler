<?php

namespace App\Http\Controllers;

use App\Models\FormTemplateM;
use App\Services\FormFieldS;
use Illuminate\Http\Request;

class FormFieldC extends Controller
{
    protected FormFieldS $formFieldService;

    public function __construct(FormFieldS $formFieldService)
    {
        $this->formFieldService = $formFieldService;
    }

    public function edit(FormTemplateM $formTemplate)
    {
        $formTemplate->load('fields');
        $masterDataTypes = \App\Models\MasterDataTypeM::with('items')->get();
        $allUsers = \App\Models\UserM::with(['role', 'group'])->where('is_active', true)->orderBy('name', 'asc')->get();
        return view('form_fields.edit', compact('formTemplate', 'masterDataTypes', 'allUsers'));
    }

    public function update(Request $request, FormTemplateM $formTemplate)
    {
        $validated = $request->validate([
            'allow_partition_entry' => 'nullable|in:0,1',
            'partition_field_id' => 'nullable',
            'fields' => 'required|array',
            'fields.*.id' => 'nullable|integer',
            'fields.*.label' => 'required|string',
            'fields.*.type' => 'required|in:text,number,textarea,select,date,auto_user,master_data,user_list,year',
            'fields.*.value_type' => 'required|in:nicel,nitel',
            'fields.*.options' => 'nullable',
            'fields.*.master_data_type_id' => 'nullable|exists:master_data_types,id',
            'fields.*.is_required' => 'boolean',
            'fields.*.is_summable' => 'boolean',
            'fields.*.is_calculated' => 'boolean',
            'fields.*.condition_field_id' => 'nullable|exists:form_fields,id',
            'fields.*.condition_value' => 'nullable',
            'fields.*.calculation_formula' => 'nullable|string',
            'fields.*.calc_condition_field_id' => 'nullable|exists:form_fields,id',
            'fields.*.calc_condition_value' => 'nullable',
            'fields.*.filter_by_user_group' => 'nullable|boolean',
            'fields.*._uid' => 'nullable|string',
        ]);

        try {
            $incomingIds = $this->formFieldService->syncFields($formTemplate, $validated['fields']);

            // Şablonun parçalı giriş modunu ve kriter alanını güncelle
            $allowPartition = $request->input('allow_partition_entry') == '1';
            $partitionFieldRaw = $request->input('partition_field_id');
            $partitionFieldId = null;

            if ($allowPartition && !empty($partitionFieldRaw)) {
                if (is_numeric($partitionFieldRaw)) {
                    if ($formTemplate->fields()->where('id', $partitionFieldRaw)->exists()) {
                        $partitionFieldId = (int)$partitionFieldRaw;
                    }
                } elseif (str_starts_with((string)$partitionFieldRaw, 'uid_')) {
                    // Client UID ile eşleştir
                    $fieldsInput = $request->input('fields', []);
                    foreach ($fieldsInput as $idx => $fData) {
                        if (($fData['_uid'] ?? '') === $partitionFieldRaw && isset($incomingIds[$idx])) {
                            $partitionFieldId = $incomingIds[$idx];
                            break;
                        }
                    }
                } elseif (str_starts_with((string)$partitionFieldRaw, 'index:')) {
                    $idx = (int)str_replace('index:', '', $partitionFieldRaw);
                    if (isset($incomingIds[$idx])) {
                        $partitionFieldId = $incomingIds[$idx];
                    }
                } else {
                    $matched = $formTemplate->fields()->where('label', $partitionFieldRaw)->first();
                    if ($matched) {
                        $partitionFieldId = $matched->id;
                    }
                }
            }

            $formTemplate->update([
                'allow_partition_entry' => $allowPartition,
                'partition_field_id' => $partitionFieldId,
            ]);
        } catch (\Exception $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }

        return redirect()->route('form-templates.show', $formTemplate)->with('success', 'Alanlar ve şablon ayarları başarıyla güncellendi.');
    }
}
