<?php

use App\Http\Controllers\ProfileC;
use Illuminate\Support\Facades\Route;

Route::any('/', function () {
    return redirect('dashboard');
});

Route::any('/hedefler', function () {
    return redirect('dashboard');
});

Route::get('/dashboard', [App\Http\Controllers\DashboardC::class, 'index'])
    ->middleware(['auth', 'verified'])->name('dashboard');

use App\Http\Controllers\FormTemplateC;
use App\Http\Controllers\FormFieldC;

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileC::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileC::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileC::class, 'destroy'])->name('profile.destroy');
    Route::get('/notifications', [\App\Http\Controllers\NotificationC::class, 'index'])->name('notifications.index');
    Route::post('/notifications/{id}/mark-read', [\App\Http\Controllers\NotificationC::class, 'markAsRead'])->name('notifications.mark-read');
    Route::post('/notifications/mark-all-read', [\App\Http\Controllers\NotificationC::class, 'markAllAsRead'])->name('notifications.mark-all-read');
    Route::get('/kullanici-profil/{user}', [\App\Http\Controllers\UserProfileC::class, 'show'])->name('users.profile');
});

use App\Http\Controllers\UserC;
use App\Http\Controllers\GroupC;
use App\Http\Controllers\RoleC;
use App\Http\Controllers\FormScheduleC;
use App\Http\Controllers\SettingsC;

// Sistem Yöneticisi için Dinamik Form Tasarım Rotaları
Route::middleware(['auth', 'permission:form_templates.manage'])->group(function () {
    Route::resource('form-templates', FormTemplateC::class)->parameters(['form-templates' => 'formTemplate']);
    Route::get('form-templates/{formTemplate}/fields', [FormFieldC::class, 'edit'])->name('form_fields.edit');
    Route::post('form-templates/{formTemplate}/fields', [FormFieldC::class, 'update'])->name('form_fields.update');
    
    // Takvim İşlemleri
    Route::get('form-templates/{formTemplate}/schedules', [FormScheduleC::class, 'index'])->name('form-schedules.index');
    Route::post('form-templates/{formTemplate}/schedules', [FormScheduleC::class, 'store'])->name('form-schedules.store');
    Route::delete('form-schedules/{formSchedule}', [FormScheduleC::class, 'destroy'])->name('form-schedules.destroy');
});

// Yetki Matrisi, Kullanıcı ve Grup Yönetim Rotaları
Route::middleware(['auth'])->group(function () {
    // Merkezi Senkronizasyon ve Başvuru Yönetimi
    Route::middleware('permission:users.manage')->group(function () {
        Route::get('users/sync', [\App\Http\Controllers\UserSyncC::class, 'index'])->name('users.sync');
        Route::post('users/sync/all', [\App\Http\Controllers\UserSyncC::class, 'syncAll'])->name('users.sync.all');
        Route::post('users/sync/{centralId}/assign-role', [\App\Http\Controllers\UserSyncC::class, 'assignRole'])->name('users.sync.assign_role');

        Route::get('users/applications', [\App\Http\Controllers\SsoApplicationC::class, 'index'])->name('users.applications.index');
        Route::post('users/applications/toggle', [\App\Http\Controllers\SsoApplicationC::class, 'toggleApplications'])->name('users.applications.toggle');
        Route::post('users/applications/{application}/approve', [\App\Http\Controllers\SsoApplicationC::class, 'approve'])->name('users.applications.approve');
        Route::post('users/applications/{application}/reject', [\App\Http\Controllers\SsoApplicationC::class, 'reject'])->name('users.applications.reject');
        Route::delete('users/applications/{application}/rejection', [\App\Http\Controllers\SsoApplicationC::class, 'destroyRejection'])->name('users.applications.destroy_rejection');
    });

    Route::get('users', [UserC::class, 'index'])->name('users.index')->middleware('permission:users.view_all,users.view_group,users.view,users.manage');
    Route::middleware('permission:users.manage')->group(function () {
        Route::resource('users', UserC::class)->except(['index', 'show']);
    });
    
    Route::middleware('permission:groups.manage')->group(function () {
        Route::resource('groups', GroupC::class)->except(['show', 'destroy']);
    });
    
    // Master Data
    Route::middleware('permission:master_data.manage')->group(function () {
        Route::get('master-data', [\App\Http\Controllers\MasterDataC::class, 'index'])->name('master_data.index');
        Route::post('master-data/types', [\App\Http\Controllers\MasterDataC::class, 'storeType'])->name('master_data.types.store');
        Route::put('master-data/types/{type}', [\App\Http\Controllers\MasterDataC::class, 'updateType'])->name('master_data.types.update');
        Route::delete('master-data/types/{type}', [\App\Http\Controllers\MasterDataC::class, 'destroyType'])->name('master_data.types.destroy');
        Route::post('master-data/items', [\App\Http\Controllers\MasterDataC::class, 'storeItem'])->name('master_data.items.store');
        Route::put('master-data/items/{item}', [\App\Http\Controllers\MasterDataC::class, 'updateItem'])->name('master_data.items.update');
        Route::delete('master-data/items/bulk', [\App\Http\Controllers\MasterDataC::class, 'destroyMultipleItems'])->name('master_data.items.bulk_destroy');
        Route::delete('master-data/items/{item}', [\App\Http\Controllers\MasterDataC::class, 'destroyItem'])->name('master_data.items.destroy');
        Route::post('master-data/batches/{batch}/undo', [\App\Http\Controllers\MasterDataC::class, 'undoBatch'])->name('master_data.batches.undo');
    });

    // API route for cascading dropdowns (herkes erişebilir veya en azından form görenler)
    Route::get('api/master-data/{type}/items', [\App\Http\Controllers\MasterDataC::class, 'getItems'])->name('api.master_data.items');

    Route::middleware('permission:roles.manage')->group(function () {
        Route::get('roles/matrix', [RoleC::class, 'matrix'])->name('roles.matrix')->middleware('permission:roles.manage');
        Route::post('roles/matrix', [RoleC::class, 'updateMatrix'])->name('roles.matrix.update')->middleware('permission:roles.manage');
        Route::post('roles', [RoleC::class, 'store'])->name('roles.store')->middleware('permission:roles.manage');
        Route::delete('roles/{role}', [RoleC::class, 'destroy'])->name('roles.destroy')->middleware('permission:roles.manage');

        // Sistem Ayarları Rotası (Logo, Genel Ayarlar vb.)
        Route::get('settings', [SettingsC::class, 'index'])->name('settings.index')->middleware('permission:settings.manage');
        Route::post('settings/general', [SettingsC::class, 'updateGeneral'])->name('settings.general.update')->middleware('permission:settings.manage');
        Route::post('settings/logo', [SettingsC::class, 'uploadLogo'])->name('settings.logo.upload')->middleware('permission:settings.manage');
        Route::delete('settings/logo', [SettingsC::class, 'removeLogo'])->name('settings.logo.remove')->middleware('permission:settings.manage');
        
        // Bildirim Matrisi Ayarları
        Route::get('settings/notifications', [\App\Http\Controllers\NotificationSettingsC::class, 'index'])->name('settings.notifications.index')->middleware('permission:settings.manage');
        Route::post('settings/notifications/matrix', [\App\Http\Controllers\NotificationSettingsC::class, 'updateMatrix'])->name('settings.notifications.matrix.update')->middleware('permission:settings.manage');
        Route::post('settings/notifications/templates/{event}', [\App\Http\Controllers\NotificationSettingsC::class, 'updateTemplate'])->name('settings.notifications.templates.update')->middleware('permission:settings.manage');

        // Bildirim ve E-Posta Gönderim Geçmişi (Loglar)
        Route::get('settings/notification-logs', [\App\Http\Controllers\NotificationLogC::class, 'index'])->name('settings.notification_logs.index')->middleware('permission:settings.manage');
        Route::post('settings/notification-logs/clear', [\App\Http\Controllers\NotificationLogC::class, 'clearLogs'])->name('settings.notification_logs.clear')->middleware('permission:settings.manage');
        Route::get('settings/email-logs', [\App\Http\Controllers\NotificationLogC::class, 'index'])->name('settings.email_logs.index')->middleware('permission:settings.manage');

        // Sistem Günlüğü & Denetim İzi (Audit Logs)
        Route::get('settings/activity-logs', [\App\Http\Controllers\ActivityLogC::class, 'index'])->name('settings.activity_logs.index')->middleware('permission:settings.manage,logs.view');
        Route::post('settings/activity-logs/clear', [\App\Http\Controllers\ActivityLogC::class, 'clearLogs'])->name('settings.activity_logs.clear')->middleware('permission:settings.manage');
        Route::get('settings/activity-logs/archive', [\App\Http\Controllers\ActivityLogC::class, 'archiveLogs'])->name('settings.activity_logs.archive')->middleware('permission:settings.manage,logs.view');

        // Aktif Oturumlar ve Giriş İzleme (Yetki Matrisi kontrollü: system.active_sessions)
        Route::get('system/active-sessions', [\App\Http\Controllers\SessionMonitorC::class, 'index'])->name('system.active_sessions')->middleware('permission:system.active_sessions');
        Route::delete('system/active-sessions/{session}', [\App\Http\Controllers\SessionMonitorC::class, 'destroy'])->name('system.active_sessions.destroy')->middleware('permission:system.active_sessions');
    });
});

use App\Http\Controllers\FormEntryC;

// Form Doldurma ve Görüntüleme Rotaları (Genel Kullanım)
Route::middleware(['auth'])->group(function () {
    Route::get('form-entries/select-template', [FormEntryC::class, 'selectTemplate'])->name('form-entries.select-template');
    Route::get('form-entries/create/{formTemplate}', [FormEntryC::class, 'create'])->name('form-entries.create');
    Route::get('form-templates/{formTemplate}/download-empty', [FormEntryC::class, 'downloadEmptyTemplate'])->name('form-templates.download-empty');
    Route::post('form-templates/{formTemplate}/import-excel', [FormEntryC::class, 'importExcel'])->name('form-templates.import-excel');
    Route::post('form-entries/store/{formTemplate}', [FormEntryC::class, 'store'])->name('form-entries.store');
    Route::post('form-entries/{formEntry}/review', [FormEntryC::class, 'review'])->name('form-entries.review');
    Route::post('form-entries/{formEntry}/revert-review', [FormEntryC::class, 'revertReview'])->name('form-entries.revert-review');
    Route::post('form-entries/{formEntry}/request-revision', [FormEntryC::class, 'requestRevision'])->name('form-entries.request-revision');
    Route::post('form-entries/revision-requests/{revisionRequest}/review', [FormEntryC::class, 'reviewRevision'])->name('form-entries.revision-requests.review');
    Route::post('form-entries/revision-requests/{revisionRequest}/dismiss', [FormEntryC::class, 'dismissRevisionRejection'])->name('form-entries.revision-requests.dismiss');
    Route::post('form-entries/{formEntry}/submit-approval', [FormEntryC::class, 'submitApproval'])->name('form-entries.submit-approval');
    Route::post('form-entries/{formEntry}/withdraw', [FormEntryC::class, 'withdraw'])->name('form-entries.withdraw');
    Route::get('form-entries/{formEntry}/export', [FormEntryC::class, 'export'])->name('form-entries.export');
    Route::post('user-preferences/pinned-columns', [FormEntryC::class, 'savePinnedColumns'])->name('user-preferences.pinned-columns');
    Route::resource('form-entries', FormEntryC::class)->only(['index', 'show', 'edit', 'update', 'destroy'])->parameters(['form-entries' => 'formEntry']);
});

use App\Http\Controllers\KpiTargetC;
use App\Http\Controllers\MyTargetsC;
use App\Http\Controllers\ReportC;

// Dinamik BI ve Raporlama Motoru Rotaları
Route::middleware(['auth'])->prefix('reports')->name('reports.')->group(function () {
    Route::get('/', [ReportC::class, 'index'])->name('index');
    Route::get('/template-meta/{template}', [ReportC::class, 'templateMeta'])->name('template-meta');
    Route::post('/query', [ReportC::class, 'query'])->name('query');
    Route::post('/rules', [ReportC::class, 'storeRule'])->name('rules.store');
    Route::put('/rules/{rule}', [ReportC::class, 'updateRule'])->name('rules.update');
    Route::delete('/rules/{rule}', [ReportC::class, 'destroyRule'])->name('rules.destroy');
    Route::get('/export/excel', [ReportC::class, 'exportExcel'])->name('export.excel');
    Route::get('/print', [ReportC::class, 'print'])->name('print');
    Route::post('/share', [ReportC::class, 'share'])->name('share');
    Route::post('/schedules', [ReportC::class, 'storeSchedule'])->name('schedules.store');
    Route::post('/schedules/{schedule}/toggle', [ReportC::class, 'toggleSchedule'])->name('schedules.toggle');
    Route::delete('/schedules/{schedule}', [ReportC::class, 'destroySchedule'])->name('schedules.destroy');
});

// KPI Hedef Yönetimi ve Performans Rotaları
Route::middleware(['auth'])->group(function () {
    // Personelin Kendi Hedefleri & Performans Karnesi (Tüm atanmış personeller)
    Route::get('/hedeflerim', [MyTargetsC::class, 'index'])->name('my-targets.index');

    Route::prefix('kpi-targets')->name('kpi-targets.')->group(function () {
        Route::get('/', [KpiTargetC::class, 'index'])->name('index');
        Route::get('/create', [KpiTargetC::class, 'create'])->name('create');
        Route::post('/', [KpiTargetC::class, 'store'])->name('store');
        Route::get('/{kpiTarget}/edit', [KpiTargetC::class, 'edit'])->name('edit')->missing(fn () => redirect()->route('kpi-targets.index')->with('warning', 'İlgili KPI hedefi bulunamadı veya sistemden silinmiş.'));
        Route::put('/{kpiTarget}', [KpiTargetC::class, 'update'])->name('update');
        Route::delete('/{kpiTarget}', [KpiTargetC::class, 'destroy'])->name('destroy');

        Route::get('/{kpiTarget}/distribute', [KpiTargetC::class, 'distributeForm'])->name('distribute')->missing(fn () => redirect()->route('kpi-targets.index')->with('warning', 'İlgili KPI hedefi bulunamadı veya sistemden silinmiş.'));
        Route::post('/{kpiTarget}/distribute', [KpiTargetC::class, 'distributeStore'])->name('distribute.store');
        Route::post('/{kpiTarget}/distribute-evenly', [KpiTargetC::class, 'distributeEvenly'])->name('distribute.evenly');

        Route::get('/{kpiTarget}/progress', [KpiTargetC::class, 'progress'])->name('progress')->missing(fn () => redirect()->route('my-targets.index')->with('warning', 'İlgili KPI hedefi bulunamadı veya sistemden silinmiş.'));
        Route::post('/{kpiTarget}/recalculate', [KpiTargetC::class, 'recalculate'])->name('recalculate');

        // AJAX Yardımcıları
        Route::get('/api/template-fields/{template}', [KpiTargetC::class, 'getFields'])->name('api.fields');
        Route::get('/api/filter-options/{field}', [KpiTargetC::class, 'getFilterOptions'])->name('api.filter-options');
    });
});

require __DIR__.'/auth.php';
