<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\KpiTargetS;

class KpiRecalculateCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'kpi:recalculate {--year= : İsteğe bağlı hedef yılı}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Tüm aktif KPI hedeflerinin gerçekleşme ve başarı oranlarını onaylanmış form verilerinden yeniden hesaplar';

    /**
     * Execute the console command.
     */
    public function handle(KpiTargetS $kpiService): int
    {
        $year = $this->option('year') ? intval($this->option('year')) : null;

        $this->info('KPI gerçekleşme hesaplama işlemi başlatıldı...');

        $count = $kpiService->recalculateAll($year);

        $this->info("Toplam {$count} adet aktif KPI hedefi başarıyla güncellendi.");

        return Command::SUCCESS;
    }
}

