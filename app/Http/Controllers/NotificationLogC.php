<?php

namespace App\Http\Controllers;

use App\Models\NotificationLogM;
use App\Models\NotificationEventM;
use App\Models\RoleM;
use Illuminate\Http\Request;

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
}

