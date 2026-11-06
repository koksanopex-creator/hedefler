<?php

namespace App\Models;

use App\Services\ActivityLogS;
use Illuminate\Database\Eloquent\Model;

class ActivityLogM extends Model
{
    protected $table = 'activity_logs';
    protected $fillable = ['user_id', 'action', 'description', 'model_type', 'model_id', 'ip_address'];

    public function user()
    {
        return $this->belongsTo(UserM::class, 'user_id');
    }

    public function getActionLabelAttribute(): string
    {
        return ActivityLogS::getActionMeta($this->action ?? '')['label'];
    }

    public function getActionBadgeClassAttribute(): string
    {
        return ActivityLogS::getActionMeta($this->action ?? '')['badge'];
    }
}
