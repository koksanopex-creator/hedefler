<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\MasterDataTypeM;
use App\Models\MasterDataItemM;
use App\Services\ActivityLogS;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ImportWorldMasterData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'master-data:import-world {--file= : Özel JSON veri dosyası yolu}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Tüm dünya ülkeleri ve şehirlerini Master Data (ÜLKE ve ŞEHİR) tablolarına aktarır (mevcutları korur, mükerrer oluşturmaz)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info("=================================================");
        $this->info("   DÜNYA ÜLKELERİ VE ŞEHİRLERİ MASTER DATA AKTARIMI   ");
        $this->info("=================================================");

        $filePath = $this->option('file') ?: database_path('data/world_countries_and_cities.json');

        if (!file_exists($filePath)) {
            $this->error("HATA: Veri dosyası bulunamadı: {$filePath}");
            return 1;
        }

        $jsonContent = file_get_contents($filePath);
        $dataset = json_decode($jsonContent, true);

        if (!is_array($dataset) || empty($dataset)) {
            $this->error("HATA: JSON veri dosyası boş veya geçersiz formatta.");
            return 1;
        }

        $this->info("Toplam " . count($dataset) . " ülke verisi yüklendi. İşlem başlıyor...\n");

        // 1. ÜLKE ve ŞEHİR Tiplerini tespit et veya oluştur
        $ulkeType = MasterDataTypeM::where('name', 'ÜLKE')->first();
        if (!$ulkeType) {
            $ulkeType = MasterDataTypeM::create([
                'name' => 'ÜLKE',
                'slug' => 'ulke_' . time(),
                'parent_id' => null,
            ]);
            $this->info("-> 'ÜLKE' veri tipi oluşturuldu (ID: {$ulkeType->id})");
        } else {
            $this->info("-> Mevcut 'ÜLKE' veri tipi kullanılıyor (ID: {$ulkeType->id})");
        }

        $sehirType = MasterDataTypeM::where('name', 'ŞEHİR')->first();
        if (!$sehirType) {
            $sehirType = MasterDataTypeM::create([
                'name' => 'ŞEHİR',
                'slug' => 'sehir_' . time(),
                'parent_id' => $ulkeType->id,
            ]);
            $this->info("-> 'ŞEHİR' veri tipi oluşturuldu (ID: {$sehirType->id})");
        } else {
            if ($sehirType->parent_id !== $ulkeType->id) {
                $sehirType->update(['parent_id' => $ulkeType->id]);
            }
            $this->info("-> Mevcut 'ŞEHİR' veri tipi kullanılıyor (ID: {$sehirType->id})");
        }

        // 2. Mevcut ülkeleri ve şehirleri hafızaya al (Performans için)
        $existingCountries = MasterDataItemM::where('type_id', $ulkeType->id)->get();
        
        // Türkçe karakter duyarlı normalizasyon fonksiyonu
        $normalize = function ($str) {
            if (!$str) return '';
            $str = mb_strtolower(trim($str), 'UTF-8');
            $str = str_replace(
                ['ı', 'ğ', 'ü', 'ş', 'ö', 'ç', 'i̇'],
                ['i', 'g', 'u', 's', 'o', 'c', 'i'],
                $str
            );
            return preg_replace('/[^a-z0-9]/', '', $str);
        };

        // Ülke arama haritası
        $countryMap = [];
        foreach ($existingCountries as $c) {
            $countryMap[$normalize($c->name)] = $c;
        }

        // Amerika/ABD gibi yaygın eşleşmeler için özel takma adlar
        $aliases = [
            'amerika' => ['amerikabirlesikdevletleri', 'abd', 'usa', 'unitedstates'],
            'birlesikkrallik' => ['ingiltere', 'uk', 'unitedkingdom'],
            'hollanda' => ['netherlands'],
            'almanya' => ['germany'],
            'turkiye' => ['turkey'],
            'rusya' => ['russia', 'rusyafederasyonu'],
            'guneykore' => ['korecumhuriyeti'],
            'kuzeykore' => ['demokratikhalkcumhuriyeti'],
        ];

        // Mevcut şehirleri parent_id bazında hafızaya al: [country_id => [normalized_city => true]]
        $existingCities = MasterDataItemM::where('type_id', $sehirType->id)->get();
        $citiesMap = [];
        foreach ($existingCities as $city) {
            if ($city->parent_id) {
                $citiesMap[$city->parent_id][$normalize($city->name)] = true;
            }
        }

        $addedCountries = 0;
        $existingCountriesCount = 0;
        $addedCities = 0;
        $skippedCities = 0;
        $newCitiesBatch = [];

        $progressBar = $this->output->createProgressBar(count($dataset));
        $progressBar->start();

        foreach ($dataset as $countryData) {
            $countryName = trim($countryData['name']);
            $countryEn = trim($countryData['name_en'] ?? '');
            $cities = $countryData['cities'] ?? [];

            $normName = $normalize($countryName);
            $normEn = $normalize($countryEn);

            // Mevcut ülke var mı kontrol et
            $countryItem = $countryMap[$normName] ?? ($normEn ? ($countryMap[$normEn] ?? null) : null);

            // Takma adlarla kontrol
            if (!$countryItem) {
                foreach ($aliases as $mainAlias => $aliasList) {
                    if ($normName === $mainAlias || in_array($normName, $aliasList)) {
                        if (isset($countryMap[$mainAlias])) {
                            $countryItem = $countryMap[$mainAlias];
                            break;
                        }
                        foreach ($aliasList as $al) {
                            if (isset($countryMap[$al])) {
                                $countryItem = $countryMap[$al];
                                break 2;
                            }
                        }
                    }
                }
            }

            // Ülke yoksa oluştur
            if (!$countryItem) {
                $countryItem = MasterDataItemM::create([
                    'type_id' => $ulkeType->id,
                    'name' => $countryName,
                    'parent_id' => null,
                    'user_id' => 1,
                ]);
                $countryMap[$normName] = $countryItem;
                $addedCountries++;
            } else {
                $existingCountriesCount++;
            }

            $countryId = $countryItem->id;

            // Şehirleri kontrol et ve ekle
            foreach ($cities as $cityName) {
                $cityName = trim($cityName);
                if (empty($cityName)) continue;

                $normCity = $normalize($cityName);

                // Eğer bu şehir bu ülkenin altında zaten kayıtlıysa ATLA (MÜKERRER EKLEME)
                if (isset($citiesMap[$countryId][$normCity])) {
                    $skippedCities++;
                    continue;
                }

                // Eklenecekler listesine al
                $newCitiesBatch[] = [
                    'type_id' => $sehirType->id,
                    'name' => $cityName,
                    'parent_id' => $countryId,
                    'user_id' => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];

                $citiesMap[$countryId][$normCity] = true;
                $addedCities++;

                // Her 500 kayıtta bir topluca veritabanına yaz
                if (count($newCitiesBatch) >= 500) {
                    MasterDataItemM::insert($newCitiesBatch);
                    $newCitiesBatch = [];
                }
            }

            $progressBar->advance();
        }

        // Kalan şehirleri yaz
        if (!empty($newCitiesBatch)) {
            MasterDataItemM::insert($newCitiesBatch);
            $newCitiesBatch = [];
        }

        $progressBar->finish();
        $this->newLine(2);

        // Denetim kaydı oluştur
        try {
            ActivityLogS::logAction(
                'master_data.import_world',
                "Tüm dünya ülkeleri ve şehirleri içe aktarıldı (Yeni Ülke: {$addedCountries}, Korunan Ülke: {$existingCountriesCount}, Yeni Şehir: {$addedCities}, Atlanan Mükerrer Şehir: {$skippedCities})"
            );
        } catch (\Throwable $e) {
        }

        $this->info("=================== İŞLEM RAPORU ===================");
        $this->info("✅ Mevcut Korunan Ülke Sayısı       : {$existingCountriesCount}");
        $this->info("✅ Yeni Eklenen Ülke Sayısı         : {$addedCountries}");
        $this->info("✅ Toplam Aktif Ülke Sayısı         : " . ($existingCountriesCount + $addedCountries));
        $this->info("---------------------------------------------------");
        $this->info("✅ Önceden Var Olan / Atlanan Şehir : {$skippedCities} (Mükerrerlik engellendi)");
        $this->info("✅ Yeni Eklenen Şehir Sayısı        : {$addedCities}");
        $totalCitiesInDb = MasterDataItemM::where('type_id', $sehirType->id)->count();
        $this->info("✅ Sistemdeki Toplam Şehir Sayısı   : {$totalCitiesInDb}");
        $this->info("===================================================");
        $this->info("İşlem başarıyla tamamlandı!");

        return 0;
    }
}
