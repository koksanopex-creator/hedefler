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
        ]);

        try {
            $this->formFieldService->syncFields($formTemplate, $validated['fields']);
        } catch (\Exception $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }

        return redirect()->route('form-templates.show', $formTemplate)->with('success', 'Alanlar başarıyla güncellendi.');
    }
}
