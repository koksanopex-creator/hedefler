<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FormFieldM extends Model
{
    protected $table = 'form_fields';
    protected $fillable = [
        'form_template_id', 
        'label', 
        'field_key', 
        'type', 
        'value_type', 
        'options', 
        'master_data_type_id', 
        'is_required', 
        'is_summable', 
        'sort_order',
        'condition_field_id',
        'condition_value',
        'is_calculated',
        'calculation_formula',
        'calc_condition_field_id',
        'calc_condition_value',
        'filter_by_user_group'
    ];

    protected function casts(): array
    {
        return [
            'options' => 'array',
            'is_required' => 'boolean',
            'is_summable' => 'boolean',
            'is_calculated' => 'boolean',
            'filter_by_user_group' => 'boolean',
        ];
    }

    public function template()
    {
        return $this->belongsTo(FormTemplateM::class, 'form_template_id');
    }

    public function masterDataType()
    {
        return $this->belongsTo(MasterDataTypeM::class, 'master_data_type_id');
    }

    public function conditionField()
    {
        return $this->belongsTo(FormFieldM::class, 'condition_field_id');
    }

    public function calcConditionField()
    {
        return $this->belongsTo(FormFieldM::class, 'calc_condition_field_id');
    }

    public function kpiTargets()
    {
        return $this->hasMany(KpiTargetM::class, 'target_field_id');
    }
}
