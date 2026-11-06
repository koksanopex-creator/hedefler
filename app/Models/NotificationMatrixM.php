<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NotificationMatrixM extends Model
{
    protected $table = 'notification_matrix';
    protected $fillable = ['notification_event_id', 'target_type', 'send_email', 'send_in_app'];

    protected $casts = [
        'send_email' => 'boolean',
        'send_in_app' => 'boolean',
    ];

    public function event()
    {
        return $this->belongsTo(NotificationEventM::class, 'notification_event_id');
    }
}
