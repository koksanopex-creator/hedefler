<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NotificationEventM extends Model
{
    protected $table = 'notification_events';
    protected $fillable = ['name', 'slug'];

    public function matrix()
    {
        return $this->hasMany(NotificationMatrixM::class, 'notification_event_id');
    }

    public function template()
    {
        return $this->hasOne(NotificationTemplateM::class, 'notification_event_id');
    }
}
