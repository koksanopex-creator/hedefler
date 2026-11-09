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
            // Form Onay & Süreç İşlemleri
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
            'form_entry.approved' => [
                'label' => 'Form Onaylandı',
                'badge' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
            ],
            'form_entry.rejected' => [
                'label' => 'Form Reddedildi',
                'badge' => 'bg-rose-50 text-rose-700 border-rose-200',
            ],
            'form_entry.revert' => [
                'label' => 'Karar Geri Alındı',
                'badge' => 'bg-amber-50 text-amber-700 border-amber-200',
            ],
            'form_entry.withdrawn' => [
                'label' => 'Onaydan Geri Çekildi',
                'badge' => 'bg-amber-50 text-amber-700 border-amber-200',
            ],
            'form_entry.draft_deleted' => [
                'label' => 'Taslak Form Silindi',
                'badge' => 'bg-rose-50 text-rose-700 border-rose-200',
            ],
            'form_entry.revision_requested' => [
                'label' => 'Revizyon Talep Edildi',
                'badge' => 'bg-amber-50 text-amber-800 border-amber-200',
            ],
            'form_entry.revision_approved' => [
                'label' => 'Revizyon Talebi Onaylandı',
                'badge' => 'bg-teal-50 text-teal-700 border-teal-200',
            ],
            'form_entry.revision_rejected' => [
                'label' => 'Revizyon Talebi Reddedildi',
                'badge' => 'bg-rose-50 text-rose-700 border-rose-200',
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

            // Kullanıcı & Başvuru İşlemleri
            'user.create' => [
                'label' => 'Kullanıcı Oluşturuldu',
                'badge' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
            ],
            'user.created' => [
                'label' => 'Kullanıcı Oluşturuldu',
                'badge' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
            ],
            'user.update' => [
                'label' => 'Kullanıcı Güncellendi',
                'badge' => 'bg-indigo-50 text-indigo-700 border-indigo-200',
            ],
            'user.updated' => [
                'label' => 'Kullanıcı Güncellendi',
                'badge' => 'bg-indigo-50 text-indigo-700 border-indigo-200',
            ],
            'user.delete' => [
                'label' => 'Kullanıcı Silindi',
                'badge' => 'bg-rose-50 text-rose-700 border-rose-200',
            ],
            'user.deleted' => [
                'label' => 'Kullanıcı Silindi',
                'badge' => 'bg-rose-50 text-rose-700 border-rose-200',
            ],
            'user.sync' => [
                'label' => 'Merkez Senkronizasyonu',
                'badge' => 'bg-purple-50 text-purple-700 border-purple-200',
            ],
            'application.approved' => [
                'label' => 'Başvuru Onaylandı',
                'badge' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
            ],
            'application.rejected' => [
                'label' => 'Başvuru Reddedildi',
                'badge' => 'bg-rose-50 text-rose-700 border-rose-200',
            ],

            // Grup İşlemleri
            'group.create' => [
                'label' => 'Grup Oluşturuldu',
                'badge' => 'bg-indigo-50 text-indigo-700 border-indigo-200',
            ],
            'group.update' => [
                'label' => 'Grup Güncellendi',
                'badge' => 'bg-blue-50 text-blue-700 border-blue-200',
            ],
            'group.delete' => [
                'label' => 'Grup Silindi',
                'badge' => 'bg-rose-50 text-rose-700 border-rose-200',
            ],

            // Rol & Yetki Matrisi
            'role.update' => [
                'label' => 'Yetki Matrisi Güncellendi',
                'badge' => 'bg-purple-50 text-purple-700 border-purple-200',
            ],
            'roles.matrix.update' => [
                'label' => 'Yetki Matrisi Güncellendi',
                'badge' => 'bg-purple-50 text-purple-700 border-purple-200',
            ],

            // Ayarlar & Sistem
            'settings.general' => [
                'label' => 'Sistem Ayarları Güncellendi',
                'badge' => 'bg-indigo-50 text-indigo-700 border-indigo-200',
            ],
            'settings.logo' => [
                'label' => 'Sistem Logosu Güncellendi',
                'badge' => 'bg-indigo-50 text-indigo-700 border-indigo-200',
            ],
            'activity_log.clear' => [
                'label' => 'Loglar Temizlendi',
                'badge' => 'bg-rose-50 text-rose-700 border-rose-200',
            ],
            'activity_log.export' => [
                'label' => 'Loglar Arşivlendi',
                'badge' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
            ],
            'notification_log.clear' => [
                'label' => 'Bildirim Logları Temizlendi',
                'badge' => 'bg-amber-50 text-amber-800 border-amber-200',
            ],
            'notification.clear' => [
                'label' => 'Bildirim Logları Temizlendi',
                'badge' => 'bg-amber-50 text-amber-800 border-amber-200',
            ],
            'session.terminate' => [
                'label' => 'Oturum Sonlandırıldı',
                'badge' => 'bg-rose-50 text-rose-700 border-rose-200',
            ],
            'login' => [
                'label' => 'Giriş Yapıldı',
                'badge' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
            ],
            'auth.login' => [
                'label' => 'Giriş Yapıldı',
                'badge' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
            ],
            'logout' => [
                'label' => 'Çıkış Yapıldı',
                'badge' => 'bg-slate-50 text-slate-600 border-slate-200',
            ],
            'auth.logout' => [
                'label' => 'Çıkış Yapıldı',
                'badge' => 'bg-slate-50 text-slate-600 border-slate-200',
            ],

            // KPI Hedefleri
            'kpi.target.created' => [
                'label' => 'KPI Hedefi Tanımlandı',
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

        // Bilinmeyen slug kodları için akıllı Türkçe formatlama
        $clean = str_replace(['_', '.'], ' ', $action);
        $clean = str_ireplace(
            ['form entry', 'revision requested', 'revision approved', 'revision rejected', 'created', 'updated', 'deleted', 'approved', 'rejected'],
            ['Form Girişi', 'Revizyon Talep Edildi', 'Revizyon Onaylandı', 'Revizyon Reddedildi', 'Oluşturuldu', 'Güncellendi', 'Silindi', 'Onaylandı', 'Reddedildi'],
            $clean
        );
        return [
            'label' => mb_convert_case($clean, MB_CASE_TITLE, 'UTF-8'),
            'badge' => 'bg-slate-50 text-slate-700 border-slate-200',
        ];
    }
}
