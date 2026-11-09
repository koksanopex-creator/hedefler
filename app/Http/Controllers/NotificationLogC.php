<?php

namespace App\Http\Controllers;

use App\Models\NotificationLogM;
use App\Models\NotificationEventM;
use App\Models\EmailLogM;
use App\Models\RoleM;
use App\Services\ActivityLogS;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class NotificationLogC extends Controller
{
    public function index(Request $request)
    {
        $channel = $request->get('channel');
        $eventSlug = $request->get('event_slug');
        $role = $request->get('role');
        $search = $request->get('search');
        $dateFrom = $request->get('date_from');
        $dateTo = $request->get('date_to');
        $viewMode = $request->get('view_mode', 'list');

        // Ana Sorgu
        $query = NotificationLogM::with('user')->orderBy('sent_at', 'desc');

        if ($channel && in_array($channel, ['mail', 'in_app'])) {
            $query->channel($channel);
        }
        if ($eventSlug) {
            $query->eventSlug($eventSlug);
        }
        if ($role) {
            $query->role($role);
        }
        if ($search) {
            $query->search($search);
        }
        if ($dateFrom || $dateTo) {
            $query->dateRange($dateFrom, $dateTo);
        }

        // İstatistikler (Tüm zamanlar veya filtrelere göre)
        $totalCount = NotificationLogM::count();
        $inAppCount = NotificationLogM::where('channel', 'in_app')->count();
        $mailCount = NotificationLogM::where('channel', 'mail')->count();
        $eventsCount = NotificationEventM::count();

        // Filtre Seçenekleri
        $events = NotificationEventM::orderBy('name')->get();
        $distinctRoles = NotificationLogM::whereNotNull('recipient_role')
            ->distinct()
            ->pluck('recipient_role')
            ->toArray();

        if (empty($distinctRoles)) {
            $distinctRoles = RoleM::pluck('name')->toArray();
        }

        // Sayfalama
        $logs = $query->paginate(30)->withQueryString();

        // Gruplanmış görünüm için gruplama
        $groupedLogs = null;
        if ($viewMode === 'grouped') {
            $groupedLogs = $logs->getCollection()->groupBy('event_name');
        }

        return view('settings.notification_logs.index', compact(
            'logs',
            'groupedLogs',
            'channel',
            'eventSlug',
            'role',
            'search',
            'dateFrom',
            'dateTo',
            'viewMode',
            'totalCount',
            'inAppCount',
            'mailCount',
            'eventsCount',
            'events',
            'distinctRoles'
        ));
    }

    /**
     * Geçmiş bildirim ve e-posta loglarını temizler.
     */
    public function clearLogs(Request $request)
    {
        // Madde 19: Yonetim rolü salt okunurdur, silme yapamaz
        if (auth()->check() && auth()->user()->isYonetim()) {
            abort(403, 'Yönetim rolü salt okunur yetkiye sahiptir, kayıt silemez.');
        }

        $period = $request->input('period', 'all');
        $includeInApp = $request->boolean('include_in_app', false);

        $notifQuery = NotificationLogM::query();
        $emailQuery = EmailLogM::query();
        $notificationsQuery = DB::table('notifications');

        $periodText = 'Tüm Zamanlar';

        if ($period === '30_days') {
            $cutoff = now()->subDays(30);
            $notifQuery->where('sent_at', '<', $cutoff);
            $emailQuery->where('created_at', '<', $cutoff);
            $notificationsQuery->where('created_at', '<', $cutoff);
            $periodText = '30 Günden Eski';
        } elseif ($period === '90_days') {
            $cutoff = now()->subDays(90);
            $notifQuery->where('sent_at', '<', $cutoff);
            $emailQuery->where('created_at', '<', $cutoff);
            $notificationsQuery->where('created_at', '<', $cutoff);
            $periodText = '90 Günden Eski';
        }

        $deletedNotif = $notifQuery->delete();
        $deletedEmail = $emailQuery->delete();
        $deletedSystemNotif = 0;

        if ($includeInApp) {
            $deletedSystemNotif = $notificationsQuery->delete();
        }

        $totalDeleted = $deletedNotif + $deletedEmail + $deletedSystemNotif;

        // Madde 11 & 15: Denetim kaydı oluştur
        ActivityLogS::logAction(
            'notification_log.clear',
            "Bildirim ve e-posta logları temizlendi (Kapsam: {$periodText}, Toplam Silinen: {$totalDeleted} [Bildirim Log: {$deletedNotif}, E-posta Log: {$deletedEmail}, Zil Bildirimi: {$deletedSystemNotif}])"
        );

        return redirect()->route('settings.notification_logs.index')
            ->with('success', "Geçmiş loglar başarıyla temizlendi. (Silinen kayıt sayısı: {$totalDeleted})");
    }
}

