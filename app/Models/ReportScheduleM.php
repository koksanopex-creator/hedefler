<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ReportScheduleM extends Model
{
    protected $table = 'report_schedules';

    protected $fillable = [
        'report_rule_id',
        'title',
        'frequency',
        'execution_time',
        'execution_day',
        'recipient_roles',
        'recipient_groups',
        'recipient_users',
        'custom_emails',
        'include_excel',
        'is_active',
        'last_run_at',
        'next_run_at',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'recipient_roles' => 'array',
            'recipient_groups' => 'array',
            'recipient_users' => 'array',
            'include_excel' => 'boolean',
            'is_active' => 'boolean',
            'last_run_at' => 'datetime',
            'next_run_at' => 'datetime',
        ];
    }

    public function rule(): BelongsTo
    {
        return $this->belongsTo(ReportRuleM::class, 'report_rule_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(UserM::class, 'created_by');
    }

    public function logs(): HasMany
    {
        return $this->hasMany(ReportScheduleLogM::class, 'report_schedule_id')->latest('executed_at');
    }
}

