<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ReportRuleM extends Model
{
    protected $table = 'report_rules';

    protected $fillable = [
        'title',
        'description',
        'form_template_id',
        'dimension_x_field_id',
        'dimension_x_type',
        'dimension_series_field_id',
        'dimension_series_type',
        'metric_field_id',
        'metric_aggregation',
        'chart_type',
        'filters_json',
        'is_public',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'filters_json' => 'array',
            'is_public' => 'boolean',
            'dimension_x_field_id' => 'integer',
            'dimension_series_field_id' => 'integer',
            'metric_field_id' => 'integer',
        ];
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(FormTemplateM::class, 'form_template_id');
    }

    public function dimensionXField(): BelongsTo
    {
        return $this->belongsTo(FormFieldM::class, 'dimension_x_field_id');
    }

    public function dimensionSeriesField(): BelongsTo
    {
        return $this->belongsTo(FormFieldM::class, 'dimension_series_field_id');
    }

    public function metricField(): BelongsTo
    {
        return $this->belongsTo(FormFieldM::class, 'metric_field_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(UserM::class, 'created_by');
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(ReportScheduleM::class, 'report_rule_id');
    }
}

