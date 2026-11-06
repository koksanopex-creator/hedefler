<?php

namespace App\Http\Controllers;

use App\Models\FormEntryM;
use App\Models\FormTemplateM;
use App\Models\FormRevisionRequestM;
use App\Services\FormEntryS;
use App\Services\PermissionS;
use App\Services\ActivityLogS;
use App\Services\FormScheduleS;
use App\Services\FormExportS;
use App\Services\SettingS;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use PhpOffice\PhpSpreadsheet\IOFactory;

class FormEntryC extends Controller
{
    protected FormEntryS $formEntryService;
    protected PermissionS $permissionService;
    protected ActivityLogS $activityLogService;
    protected FormScheduleS $formScheduleService;
    protected FormExportS $formExportService;

    public function __construct(
        FormEntryS $formEntryService,
        PermissionS $permissionService,
        ActivityLogS $activityLogService,
        FormScheduleS $formScheduleService,
        FormExportS $formExportService
    ) {
        $this->formEntryService = $formEntryService;
        $this->permissionService = $permissionService;
        $this->activityLogService = $activityLogService;
        $this->formScheduleService = $formScheduleService;
        $this->formExportService = $formExportService;
    }

    public function index(Request $request)
    {
        $user = $request->user() ?? Auth::user();
        $baseQuery = $this->formEntryService->getVisibleEntriesQuery($user);

        $statusCounts = [
            'all' => (clone $baseQuery)->count(),
            'submitted' => (clone $baseQuery)->where('status', 'submitted')->count(),
            'approved' => (clone $baseQuery)->where('status', 'approved')
                ->whereDoesntHave('revisionRequests', fn($q) => $q->where('status', 'pending'))
                ->count(),
            'draft' => (clone $baseQuery)->where('status', 'draft')->count(),
            'rejected' => (clone $baseQuery)->where('status', 'rejected')->count(),
            'revision_pending' => (clone $baseQuery)->whereHas('revisionRequests', fn($q) => $q->where('status', 'pending'))->count(),
        ];

        $query = clone $baseQuery;

        if ($request->filled('status')) {
            if ($request->status === 'revision_pending') {
                $query->whereHas('revisionRequests', fn($q) => $q->where('status', 'pending'));
            } elseif ($request->status === 'approved') {
                $query->where('status', 'approved')
                    ->whereDoesntHave('revisionRequests', fn($q) => $q->where('status', 'pending'));
            } else {
                $query->where('status', $request->status);
            }
        }

        if ($request->get('filter_date') === 'today') {
            $query->whereDate('entry_date', today());
        }

        $entries = $query->with(['template', 'user.group', 'revisionRequests', 'approver'])
            ->orderBy('entry_date', 'desc')
            ->paginate(20)
            ->withQueryString();

        $canViewApprover = $user ? $this->permissionService->hasPermission($user, 'form_entries.view_approver') : false;

        return view('form_entries.index', compact('entries', 'canViewApprover', 'statusCounts'));
    }

    public function selectTemplate(Request $request)
    {
        $accessibleIds = $this->formScheduleService->getAccessibleTemplateIds($request->user());
        $templates = FormTemplateM::with('schedules')->whereIn('id', $accessibleIds)->get();
        $canViewTargets = $this->permissionService->hasPermission($request->user(), 'form_templates.view_targets');

        return view('form_entries.select_template', compact('templates', 'canViewTargets'));
    }

    public function create(Request $request, FormTemplateM $formTemplate)
    {
        $user = $request->user();
        if (!$this->formScheduleService->canUserAccessTemplate($user, $formTemplate)) {
            abort(403, 'Bu form için erişim yetkiniz veya aktif bir takvim kuralınız bulunmuyor.');
        }

        // Mevcut şablonun alanlarını yükle
        $formTemplate->load('fields');
        $date = now()->format('Y-m-d');

        // Artık taslak aramıyoruz, "Yeni Veri Girişi" her seferinde yepyeni bir kayıt açar.
        $existingEntry = null;

        $masterDataTree = \App\Models\MasterDataTypeM::with('items')->get()->keyBy('id')->toArray();
        $currentUserScope = $this->getUserScope($user);
        $usersList = \App\Models\UserM::where('is_active', true)->orderBy('name', 'asc')->get(['id', 'name', 'group_id'])->toArray();
        $rawPinned = $user->preferences['pinned_columns'][(string) $formTemplate->id] ?? [];
        if (is_array($rawPinned) && !isset($rawPinned['left']) && !isset($rawPinned['right'])) {
            $pinnedColumns = [
                'left' => array_map('intval', $rawPinned),
                'right' => [],
            ];
        } else {
            $pinnedColumns = [
                'left' => array_map('intval', $rawPinned['left'] ?? []),
                'right' => array_map('intval', $rawPinned['right'] ?? []),
            ];
        }

        return view('form_entries.create', compact('formTemplate', 'date', 'existingEntry', 'masterDataTree', 'currentUserScope', 'usersList', 'pinnedColumns'));
    }

    public function store(Request $request, FormTemplateM $formTemplate)
    {
        if ($request->has('rows_json') && is_string($request->rows_json)) {
            $decoded = json_decode($request->rows_json, true);
            if (is_array($decoded)) {
                $request->merge(['rows' => $decoded]);
            }
        }

        $request->validate([
            'entry_date' => 'required|date',
            'rows' => 'required|array',
            'action' => 'required|in:draft,submit'
        ]);

        // Artık benzersizlik kısıtlaması yok, her form gönderimi yeni bir kayıt oluşturacak
        $existingEntry = null;

        $status = $request->action === 'submit' ? 'submitted' : 'draft';

        // Onaya gönderme durumunda zorunlu alan doğrulaması (Backend koruması)
        if ($status === 'submitted') {
            $formTemplate->loadMissing('fields');
            $requiredFields = $formTemplate->fields->where('is_required', true);

            $rowNum = 0;
            foreach ($request->rows as $rowData) {
                $rowNum++;
                // Satır tamamen boşsa atla
                $hasVal = false;
                foreach ($rowData as $val) {
                    if ($val !== null && trim((string) $val) !== '') {
                        $hasVal = true;
                        break;
                    }
                }
                if (!$hasVal && count($request->rows) > 1) {
                    continue;
                }

                foreach ($requiredFields as $reqField) {
                    if (!empty($reqField->is_calculated)) {
                        continue;
                    }
                    $v = $rowData[$reqField->id] ?? null;
                    if ($v === null || trim((string) $v) === '') {
                        return back()->withInput()->withErrors([
                            'rows' => "Formu onaya gönderebilmek için tüm zorunlu alanlar doldurulmalıdır (Satır {$rowNum}: [{$reqField->label}] alanı boş bırakılamaz)."
                        ]);
                    }
                }
            }
        }

        $entry = $this->formEntryService->saveEntry(
            $formTemplate,
            $request->user(),
            $request->entry_date,
            $request->rows,
            $status,
            $existingEntry
        );

        if ($status === 'draft') {
            return redirect()->route('form-entries.edit', $entry)->with('success', 'Taslak başarıyla kaydedildi.');
        }

        if ($status === 'submitted') {
            \App\Services\NotificationS::dispatch('form.submitted', [
                'user_name' => $request->user()->name,
                'form_name' => $formTemplate->name,
            ], $request->user(), route('form-entries.show', $entry));
        }

        return redirect()->route('form-entries.index')->with('success', 'Veri girişi kaydedildi.');
    }

    public function show(FormEntryM $formEntry)
    {
        $user = Auth::user();
        $isSuperAdmin = ($user->role && $user->role->slug === 'sistem_yoneticisi') || $this->permissionService->hasPermission($user, 'roles.manage') || $user->id === 1;

        // Yetki Kontrolü
        if (
            $formEntry->user_id !== $user->id &&
            !$isSuperAdmin &&
            !$this->permissionService->hasPermission($user, 'form_entries.view_all') &&
            !$this->permissionService->hasPermission($user, 'form_entries.view_group')
        ) {
            abort(403);
        }

        // Başkasının verisine bakıyorsa logla
        if ($formEntry->user_id !== $user->id) {
            $this->activityLogService->log('form_entry.view', 'Başka kullanıcının verisi görüntülendi.', $formEntry);
        }

        $formEntry->load(['template.fields', 'rows.values', 'user.group', 'revisionRequests.reviewer', 'revisionRequests.user', 'approver']);

        // Onay Yetkisi & Grup İzolasyonu Kontrolü
        $hasGlobalApprove = $this->permissionService->hasPermission($user, 'form_entries.approve');
        $hasGroupApprove = $this->permissionService->hasPermission($user, 'form_entries.approve_group');
        $isGroupManagerOfUser = $formEntry->user && $formEntry->user->group_id && $user->managedGroups()->where('groups.id', $formEntry->user->group_id)->exists();

        $isSelf = $formEntry->user_id === $user->id;
        $canApprove = (!$isSelf || $isSuperAdmin) && ($isSuperAdmin || $hasGlobalApprove || ($hasGroupApprove && $isGroupManagerOfUser));

        // Onayı Geri Alma Yetkisi
        $canRevert = $canApprove && in_array($formEntry->status, ['approved', 'rejected']);

        // Revizyon Talebi Sistemi Kontrolü
        $formRevisionEnabled = SettingS::get('form_revision_enabled', true);
        $pendingRevision = $formEntry->revisionRequests->firstWhere('status', 'pending');
        $rejectedRevision = $formEntry->revisionRequests->where('status', 'rejected')->where('is_dismissed_by_user', false)->first();
        $approvedRevision = $formEntry->revisionRequests->firstWhere('status', 'approved');

        $canRequestRevision = $formRevisionEnabled && ($isSelf || $isSuperAdmin) && $formEntry->status === 'approved' && !$pendingRevision;

        // Revizyon Taleplerini Yönetme Yetkisi (Sistem Yöneticisi veya form_entries.manage_revision_requests yetkili Grup Yöneticisi)
        $hasManageRevision = $this->permissionService->hasPermission($user, 'form_entries.manage_revision_requests');
        $canManageRevision = ($isSuperAdmin || ($hasManageRevision && ($isGroupManagerOfUser || $hasGlobalApprove))) && (!$isSelf || $isSuperAdmin);

        $canViewLogs = $this->permissionService->hasPermission($user, 'form_entries.view_logs');
        $canViewHistory = $isSuperAdmin || $this->permissionService->hasPermission($user, 'form_entries.view_history');
        $canViewApprover = $isSuperAdmin || $this->permissionService->hasPermission($user, 'form_entries.view_approver');
        $logs = collect();
        $historyLogs = collect();

        // Onaya Gönderme ve Geri Çekme Yetkileri (Form sahibi veya Superadmin)
        $canSubmitApproval = ($isSelf || $isSuperAdmin) && $formEntry->status === 'draft';
        $canWithdraw = ($isSelf || $isSuperAdmin) && in_array($formEntry->status, ['draft', 'submitted']);

        if ($canViewLogs || $canViewHistory) {
            $baseLogsQuery = \App\Models\ActivityLogM::where('model_type', FormEntryM::class)
                ->where('model_id', $formEntry->id)
                ->with(['user.role', 'user.group'])
                ->orderBy('created_at', 'desc');

            if ($canViewLogs) {
                $logs = (clone $baseLogsQuery)->get();
            }

            if ($canViewHistory) {
                // Tarihçe modalı için görüntüleme (view) dışındaki gerçek işlem ve durum değişiklikleri
                $historyLogs = (clone $baseLogsQuery)
                    ->where('action', '!=', 'form_entry.view')
                    ->get();
            }
        }

        return view('form_entries.show', compact(
            'formEntry',
            'logs',
            'historyLogs',
            'canViewLogs',
            'canViewHistory',
            'canViewApprover',
            'canApprove',
            'canRevert',
            'canRequestRevision',
            'canManageRevision',
            'pendingRevision',
            'rejectedRevision',
            'approvedRevision',
            'formRevisionEnabled',
            'isSuperAdmin',
            'isSelf',
            'canSubmitApproval',
            'canWithdraw'
        ));
    }

    public function edit(FormEntryM $formEntry)
    {
        $user = Auth::user();
        $isSuperAdmin = ($user->role && $user->role->slug === 'sistem_yoneticisi') || $this->permissionService->hasPermission($user, 'roles.manage') || $user->id === 1;

        // Yetki kontrolü: Onaylanmışsa sadece Sistem Yöneticisi doğrudan düzenleyebilir.
        if ($formEntry->status === 'approved') {
            if (!$isSuperAdmin) {
                abort(403, 'Onaylanmış bir formu sadece Sistem Yöneticisi doğrudan düzenleyebilir. Düzenleme yapabilmek için lütfen form detayından "Düzenleme Talep Et (Revizyon)" butonunu kullanınız.');
            }
        } else {
            $hasGlobalApprove = $this->permissionService->hasPermission($user, 'form_entries.approve');
            $hasGroupApprove = $this->permissionService->hasPermission($user, 'form_entries.approve_group');
            $isGroupManagerOfUser = $formEntry->user && $formEntry->user->group_id && $user->managedGroups()->where('groups.id', $formEntry->user->group_id)->exists();

            if ($formEntry->user_id !== $user->id && !$isSuperAdmin && !$hasGlobalApprove && !($hasGroupApprove && $isGroupManagerOfUser)) {
                abort(403, 'Bu taslağı düzenleme yetkiniz yok.');
            }
        }

        // Snapshot alanlarını yükle
        $formEntry->load(['template.fields', 'rows.values']);

        $formTemplate = $formEntry->template;
        $date = $formEntry->entry_date->format('Y-m-d');
        $existingEntry = $formEntry;

        $masterDataTree = \App\Models\MasterDataTypeM::with('items')->get()->keyBy('id')->toArray();
        $currentUserScope = $this->getUserScope($user);
        $usersList = \App\Models\UserM::where('is_active', true)->orderBy('name', 'asc')->get(['id', 'name', 'group_id'])->toArray();
        $rawPinned = $user->preferences['pinned_columns'][(string) $formTemplate->id] ?? [];
        if (is_array($rawPinned) && !isset($rawPinned['left']) && !isset($rawPinned['right'])) {
            $pinnedColumns = [
                'left' => array_map('intval', $rawPinned),
                'right' => [],
            ];
        } else {
            $pinnedColumns = [
                'left' => array_map('intval', $rawPinned['left'] ?? []),
                'right' => array_map('intval', $rawPinned['right'] ?? []),
            ];
        }

        return view('form_entries.create', compact('formTemplate', 'date', 'existingEntry', 'masterDataTree', 'currentUserScope', 'usersList', 'pinnedColumns'));
    }

    public function update(Request $request, FormEntryM $formEntry)
    {
        $user = Auth::user();
        $isSuperAdmin = ($user->role && $user->role->slug === 'sistem_yoneticisi') || $this->permissionService->hasPermission($user, 'roles.manage') || $user->id === 1;

        if ($formEntry->status === 'approved') {
            if (!$isSuperAdmin) {
                abort(403, 'Onaylanmış bir formu sadece Sistem Yöneticisi doğrudan güncelleyebilir.');
            }
        } else {
            $hasGlobalApprove = $this->permissionService->hasPermission($user, 'form_entries.approve');
            $hasGroupApprove = $this->permissionService->hasPermission($user, 'form_entries.approve_group');
            $isGroupManagerOfUser = $formEntry->user && $formEntry->user->group_id && $user->managedGroups()->where('groups.id', $formEntry->user->group_id)->exists();

            if ($formEntry->user_id !== $user->id && !$isSuperAdmin && !$hasGlobalApprove && !($hasGroupApprove && $isGroupManagerOfUser)) {
                abort(403, 'Bu kaydı güncelleme yetkiniz yok.');
            }
        }

        if ($request->has('rows_json') && is_string($request->rows_json)) {
            $decoded = json_decode($request->rows_json, true);
            if (is_array($decoded)) {
                $request->merge(['rows' => $decoded]);
            }
        }

        $request->validate([
            'entry_date' => 'required|date',
            'rows' => 'required|array',
            'action' => 'required|in:draft,submit'
        ]);

        // Düzenleme öncesi mevcut alan değerlerini haritalandır (Detaylı Diff / Audit Trail için)
        $formEntry->loadMissing(['template.fields', 'rows.values']);
        $fieldsMap = $formEntry->template->fields->pluck('label', 'id')->toArray();

        $oldRowsData = [];
        foreach ($formEntry->rows as $rIdx => $r) {
            foreach ($r->values as $v) {
                $oldRowsData[$rIdx][$v->form_field_id] = $v->value;
            }
        }

        $status = $request->action === 'submit' ? 'submitted' : 'draft';

        // Onaya gönderme durumunda zorunlu alan doğrulaması (Backend koruması)
        if ($status === 'submitted') {
            $formEntry->loadMissing('template.fields');
            $requiredFields = $formEntry->template->fields->where('is_required', true);

            $rowNum = 0;
            foreach ($request->rows as $rowData) {
                $rowNum++;
                // Satır tamamen boşsa atla
                $hasVal = false;
                foreach ($rowData as $val) {
                    if ($val !== null && trim((string) $val) !== '') {
                        $hasVal = true;
                        break;
                    }
                }
                if (!$hasVal && count($request->rows) > 1) {
                    continue;
                }

                foreach ($requiredFields as $reqField) {
                    if (!empty($reqField->is_calculated)) {
                        continue;
                    }
                    $v = $rowData[$reqField->id] ?? null;
                    if ($v === null || trim((string) $v) === '') {
                        return back()->withInput()->withErrors([
                            'rows' => "Formu onaya gönderebilmek için tüm zorunlu alanlar doldurulmalıdır (Satır {$rowNum}: [{$reqField->label}] alanı boş bırakılamaz)."
                        ]);
                    }
                }
            }
        }
        if ($formEntry->status === 'approved' && $isSuperAdmin) {
            $status = 'approved';
        }

        $this->formEntryService->saveEntry(
            $formEntry->template,
            $formEntry->user,
            $request->entry_date,
            $request->rows,
            $status,
            $formEntry
        );

        // Değişiklikleri kalem kalem tespit et (Field-level diffing)
        $formEntry->load('rows.values');
        $fieldDiffs = [];
        foreach ($formEntry->rows as $rIdx => $newRow) {
            $rowNum = $rIdx + 1;
            foreach ($newRow->values as $newVal) {
                $fId = $newVal->form_field_id;
                $fLabel = $fieldsMap[$fId] ?? "Alan #{$fId}";
                $oldVal = $oldRowsData[$rIdx][$fId] ?? '';
                $currVal = $newVal->value ?? '';
                if ((string) $oldVal !== (string) $currVal) {
                    $oldDisplay = $oldVal === '' ? '(Boş)' : $oldVal;
                    $currDisplay = $currVal === '' ? '(Boş)' : $currVal;
                    $fieldDiffs[] = "Satır {$rowNum} [{$fLabel}]: '{$oldDisplay}' ➔ '{$currDisplay}'";
                }
            }
        }

        $logDesc = 'Form verisi düzenlendi ve güncellendi.';
        if (!empty($fieldDiffs)) {
            $logDesc .= "\nDeğiştirilen Alanlar:\n• " . implode("\n• ", $fieldDiffs);
        }

        $this->activityLogService->log('form_entry.update', $logDesc, $formEntry);

        if ($status === 'submitted') {
            \App\Services\NotificationS::dispatch('form.submitted', [
                'user_name' => $formEntry->user->name,
                'form_name' => $formEntry->template->name,
            ], $formEntry->user, route('form-entries.show', $formEntry));
        }

        if ($status === 'draft') {
            return redirect()->route('form-entries.edit', $formEntry)->with('success', 'Taslak başarıyla güncellendi.');
        }

        return redirect()->route('form-entries.show', $formEntry)->with('success', 'Veri başarıyla güncellendi.');
    }

    public function review(Request $request, FormEntryM $formEntry)
    {
        $user = $request->user();
        $isSuperAdmin = ($user->role && $user->role->slug === 'sistem_yoneticisi') || $this->permissionService->hasPermission($user, 'roles.manage') || $user->id === 1;

        // Yetki ve Grup İzolasyonu Kontrolü (Model 1)
        $hasGlobalApprove = $this->permissionService->hasPermission($user, 'form_entries.approve');
        $hasGroupApprove = $this->permissionService->hasPermission($user, 'form_entries.approve_group');
        $isGroupManagerOfUser = $formEntry->user && $formEntry->user->group_id && $user->managedGroups()->where('groups.id', $formEntry->user->group_id)->exists();

        if (!$isSuperAdmin && !$hasGlobalApprove && !($hasGroupApprove && $isGroupManagerOfUser)) {
            abort(403, 'Sadece sorumlu olduğunuz departman/gruptaki personellerin form girişlerini onaylama veya reddetme yetkiniz bulunmaktadır.');
        }

        // Kendi formunu onaylama engeli
        if ($formEntry->user_id === $user->id && !$isSuperAdmin) {
            abort(403, 'Kendi doldurduğunuz form girişini onaylayamaz veya reddedemezsiniz.');
        }

        $request->validate([
            'status' => 'required|in:approved,rejected',
            'rejection_reason' => 'required_if:status,rejected|nullable|string|min:3'
        ], [
            'rejection_reason.required_if' => 'Formu reddederken lütfen personele iletilmek üzere geçerli bir red gerekçesi belirtiniz.',
            'rejection_reason.min' => 'Red gerekçesi en az 3 karakter olmalıdır.',
        ]);

        $formEntry->status = $request->status;
        if ($request->status === 'rejected') {
            $formEntry->rejection_reason = $request->rejection_reason;
            $formEntry->approved_at = null;
            $formEntry->approved_by = $user->id; // Reddeden yönetici
        } else if ($request->status === 'approved') {
            $formEntry->approved_at = now();
            $formEntry->approved_by = $user->id;
            $formEntry->rejection_reason = null;
        }
        $formEntry->save();

        if ($request->status === 'approved') {
            $this->activityLogService->log('form_entry.approved', 'Form girişi incelendi ve ONAYLANDI.', $formEntry);

            // KPI Hedef Gerçekleşmelerini Anlık Güncelle
            app(\App\Services\KpiTargetS::class)->recalculateForEntry($formEntry);

            \App\Services\NotificationS::dispatch('form.approved', [
                'user_name' => $formEntry->user->name,
                'form_name' => $formEntry->template->name,
            ], $formEntry->user, route('form-entries.show', $formEntry));
        } elseif ($request->status === 'rejected') {
            $this->activityLogService->log('form_entry.rejected', 'Form girişi REDDEDİLDİ. Gerekçe: ' . $request->rejection_reason, $formEntry);

            // Reddedildiğinde de hedefleri güncelle (daha önce onaylıysa geri alsın)
            app(\App\Services\KpiTargetS::class)->recalculateForEntry($formEntry);

            \App\Services\NotificationS::dispatch('form.rejected', [
                'user_name' => $formEntry->user->name,
                'form_name' => $formEntry->template->name,
                'rejection_reason' => $formEntry->rejection_reason,
            ], $formEntry->user, route('form-entries.show', $formEntry));
        }

        return redirect()->route('form-entries.show', $formEntry)->with('success', 'Form onay durumu başarıyla güncellendi.');
    }

    /**
     * Onayı veya Reddi Geri Al (İncelemeye Çek)
     */
    public function revertReview(Request $request, FormEntryM $formEntry)
    {
        $user = $request->user();
        $isSuperAdmin = ($user->role && $user->role->slug === 'sistem_yoneticisi') || $this->permissionService->hasPermission($user, 'roles.manage') || $user->id === 1;

        $hasGlobalApprove = $this->permissionService->hasPermission($user, 'form_entries.approve');
        $hasGroupApprove = $this->permissionService->hasPermission($user, 'form_entries.approve_group');
        $isGroupManagerOfUser = $formEntry->user && $formEntry->user->group_id && $user->managedGroups()->where('groups.id', $formEntry->user->group_id)->exists();

        if (!$isSuperAdmin && !$hasGlobalApprove && !($hasGroupApprove && $isGroupManagerOfUser)) {
            abort(403, 'Bu formun onay durumunu geri alma yetkiniz bulunmamaktadır.');
        }

        if (!in_array($formEntry->status, ['approved', 'rejected'])) {
            return back()->with('error', 'Sadece onaylanmış veya reddedilmiş formların onayı geri alınabilir.');
        }

        $prevStatus = $formEntry->status;
        $formEntry->status = 'submitted';
        $formEntry->approved_at = null;
        $formEntry->approved_by = null;
        $formEntry->rejection_reason = null;
        $formEntry->save();

        if ($prevStatus === 'approved') {
            // KPI gerçekleşmelerini geri çek (actuals düşürülür)
            app(\App\Services\KpiTargetS::class)->recalculateForEntry($formEntry);
        }

        $this->activityLogService->log(
            'form_entry.revert',
            'Form onay/red kararı geri alındı. Form durumu tekrar "Onay Bekliyor (submitted)" seviyesine çekildi.',
            $formEntry
        );

        return redirect()->route('form-entries.show', $formEntry)->with('success', 'Form durumu başarıyla geri alındı ve form tekrar onay bekliyor durumuna getirildi.');
    }

    /**
     * Taslak halindeki formu doğrudan show sayfasından onaya gönderir.
     */
    public function submitApproval(Request $request, FormEntryM $formEntry)
    {
        $user = $request->user();
        $isSuperAdmin = ($user->role && $user->role->slug === 'sistem_yoneticisi') || $this->permissionService->hasPermission($user, 'roles.manage') || $user->id === 1;

        if ($formEntry->user_id !== $user->id && !$isSuperAdmin) {
            abort(403, 'Bu formu sadece formu oluşturan kullanıcı veya Sistem Yöneticisi onaya gönderebilir.');
        }

        if ($formEntry->status !== 'draft') {
            return back()->with('error', 'Sadece taslak durumundaki formlar onaya gönderilebilir.');
        }

        // Zorunlu alan kontrolü
        $formEntry->loadMissing(['template.fields', 'rows.values']);
        $requiredFields = $formEntry->template->fields->where('is_required', true);

        $rowNum = 0;
        foreach ($formEntry->rows as $row) {
            $rowNum++;
            $rowValues = $row->values->pluck('value', 'form_field_id')->toArray();

            // Satır tamamen boşsa ve birden fazla satır varsa atla
            $hasVal = false;
            foreach ($rowValues as $val) {
                if ($val !== null && trim((string) $val) !== '') {
                    $hasVal = true;
                    break;
                }
            }
            if (!$hasVal && $formEntry->rows->count() > 1) {
                continue;
            }

            foreach ($requiredFields as $reqField) {
                if (!empty($reqField->is_calculated)) {
                    continue;
                }
                $v = $rowValues[$reqField->id] ?? null;
                if ($v === null || trim((string) $v) === '') {
                    return back()->with('error', "Formu onaya gönderebilmek için tüm zorunlu alanlar doldurulmalıdır (Satır {$rowNum}: [{$reqField->label}] alanı boş bırakılamaz). Lütfen 'Düzenle' butonunu kullanarak eksik alanları tamamlayınız.");
                }
            }
        }

        $formEntry->status = 'submitted';
        $formEntry->submitted_at = now();
        $formEntry->save();

        $this->activityLogService->log('form_entry.submit', 'Form detay sayfasından onaya gönderildi.', $formEntry);

        \App\Services\NotificationS::dispatch('form.submitted', [
            'user_name' => $formEntry->user->name,
            'form_name' => $formEntry->template->name,
        ], $formEntry->user, route('form-entries.show', $formEntry));

        return redirect()->route('form-entries.show', $formEntry)->with('success', 'Form başarıyla onaya gönderildi.');
    }

    /**
     * Formu onay sürecinden geri çeker (taslağa alır) veya taslak kaydını sistemden geri çeker (siler).
     */
    public function withdraw(Request $request, FormEntryM $formEntry)
    {
        $user = $request->user();
        $isSuperAdmin = ($user->role && $user->role->slug === 'sistem_yoneticisi') || $this->permissionService->hasPermission($user, 'roles.manage') || $user->id === 1;

        if ($formEntry->user_id !== $user->id && !$isSuperAdmin) {
            abort(403, 'Bu formu sadece formu oluşturan kullanıcı veya Sistem Yöneticisi geri çekebilir.');
        }

        // 1. Durum: Form onay bekliyorsa (submitted) -> Onay sürecinden geri çekip tekrar taslak yapar
        if ($formEntry->status === 'submitted') {
            $formEntry->status = 'draft';
            $formEntry->submitted_at = null;
            $formEntry->save();

            $this->activityLogService->log(
                'form_entry.withdrawn',
                'Form onay sürecinden geri çekildi ve tekrar taslak durumuna alındı.',
                $formEntry
            );

            return redirect()->route('form-entries.show', $formEntry)->with('success', 'Form onay sürecinden başarıyla geri çekildi ve taslak durumuna alındı. Dilediğiniz gibi düzenleyebilirsiniz.');
        }

        // 2. Durum: Form taslak durumundaysa (draft) -> Taslağı geri çeker (siler)
        if ($formEntry->status === 'draft') {
            $formEntryName = $formEntry->template->name;
            $formEntry->rows()->delete();
            $formEntry->delete();

            $this->activityLogService->log(
                'form_entry.draft_deleted',
                "Taslak form ({$formEntryName}) sistemden geri çekildi (silindi).",
                null
            );

            return redirect()->route('form-entries.index')->with('success', 'Taslak form başarıyla sistemden geri çekildi.');
        }

        return back()->with('error', 'Sadece taslak veya onay bekleyen formlar geri çekilebilir.');
    }

    /**
     * Personel: Onaylı Form İçin Düzenleme (Revizyon) Talebi İlet
     */
    public function requestRevision(Request $request, FormEntryM $formEntry)
    {
        $user = $request->user();
        $isSuperAdmin = ($user->role && $user->role->slug === 'sistem_yoneticisi') || $this->permissionService->hasPermission($user, 'roles.manage') || $user->id === 1;

        if (!SettingS::get('form_revision_enabled', true)) {
            abort(403, 'Form revizyon / düzenleme talebi özelliği sistem genelinde kapatılmıştır.');
        }

        if ($formEntry->user_id !== $user->id && !$isSuperAdmin) {
            abort(403, 'Sadece kendi formunuz için düzenleme talebinde bulunabilirsiniz.');
        }

        if ($formEntry->status !== 'approved') {
            return back()->with('error', 'Sadece onaylanmış formlar için düzenleme (revizyon) talebinde bulunabilirsiniz.');
        }

        // Bekleyen talep var mı?
        $pending = $formEntry->revisionRequests()->where('status', 'pending')->first();
        if ($pending) {
            return back()->with('warning', 'Bu form için zaten inceleme bekleyen bir revizyon talebiniz bulunmaktadır.');
        }

        $request->validate([
            'reason' => 'required|string|min:5|max:1000',
        ], [
            'reason.required' => 'Lütfen düzenleme (revizyon) gerekçesini belirtiniz.',
            'reason.min' => 'Gerekçe en az 5 karakter olmalıdır.',
        ]);

        $revRequest = FormRevisionRequestM::create([
            'form_entry_id' => $formEntry->id,
            'user_id' => $user->id,
            'reason' => $request->reason,
            'status' => 'pending',
        ]);

        $this->activityLogService->log(
            'form_entry.revision_requested',
            'Form için düzenleme (revizyon) talebi iletildi. Gerekçe: ' . $request->reason,
            $formEntry
        );

        $formEntry->loadMissing(['user.group', 'template']);
        $owner = $formEntry->user;
        $groupId = $owner?->group_id ?? $user->group_id;

        \App\Services\NotificationS::dispatch('form.revision_requested', [
            'user_name' => $user->name,
            'requester_name' => $user->name,
            'form_name' => $formEntry->template->name,
            'revision_reason' => $request->reason,
            'entry_date' => $formEntry->entry_date->format('d.m.Y'),
            'group_id' => $groupId,
        ], $user, route('form-entries.show', $formEntry));

        return redirect()->route('form-entries.show', $formEntry)->with('success', 'Düzenleme talebiniz yöneticinize iletilmiştir. Onaylandığında form düzenlemeye açılacaktır.');
    }

    /**
     * Yönetici / Admin: Düzenleme Talebini Onayla veya Reddet
     */
    public function reviewRevision(Request $request, FormRevisionRequestM $revisionRequest)
    {
        $user = $request->user();
        $formEntry = $revisionRequest->formEntry;
        $isSuperAdmin = ($user->role && $user->role->slug === 'sistem_yoneticisi') || $this->permissionService->hasPermission($user, 'roles.manage') || $user->id === 1;

        $hasManageRevision = $this->permissionService->hasPermission($user, 'form_entries.manage_revision_requests');
        $hasGlobalApprove = $this->permissionService->hasPermission($user, 'form_entries.approve');
        $isGroupManagerOfUser = $formEntry->user && $formEntry->user->group_id && $user->managedGroups()->where('groups.id', $formEntry->user->group_id)->exists();

        if (!$isSuperAdmin && !($hasManageRevision && ($isGroupManagerOfUser || $hasGlobalApprove))) {
            abort(403, 'Düzenleme taleplerini değerlendirme yetkiniz bulunmamaktadır.');
        }

        $request->validate([
            'action' => 'required|in:approve,reject',
            'rejection_reason' => 'required_if:action,reject|nullable|string|min:3|max:1000',
        ], [
            'rejection_reason.required_if' => 'Düzenleme talebini reddederken red gerekçesi belirtilmesi zorunludur.',
            'rejection_reason.min' => 'Red gerekçesi en az 3 karakter olmalıdır.',
        ]);

        if ($request->action === 'approve') {
            $revisionRequest->update([
                'status' => 'approved',
                'reviewed_by' => $user->id,
                'reviewed_at' => now(),
            ]);

            // Formun onayını kaldır ve submitted (düzenlenebilir) duruma al
            $formEntry->status = 'submitted';
            $formEntry->approved_at = null;
            $formEntry->approved_by = null;
            $formEntry->save();

            // KPI hedeflerini geçici olarak güncelle (onay kalktı)
            app(\App\Services\KpiTargetS::class)->recalculateForEntry($formEntry);

            $this->activityLogService->log(
                'form_entry.revision_approved',
                'Form düzenleme (revizyon) talebi onaylandı. Form personel için düzenlemeye açıldı.',
                $formEntry
            );

            \App\Services\NotificationS::dispatch('form.revision_approved', [
                'user_name' => $formEntry->user->name,
                'form_name' => $formEntry->template->name,
            ], $formEntry->user, route('form-entries.edit', $formEntry));

            return redirect()->route('form-entries.show', $formEntry)->with('success', 'Düzenleme talebi onaylandı. Form kullanıcının düzenlemesine açıldı.');
        } else {
            $revisionRequest->update([
                'status' => 'rejected',
                'rejection_reason' => $request->rejection_reason,
                'reviewed_by' => $user->id,
                'reviewed_at' => now(),
                'is_dismissed_by_user' => false,
            ]);

            $this->activityLogService->log(
                'form_entry.revision_rejected',
                'Form düzenleme talebi reddedildi. Red Gerekçesi: ' . $request->rejection_reason,
                $formEntry
            );

            \App\Services\NotificationS::dispatch('form.revision_rejected', [
                'user_name' => $formEntry->user->name,
                'form_name' => $formEntry->template->name,
                'rejection_reason' => $request->rejection_reason,
            ], $formEntry->user, route('form-entries.show', $formEntry));

            return redirect()->route('form-entries.show', $formEntry)->with('success', 'Düzenleme talebi reddedildi.');
        }
    }

    /**
     * Personel: Reddedilen Revizyon Uyarısını Kapat (Gizle)
     */
    public function dismissRevisionRejection(FormRevisionRequestM $revisionRequest)
    {
        $user = Auth::user();
        $isSuperAdmin = ($user->role && $user->role->slug === 'sistem_yoneticisi') || $this->permissionService->hasPermission($user, 'roles.manage') || $user->id === 1;

        if ($revisionRequest->user_id !== $user->id && !$isSuperAdmin) {
            abort(403);
        }

        $revisionRequest->update(['is_dismissed_by_user' => true]);

        return back()->with('info', 'Bilgilendirme kapatıldı.');
    }

    public function destroy(Request $request, FormEntryM $formEntry)
    {
        $user = $request->user();
        if (!$this->permissionService->hasPermission($user, 'form_entries.delete')) {
            abort(403, 'Form kaydını silme yetkiniz yok.');
        }

        $template = $formEntry->template;
        $wasApproved = $formEntry->status === 'approved';
        $entryYear = intval($formEntry->entry_date->format('Y'));
        $templateId = $formEntry->form_template_id;
        $formEntryId = $formEntry->id;

        $this->activityLogService->log('form_entry.delete', 'Form verisi (tüm satırlarıyla birlikte) silindi.', $formEntry);

        \App\Services\NotificationS::dispatch('form.deleted', [
            'form_name' => $template->name,
        ]);

        $formEntry->delete();

        if ($wasApproved) {
            $targets = \App\Models\KpiTargetM::where('form_template_id', $templateId)
                ->where('target_year', $entryYear)
                ->where('status', 'active')
                ->get();
            $kpiS = app(\App\Services\KpiTargetS::class);
            foreach ($targets as $target) {
                $kpiS->calculateTargetRealization($target);
            }
        }

        $previousUrl = $request->headers->get('referer') ?? url()->previous();
        // Eğer silinen kaydın kendi show detay sayfasındaysa form-entries.index sayfasına yönlendir
        if ($previousUrl && str_contains($previousUrl, '/form-entries/' . $formEntryId)) {
            return redirect()->route('form-entries.index')->with('success', 'Veri kaydı başarıyla silindi.');
        }

        // Kullanıcı form-entries listesindeyse veya başka bir sayfadaysa aynı sayfada kal
        return redirect()->back(fallback: route('form-entries.index'))->with('success', 'Veri kaydı başarıyla silindi.');
    }

    public function export(Request $request, FormEntryM $formEntry)
    {
        $user = Auth::user();

        // Sıkı Yetki Kontrolü: Kesinlikle exports.manage yetkisi olmalı. 
        if (!$this->permissionService->hasPermission($user, 'exports.manage')) {
            abort(403, 'Bu Excel çıktısını alma yetkiniz (exports.manage) bulunmamaktadır.');
        }

        $this->activityLogService->log('form_entry.export', 'Form verisi Excel olarak indirildi.', $formEntry);

        $filters = $request->input('filters', []);

        return $this->formExportService->exportToExcel($formEntry, $filters);
    }

    public function downloadEmptyTemplate(FormTemplateM $formTemplate)
    {
        return $this->formExportService->exportEmptyTemplate($formTemplate);
    }

    public function importExcel(Request $request, FormTemplateM $formTemplate)
    {
        $request->validate([
            'excel_file' => 'required|file|mimes:xlsx,xls'
        ]);

        try {
            $file = $request->file('excel_file');
            $spreadsheet = IOFactory::load($file->getPathname());
            $sheet = $spreadsheet->getActiveSheet();

            $templateId = $formTemplate->id;
            $templatePath = storage_path("app/templates/template_{$templateId}.xlsx");

            // Eğer özel tasarım varsa, 6. satırdan başlar; yoksa 2. satırdan
            $startRow = file_exists($templatePath) ? 6 : 2;
            $highestRow = $sheet->getHighestDataRow();

            $fields = $formTemplate->fields;
            $rowsData = [];

            for ($row = $startRow; $row <= $highestRow; $row++) {
                // Eğer A sütununda 'TOPLAM:' veya 'Form Adı:' yazıyorsa okumayı bitir (metadata bölümü)
                $firstColVal = (string) $sheet->getCell('A' . $row)->getValue();
                if (str_starts_with($firstColVal, 'TOPLAM:') || str_starts_with($firstColVal, 'Form Adı:')) {
                    break;
                }

                $rowData = [];
                // B sütunundan başla (Sıra No = A, İlk Alan = B)
                $colIndex = 2;
                $isEmptyRow = true;

                foreach ($fields as $field) {
                    $cellValue = $sheet->getCell([$colIndex, $row])->getFormattedValue();

                    // Hesaplama alanı falan olabilir diye raw değere de bakalım
                    $rawValue = $sheet->getCell([$colIndex, $row])->getValue();
                    if ($rawValue instanceof \PhpOffice\PhpSpreadsheet\RichText\RichText) {
                        $rawValue = $rawValue->getPlainText();
                    }

                    $val = (string) $rawValue;

                    if (trim($val) !== '') {
                        $isEmptyRow = false;
                    }

                    $rowData[$field->id] = trim($val);
                    $colIndex++;
                }

                // Eğer satır tamamen boşsa ekleme
                if (!$isEmptyRow) {
                    $rowsData[] = [
                        'id' => uniqid(),
                        'values' => $rowData
                    ];
                }
            }

            return response()->json([
                'success' => true,
                'rows' => $rowsData
            ]);

        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Excel okunurken bir hata oluştu: ' . $e->getMessage()
            ], 500);
        }
    }

    public function getUserScope(\App\Models\UserM $user): array
    {
        // 1. Sistem Yöneticisi veya form_entries.view_all yetkisine sahipse tam yetkili
        if ($user->role?->slug === 'sistem_yoneticisi' || $this->permissionService->hasPermission($user, 'form_entries.view_all')) {
            return [
                'is_admin' => true,
                'is_manager' => true,
                'allowed_user_ids' => [],
                'allowed_user_names' => [],
            ];
        }

        // 2. Grup Yöneticisi mi? (managedGroups var veya form_entries.view_group yetkisi var)
        $managedGroupIds = $user->managedGroups()->pluck('groups.id')->toArray();
        if (empty($managedGroupIds) && $user->group_id && ($user->role?->slug === 'veri_giris_yoneticisi' || $this->permissionService->hasPermission($user, 'form_entries.view_group'))) {
            $managedGroupIds = [$user->group_id];
        }

        if (!empty($managedGroupIds) || $this->permissionService->hasPermission($user, 'form_entries.view_group')) {
            $groupUsers = \App\Models\UserM::whereIn('group_id', $managedGroupIds)->get();
            $userIds = $groupUsers->pluck('id')->push($user->id)->unique()->values()->toArray();
            $userNames = $groupUsers->pluck('name')->push($user->name)->unique()->values()->toArray();

            return [
                'is_admin' => false,
                'is_manager' => true,
                'allowed_user_ids' => $userIds,
                'allowed_user_names' => $userNames,
            ];
        }

        // 3. Normal Veri Girişi kullanıcısı: Sadece kendi adı ve ID'si
        return [
            'is_admin' => false,
            'is_manager' => false,
            'allowed_user_ids' => [$user->id],
            'allowed_user_names' => [$user->name],
        ];
    }

    /**
     * Kullanıcının form şablonuna özel sabitlenmiş (pinned) sütun tercihlerini veritabanına kaydeder.
     */
    public function savePinnedColumns(Request $request)
    {
        $request->validate([
            'template_id' => 'required|integer',
            'pinned_columns' => 'nullable|array',
            'pinned_column_ids' => 'nullable|array',
        ]);

        $user = Auth::user();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Oturum açılmamış.'], 401);
        }

        $preferences = $user->preferences ?? [];
        if (!isset($preferences['pinned_columns']) || !is_array($preferences['pinned_columns'])) {
            $preferences['pinned_columns'] = [];
        }

        if ($request->has('pinned_columns')) {
            $raw = $request->pinned_columns;
            $preferences['pinned_columns'][(string) $request->template_id] = [
                'left' => array_map('intval', array_values($raw['left'] ?? [])),
                'right' => array_map('intval', array_values($raw['right'] ?? [])),
            ];
        } elseif ($request->has('pinned_column_ids')) {
            $preferences['pinned_columns'][(string) $request->template_id] = [
                'left' => array_map('intval', array_values($request->pinned_column_ids)),
                'right' => [],
            ];
        }

        $user->preferences = $preferences;
        $user->save();

        return response()->json([
            'success' => true,
            'template_id' => $request->template_id,
            'pinned_columns' => $preferences['pinned_columns'][(string) $request->template_id],
        ]);
    }
}
