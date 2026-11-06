<?php

namespace Database\Seeders;

use App\Models\NotificationEventM;
use App\Models\NotificationMatrixM;
use App\Models\NotificationTemplateM;
use Illuminate\Database\Seeder;

class NotificationSeeder extends Seeder
{
    public function run(): void
    {
        $events = [
            ['name' => 'Yeni Form Gönderildi', 'slug' => 'form.submitted'],
            ['name' => 'Form Onaylandı', 'slug' => 'form.approved'],
            ['name' => 'Form Reddedildi', 'slug' => 'form.rejected'],
            ['name' => 'Form Verisi Silindi', 'slug' => 'form.deleted'],
            ['name' => 'Form Şablonu Silindi', 'slug' => 'template.deleted'],
            ['name' => 'Yeni Kullanıcı Eklendi', 'slug' => 'user.created'],
        ];

        foreach ($events as $event) {
            NotificationEventM::firstOrCreate(['slug' => $event['slug']], ['name' => $event['name']]);
        }

        // --- Templates ---
        $templates = [
            'form.submitted' => [
                'subject' => 'Onay Bekleyen Yeni Bir Form Var',
                'body' => 'Merhaba,<br><br>{user_name} adlı kullanıcı "{form_name}" başlıklı yeni bir form gönderdi.<br><br>Lütfen sistemi kontrol ediniz.'
            ],
            'form.approved' => [
                'subject' => 'Formunuz Onaylandı',
                'body' => 'Merhaba {user_name},<br><br>Göndermiş olduğunuz "{form_name}" başlıklı form onaylanmıştır.'
            ],
            'form.rejected' => [
                'subject' => 'Formunuz Reddedildi',
                'body' => 'Merhaba {user_name},<br><br>Göndermiş olduğunuz "{form_name}" başlıklı form reddedilmiştir.<br>Red Nedeni: {rejection_reason}'
            ],
            'form.deleted' => [
                'subject' => 'Form Verisi Silindi',
                'body' => 'Merhaba,<br><br>Sisteme girilmiş olan bir "{form_name}" verisi silinmiştir.'
            ],
            'template.deleted' => [
                'subject' => 'Form Şablonu Silindi',
                'body' => 'Merhaba,<br><br>"{form_name}" isimli form şablonu sistemden silinmiştir.'
            ],
            'user.created' => [
                'subject' => 'Sisteme Yeni Kullanıcı Eklendi',
                'body' => 'Merhaba,<br><br>Sisteme "{user_name}" adlı yeni bir kullanıcı eklenmiştir.'
            ],
        ];

        foreach ($templates as $slug => $data) {
            $event = NotificationEventM::where('slug', $slug)->first();
            if ($event) {
                NotificationTemplateM::firstOrCreate(
                    ['notification_event_id' => $event->id],
                    ['subject' => $data['subject'], 'body' => $data['body']]
                );
            }
        }

        // --- Default Matrix ---
        // form.submitted -> Group Manager
        $this->addMatrix('form.submitted', 'special:group_manager', true, true);
        
        // form.approved -> Creator
        $this->addMatrix('form.approved', 'special:creator', true, true);
        
        // form.rejected -> Creator
        $this->addMatrix('form.rejected', 'special:creator', true, true);
        
        // user.created -> Sistem Yöneticisi Role
        $this->addMatrix('user.created', 'role:sistem_yoneticisi', false, true);

        // form.deleted -> Sistem Yöneticisi Role
        $this->addMatrix('form.deleted', 'role:sistem_yoneticisi', false, true);

        // form.revision_requested -> Creator, Group Manager, Sistem Yöneticisi
        $this->addMatrix('form.revision_requested', 'special:creator', true, true);
        $this->addMatrix('form.revision_requested', 'special:group_manager', true, true);
        $this->addMatrix('form.revision_requested', 'role:sistem_yoneticisi', true, true);

        // form.revision_approved -> Creator, Sistem Yöneticisi
        $this->addMatrix('form.revision_approved', 'special:creator', true, true);
        $this->addMatrix('form.revision_approved', 'role:sistem_yoneticisi', true, true);

        // form.revision_rejected -> Creator, Sistem Yöneticisi
        $this->addMatrix('form.revision_rejected', 'special:creator', true, true);
        $this->addMatrix('form.revision_rejected', 'role:sistem_yoneticisi', true, true);
    }

    private function addMatrix($eventSlug, $targetType, $sendEmail, $sendInApp)
    {
        $event = NotificationEventM::where('slug', $eventSlug)->first();
        if ($event) {
            NotificationMatrixM::firstOrCreate(
                [
                    'notification_event_id' => $event->id,
                    'target_type' => $targetType
                ],
                [
                    'send_email' => $sendEmail,
                    'send_in_app' => $sendInApp
                ]
            );
        }
    }
}
