<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReportScheduleLogM extends Model
{
    protected $table = 'report_schedule_logs';

    public $timestamps = false;

    protected $fillable = [
        'report_schedule_id',
        'status',
        'recipients_count',
        'recipients_summary',
        'error_message',
        'executed_at',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'executed_at' => 'datetime',
            'created_at' => 'datetime',
        ];
    }

    public function schedule(): BelongsTo
    {
        return $this->belongsTo(ReportScheduleM::class, 'report_schedule_id');
    }
}

