<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NotificationTemplateM extends Model
{
    protected $table = 'notification_templates';
    
    protected $fillable = [
        'notification_event_id',
        'subject',
        'in_app_subject',
        'body',
        'in_app_body',
        'additional_emails',
        'role_templates'
    ];

    protected $casts = [
        'role_templates' => 'array'
    ];

    /**
     * Belirli bir hedef türü (örn: role:sistem_yoneticisi, special:group_manager) için
     * özelleştirilmiş şablon varsa döner, yoksa varsayılan genel şablonu döner.
     */
    public function getResolvedTemplate(?string $targetType = null): array
    {
        $resolved = [
            'subject' => $this->subject,
            'body' => $this->body,
            'in_app_subject' => !empty($this->in_app_subject) ? $this->in_app_subject : $this->subject,
            'in_app_body' => !empty($this->in_app_body) ? $this->in_app_body : $this->body,
        ];

        if ($targetType && !empty($this->role_templates[$targetType])) {
            $rt = $this->role_templates[$targetType];
            if (!empty($rt['is_custom'])) {
                if (!empty($rt['subject'])) $resolved['subject'] = $rt['subject'];
                if (!empty($rt['body'])) $resolved['body'] = $rt['body'];
                $resolved['in_app_subject'] = !empty($rt['in_app_subject']) ? $rt['in_app_subject'] : $resolved['subject'];
                $resolved['in_app_body'] = !empty($rt['in_app_body']) ? $rt['in_app_body'] : $resolved['body'];
            }
        }

        return $resolved;
    }

    public function event()
    {
        return $this->belongsTo(NotificationEventM::class, 'notification_event_id');
    }
}
