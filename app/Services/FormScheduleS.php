<?php

namespace App\Services;

use App\Models\FormTemplateM;
use App\Models\UserM;
use App\Models\FormScheduleM;
use Illuminate\Support\Carbon;

class FormScheduleS
{
    /**
     * Kullanıcının belirtilen şablona, belirtilen tarihte erişim hakkı olup olmadığını kontrol eder.
     */
    public function canUserAccessTemplate(UserM $user, FormTemplateM $template, Carbon $checkDate = null): bool
    {
        if (!$checkDate) {
            $checkDate = now();
        }

        // Şablonun tüm kurallarını getir (öncelik sırası: Kilitli olanlar ezer, Kişi > Grup > Herkes)
        // Ancak basit bir mantık: Önce bu kullanıcıyı bağlayan kilitli (is_locked) bir kural var mı?
        // Varsa direkt reddet.
        // Yoksa, bu kullanıcıyı bağlayan açık bir kural var mı? Varsa kabul et.
        
        $schedules = FormScheduleM::where('form_template_id', $template->id)->get();
        
        if ($schedules->isEmpty()) {
            return false; // Hiç kural yoksa varsayılan olarak kapalıdır
        }

        $applicableSchedules = [];

        foreach ($schedules as $schedule) {
            // Bu kural kullanıcıyı kapsıyor mu?
            $isMatch = false;
            if ($schedule->target_type === 'all') {
                $isMatch = true;
            } elseif ($schedule->target_type === 'user' && $schedule->target_id === $user->id) {
                $isMatch = true;
            } elseif ($schedule->target_type === 'group' && $schedule->target_id === $user->group_id) {
                $isMatch = true;
            }

            if ($isMatch) {
                // Zaman kısıtlamasına uyuyor mu?
                if ($schedule->is_indefinite) {
                    $applicableSchedules[] = $schedule;
                } else {
                    if ($checkDate->between($schedule->start_time, $schedule->end_time)) {
                        $applicableSchedules[] = $schedule;
                    }
                }
            }
        }

        if (empty($applicableSchedules)) {
            return false;
        }

        // Kapsayan kurallar içinde "kilitli (is_locked = true)" olan var mı?
        // Kilit her zaman açmayı ezer (Deny overrides Allow)
        foreach ($applicableSchedules as $schedule) {
            if ($schedule->is_locked) {
                return false;
            }
        }

        // Kilit yoksa ve en az bir tane geçerli izin kuralı varsa erişebilir
        return true;
    }

    /**
     * Kullanıcının erişebildiği tüm aktif şablonların ID'lerini döndürür
     */
    public function getAccessibleTemplateIds(UserM $user): array
    {
        $activeTemplates = FormTemplateM::where('is_active', true)->get();
        $accessibleIds = [];

        foreach ($activeTemplates as $template) {
            if ($this->canUserAccessTemplate($user, $template)) {
                $accessibleIds[] = $template->id;
            }
        }

        return $accessibleIds;
    }
}
