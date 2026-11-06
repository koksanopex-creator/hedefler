<?php

namespace App\Http\Controllers;

use App\Models\KpiTargetM;
use App\Models\KpiTargetPeriodM;
use App\Models\FormTemplateM;
use App\Models\FormFieldM;
use App\Models\MasterDataItemM;
use App\Models\UserM;
use App\Models\GroupM;
use App\Services\KpiTargetS;
use App\Services\PermissionS;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class KpiTargetC extends Controller
{
    protected KpiTargetS $kpiService;
    protected PermissionS $permissionService;

    public function __construct(KpiTargetS $kpiService, PermissionS $permissionService)
    {
        $this->kpiService = $kpiService;
        $this->permissionService = $permissionService;
    }

    /**
     * Kullanıcının yetki kapsamına göre erişebileceği personelleri döner
     */
    private function getAccessibleUsers(UserM $user)
    {
        if ($this->permissionService->hasPermission($user, 'kpi_targets.view_all') ||
            $this->permissionService->hasPermission($user, 'kpi_targets.manage') ||
            $user->role?->slug === 'sistem_yoneticisi') {
            return UserM::with('group')->where('is_active', true)->orderBy('name')->get();
        }

        if ($this->permissionService->hasPermission($user, 'kpi_targets.view_group') || $user->managedGroups->isNotEmpty()) {
            $managedGroupIds = $user->managedGroups->pluck('id')->toArray();
            $allGroupIds = array_unique(array_filter(array_merge([$user->group_id], $managedGroupIds)));

            if (!empty($allGroupIds)) {
                return UserM::with('group')
                    ->whereIn('group_id', $allGroupIds)
                    ->where('is_active', true)
                    ->orderBy('name')
                    ->get();
            }
        }

        if ($this->permissionService->hasPermission($user, 'kpi_targets.view_own')) {
            return UserM::with('group')->where('id', $user->id)->get();
        }

        return collect();
    }

    /**
     * Kullanıcının yetki kapsamına göre erişebileceği grupları döner
     */
    private function getAccessibleGroups(UserM $user)
    {
        if ($this->permissionService->hasPermission($user, 'kpi_targets.view_all') ||
            $this->permissionService->hasPermission($user, 'kpi_targets.manage') ||
            $user->role?->slug === 'sistem_yoneticisi') {
            return GroupM::where('is_active', true)->orderBy('name')->get();
        }

        if ($this->permissionService->hasPermission($user, 'kpi_targets.view_group') || $user->managedGroups->isNotEmpty()) {
            $managedGroupIds = $user->managedGroups->pluck('id')->toArray();
            $allGroupIds = array_unique(array_filter(array_merge([$user->group_id], $managedGroupIds)));

            if (!empty($allGroupIds)) {
                return GroupM::whereIn('id', $allGroupIds)->where('is_active', true)->orderBy('name')->get();
            }
        }

        if ($user->group_id) {
            return GroupM::where('id', $user->group_id)->where('is_active', true)->get();
        }

        return collect();
    }

    /**
     * Kullanıcının hedef üzerindeki işlem yetkisini doğrular
     */
    private function authorizeTargetAccess(UserM $user, KpiTargetM $target): void
    {
        if ($this->permissionService->hasPermission($user, 'kpi_targets.view_all') ||
            $this->permissionService->hasPermission($user, 'kpi_targets.manage') ||
            $user->role?->slug === 'sistem_yoneticisi') {
            return;
        }

        $accessibleUserIds = $this->getAccessibleUsers($user)->pluck('id')->toArray();
        $accessibleGroupIds = $this->getAccessibleGroups($user)->pluck('id')->toArray();

        if ($target->assignee_type === 'user' && in_array($target->assignee_id, $accessibleUserIds)) {
            return;
        }

        if (in_array($target->assignee_type, ['group', 'manager']) && in_array($target->assignee_id, $accessibleGroupIds)) {
            return;
        }

        abort(403, 'Bu KPI hedefine erişim veya işlem yapma yetkiniz bulunmamaktadır.');
    }

    /**
     * Hedef Listesi & Filtreleme Ekranı
     */
    public function index(Request $request)
    {
        $user = Auth::user();

        $activeTab = $request->get('tab', 'all');

        $filters = $request->only(['year', 'template_id', 'target_field_id', 'assignee_type', 'assignee_id', 'status', 'search']);
        if (!isset($filters['year'])) {
            $filters['year'] = date('Y');
        }

        // Sekme filtrelemesi (Tab filter)
        if ($activeTab === 'user') {
            $filters['assignee_type'] = 'user';
        } elseif ($activeTab === 'group') {
            $filters['assignee_type'] = 'group';
        } elseif ($activeTab === 'manager') {
            $filters['assignee_type'] = 'manager';
        }

        $targets = $this->kpiService->getTargetsQuery($filters, $user)->paginate(15)->withQueryString();

        $templates = FormTemplateM::where('is_active', true)->orderBy('name')->get();
        $groups = $this->getAccessibleGroups($user);
        $users = $this->getAccessibleUsers($user);
        $years = range(date('Y') + 2, 2024);

        // Özet Metrik Kartları (Kullanıcının yetki kapsamındaki hedeflere göre hesaplanır)
        $baseQuery = $this->kpiService->getTargetsQuery(['year' => $filters['year']], $user);
        $totalTargetsCount = (clone $baseQuery)->count();
        $userTargetsCount = (clone $baseQuery)->where('assignee_type', 'user')->count();
        $groupTargetsCount = (clone $baseQuery)->whereIn('assignee_type', ['group', 'manager'])->count();

        // Ortalama Başarı Oranı
        $allTargetIds = (clone $baseQuery)->pluck('id');
        $avgRate = 0;
        if ($allTargetIds->isNotEmpty()) {
            $realizations = \App\Models\KpiTargetRealizationM::whereIn('kpi_target_id', $allTargetIds)
                ->where('realization_month', 0)
                ->get();
            if ($realizations->isNotEmpty()) {
                $avgRate = round($realizations->avg('achievement_rate'), 1);
            }
        }

        // Harita & Radar Analiz Verileri (Kişi ve Grup Filtresi Desteği)
        $geoUserId = $request->filled('geo_user_id') ? intval($request->get('geo_user_id')) : null;
        $geoGroupId = $request->filled('geo_group_id') ? intval($request->get('geo_group_id')) : null;

        // Grup yöneticisi ise ve harita filtresi verilmemişse yöneticinin grubuna odaklan
        $isGlobalView = $this->permissionService->hasPermission($user, 'kpi_targets.view_all') ||
            $this->permissionService->hasPermission($user, 'kpi_targets.manage') ||
            $user->role?->slug === 'sistem_yoneticisi';

        if (!$isGlobalView && empty($geoGroupId) && empty($geoUserId) && $groups->isNotEmpty()) {
            $geoGroupId = $groups->first()->id;
        }

        $geoData = $this->kpiService->getGeographicDistributionData($geoUserId, intval($filters['year']), $geoGroupId);
        $radarData = $this->kpiService->getRadarPerformanceData($geoUserId, intval($filters['year']), $geoGroupId);

        // İzin durumları
        $canCreate = $this->permissionService->hasPermission($user, 'kpi_targets.create') || $this->permissionService->hasPermission($user, 'kpi_targets.manage');
        $canEdit = $this->permissionService->hasPermission($user, 'kpi_targets.edit') || $this->permissionService->hasPermission($user, 'kpi_targets.manage');
        $canDelete = $this->permissionService->hasPermission($user, 'kpi_targets.delete') || $this->permissionService->hasPermission($user, 'kpi_targets.manage');
        $canAssign = $this->permissionService->hasPermission($user, 'kpi_targets.assign') || $this->permissionService->hasPermission($user, 'kpi_targets.manage');
        $canDistribute = $this->permissionService->hasPermission($user, 'kpi_targets.distribute') || $this->permissionService->hasPermission($user, 'kpi_targets.manage');
        $canRecalculate = $this->permissionService->hasPermission($user, 'kpi_targets.recalculate') || $this->permissionService->hasPermission($user, 'kpi_targets.manage');

        return view('kpi_targets.index', compact(
            'targets',
            'templates',
            'groups',
            'users',
            'years',
            'filters',
            'activeTab',
            'totalTargetsCount',
            'userTargetsCount',
            'groupTargetsCount',
            'avgRate',
            'geoData',
            'radarData',
            'geoUserId',
            'geoGroupId',
            'canCreate',
            'canEdit',
            'canDelete',
            'canAssign',
            'canDistribute',
            'canRecalculate'
        ));
    }

    /**
     * Yeni Hedef Tanımlama Sihirbazı
     */
    public function create()
    {
        $user = Auth::user();
        if (!$this->permissionService->hasPermission($user, 'kpi_targets.create') && !$this->permissionService->hasPermission($user, 'kpi_targets.manage')) {
            abort(403, 'KPI hedefi oluşturma yetkiniz bulunmamaktadır.');
        }

        $templates = FormTemplateM::with([
            'fields' => function ($q) {
                $q->orderBy('sort_order');
            }
        ])->where('is_active', true)->orderBy('name')->get();

        $groups = $this->getAccessibleGroups($user);
        $users = $this->getAccessibleUsers($user);
        $years = range(date('Y') + 2, 2024);

        $isGlobalView = $this->permissionService->hasPermission($user, 'kpi_targets.view_all') ||
            $this->permissionService->hasPermission($user, 'kpi_targets.manage') ||
            $user->role?->slug === 'sistem_yoneticisi';

        // Varsa üst hedefler (hiyerarşik yayılım için)
        $parentTargetsQuery = KpiTargetM::where('status', 'active');
        if (!$isGlobalView) {
            $accessibleUserIds = $users->pluck('id')->toArray();
            $accessibleGroupIds = $groups->pluck('id')->toArray();
            $parentTargetsQuery->where(function ($q) use ($accessibleUserIds, $accessibleGroupIds) {
                $q->where(function ($sub) use ($accessibleGroupIds) {
                    $sub->whereIn('assignee_type', ['group', 'manager'])->whereIn('assignee_id', $accessibleGroupIds);
                })->orWhere(function ($sub) use ($accessibleUserIds) {
                    $sub->where('assignee_type', 'user')->whereIn('assignee_id', $accessibleUserIds);
                });
            });
        }
        $parentTargets = $parentTargetsQuery->orderBy('title')->get();

        // Master Data'dan Birim Listesi
        $unitType = \App\Models\MasterDataTypeM::where('name', 'BİRİM')->first();
        $units = $unitType ? $unitType->items()->orderBy('name')->pluck('name')->toArray() : ['Ton', 'Adet', 'KG', 'TL', 'USD', 'EUR'];

        return view('kpi_targets.create', compact('templates', 'groups', 'users', 'years', 'parentTargets', 'units'));
    }

    /**
     * Hedefi Kaydeder
     */
    public function store(Request $request)
    {
        $user = Auth::user();
        if (!$this->permissionService->hasPermission($user, 'kpi_targets.create') && !$this->permissionService->hasPermission($user, 'kpi_targets.manage')) {
            abort(403, 'KPI hedefi oluşturma yetkiniz bulunmamaktadır.');
        }

        $validated = $request->validate([
            'form_template_id' => 'required|exists:form_templates,id',
            'target_field_id' => 'required|exists:form_fields,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'target_year' => 'required|integer|min:2020|max:2050',
            'assignee_type' => 'required|in:user,group,manager',
            'assignee_id' => 'nullable|integer',
            'assignee_ids' => 'nullable|array',
            'assignee_ids.*' => 'integer|exists:users,id',
            'target_value' => 'required|numeric|min:0.01',
            'unit' => 'required|string|max:50',
            'criteria' => 'nullable|array',
            'parent_target_id' => 'nullable|exists:kpi_targets,id',
            'distribute_evenly' => 'nullable|boolean',
            'status' => 'nullable|in:active,paused,completed,cancelled',
        ]);

        $isGlobalView = $this->permissionService->hasPermission($user, 'kpi_targets.view_all') ||
            $this->permissionService->hasPermission($user, 'kpi_targets.manage') ||
            $user->role?->slug === 'sistem_yoneticisi';

        $accessibleUserIds = $this->getAccessibleUsers($user)->pluck('id')->toArray();
        $accessibleGroupIds = $this->getAccessibleGroups($user)->pluck('id')->toArray();

        if ($validated['assignee_type'] === 'user') {
            $assigneeIds = $request->input('assignee_ids', []);
            if (empty($assigneeIds) && $request->filled('assignee_id')) {
                $assigneeIds = [(int) $request->input('assignee_id')];
            }

            if (empty($assigneeIds)) {
                return back()->withInput()->withErrors(['assignee_ids' => 'Lütfen hedefin atanacağı en az bir personel seçiniz.']);
            }

            // Kapsam dışındaki personele atamayı engelle
            if (!$isGlobalView) {
                foreach ($assigneeIds as $aId) {
                    if (!in_array($aId, $accessibleUserIds)) {
                        return back()->withInput()->withErrors(['assignee_ids' => 'Yetki kapsamınız dışındaki bir personele hedef atayamazsınız.']);
                    }
                }
            }

            // Birden fazla personel seçilmişse toplu olarak her biri için ayrı hedef oluştur
            if (count($assigneeIds) > 1) {
                $batchData = $validated;
                $batchData['distribute_evenly'] = $request->boolean('distribute_evenly', true);
                $createdTargets = $this->kpiService->createTargetsBatch($batchData, $assigneeIds, $user);

                return redirect()->route('kpi-targets.index')
                    ->with('success', 'Toplam ' . count($createdTargets) . ' personele KPI Hedefi başarıyla tanımlandı ve atandı.');
            }

            // Tek personel seçilmişse
            $validated['assignee_id'] = $assigneeIds[0];
            $target = $this->kpiService->createTarget($validated, $user);

            if ($request->boolean('distribute_evenly')) {
                return redirect()->route('kpi-targets.index')
                    ->with('success', 'KPI Hedefi başarıyla oluşturuldu ve aylara eşit olarak dağıtıldı.');
            }

            return redirect()->route('kpi-targets.distribute', $target->id)
                ->with('success', 'KPI Hedefi başarıyla oluşturuldu. Şimdi aylık periyot dağılımını belirleyebilir veya eşit bırakabilirsiniz.');
        }

        // Grup veya Yönetici ataması
        if (empty($validated['assignee_id'])) {
            return back()->withInput()->withErrors(['assignee_id' => 'Lütfen hedefin atanacağı grubu seçiniz.']);
        }

        if (!$isGlobalView && !in_array($validated['assignee_id'], $accessibleGroupIds)) {
            return back()->withInput()->withErrors(['assignee_id' => 'Yetki kapsamınız dışındaki bir gruba hedef atayamazsınız.']);
        }

        $target = $this->kpiService->createTarget($validated, $user);

        if ($request->boolean('distribute_evenly')) {
            return redirect()->route('kpi-targets.index')
                ->with('success', 'KPI Hedefi başarıyla oluşturuldu ve aylara eşit olarak dağıtıldı.');
        }

        return redirect()->route('kpi-targets.distribute', $target->id)
            ->with('success', 'KPI Hedefi başarıyla oluşturuldu. Şimdi aylık periyot dağılımını belirleyebilir veya eşit bırakabilirsiniz.');
    }

    /**
     * Hedef Düzenleme Ekranı
     */
    public function edit(KpiTargetM $kpiTarget)
    {
        $user = Auth::user();
        if (!$this->permissionService->hasPermission($user, 'kpi_targets.edit') && !$this->permissionService->hasPermission($user, 'kpi_targets.manage')) {
            abort(403, 'Bu hedefi düzenleme yetkiniz bulunmamaktadır.');
        }

        $this->authorizeTargetAccess($user, $kpiTarget);

        $kpiTarget->load(['template.fields', 'targetField', 'periods', 'parentTarget']);

        $templates = FormTemplateM::with('fields')->where('is_active', true)->orderBy('name')->get();
        $groups = $this->getAccessibleGroups($user);
        $users = $this->getAccessibleUsers($user);
        $years = range(date('Y') + 2, 2024);
        $parentTargets = KpiTargetM::where('id', '!=', $kpiTarget->id)->where('status', 'active')->orderBy('title')->get();

        // Şablonun Kriter Filtre Alanları
        $fields = $kpiTarget->template ? $kpiTarget->template->fields : collect();
        $filterFields = $fields->filter(function ($f) {
            return in_array($f->type, ['master_data', 'select', 'text', 'year', 'user_list'])
                || ($f->type === 'number' && !$f->is_summable);
        })->values();

        // Master Data Ağacı
        $masterDataTree = \App\Models\MasterDataTypeM::with('items')->get()->keyBy('id');

        // Master Data'dan Birim Listesi
        $unitType = \App\Models\MasterDataTypeM::where('name', 'BİRİM')->first();
        $units = $unitType ? $unitType->items()->orderBy('name')->pluck('name')->toArray() : ['Ton', 'Adet', 'KG', 'TL', 'USD', 'EUR'];

        // Kriter Rozetleri
        $criteriaBadges = $this->kpiService->getFormattedCriteriaBadges($kpiTarget->criteria_json);

        return view('kpi_targets.edit', compact(
            'kpiTarget',
            'templates',
            'groups',
            'users',
            'years',
            'parentTargets',
            'units',
            'filterFields',
            'masterDataTree',
            'criteriaBadges'
        ));
    }

    /**
     * Hedefi Günceller
     */
    public function update(Request $request, KpiTargetM $kpiTarget)
    {
        $user = Auth::user();
        if (!$this->permissionService->hasPermission($user, 'kpi_targets.edit') && !$this->permissionService->hasPermission($user, 'kpi_targets.manage')) {
            abort(403, 'Bu hedefi düzenleme yetkiniz bulunmamaktadır.');
        }

        $this->authorizeTargetAccess($user, $kpiTarget);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'target_year' => 'required|integer|min:2020|max:2050',
            'assignee_type' => 'required|in:user,group,manager',
            'assignee_id' => 'required|integer',
            'target_value' => 'required|numeric|min:0.01',
            'unit' => 'required|string|max:50',
            'criteria' => 'nullable|array',
            'parent_target_id' => 'nullable|exists:kpi_targets,id',
            'distribute_evenly' => 'nullable|boolean',
            'status' => 'required|in:active,paused,completed,cancelled',
        ]);

        $this->kpiService->updateTarget($kpiTarget, $validated, $user);

        return redirect()->route('kpi-targets.index')->with('success', 'KPI Hedefi başarıyla güncellendi.');
    }

    /**
     * Hedefi Siler
     */
    public function destroy(KpiTargetM $kpiTarget)
    {
        $user = Auth::user();
        if (!$this->permissionService->hasPermission($user, 'kpi_targets.delete') && !$this->permissionService->hasPermission($user, 'kpi_targets.manage')) {
            abort(403, 'Bu hedefi silme yetkiniz bulunmamaktadır.');
        }

        $this->authorizeTargetAccess($user, $kpiTarget);

        $this->kpiService->deleteTarget($kpiTarget, $user);

        return redirect()->route('kpi-targets.index')->with('success', 'KPI Hedefi başarıyla silindi.');
    }

    /**
     * 12 Aylık Dağıtım Ekranı
     */
    public function distributeForm(KpiTargetM $kpiTarget)
    {
        $user = Auth::user();
        if (!$this->permissionService->hasPermission($user, 'kpi_targets.distribute') && !$this->permissionService->hasPermission($user, 'kpi_targets.manage')) {
            abort(403, 'Hedef dağıtımı yapma yetkiniz bulunmamaktadır.');
        }

        $this->authorizeTargetAccess($user, $kpiTarget);

        $kpiTarget->load(['periods', 'template', 'targetField']);

        $periods = $kpiTarget->periods->keyBy('period_month');

        return view('kpi_targets.distribute', compact('kpiTarget', 'periods'));
    }

    /**
     * 12 Aylık Dağıtımı Kaydeder
     */
    public function distributeStore(Request $request, KpiTargetM $kpiTarget)
    {
        $user = Auth::user();
        if (!$this->permissionService->hasPermission($user, 'kpi_targets.distribute') && !$this->permissionService->hasPermission($user, 'kpi_targets.manage')) {
            abort(403, 'Hedef dağıtımı yapma yetkiniz bulunmamaktadır.');
        }

        $this->authorizeTargetAccess($user, $kpiTarget);

        $months = $request->input('months', []);
        $this->kpiService->distributePeriods($kpiTarget, $months, $user);

        return redirect()->route('kpi-targets.progress', $kpiTarget->id)
            ->with('success', 'Aylık hedef dağılımı başarıyla kaydedildi.');
    }

    /**
     * Hedefi 12 aya eşit olarak böler
     */
    public function distributeEvenly(KpiTargetM $kpiTarget)
    {
        $user = Auth::user();
        if (!$this->permissionService->hasPermission($user, 'kpi_targets.distribute') && !$this->permissionService->hasPermission($user, 'kpi_targets.manage')) {
            abort(403, 'Hedef dağıtımı yapma yetkiniz bulunmamaktadır.');
        }

        $this->authorizeTargetAccess($user, $kpiTarget);

        $this->kpiService->distributeEvenly($kpiTarget, $user);

        return back()->with('success', 'Hedef 12 aya eşit olarak dağıtıldı.');
    }

    /**
     * İlerleme ve Performans Grafiği (Chart.js)
     */
    public function progress(KpiTargetM $kpiTarget)
    {
        $user = Auth::user();
        $this->authorizeTargetAccess($user, $kpiTarget);

        $kpiTarget->load(['template', 'targetField', 'creator', 'parentTarget', 'childTargets']);

        $chartData = $this->kpiService->getProgressData($kpiTarget);
        $criteriaSummary = $this->kpiService->formatCriteriaSummary($kpiTarget->criteria_json);

        $canEdit = $this->permissionService->hasPermission($user, 'kpi_targets.edit') || $this->permissionService->hasPermission($user, 'kpi_targets.manage');
        $canDistribute = $this->permissionService->hasPermission($user, 'kpi_targets.distribute') || $this->permissionService->hasPermission($user, 'kpi_targets.manage');
        $canRecalculate = $this->permissionService->hasPermission($user, 'kpi_targets.recalculate') || $this->permissionService->hasPermission($user, 'kpi_targets.manage');

        return view('kpi_targets.progress', compact('kpiTarget', 'chartData', 'criteriaSummary', 'canEdit', 'canDistribute', 'canRecalculate'));
    }

    /**
     * Hedefin gerçekleşmesini anlık yeniden hesaplatır
     */
    public function recalculate(KpiTargetM $kpiTarget)
    {
        $user = Auth::user();
        if (!$this->permissionService->hasPermission($user, 'kpi_targets.recalculate') && !$this->permissionService->hasPermission($user, 'kpi_targets.manage')) {
            abort(403, 'Yeniden hesaplama yetkiniz bulunmamaktadır.');
        }

        $this->authorizeTargetAccess($user, $kpiTarget);

        $this->kpiService->calculateTargetRealization($kpiTarget);

        return back()->with('success', 'Hedef gerçekleşme verileri güncel form girişlerine göre başarıyla yeniden hesaplandı.');
    }

    /**
     * AJAX: Seçilen form şablonunun sayısal (hedef olabilecek) ve kırılım (master data) alanlarını döner.
     */
    public function getFields(FormTemplateM $template)
    {
        $fields = $template->fields()->orderBy('sort_order')->get();

        // 1. Hedef Alanı: Sadece toplanabilir (is_summable = 1) veya gerçek kümülatif sayısal metrikler (Tonaj, Teslimat Miktarı, Tutar vb.)
        $numericFields = $fields->filter(function ($f) {
            if ($f->is_summable) {
                return true;
            }
            return in_array($f->type, ['number', 'decimal'])
                && !in_array($f->type, ['select', 'master_data', 'date', 'year', 'text', 'user_list'])
                && !str_contains(strtolower($f->field_key), 'sap')
                && !str_contains(strtolower($f->field_key), 'kod')
                && !str_contains(strtolower($f->field_key), 'cap')
                && !str_contains(strtolower($f->field_key), 'no')
                && !str_contains(strtolower($f->field_key), 'id');
        })->values();

        // 2. Kriter / Kırılım Alanları: Filtre olarak kullanılabilecek tüm boyutlar (Master data, select, text vb.)
        $filterFields = $fields->filter(function ($f) {
            return in_array($f->type, ['master_data', 'select', 'text', 'year', 'user_list'])
                || ($f->type === 'number' && !$f->is_summable);
        })->values();

        // 3. Master Data Ağacı (Tüm tipler, parent_id bağımlılıkları ve öğeler)
        $masterDataTree = \App\Models\MasterDataTypeM::with('items')->get()->keyBy('id');

        return response()->json([
            'numeric_fields' => $numericFields,
            'filter_fields' => $filterFields,
            'master_data_tree' => $masterDataTree,
        ]);
    }

    /**
     * AJAX: Seçilen alanın master data veya dropdown seçeneklerini döner.
     */
    public function getFilterOptions(FormFieldM $field)
    {
        $options = [];

        if ($field->type === 'master_data' && $field->master_data_type_id) {
            $options = MasterDataItemM::where('type_id', $field->master_data_type_id)
                ->orderBy('name')
                ->get(['id', 'name', 'parent_id']);
        } elseif ($field->type === 'select' && !empty($field->options)) {
            $rawOptions = is_array($field->options) ? $field->options : json_decode($field->options, true);
            foreach ($rawOptions as $opt) {
                $options[] = ['id' => $opt, 'name' => $opt];
            }
        }

        return response()->json([
            'field_id' => $field->id,
            'label' => $field->label,
            'options' => $options,
        ]);
    }
}
