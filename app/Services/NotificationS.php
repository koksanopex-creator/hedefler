<?php

namespace App\Services;

use App\Models\NotificationEventM;
use App\Models\UserM;
use App\Models\RoleM;
use App\Models\GroupManagerM;
use App\Notifications\DynamicNotification;
use Illuminate\Support\Facades\Log;

class NotificationS
{
    /**
     * Dispatch notifications based on the dynamic matrix and templates.
     * 
     * @param string $eventSlug The slug of the event (e.g., 'form.submitted')
     * @param array $variables Key-value pairs to replace in templates
     * @param UserM|null $contextUser The user performing the action (used to resolve dynamic targets like 'manager')
     * @param string|null $actionUrl Optional URL to include in the email
     */
    public static function dispatch(string $eventSlug, array $variables = [], ?UserM $contextUser = null, ?string $actionUrl = null)
    {
        try {
            $event = NotificationEventM::with(['matrix', 'template'])->where('slug', $eventSlug)->first();
            
            if (!$event || !$event->template || $event->matrix->isEmpty()) {
                return; // No configuration for this event
            }

            // Temel sistem değişkenlerini otomatik doldur
            $variables = self::prepareVariables($variables, $contextUser, $actionUrl);

            // Kullanıcı bildirimlerini topla (çift bildirim engelleme ve rol şablonu önceliği)
            $usersQueue = [];

            foreach ($event->matrix as $rule) {
                if (!$rule->send_email && !$rule->send_in_app) {
                    continue;
                }

                $targetUsers = self::resolveTargetUsers($rule->target_type, $contextUser, $variables);
                $targetTemplate = $event->template->getResolvedTemplate($rule->target_type);

                foreach ($targetUsers as $user) {
                    $userId = $user->id;

                    if (!isset($usersQueue[$userId])) {
                        $usersQueue[$userId] = [
                            'user' => $user,
                            'send_email' => (bool)$rule->send_email,
                            'send_in_app' => (bool)$rule->send_in_app,
                            'target_type' => $rule->target_type,
                            'template' => $targetTemplate,
                        ];
                    } else {
                        $usersQueue[$userId]['send_email'] = $usersQueue[$userId]['send_email'] || (bool)$rule->send_email;
                        $usersQueue[$userId]['send_in_app'] = $usersQueue[$userId]['send_in_app'] || (bool)$rule->send_in_app;
                        
                        // Eğer mevcut şablon varsayılanken gelen şablon role özel ise özeli tercih et
                        if (isset($event->template->role_templates[$rule->target_type]['is_custom']) &&
                            $event->template->role_templates[$rule->target_type]['is_custom']) {
                            $usersQueue[$userId]['template'] = $targetTemplate;
                            $usersQueue[$userId]['target_type'] = $rule->target_type;
                        }
                    }
                }
            }

            // Bildirimleri Gönder
            foreach ($usersQueue as $item) {
                $user = $item['user'];
                $tpl = $item['template'];
                $userVars = array_merge($variables, [
                    'alici_adi' => $user->name,
                    'recipient_name' => $user->name,
                ]);

                if (!$item['send_email'] && !$item['send_in_app']) continue;

                $subject = self::replaceVariables($tpl['subject'], $userVars, false);
                $body = self::replaceVariables($tpl['body'], $userVars, true);
                $inAppSubject = self::replaceVariables($tpl['in_app_subject'] ?: $tpl['subject'], $userVars, false);
                $inAppBody = self::replaceVariables($tpl['in_app_body'] ?: $tpl['body'], $userVars, true);

                // 1) Zil (In-App / Database) bildirimi doğrudan veritabanına anında yazılsın (kuyruğa takılmasın)
                if ($item['send_in_app']) {
                    $user->notifyNow(new DynamicNotification(
                        $subject,
                        $body,
                        ['database'],
                        $actionUrl,
                        $inAppSubject,
                        $inAppBody
                    ));
                }

                // 2) E-posta gönderimi arka planda asenkron kuyruk üzerinden yürütülsün
                if ($item['send_email']) {
                    $user->notify(new DynamicNotification(
                        $subject,
                        $body,
                        ['mail'],
                        $actionUrl,
                        $inAppSubject,
                        $inAppBody
                    ));
                }

                $userRoleName = $user->role?->name ?? (is_numeric($item['target_type']) ? (\App\Models\RoleM::find($item['target_type'])?->name ?? 'Kullanıcı') : ($item['target_type'] === 'manager' ? 'Grup Yöneticisi' : 'Kullanıcı'));

                // 1) Zil (In-App) Bildirim Logu
                if ($item['send_in_app']) {
                    \App\Models\NotificationLogM::create([
                        'channel' => 'in_app',
                        'event_slug' => $eventSlug,
                        'event_name' => $event->name,
                        'user_id' => $user->id,
                        'recipient_name' => $user->name,
                        'recipient_email' => $user->email,
                        'recipient_role' => $userRoleName,
                        'subject' => $inAppSubject,
                        'body' => $inAppBody,
                        'action_url' => $actionUrl,
                        'status' => 'sent',
                        'sent_at' => now(),
                        'metadata' => [
                            'target_type' => $item['target_type'],
                            'variables' => $userVars,
                        ],
                    ]);
                }

                // 2) E-Posta (Mail) Bildirim Logu
                if ($item['send_email']) {
                    \App\Models\NotificationLogM::create([
                        'channel' => 'mail',
                        'event_slug' => $eventSlug,
                        'event_name' => $event->name,
                        'user_id' => $user->id,
                        'recipient_name' => $user->name,
                        'recipient_email' => $user->email,
                        'recipient_role' => $userRoleName,
                        'subject' => $subject,
                        'body' => $body,
                        'action_url' => $actionUrl,
                        'status' => 'sent',
                        'sent_at' => now(),
                        'metadata' => [
                            'target_type' => $item['target_type'],
                            'variables' => $userVars,
                        ],
                    ]);

                    \App\Models\EmailLogM::create([
                        'user_id' => $user->id,
                        'to_email' => $user->email,
                        'subject' => $subject,
                        'body' => $body,
                    ]);
                }
            }
            
            // Harici ekstra e-postalar (Genel şablonla gönderilir)
            if (!empty($event->template->additional_emails)) {
                $emails = array_filter(array_map('trim', explode(',', $event->template->additional_emails)));
                $generalTpl = $event->template->getResolvedTemplate();
                $subject = self::replaceVariables($generalTpl['subject'], $variables, false);
                $body = self::replaceVariables($generalTpl['body'], $variables, true);

                foreach ($emails as $email) {
                    if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                        \Illuminate\Support\Facades\Notification::route('mail', $email)
                            ->notify(new DynamicNotification($subject, $body, ['mail'], $actionUrl));
                            
                        \App\Models\NotificationLogM::create([
                            'channel' => 'mail',
                            'event_slug' => $eventSlug,
                            'event_name' => $event->name,
                            'user_id' => null,
                            'recipient_name' => 'Harici E-Posta',
                            'recipient_email' => $email,
                            'recipient_role' => 'Harici Alıcı',
                            'subject' => $subject,
                            'body' => $body,
                            'action_url' => $actionUrl,
                            'status' => 'sent',
                            'sent_at' => now(),
                            'metadata' => [
                                'variables' => $variables,
                            ],
                        ]);

                        \App\Models\EmailLogM::create([
                            'user_id' => null, // Harici kullanıcı
                            'to_email' => $email,
                            'subject' => $subject,
                            'body' => $body,
                        ]);
                    }
                }
            }

        } catch (\Exception $e) {
            Log::error('Notification Dispatch Error: ' . $e->getMessage(), [
                'slug' => $eventSlug,
                'variables' => $variables,
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * Uygulama başvuru onay ve ret bildirimlerini gönderir.
     * Alıcı doğrudan başvuran kullanıcıdır (role veya gruba bağlı değildir).
     */
    public static function dispatchApplicationResult(string $eventSlug, string $recipientEmail, string $recipientName, array $variables = [], ?string $actionUrl = null, ?UserM $localUser = null)
    {
        try {
            $event = NotificationEventM::with(['matrix', 'template'])->where('slug', $eventSlug)->first();
            if (!$event || !$event->template) {
                return;
            }

            // Matriste 'applicant' hedefinin mail gönderimi açık mı?
            $rule = $event->matrix->firstWhere('target_type', 'applicant');
            if (!$rule || !$rule->send_email) {
                return;
            }

            $now = now();
            $baseVars = [
                'alici_adi' => $recipientName,
                'kullanici_adi' => $recipientName,
                'email' => $recipientEmail,
                'tarih_saat' => $now->format('d.m.Y H:i'),
                'tarih' => $now->format('d.m.Y'),
                'saat' => $now->format('H:i'),
                'sirket_adi' => 'KÖKSAN',
                'action_url' => $actionUrl ?: url('/'),
                'islem_linki' => '<a href="' . e($actionUrl ?: url('/')) . '" style="display:inline-block; padding:10px 20px; background-color:#4f46e5; color:#ffffff; font-weight:bold; border-radius:8px; text-decoration:none;">Hedefler Uygulamasına Giriş Yap</a>',
            ];
            $allVars = array_merge($baseVars, $variables);

            $template = $event->template;
            $subject = self::replaceVariables($template->subject, $allVars, false);
            $body = self::replaceVariables($template->body, $allVars, true);

            // Bildirimi e-posta olarak gönder (Microsoft Graph kys@koksan.com)
            if ($localUser) {
                $localUser->notify(new DynamicNotification($subject, $body, ['mail'], $actionUrl ?: url('/')));
            } else {
                \Illuminate\Support\Facades\Notification::route('mail', $recipientEmail)
                    ->notify(new DynamicNotification($subject, $body, ['mail'], $actionUrl ?: url('/')));
            }

            // 1) Bildirim Logu (notification_logs)
            \App\Models\NotificationLogM::create([
                'channel' => 'mail',
                'event_slug' => $eventSlug,
                'event_name' => $event->name,
                'user_id' => $localUser?->id,
                'recipient_name' => $recipientName,
                'recipient_email' => $recipientEmail,
                'recipient_role' => $localUser?->role?->name ?? 'Başvuru Sahibi',
                'subject' => $subject,
                'body' => $body,
                'action_url' => $actionUrl ?: url('/'),
                'status' => 'sent',
                'sent_at' => now(),
                'metadata' => [
                    'target_type' => 'applicant',
                    'variables' => $allVars,
                ],
            ]);

            // 2) E-Posta Logu (email_logs)
            \App\Models\EmailLogM::create([
                'user_id' => $localUser?->id,
                'to_email' => $recipientEmail,
                'subject' => $subject,
                'body' => $body,
            ]);

        } catch (\Exception $e) {
            Log::error("Application Notification Dispatch Error ({$eventSlug}): " . $e->getMessage(), [
                'email' => $recipientEmail,
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    /**
     * Standart ve dinamik değişkenleri hazırlar ve eşler.
     */
    public static function prepareVariables(array $variables, ?UserM $contextUser = null, ?string $actionUrl = null): array
    {
        $now = now();
        $defaults = [
            'tarih_saat' => $now->format('d.m.Y H:i'),
            'date_time' => $now->format('d.m.Y H:i'),
            'tarih' => $now->format('d.m.Y'),
            'date' => $now->format('d.m.Y'),
            'saat' => $now->format('H:i'),
            'time' => $now->format('H:i'),
            'sirket_adi' => 'KÖKSAN',
            'company_name' => 'KÖKSAN',
            'parca_bilgisi' => '',
            'partition_info' => '',
            'gonderim_metni' => 'formu gönderdi',
            'submission_text' => 'formu gönderdi',
        ];

        if ($actionUrl) {
            $defaults['action_url'] = $actionUrl;
            $defaults['islem_linki'] = '<a href="' . e($actionUrl) . '" style="display:inline-block; padding:8px 16px; background-color:#4f46e5; color:#ffffff; font-weight:bold; border-radius:6px; text-decoration:none;">Sisteme Git / Görüntüle</a>';
        }

        if ($contextUser) {
            $defaults['kullanici_adi'] = $contextUser->name;
            $defaults['user_name'] = $contextUser->name;
            $defaults['islem_yapan'] = $contextUser->name;
            if ($contextUser->group) {
                $defaults['grup_adi'] = $contextUser->group->name;
                $defaults['group_name'] = $contextUser->group->name;
            }
        }

        // İki yönlü alias eşitlemesi (user_name <-> kullanici_adi vb.)
        $aliases = [
            'user_name' => 'kullanici_adi',
            'form_name' => 'form_adi',
            'rejection_reason' => 'red_nedeni',
            'action_url' => 'islem_linki',
            'approver_name' => 'onaylayan_kullanici',
            'entry_id' => 'kayit_id',
            'group_name' => 'grup_adi',
            'company_name' => 'sirket_adi',
            'date_time' => 'tarih_saat',
            'recipient_name' => 'alici_adi',
            'target_title' => 'hedef_basligi',
            'target_value' => 'hedef_degeri',
            'actual_value' => 'gerceklesen_deger',
            'achievement_rate' => 'basari_orani',
            'target_year' => 'hedef_yili',
            'unit' => 'birim',
            'template_name' => 'sablon_adi',
            'target_field' => 'hedef_alani',
            'criteria_summary' => 'kriter_ozeti',
            'assigned_by' => 'atayan_kisi',
            'parca_bilgisi' => 'partition_info',
            'gonderim_metni' => 'submission_text',
        ];

        foreach ($aliases as $en => $tr) {
            if (isset($variables[$en]) && !isset($variables[$tr])) {
                $variables[$tr] = $variables[$en];
            } elseif (isset($variables[$tr]) && !isset($variables[$en])) {
                $variables[$en] = $variables[$tr];
            }
        }

        return array_merge($defaults, $variables);
    }

    /**
     * Metin içindeki değişkenleri ({kullanici_adi}, {form_adi} vb.) değiştirir.
     * Kural 17 gereği HTML içeriğinde dinamik değişkenler otomatik kalınlaştırılır.
     */
    public static function replaceVariables(string $text, array $variables, bool $isHtml = true): string
    {
        foreach ($variables as $key => $value) {
            if ($value === null) $value = '';

            $valString = (string)$value;
            
            // Eğer HTML içerikse ve değer saf metinse, dinamik alan kuralı (Madde 17) gereği kalınlaştır
            if ($isHtml && !empty($valString) && !str_starts_with($valString, '<a') && !str_starts_with($valString, '<strong')) {
                // Konu başlığı değil ve URL değilse kalınlaştır
                if (!filter_var($valString, FILTER_VALIDATE_URL) && !in_array($key, ['action_url', 'islem_linki'])) {
                    $replacement = '<strong>' . e($valString) . '</strong>';
                } else {
                    $replacement = $valString;
                }
            } else {
                $replacement = $valString;
            }

            $text = str_replace('{' . $key . '}', $replacement, $text);
        }

        return $text;
    }

    /**
     * @return \Illuminate\Support\Collection<UserM>
     */
    private static function resolveTargetUsers(string $targetType, ?UserM $contextUser, array $variables = [])
    {
        if (str_starts_with($targetType, 'role:')) {
            $roleSlug = str_replace('role:', '', $targetType);
            $role = RoleM::where('slug', $roleSlug)->first();
            if ($role) {
                return $role->users;
            }
        } elseif (str_starts_with($targetType, 'special:')) {
            $specialType = str_replace('special:', '', $targetType);
            
            if ($specialType === 'group_manager') {
                $targetGroupId = $contextUser?->group_id ?? ($variables['group_id'] ?? null);
                if ($targetGroupId) {
                    $managerIds = \Illuminate\Support\Facades\DB::table('group_manager')->where('group_id', $targetGroupId)->pluck('user_id');
                    return UserM::whereIn('id', $managerIds)->get();
                }
            }
            
            if ($specialType === 'creator' && $contextUser) {
                return collect([$contextUser]);
            }

            if ($specialType === 'target_assignee') {
                if (!empty($variables['target_user']) && $variables['target_user'] instanceof UserM) {
                    return collect([$variables['target_user']]);
                }
                if (!empty($variables['target_users']) && is_iterable($variables['target_users'])) {
                    return collect($variables['target_users']);
                }
                if ($contextUser) {
                    return collect([$contextUser]);
                }
            }
        }

        return collect();
    }
}
