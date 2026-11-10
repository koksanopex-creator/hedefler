<?php

namespace App\Http\Controllers;

use App\Models\FormTemplateM;
use App\Models\FormFieldM;
use App\Models\ReportRuleM;
use App\Models\ReportScheduleM;
use App\Models\UserM;
use App\Models\GroupM;
use App\Services\ReportEngineS;
use App\Services\ReportExportS;
use App\Services\PermissionS;
use App\Mail\ScheduledReportMail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class ReportC extends Controller
{
    protected ReportEngineS $engine;
    protected ReportExportS $exporter;
    protected PermissionS $permissionService;

    public function __construct(ReportEngineS $engine, ReportExportS $exporter, PermissionS $permissionService)
    {
        $this->engine = $engine;
        $this->exporter = $exporter;
        $this->permissionService = $permissionService;
    }

    /**
     * Raporlama Merkezi Ana Sayfası
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        if (!$this->permissionService->hasPermission($user, 'reports.view')) {
            abort(403, 'Bu sayfayı görüntüleme yetkiniz bulunmamaktadır.');
        }

        // 1. Erişilebilir Form Şablonları (Alanı olan aktif şablonlar önce listelenir)
        $templates = FormTemplateM::where('is_active', true)
            ->with(['fields' => fn($q) => $q->orderBy('sort_order')])
            ->withCount('fields')
            ->orderByDesc('fields_count')
            ->orderBy('name')
            ->get();

        // 2. Kayıtlı Rapor Varyant Kuralları
        $rulesQuery = ReportRuleM::with(['template', 'creator'])
            ->orderBy('title');

        if (!$user->isSuperAdmin() && !$user->isYonetim()) {
            $rulesQuery->where(function($q) use ($user) {
                $q->where('is_public', true)
                  ->orWhere('created_by', $user->id);
            });
        }
        $savedRules = $rulesQuery->get();

        // 3. Filtreleme İçin Kullanıcılar ve Gruplar
        // Kural: Sistem yöneticisi hariç tutulur; yönetim rolündekiler ise sadece fiilen veri girişi / satışı varsa listelenir.
        $usersQuery = UserM::with(['group', 'role'])
            ->where('is_active', true)
            ->where('approval_status', 'approved')
            ->where('id', '!=', 1)
            ->whereHas('role', function($q) {
                $q->where('slug', '!=', 'sistem_yoneticisi');
            })
            ->where(function($q) {
                $q->whereHas('role', function($rq) {
                    $rq->where('slug', '!=', 'yonetim');
                })
                ->orWhereHas('formEntries');
            })
            ->orderBy('name');
        $groups = GroupM::where('is_active', true)->orderBy('name')->get();

        $canViewAll = $this->permissionService->hasPermission($user, 'form_entries.view_all') ||
                      $user->isSuperAdmin() || $user->isYonetim();

        if (!$canViewAll) {
            if ($this->permissionService->hasPermission($user, 'form_entries.view_group') && $user->group_id) {
                $usersQuery->where('group_id', $user->group_id);
            } else {
                $usersQuery->where('id', $user->id);
            }
        }
        $users = $usersQuery->get();

        // 4. Zamanlanmış Raporlar
        $schedules = collect();
        if ($this->permissionService->hasPermission($user, 'reports.schedule')) {
            $schedulesQuery = ReportScheduleM::with(['rule.template', 'creator', 'logs' => fn($q) => $q->latest()->limit(5)])
                ->latest();
            if (!$user->isSuperAdmin() && !$user->isYonetim()) {
                $schedulesQuery->where('created_by', $user->id);
            }
            $schedules = $schedulesQuery->get();
        }

        // Eğer URL'den belirli bir rule_id veya template_id geldiyse
        $initialRuleId = $request->query('rule_id');
        $selectedRule = $initialRuleId ? ReportRuleM::find($initialRuleId) : null;
        $requestedTemplateId = $request->query('template_id');

        if ($selectedRule) {
            $initialTemplate = $selectedRule->template;
        } elseif ($requestedTemplateId && $templates->firstWhere('id', $requestedTemplateId)) {
            $initialTemplate = $templates->firstWhere('id', $requestedTemplateId);
        } else {
            // Alanı tanımlı aktif şablonu öncelikli seç (boş test şablonları yerine)
            $initialTemplate = $templates->firstWhere(fn($t) => $t->fields->count() > 0) ?: $templates->first();
        }
        $initialTemplateId = $initialTemplate?->id;

        $initialDimensions = $initialTemplate ? $this->engine->getAvailableDimensions($initialTemplate) : [];
        $initialMetrics = $initialTemplate ? $this->engine->getAvailableMetrics($initialTemplate) : [];

        $templatesMeta = [];
        foreach ($templates as $tpl) {
            $templatesMeta[$tpl->id] = [
                'dimensions' => $this->engine->getAvailableDimensions($tpl),
                'metrics' => $this->engine->getAvailableMetrics($tpl),
                'filter_fields' => $tpl->fields
                    ->whereIn('type', ['select', 'master_data', 'user_list', 'year'])
                    ->values(),
            ];
        }

        $statusOptions = \App\Services\ReportEngineS::getStatusMeta();

        return view('reports.index', compact(
            'templates',
            'savedRules',
            'users',
            'groups',
            'schedules',
            'selectedRule',
            'initialTemplateId',
            'initialDimensions',
            'initialMetrics',
            'templatesMeta',
            'statusOptions'
        ));
    }

    /**
     * Şablonun Boyut ve Metrik Bilgilerini Döner (AJAX)
     */
    public function templateMeta(FormTemplateM $template)
    {
        $user = Auth::user();
        if (!$this->permissionService->hasPermission($user, 'reports.view')) {
            return response()->json(['error' => 'Yetkisiz erişim'], 403);
        }

        $dimensions = $this->engine->getAvailableDimensions($template);
        $metrics = $this->engine->getAvailableMetrics($template);

        // Dinamik alan filtreleri için filtreleme seçenekleri
        $filterFields = $template->fields()
            ->whereIn('type', ['select', 'master_data', 'user_list', 'year'])
            ->orderBy('sort_order')
            ->get(['id', 'label', 'type', 'master_data_type_id', 'options_list']);

        return response()->json([
            'dimensions' => $dimensions,
            'metrics' => $metrics,
            'filter_fields' => $filterFields,
        ]);
    }

    /**
     * Dinamik Rapor Sorgusu (AJAX)
     */
    public function query(Request $request)
    {
        $user = Auth::user();
        if (!$this->permissionService->hasPermission($user, 'reports.view')) {
            return response()->json(['error' => 'Yetkisiz erişim'], 403);
        }

        $mode = $request->input('analysis_mode', 'standard');

        try {
            if ($mode === 'yoy') {
                $result = $this->engine->generateYoYReport($request->all(), $user);
            } elseif ($mode === 'geo') {
                $result = $this->engine->generateGeoReport($request->all(), $user);
            } elseif ($mode === 'segment') {
                $result = $this->engine->generateSegmentBenchmarkReport($request->all(), $user);
            } elseif ($mode === 'multi_pivot') {
                $result = $this->engine->generateMultiPivotReport($request->all(), $user);
            } else {
                $validated = $request->validate([
                    'form_template_id' => 'required|integer|exists:form_templates,id',
                    'dimension_x_type' => 'required|string',
                    'dimension_x_field_id' => 'nullable|integer',
                    'dimension_series_type' => 'nullable|string',
                    'dimension_series_field_id' => 'nullable|integer',
                    'metric_field_id' => 'nullable|integer',
                    'metric_aggregation' => 'required|string|in:sum,avg,count,min,max',
                    'chart_type' => 'required|string|in:bar,horizontalBar,stackedBar,line,pie,doughnut,radar',
                    'filters' => 'nullable|array',
                    'top_n' => 'nullable|integer|min:0|max:100',
                ]);
                $result = $this->engine->generateReport($validated, $user);
            }
            return response()->json($result);
        } catch (\Throwable $e) {
            Log::error('Report Engine Error: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response()->json(['error' => 'Rapor hesaplanırken bir hata oluştu: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Yeni Rapor Varyant Kuralı Kaydet
     */
    public function storeRule(Request $request)
    {
        $user = Auth::user();
        if (!$this->permissionService->hasPermission($user, 'reports.create')) {
            return response()->json(['error' => 'Rapor kaydetme yetkiniz bulunmamaktadır.'], 403);
        }

        $validated = $request->validate([
            'title' => 'required|string|max:150',
            'description' => 'nullable|string|max:500',
            'form_template_id' => 'required|integer|exists:form_templates,id',
            'dimension_x_type' => 'required|string',
            'dimension_x_field_id' => 'nullable|integer',
            'dimension_series_type' => 'nullable|string',
            'dimension_series_field_id' => 'nullable|integer',
            'metric_field_id' => 'nullable|integer',
            'metric_aggregation' => 'required|string|in:sum,avg,count,min,max',
            'chart_type' => 'required|string',
            'filters' => 'nullable|array',
            'is_public' => 'nullable|boolean',
        ]);

        $rule = ReportRuleM::create([
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'form_template_id' => $validated['form_template_id'],
            'dimension_x_type' => $validated['dimension_x_type'],
            'dimension_x_field_id' => $validated['dimension_x_field_id'] ?? null,
            'dimension_series_type' => $validated['dimension_series_type'] ?? 'none',
            'dimension_series_field_id' => $validated['dimension_series_field_id'] ?? null,
            'metric_field_id' => $validated['metric_field_id'] ?? null,
            'metric_aggregation' => $validated['metric_aggregation'],
            'chart_type' => $validated['chart_type'],
            'filters' => $validated['filters'] ?? [],
            'is_public' => (bool)($validated['is_public'] ?? false),
            'created_by' => $user->id,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Rapor varyantı başarıyla kaydedildi.',
            'rule' => $rule->load('template'),
        ]);
    }

    /**
     * Mevcut Varyantı Güncelle
     */
    public function updateRule(Request $request, ReportRuleM $rule)
    {
        $user = Auth::user();
        if (!$this->permissionService->hasPermission($user, 'reports.create')) {
            return response()->json(['error' => 'Yetkisiz erişim.'], 403);
        }

        if ($rule->created_by !== $user->id && !$user->isSuperAdmin()) {
            return response()->json(['error' => 'Bu raporu düzenleme yetkiniz yok.'], 403);
        }

        $validated = $request->validate([
            'title' => 'required|string|max:150',
            'description' => 'nullable|string|max:500',
            'dimension_x_type' => 'required|string',
            'dimension_x_field_id' => 'nullable|integer',
            'dimension_series_type' => 'nullable|string',
            'dimension_series_field_id' => 'nullable|integer',
            'metric_field_id' => 'nullable|integer',
            'metric_aggregation' => 'required|string|in:sum,avg,count,min,max',
            'chart_type' => 'required|string',
            'filters' => 'nullable|array',
            'is_public' => 'nullable|boolean',
        ]);

        $rule->update([
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'dimension_x_type' => $validated['dimension_x_type'],
            'dimension_x_field_id' => $validated['dimension_x_field_id'] ?? null,
            'dimension_series_type' => $validated['dimension_series_type'] ?? 'none',
            'dimension_series_field_id' => $validated['dimension_series_field_id'] ?? null,
            'metric_field_id' => $validated['metric_field_id'] ?? null,
            'metric_aggregation' => $validated['metric_aggregation'],
            'chart_type' => $validated['chart_type'],
            'filters' => $validated['filters'] ?? [],
            'is_public' => (bool)($validated['is_public'] ?? false),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Rapor varyantı başarıyla güncellendi.',
            'rule' => $rule->load('template'),
        ]);
    }

    /**
     * Rapor Varyantını Sil
     */
    public function destroyRule(ReportRuleM $rule)
    {
        $user = Auth::user();
        if (!$this->permissionService->hasPermission($user, 'reports.create')) {
            return response()->json(['error' => 'Yetkisiz işlem.'], 403);
        }

        if ($rule->created_by !== $user->id && !$user->isSuperAdmin()) {
            return response()->json(['error' => 'Bu raporu silme yetkiniz yok.'], 403);
        }

        $rule->delete();

        return response()->json([
            'success' => true,
            'message' => 'Rapor varyantı silindi.',
        ]);
    }

    /**
     * Excel Olarak İndir (.xlsx)
     */
    public function exportExcel(Request $request)
    {
        $user = Auth::user();
        if (!$this->permissionService->hasPermission($user, 'reports.export')) {
            abort(403, 'Excel dışa aktarma yetkiniz bulunmamaktadır.');
        }

        $params = $request->all();
        $reportTitle = $params['report_title'] ?? 'Dinamik Analiz Raporu';
        $mode = $params['analysis_mode'] ?? 'standard';

        if ($mode === 'yoy') {
            $reportResult = $this->engine->generateYoYReport($params, $user);
            $spreadsheet = $this->exporter->exportYoYToSpreadsheet($reportResult, $reportTitle);
        } elseif ($mode === 'geo') {
            $reportResult = $this->engine->generateGeoReport($params, $user);
            $spreadsheet = $this->exporter->exportGeoToSpreadsheet($reportResult, $reportTitle);
        } elseif ($mode === 'segment') {
            $reportResult = $this->engine->generateSegmentBenchmarkReport($params, $user);
            $spreadsheet = $this->exporter->exportSegmentToSpreadsheet($reportResult, $reportTitle);
        } elseif ($mode === 'multi_pivot') {
            $reportResult = $this->engine->generateMultiPivotReport($params, $user);
            $spreadsheet = $this->exporter->exportMultiPivotToSpreadsheet($reportResult, $reportTitle);
        } else {
            $reportResult = $this->engine->generateReport($params, $user);
            $spreadsheet = $this->exporter->exportPivotToSpreadsheet($reportResult, $reportTitle);
        }

        $safeName = \Illuminate\Support\Str::slug($reportTitle) . '_' . date('Y-m-d_Hi') . '.xlsx';
        return $this->exporter->streamDownload($spreadsheet, $safeName);
    }

    /**
     * PDF / Yazdır Görünümü
     */
    public function print(Request $request)
    {
        $user = Auth::user();
        if (!$this->permissionService->hasPermission($user, 'reports.view')) {
            abort(403, 'Yetkisiz erişim');
        }

        $params = $request->all();
        $reportTitle = $params['report_title'] ?? 'Dinamik Analiz Raporu';
        $mode = $params['analysis_mode'] ?? 'standard';

        if ($mode === 'yoy') {
            $reportResult = $this->engine->generateYoYReport($params, $user);
        } elseif ($mode === 'geo') {
            $reportResult = $this->engine->generateGeoReport($params, $user);
        } elseif ($mode === 'segment') {
            $reportResult = $this->engine->generateSegmentBenchmarkReport($params, $user);
        } elseif ($mode === 'multi_pivot') {
            $reportResult = $this->engine->generateMultiPivotReport($params, $user);
        } else {
            $reportResult = $this->engine->generateReport($params, $user);
        }

        return view('reports.print', compact('reportResult', 'reportTitle', 'mode'));
    }

    /**
     * Anlık E-Posta ile Paylaş
     */
    public function share(Request $request)
    {
        $user = Auth::user();
        if (!$this->permissionService->hasPermission($user, 'reports.share')) {
            return response()->json(['error' => 'Rapor paylaşma yetkiniz bulunmamaktadır.'], 403);
        }

        $request->validate([
            'emails' => 'required|string',
            'report_title' => 'required|string|max:150',
            'form_template_id' => 'required|integer',
        ]);

        $rawEmails = preg_split('/[;, \n\r]+/', (string)$request->input('emails'));
        $validEmails = [];
        foreach ($rawEmails as $e) {
            $clean = trim($e);
            if (filter_var($clean, FILTER_VALIDATE_EMAIL)) {
                $validEmails[] = $clean;
            }
        }

        if (empty($validEmails)) {
            return response()->json(['error' => 'Geçerli en az bir e-posta adresi giriniz.'], 422);
        }

        try {
            $params = $request->all();
            $reportTitle = $params['report_title'];
            $mode = $params['analysis_mode'] ?? 'standard';

            if ($mode === 'yoy') {
                $reportResult = $this->engine->generateYoYReport($params, $user);
                $spreadsheet = $this->exporter->exportYoYToSpreadsheet($reportResult, $reportTitle);
            } elseif ($mode === 'geo') {
                $reportResult = $this->engine->generateGeoReport($params, $user);
                $spreadsheet = $this->exporter->exportGeoToSpreadsheet($reportResult, $reportTitle);
            } elseif ($mode === 'segment') {
                $reportResult = $this->engine->generateSegmentBenchmarkReport($params, $user);
                $spreadsheet = $this->exporter->exportSegmentToSpreadsheet($reportResult, $reportTitle);
            } elseif ($mode === 'multi_pivot') {
                $reportResult = $this->engine->generateMultiPivotReport($params, $user);
                $spreadsheet = $this->exporter->exportMultiPivotToSpreadsheet($reportResult, $reportTitle);
            } else {
                $reportResult = $this->engine->generateReport($params, $user);
                $spreadsheet = $this->exporter->exportPivotToSpreadsheet($reportResult, $reportTitle);
            }

            $tempExcel = $this->exporter->saveToTempFile($spreadsheet, 'paylasim');

            $mailable = new ScheduledReportMail(
                reportTitle: $reportTitle,
                reportResult: $reportResult,
                reportUrl: route('reports.index', ['template_id' => $params['form_template_id']]),
                scheduleFrequencyName: "{$user->name} Tarafından Paylaşılan Rapor",
                excelFilePath: $tempExcel,
                attachmentName: \Illuminate\Support\Str::slug($reportTitle) . '.xlsx'
            );

            Mail::to($validEmails)->send($mailable);

            if (file_exists($tempExcel)) {
                @unlink($tempExcel);
            }

            return response()->json([
                'success' => true,
                'message' => 'Rapor başarıyla ' . count($validEmails) . ' kişiye e-posta olarak iletildi.',
            ]);
        } catch (\Throwable $e) {
            Log::error('Rapor Paylaşım Hatası: ' . $e->getMessage());
            return response()->json(['error' => 'E-posta gönderilemedi: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Zamanlanmış Rapor Görevi Oluştur
     */
    public function storeSchedule(Request $request)
    {
        $user = Auth::user();
        if (!$this->permissionService->hasPermission($user, 'reports.schedule')) {
            return response()->json(['error' => 'Zamanlanmış rapor tanımlama yetkiniz bulunmamaktadır.'], 403);
        }

        $validated = $request->validate([
            'report_rule_id' => 'required|integer|exists:report_rules,id',
            'title' => 'required|string|max:150',
            'frequency' => 'required|string|in:hourly,daily,weekly,monthly',
            'time_of_day' => 'nullable|string',
            'day_of_week' => 'nullable|integer|min:1|max:7',
            'day_of_month' => 'nullable|integer|min:1|max:31',
            'recipient_type' => 'required|string|in:management,group_managers,specific_users,custom_emails',
            'recipient_user_ids' => 'nullable|array',
            'custom_emails' => 'nullable|string',
        ]);

        $schedule = new ReportScheduleM();
        $schedule->report_rule_id = $validated['report_rule_id'];
        $schedule->title = $validated['title'];
        $schedule->frequency = $validated['frequency'];
        $schedule->time_of_day = $validated['time_of_day'] ?? '08:00';
        $schedule->day_of_week = $validated['day_of_week'] ?? 1;
        $schedule->day_of_month = $validated['day_of_month'] ?? 1;
        $schedule->recipient_type = $validated['recipient_type'];
        $schedule->recipient_user_ids = $validated['recipient_user_ids'] ?? [];
        $schedule->custom_emails = $validated['custom_emails'] ?? null;
        $schedule->is_active = true;
        $schedule->created_by = $user->id;

        // İlk çalışma zamanını belirle
        $now = Carbon::now();
        $timeStr = $schedule->time_of_day ?: '08:00';
        [$hour, $minute] = explode(':', $timeStr . ':00');

        $schedule->next_run_at = match ($schedule->frequency) {
            'hourly' => $now->copy()->addHour()->startOfHour(),
            'daily' => $now->copy()->setTime((int)$hour, (int)$minute, 0)->isPast()
                ? $now->copy()->addDay()->setTime((int)$hour, (int)$minute, 0)
                : $now->copy()->setTime((int)$hour, (int)$minute, 0),
            'weekly' => $now->copy()->next((int)($schedule->day_of_week ?? 1))->setTime((int)$hour, (int)$minute, 0),
            'monthly' => $now->copy()->setDay((int)($schedule->day_of_month ?? 1))->setTime((int)$hour, (int)$minute, 0)->isPast()
                ? $now->copy()->addMonth()->setDay((int)($schedule->day_of_month ?? 1))->setTime((int)$hour, (int)$minute, 0)
                : $now->copy()->setDay((int)($schedule->day_of_month ?? 1))->setTime((int)$hour, (int)$minute, 0),
            default => $now->copy()->addDay(),
        };

        $schedule->save();

        return response()->json([
            'success' => true,
            'message' => 'Otomatik rapor zamanlaması başarıyla kuruldu.',
            'schedule' => $schedule->load('rule'),
        ]);
    }

    /**
     * Zamanlanmış Raporu Aç/Kapat (Aktif/Pasif)
     */
    public function toggleSchedule(ReportScheduleM $schedule)
    {
        $user = Auth::user();
        if (!$this->permissionService->hasPermission($user, 'reports.schedule')) {
            return response()->json(['error' => 'Yetkisiz işlem'], 403);
        }

        $schedule->update(['is_active' => !$schedule->is_active]);

        return response()->json([
            'success' => true,
            'is_active' => $schedule->is_active,
            'message' => $schedule->is_active ? 'Zamanlama aktif edildi.' : 'Zamanlama duraklatıldı.',
        ]);
    }

    /**
     * Zamanlanmış Raporu Sil
     */
    public function destroySchedule(ReportScheduleM $schedule)
    {
        $user = Auth::user();
        if (!$this->permissionService->hasPermission($user, 'reports.schedule')) {
            return response()->json(['error' => 'Yetkisiz işlem'], 403);
        }

        $schedule->delete();

        return response()->json([
            'success' => true,
            'message' => 'Zamanlama başarıyla silindi.',
        ]);
    }
}

