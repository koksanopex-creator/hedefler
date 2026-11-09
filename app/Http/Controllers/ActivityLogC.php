<?php

namespace App\Http\Controllers;

use App\Models\ActivityLogM;
use App\Models\UserM;
use App\Services\ActivityLogS;
use Illuminate\Http\Request;
use Carbon\Carbon;

class ActivityLogC extends Controller
{
    /**
     * Tüm sistem aktivitelerini ve denetim izini listeler.
     */
    public function index(Request $request)
    {
        $search = $request->get('search');
        $userId = $request->get('user_id');
        $action = $request->get('action');
        $dateFrom = $request->get('date_from');
        $dateTo = $request->get('date_to');

        $query = ActivityLogM::with(['user.role', 'user.group'])
            ->orderBy('created_at', 'desc');

        // Arama (Açıklama, Aksiyon, IP Adresi)
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('description', 'like', "%{$search}%")
                  ->orWhere('action', 'like', "%{$search}%")
                  ->orWhere('ip_address', 'like', "%{$search}%")
                  ->orWhereHas('user', function ($uq) use ($search) {
                      $uq->where('name', 'like', "%{$search}%")
                         ->orWhere('email', 'like', "%{$search}%");
                  });
            });
        }

        // Kullanıcı Filtresi
        if ($userId !== null && $userId !== '') {
            if ($userId === 'system') {
                $query->whereNull('user_id');
            } else {
                $query->where('user_id', $userId);
            }
        }

        // İşlem (Aksiyon) Filtresi
        if ($action) {
            $query->where('action', $action);
        }

        // Tarih Aralığı Filtresi
        if ($dateFrom) {
            $query->whereDate('created_at', '>=', Carbon::parse($dateFrom)->startOfDay());
        }
        if ($dateTo) {
            $query->whereDate('created_at', '<=', Carbon::parse($dateTo)->endOfDay());
        }

        // Sayfalama (Her sayfada 20 veri)
        $logs = $query->paginate(20)->withQueryString();

        // Üst İstatistikler
        $totalCount = ActivityLogM::count();
        $todayCount = ActivityLogM::whereDate('created_at', Carbon::today())->count();
        $activeUsersCount = ActivityLogM::whereNotNull('user_id')->distinct('user_id')->count('user_id');

        // Filtre Seçenekleri
        $distinctUserIds = ActivityLogM::whereNotNull('user_id')
            ->distinct()
            ->pluck('user_id')
            ->toArray();
        $filterUsers = UserM::whereIn('id', $distinctUserIds)
            ->orderBy('name')
            ->get(['id', 'name', 'email']);

        $distinctActionSlugs = ActivityLogM::distinct()
            ->whereNotNull('action')
            ->pluck('action')
            ->toArray();

        $filterActions = collect($distinctActionSlugs)->map(function ($slug) {
            return [
                'slug' => $slug,
                'label' => ActivityLogS::getActionMeta($slug)['label'] ?? $slug,
            ];
        })->sortBy('label')->values();

        return view('settings.activity_logs.index', compact(
            'logs',
            'search',
            'userId',
            'action',
            'dateFrom',
            'dateTo',
            'totalCount',
            'todayCount',
            'activeUsersCount',
            'filterUsers',
            'filterActions'
        ));
    }
}

