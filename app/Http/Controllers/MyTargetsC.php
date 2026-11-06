<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\KpiTargetM;
use App\Services\PermissionS;
use App\Services\KpiTargetS;

class MyTargetsC extends Controller
{
    protected PermissionS $permissionService;
    protected KpiTargetS $kpiService;

    public function __construct(PermissionS $permissionService, KpiTargetS $kpiService)
    {
        $this->permissionService = $permissionService;
        $this->kpiService = $kpiService;
    }

    /**
     * Kullanıcının kendi hedeflerini, gerçekleşmelerini ve geçmiş yıl arşivini görüntüler.
     */
    public function index(Request $request)
    {
        $user = Auth::user();

        // Sistem Yöneticisi için operasyonel hedef olmaz; doğrudan KPI Yönetimine yönlendir
        $roleSlug = $user->role?->slug ?? '';
        $isSuperAdmin = $user->id === 1 || $user->role_id === 1 || $roleSlug === 'sistem_yoneticisi';
        if ($isSuperAdmin) {
            return redirect()->route('kpi-targets.index')->with('info', 'Sistem Yöneticisi rolü için bireysel operasyonel hedef tanımlanmamaktadır. Şirket, grup ve tüm personel hedeflerini KPI Yönetimi panelinden yönetebilirsiniz.');
        }

        if (!$this->permissionService->hasPermission($user, 'kpi_targets.view_own') && !$this->permissionService->hasPermission($user, 'kpi_targets.manage')) {
            abort(403, 'Hedeflerinizi görüntüleme yetkiniz bulunmamaktadır.');
        }

        $selectedYear = (int) $request->get('year', date('Y'));

        // Kullanıcının hedefleri olan tüm yılları getir (Arşiv filtresi için)
        $availableYears = KpiTargetM::where(function ($q) use ($user) {
            $q->where(function ($sub) use ($user) {
                $sub->where('assignee_type', 'user')->where('assignee_id', $user->id);
            });
            if ($user->group_id) {
                $q->orWhere(function ($sub) use ($user) {
                    $sub->where('assignee_type', 'group')->where('assignee_id', $user->group_id);
                });
            }
            if ($user->managedGroups && $user->managedGroups->isNotEmpty()) {
                $q->orWhere(function ($sub) use ($user) {
                    $sub->where('assignee_type', 'manager')->whereIn('assignee_id', $user->managedGroups->pluck('id'));
                });
            }
        })
        ->distinct()
        ->pluck('target_year')
        ->toArray();

        $currentYear = (int) date('Y');
        if (!in_array($currentYear, $availableYears)) {
            $availableYears[] = $currentYear;
        }
        rsort($availableYears);

        // Seçilen yıla ait hedefler
        $targets = KpiTargetM::with([
            'template',
            'targetField',
            'periods' => function ($q) {
                $q->orderBy('period_month');
            },
            'realizations' => function ($q) {
                $q->orderBy('realization_month');
            }
        ])
        ->where('target_year', $selectedYear)
        ->where(function ($q) use ($user) {
            $q->where(function ($sub) use ($user) {
                $sub->where('assignee_type', 'user')->where('assignee_id', $user->id);
            });
            if ($user->group_id) {
                $q->orWhere(function ($sub) use ($user) {
                    $sub->where('assignee_type', 'group')->where('assignee_id', $user->group_id);
                });
            }
            if ($user->managedGroups && $user->managedGroups->isNotEmpty()) {
                $q->orWhere(function ($sub) use ($user) {
                    $sub->where('assignee_type', 'manager')->whereIn('assignee_id', $user->managedGroups->pluck('id'));
                });
            }
        })
        ->orderBy('created_at', 'desc')
        ->get();

        // Performans karnesi özeti
        $totalTargets = $targets->count();
        $totalAchieved = 0;
        $totalInProgress = 0;
        $totalAtRisk = 0;
        $sumRates = 0;

        foreach ($targets as $target) {
            $annualReal = $target->realizations->where('realization_month', 0)->first();
            $rate = $annualReal ? floatval($annualReal->achievement_rate) : 0;
            $sumRates += $rate;
            if ($rate >= 100) {
                $totalAchieved++;
            } elseif ($rate >= 70) {
                $totalInProgress++;
            } else {
                $totalAtRisk++;
            }
        }

        $averageRate = $totalTargets > 0 ? round($sumRates / $totalTargets, 1) : 0;
        $performanceScore = [
            'total_targets' => $totalTargets,
            'average_rate' => $averageRate,
            'achieved_count' => $totalAchieved,
            'in_progress_count' => $totalInProgress,
            'at_risk_count' => $totalAtRisk,
        ];

        // Coğrafi Harita & Radar Verileri
        $geoData = $this->kpiService->getGeographicDistributionData($user->id, $selectedYear);
        $radarData = $this->kpiService->getRadarPerformanceData($user->id, $selectedYear);

        return view('kpi_targets.my_targets', compact(
            'targets',
            'selectedYear',
            'availableYears',
            'performanceScore',
            'user',
            'geoData',
            'radarData'
        ));
    }
}

