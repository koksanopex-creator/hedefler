<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FormTemplateM extends Model
{
    protected $table = 'form_templates';
    protected $fillable = ['name', 'slug', 'description', 'created_by', 'is_active', 'show_row_totals'];

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
