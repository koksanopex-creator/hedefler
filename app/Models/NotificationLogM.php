<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NotificationLogM extends Model
{
    use HasFactory;

    protected $table = 'notification_logs';

    protected $fillable = [
        'channel',
        'event_slug',
        'event_name',
        'user_id',
        'recipient_name',
        'recipient_email',
        'recipient_role',
        'subject',
        'body',
        'action_url',
        'status',
        'error_message',
        'metadata',
        'sent_at',
    ];

    protected $casts = [
        'metadata' => 'array',
        'sent_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(UserM::class, 'user_id');
    }

    public function scopeChannel($query, ?string $channel)
    {
        if (!empty($channel) && in_array($channel, ['mail', 'in_app'])) {
            return $query->where('channel', $channel);
        }
        return $query;
    }

    public function scopeEventSlug($query, ?string $eventSlug)
    {
        if (!empty($eventSlug)) {
            return $query->where('event_slug', $eventSlug);
        }
        return $query;
    }

    public function scopeRole($query, ?string $role)
    {
        if (!empty($role)) {
            return $query->where('recipient_role', $role);
        }
        return $query;
    }

    public function scopeSearch($query, ?string $search)
    {
        if (!empty($search)) {
            $trimmed = trim($search);
            return $query->where(function ($q) use ($trimmed) {
                $q->where('recipient_name', 'like', "%{$trimmed}%")
                  ->orWhere('recipient_email', 'like', "%{$trimmed}%")
                  ->orWhere('subject', 'like', "%{$trimmed}%")
                  ->orWhere('body', 'like', "%{$trimmed}%")
                  ->orWhere('event_name', 'like', "%{$trimmed}%");
            });
        }
        return $query;
    }

    public function scopeDateRange($query, ?string $dateFrom, ?string $dateTo)
    {
        if (!empty($dateFrom)) {
            $query->whereDate('sent_at', '>=', $dateFrom);
        }
        if (!empty($dateTo)) {
            $query->whereDate('sent_at', '<=', $dateTo);
        }
        return $query;
    }
}

