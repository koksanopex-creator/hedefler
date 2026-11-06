<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SsoApplicationM extends Model
{
    use HasFactory;

    protected $table = 'sso_applications';

    protected $fillable = [
        'user_id',
        'central_user_id',
        'name',
        'email',
        'department',
        'application_note',
        'status', // pending, approved, rejected
        'rejection_type', // correctable, permanent
        'rejection_reason',
        'reviewed_by',
        'reviewed_at',
    ];

    protected $casts = [
        'reviewed_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(UserM::class, 'user_id');
    }

    public function reviewer()
    {
        return $this->belongsTo(UserM::class, 'reviewed_by');
    }
}

