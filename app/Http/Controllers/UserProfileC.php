<?php

namespace App\Http\Controllers;

use App\Models\UserM;
use App\Models\FormEntryM;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UserProfileC extends Controller
{
    public function show(Request $request, $id)
    {
        $user = UserM::with(['role', 'group', 'managedGroups'])->findOrFail($id);

        // Kullanıcının form girişleri ve istatistikleri
        $formEntries = FormEntryM::with('template')
            ->where('user_id', $user->id)
            ->orderBy('entry_date', 'desc')
            ->paginate(10);

        $stats = [
            'total_entries' => FormEntryM::where('user_id', $user->id)->count(),
            'approved_entries' => FormEntryM::where('user_id', $user->id)->where('status', 'approved')->count(),
            'pending_entries' => FormEntryM::where('user_id', $user->id)->where('status', 'submitted')->count(),
            'draft_entries' => FormEntryM::where('user_id', $user->id)->where('status', 'draft')->count(),
            'last_entry_date' => FormEntryM::where('user_id', $user->id)->max('entry_date'),
        ];

        // KPI & Performans Karnesi Görüntüleme Yetki Kontrolü
        $viewer = Auth::user();
        $isOwnProfile = $viewer && $viewer->id === $user->id;
        $canViewKpi = $isOwnProfile
            || ($viewer && $viewer->can('kpi_targets.view_all'))
            || ($viewer && $viewer->can('kpi_targets.view_profile_kpi'))
            || ($viewer && $viewer->can('kpi_targets.manage'))
            || ($viewer && $viewer->can('kpi_targets.view_group') && $viewer->group_id && $viewer->group_id === $user->group_id)
            || ($viewer && $viewer->managedGroups && $viewer->managedGroups->contains('id', $user->group_id));

        $selectedKpiYear = (int) $request->get('kpi_year', date('Y'));
        $kpiTargets = collect();
        $availableKpiYears = [(int) date('Y')];
        $kpiStats = [
            'total_targets' => 0,
            'average_rate' => 0,
            'achieved_count' => 0,
            'in_progress_count' => 0,
            'at_risk_count' => 0,
            'performance_grade' => 'Nötr',
        ];

        if ($canViewKpi) {
            // Kullanıcının hedefi olan tüm yılları çek (Arşiv seçicisi için)
            $dbYears = \App\Models\KpiTargetM::where(function ($q) use ($user) {
                $q->where(function ($sub) use ($user) {
                    $sub->where('assignee_type', 'user')->where('assignee_id', $user->id);
                });
                if ($user->group_id) {
                    $q->orWhere(function ($sub) use ($user) {
                        $sub->where('assignee_type', 'group')->where('assignee_id', $user->group_id);
                    });
                }
            })
            ->distinct()
            ->pluck('target_year')
            ->toArray();

            if (!empty($dbYears)) {
                $availableKpiYears = array_unique(array_merge($availableKpiYears, $dbYears));
            }
            rsort($availableKpiYears);

            // Seçilen yıla ait hedefler ve aylık gerçekleşmeler
            $kpiTargets = \App\Models\KpiTargetM::with([
                'template',
                'targetField',
                'realizations' => function ($q) {
                    $q->orderBy('realization_month');
                },
                'periods' => function ($q) {
                    $q->orderBy('period_month');
                }
            ])
            ->where('target_year', $selectedKpiYear)
            ->where(function ($q) use ($user) {
                $q->where(function ($sub) use ($user) {
                    $sub->where('assignee_type', 'user')->where('assignee_id', $user->id);
                });
                if ($user->group_id) {
                    $q->orWhere(function ($sub) use ($user) {
                        $sub->where('assignee_type', 'group')->where('assignee_id', $user->group_id);
                    });
                }
            })
            ->orderBy('created_at', 'desc')
            ->get();

            // Performans karnesi hesaplaması
            if ($kpiTargets->isNotEmpty()) {
                $kpiStats['total_targets'] = $kpiTargets->count();
                $sumRate = 0;
                foreach ($kpiTargets as $t) {
                    $annualReal = $t->realizations->where('realization_month', 0)->first();
                    $rate = $annualReal ? floatval($annualReal->achievement_rate) : 0;
                    $sumRate += $rate;
                    if ($rate >= 100) {
                        $kpiStats['achieved_count']++;
                    } elseif ($rate >= 70) {
                        $kpiStats['in_progress_count']++;
                    } else {
                        $kpiStats['at_risk_count']++;
                    }
                }
                $kpiStats['average_rate'] = round($sumRate / $kpiTargets->count(), 1);

                if ($kpiStats['average_rate'] >= 100) {
                    $kpiStats['performance_grade'] = 'Üstün Başarı';
                } elseif ($kpiStats['average_rate'] >= 85) {
                    $kpiStats['performance_grade'] = 'Başarılı';
                } elseif ($kpiStats['average_rate'] >= 70) {
                    $kpiStats['performance_grade'] = 'Hedef Yolunda';
                } else {
                    $kpiStats['performance_grade'] = 'Geliştirilmeli (Kritik)';
                }
            }
        }

        // Coğrafi Harita & Radar Analiz Verileri
        $geoData = $canViewKpi ? app(\App\Services\KpiTargetS::class)->getGeographicDistributionData($user->id, $selectedKpiYear) : null;
        $radarData = $canViewKpi ? app(\App\Services\KpiTargetS::class)->getRadarPerformanceData($user->id, $selectedKpiYear) : null;

        return view('users.profile', compact(
            'user',
            'formEntries',
            'stats',
            'kpiTargets',
            'canViewKpi',
            'selectedKpiYear',
            'availableKpiYears',
            'kpiStats',
            'geoData',
            'radarData'
        ));
    }
}

