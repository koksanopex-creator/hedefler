<?php

namespace App\Http\Controllers;

use App\Models\FormEntryM;
use App\Models\FormTemplateM;
use App\Models\FormRevisionRequestM;
use App\Models\UserM;
use App\Models\GroupM;
use App\Models\ActivityLogM;
use App\Models\KpiTargetM;
use App\Models\KpiTargetRealizationM;
use App\Services\PermissionS;
use App\Services\FormScheduleS;
use App\Services\KpiTargetS;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DashboardC extends Controller
{
    protected PermissionS $permissionService;
    protected FormScheduleS $formScheduleService;
    protected KpiTargetS $kpiService;

    public function __construct(
        PermissionS $permissionService,
        FormScheduleS $formScheduleService,
        KpiTargetS $kpiService
    ) {
        $this->permissionService = $permissionService;
        $this->formScheduleService = $formScheduleService;
        $this->kpiService = $kpiService;
    }

    public function index(Request $request)
    {
        $user = Auth::user();
        $can = fn(string $slug) => $this->permissionService->hasPermission($user, $slug);

        // ─── Dinamik Zaman Filtreleri ───
        $selectedYear = (int) $request->get('year', date('Y'));
        $selectedMonth = $request->filled('month') ? (int) $request->get('month') : null;
        $startDate = $request->get('start_date');
        $endDate = $request->get('end_date');
        $inspectUserId = $request->filled('inspect_user_id') ? (int) $request->get('inspect_user_id') : null;

        $filters = [
            'year'            => $selectedYear,
            'month'           => $selectedMonth,
            'start_date'      => $startDate,
            'end_date'        => $endDate,
            'inspect_user_id' => $inspectUserId,
        ];

        $data = [
            'filters'        => $filters,
            'availableYears' => range(date('Y') + 1, 2024),
        ];

        // ─── 1. Özet Kartları (Summary Cards) ───
        if ($can('dashboard.summary_cards')) {
            $data['summaryCards'] = $this->getSummaryCards($user, $can, $filters);
        }

        // ─── 2. Rol Bazlı KPI Kokpiti (Bireysel / Grup / Yönetim) ───
        if ($can('dashboard.kpi_cockpit')) {
            $data['kpiCockpit'] = $this->getKpiCockpitData($user, $can, $selectedYear, $selectedMonth, $inspectUserId);
        } else {
            $data['kpiCockpit'] = null;
        }

        // ─── 3. Durum Dağılımı Pasta Grafiği ───
        if ($can('dashboard.status_chart')) {
            $data['statusChart'] = $this->getStatusChartData($user, $can, $filters);
        }

        // ─── 4. Aylık Giriş Trendi (Seçilen Yılın 12 Ayı) ───
        if ($can('dashboard.trend_chart')) {
            $data['trendChart'] = $this->getTrendChartData($user, $can, $selectedYear);
        }

        // ─── 5. Form Şablonu Bazlı Dağılım ───
        if ($can('dashboard.template_chart')) {
            $data['templateChart'] = $this->getTemplateChartData($user, $can, $filters);
        }

        // ─── 6. Grup Bazlı Karşılaştırma ───
        if ($can('dashboard.group_chart')) {
            $data['groupChart'] = $this->getGroupChartData($filters);
        }

        // ─── 7. Kullanıcı Bazlı Liderlik Tablosu ───
        if ($can('dashboard.leaderboard')) {
            $data['leaderboard'] = $this->getLeaderboard($filters);
        }

        // ─── 8. Onay Bekleyen Formlar & Revizyon Talepleri ───
        if ($can('dashboard.pending_approvals')) {
            $data['pendingApprovals'] = $this->getPendingApprovals($user, $can);
            $data['pendingRevisionRequests'] = $this->getPendingRevisionRequests($user, $can);
        }

        // ─── 9. Son Aktiviteler ───
        if ($can('dashboard.recent_activity')) {
            $data['recentActivity'] = $this->getRecentActivity();
        }

        // ─── 10. Kullanıcının Kendi İstatistikleri ───
        if ($can('dashboard.my_stats')) {
            $data['myStats'] = $this->getMyStats($user, $filters);
        }

        // ─── 11. Grup Bilgileri ───
        if ($can('dashboard.view_group_users') || $can('dashboard.view_group_manager')) {
            if ($user->group_id) {
                $group = GroupM::with(['users', 'managers'])->find($user->group_id);
                if ($group) {
                    if ($can('dashboard.view_group_users')) {
                        $data['groupUsers'] = $group->users;
                    }
                    if ($can('dashboard.view_group_manager')) {
                        $data['groupManagers'] = $group->managers;
                    }
                }
            }
        }

        $data['permissions'] = [
            'date_cockpit'      => $can('dashboard.date_cockpit'),
            'kpi_cockpit'       => $can('dashboard.kpi_cockpit'),
            'summary_cards'     => $can('dashboard.summary_cards'),
            'status_chart'      => $can('dashboard.status_chart'),
            'trend_chart'       => $can('dashboard.trend_chart'),
            'template_chart'    => $can('dashboard.template_chart'),
            'group_chart'       => $can('dashboard.group_chart'),
            'leaderboard'       => $can('dashboard.leaderboard'),
            'pending_approvals' => $can('dashboard.pending_approvals'),
            'recent_activity'   => $can('dashboard.recent_activity'),
            'my_stats'          => $can('dashboard.my_stats'),
            'view_group_users'  => $can('dashboard.view_group_users'),
            'view_group_manager'=> $can('dashboard.view_group_manager'),
        ];

        return view('dashboard', $data);
    }

    // ─── Helpers ─────────────────────────────────────

    /**
     * Sorguya dinamik tarih/zaman filtrelerini uygular
     */
    private function applyDateFilters($query, array $filters, string $dateColumn = 'entry_date')
    {
        if (!empty($filters['start_date']) && !empty($filters['end_date'])) {
            $query->whereBetween($dateColumn, [$filters['start_date'], $filters['end_date']]);
        } else {
            if (!empty($filters['year'])) {
                $query->whereYear($dateColumn, $filters['year']);
            }
            if (!empty($filters['month'])) {
                $query->whereMonth($dateColumn, $filters['month']);
            }
        }
        return $query;
    }

    /**
     * Kullanıcının yetkisine göre erişebileceği FormEntry sorgu tabanını üretir.
     */
    private function getBaseFormEntryQuery($user, $can)
    {
        if ($can('form_entries.view_all')) {
            return FormEntryM::query();
        }

        if ($can('form_entries.view_group') || $can('form_entries.approve_group')) {
            $managedGroupIds = $user->managedGroups->pluck('id')->toArray();
            $groupIds = array_unique(array_filter(array_merge([$user->group_id], $managedGroupIds)));

            return FormEntryM::where(function ($q) use ($user, $groupIds) {
                $q->where('user_id', $user->id);
                if (!empty($groupIds)) {
                    $q->orWhereHas('user', fn($sub) => $sub->whereIn('group_id', $groupIds));
                }
            });
        }

        return FormEntryM::where('user_id', $user->id);
    }

    private function getSummaryCards($user, $can, array $filters): array
    {
        $viewAll = $can('form_entries.view_all');

        $base = $this->getBaseFormEntryQuery($user, $can);
        $this->applyDateFilters($base, $filters, 'entry_date');

        $totalEntries         = (clone $base)->count();
        $pendingCount         = (clone $base)->where('status', 'submitted')->count();
        $revisionPendingCount = (clone $base)->whereHas('revisionRequests', fn($q) => $q->where('status', 'pending'))->count();
        $approvedCount        = (clone $base)->where('status', 'approved')
            ->whereDoesntHave('revisionRequests', fn($q) => $q->where('status', 'pending'))
            ->count();
        $draftCount           = (clone $base)->where('status', 'draft')->count();
        $rejectedCount        = (clone $base)->where('status', 'rejected')->count();
        $todayEntries         = (clone $base)->whereDate('entry_date', today())->count();

        // Form şablon sayısı
        $templateCount = $viewAll ? FormTemplateM::count() : count($this->formScheduleService->getAccessibleTemplateIds($user));

        // Kullanıcı sayısı
        if ($can('users.manage') || $viewAll) {
            $userCount = UserM::count();
        } elseif ($can('dashboard.view_group_users') && $user->group_id) {
            $userCount = UserM::where('group_id', $user->group_id)->count();
        } else {
            $userCount = 1;
        }

        return compact('totalEntries', 'pendingCount', 'revisionPendingCount', 'approvedCount', 'draftCount', 'rejectedCount', 'templateCount', 'userCount', 'todayEntries');
    }

    /**
     * Rol Bazlı Kapsamlı KPI Kokpit Verilerini Üretir
     */
    private function getKpiCockpitData($user, $can, int $year, ?int $month, ?int $inspectUserId = null): array
    {
        $roleName = $user->role?->name ?? '';
        $roleSlug = $user->role?->slug ?? '';
        $isSuperAdmin = $user->id === 1 || $user->role_id === 1 || $roleSlug === 'sistem_yoneticisi' || str_contains(strtolower($roleName), 'superadmin');
        $isYonetim = $user->role_id === 2 || $user->role_id === 19 || $roleSlug === 'yonetim' || mb_stripos($roleName, 'yönetim') !== false || mb_stripos($roleName, 'yonetim') !== false;
        $isGroupManager = $user->managedGroups()->exists() || $can('kpi_targets.view_group');
        $canManageKpi = $can('kpi_targets.manage') || $can('kpi_targets.view_all');

        $monthKey = $month ?: 0; // 0 = Yıllık Kümülatif, 1..12 = Seçilen Ay

        // İncelenecek kullanıcı: Yönetim/Superadmin dropdown'dan bir personel seçtiyse o, aksi halde kullanıcının kendisi
        $targetUserId = ($isSuperAdmin || $isYonetim || $canManageKpi) && $inspectUserId ? $inspectUserId : $user->id;
        $inspectedUser = $targetUserId !== $user->id ? UserM::find($targetUserId) : $user;

        // 1. Bireysel Personel Hedefleri
        $myTargetsQuery = KpiTargetM::with([
            'template',
            'targetField',
            'realizations' => function ($q) use ($year) {
                $q->where('realization_year', $year);
            },
            'periods' => function ($q) use ($year) {
                $q->where('period_year', $year);
            }
        ])
        ->where('target_year', $year)
        ->where('assignee_type', 'user')
        ->where('assignee_id', $targetUserId)
        ->where('status', 'active');

        $myTargets = $myTargetsQuery->get();

        $myTargetItems = [];
        $sumRates = 0;
        $totalTargetVal = 0;
        $totalActualVal = 0;
        $achievedCount = 0;
        $inProgressCount = 0;
        $atRiskCount = 0;

        foreach ($myTargets as $t) {
            $real = $t->realizations->firstWhere('realization_month', $monthKey);
            $actual = $real ? floatval($real->actual_value) : 0;
            $rate = $real ? floatval($real->achievement_rate) : 0;
            $targetVal = floatval($t->target_value);

            if ($monthKey > 0) {
                $period = $t->periods->firstWhere('period_month', $monthKey);
                $targetVal = $period ? floatval($period->period_target_value) : round($targetVal / 12, 2);
            }

            $sumRates += $rate;
            $totalTargetVal += $targetVal;
            $totalActualVal += $actual;

            if ($rate >= 100) {
                $achievedCount++;
                $barColor = 'bg-emerald-500';
                $badgeClass = 'bg-emerald-50 text-emerald-700 border-emerald-200';
            } elseif ($rate >= 70) {
                $inProgressCount++;
                $barColor = 'bg-amber-500';
                $badgeClass = 'bg-amber-50 text-amber-700 border-amber-200';
            } else {
                $atRiskCount++;
                $barColor = 'bg-rose-500';
                $badgeClass = 'bg-rose-50 text-rose-700 border-rose-200';
            }

            $myTargetItems[] = [
                'id'               => $t->id,
                'title'            => $t->title,
                'template'         => $t->template->name ?? 'Dinamik Form',
                'template_name'    => $t->template->name ?? 'Dinamik Form',
                'target_field'     => $t->targetField->label ?? 'Hedef Alanı',
                'field_label'      => $t->targetField->label ?? 'Hedef Alanı',
                'target_value'     => $targetVal,
                'actual_value'     => $actual,
                'unit'             => $t->unit,
                'achievement_rate' => $rate,
                'bar_color'        => $barColor,
                'badge_class'      => $badgeClass,
                'badges'           => $this->kpiService->getFormattedCriteriaBadges($t->criteria_json),
            ];
        }

        $myTargetsCount = count($myTargetItems);
        $myAverageRate = $myTargetsCount > 0 ? round($sumRates / $myTargetsCount, 1) : 0;

        if ($myAverageRate >= 100) {
            $grade = 'Üstün Başarı';
            $gradeColor = 'text-emerald-700 bg-emerald-50 border-emerald-200';
        } elseif ($myAverageRate >= 70) {
            $grade = 'Hedef Yolunda';
            $gradeColor = 'text-indigo-700 bg-indigo-50 border-indigo-200';
        } elseif ($myTargetsCount > 0) {
            $grade = 'Geliştirilmeli (Kritik)';
            $gradeColor = 'text-rose-700 bg-rose-50 border-rose-200';
        } else {
            $grade = 'Tanımlı Hedef Yok';
            $gradeColor = 'text-slate-500 bg-slate-50 border-slate-200';
        }

        $myScorecard = [
            'total_targets'    => $myTargetsCount,
            'average_rate'     => $myAverageRate,
            'total_target_val' => $totalTargetVal,
            'total_actual_val' => $totalActualVal,
            'achieved_count'   => $achievedCount,
            'in_progress_count'=> $inProgressCount,
            'at_risk_count'    => $atRiskCount,
            'grade'            => $grade,
            'grade_color'      => $gradeColor,
            'targets'          => $myTargetItems,
            'user'             => $inspectedUser,
        ];

        // 2. Grup Yöneticisi ve Yönetim için Departman / Grup Hedefleri
        $groupScorecard = null;
        if ($isGroupManager || $isSuperAdmin || $isYonetim || $canManageKpi) {
            $managedGroupIds = $user->managedGroups()->pluck('groups.id')->toArray();
            if ($user->group_id && !in_array($user->group_id, $managedGroupIds)) {
                $managedGroupIds[] = $user->group_id;
            }

            $groupQuery = GroupM::query();
            if (!$isSuperAdmin && !$isYonetim && !$canManageKpi && !empty($managedGroupIds)) {
                $groupQuery->whereIn('id', $managedGroupIds);
            }
            $groups = $groupQuery->with('users')->get();

            $groupList = [];
            foreach ($groups as $grp) {
                $grpTargets = KpiTargetM::with(['realizations' => function ($q) use ($year) {
                    $q->where('realization_year', $year);
                }])
                ->where('target_year', $year)
                ->where(function ($q) use ($grp) {
                    $q->where(function ($sub) use ($grp) {
                        $sub->whereIn('assignee_type', ['group', 'manager'])->where('assignee_id', $grp->id);
                    })->orWhere(function ($sub) use ($grp) {
                        $sub->where('assignee_type', 'user')->whereIn('assignee_id', $grp->users->pluck('id'));
                    });
                })
                ->get();

                $grpRates = [];
                foreach ($grpTargets as $gt) {
                    $r = $gt->realizations->firstWhere('realization_month', $monthKey);
                    if ($r) {
                        $grpRates[] = floatval($r->achievement_rate);
                    }
                }

                $avgGrpRate = !empty($grpRates) ? round(array_sum($grpRates) / count($grpRates), 1) : 0;

                $groupList[] = [
                    'id'           => $grp->id,
                    'name'         => $grp->name,
                    'user_count'   => $grp->users->count(),
                    'target_count' => $grpTargets->count(),
                    'average_rate' => $avgGrpRate,
                ];
            }

            $groupScorecard = [
                'groups' => $groupList,
            ];
        }

        // 3. Şirket Geneli KPI Başarısı (Yönetim / Superadmin için)
        $companyScorecard = null;
        if ($isSuperAdmin || $isYonetim || $canManageKpi) {
            $companyTargets = KpiTargetM::with(['realizations' => function ($q) use ($year) {
                $q->where('realization_year', $year);
            }])
            ->where('target_year', $year)
            ->where('status', 'active')
            ->get();

            $cmpRates = [];
            $cmpAchieved = 0;
            $cmpTotalTarget = 0;
            $cmpTotalActual = 0;

            foreach ($companyTargets as $ct) {
                $r = $ct->realizations->firstWhere('realization_month', $monthKey);
                $rate = $r ? floatval($r->achievement_rate) : 0;
                $actual = $r ? floatval($r->actual_value) : 0;
                $targetVal = floatval($ct->target_value);

                $cmpRates[] = $rate;
                $cmpTotalTarget += $targetVal;
                $cmpTotalActual += $actual;
                if ($rate >= 100) {
                    $cmpAchieved++;
                }
            }

            $cmpAvg = !empty($cmpRates) ? round(array_sum($cmpRates) / count($cmpRates), 1) : 0;

            $companyScorecard = [
                'total_targets'    => $companyTargets->count(),
                'average_rate'     => $cmpAvg,
                'achieved_count'   => $cmpAchieved,
                'total_target_val' => $cmpTotalTarget,
                'total_actual_val' => $cmpTotalActual,
            ];
        }

        // 4. Yönetim / Superadmin için Canlı Personel İnceleme Listesi (Dropdown)
        $allUsersList = [];
        if ($isSuperAdmin || $isYonetim || $canManageKpi) {
            $allUsersList = UserM::with('group')
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name', 'group_id']);
        }

        return [
            'isSuperAdmin'     => $isSuperAdmin,
            'isYonetim'        => $isYonetim,
            'isGroupManager'   => $isGroupManager,
            'canManageKpi'     => $canManageKpi,
            'year'             => $year,
            'month'            => $month,
            'myScorecard'      => $myScorecard,
            'groupScorecard'   => $groupScorecard,
            'companyScorecard' => $companyScorecard,
            'allUsersList'     => $allUsersList,
            'inspectUserId'    => $inspectUserId,
        ];
    }

    private function getStatusChartData($user, $can, array $filters): array
    {
        $query = $this->getBaseFormEntryQuery($user, $can);
        $this->applyDateFilters($query, $filters, 'entry_date');

        $draftCount = (clone $query)->where('status', 'draft')->count();
        $submittedCount = (clone $query)->where('status', 'submitted')->count();
        $revisionCount = (clone $query)->whereHas('revisionRequests', fn($q) => $q->where('status', 'pending'))->count();
        $approvedCount = (clone $query)->where('status', 'approved')
            ->whereDoesntHave('revisionRequests', fn($q) => $q->where('status', 'pending'))
            ->count();
        $rejectedCount = (clone $query)->where('status', 'rejected')->count();

        return [
            'labels' => ['Taslak', 'Onay Bekliyor', 'Revizyon Bekliyor', 'Onaylandı', 'Reddedildi'],
            'data'   => [
                $draftCount,
                $submittedCount,
                $revisionCount,
                $approvedCount,
                $rejectedCount,
            ],
            'colors' => ['#94a3b8', '#f59e0b', '#ea580c', '#10b981', '#ef4444'],
        ];
    }

    private function getTrendChartData($user, $can, int $year): array
    {
        $viewAll = $can('form_entries.view_all');

        $months = [];
        $labels = [];
        for ($m = 1; $m <= 12; $m++) {
            $months[] = sprintf('%04d-%02d', $year, $m);
            $labels[] = Carbon::create($year, $m, 1)->translatedFormat('M');
        }

        $query = $this->getBaseFormEntryQuery($user, $can);
        $entries = (clone $query)
            ->whereYear('entry_date', $year)
            ->select(DB::raw("DATE_FORMAT(entry_date, '%Y-%m') as month"), DB::raw('count(*) as total'))
            ->groupBy('month')
            ->pluck('total', 'month');

        $approved = (clone $query)
            ->where('status', 'approved')
            ->whereDoesntHave('revisionRequests', fn($q) => $q->where('status', 'pending'))
            ->whereYear('entry_date', $year)
            ->select(DB::raw("DATE_FORMAT(entry_date, '%Y-%m') as month"), DB::raw('count(*) as total'))
            ->groupBy('month')
            ->pluck('total', 'month');

        return [
            'labels'   => $labels,
            'total'    => array_map(fn($m) => $entries[$m] ?? 0, $months),
            'approved' => array_map(fn($m) => $approved[$m] ?? 0, $months),
        ];
    }

    private function getTemplateChartData($user, $can, array $filters): array
    {
        $query = $this->getBaseFormEntryQuery($user, $can);
        $this->applyDateFilters($query, $filters, 'entry_date');

        $results = (clone $query)
            ->join('form_templates', 'form_entries.form_template_id', '=', 'form_templates.id')
            ->select('form_templates.name', DB::raw('count(*) as total'))
            ->groupBy('form_templates.name')
            ->orderByDesc('total')
            ->limit(10)
            ->get();

        return [
            'labels' => $results->pluck('name')->toArray(),
            'data'   => $results->pluck('total')->toArray(),
        ];
    }

    private function getGroupChartData(array $filters = []): array
    {
        $query = FormEntryM::join('users', 'form_entries.user_id', '=', 'users.id')
            ->join('groups', 'users.group_id', '=', 'groups.id');
        $this->applyDateFilters($query, $filters, 'form_entries.entry_date');

        $results = $query
            ->select('groups.name', DB::raw('count(*) as total'))
            ->groupBy('groups.name')
            ->orderByDesc('total')
            ->get();

        return [
            'labels' => $results->pluck('name')->toArray(),
            'data'   => $results->pluck('total')->toArray(),
        ];
    }

    private function getLeaderboard(array $filters = []): array
    {
        $query = FormEntryM::join('users', 'form_entries.user_id', '=', 'users.id');
        $this->applyDateFilters($query, $filters, 'form_entries.entry_date');

        $results = $query
            ->select('users.name', DB::raw('count(*) as total'), DB::raw("SUM(CASE WHEN form_entries.status = 'approved' THEN 1 ELSE 0 END) as approved"))
            ->groupBy('users.id', 'users.name')
            ->orderByDesc('total')
            ->limit(5)
            ->get();

        return $results->map(fn($r) => [
            'name'     => $r->name,
            'total'    => (int) $r->total,
            'approved' => (int) $r->approved,
        ])->toArray();
    }

    private function getPendingApprovals($user, $can): \Illuminate\Database\Eloquent\Collection
    {
        $query = FormEntryM::with(['user', 'template'])
            ->where('status', 'submitted');

        if (!$can('form_entries.view_all')) {
            $managedGroupIds = $user->managedGroups->pluck('id')->toArray();
            $groupIds = array_unique(array_filter(array_merge([$user->group_id], $managedGroupIds)));
            $query->whereHas('user', fn($sub) => $sub->whereIn('group_id', $groupIds));
        }

        return $query->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();
    }

    private function getPendingRevisionRequests($user, $can): \Illuminate\Database\Eloquent\Collection
    {
        $query = FormRevisionRequestM::with(['user.group', 'formEntry.template'])
            ->where('status', 'pending');

        if (!$can('form_entries.view_all')) {
            $managedGroupIds = $user->managedGroups->pluck('id')->toArray();
            $groupIds = array_unique(array_filter(array_merge([$user->group_id], $managedGroupIds)));
            $query->whereHas('user', fn($sub) => $sub->whereIn('group_id', $groupIds));
        }

        return $query->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();
    }

    private function getRecentActivity(): \Illuminate\Database\Eloquent\Collection
    {
        return ActivityLogM::with('user')
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();
    }

    private function getMyStats($user, array $filters = []): array
    {
        $base = FormEntryM::where('user_id', $user->id);
        $this->applyDateFilters($base, $filters, 'entry_date');

        $total           = (clone $base)->count();
        $revisionPending = (clone $base)->whereHas('revisionRequests', fn($q) => $q->where('status', 'pending'))->count();
        $approved        = (clone $base)->where('status', 'approved')
            ->whereDoesntHave('revisionRequests', fn($q) => $q->where('status', 'pending'))
            ->count();
        $pending         = (clone $base)->where('status', 'submitted')->count();
        $rejected        = (clone $base)->where('status', 'rejected')->count();
        $draft           = (clone $base)->where('status', 'draft')->count();
        $rate            = $total > 0 ? round(($approved / $total) * 100) : 0;

        $lastEntry = (clone $base)->latest('entry_date')->first();

        return compact('total', 'approved', 'pending', 'revisionPending', 'rejected', 'draft', 'rate', 'lastEntry');
    }
}
