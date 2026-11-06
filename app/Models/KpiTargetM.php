<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class KpiTargetM extends Model
{
    protected $table = 'kpi_targets';

    protected $fillable = [
        'form_template_id',
        'target_field_id',
        'title',
        'description',
        'target_year',
        'assignee_type',
        'assignee_id',
        'target_value',
        'unit',
        'criteria_json',
        'parent_target_id',
        'status',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'criteria_json' => 'array',
            'target_year' => 'integer',
            'target_value' => 'decimal:2',
        ];
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(FormTemplateM::class, 'form_template_id');
    }

    public function targetField(): BelongsTo
    {
        return $this->belongsTo(FormFieldM::class, 'target_field_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(UserM::class, 'created_by');
    }

    public function parentTarget(): BelongsTo
    {
        return $this->belongsTo(KpiTargetM::class, 'parent_target_id');
    }

    public function childTargets(): HasMany
    {
        return $this->hasMany(KpiTargetM::class, 'parent_target_id');
    }

    public function periods(): HasMany
    {
        return $this->hasMany(KpiTargetPeriodM::class, 'kpi_target_id')->orderBy('period_month');
    }

    public function realizations(): HasMany
    {
        return $this->hasMany(KpiTargetRealizationM::class, 'kpi_target_id')->orderBy('realization_month');
    }

    /**
     * Hedef sahibini assignee_type'a göre çözer.
     */
    public function getAssigneeAttribute()
    {
        if ($this->assignee_type === 'user') {
            return UserM::find($this->assignee_id);
        } elseif ($this->assignee_type === 'group') {
            return GroupM::find($this->assignee_id);
        } elseif ($this->assignee_type === 'manager') {
            // assignee_id grup id'si ise grubun yöneticisini döner
            $group = GroupM::find($this->assignee_id);
            return $group ? $group->managers->first() : null;
        }

        return null;
    }

    /**
     * Hedef sahibinin okunabilir adı
     */
    public function getAssigneeNameAttribute(): string
    {
        if ($this->assignee_type === 'user') {
            $user = UserM::find($this->assignee_id);
            return $user ? $user->name : 'Bilinmeyen Kullanıcı';
        } elseif ($this->assignee_type === 'group') {
            $group = GroupM::find($this->assignee_id);
            return $group ? ($group->name . ' (Grup)') : 'Bilinmeyen Grup';
        } elseif ($this->assignee_type === 'manager') {
            $group = GroupM::find($this->assignee_id);
            $mgr = $group ? $group->managers->first() : null;
            return $mgr ? ($mgr->name . ' (' . $group->name . ' Yöneticisi)') : ($group ? $group->name . ' Yöneticisi' : 'Yönetici');
        }

        return 'Tanımsız';
    }

    /**
     * Toplam gerçekleşen değeri döner (en son hesaplanan gerçekleşme)
     */
    public function getLatestRealizationAttribute(): ?KpiTargetRealizationM
    {
        return $this->realizations()
            ->where('realization_month', 0)
            ->latest('calculated_at')
            ->first();
    }
}

