<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FormEntryVersionM extends Model
{
    protected $table = 'form_entry_versions';

    protected $fillable = [
        'form_entry_id',
        'version_number',
        'status',
        'action_type',
        'user_id',
        'row_count',
        'rows_data',
        'summary',
    ];

    protected function casts(): array
    {
        return [
            'version_number' => 'integer',
            'row_count' => 'integer',
            'rows_data' => 'array',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function formEntry()
    {
        return $this->belongsTo(FormEntryM::class, 'form_entry_id');
    }

    public function user()
    {
        return $this->belongsTo(UserM::class, 'user_id');
    }

    public function getFormattedStatusAttribute(): string
    {
        return match ($this->status) {
            'draft' => 'Taslak',
            'submitted' => 'Onay Bekliyor',
            'approved' => 'Onaylandı',
            'rejected' => 'Reddedildi',
            default => ucfirst($this->status),
        };
    }
}

