<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KpiTargetRealizationM extends Model
{
    protected $table = 'kpi_target_realizations';

    protected $fillable = [
        'kpi_target_id',
        'realization_year',
        'realization_month',
        'realization_week',
        'actual_value',
        'achievement_rate',
        'calculated_at',
    ];

    protected function casts(): array
    {
        return [
            'realization_year' => 'integer',
            'realization_month' => 'integer',
            'realization_week' => 'integer',
            'actual_value' => 'decimal:2',
            'achievement_rate' => 'decimal:2',
            'calculated_at' => 'datetime',
        ];
    }

    public function target(): BelongsTo
    {
        return $this->belongsTo(KpiTargetM::class, 'kpi_target_id');
    }

    public function getMonthNameAttribute(): string
    {
        if ($this->realization_month === 0) {
            return 'Yıllık Toplam';
        }

        $months = [
            1 => 'Ocak', 2 => 'Şubat', 3 => 'Mart', 4 => 'Nisan',
            5 => 'Mayıs', 6 => 'Haziran', 7 => 'Temmuz', 8 => 'Ağustos',
            9 => 'Eylül', 10 => 'Ekim', 11 => 'Kasım', 12 => 'Aralık'
        ];

        return $months[$this->realization_month] ?? 'Ay ' . $this->realization_month;
    }

    /**
     * Başarı rengi badge class'ı döner
     */
    public function getBadgeClassAttribute(): string
    {
        if ($this->achievement_rate >= 100) {
            return 'bg-emerald-100 text-emerald-800 border-emerald-300';
        } elseif ($this->achievement_rate >= 70) {
            return 'bg-amber-100 text-amber-800 border-amber-300';
        } else {
            return 'bg-rose-100 text-rose-800 border-rose-300';
        }
    }
}

