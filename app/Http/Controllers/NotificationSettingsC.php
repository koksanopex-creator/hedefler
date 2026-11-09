<?php

namespace App\Http\Controllers;

use App\Models\NotificationEventM;
use App\Models\NotificationMatrixM;
use App\Models\NotificationTemplateM;
use App\Models\RoleM;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class NotificationSettingsC extends Controller
{
    public function index()
    {
        $allEvents = NotificationEventM::with(['matrix', 'template'])->get();
        $events = $allEvents->whereNotIn('slug', ['application.approved', 'application.rejected']);
        $applicationEvents = $allEvents->whereIn('slug', ['application.approved', 'application.rejected']);
        $roles = RoleM::all();
        
        // Özel dinamik hedefler
        $specialTargets = [
            'special:target_assignee' => 'Hedef Sahibi / Personel',
            'special:group_manager' => 'Kullanıcının Yöneticisi',
            'special:creator' => 'İşlemi Yapan Kişi'
        ];

        return view('settings.notifications.index', compact('events', 'applicationEvents', 'roles', 'specialTargets'));
    }

    public function updateMatrix(Request $request)
    {
        $matrixData = $request->input('matrix', []);
        
        DB::transaction(function () use ($matrixData) {
            NotificationMatrixM::query()->delete();

            foreach ($matrixData as $eventId => $targets) {
                foreach ($targets as $targetType => $channels) {
                    $sendEmail = isset($channels['email']) && $channels['email'] == '1';
                    $sendInApp = isset($channels['in_app']) && $channels['in_app'] == '1';
                    
                    if ($sendEmail || $sendInApp) {
                        NotificationMatrixM::create([
                            'notification_event_id' => $eventId,
                            'target_type' => $targetType,
                            'send_email' => $sendEmail,
                            'send_in_app' => $sendInApp
                        ]);
                    }
                }
            }
        });

        return redirect()->route('settings.notifications.index')->with('success', 'Bildirim Matrisi başarıyla güncellendi.');
    }

    public function updateTemplate(Request $request, NotificationEventM $event)
    {
        $request->validate([
            'subject' => 'required|string|max:255',
            'body' => 'required|string',
            'in_app_subject' => 'nullable|string|max:255',
            'in_app_body' => 'nullable|string',
            'additional_emails' => 'nullable|string',
            'role_templates' => 'nullable|array'
        ]);

        $roleTemplates = $request->input('role_templates', []);
        $cleanedRoleTemplates = [];
        
        if (is_array($roleTemplates)) {
            foreach ($roleTemplates as $targetKey => $data) {
                $isCustom = !empty($data['is_custom']) && ($data['is_custom'] == '1' || $data['is_custom'] === true || $data['is_custom'] === 'true');
                if ($isCustom) {
                    $cleanedRoleTemplates[$targetKey] = [
                        'is_custom' => true,
                        'subject' => $data['subject'] ?? null,
                        'body' => $data['body'] ?? null,
                        'in_app_subject' => $data['in_app_subject'] ?? null,
                        'in_app_body' => $data['in_app_body'] ?? null,
                    ];
                }
            }
        }

        NotificationTemplateM::updateOrCreate(
            ['notification_event_id' => $event->id],
            [
                'subject' => $request->subject,
                'body' => $request->body,
                'in_app_subject' => $request->in_app_subject,
                'in_app_body' => $request->in_app_body,
                'additional_emails' => $request->additional_emails,
                'role_templates' => !empty($cleanedRoleTemplates) ? $cleanedRoleTemplates : null,
            ]
        );

        return redirect()->route('settings.notifications.index')->with('success', 'Zil ve Mail bildirim şablonları başarıyla güncellendi.');
    }
}
