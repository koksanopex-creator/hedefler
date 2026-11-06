<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FormRevisionRequestM extends Model
{
    protected $table = 'form_revision_requests';

    protected $fillable = [
        'form_entry_id',
        'user_id',
        'reason',
        'status',
        'reviewed_by',
        'rejection_reason',
        'reviewed_at',
        'is_dismissed_by_user',
    ];

    protected function casts(): array
    {
        return [
            'reviewed_at' => 'datetime',
            'is_dismissed_by_user' => 'boolean',
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

    public function reviewer()
    {
        return $this->belongsTo(UserM::class, 'reviewed_by');
    }
}

