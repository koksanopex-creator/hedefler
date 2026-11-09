<?php

namespace App\Services;

use App\Models\ActivityLogM;
use Illuminate\Support\Facades\Auth;

class ActivityLogS
{
    public function log(string $action, string $description = null, $model = null): void
    {
        ActivityLogM::create([
            'user_id' => Auth::id(),
            'action' => $action,
            'description' => $description,
            'model_type' => $model ? (is_object($model) ? get_class($model) : (string)$model) : null,
            'model_id' => $model ? (is_object($model) ? ($model->id ?? null) : null) : null,
            'ip_address' => request()->ip(),
        ]);
    }

    public static function logAction(string $action, string $description = null, $model = null, $modelId = null): void
    {
        ActivityLogM::create([
            'user_id' => Auth::id(),
            'action' => $action,
            'description' => $description,
            'model_type' => $model ? (is_object($model) ? get_class($model) : (string)$model) : null,
            'model_id' => $modelId ?? ($model ? (is_object($model) ? ($model->id ?? null) : null) : null),
            'ip_address' => request()->ip(),
        ]);
    }

    /**
     * İşlem (Action) slug kodlarını standart Türkçe etiket ve rozet sınıfına dönüştürür.
     */
    public static function getActionMeta(string $action): array
    {
        $map = [
            'form_entry.create' => [
                'label' => 'Form Oluşturuldu',
                'badge' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
            ],
            'form_entry.draft' => [
                'label' => 'Taslak Kaydedildi',
                'badge' => 'bg-amber-50 text-amber-700 border-amber-200',
            ],
            'form_entry.submit' => [
                'label' => 'Onaya Gönderildi',
                'badge' => 'bg-blue-50 text-blue-700 border-blue-200',
            ],
            'form_entry.update' => [
                'label' => 'Form Güncellendi',
                'badge' => 'bg-indigo-50 text-indigo-700 border-indigo-200',
            ],
            'form_entry.review' => [
                'label' => 'Durum Değiştirildi',
                'badge' => 'bg-purple-50 text-purple-700 border-purple-200',
            ],
            'form_entry.view' => [
                'label' => 'Form Görüntülendi',
                'badge' => 'bg-slate-50 text-slate-600 border-slate-200',
            ],
            'form_entry.delete' => [
                'label' => 'Form Silindi',
                'badge' => 'bg-rose-50 text-rose-700 border-rose-200',
            ],
            'form_entry.export' => [
                'label' => 'Excel Olarak İndirildi',
                'badge' => 'bg-teal-50 text-teal-700 border-teal-200',
            ],
            'approved' => [
                'label' => 'Onaylandı',
                'badge' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
            ],
            'rejected' => [
                'label' => 'Reddedildi',
                'badge' => 'bg-rose-50 text-rose-700 border-rose-200',
            ],
            'draft' => [
                'label' => 'Taslak',
                'badge' => 'bg-amber-50 text-amber-700 border-amber-200',
            ],
            'submitted' => [
                'label' => 'Onay Bekliyor',
                'badge' => 'bg-blue-50 text-blue-700 border-blue-200',
            ],
            'settings.logo' => [
                'label' => 'Sistem Logosu Güncellendi',
                'badge' => 'bg-indigo-50 text-indigo-700 border-indigo-200',
            ],
            'notification_log.clear' => [
                'label' => 'Bildirim Logları Temizlendi',
                'badge' => 'bg-rose-50 text-rose-700 border-rose-200',
            ],
            'login' => [
                'label' => 'Giriş Yapıldı',
                'badge' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
            ],
            'logout' => [
                'label' => 'Çıkış Yapıldı',
                'badge' => 'bg-slate-50 text-slate-600 border-slate-200',
            ],
            'kpi.target.created' => [
                'label' => 'Yeni KPI Hedefi Tanımlandı',
                'badge' => 'bg-indigo-50 text-indigo-700 border-indigo-200',
            ],
            'kpi.target.updated' => [
                'label' => 'KPI Hedefi Güncellendi',
                'badge' => 'bg-blue-50 text-blue-700 border-blue-200',
            ],
            'kpi.target.deleted' => [
                'label' => 'KPI Hedefi Silindi',
                'badge' => 'bg-rose-50 text-rose-700 border-rose-200',
            ],
            'kpi.target.assigned' => [
                'label' => 'KPI Hedefi Atandı',
                'badge' => 'bg-purple-50 text-purple-700 border-purple-200',
            ],
            'kpi.target.distributed' => [
                'label' => 'KPI Aylık Dağıtım Yapıldı',
                'badge' => 'bg-teal-50 text-teal-700 border-teal-200',
            ],
        ];

        if (isset($map[$action])) {
            return $map[$action];
        }

        // Bilinmeyen slug kodları için temiz formatlama
        $clean = str_replace(['_', '.'], ' ', $action);
        return [
            'label' => mb_convert_case($clean, MB_CASE_TITLE, 'UTF-8'),
            'badge' => 'bg-slate-50 text-slate-600 border-slate-200',
        ];
    }
}
