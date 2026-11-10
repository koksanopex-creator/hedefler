<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ReportScheduleM;
use App\Models\ReportScheduleLogM;
use App\Models\UserM;
use App\Models\GroupM;
use App\Services\ReportEngineS;
use App\Services\ReportExportS;
use App\Mail\ScheduledReportMail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class DispatchScheduledReports extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'reports:dispatch-scheduled {--force : Zaman kontrolü yapmadan tüm aktif kuralları çalıştır}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Zamanlanmış dinamik raporları çalıştırır ve ilgililere e-posta ile iletir.';

    /**
     * Execute the console command.
     */
    public function handle(ReportEngineS $engine, ReportExportS $exporter): int
    {
        $this->info('Zamanlanmış raporlar taranıyor...');

        $query = ReportScheduleM::where('is_active', true)
            ->with(['rule.template.fields', 'creator']);

        if (!$this->option('force')) {
            $query->where(function($q) {
                $q->whereNull('next_run_at')
                  ->orWhere('next_run_at', '<=', now());
            });
        }

        $schedules = $query->get();

        if ($schedules->isEmpty()) {
            $this->info('Çalıştırılacak zamanlanmış rapor bulunamadı.');
            return 0;
        }

        $this->info("Toplam {$schedules->count()} adet zamanlanmış kural bulundu.");

        foreach ($schedules as $schedule) {
            $startTime = microtime(true);
            $rule = $schedule->rule;

            if (!$rule || !$rule->template) {
                $this->warn("Kural ID #{$schedule->id} için geçerli şablon/kural bulunamadı. Atlanıyor.");
                continue;
            }

            $this->line("Rapor hazırlanıyor: [ID: {$schedule->id}] '{$schedule->title}' (Kural: {$rule->title})");

            try {
                // 1. Alıcıları Belirle
                $recipientEmails = $this->resolveRecipients($schedule);

                if (empty($recipientEmails)) {
                    $this->warn("Kural #{$schedule->id} için hiçbir alıcı bulunamadı. Atlandı.");
                    continue;
                }

                // 2. Raporu Hesapla
                $params = [
                    'form_template_id' => $rule->form_template_id,
                    'dimension_x_type' => $rule->dimension_x_type,
                    'dimension_x_field_id' => $rule->dimension_x_field_id,
                    'dimension_series_type' => $rule->dimension_series_type,
                    'dimension_series_field_id' => $rule->dimension_series_field_id,
                    'metric_field_id' => $rule->metric_field_id,
                    'metric_aggregation' => $rule->metric_aggregation,
                    'chart_type' => $rule->chart_type,
                    'filters' => $rule->filters ?? [],
                ];

                $reportResult = $engine->generateReport($params, $schedule->creator);

                // 3. Excel Dosyasını Oluştur
                $spreadsheet = $exporter->exportPivotToSpreadsheet($reportResult, $schedule->title);
                $tempExcelPath = $exporter->saveToTempFile($spreadsheet, 'zamanlanmis_rapor_' . $schedule->id);

                // 4. E-Posta Gönderimi
                $reportUrl = route('reports.index', ['rule_id' => $rule->id]);
                $frequencyName = match($schedule->frequency) {
                    'hourly' => 'Saatlik Otomatik Rapor',
                    'daily' => 'Günlük Otomatik Rapor',
                    'weekly' => 'Haftalık Otomatik Rapor',
                    'monthly' => 'Aylık Otomatik Rapor',
                    default => 'Zamanlanmış Rapor',
                };

                $mailable = new ScheduledReportMail(
                    reportTitle: $schedule->title,
                    reportResult: $reportResult,
                    reportUrl: $reportUrl,
                    scheduleFrequencyName: $frequencyName,
                    excelFilePath: $tempExcelPath,
                    attachmentName: \Illuminate\Support\Str::slug($schedule->title) . '_' . date('Y-m-d') . '.xlsx'
                );

                Mail::to($recipientEmails)->send($mailable);

                // Geçici Excel dosyasını temizle
                if (file_exists($tempExcelPath)) {
                    @unlink($tempExcelPath);
                }

                $durationMs = (int) round((microtime(true) - $startTime) * 1000);

                // 5. Başarılı Log Kaydı
                ReportScheduleLogM::create([
                    'report_schedule_id' => $schedule->id,
                    'ran_at' => now(),
                    'status' => 'success',
                    'recipients_count' => count($recipientEmails),
                    'recipients_snapshot' => $recipientEmails,
                    'error_message' => null,
                    'execution_time_ms' => $durationMs,
                ]);

                // 6. Sonraki Çalışma Zamanını Güncelle
                $nextRun = $this->calculateNextRunAt($schedule);
                $schedule->update([
                    'last_run_at' => now(),
                    'next_run_at' => $nextRun,
                ]);

                $this->info("✓ Rapor gönderildi ({$durationMs}ms): " . implode(', ', $recipientEmails));

            } catch (\Throwable $e) {
                $durationMs = (int) round((microtime(true) - $startTime) * 1000);

                Log::error("Zamanlanmış Rapor Gönderim Hatası [Schedule ID: {$schedule->id}]: " . $e->getMessage(), [
                    'trace' => $e->getTraceAsString(),
                ]);

                ReportScheduleLogM::create([
                    'report_schedule_id' => $schedule->id,
                    'ran_at' => now(),
                    'status' => 'failed',
                    'recipients_count' => 0,
                    'recipients_snapshot' => [],
                    'error_message' => $e->getMessage(),
                    'execution_time_ms' => $durationMs,
                ]);

                $this->error("✗ Hata: " . $e->getMessage());
            }
        }

        $this->info('Zamanlanmış raporlar başarıyla tamamlandı.');
        return 0;
    }

    /**
     * Kuraldaki alıcı türüne göre e-posta adreslerini çözer.
     */
    protected function resolveRecipients(ReportScheduleM $schedule): array
    {
        $emails = [];

        // 1. Manuel Yazılan E-Postalar
        if (!empty($schedule->custom_emails)) {
            $rawEmails = is_array($schedule->custom_emails) 
                ? $schedule->custom_emails 
                : preg_split('/[;, \n\r]+/', (string)$schedule->custom_emails);

            foreach ($rawEmails as $e) {
                $clean = trim($e);
                if (filter_var($clean, FILTER_VALIDATE_EMAIL)) {
                    $emails[] = $clean;
                }
            }
        }

        // 2. Alıcı Türüne Göre Kullanıcılar
        $recipientType = $schedule->recipient_type;

        if ($recipientType === 'management') {
            // Sistem Yöneticisi ve Yönetim rolleri
            $mgmtUsers = UserM::whereHas('role', function($q) {
                $q->whereIn('slug', ['sistem_yoneticisi', 'yonetim']);
            })->whereNotNull('email')->get();

            foreach ($mgmtUsers as $u) {
                $emails[] = $u->email;
            }
        } elseif ($recipientType === 'group_managers') {
            // Grup liderleri / Yöneticileri
            $leaderIds = GroupM::whereNotNull('group_leader_id')->pluck('group_leader_id')->toArray();
            if (!empty($leaderIds)) {
                $leaders = UserM::whereIn('id', $leaderIds)->whereNotNull('email')->get();
                foreach ($leaders as $l) {
                    $emails[] = $l->email;
                }
            }
        } elseif ($recipientType === 'specific_users') {
            $userIds = (array) ($schedule->recipient_user_ids ?? []);
            if (!empty($userIds)) {
                $users = UserM::whereIn('id', $userIds)->whereNotNull('email')->get();
                foreach ($users as $u) {
                    $emails[] = $u->email;
                }
            }
        }

        return array_values(array_unique(array_filter($emails)));
    }

    /**
     * Bir sonraki periyot çalışma tarih/saatini hesaplar.
     */
    protected function calculateNextRunAt(ReportScheduleM $schedule): Carbon
    {
        $now = Carbon::now();
        $timeStr = $schedule->time_of_day ?: '08:00';
        [$hour, $minute] = explode(':', $timeStr . ':00');

        return match ($schedule->frequency) {
            'hourly' => $now->copy()->addHour()->startOfHour(),
            'daily' => $now->copy()->addDay()->setTime((int)$hour, (int)$minute, 0),
            'weekly' => $now->copy()->addWeek()->next((int)($schedule->day_of_week ?? 1))->setTime((int)$hour, (int)$minute, 0),
            'monthly' => $now->copy()->addMonth()->setDay((int)($schedule->day_of_month ?? 1))->setTime((int)$hour, (int)$minute, 0),
            default => $now->copy()->addDay()->setTime((int)$hour, (int)$minute, 0),
        };
    }
}

