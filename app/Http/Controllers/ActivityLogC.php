<?php

namespace App\Http\Controllers;

use App\Models\ActivityLogM;
use App\Models\FormEntryM;
use App\Models\FormTemplateM;
use App\Models\KpiTargetM;
use App\Models\UserM;
use App\Services\ActivityLogS;
use Illuminate\Http\Request;
use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;

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

        // İlişkili modelleri N+1 yapmadan tek seferde yükle
        $collection = $logs->getCollection();
        $formEntryIds = $collection->filter(function ($item) {
            return in_array($item->model_type, [FormEntryM::class, 'App\\Models\\FormEntryM', 'FormEntryM']);
        })->pluck('model_id')->filter()->unique();

        $kpiTargetIds = $collection->filter(function ($item) {
            return in_array($item->model_type, [KpiTargetM::class, 'App\\Models\\KpiTargetM', 'KpiTargetM']);
        })->pluck('model_id')->filter()->unique();

        $userIds = $collection->filter(function ($item) {
            return in_array($item->model_type, [UserM::class, 'App\\Models\\UserM', 'UserM']);
        })->pluck('model_id')->filter()->unique();

        $formTemplateIds = $collection->filter(function ($item) {
            return in_array($item->model_type, [FormTemplateM::class, 'App\\Models\\FormTemplateM', 'FormTemplateM']);
        })->pluck('model_id')->filter()->unique();

        $relatedFormEntries = FormEntryM::with(['template', 'user'])->whereIn('id', $formEntryIds)->get()->keyBy('id');
        $relatedKpiTargets = KpiTargetM::whereIn('id', $kpiTargetIds)->get()->keyBy('id');
        $relatedUsers = UserM::whereIn('id', $userIds)->get()->keyBy('id');
        $relatedFormTemplates = FormTemplateM::whereIn('id', $formTemplateIds)->get()->keyBy('id');

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
            'relatedFormEntries',
            'relatedKpiTargets',
            'relatedUsers',
            'relatedFormTemplates',
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

    /**
     * Filtrelenmiş veya tüm sistem denetim loglarını Excel (.xlsx) olarak arşivler/indirir.
     */
    public function archiveLogs(Request $request)
    {
        $search = $request->get('search');
        $userId = $request->get('user_id');
        $action = $request->get('action');
        $dateFrom = $request->get('date_from');
        $dateTo = $request->get('date_to');

        $query = ActivityLogM::with(['user.role', 'user.group'])
            ->orderBy('created_at', 'desc');

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

        if ($userId !== null && $userId !== '') {
            if ($userId === 'system') {
                $query->whereNull('user_id');
            } else {
                $query->where('user_id', $userId);
            }
        }

        if ($action) {
            $query->where('action', $action);
        }

        if ($dateFrom) {
            $query->whereDate('created_at', '>=', Carbon::parse($dateFrom)->startOfDay());
        }
        if ($dateTo) {
            $query->whereDate('created_at', '<=', Carbon::parse($dateTo)->endOfDay());
        }

        // Güvenli limit: en fazla 10.000 kayıt export edilir
        $logs = $query->limit(10000)->get();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Denetim İzi Arşivi');

        // Başlıklar
        $headers = [
            'ID',
            'Tarih / Saat',
            'Kullanıcı Adı',
            'Kullanıcı E-Posta',
            'Kullanıcı Rolü',
            'Kullanıcı Grubu',
            'İşlem Kodu',
            'İşlem Başlığı',
            'Model / Hedef Türü',
            'Kayıt ID',
            'Detay / Açıklama',
            'IP Adresi',
        ];

        $col = 1;
        foreach ($headers as $h) {
            $cell = Coordinate::stringFromColumnIndex($col) . '1';
            $sheet->setCellValue($cell, $h);
            $col++;
        }

        // Başlık stili
        $highestCol = Coordinate::stringFromColumnIndex(count($headers));
        $headerRange = "A1:{$highestCol}1";
        $sheet->getStyle($headerRange)->getFont()->setBold(true)->getColor()->setARGB('FFFFFFFF');
        $sheet->getStyle($headerRange)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FF1E293B'); // slate-800
        $sheet->getStyle($headerRange)->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->getRowDimension(1)->setRowHeight(28);

        // Satırlar
        $row = 2;
        foreach ($logs as $log) {
            $meta = ActivityLogS::getActionMeta($log->action ?? '');
            $sheet->setCellValue('A' . $row, $log->id);
            $sheet->setCellValue('B' . $row, $log->created_at ? $log->created_at->format('d.m.Y H:i:s') : '-');
            $sheet->setCellValue('C' . $row, $log->user?->name ?? 'Sistem / Anonim');
            $sheet->setCellValue('D' . $row, $log->user?->email ?? '-');
            $sheet->setCellValue('E' . $row, $log->user?->role?->name ?? '-');
            $sheet->setCellValue('F' . $row, $log->user?->group?->name ?? '-');
            $sheet->setCellValue('G' . $row, $log->action ?? '-');
            $sheet->setCellValue('H' . $row, $meta['label'] ?? ($log->action ?? '-'));
            $sheet->setCellValue('I' . $row, $log->model_type ? class_basename($log->model_type) : '-');
            $sheet->setCellValue('J' . $row, $log->model_id ?? '-');
            $sheet->setCellValue('K' . $row, $log->description ?? '-');
            $sheet->setCellValue('L' . $row, $log->ip_address ?? '-');

            $sheet->getStyle('K' . $row)->getAlignment()->setWrapText(true);
            $row++;
        }

        // Auto-fit sütun genişlikleri
        for ($i = 1; $i <= count($headers); $i++) {
            $colLetter = Coordinate::stringFromColumnIndex($i);
            if ($colLetter === 'K') {
                $sheet->getColumnDimension($colLetter)->setWidth(50);
            } else {
                $sheet->getColumnDimension($colLetter)->setAutoSize(true);
            }
        }

        // Log export işlemi
        ActivityLogS::logAction(
            'activity_log.export',
            "Sistem denetim günlüğü kayıtları Excel olarak arşivlendi/indirildi (Toplam: " . $logs->count() . " Kayıt)."
        );

        $fileName = 'Denetim_Izi_Arsiv_' . Carbon::now()->format('Ymd_His') . '.xlsx';

        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, $fileName, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Cache-Control' => 'max-age=0',
        ]);
    }

    /**
     * Sistem loglarını seçili kapsama göre temizler.
     * Yönetim rolü (Read-only) kesinlikle silemez. Sadece Sistem Yöneticisi yetkilidir.
     */
    public function clearLogs(Request $request)
    {
        $user = $request->user();

        if (!$user || $user->isYonetim() || (!$user->isSuperAdmin() && !$user->hasPermission('settings.manage'))) {
            abort(403, 'Denetim loglarını silme yetkiniz bulunmamaktadır. Bu işlem sadece Sistem Yöneticisi tarafından gerçekleştirilebilir.');
        }

        $request->validate([
            'scope' => 'required|in:30_days,90_days,180_days,all',
        ]);

        $scope = $request->input('scope');
        $query = ActivityLogM::query();
        $scopeLabel = '';

        if ($scope === '30_days') {
            $query->where('created_at', '<', Carbon::now()->subDays(30));
            $scopeLabel = 'Son 30 Günden Eski Kayıtlar';
        } elseif ($scope === '90_days') {
            $query->where('created_at', '<', Carbon::now()->subDays(90));
            $scopeLabel = 'Son 90 Günden Eski Kayıtlar';
        } elseif ($scope === '180_days') {
            $query->where('created_at', '<', Carbon::now()->subDays(180));
            $scopeLabel = 'Son 180 Günden Eski Kayıtlar';
        } elseif ($scope === 'all') {
            $scopeLabel = 'Tüm Zamanların Logları';
        }

        $deletedCount = $query->count();
        $query->delete();

        // Temizleme hareketini denetim günlüğüne kaydet
        ActivityLogS::logAction(
            'activity_log.clear',
            "Sistem denetim günlüğü kayıtları temizlendi. Kapsam: [{$scopeLabel}], Silinen Kayıt Sayısı: {$deletedCount}"
        );

        return redirect()->route('settings.activity_logs.index')->with(
            'success',
            "Seçilen kapsamdaki ({$scopeLabel}) {$deletedCount} adet log kaydı başarıyla temizlendi."
        );
    }
}
