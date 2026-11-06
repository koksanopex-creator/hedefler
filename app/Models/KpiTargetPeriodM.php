<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KpiTargetPeriodM extends Model
{
    protected $table = 'kpi_target_periods';

    protected $fillable = [
        'kpi_target_id',
        'period_year',
        'period_month',
        'period_target_value',
    ];

    protected function casts(): array
    {
        return [
            'period_year' => 'integer',
            'period_month' => 'integer',
            'period_target_value' => 'decimal:2',
        ];
    }

    public function target(): BelongsTo
    {
        return $this->belongsTo(KpiTargetM::class, 'kpi_target_id');
    }

    public function getMonthNameAttribute(): string
    {
        $months = [
            1 => 'Ocak', 2 => 'Şubat', 3 => 'Mart', 4 => 'Nisan',
            5 => 'Mayıs', 6 => 'Haziran', 7 => 'Temmuz', 8 => 'Ağustos',
            9 => 'Eylül', 10 => 'Ekim', 11 => 'Kasım', 12 => 'Aralık'
        ];

        return $months[$this->period_month] ?? 'Ay ' . $this->period_month;
    }
}

