{{-- 
    Etkileşimli Dünya / Şehir Haritası & Radar Grafik Bileşeni
    Veriler: $geoData (countries, cities, year), $radarData (labels, target_scores, actual_rates, raw_targets, raw_actuals)
--}}

<!-- Harita ve Grafik Kütüphaneleri -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/jsvectormap/dist/css/jsvectormap.min.css" />
<script src="https://cdn.jsdelivr.net/npm/jsvectormap/dist/js/jsvectormap.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/jsvectormap/dist/maps/world.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>

<style>
    /* 1. Bilgi Kartının (Tooltip) Kesinlikle SVG ve Haritanın En Üstünde Görünmesi İçin */
    .jvm-tooltip {
        z-index: 2147483647 !important;
        pointer-events: none !important;
        background: transparent !important;
        border: none !important;
        padding: 0 !important;
        box-shadow: none !important;
        position: absolute !important;
    }
    #kpi-world-map svg {
        width: 100% !important;
        height: 100% !important;
        display: block;
    }
    /* 2. Tam Ekran (Fullscreen) Modu Sitilleri */
    #kpi-geo-map-card:fullscreen {
        padding: 1.5rem !important;
        border-radius: 0 !important;
        width: 100vw !important;
        height: 100vh !important;
        background-color: #0b0f19 !important;
        overflow: hidden !important;
        display: flex !important;
        flex-direction: column !important;
        justify-content: space-between !important;
    }
    #kpi-geo-map-card:fullscreen #kpi-world-map {
        width: 100% !important;
        flex: 1 1 auto !important;
        height: calc(100vh - 170px) !important;
        max-height: calc(100vh - 170px) !important;
    }
</style>

<div class="bg-white rounded-3xl p-6 shadow-sm border border-slate-200/80 space-y-6"
    @kpi-open-country.window="openCountryModal($event.detail.iso)"
    @keydown.escape.window="if (selectedCountry) closeCountryModal()"
    x-data="{
        isTheaterMode: false,
        isFullscreen: false,
        selectedCountry: null,
        openCountryModal(iso) {
            const mapData = window.kpiGeoCountries || {};
            if (mapData[iso]) {
                this.selectedCountry = mapData[iso];
            }
        },
        closeCountryModal() {
            this.selectedCountry = null;
        },
        resizeMap() {
            const doResize = () => {
                if (window.kpiMapInstance && typeof window.kpiMapInstance.updateSize === 'function') {
                    window.kpiMapInstance.updateSize();
                }
                window.dispatchEvent(new Event('resize'));
            };
            requestAnimationFrame(doResize);
            setTimeout(doResize, 120);
            setTimeout(doResize, 320);
        },
        toggleTheater() {
            this.isTheaterMode = !this.isTheaterMode;
            $nextTick(() => {
                this.resizeMap();
            });
        },
        toggleFullscreen() {
            const el = document.getElementById('kpi-geo-map-card');
            if (!document.fullscreenElement) {
                if (el.requestFullscreen) {
                    el.requestFullscreen();
                } else if (el.webkitRequestFullscreen) {
                    el.webkitRequestFullscreen();
                }
            } else {
                if (document.exitFullscreen) {
                    document.exitFullscreen();
                }
            }
        },
        resetMap() {
            if (window.kpiMapInstance) {
                window.kpiMapInstance.reset();
            }
        }
    }"
    x-init="
        document.addEventListener('fullscreenchange', () => {
            isFullscreen = !!document.fullscreenElement;
            const tooltip = document.querySelector('.jvm-tooltip');
            const card = document.getElementById('kpi-geo-map-card');
            if (tooltip && card) {
                if (isFullscreen) {
                    card.appendChild(tooltip);
                } else if (tooltip.parentElement !== document.body) {
                    document.body.appendChild(tooltip);
                }
            }
            $nextTick(() => { resizeMap(); });
        });
    ">

    {{-- ─── ÜST BAŞLIK VE LEJANT ─── --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-slate-100 pb-4">
        <div>
            <h3 class="text-lg font-black text-slate-900 flex items-center gap-2">
                <span class="w-8 h-8 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center text-base shadow-xs">
                    🌍
                </span>
                Coğrafi Satış Dağılımı & Radar Performans Analizi
            </h3>
            <p class="text-xs text-slate-500 mt-0.5">
                Ülke ve şehir bazlı KPI hedefleri, gerçekleşme ısı haritası ve master data işyeri dağılım dengesi
            </p>
        </div>

        {{-- Isı Haritası Lejantı --}}
        <div class="flex items-center gap-3 bg-slate-50 px-3.5 py-1.5 rounded-xl border border-slate-200/70 text-[11px] font-bold text-slate-600">
            <span class="text-slate-400 font-medium">Hedef Başarısı:</span>
            <span class="flex items-center gap-1">
                <span class="w-2.5 h-2.5 rounded-full bg-emerald-500"></span>
                <span>≥%90</span>
            </span>
            <span class="flex items-center gap-1">
                <span class="w-2.5 h-2.5 rounded-full bg-amber-500"></span>
                <span>%70-%89</span>
            </span>
            <span class="flex items-center gap-1">
                <span class="w-2.5 h-2.5 rounded-full bg-rose-500"></span>
                <span>&lt;%70</span>
            </span>
        </div>
    </div>

    {{-- ─── COĞRAFİ FİLTRELEME ÇUBUĞU (KİŞİ & GRUP BAZLI) ─── --}}
    @if(isset($users) || isset($groups))
    <div class="bg-slate-50/90 rounded-2xl p-3 sm:p-4 border border-slate-200/80 flex flex-col md:flex-row md:items-center justify-between gap-3">
        <div class="flex items-center gap-2">
            <div class="w-8 h-8 rounded-lg bg-indigo-100/70 text-indigo-700 flex items-center justify-center text-xs font-bold">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/></svg>
            </div>
            <div>
                <span class="text-xs font-bold text-slate-800 block">Harita Hedef Filtresi</span>
                <span class="text-[11px] text-slate-500">Kişi veya departman seçerek harita ve radar verilerini daraltın</span>
            </div>
            @if(!empty($geoUserId) || !empty($geoGroupId))
                <span class="ml-2 px-2 py-0.5 rounded-md text-[10px] font-extrabold bg-indigo-100 text-indigo-800 border border-indigo-200">
                    Filtre Uygulandı
                </span>
            @endif
        </div>

        <form method="GET" action="{{ url()->current() }}" id="geoFilterForm" class="flex flex-wrap items-center gap-2 sm:gap-3">
            <input type="hidden" name="tab" value="geo">
            <input type="hidden" name="year" value="{{ $geoData['year'] ?? date('Y') }}">

            {{-- Kişi / Personel Seçici --}}
            <div class="flex items-center gap-1.5 bg-white px-2.5 py-1.5 rounded-xl border border-slate-200 shadow-2xs">
                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider flex items-center gap-1">
                    <svg class="w-3 h-3 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                    Kişi:
                </span>
                <select name="geo_user_id" onchange="document.getElementById('geoFilterForm').submit()" class="border-0 bg-transparent py-0 pl-1 pr-6 text-xs font-bold text-slate-800 focus:ring-0 cursor-pointer max-w-[190px]">
                    <option value="">-- Tüm Kişiler (Genel) --</option>
                    @if(isset($users))
                        @foreach($users as $u)
                            <option value="{{ $u->id }}" {{ ($geoUserId ?? null) == $u->id ? 'selected' : '' }}>
                                {{ $u->name }} ({{ $u->group?->name ?? 'Grup Yok' }})
                            </option>
                        @endforeach
                    @endif
                </select>
            </div>

            {{-- Grup / Departman Seçici --}}
            <div class="flex items-center gap-1.5 bg-white px-2.5 py-1.5 rounded-xl border border-slate-200 shadow-2xs">
                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider flex items-center gap-1">
                    <svg class="w-3 h-3 text-teal-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                    Grup:
                </span>
                <select name="geo_group_id" onchange="document.getElementById('geoFilterForm').submit()" class="border-0 bg-transparent py-0 pl-1 pr-6 text-xs font-bold text-slate-800 focus:ring-0 cursor-pointer max-w-[170px]">
                    <option value="">-- Tüm Gruplar --</option>
                    @if(isset($groups))
                        @foreach($groups as $g)
                            <option value="{{ $g->id }}" {{ ($geoGroupId ?? null) == $g->id ? 'selected' : '' }}>
                                {{ $g->name }}
                            </option>
                        @endforeach
                    @endif
                </select>
            </div>

            {{-- Filtre Sıfırla --}}
            @if(!empty($geoUserId) || !empty($geoGroupId))
                <a href="{{ route('kpi-targets.index', ['tab' => 'geo', 'year' => $geoData['year'] ?? date('Y')]) }}" class="p-2 text-slate-400 hover:text-rose-600 hover:bg-rose-50 rounded-xl transition-colors border border-slate-200 bg-white" title="Filtreyi Temizle">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </a>
            @endif
        </form>
    </div>
    @endif

    {{-- ─── ANA IZGARA: DÜNYA HARİTASI & RADAR GRAFİĞİ ─── --}}
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-stretch">
        
        {{-- 1. DÜNYA VE ŞEHİR HARİTASI KARTI --}}
        <div id="kpi-geo-map-card"
            class="flex flex-col justify-between bg-slate-900 rounded-2xl p-5 shadow-inner text-white relative transition-all duration-300"
            :class="isTheaterMode ? 'col-span-12' : 'lg:col-span-7'">
            
            {{-- Harita Başlığı ve YouTube Tarzı Kontrol Butonları --}}
            @php
                $currentMapYear = (int) ($geoData['year'] ?? date('Y'));
                $mapYearParam = $yearParamName ?? 'year';
                $mapYearsList = collect($availableYears ?? [$currentMapYear])
                    ->map(fn($y) => (int) $y)
                    ->push($currentMapYear)
                    ->unique()
                    ->sortDesc()
                    ->values()
                    ->all();
            @endphp
            <div class="flex items-center justify-between mb-3 z-10 flex-wrap gap-2">
                <div class="flex items-center gap-2 flex-wrap">
                    <span class="text-xs font-bold uppercase tracking-wider text-indigo-400">Küresel Satış & Hedef Isı Haritası</span>
                    <div class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[10px] font-mono font-bold bg-indigo-500/20 text-indigo-200 border border-indigo-400/30">
                        <select aria-label="Dönem Yılı Seçimi"
                            onchange="const u = new URL(window.location.href); u.searchParams.set('{{ $mapYearParam }}', this.value); window.location.href = u.toString();"
                            class="bg-transparent border-0 py-0 pl-0.5 pr-4 text-[10px] font-mono font-bold text-indigo-200 focus:ring-0 cursor-pointer">
                            @foreach($mapYearsList as $yOpt)
                                <option value="{{ $yOpt }}" class="bg-slate-900 text-white" {{ $currentMapYear === $yOpt ? 'selected' : '' }}>
                                    {{ $yOpt }} Dönemi
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <span class="hidden sm:inline-flex items-center gap-1 text-[10px] text-slate-400 bg-slate-800/80 px-2 py-0.5 rounded-md border border-slate-700">
                        <svg class="w-3 h-3 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 15l-2 5L9 9l11 4-5 2zm0 0l5 5M7.188 2.239l.777 2.897M5.136 7.965l-2.898-.777M13.95 4.05l-2.122 2.122m-5.657 5.656l-2.12 2.122"/></svg>
                        Ülkeye Tıkla / Yakınlaş
                    </span>
                </div>

                {{-- YouTube Tarzı Harita Araç Çubuğu --}}
                <div class="flex items-center gap-1.5">
                    {{-- Haritayı Sıfırla (Reset Zoom) --}}
                    <button type="button" @click="resetMap()" class="p-1.5 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-300 hover:text-white transition-colors border border-slate-700" title="Haritayı İlk Konumuna Sıfırla">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                    </button>

                    {{-- Geniş Ekran / Tiyatro Modu (YouTube Theater) --}}
                    <button type="button" @click="toggleTheater()" class="p-1.5 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-300 hover:text-white transition-colors border border-slate-700" :title="isTheaterMode ? 'Standart Görünüme Dön' : 'Geniş Ekran Modu (Sinema Modu)'">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path x-show="!isTheaterMode" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                            <path x-show="isTheaterMode" x-cloak stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h7M4 12h7M4 18h7m6-12h3M17 12h3M17 18h3" />
                        </svg>
                    </button>

                    {{-- Tam Ekran (Fullscreen) --}}
                    <button type="button" @click="toggleFullscreen()" class="p-1.5 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-300 hover:text-white transition-colors border border-slate-700" :title="isFullscreen ? 'Tam Ekrandan Çık' : 'Tüm Ekranı Kapla (Fullscreen)'">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path x-show="!isFullscreen" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4" />
                            <path x-show="isFullscreen" x-cloak stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            </div>

            {{-- SVG Harita Konteyneri (Mouse scroll zoom aktif) --}}
            <div id="kpi-world-map" class="w-full transition-all duration-300 relative z-10" :class="isTheaterMode ? 'h-[500px]' : 'h-[330px] sm:h-[370px]'"></div>

            {{-- Harita Altı Ülke Özet Şeridi (Tıklanabilir -> İşyeri Kırılımı Modalı) --}}
            <div class="mt-4 pt-3 border-t border-slate-800/80 grid grid-cols-2 sm:grid-cols-4 gap-2 z-10">
                @forelse(array_slice($geoData['countries'] ?? [], 0, 8) as $iso => $cty)
                    <button type="button" @click="openCountryModal('{{ $iso }}')"
                        class="text-left bg-slate-800/60 hover:bg-slate-800 p-2.5 rounded-xl border border-slate-700/60 hover:border-indigo-400/70 transition-all cursor-pointer group">
                        <div class="flex items-center justify-between text-[11px] gap-1.5">
                            <span class="font-bold text-slate-100 group-hover:text-indigo-300 truncate">{{ $cty['name'] }}</span>
                            @if(($cty['target'] ?? 0) > 0)
                                <span class="font-extrabold shrink-0" style="color: {{ $cty['color'] }}">%{{ $cty['rate'] }}</span>
                            @else
                                <span class="w-2.5 h-2.5 rounded-full shrink-0" style="background-color: {{ $cty['color'] }}"></span>
                            @endif
                        </div>
                        @if(($cty['target'] ?? 0) > 0)
                            <div class="flex items-center justify-between text-[10px] text-slate-400 mt-1">
                                <span>H: {{ number_format($cty['target'], 0, ',', '.') }}</span>
                                <span>G: {{ number_format($cty['actual'], 0, ',', '.') }}</span>
                            </div>
                        @else
                            <div class="flex items-center justify-between text-[10px] text-slate-300 mt-1">
                                <span>{{ $cty['entry_rows'] ?? 0 }} Satır</span>
                                @if(($cty['total_tonaj'] ?? 0) > 0)
                                    <span class="text-emerald-300 font-semibold">{{ number_format($cty['total_tonaj'], 0, ',', '.') }} Ton</span>
                                @endif
                            </div>
                        @endif
                        @if(!empty($cty['workplaces']))
                            <div class="mt-1.5 flex flex-wrap gap-1">
                                @foreach(array_slice($cty['workplaces'], 0, 3) as $wp)
                                    <span class="px-1.5 py-0.5 rounded bg-indigo-500/20 text-indigo-200 border border-indigo-400/30 text-[9px] font-bold truncate max-w-[90px]">
                                        {{ $wp['name'] }}
                                    </span>
                                @endforeach
                                @if(count($cty['workplaces']) > 3)
                                    <span class="text-[9px] text-slate-400 font-bold">+{{ count($cty['workplaces']) - 3 }}</span>
                                @endif
                            </div>
                        @endif
                    </button>
                @empty
                    <div class="col-span-4 text-center py-2 text-xs text-slate-500">
                        Bu dönem ve kapsam için ülke bazlı veri veya hedef girişi bulunmuyor.
                    </div>
                @endforelse
            </div>

            {{-- Ülke İşyeri Kırılımı Detay Modalı (Normal ve Fullscreen modunda #kpi-geo-map-card içinde çalışır) --}}
            <div x-show="selectedCountry" x-cloak style="display: none;"
                class="fixed inset-0 z-[99999] flex items-center justify-center p-4 bg-slate-950/75 backdrop-blur-xs"
                @click.self="closeCountryModal()">
                <div class="bg-slate-900 text-white border border-slate-700 rounded-2xl shadow-2xl max-w-xl w-full overflow-hidden"
                    @click.stop>
                    <div class="px-5 py-4 bg-slate-800/80 border-b border-slate-700 flex items-center justify-between">
                        <div>
                            <div class="flex items-center gap-2">
                                <span class="w-3 h-3 rounded-full inline-block" :style="selectedCountry ? 'background-color:' + selectedCountry.color : ''"></span>
                                <h4 class="text-base font-black text-white" x-text="selectedCountry ? selectedCountry.name : ''"></h4>
                                <span class="px-1.5 py-0.5 rounded text-[10px] font-mono bg-slate-700 text-slate-300" x-text="selectedCountry ? selectedCountry.iso : ''"></span>
                                <span class="px-2 py-0.5 rounded text-[10px] font-mono bg-indigo-500/20 text-indigo-300 border border-indigo-400/30">{{ $currentMapYear }}</span>
                            </div>
                            <p class="text-[11px] text-slate-400 mt-0.5">Girilen form verilerine göre işyeri bazlı dağılım özeti</p>
                        </div>
                        <button type="button" @click="closeCountryModal()" class="p-1.5 rounded-lg text-slate-400 hover:text-white hover:bg-slate-700 transition cursor-pointer">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>

                    <div class="p-5 space-y-4 max-h-[75vh] overflow-y-auto" x-if="selectedCountry">
                        {{-- Ülke Özet Metrikleri --}}
                        <div class="grid grid-cols-2 sm:grid-cols-3 gap-2.5">
                            <div class="bg-slate-800/70 p-3 rounded-xl border border-slate-700/70">
                                <span class="text-[10px] uppercase tracking-wider text-slate-400 font-bold block">Girilen Satır</span>
                                <span class="text-lg font-black text-white" x-text="selectedCountry.entry_rows ?? 0"></span>
                            </div>
                            <div class="bg-slate-800/70 p-3 rounded-xl border border-slate-700/70">
                                <span class="text-[10px] uppercase tracking-wider text-slate-400 font-bold block">Toplam Tonaj</span>
                                <span class="text-lg font-black text-emerald-400" x-text="Number(selectedCountry.total_tonaj || 0).toLocaleString('tr-TR', { maximumFractionDigits: 2 })"></span>
                            </div>
                            <div class="bg-slate-800/70 p-3 rounded-xl border border-slate-700/70 col-span-2 sm:col-span-1">
                                <span class="text-[10px] uppercase tracking-wider text-slate-400 font-bold block">Toplam Tutar</span>
                                <span class="text-lg font-black text-indigo-300" x-text="Number(selectedCountry.total_tutar || 0).toLocaleString('tr-TR', { maximumFractionDigits: 2 })"></span>
                            </div>
                        </div>

                        {{-- KPI Hedef Bilgisi (Varsa) --}}
                        <template x-if="(selectedCountry.target || 0) > 0">
                            <div class="bg-indigo-950/50 border border-indigo-500/30 rounded-xl p-3 flex items-center justify-between text-xs">
                                <div>
                                    <span class="text-indigo-300 font-bold block">KPI Hedef / Gerçekleşme</span>
                                    <span class="text-slate-300">
                                        Hedef: <strong class="text-white" x-text="Number(selectedCountry.target).toLocaleString('tr-TR')"></strong>
                                        &bull;
                                        Gerçekleşen: <strong class="text-emerald-400" x-text="Number(selectedCountry.actual).toLocaleString('tr-TR')"></strong>
                                    </span>
                                </div>
                                <span class="text-base font-black text-amber-400" x-text="'%' + selectedCountry.rate"></span>
                            </div>
                        </template>

                        {{-- İşyeri Kırılımı Listesi --}}
                        <div>
                            <h5 class="text-xs font-extrabold uppercase tracking-wider text-slate-300 mb-2">İşyeri Kırılımı</h5>
                            <template x-if="selectedCountry.workplaces && selectedCountry.workplaces.length > 0">
                                <div class="space-y-2">
                                    <template x-for="wp in selectedCountry.workplaces" :key="wp.name">
                                        <div class="bg-slate-800/80 border border-slate-700 rounded-xl p-3 flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                                            <div>
                                                <div class="flex items-center gap-2">
                                                    <span class="px-2 py-0.5 rounded-md text-xs font-black bg-amber-400/20 text-amber-300 border border-amber-400/40" x-text="wp.name"></span>
                                                    <span class="text-xs text-slate-300 font-semibold" x-text="wp.rows + ' Satır'"></span>
                                                </div>
                                                <template x-if="wp.cities && wp.cities.length > 0">
                                                    <p class="text-[11px] text-slate-400 mt-1" x-text="'Şehirler: ' + wp.cities.join(', ')"></p>
                                                </template>
                                            </div>
                                            <div class="flex items-center gap-3 text-xs font-mono">
                                                <div class="text-right">
                                                    <span class="text-[10px] text-slate-400 block">Tonaj</span>
                                                    <strong class="text-emerald-400" x-text="Number(wp.tonaj || 0).toLocaleString('tr-TR', { maximumFractionDigits: 2 })"></strong>
                                                </div>
                                                <div class="text-right">
                                                    <span class="text-[10px] text-slate-400 block">Tutar</span>
                                                    <strong class="text-indigo-300" x-text="Number(wp.tutar || 0).toLocaleString('tr-TR', { maximumFractionDigits: 2 })"></strong>
                                                </div>
                                            </div>
                                        </div>
                                    </template>
                                </div>
                            </template>
                            <template x-if="!selectedCountry.workplaces || selectedCountry.workplaces.length === 0">
                                <div class="text-xs text-slate-400 bg-slate-800/40 border border-slate-800 rounded-xl p-3">
                                    Bu ülke için henüz form satırında işyeri girişi bulunmuyor (yalnızca KPI hedef kaydı mevcut).
                                </div>
                            </template>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- 2. RADAR GRAFİĞİ KARTI (Master Data Dinamik İşyerleri) --}}
        <div class="flex flex-col justify-between bg-slate-50/70 rounded-2xl p-5 border border-slate-200/80 transition-all duration-300"
            :class="isTheaterMode ? 'col-span-12 mt-4' : 'lg:col-span-5'">
            <div>
                <div class="flex items-center justify-between mb-2">
                    <span class="text-xs font-bold uppercase tracking-wider text-slate-700 flex items-center gap-1.5">
                        <span class="text-purple-600">🕸️</span> Ürün & İşyeri Dağılımı (Radar)
                    </span>
                    <span class="text-[10px] font-bold text-indigo-700 bg-indigo-50 px-2 py-0.5 rounded-md border border-indigo-200">
                        Master Data Entegre
                    </span>
                </div>
                <p class="text-[11px] text-slate-500 leading-snug">
                    Personelin veya şirketin Master Data'da kayıtlı işyerlerindeki (Preform, Kapak, Kulp, Levha, Resin vb.) hedeflere dengeli ulaşma performansı.
                </p>
            </div>

            {{-- Radar Canvas Konteyneri --}}
            <div class="relative w-full flex items-center justify-center my-auto py-2" :class="isTheaterMode ? 'h-[360px]' : 'h-[310px]'">
                <canvas id="kpi-radar-chart"></canvas>
            </div>

            {{-- Radar Altı Bilgilendirme --}}
            <div class="mt-2 pt-2 border-t border-slate-200/70 flex items-center justify-around text-center text-xs">
                <div>
                    <span class="text-[10px] text-slate-400 block font-medium">Hedef Referansı</span>
                    <strong class="text-indigo-600 font-extrabold">%100 Referans</strong>
                </div>
                <div class="h-6 w-px bg-slate-200"></div>
                <div>
                    <span class="text-[10px] text-slate-400 block font-medium">Kapsanan İşyeri</span>
                    <strong class="text-slate-800 font-extrabold">{{ count($radarData['labels'] ?? []) }} İşyeri / Ürün</strong>
                </div>
            </div>
        </div>

    </div>
</div>

{{-- Harita ve Radar Başlatıcı Script --}}
<script>
document.addEventListener('DOMContentLoaded', function () {
    // 1. DÜNYA HARİTASI KURULUMU (jsVectorMap)
    const geoCountries = @json($geoData['countries'] ?? []);
    const geoCities = @json($geoData['cities'] ?? []);
    window.kpiGeoCountries = geoCountries;

    // Ülke Renk Haritası
    const countryColors = {};
    for (const [iso, data] of Object.entries(geoCountries)) {
        if (data.color) {
            countryColors[iso] = data.color;
        }
    }

    // Şehir Markerları
    const mapMarkers = geoCities.map(city => ({
        name: city.name,
        coords: city.coords,
        target: city.target,
        actual: city.actual,
        rate: city.rate,
        unit: city.unit,
        country: city.country,
        style: {
            fill: city.color || '#10b981',
            stroke: '#ffffff',
            strokeWidth: 2,
            r: Math.max(5, Math.min(10, Math.log10(city.target || 10) * 2.5))
        }
    }));

    if (document.getElementById('kpi-world-map')) {
        try {
            window.kpiMapInstance = new jsVectorMap({
                selector: '#kpi-world-map',
                map: 'world',
                backgroundColor: 'transparent',
                draggable: true,
                zoomButtons: true,
                zoomOnScroll: true,       // Mouse Scroll Zoom Aktif
                zoomMax: 15,
                zoomMin: 1,
                zoomStep: 1.35,
                regionStyle: {
                    initial: {
                        fill: '#334155',
                        stroke: '#1e293b',
                        strokeWidth: 0.5,
                        fillOpacity: 1
                    },
                    hover: {
                        fill: '#6366f1',
                        fillOpacity: 0.9,
                        cursor: 'pointer'
                    }
                },
                series: {
                    regions: [{
                        attribute: 'fill',
                        values: countryColors
                    }]
                },
                markers: mapMarkers,
                markerStyle: {
                    initial: {
                        stroke: '#ffffff',
                        strokeWidth: 2
                    },
                    hover: {
                        stroke: '#fbbf24',
                        strokeWidth: 3,
                        cursor: 'pointer'
                    }
                },
                onRegionClick(event, code) {
                    if (geoCountries[code]) {
                        window.dispatchEvent(new CustomEvent('kpi-open-country', { detail: { iso: code } }));
                    }
                },
                onRegionTooltipShow(event, tooltip, code) {
                    const country = geoCountries[code];
                    if (country) {
                        const wpBadges = (country.workplaces || []).map(wp =>
                            `<span class="inline-block px-1.5 py-0.5 rounded bg-indigo-500/30 text-indigo-200 border border-indigo-400/40 text-[10px] font-bold">${wp.name}: ${wp.rows} satır</span>`
                        ).join(' ');
                        const kpiSection = Number(country.target || 0) > 0 ? `
                            <div class="flex justify-between text-slate-300">
                                <span>Hedeflenen:</span>
                                <strong class="text-white">${Number(country.target).toLocaleString('tr-TR')} ${country.unit}</strong>
                            </div>
                            <div class="flex justify-between text-slate-300">
                                <span>Gerçekleşen:</span>
                                <strong class="text-emerald-400 font-bold">${Number(country.actual).toLocaleString('tr-TR')} ${country.unit}</strong>
                            </div>
                            <div class="flex justify-between text-slate-300">
                                <span>Başarı Oranı:</span>
                                <strong class="text-amber-400 font-black">%${country.rate}</strong>
                            </div>
                        ` : '';
                        const entrySection = Number(country.entry_rows || 0) > 0 ? `
                            <div class="flex justify-between text-slate-300">
                                <span>Girilen Satır:</span>
                                <strong class="text-white">${Number(country.entry_rows).toLocaleString('tr-TR')} Satır</strong>
                            </div>
                            <div class="flex justify-between text-slate-300">
                                <span>Toplam Tonaj:</span>
                                <strong class="text-emerald-400 font-bold">${Number(country.total_tonaj || 0).toLocaleString('tr-TR', { maximumFractionDigits: 2 })}</strong>
                            </div>
                            <div class="flex justify-between text-slate-300">
                                <span>Toplam Tutar:</span>
                                <strong class="text-indigo-300 font-bold">${Number(country.total_tutar || 0).toLocaleString('tr-TR', { maximumFractionDigits: 2 })}</strong>
                            </div>
                        ` : '';
                        tooltip.text(`
                            <div style="z-index:999999999;" class="p-3 bg-slate-900/95 text-white rounded-xl shadow-2xl border-2 border-indigo-400 text-xs backdrop-blur-md min-w-[200px]">
                                <div class="font-extrabold text-indigo-300 text-sm border-b border-slate-700 pb-1.5 mb-2 flex items-center justify-between gap-2">
                                    <span>${country.name}</span>
                                    <span class="text-[10px] text-slate-400 font-mono">${code}</span>
                                </div>
                                <div class="space-y-1">
                                    ${kpiSection}
                                    ${entrySection}
                                </div>
                                ${wpBadges ? `<div class="mt-2 pt-1.5 border-t border-slate-800 flex flex-wrap gap-1">${wpBadges}</div>` : ''}
                                <div class="mt-1.5 text-[10px] text-amber-300 font-semibold">İşyeri detayı için tıklayın →</div>
                            </div>
                        `, true);
                    } else {
                        tooltip.text(`<div style="z-index:999999999;" class="px-2.5 py-1 bg-slate-900/95 text-slate-400 text-xs rounded-lg border border-slate-700 shadow-lg">${tooltip.text()} (Veri Yok)</div>`, true);
                    }
                },
                onMarkerTooltipShow(event, tooltip, index) {
                    const city = mapMarkers[index];
                    if (city) {
                        tooltip.text(`
                            <div style="z-index:999999999;" class="p-3 bg-slate-900/95 text-white rounded-xl shadow-2xl border-2 border-amber-400 text-xs backdrop-blur-md min-w-[180px]">
                                <div class="font-black text-amber-300 text-sm border-b border-slate-700 pb-1.5 mb-2 flex items-center justify-between">
                                    <span>📍 ${city.name}</span>
                                    <span class="text-[10px] text-slate-400">${city.country}</span>
                                </div>
                                <div class="space-y-1.5">
                                    <div class="flex justify-between text-slate-300">
                                        <span>Şehir Hedefi:</span>
                                        <strong class="text-white">${Number(city.target).toLocaleString('tr-TR')} ${city.unit}</strong>
                                    </div>
                                    <div class="flex justify-between text-slate-300">
                                        <span>Gerçekleşen:</span>
                                        <strong class="text-emerald-400 font-bold">${Number(city.actual).toLocaleString('tr-TR')} ${city.unit}</strong>
                                    </div>
                                    <div class="flex justify-between text-slate-300 pt-1.5 border-t border-slate-800">
                                        <span>Şehir Başarısı:</span>
                                        <strong class="text-indigo-300 font-black text-sm">%${city.rate}</strong>
                                    </div>
                                </div>
                            </div>
                        `, true);
                    }
                }
            });
        } catch (e) {
            console.error('jsVectorMap init error:', e);
        }
    }

    // 2. RADAR GRAFİĞİ KURULUMU (Chart.js)
    const radarCtx = document.getElementById('kpi-radar-chart');
    if (radarCtx) {
        const radarData = @json($radarData ?? []);

        window.kpiRadarInstance = new Chart(radarCtx, {
            type: 'radar',
            data: {
                labels: radarData.labels || ['PREFORM', 'KAPAK', 'KULP', 'LEVHA', 'STRECH', 'SHRINK', 'RESIN', 'TİCARİ PET RESIN'],
                datasets: [
                    {
                        label: 'Hedeflenen (%100 Çizgisi)',
                        data: radarData.target_scores || [],
                        borderColor: '#94a3b8',
                        borderWidth: 1.5,
                        borderDash: [4, 4],
                        backgroundColor: 'rgba(148, 163, 184, 0.05)',
                        pointRadius: 2,
                        pointBackgroundColor: '#94a3b8',
                    },
                    {
                        label: 'Gerçekleşme Başarısı (%)',
                        data: radarData.actual_rates || [],
                        borderColor: '#6366f1',
                        borderWidth: 2.5,
                        backgroundColor: 'rgba(99, 102, 241, 0.25)',
                        pointBackgroundColor: '#4f46e5',
                        pointBorderColor: '#ffffff',
                        pointBorderWidth: 2,
                        pointRadius: 5,
                        pointHoverRadius: 7
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    r: {
                        angleLines: {
                            color: 'rgba(203, 213, 225, 0.6)'
                        },
                        grid: {
                            color: 'rgba(226, 232, 240, 0.8)'
                        },
                        pointLabels: {
                            font: {
                                size: 10,
                                weight: 'bold'
                            },
                            color: '#1e293b'
                        },
                        ticks: {
                            display: true,
                            stepSize: 25,
                            font: {
                                size: 9
                            },
                            color: '#64748b',
                            backdropColor: 'transparent'
                        },
                        min: 0,
                        suggestedMax: 125
                    }
                },
                plugins: {
                    legend: {
                        display: true,
                        position: 'top',
                        labels: {
                            boxWidth: 12,
                            font: {
                                size: 10,
                                weight: 'bold'
                            },
                            color: '#475569'
                        }
                    },
                    tooltip: {
                        backgroundColor: '#0f172a',
                        titleFont: { size: 12, weight: 'bold' },
                        bodyFont: { size: 11 },
                        padding: 10,
                        cornerRadius: 8,
                        callbacks: {
                            label: function(context) {
                                return context.dataset.label + ': %' + context.formattedValue;
                            }
                        }
                    }
                }
            }
        });
    }
});
</script>
