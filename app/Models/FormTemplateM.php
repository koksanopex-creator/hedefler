<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FormTemplateM extends Model
{
    protected $table = 'form_templates';
    protected $fillable = [
        'name', 'slug', 'description', 'created_by', 'is_active', 'show_row_totals',
        'allow_partition_entry', 'partition_field_id'
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'show_row_totals' => 'boolean',
            'allow_partition_entry' => 'boolean',
            'partition_field_id' => 'integer',
        ];
    }

    public function partitionField()
    {
        return $this->belongsTo(FormFieldM::class, 'partition_field_id');
    }

    public function isPartitioned(): bool
    {
        return (bool)($this->allow_partition_entry && $this->partition_field_id);
    }

    public function fields()
    {
        return $this->hasMany(FormFieldM::class, 'form_template_id')->orderBy('sort_order');
    }

    public function schedules()
    {
        return $this->hasMany(FormScheduleM::class, 'form_template_id');
    }

    public function creator()
    {
        return $this->belongsTo(UserM::class, 'created_by');
    }

    public function kpiTargets()
    {
        return $this->hasMany(KpiTargetM::class, 'form_template_id');
    }
}
