@section('title', 'Dinamik BI & Raporlama Merkezi')
<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-3">
            <div class="flex items-center gap-3">
                <div
                    class="w-10 h-10 rounded-xl bg-gradient-to-tr from-indigo-600 to-blue-500 text-white flex items-center justify-center shadow-md shadow-indigo-100 shrink-0">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                    </svg>
                </div>
                <div>
                    <h2 class="font-black text-xl sm:text-2xl text-slate-900 tracking-tight">Dinamik BI & Raporlama
                        Stüdyosu</h2>
                    <p class="text-xs sm:text-sm text-slate-500 mt-0.5">Çok boyutlu serbest pivot, yıllık kıyaslama
                        (YoY), coğrafi dünya haritası ve küme benchmark motoru</p>
                </div>
            </div>
            <div class="flex items-center gap-2 self-end md:self-auto">
                <span
                    class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-indigo-50 text-indigo-700 border border-indigo-100">
                    <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                    Canlı BI Motoru
                </span>
            </div>
        </div>
    </x-slot>

    <!-- Harita & Grafik Kütüphaneleri -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/jsvectormap/dist/css/jsvectormap.min.css" />
    <script src="https://cdn.jsdelivr.net/npm/jsvectormap/dist/js/jsvectormap.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/jsvectormap/dist/maps/world.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>

    <style>
        .jvm-tooltip {
            z-index: 2147483647 !important;
            pointer-events: none !important;
            background: transparent !important;
            border: none !important;
            padding: 0 !important;
            box-shadow: none !important;
            position: absolute !important;
        }

        #reportWorldMap svg {
            width: 100% !important;
            height: 100% !important;
            display: block;
        }

        .jvm-container {
            touch-action: pan-x pan-y !important;
        }
    </style>

    <div class="py-6 sm:py-8" x-data="reportCenter()" x-init="init()">
        <div class="w-full max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            <!-- 1. ÜST PANEL: ŞABLON SEÇİMİ VE KAYITLI VARYANTLAR (REPORT RULES) -->
            <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-4 sm:p-5">
                <div class="grid grid-cols-1 md:grid-cols-12 gap-4 items-center">
                    <!-- Form Şablonu Seçimi -->
                    <div class="md:col-span-5">
                        <label class="block text-xs font-extrabold text-slate-700 uppercase tracking-wider mb-1.5">
                            📋 Analiz Edilecek Form Şablonu
                        </label>
                        <select x-model="templateId" @change="onTemplateChange()"
                            class="w-full rounded-xl border-slate-200 text-sm font-semibold focus:border-indigo-500 focus:ring-indigo-500 shadow-2xs">
                            @foreach($templates as $tpl)
                                <option value="{{ $tpl->id }}">{{ $tpl->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Kayıtlı Rapor Varyantı (Şablon) -->
                    <div class="md:col-span-5">
                        <label class="block text-xs font-extrabold text-slate-700 uppercase tracking-wider mb-1.5">
                            ⚡ Kayıtlı Rapor Varyantı / Kuralı
                        </label>
                        <div class="flex gap-2">
                            <select x-model="selectedRuleId" @change="onRuleSelect()"
                                class="w-full rounded-xl border-slate-200 text-sm font-semibold focus:border-indigo-500 focus:ring-indigo-500 shadow-2xs">
                                <option value="">-- Özel / Anlık Analiz (Kayıtsız) --</option>
                                <template x-for="r in filteredSavedRules" :key="r.id">
                                    <option :value="r.id" x-text="r.title + (r.is_public ? ' 🌐' : '')"></option>
                                </template>
                            </select>

                            @if(Auth::user()->can('reports.create'))
                                <button type="button" @click="openSaveRuleModal()" title="Mevcut Rapor Kuralını Kaydet"
                                    class="px-3 py-2 rounded-xl bg-indigo-50 text-indigo-700 hover:bg-indigo-100 font-bold text-xs shrink-0 flex items-center gap-1 transition-colors">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4" />
                                    </svg>
                                    <span class="hidden sm:inline">Kaydet</span>
                                </button>
                                <template x-if="selectedRuleId">
                                    <button type="button" @click="deleteSelectedRule()" title="Seçili Varyantı Sil"
                                        class="px-2.5 py-2 rounded-xl bg-rose-50 text-rose-600 hover:bg-rose-100 font-bold text-xs shrink-0 transition-colors">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                    </button>
                                </template>
                            @endif
                        </div>
                    </div>

                    <!-- Otomatik Zamanlama & Görev Butonu -->
                    <div class="md:col-span-2 flex justify-start md:justify-end items-end pt-2 md:pt-0">
                        @if(Auth::user()->can('reports.schedule'))
                            <button type="button" @click="schedulesListModal = true"
                                class="w-full md:w-auto px-3.5 py-2.5 rounded-xl border border-slate-200 hover:border-indigo-300 bg-white hover:bg-slate-50 text-slate-700 font-bold text-xs shadow-2xs flex items-center justify-center gap-1.5 transition-all">
                                <svg class="w-4 h-4 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <span>Zamanlamalar</span>
                                <span
                                    class="px-1.5 py-0.5 rounded-full text-[10px] bg-indigo-100 text-indigo-700 font-extrabold"
                                    x-text="schedulesCount"></span>
                            </button>
                        @endif
                    </div>
                </div>
            </div>

            <!-- 2. ANALİZ MODU SEÇİCİ (4 TEMEL KURUMSAL MOD) -->
            <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-2">
                <div class="grid grid-cols-2 md:grid-cols-4 gap-2">
                    <button type="button" @click="setAnalysisMode('multi_pivot')"
                        :class="analysisMode === 'multi_pivot' ? 'bg-indigo-600 text-white shadow-md shadow-indigo-200' : 'bg-slate-50 text-slate-700 hover:bg-slate-100'"
                        class="px-4 py-3 rounded-xl font-extrabold text-xs sm:text-sm flex items-center justify-center gap-2 transition-all">
                        <span>📊</span>
                        <span>Çok Boyutlu Pivot</span>
                    </button>

                    <button type="button" @click="setAnalysisMode('yoy')"
                        :class="analysisMode === 'yoy' ? 'bg-indigo-600 text-white shadow-md shadow-indigo-200' : 'bg-slate-50 text-slate-700 hover:bg-slate-100'"
                        class="px-4 py-3 rounded-xl font-extrabold text-xs sm:text-sm flex items-center justify-center gap-2 transition-all">
                        <span>📅</span>
                        <span>Yıllık Kıyaslama (YoY)</span>
                    </button>

                    <button type="button" @click="setAnalysisMode('segment')"
                        :class="analysisMode === 'segment' ? 'bg-indigo-600 text-white shadow-md shadow-indigo-200' : 'bg-slate-50 text-slate-700 hover:bg-slate-100'"
                        class="px-4 py-3 rounded-xl font-extrabold text-xs sm:text-sm flex items-center justify-center gap-2 transition-all">
                        <span>⚖️</span>
                        <span>A vs B Kümeler Kıyası</span>
                    </button>

                    <button type="button" @click="setAnalysisMode('geo')"
                        :class="analysisMode === 'geo' ? 'bg-indigo-600 text-white shadow-md shadow-indigo-200' : 'bg-slate-50 text-slate-700 hover:bg-slate-100'"
                        class="px-4 py-3 rounded-xl font-extrabold text-xs sm:text-sm flex items-center justify-center gap-2 transition-all">
                        <span>🌍</span>
                        <span>Dünya Haritası</span>
                    </button>
                </div>
            </div>

            <!-- 3. KOKPİT AYARLARI VE BOYUT SEÇİCİLER (SEÇİLİ MODA GÖRE DİNAMİK DEĞİŞİR) -->
            <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-5 space-y-5">

                {{-- ─── MOD 1: ÇOK BOYUTLU PİVOT MATRİSİ KONTROLLERİ ─── --}}
                <div x-show="analysisMode === 'multi_pivot'" class="space-y-4">
                    <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                        <div>
                            <h3
                                class="text-sm font-black text-slate-900 uppercase tracking-wide flex items-center gap-2">
                                <span class="w-2.5 h-2.5 rounded-full bg-indigo-600"></span>
                                Serbest N-Boyutlu Satır Kırılımları ve Penetrasyon
                            </h3>
                            <p class="text-xs text-slate-500 mt-0.5">İstediğiniz dinamik alanları satır boyutu olarak
                                ekleyin (Örn: İşyeri ➔ Renk ➔ Gramaj veya Müşteri ➔ Malzeme)</p>
                        </div>
                        <button type="button" @click="showFilters = !showFilters"
                            class="text-xs font-bold text-indigo-600 hover:underline">
                            <span x-text="showFilters ? 'Filtreleri Gizle ▲' : 'Gelişmiş Filtreleri Aç ▼'"></span>
                        </button>
                    </div>

                    <!-- Satır Boyutları Seçici (Etiket Hapları) -->
                    <div>
                        <label class="block text-xs font-extrabold text-slate-700 mb-1.5">
                            📌 Satır Boyutları (Seçilenler Sırasıyla İç İçe Kırılır):
                        </label>
                        <div
                            class="flex flex-wrap gap-1.5 items-center p-2.5 bg-slate-50 rounded-xl border border-slate-200 min-h-[46px]">
                            <template x-for="(rKey, idx) in rowDimensions" :key="rKey">
                                <span
                                    class="inline-flex items-center gap-1.5 px-3 py-1 rounded-lg text-xs font-bold bg-indigo-600 text-white shadow-2xs">
                                    <span x-text="(idx + 1) + '. ' + getDimensionLabel(rKey)"></span>
                                    <button type="button" @click="removeRowDimension(rKey)"
                                        class="hover:text-rose-200 font-black text-sm ml-1">&times;</button>
                                </span>
                            </template>
                            <span x-show="rowDimensions.length === 0" class="text-xs text-slate-400 italic">En az bir
                                satır boyutu seçiniz...</span>
                        </div>

                        <!-- Boyut Ekleme Hapları -->
                        <div class="mt-2 flex flex-wrap gap-1 items-center">
                            <span class="text-[11px] font-bold text-slate-500 mr-1">Hızlı Boyut Ekle:</span>
                            <template x-for="dim in templateDimensions" :key="dim.id">
                                <button type="button" @click="toggleRowDimension(dim.id)"
                                    :class="rowDimensions.includes(dim.id) ? 'bg-indigo-100 text-indigo-800 border-indigo-300' : 'bg-white text-slate-700 hover:bg-slate-100 border-slate-200'"
                                    class="px-2 py-1 rounded-md text-[11px] font-semibold border transition-all">
                                    <span x-text="dim.name"></span>
                                </button>
                            </template>
                        </div>
                    </div>

                    <!-- Sütun Boyutu, Metrik ve Grafik Türü -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 pt-2">
                        <!-- Sütun Boyutu -->
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">
                                Sütun Kırılımı (Seriler)
                            </label>
                            <select x-model="colDimension"
                                class="w-full rounded-xl border-slate-200 text-xs font-semibold focus:border-indigo-500 focus:ring-indigo-500 shadow-2xs">
                                <option value="none:0">-- Kırılım Yok (Tek Sütun) --</option>
                                <optgroup label="Zaman & Sistem">
                                    <option value="entry_date_year:0">📅 Yıl (Kayıt Yılı)</option>
                                    <option value="entry_date_month:0">📅 Dönem (Ay)</option>
                                    <option value="user:0">👤 Personel Kırılımı</option>
                                    <option value="group:0">🏢 Departman Kırılımı</option>
                                </optgroup>
                                <optgroup label="Form Alanları (Dinamik)" id="colDimensionDynamicOptgroup">
                                    @foreach($initialDimensions as $dim)
                                        @if($dim['type'] === 'field')
                                            <option value="field:{{ $dim['field_id'] }}">{{ $dim['name'] }}</option>
                                        @endif
                                    @endforeach
                                </optgroup>
                            </select>
                        </div>

                        <!-- Metrik ve Agregasyon -->
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">
                                Hesaplanacak Metrik & Agregasyon <span class="text-rose-500">*</span>
                            </label>
                            <div class="flex gap-1.5">
                                <select x-model="metricFieldId"
                                    class="w-3/5 rounded-xl border-slate-200 text-xs font-semibold focus:border-indigo-500 focus:ring-indigo-500 shadow-2xs">
                                    <option value="">🔢 Satır Adedi (COUNT)</option>
                                    <optgroup label="Sayısal Metrikler (Dinamik)" id="multiPivotMetricsOptgroup"
                                        class="dynamic-metrics-optgroup">
                                        @foreach($initialMetrics as $m)
                                            @if($m['id'] !== 'count')
                                                <option value="{{ $m['field_id'] }}">{{ $m['name'] }}</option>
                                            @endif
                                        @endforeach
                                    </optgroup>
                                </select>

                                <select x-model="metricAggregation"
                                    class="w-2/5 rounded-xl border-slate-200 text-xs font-bold focus:border-indigo-500 focus:ring-indigo-500 shadow-2xs bg-slate-50">
                                    <option value="sum">TOPLAM</option>
                                    <option value="avg">ORTALAMA</option>
                                    <option value="count">ADET</option>
                                    <option value="min">EN DÜŞÜK</option>
                                    <option value="max">EN YÜKSEK</option>
                                </select>
                            </div>
                        </div>

                        <!-- Grafik Türü -->
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">Grafik Türü</label>
                            <select x-model="chartType" @change="renderChart()"
                                class="w-full rounded-xl border-slate-200 text-xs font-semibold focus:border-indigo-500 focus:ring-indigo-500 shadow-2xs">
                                <option value="bar">📊 Dikey Sütun (Bar)</option>
                                <option value="horizontalBar">📊 Yatay Sütun (Horizontal Bar)</option>
                                <option value="stackedBar">📚 Yığılmış Sütun (Stacked Bar)</option>
                                <option value="line">📈 Çizgi Trend (Line)</option>
                                <option value="pie">🥧 Pasta Dağılımı (Pie)</option>
                                <option value="doughnut">🍩 Halka Dağılımı (Doughnut)</option>
                                <option value="radar">🕸️ Radar / Çokgen</option>
                            </select>
                        </div>
                    </div>
                </div>

                {{-- ─── MOD 2: YILLIK KIYASLAMA (YoY 2026 vs 2027) KONTROLLERİ ─── --}}
                <div x-show="analysisMode === 'yoy'" class="space-y-4">
                    <div class="border-b border-slate-100 pb-3">
                        <h3 class="text-sm font-black text-slate-900 uppercase tracking-wide flex items-center gap-2">
                            <span class="w-2.5 h-2.5 rounded-full bg-emerald-600"></span>
                            Yıllık Kıyaslama (Year-over-Year Delta & Büyüme Analizi)
                        </h3>
                        <p class="text-xs text-slate-500 mt-0.5">Aytaç AY ve diğer personellerin, müşterilerin veya
                            ürünlerin 2026 vs 2027 performansını yan yana kıyaslayın</p>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                        <!-- 1. Yıl -->
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">1. Dönem / Yıl (Baz Yıl)</label>
                            <select x-model="yoyYear1"
                                class="w-full rounded-xl border-slate-200 text-xs font-bold text-blue-700 focus:border-indigo-500 focus:ring-indigo-500 shadow-2xs">
                                <option value="2026">2026 Yılı</option>
                                <option value="2025">2025 Yılı</option>
                                <option value="2024">2024 Yılı</option>
                                <option value="2027">2027 Yılı</option>
                            </select>
                        </div>

                        <!-- 2. Yıl -->
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">2. Dönem / Yıl (Karşılaştırılan
                                Yıl)</label>
                            <select x-model="yoyYear2"
                                class="w-full rounded-xl border-slate-200 text-xs font-bold text-emerald-700 focus:border-indigo-500 focus:ring-indigo-500 shadow-2xs">
                                <option value="2027">2027 Yılı</option>
                                <option value="2026">2026 Yılı</option>
                                <option value="2028">2028 Yılı</option>
                            </select>
                        </div>

                        <!-- Kıyaslanacak Varlık / Boyut -->
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">Kıyaslanacak Boyut</label>
                            <select x-model="yoyEntityDimKey"
                                class="w-full rounded-xl border-slate-200 text-xs font-semibold focus:border-indigo-500 focus:ring-indigo-500 shadow-2xs">
                                <option value="user:0">👤 Personel (Aytaç AY vb.)</option>
                                <option value="group:0">🏢 Departman / Satış Grubu</option>
                                <optgroup label="Dinamik Form Alanları" id="yoyEntityDynamicOptgroup">
                                    @foreach($initialDimensions as $dim)
                                        @if($dim['type'] === 'field')
                                            <option value="field:{{ $dim['field_id'] }}">{{ $dim['name'] }}</option>
                                        @endif
                                    @endforeach
                                </optgroup>
                            </select>
                        </div>

                        <!-- Metrik -->
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">Kıyaslanacak Metrik</label>
                            <select x-model="metricFieldId"
                                class="w-full rounded-xl border-slate-200 text-xs font-semibold focus:border-indigo-500 focus:ring-indigo-500 shadow-2xs">
                                <option value="">🔢 Satır Adedi (COUNT)</option>
                                <optgroup label="Sayısal Metrikler (Dinamik)" id="yoyMetricsOptgroup"
                                    class="dynamic-metrics-optgroup">
                                    @foreach($initialMetrics as $m)
                                        @if($m['id'] !== 'count')
                                            <option value="{{ $m['field_id'] }}">{{ $m['name'] }}</option>
                                        @endif
                                    @endforeach
                                </optgroup>
                            </select>
                        </div>
                    </div>
                </div>

                {{-- ─── MOD 3: A vs B SEGMENT BENCHMARK KONTROLLERİ ─── --}}
                <div x-show="analysisMode === 'segment'" class="space-y-4">
                    <div class="border-b border-slate-100 pb-3">
                        <h3 class="text-sm font-black text-slate-900 uppercase tracking-wide flex items-center gap-2">
                            <span class="w-2.5 h-2.5 rounded-full bg-amber-500"></span>
                            Kümeler Arası A/B Segment Karşılaştırması & Personel Katkı Payı
                        </h3>
                        <p class="text-xs text-slate-500 mt-0.5">İki farklı ürün veya operasyon kümesini (Örn: Preform
                            Mavi 25g vs Kapak Turkuaz 7.6g) baş başa benchmark edin</p>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <!-- SEGMENT A KUTUSU -->
                        <div class="p-4 rounded-2xl border-2 border-indigo-200 bg-indigo-50/40 space-y-3">
                            <div class="flex items-center justify-between gap-2 border-b border-indigo-200/60 pb-2">
                                <div class="flex items-center gap-2">
                                    <span class="text-xs font-black text-indigo-900 uppercase tracking-wide">🔵 Segment
                                        A Tanımı</span>
                                    <span x-show="getActiveFiltersCount(segA) > 0"
                                        class="bg-indigo-600 text-white text-[10px] font-black px-2 py-0.5 rounded-full shadow-2xs"
                                        x-text="getActiveFiltersCount(segA) + ' filtre aktif'"></span>
                                </div>
                                <input type="text" x-model="segA.title" placeholder="Segment A Başlığı..."
                                    class="text-xs font-bold rounded-lg border-indigo-300 bg-white px-2.5 py-1 text-indigo-950 w-1/2 focus:ring-indigo-500">
                            </div>
                            <div class="space-y-2">
                                <div class="flex items-center justify-between text-[11px]">
                                    <span class="font-extrabold text-indigo-900 flex items-center gap-1">
                                        <svg class="w-3.5 h-3.5 text-indigo-600" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
                                        </svg>
                                        Form Dinamik Alanları & Seçenekleri:
                                    </span>
                                    <button type="button" @click="clearSegmentFilters(segA)"
                                        x-show="getActiveFiltersCount(segA) > 0"
                                        class="text-[10px] font-bold text-rose-600 hover:text-rose-800 underline cursor-pointer">
                                        Filtreleri Sıfırla
                                    </button>
                                </div>

                                <!-- Kaydırılabilir Dinamik Alanlar Grid Listesi -->
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5 max-h-80 overflow-y-auto pr-1">
                                    <template x-for="f in templateFilterFields" :key="'sega_' + f.id">
                                        <div class="bg-white p-2.5 rounded-xl border transition-all shadow-2xs"
                                            :class="segA.filters.field_filters[f.id] ? 'border-indigo-500 ring-1 ring-indigo-400/30 bg-indigo-50/40' : 'border-slate-200/80 hover:border-slate-300'">
                                            <div class="flex items-center justify-between mb-1">
                                                <label class="block text-[11px] font-bold text-slate-700 truncate"
                                                    x-text="f.label" :title="f.label"></label>
                                                <span x-show="segA.filters.field_filters[f.id]"
                                                    class="w-2 h-2 rounded-full bg-indigo-600 shrink-0"
                                                    title="Aktif Kriter"></span>
                                            </div>

                                            <template x-if="f.has_options">
                                                <select x-model="segA.filters.field_filters[f.id]"
                                                    class="w-full text-xs font-semibold rounded-lg border-slate-200 py-1.5 px-2 focus:ring-indigo-500 focus:border-indigo-500 cursor-pointer"
                                                    :class="segA.filters.field_filters[f.id] ? 'bg-indigo-50/70 font-black text-indigo-950 border-indigo-400' : 'bg-slate-50 text-slate-600'">
                                                    <option value="">-- Tümü / Seçilmedi --</option>
                                                    <template x-for="opt in f.options" :key="opt">
                                                        <option :value="opt" x-text="opt"></option>
                                                    </template>
                                                </select>
                                            </template>

                                            <template x-if="!f.has_options">
                                                <input type="text" x-model="segA.filters.field_filters[f.id]"
                                                    :placeholder="f.label + ' değeri...'"
                                                    class="w-full text-xs rounded-lg border-slate-200 bg-slate-50 py-1.5 px-2 focus:ring-indigo-500">
                                            </template>
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </div>

                        <!-- SEGMENT B KUTUSU -->
                        <div class="p-4 rounded-2xl border-2 border-amber-200 bg-amber-50/40 space-y-3">
                            <div class="flex items-center justify-between gap-2 border-b border-amber-200/60 pb-2">
                                <div class="flex items-center gap-2">
                                    <span class="text-xs font-black text-amber-900 uppercase tracking-wide">🟡 Segment B
                                        Tanımı</span>
                                    <span x-show="getActiveFiltersCount(segB) > 0"
                                        class="bg-amber-600 text-white text-[10px] font-black px-2 py-0.5 rounded-full shadow-2xs"
                                        x-text="getActiveFiltersCount(segB) + ' filtre aktif'"></span>
                                </div>
                                <input type="text" x-model="segB.title" placeholder="Segment B Başlığı..."
                                    class="text-xs font-bold rounded-lg border-amber-300 bg-white px-2.5 py-1 text-amber-950 w-1/2 focus:ring-amber-500">
                            </div>
                            <div class="space-y-2">
                                <div class="flex items-center justify-between text-[11px]">
                                    <span class="font-extrabold text-amber-900 flex items-center gap-1">
                                        <svg class="w-3.5 h-3.5 text-amber-600" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
                                        </svg>
                                        Form Dinamik Alanları & Seçenekleri:
                                    </span>
                                    <button type="button" @click="clearSegmentFilters(segB)"
                                        x-show="getActiveFiltersCount(segB) > 0"
                                        class="text-[10px] font-bold text-rose-600 hover:text-rose-800 underline cursor-pointer">
                                        Filtreleri Sıfırla
                                    </button>
                                </div>

                                <!-- Kaydırılabilir Dinamik Alanlar Grid Listesi -->
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5 max-h-80 overflow-y-auto pr-1">
                                    <template x-for="f in templateFilterFields" :key="'segb_' + f.id">
                                        <div class="bg-white p-2.5 rounded-xl border transition-all shadow-2xs"
                                            :class="segB.filters.field_filters[f.id] ? 'border-amber-500 ring-1 ring-amber-400/30 bg-amber-50/40' : 'border-slate-200/80 hover:border-slate-300'">
                                            <div class="flex items-center justify-between mb-1">
                                                <label class="block text-[11px] font-bold text-slate-700 truncate"
                                                    x-text="f.label" :title="f.label"></label>
                                                <span x-show="segB.filters.field_filters[f.id]"
                                                    class="w-2 h-2 rounded-full bg-amber-600 shrink-0"
                                                    title="Aktif Kriter"></span>
                                            </div>

                                            <template x-if="f.has_options">
                                                <select x-model="segB.filters.field_filters[f.id]"
                                                    class="w-full text-xs font-semibold rounded-lg border-slate-200 py-1.5 px-2 focus:ring-amber-500 focus:border-amber-500 cursor-pointer"
                                                    :class="segB.filters.field_filters[f.id] ? 'bg-amber-50/70 font-black text-amber-950 border-amber-400' : 'bg-slate-50 text-slate-600'">
                                                    <option value="">-- Tümü / Seçilmedi --</option>
                                                    <template x-for="opt in f.options" :key="opt">
                                                        <option :value="opt" x-text="opt"></option>
                                                    </template>
                                                </select>
                                            </template>

                                            <template x-if="!f.has_options">
                                                <input type="text" x-model="segB.filters.field_filters[f.id]"
                                                    :placeholder="f.label + ' değeri...'"
                                                    class="w-full text-xs rounded-lg border-slate-200 bg-slate-50 py-1.5 px-2 focus:ring-amber-500">
                                            </template>
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">Hesaplanacak Kıyaslama
                                Metriği</label>
                            <select x-model="metricFieldId"
                                class="w-full rounded-xl border-slate-200 text-xs font-semibold focus:border-indigo-500 focus:ring-indigo-500 shadow-2xs">
                                <option value="">🔢 Satır Adedi (COUNT)</option>
                                <optgroup label="Sayısal Metrikler (Dinamik)" id="segmentMetricsOptgroup"
                                    class="dynamic-metrics-optgroup">
                                    @foreach($initialMetrics as $m)
                                        @if($m['id'] !== 'count')
                                            <option value="{{ $m['field_id'] }}">{{ $m['name'] }}</option>
                                        @endif
                                    @endforeach
                                </optgroup>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">Agregasyon Türü</label>
                            <select x-model="metricAggregation"
                                class="w-full rounded-xl border-slate-200 text-xs font-bold focus:border-indigo-500 focus:ring-indigo-500 shadow-2xs bg-slate-50">
                                <option value="sum">TOPLAM DEĞER</option>
                                <option value="avg">ORTALAMA DEĞER</option>
                                <option value="count">KAYIT SAYISI</option>
                            </select>
                        </div>
                    </div>
                </div>

                {{-- ─── MOD 4: COĞRAFİ DÜNYA HARİTASI KONTROLLERİ ─── --}}
                <div x-show="analysisMode === 'geo'" class="space-y-4">
                    <div class="border-b border-slate-100 pb-3">
                        <h3 class="text-sm font-black text-slate-900 uppercase tracking-wide flex items-center gap-2">
                            <span class="w-2.5 h-2.5 rounded-full bg-blue-600"></span>
                            Coğrafi Dünya Isı Haritası & Ülke Pazar Payı
                        </h3>
                        <p class="text-xs text-slate-500 mt-0.5">Ülkelere göre sipariş ve tonaj yoğunluğunu interaktif
                            haritada görüntüleyin, ülke üzerine gelerek personelleri inceleyin</p>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">Ülke Alanı (Master Data /
                                Metin)</label>
                            <select x-model="geoCountryFieldId" id="geoCountryFieldSelect"
                                class="w-full rounded-xl border-slate-200 text-xs font-semibold focus:border-indigo-500 focus:ring-indigo-500 shadow-2xs">
                                <option value="">-- Otomatik Tespit Et ('Ülke' Alanı) --</option>
                                @foreach($initialDimensions as $dim)
                                    @if($dim['type'] === 'field')
                                        <option value="{{ $dim['field_id'] }}">{{ $dim['name'] }}</option>
                                    @endif
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">Haritada Renklendirilecek
                                Metrik</label>
                            <select x-model="metricFieldId"
                                class="w-full rounded-xl border-slate-200 text-xs font-semibold focus:border-indigo-500 focus:ring-indigo-500 shadow-2xs">
                                <option value="">🔢 Satır Adedi (COUNT)</option>
                                <optgroup label="Sayısal Metrikler (Dinamik)" id="geoMetricsOptgroup"
                                    class="dynamic-metrics-optgroup">
                                    @foreach($initialMetrics as $m)
                                        @if($m['id'] !== 'count')
                                            <option value="{{ $m['field_id'] }}">{{ $m['name'] }}</option>
                                        @endif
                                    @endforeach
                                </optgroup>
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">Agregasyon</label>
                            <select x-model="metricAggregation"
                                class="w-full rounded-xl border-slate-200 text-xs font-bold focus:border-indigo-500 focus:ring-indigo-500 shadow-2xs bg-slate-50">
                                <option value="sum">TOPLAM</option>
                                <option value="avg">ORTALAMA</option>
                                <option value="count">ADET</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- GELİŞMİŞ FİLTRELER BÖLÜMÜ (AÇILIR / KAPANIR) -->
                <div x-show="showFilters" x-transition.duration.200ms
                    class="pt-4 border-t border-slate-100 space-y-4 bg-slate-50/70 p-4 rounded-xl">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-extrabold text-slate-600 uppercase tracking-wider">🎯 Ortak Kriter ve
                            Veri Süzgeçleri</span>
                        <button type="button" @click="resetFilters()"
                            class="text-[11px] text-rose-600 hover:underline font-bold">Filtreleri Temizle</button>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                        <!-- Personel Filtresi (Çoktan Seçmeli Dropdown) -->
                        <div class="relative" x-data="{ userSearch: '' }" @click.away="userDropdownOpen = false">
                            <label
                                class="block text-[11px] font-bold text-slate-700 mb-1 flex items-center justify-between">
                                <span>👤 Personel(ler)</span>
                                <button type="button" x-show="filters.user_ids && filters.user_ids.length > 0"
                                    @click="filters.user_ids = []"
                                    class="text-[10px] text-rose-500 hover:underline font-bold">Temizle</button>
                            </label>

                            <!-- Tetikleyici Buton -->
                            <button type="button" @click="userDropdownOpen = !userDropdownOpen"
                                class="w-full text-left rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-semibold focus:border-indigo-500 focus:ring-indigo-500 shadow-2xs flex items-center justify-between transition-colors">
                                <div class="truncate flex items-center gap-1.5 pr-2">
                                    <template x-if="!filters.user_ids || filters.user_ids.length === 0">
                                        <span class="text-slate-400">Tüm Personeller (Filtresiz)</span>
                                    </template>
                                    <template x-if="filters.user_ids && filters.user_ids.length === 1">
                                        <span class="text-slate-800 font-bold"
                                            x-text="getUserName(filters.user_ids[0])"></span>
                                    </template>
                                    <template x-if="filters.user_ids && filters.user_ids.length > 1">
                                        <span class="text-indigo-700 font-extrabold flex items-center gap-1 truncate">
                                            <span class="px-1.5 py-0.5 rounded-md bg-indigo-50 text-[10px]"
                                                x-text="filters.user_ids.length + ' Personel'"></span>
                                            <span class="text-slate-700 font-bold truncate"
                                                x-text="getUserName(filters.user_ids[0]) + ' + ' + (filters.user_ids.length - 1) + ' kişi'"></span>
                                        </span>
                                    </template>
                                </div>
                                <svg class="w-4 h-4 text-slate-400 shrink-0 transition-transform duration-200"
                                    :class="userDropdownOpen ? 'rotate-180' : ''" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M19 9l-7 7-7-7" />
                                </svg>
                            </button>

                            <!-- Açılır Panel -->
                            <div x-show="userDropdownOpen" x-transition.origin.top.duration.150ms
                                class="absolute left-0 top-full mt-1.5 w-full sm:w-80 bg-white rounded-2xl border border-slate-200 shadow-xl z-30 p-3 space-y-2.5"
                                x-cloak>

                                <!-- Arama Alanı -->
                                <div class="relative">
                                    <input type="text" x-model="userSearch" placeholder="Personel veya grup ara..."
                                        class="w-full rounded-xl border-slate-200 text-xs pl-8 pr-3 py-1.5 focus:border-indigo-500 focus:ring-indigo-500 bg-slate-50">
                                    <svg class="w-3.5 h-3.5 text-slate-400 absolute left-2.5 top-2.5" fill="none"
                                        stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                    </svg>
                                </div>

                                <!-- Hızlı İşlemler -->
                                <div
                                    class="flex items-center justify-between text-[11px] font-bold px-1 border-b border-slate-100 pb-1.5">
                                    <button type="button" @click="selectAllVisibleUsers(userSearch)"
                                        class="text-indigo-600 hover:underline">Görünenleri Seç</button>
                                    <button type="button" @click="filters.user_ids = []"
                                        class="text-slate-400 hover:text-rose-500">Seçimleri Kaldır</button>
                                </div>

                                <!-- Personel Listesi -->
                                <div class="max-h-52 overflow-y-auto divide-y divide-slate-50 pr-1 space-y-0.5">
                                    <template x-for="u in filteredUsers(userSearch)" :key="u.id">
                                        <label
                                            class="flex items-center justify-between p-2 rounded-xl hover:bg-indigo-50/50 cursor-pointer select-none transition-colors">
                                            <div class="flex items-center gap-2 truncate">
                                                <input type="checkbox" :value="u.id" x-model="filters.user_ids"
                                                    class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500 w-4 h-4">
                                                <span class="text-xs font-bold text-slate-800 truncate"
                                                    x-text="u.name"></span>
                                            </div>
                                            <span class="text-[10px] px-1.5 py-0.5 rounded-md font-semibold shrink-0"
                                                :class="u.group_name === 'Grup Yok' ? 'bg-slate-100 text-slate-500' : 'bg-indigo-50 text-indigo-700'"
                                                x-text="u.group_name"></span>
                                        </label>
                                    </template>
                                    <div x-show="filteredUsers(userSearch).length === 0"
                                        class="text-center py-4 text-xs text-slate-400">
                                        Personel bulunamadı.
                                    </div>
                                </div>
                            </div>

                            <!-- Seçili Personel Rozetleri (Hızlı Kaldırma) -->
                            <div x-show="filters.user_ids && filters.user_ids.length > 0"
                                class="flex flex-wrap gap-1 mt-1.5">
                                <template x-for="uId in (filters.user_ids || []).slice(0, 3)" :key="'chip_u_' + uId">
                                    <span
                                        class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[10px] font-bold bg-indigo-50 text-indigo-700 border border-indigo-100">
                                        <span x-text="getUserName(uId)"></span>
                                        <button type="button" @click="removeUser(uId)"
                                            class="hover:text-rose-600 font-black">&times;</button>
                                    </span>
                                </template>
                                <template x-if="filters.user_ids && filters.user_ids.length > 3">
                                    <span class="text-[10px] text-slate-400 font-bold self-center"
                                        x-text="'+' + (filters.user_ids.length - 3) + ' kişi'"></span>
                                </template>
                            </div>
                        </div>

                        <!-- Departman Filtresi (Çoktan Seçmeli Dropdown) -->
                        <div class="relative" x-data="{ groupSearch: '' }" @click.away="groupDropdownOpen = false">
                            <label
                                class="block text-[11px] font-bold text-slate-700 mb-1 flex items-center justify-between">
                                <span>🏢 Departman / Grup</span>
                                <button type="button" x-show="filters.group_ids && filters.group_ids.length > 0"
                                    @click="filters.group_ids = []"
                                    class="text-[10px] text-rose-500 hover:underline font-bold">Temizle</button>
                            </label>

                            <!-- Tetikleyici Buton -->
                            <button type="button" @click="groupDropdownOpen = !groupDropdownOpen"
                                class="w-full text-left rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-semibold focus:border-indigo-500 focus:ring-indigo-500 shadow-2xs flex items-center justify-between transition-colors">
                                <div class="truncate flex items-center gap-1.5 pr-2">
                                    <template x-if="!filters.group_ids || filters.group_ids.length === 0">
                                        <span class="text-slate-400">Tüm Departmanlar (Filtresiz)</span>
                                    </template>
                                    <template x-if="filters.group_ids && filters.group_ids.length === 1">
                                        <span class="text-slate-800 font-bold"
                                            x-text="getGroupName(filters.group_ids[0])"></span>
                                    </template>
                                    <template x-if="filters.group_ids && filters.group_ids.length > 1">
                                        <span class="text-indigo-700 font-extrabold flex items-center gap-1 truncate">
                                            <span class="px-1.5 py-0.5 rounded-md bg-indigo-50 text-[10px]"
                                                x-text="filters.group_ids.length + ' Departman'"></span>
                                            <span class="text-slate-700 font-bold truncate"
                                                x-text="getGroupName(filters.group_ids[0]) + ' + ' + (filters.group_ids.length - 1) + ' grup'"></span>
                                        </span>
                                    </template>
                                </div>
                                <svg class="w-4 h-4 text-slate-400 shrink-0 transition-transform duration-200"
                                    :class="groupDropdownOpen ? 'rotate-180' : ''" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M19 9l-7 7-7-7" />
                                </svg>
                            </button>

                            <!-- Açılır Panel -->
                            <div x-show="groupDropdownOpen" x-transition.origin.top.duration.150ms
                                class="absolute left-0 top-full mt-1.5 w-full sm:w-80 bg-white rounded-2xl border border-slate-200 shadow-xl z-30 p-3 space-y-2.5"
                                x-cloak>

                                <!-- Arama Alanı -->
                                <div class="relative">
                                    <input type="text" x-model="groupSearch" placeholder="Departman ara..."
                                        class="w-full rounded-xl border-slate-200 text-xs pl-8 pr-3 py-1.5 focus:border-indigo-500 focus:ring-indigo-500 bg-slate-50">
                                    <svg class="w-3.5 h-3.5 text-slate-400 absolute left-2.5 top-2.5" fill="none"
                                        stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                    </svg>
                                </div>

                                <!-- Hızlı İşlemler -->
                                <div
                                    class="flex items-center justify-between text-[11px] font-bold px-1 border-b border-slate-100 pb-1.5">
                                    <button type="button" @click="selectAllVisibleGroups(groupSearch)"
                                        class="text-indigo-600 hover:underline">Görünenleri Seç</button>
                                    <button type="button" @click="filters.group_ids = []"
                                        class="text-slate-400 hover:text-rose-500">Seçimleri Kaldır</button>
                                </div>

                                <!-- Grup Listesi -->
                                <div class="max-h-52 overflow-y-auto divide-y divide-slate-50 pr-1 space-y-0.5">
                                    <template x-for="g in filteredGroups(groupSearch)" :key="g.id">
                                        <label
                                            class="flex items-center gap-2 p-2 rounded-xl hover:bg-indigo-50/50 cursor-pointer select-none transition-colors">
                                            <input type="checkbox" :value="g.id" x-model="filters.group_ids"
                                                class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500 w-4 h-4">
                                            <span class="text-xs font-bold text-slate-800 truncate"
                                                x-text="g.name"></span>
                                        </label>
                                    </template>
                                    <div x-show="filteredGroups(groupSearch).length === 0"
                                        class="text-center py-4 text-xs text-slate-400">
                                        Departman bulunamadı.
                                    </div>
                                </div>
                            </div>

                            <!-- Seçili Departman Rozetleri (Hızlı Kaldırma) -->
                            <div x-show="filters.group_ids && filters.group_ids.length > 0"
                                class="flex flex-wrap gap-1 mt-1.5">
                                <template x-for="gId in (filters.group_ids || []).slice(0, 3)" :key="'chip_g_' + gId">
                                    <span
                                        class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[10px] font-bold bg-indigo-50 text-indigo-700 border border-indigo-100">
                                        <span x-text="getGroupName(gId)"></span>
                                        <button type="button" @click="removeGroup(gId)"
                                            class="hover:text-rose-600 font-black">&times;</button>
                                    </span>
                                </template>
                                <template x-if="filters.group_ids && filters.group_ids.length > 3">
                                    <span class="text-[10px] text-slate-400 font-bold self-center"
                                        x-text="'+' + (filters.group_ids.length - 3) + ' grup'"></span>
                                </template>
                            </div>
                        </div>

                        <!-- Form Durumu / Statü (Çoktan Seçmeli Dropdown) -->
                        <div class="relative" @click.away="statusDropdownOpen = false">
                            <label
                                class="block text-[11px] font-bold text-slate-700 mb-1 flex items-center justify-between">
                                <span>🏷️ Form Onay Durumu</span>
                                <div class="flex items-center gap-1.5">
                                    <button type="button" @click="setStatusPreset('only_approved')"
                                        class="text-[10px] text-emerald-600 hover:underline font-bold">Onaylılar</button>
                                    <span class="text-slate-300">&bull;</span>
                                    <button type="button" @click="selectAllStatuses()"
                                        class="text-[10px] text-indigo-600 hover:underline font-bold">Tümü</button>
                                </div>
                            </label>

                            <!-- Tetikleyici Buton -->
                            <button type="button" @click="statusDropdownOpen = !statusDropdownOpen"
                                class="w-full text-left rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-semibold focus:border-indigo-500 focus:ring-indigo-500 shadow-2xs flex items-center justify-between transition-colors">
                                <div class="truncate flex items-center gap-1.5 pr-2">
                                    <template x-if="!filters.status || filters.status.length === 0">
                                        <span class="text-rose-500 font-bold">Durum Seçiniz</span>
                                    </template>
                                    <template
                                        x-if="filters.status && filters.status.length === 1 && filters.status[0] === 'approved'">
                                        <span class="text-emerald-700 font-extrabold flex items-center gap-1.5">
                                            <span class="w-2 h-2 rounded-full bg-emerald-500 shrink-0"></span>
                                            <span>Onaylananlar (Varsayılan)</span>
                                        </span>
                                    </template>
                                    <template
                                        x-if="filters.status && filters.status.length === 1 && filters.status[0] !== 'approved'">
                                        <span class="font-bold flex items-center gap-1.5"
                                            :class="getStatusOption(filters.status[0]).text_class">
                                            <span class="w-2 h-2 rounded-full shrink-0"
                                                :class="getStatusOption(filters.status[0]).dot_class"></span>
                                            <span x-text="getStatusOption(filters.status[0]).label"></span>
                                        </span>
                                    </template>
                                    <template x-if="filters.status && filters.status.length > 1">
                                        <span class="font-black flex items-center gap-1.5 truncate"
                                            :class="filters.status.length === statusOptions.length ? 'text-indigo-700' : 'text-amber-800'">
                                            <span class="px-1.5 py-0.5 rounded-md text-[10px]"
                                                :class="filters.status.length === statusOptions.length ? 'bg-indigo-100 text-indigo-800' : 'bg-amber-100 text-amber-900'"
                                                x-text="filters.status.length + ' Durum'"></span>
                                            <span class="truncate" x-text="getStatusButtonLabel()"></span>
                                        </span>
                                    </template>
                                </div>
                                <svg class="w-4 h-4 text-slate-400 shrink-0 transition-transform duration-200"
                                    :class="statusDropdownOpen ? 'rotate-180' : ''" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M19 9l-7 7-7-7" />
                                </svg>
                            </button>

                            <!-- Açılır Panel -->
                            <div x-show="statusDropdownOpen" x-transition.origin.top.duration.150ms
                                class="absolute left-0 top-full mt-1.5 w-full sm:w-80 bg-white rounded-2xl border border-slate-200 shadow-xl z-30 p-3 space-y-2.5"
                                x-cloak>

                                <!-- Hızlı İşlemler -->
                                <div
                                    class="flex items-center justify-between text-[11px] font-bold px-1 border-b border-slate-100 pb-1.5">
                                    <button type="button" @click="setStatusPreset('only_approved')"
                                        class="text-emerald-700 hover:underline">Sadece Onaylananlar</button>
                                    <button type="button" @click="selectAllStatuses()"
                                        class="text-indigo-600 hover:underline">Tümünü Seç</button>
                                    <button type="button" @click="filters.status = []"
                                        class="text-slate-400 hover:text-rose-500">Temizle</button>
                                </div>

                                <!-- Statü Seçim Listesi -->
                                <div class="space-y-1">
                                    <template x-for="st in statusOptions" :key="st.key">
                                        <label
                                            class="flex items-center justify-between p-2 rounded-xl hover:bg-slate-50 cursor-pointer select-none transition-colors border border-transparent"
                                            :class="filters.status && filters.status.includes(st.key) ? (st.bg_class + ' border-indigo-200') : ''">
                                            <div class="flex items-center gap-2.5 truncate">
                                                <input type="checkbox" :value="st.key" x-model="filters.status"
                                                    class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500 w-4 h-4">
                                                <span class="w-2 h-2 rounded-full shrink-0"
                                                    :class="st.dot_class"></span>
                                                <div class="truncate">
                                                    <span class="text-xs font-bold text-slate-800 block truncate"
                                                        x-text="st.label"></span>
                                                    <span class="text-[10px] text-slate-400 block truncate"
                                                        x-text="st.desc"></span>
                                                </div>
                                            </div>
                                            <span
                                                class="text-[10px] px-1.5 py-0.5 rounded font-mono font-bold shrink-0 ml-1"
                                                :class="st.bg_class + ' ' + st.text_class" x-text="st.row_label"></span>
                                        </label>
                                    </template>
                                </div>
                            </div>

                            <!-- Seçili Statü Rozetleri (Hızlı Kaldırma) -->
                            <div x-show="filters.status && filters.status.length > 0"
                                class="flex flex-wrap gap-1 mt-1.5">
                                <template x-for="stKey in filters.status" :key="'chip_st_' + stKey">
                                    <span
                                        class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[10px] font-bold border"
                                        :class="getStatusOption(stKey).bg_class + ' ' + getStatusOption(stKey).text_class + ' ' + getStatusOption(stKey).border_class">
                                        <span class="w-1.5 h-1.5 rounded-full"
                                            :class="getStatusOption(stKey).dot_class"></span>
                                        <span x-text="getStatusOption(stKey).row_label"></span>
                                        <button type="button" @click="removeStatus(stKey)"
                                            class="hover:opacity-75 font-black ml-0.5">&times;</button>
                                    </span>
                                </template>
                            </div>
                        </div>

                        <!-- Tarih Aralığı -->
                        <div class="space-y-1.5">
                            <label class="block text-[11px] font-bold text-slate-700">📅 Tarih Aralığı (Giriş
                                Tarihi)</label>
                            <div class="grid grid-cols-2 gap-1.5">
                                <div>
                                    <input type="date" x-model="filters.start_date" title="Başlangıç Tarihi"
                                        class="w-full rounded-xl border-slate-200 text-[11px] py-1.5 px-2 focus:border-indigo-500 focus:ring-indigo-500 bg-white">
                                </div>
                                <div>
                                    <input type="date" x-model="filters.end_date" title="Bitiş Tarihi"
                                        class="w-full rounded-xl border-slate-200 text-[11px] py-1.5 px-2 focus:border-indigo-500 focus:ring-indigo-500 bg-white">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Hızlı Statü Kısayol Butonları -->
                    <div class="flex flex-wrap items-center gap-1.5 pt-2 border-t border-slate-200/60 text-xs">
                        <span class="text-[11px] font-extrabold text-slate-500 mr-1">⚡ Hızlı Statü Kapsamı:</span>
                        <button type="button" @click="setStatusPreset('only_approved')"
                            :class="(filters.status && filters.status.length === 1 && filters.status.includes('approved')) ? 'bg-emerald-600 text-white shadow-xs' : 'bg-white hover:bg-emerald-50 text-emerald-800 border-emerald-200'"
                            class="px-2.5 py-1 rounded-lg text-xs font-bold border transition-all flex items-center gap-1.5">
                            <span class="w-2 h-2 rounded-full"
                                :class="(filters.status && filters.status.length === 1 && filters.status.includes('approved')) ? 'bg-white' : 'bg-emerald-500'"></span>
                            <span>Sadece Onaylananlar (Varsayılan)</span>
                        </button>

                        <button type="button" @click="setStatusPreset('all')"
                            :class="(filters.status && filters.status.length === statusOptions.length) ? 'bg-indigo-600 text-white shadow-xs' : 'bg-white hover:bg-indigo-50 text-indigo-700 border-slate-200'"
                            class="px-2.5 py-1 rounded-lg text-xs font-bold border transition-all">
                            <span>Tüm Durumlar</span>
                        </button>

                        <button type="button" @click="setStatusPreset('approved_draft')"
                            :class="(filters.status && filters.status.length === 2 && filters.status.includes('approved') && filters.status.includes('draft')) ? 'bg-indigo-600 text-white shadow-xs' : 'bg-white hover:bg-slate-100 text-slate-700 border-slate-200'"
                            class="px-2.5 py-1 rounded-lg text-xs font-bold border transition-all">
                            <span>Onaylı + Taslak</span>
                        </button>

                        <button type="button" @click="setStatusPreset('pending_approved')"
                            :class="(filters.status && filters.status.length === 2 && filters.status.includes('approved') && filters.status.includes('submitted')) ? 'bg-amber-600 text-white shadow-xs' : 'bg-white hover:bg-amber-50 text-amber-800 border-amber-200'"
                            class="px-2.5 py-1 rounded-lg text-xs font-bold border transition-all">
                            <span>Onay Bekleyenler + Onaylı</span>
                        </button>

                        <button type="button" @click="setStatusPreset('only_draft')"
                            :class="(filters.status && filters.status.length === 1 && filters.status.includes('draft')) ? 'bg-slate-700 text-white shadow-xs' : 'bg-white hover:bg-slate-100 text-slate-700 border-slate-200'"
                            class="px-2.5 py-1 rounded-lg text-xs font-bold border transition-all">
                            <span>Sadece Taslaklar</span>
                        </button>

                        <button type="button" @click="setStatusPreset('only_rejected')"
                            :class="(filters.status && filters.status.length === 1 && filters.status.includes('rejected')) ? 'bg-rose-600 text-white shadow-xs' : 'bg-white hover:bg-rose-50 text-rose-800 border-rose-200'"
                            class="px-2.5 py-1 rounded-lg text-xs font-bold border transition-all">
                            <span>Sadece Reddedilenler</span>
                        </button>
                    </div>
                </div>

                <!-- ÇALIŞTIR VE DIŞA AKTAR BUTONLARI -->
                <div
                    class="flex flex-col sm:flex-row items-center justify-between gap-3 pt-3 border-t border-slate-100">
                    <button type="button" @click="runReport()" :disabled="loading"
                        class="w-full sm:w-auto px-6 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-black text-sm shadow-md shadow-indigo-200 flex items-center justify-center gap-2 transition-all active:scale-95">
                        <svg x-show="!loading" class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd"
                                d="M10 18a8 8 0 100-16 8 8 0 000 16zM9.555 7.168A1 1 0 008 8v4a1 1 0 001.555.832l3-2a1 1 0 000-1.664l-3-2z"
                                clip-rule="evenodd" />
                        </svg>
                        <svg x-show="loading" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none"
                            viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4">
                            </circle>
                            <path class="opacity-75" fill="currentColor"
                                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                            </path>
                        </svg>
                        <span x-text="loading ? 'Analiz Hesaplanıyor...' : 'Raporu Çalıştır'"></span>
                    </button>

                    <div class="flex items-center gap-2 w-full sm:w-auto justify-end">
                        @if(Auth::user()->can('reports.export'))
                            <button type="button" @click="downloadExcel()"
                                class="px-3.5 py-2 rounded-xl border border-slate-200 hover:border-emerald-300 bg-white hover:bg-emerald-50/50 text-slate-700 hover:text-emerald-700 font-bold text-xs shadow-2xs flex items-center gap-1.5 transition-all">
                                <span class="text-emerald-600 font-black">XLS</span>
                                <span>Excel İndir</span>
                            </button>
                        @endif

                        <button type="button" @click="printReport()"
                            class="px-3.5 py-2 rounded-xl border border-slate-200 hover:border-slate-300 bg-white hover:bg-slate-50 text-slate-700 font-bold text-xs shadow-2xs flex items-center gap-1.5 transition-all">
                            <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                            </svg>
                            <span>Yazdır / PDF</span>
                        </button>

                        @if(Auth::user()->can('reports.share'))
                            <button type="button" @click="shareModal = true"
                                class="px-3.5 py-2 rounded-xl border border-slate-200 hover:border-indigo-300 bg-white hover:bg-indigo-50/50 text-slate-700 hover:text-indigo-700 font-bold text-xs shadow-2xs flex items-center gap-1.5 transition-all">
                                <svg class="w-4 h-4 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                </svg>
                                <span>E-Posta Paylaş</span>
                            </button>
                        @endif
                    </div>
                </div>
            </div>

            <!-- 4. YÜKLENİYOR DURUMU -->
            <div x-show="loading"
                class="bg-white rounded-2xl border border-slate-200 p-12 text-center space-y-3 shadow-xs">
                <div
                    class="inline-block animate-spin rounded-full h-10 w-10 border-4 border-indigo-600 border-t-transparent">
                </div>
                <h4 class="text-base font-extrabold text-slate-800">Veri Seti İşleniyor ve Analiz Ediliyor</h4>
                <p class="text-xs text-slate-500">Form satırları dinamik boyutlara ve agregasyon kurallarına göre
                    birleştiriliyor...</p>
            </div>

            <!-- 5. SONUÇ GÖRÜNÜMÜ ALANI (SEÇİLİ MODA GÖRE GÖSTERİM) -->
            <div x-show="!loading && hasReportData" class="space-y-6">

                <!-- 📌 RAPOR STATÜ KAPSAMI BİLDİRİM BANNERI -->
                <template x-if="reportData && reportData.status_scope">
                    <div :class="reportData.status_scope.is_multi ? 'bg-amber-50/90 border-amber-200 text-amber-950' : (reportData.status_scope.is_default ? 'bg-emerald-50/90 border-emerald-200 text-emerald-950' : 'bg-slate-50 border-slate-200 text-slate-800')"
                        class="p-4 rounded-2xl border shadow-xs flex flex-col md:flex-row md:items-center justify-between gap-3 transition-all">
                        <div class="flex items-start sm:items-center gap-3">
                            <span
                                class="inline-flex items-center gap-1.5 px-3 py-1 rounded-xl text-xs font-black uppercase tracking-wider shrink-0 shadow-2xs"
                                :class="reportData.status_scope.is_multi ? 'bg-amber-200 text-amber-900' : (reportData.status_scope.is_default ? 'bg-emerald-200 text-emerald-900' : 'bg-slate-200 text-slate-800')">
                                <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd"
                                        d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z"
                                        clip-rule="evenodd" />
                                </svg>
                                <span>RAPOR KAPSAMI</span>
                            </span>
                            <div>
                                <p class="text-xs sm:text-sm font-bold"
                                    x-text="reportData.status_scope.description_text"></p>
                                <template x-if="reportData.status_scope.is_multi">
                                    <p class="text-[11px] text-amber-800 font-semibold mt-0.5">
                                        ⚠️ Rapor birden fazla onay statüsündeki kayıtları birlikte içermektedir. Taslak
                                        veya henüz onaylanmamış kayıtlar sayısal toplamları etkileyebilir.
                                    </p>
                                </template>
                            </div>
                        </div>
                        <div class="flex items-center gap-1.5 flex-wrap shrink-0">
                            <template x-for="badge in (reportData.status_scope.badges || [])"
                                :key="'scope_b_' + badge.key">
                                <span
                                    class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-bold border shadow-2xs"
                                    :class="badge.bg_class + ' ' + badge.text_class + ' ' + badge.border_class">
                                    <span class="w-2 h-2 rounded-full" :class="badge.dot_class"></span>
                                    <span x-text="badge.label"></span>
                                </span>
                            </template>
                        </div>
                    </div>
                </template>

                {{-- ═══════════════════════════════════════════════════════════════ --}}
                {{-- A) MOD 1: ÇOK BOYUTLU PİVOT MATRİSİ ÇIKTISI --}}
                {{-- ═══════════════════════════════════════════════════════════════ --}}
                <div x-show="analysisMode === 'multi_pivot'" class="space-y-6">
                    <!-- Özet Kartlar -->
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                        <div class="bg-white rounded-2xl p-4 border border-slate-200 shadow-2xs">
                            <span class="text-[11px] font-bold text-slate-400 uppercase block">Toplam Boyut
                                Satırı</span>
                            <span class="text-xl sm:text-2xl font-black text-slate-800 mt-1 block"
                                x-text="reportData.summary ? reportData.summary.total_rows : 0"></span>
                        </div>
                        <div class="bg-white rounded-2xl p-4 border border-slate-200 shadow-2xs">
                            <span class="text-[11px] font-bold text-slate-400 uppercase block">İncelenen Kayıt</span>
                            <span class="text-xl sm:text-2xl font-black text-slate-800 mt-1 block"
                                x-text="reportData.summary ? reportData.summary.analyzed_records : 0"></span>
                        </div>
                        <div class="bg-white rounded-2xl p-4 border border-slate-200 shadow-2xs">
                            <span class="text-[11px] font-bold text-slate-400 uppercase block">Sütun Kırılımı</span>
                            <span class="text-xl sm:text-2xl font-black text-indigo-600 mt-1 block"
                                x-text="reportData.col_dimension_name || 'Tek Sütun'"></span>
                        </div>
                        <div
                            class="bg-gradient-to-tr from-indigo-900 to-indigo-700 text-white rounded-2xl p-4 shadow-sm">
                            <span class="text-[11px] font-bold text-indigo-200 uppercase block"
                                x-text="reportData.full_metric_title || 'Genel Toplam'"></span>
                            <span class="text-xl sm:text-2xl font-black text-white mt-1 block"
                                x-text="formatDecimal(reportData.grand_total)"></span>
                        </div>
                    </div>

                    <!-- Grafik Alanı -->
                    <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-xs">
                        <div class="flex items-center justify-between border-b border-slate-100 pb-3 mb-4">
                            <h4 class="text-sm font-black text-slate-900 flex items-center gap-2">
                                <span>📊</span>
                                <span>Önde Gelen Kırılımlar Grafiği (Top 15)</span>
                            </h4>
                            <span class="text-xs text-slate-400 font-semibold"
                                x-text="reportData.full_metric_title"></span>
                        </div>
                        <div class="w-full h-80 sm:h-96 relative">
                            <canvas id="multiPivotChartCanvas"></canvas>
                        </div>
                    </div>

                    <!-- Çok Boyutlu Pivot Tablosu (N-Boyutlu Başlıklar ve % Penetrasyon) -->
                    <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden shadow-xs">
                        <div class="p-4 bg-slate-50 border-b border-slate-200 flex items-center justify-between">
                            <div>
                                <h4 class="text-sm font-black text-slate-900 flex items-center gap-2">
                                    <span>📑</span>
                                    <span>Çok Boyutlu Çapraz Pivot Tablosu</span>
                                </h4>
                                <p class="text-xs text-slate-500 mt-0.5">Satır kırılımları, sütun dağılımları ve genel
                                    toplama göre % penetrasyon payı</p>
                            </div>
                        </div>

                        <div class="overflow-x-auto max-h-[600px] overflow-y-auto">
                            <table class="w-full text-left border-collapse text-xs">
                                <thead class="bg-slate-900 text-white sticky top-0 z-10 shadow-xs">
                                    <tr>
                                        <!-- Satır Boyut Başlıkları -->
                                        <template x-for="rName in (reportData.row_dimension_names || [])" :key="rName">
                                            <th class="py-3 px-3 font-extrabold uppercase tracking-wider border-r border-slate-800"
                                                x-text="rName"></th>
                                        </template>

                                        <!-- Sütun Başlıkları -->
                                        <template x-for="col in (reportData.columns || [])" :key="col">
                                            <th class="py-3 px-3 font-extrabold text-right uppercase tracking-wider border-r border-slate-800"
                                                x-text="col"></th>
                                        </template>

                                        <!-- Genel Toplam ve Penetrasyon -->
                                        <th
                                            class="py-3 px-4 font-black text-right uppercase tracking-wider bg-indigo-900 text-white">
                                            GENEL TOPLAM</th>
                                        <th
                                            class="py-3 px-4 font-black text-right uppercase tracking-wider bg-indigo-950 text-indigo-300">
                                            % PAY (PENETRASYON)</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                    <template x-for="row in (reportData.rows || [])" :key="row.composite_key">
                                        <tr class="hover:bg-indigo-50/40 transition-colors">
                                            <!-- Boyut Değerleri -->
                                            <template x-for="dVal in (row.dim_values || [])" :key="dVal">
                                                <td class="py-2.5 px-3 font-bold text-slate-800 border-r border-slate-100"
                                                    x-text="dVal"></td>
                                            </template>

                                            <!-- Sütun Değerleri -->
                                            <template x-for="col in (reportData.columns || [])" :key="col">
                                                <td class="py-2.5 px-3 text-right font-mono text-slate-700 border-r border-slate-100"
                                                    x-text="formatDecimal(row.values[col] || 0)"></td>
                                            </template>

                                            <!-- Satır Toplamı -->
                                            <td class="py-2.5 px-4 text-right font-mono font-black text-slate-900 bg-slate-50"
                                                x-text="formatDecimal(row.row_total)"></td>

                                            <!-- % Penetrasyon -->
                                            <td
                                                class="py-2.5 px-4 text-right font-mono font-extrabold text-indigo-700 bg-indigo-50/50">
                                                <div class="flex items-center justify-end gap-2">
                                                    <span
                                                        x-text="'%' + Number(row.penetration_share).toFixed(2)"></span>
                                                    <div class="w-12 bg-slate-200 rounded-full h-1.5 overflow-hidden">
                                                        <div class="bg-indigo-600 h-1.5 rounded-full"
                                                            :style="'width: ' + Math.min(row.penetration_share, 100) + '%'">
                                                        </div>
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                    </template>
                                </tbody>
                                <tfoot
                                    class="bg-slate-100 font-black text-slate-900 border-t-2 border-slate-300 sticky bottom-0">
                                    <tr>
                                        <td :colspan="(reportData.row_dimension_names || []).length"
                                            class="py-3 px-3 uppercase tracking-wider">
                                            GENEL TOPLAM
                                        </td>
                                        <template x-for="col in (reportData.columns || [])" :key="'foot_' + col">
                                            <td class="py-3 px-3 text-right font-mono font-black text-indigo-900"
                                                x-text="formatDecimal(reportData.column_totals ? reportData.column_totals[col] : 0)">
                                            </td>
                                        </template>
                                        <td class="py-3 px-4 text-right font-mono font-black text-indigo-950 bg-indigo-100"
                                            x-text="formatDecimal(reportData.grand_total)"></td>
                                        <td
                                            class="py-3 px-4 text-right font-mono font-black text-indigo-950 bg-indigo-200">
                                            %100,00</td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>

                {{-- ═══════════════════════════════════════════════════════════════ --}}
                {{-- B) MOD 2: YILLIK KIYASLAMA (YoY 2026 vs 2027) ÇIKTISI --}}
                {{-- ═══════════════════════════════════════════════════════════════ --}}
                <div x-show="analysisMode === 'yoy'" class="space-y-6">
                    <!-- 4'lü KPI Kartları -->
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                        <div class="bg-white rounded-2xl p-4 border border-blue-200 shadow-2xs">
                            <span class="text-[11px] font-bold text-blue-600 uppercase block"
                                x-text="reportData.year_1 + ' Toplamı'"></span>
                            <span class="text-xl sm:text-2xl font-black text-slate-900 mt-1 block"
                                x-text="formatDecimal(reportData.summary ? reportData.summary.total_y1 : 0)"></span>
                        </div>

                        <div class="bg-white rounded-2xl p-4 border border-emerald-200 shadow-2xs">
                            <span class="text-[11px] font-bold text-emerald-600 uppercase block"
                                x-text="reportData.year_2 + ' Toplamı'"></span>
                            <span class="text-xl sm:text-2xl font-black text-slate-900 mt-1 block"
                                x-text="formatDecimal(reportData.summary ? reportData.summary.total_y2 : 0)"></span>
                        </div>

                        <div class="bg-white rounded-2xl p-4 border border-slate-200 shadow-2xs">
                            <span class="text-[11px] font-bold text-slate-500 uppercase block">Net Fark (Delta)</span>
                            <span class="text-xl sm:text-2xl font-black mt-1 block"
                                :class="(reportData.summary && reportData.summary.total_delta >= 0) ? 'text-emerald-600' : 'text-rose-600'"
                                x-text="((reportData.summary && reportData.summary.total_delta > 0) ? '+' : '') + formatDecimal(reportData.summary ? reportData.summary.total_delta : 0)">
                            </span>
                        </div>

                        <div class="bg-slate-900 text-white rounded-2xl p-4 shadow-sm">
                            <span class="text-[11px] font-bold text-slate-400 uppercase block">Net Yıllık Büyüme</span>
                            <span class="text-xl sm:text-2xl font-black mt-1 block"
                                :class="(reportData.summary && reportData.summary.total_growth_rate >= 0) ? 'text-emerald-400' : 'text-rose-400'"
                                x-text="((reportData.summary && reportData.summary.total_growth_rate > 0) ? '+' : '') + (reportData.summary ? reportData.summary.total_growth_rate : 0) + '%'">
                            </span>
                        </div>
                    </div>

                    <!-- Çift Sütunlu Karşılaştırmalı Bar Grafik -->
                    <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-xs">
                        <div class="flex items-center justify-between border-b border-slate-100 pb-3 mb-4">
                            <h4 class="text-sm font-black text-slate-900 flex items-center gap-2">
                                <span>📅</span>
                                <span
                                    x-text="reportData.year_1 + ' vs ' + reportData.year_2 + ' Dönemsel Karşılaştırma Grafiği'"></span>
                            </h4>
                            <span class="text-xs text-slate-400 font-semibold"
                                x-text="reportData.full_metric_title"></span>
                        </div>
                        <div class="w-full h-80 sm:h-96 relative">
                            <canvas id="yoyChartCanvas"></canvas>
                        </div>
                    </div>

                    <!-- YoY Delta & Büyüme Tablosu -->
                    <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden shadow-xs">
                        <div class="p-4 bg-slate-50 border-b border-slate-200 flex items-center justify-between">
                            <h4 class="text-sm font-black text-slate-900 flex items-center gap-2">
                                <span>⚖️</span>
                                <span x-text="reportData.entity_dim_name + ' Bazında Değişim ve Büyüme Matrisi'"></span>
                            </h4>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="w-full text-left border-collapse text-xs">
                                <thead class="bg-slate-900 text-white">
                                    <tr>
                                        <th class="py-3 px-4 font-extrabold uppercase"
                                            x-text="reportData.entity_dim_name || 'Varlık'"></th>
                                        <th class="py-3 px-4 font-extrabold text-right uppercase"
                                            x-text="reportData.year_1 + ' Değeri'"></th>
                                        <th class="py-3 px-4 font-extrabold text-right uppercase"
                                            x-text="reportData.year_2 + ' Değeri'"></th>
                                        <th class="py-3 px-4 font-extrabold text-right uppercase">Fark (Değişim)</th>
                                        <th class="py-3 px-4 font-extrabold text-right uppercase">% Büyüme / Değişim
                                        </th>
                                        <th class="py-3 px-4 font-extrabold text-right uppercase"
                                            x-text="reportData.year_1 + ' Payı (%)'"></th>
                                        <th class="py-3 px-4 font-extrabold text-right uppercase"
                                            x-text="reportData.year_2 + ' Payı (%)'"></th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                    <template x-for="item in (reportData.items || [])" :key="item.name">
                                        <tr class="hover:bg-slate-50 transition-colors">
                                            <td class="py-3 px-4 font-bold text-slate-900" x-text="item.name"></td>
                                            <td class="py-3 px-4 text-right font-mono text-slate-700"
                                                x-text="formatDecimal(item.val_1)"></td>
                                            <td class="py-3 px-4 text-right font-mono text-slate-700 font-bold"
                                                x-text="formatDecimal(item.val_2)"></td>
                                            <td class="py-3 px-4 text-right font-mono font-bold"
                                                :class="item.delta >= 0 ? 'text-emerald-600' : 'text-rose-600'"
                                                x-text="(item.delta > 0 ? '+' : '') + formatDecimal(item.delta)">
                                            </td>
                                            <td class="py-3 px-4 text-right font-mono">
                                                <span
                                                    class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-black"
                                                    :class="item.growth_rate > 0 ? 'bg-emerald-50 text-emerald-700' : (item.growth_rate < 0 ? 'bg-rose-50 text-rose-700' : 'bg-slate-100 text-slate-600')"
                                                    x-text="(item.growth_rate > 0 ? '+' : '') + item.growth_rate + '%'">
                                                </span>
                                            </td>
                                            <td class="py-3 px-4 text-right font-mono text-slate-500"
                                                x-text="'%' + item.share_1"></td>
                                            <td class="py-3 px-4 text-right font-mono text-indigo-700 font-bold"
                                                x-text="'%' + item.share_2"></td>
                                        </tr>
                                    </template>
                                </tbody>
                                <tfoot class="bg-slate-100 font-black text-slate-900 border-t-2 border-slate-300">
                                    <tr>
                                        <td class="py-3 px-4">GENEL TOPLAM</td>
                                        <td class="py-3 px-4 text-right font-mono"
                                            x-text="formatDecimal(reportData.summary ? reportData.summary.total_y1 : 0)">
                                        </td>
                                        <td class="py-3 px-4 text-right font-mono"
                                            x-text="formatDecimal(reportData.summary ? reportData.summary.total_y2 : 0)">
                                        </td>
                                        <td class="py-3 px-4 text-right font-mono"
                                            :class="(reportData.summary && reportData.summary.total_delta >= 0) ? 'text-emerald-600' : 'text-rose-600'"
                                            x-text="((reportData.summary && reportData.summary.total_delta > 0) ? '+' : '') + formatDecimal(reportData.summary ? reportData.summary.total_delta : 0)">
                                        </td>
                                        <td class="py-3 px-4 text-right font-mono text-indigo-700"
                                            x-text="((reportData.summary && reportData.summary.total_growth_rate > 0) ? '+' : '') + (reportData.summary ? reportData.summary.total_growth_rate : 0) + '%'">
                                        </td>
                                        <td class="py-3 px-4 text-right font-mono">%100,0</td>
                                        <td class="py-3 px-4 text-right font-mono">%100,0</td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>

                {{-- ═══════════════════════════════════════════════════════════════ --}}
                {{-- C) MOD 3: A vs B SEGMENT BENCHMARK ÇIKTISI --}}
                {{-- ═══════════════════════════════════════════════════════════════ --}}
                <div x-show="analysisMode === 'segment'" class="space-y-6">
                    <!-- Baş Başa (Head-to-Head) Karşılaştırma Kartları -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div class="bg-indigo-900 text-white rounded-2xl p-5 shadow-sm space-y-2">
                            <span class="text-xs font-bold text-indigo-300 uppercase block"
                                x-text="'🔵 ' + (reportData.segment_a ? reportData.segment_a.title : 'Segment A')"></span>
                            <span class="text-2xl font-black block"
                                x-text="formatDecimal(reportData.segment_a ? reportData.segment_a.total : 0)"></span>
                            <div class="text-xs text-indigo-200 flex justify-between pt-2 border-t border-indigo-800">
                                <span>Kayıt Sayısı: <strong class="text-white"
                                        x-text="reportData.segment_a ? reportData.segment_a.count : 0"></strong></span>
                                <span>Ortalama: <strong class="text-white"
                                        x-text="formatDecimal(reportData.segment_a ? reportData.segment_a.avg : 0)"></strong></span>
                            </div>
                        </div>

                        <div class="bg-amber-800 text-white rounded-2xl p-5 shadow-sm space-y-2">
                            <span class="text-xs font-bold text-amber-300 uppercase block"
                                x-text="'🟡 ' + (reportData.segment_b ? reportData.segment_b.title : 'Segment B')"></span>
                            <span class="text-2xl font-black block"
                                x-text="formatDecimal(reportData.segment_b ? reportData.segment_b.total : 0)"></span>
                            <div class="text-xs text-amber-200 flex justify-between pt-2 border-t border-amber-700">
                                <span>Kayıt Sayısı: <strong class="text-white"
                                        x-text="reportData.segment_b ? reportData.segment_b.count : 0"></strong></span>
                                <span>Ortalama: <strong class="text-white"
                                        x-text="formatDecimal(reportData.segment_b ? reportData.segment_b.avg : 0)"></strong></span>
                            </div>
                        </div>

                        <div class="bg-white rounded-2xl p-5 border border-slate-200 shadow-2xs space-y-2">
                            <span class="text-xs font-bold text-slate-500 uppercase block">Kıyaslama Dengesi & Lider
                                Küme</span>
                            <div class="flex items-center gap-2 mt-1">
                                <span class="text-xl font-black text-slate-900"
                                    x-text="(reportData.kpis ? reportData.kpis.dominant_segment : '') + ' Önde'"></span>
                            </div>
                            <div class="text-xs text-slate-600 pt-2 border-t border-slate-100 flex justify-between">
                                <span>Hacim Farkı: <strong class="text-indigo-600 font-bold"
                                        x-text="formatDecimal(reportData.kpis ? reportData.kpis.metric_diff : 0)"></strong></span>
                                <span>Oran: <strong class="text-slate-900 font-bold"
                                        x-text="(reportData.kpis && reportData.kpis.metric_ratio ? reportData.kpis.metric_ratio + 'x' : '-')"></strong></span>
                            </div>
                        </div>
                    </div>

                    <!-- Segment Karşılaştırmalı Grafik -->
                    <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-xs">
                        <div class="flex items-center justify-between border-b border-slate-100 pb-3 mb-4">
                            <h4 class="text-sm font-black text-slate-900 flex items-center gap-2">
                                <span>⚖️</span>
                                <span>Personellerin Kümelere Göre Performans Dağılımı</span>
                            </h4>
                            <span class="text-xs text-slate-400 font-semibold"
                                x-text="reportData.full_metric_title"></span>
                        </div>
                        <div class="w-full h-80 sm:h-96 relative">
                            <canvas id="segmentChartCanvas"></canvas>
                        </div>
                    </div>

                    <!-- Personel Dağılım Matrisi -->
                    <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden shadow-xs">
                        <div class="p-4 bg-slate-50 border-b border-slate-200">
                            <h4 class="text-sm font-black text-slate-900">Personel Bazında Segment Satış ve Katkı
                                Payları</h4>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="w-full text-left border-collapse text-xs">
                                <thead class="bg-slate-900 text-white">
                                    <tr>
                                        <th class="py-3 px-4 font-extrabold uppercase">Personel Adı</th>
                                        <th class="py-3 px-4 font-extrabold text-right uppercase"
                                            x-text="(reportData.segment_a ? reportData.segment_a.title : 'Segment A') + ' Satışı'">
                                        </th>
                                        <th class="py-3 px-4 font-extrabold text-right uppercase"
                                            x-text="(reportData.segment_b ? reportData.segment_b.title : 'Segment B') + ' Satışı'">
                                        </th>
                                        <th class="py-3 px-4 font-extrabold text-right uppercase">Segment A Payı (%)
                                        </th>
                                        <th class="py-3 px-4 font-extrabold text-right uppercase">Segment B Payı (%)
                                        </th>
                                        <th class="py-3 px-4 font-extrabold text-center uppercase">Ağırlık Tercihi</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                    <template x-for="pm in (reportData.personnel_matrix || [])" :key="pm.user_name">
                                        <tr class="hover:bg-slate-50 transition-colors">
                                            <td class="py-3 px-4 font-bold text-slate-900" x-text="pm.user_name"></td>
                                            <td class="py-3 px-4 text-right font-mono text-indigo-700 font-bold"
                                                x-text="formatDecimal(pm.val_a)"></td>
                                            <td class="py-3 px-4 text-right font-mono text-amber-700 font-bold"
                                                x-text="formatDecimal(pm.val_b)"></td>
                                            <td class="py-3 px-4 text-right font-mono text-slate-600"
                                                x-text="'%' + pm.share_a"></td>
                                            <td class="py-3 px-4 text-right font-mono text-slate-600"
                                                x-text="'%' + pm.share_b"></td>
                                            <td class="py-3 px-4 text-center">
                                                <span
                                                    class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-extrabold"
                                                    :class="pm.preference.includes('A Ağırlıklı') ? 'bg-indigo-100 text-indigo-800' : (pm.preference.includes('B Ağırlıklı') ? 'bg-amber-100 text-amber-800' : 'bg-slate-100 text-slate-700')"
                                                    x-text="pm.preference">
                                                </span>
                                            </td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                {{-- ═══════════════════════════════════════════════════════════════ --}}
                {{-- D) MOD 4: COĞRAFİ DÜNYA HARİTASI ÇIKTISI --}}
                {{-- ═══════════════════════════════════════════════════════════════ --}}
                <div x-show="analysisMode === 'geo'" class="space-y-6">
                    <!-- Özet Kartlar -->
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                        <div class="bg-white rounded-2xl p-4 border border-slate-200 shadow-2xs">
                            <span class="text-[11px] font-bold text-slate-400 uppercase block">İşlem Yapılan Ülke</span>
                            <span class="text-xl sm:text-2xl font-black text-slate-900 mt-1 block"
                                x-text="reportData.summary ? reportData.summary.total_countries : 0"></span>
                        </div>
                        <div class="bg-white rounded-2xl p-4 border border-slate-200 shadow-2xs">
                            <span class="text-[11px] font-bold text-slate-400 uppercase block">Toplam Sipariş /
                                Kayıt</span>
                            <span class="text-xl sm:text-2xl font-black text-slate-900 mt-1 block"
                                x-text="reportData.summary ? reportData.summary.total_rows : 0"></span>
                        </div>
                        <div class="bg-white rounded-2xl p-4 border border-slate-200 shadow-2xs">
                            <span class="text-[11px] font-bold text-slate-400 uppercase block">Lider Pazar (1.
                                Ülke)</span>
                            <span class="text-base sm:text-lg font-black text-indigo-600 mt-1 block truncate"
                                x-text="reportData.summary && reportData.summary.top_country ? reportData.summary.top_country.name : '-'"></span>
                        </div>
                        <div class="bg-indigo-900 text-white rounded-2xl p-4 shadow-sm">
                            <span class="text-[11px] font-bold text-indigo-300 uppercase block">Global Toplam
                                Hacim</span>
                            <span class="text-xl sm:text-2xl font-black text-white mt-1 block"
                                x-text="formatDecimal(reportData.summary ? reportData.summary.global_total : 0)"></span>
                        </div>
                    </div>

                    <!-- İnteraktif Dünya Haritası Tuvali (jsVectorMap) -->
                    <div id="geoMapCardWrapper"
                        class="bg-slate-900 rounded-3xl p-6 shadow-xl border border-slate-800 text-white space-y-4"
                        :class="isMapFullscreen 
                            ? '!fixed !inset-0 !z-[9999] !bg-slate-950 !p-6 !flex !flex-col !w-screen !h-screen !rounded-none !shadow-2xl !space-y-4 overflow-hidden' 
                            : ''" @keydown.escape.window="if(isMapFullscreen) { toggleMapFullscreen(); }">

                        <div
                            class="flex flex-col sm:flex-row sm:items-center sm:justify-between border-b border-slate-800 pb-3 gap-3 shrink-0">
                            <div class="flex items-center gap-2.5">
                                <span class="text-2xl">🌍</span>
                                <div>
                                    <div class="flex items-center gap-2">
                                        <h4 class="text-sm sm:text-base font-black text-white">Etkileşimli Küresel Satış
                                            Isı Haritası</h4>
                                        <span x-show="isMapFullscreen"
                                            class="bg-amber-500/20 text-amber-300 border border-amber-500/30 text-[10px] font-bold px-2 py-0.5 rounded-full">Tam
                                            Ekran Modu</span>
                                    </div>
                                    <p class="text-[11px] text-slate-400">Ülkelerin üzerine gelerek detaylı personelleri
                                        ve pazar paylarını inceleyin • Fare tekerleğiyle yakınlaştırın/uzaklaştırın</p>
                                </div>
                            </div>

                            <div class="flex flex-wrap items-center gap-2 self-end sm:self-auto">
                                <!-- Katmanlı Renk Skalası Lejantı -->
                                <div
                                    class="hidden md:flex items-center gap-1.5 bg-slate-800/90 px-3 py-1.5 rounded-xl border border-slate-700/80 text-[10px] text-slate-300">
                                    <span class="font-bold text-slate-400 mr-0.5">Yoğunluk:</span>
                                    <span class="flex items-center gap-1" title="Zirve / En Çok Hacim">
                                        <span
                                            class="w-2.5 h-2.5 rounded-full bg-[#312e81] border border-indigo-400/50"></span>
                                        <span class="font-bold text-indigo-300">En Çok</span>
                                    </span>
                                    <span class="text-slate-500 font-bold">›</span>
                                    <span class="w-2.5 h-2.5 rounded-full bg-[#4338ca] border border-indigo-300/40"
                                        title="Yüksek Hacim"></span>
                                    <span class="text-slate-500 font-bold">›</span>
                                    <span class="w-2.5 h-2.5 rounded-full bg-[#6366f1] border border-indigo-200/40"
                                        title="Orta Hacim"></span>
                                    <span class="text-slate-500 font-bold">›</span>
                                    <span class="w-2.5 h-2.5 rounded-full bg-[#818cf8]" title="Düşük Hacim"></span>
                                    <span class="text-slate-500 font-bold">›</span>
                                    <span class="flex items-center gap-1" title="En Az Hacim">
                                        <span class="w-2.5 h-2.5 rounded-full bg-[#a5b4fc]"></span>
                                        <span class="font-bold text-indigo-200">En Az</span>
                                    </span>
                                    <span class="border-l border-slate-700 pl-2 ml-1 flex items-center gap-1"
                                        title="Kayıtlı verisi bulunmayan ülkeler">
                                        <span
                                            class="w-2.5 h-2.5 rounded-full bg-white border border-slate-400 shadow-2xs"></span>
                                        <span class="text-slate-400 font-medium">Veri Yok (Beyaz)</span>
                                    </span>
                                </div>

                                <span
                                    class="text-[10px] bg-indigo-950/80 text-indigo-300 px-2.5 py-1.5 rounded-xl border border-indigo-700/50 hidden xl:inline-flex items-center gap-1">
                                    <svg class="w-3 h-3 text-indigo-400" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M15 15l-2 5L9 9l11 4-5 2zm0 0l5 5M7.188 2.239l.777 2.897M5.136 7.965l-2.898-.777M13.95 4.05l-2.122 2.122m-5.657 5.656l-2.12 2.122" />
                                    </svg>
                                    Mouse ile Zoom
                                </span>

                                <!-- Haritayı Ortala / Sıfırla Butonu -->
                                <button type="button" @click="renderGeoMap()"
                                    class="inline-flex items-center gap-1.5 text-xs font-bold px-2.5 py-1.5 rounded-xl border border-slate-700 bg-slate-800 hover:bg-slate-700 text-slate-200 transition cursor-pointer"
                                    title="Haritayı Varsayılan Boyuta Döndür ve Ortala">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                    </svg>
                                    <span class="hidden sm:inline">Ortala</span>
                                </button>

                                <!-- Tam Ekran Butonu -->
                                <button type="button" @click="toggleMapFullscreen()"
                                    class="inline-flex items-center gap-1.5 text-xs font-bold px-3 py-1.5 rounded-xl border transition cursor-pointer shadow-xs"
                                    :class="isMapFullscreen 
                                        ? 'bg-amber-500 hover:bg-amber-400 text-slate-950 border-amber-400 font-black' 
                                        : 'bg-slate-800 hover:bg-slate-700 text-white border-slate-700'">
                                    <template x-if="!isMapFullscreen">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M4 8V4m0 0h4M4 4l5 5m11-5h-4m4 0v4m0-4l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4" />
                                        </svg>
                                    </template>
                                    <template x-if="isMapFullscreen">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M6 18L18 6M6 6l12 12" />
                                        </svg>
                                    </template>
                                    <span x-text="isMapFullscreen ? 'Tam Ekrandan Çık (ESC)' : 'Tam Ekran'">Tam
                                        Ekran</span>
                                </button>
                            </div>
                        </div>

                        <!-- Harita Container: Normalde 600px yükseklik, Tam ekranda flex-1 / calc(100vh - 120px) -->
                        <div id="reportWorldMap"
                            :style="isMapFullscreen ? 'height: calc(100vh - 120px) !important; width: 100% !important;' : 'height: 600px !important; width: 100% !important;'"
                            class="w-full rounded-2xl overflow-hidden relative flex-1 min-h-[400px]"></div>
                    </div>

                    <!-- Ülke Sıralama ve Pazar Payı Tablosu -->
                    <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden shadow-xs">
                        <div
                            class="p-4 bg-slate-50 border-b border-slate-200 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                            <div>
                                <h4 class="text-sm font-black text-slate-900">Ülke Bazında Pazar Dağılımı ve Personel
                                    Kırılımı</h4>
                                <p class="text-[11px] text-slate-500 mt-0.5">
                                    Toplam <strong class="text-slate-800"
                                        x-text="(reportData.rankings || []).length"></strong> ülke •
                                    <span x-show="geoSearch.trim()" class="text-indigo-600 font-bold">Filtrelenen:
                                        <strong x-text="filteredGeoRankings.length"></strong> ülke • </span>
                                    <span>Sayfa başına 10 satır listelenir</span>
                                </p>
                            </div>

                            <!-- Anlık Arama ve Filtreleme Kutusu -->
                            <div class="flex items-center gap-2">
                                <div class="relative w-full sm:w-64">
                                    <span
                                        class="absolute inset-y-0 left-0 pl-2.5 flex items-center pointer-events-none text-slate-400">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                        </svg>
                                    </span>
                                    <input type="text" x-model="geoSearch" @input="geoPage = 1"
                                        placeholder="Ülke, ISO kodu veya personel ara..."
                                        class="w-full pl-8 pr-7 py-1.5 text-xs bg-white border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 placeholder-slate-400 transition">
                                    <button type="button" x-show="geoSearch" @click="geoSearch = ''; geoPage = 1"
                                        class="absolute inset-y-0 right-0 pr-2 flex items-center text-slate-400 hover:text-slate-600 cursor-pointer">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M6 18L18 6M6 6l12 12" />
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="overflow-x-auto">
                            <table class="w-full text-left border-collapse text-xs">
                                <thead class="bg-slate-900 text-white">
                                    <tr>
                                        <th class="py-3 px-3 font-extrabold uppercase w-12 text-center">Sıra</th>
                                        <th class="py-3 px-4 font-extrabold uppercase">Ülke Adı</th>
                                        <th class="py-3 px-3 font-extrabold uppercase text-center w-20">ISO Kodu</th>
                                        <th class="py-3 px-4 font-extrabold text-right uppercase">Toplam Hacim</th>
                                        <th class="py-3 px-4 font-extrabold text-right uppercase">Dünya Pazar Payı (%)
                                        </th>
                                        <th class="py-3 px-3 font-extrabold text-right uppercase">Sipariş Sayısı</th>
                                        <th class="py-3 px-4 font-extrabold uppercase">İşlem Yapan Personeller</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                    <template x-for="r in paginatedGeoRankings" :key="r.code">
                                        <tr class="hover:bg-slate-50 transition-colors">
                                            <td class="py-3 px-3 font-black text-slate-400 text-center"
                                                x-text="'#' + r.rank"></td>
                                            <td class="py-3 px-4 font-extrabold text-slate-900 text-sm">
                                                <div class="flex items-center gap-2">
                                                    <span
                                                        class="w-3 h-3 rounded-full inline-block shrink-0 shadow-2xs border border-slate-300"
                                                        :style="'background-color: ' + (r.color || '#6366f1')"
                                                        :title="'Harita Katmanı: ' + (r.color_level ? r.color_level + '. Seviye' : 'Aktif')"></span>
                                                    <span x-text="r.name"></span>
                                                </div>
                                            </td>
                                            <td class="py-3 px-3 text-center">
                                                <span
                                                    class="inline-block bg-slate-100 text-slate-800 font-mono font-bold text-[11px] px-2 py-0.5 rounded"
                                                    x-text="r.code"></span>
                                            </td>
                                            <td class="py-3 px-4 text-right font-mono font-black text-emerald-700"
                                                x-text="formatDecimal(r.total)"></td>
                                            <td class="py-3 px-4 text-right font-mono font-extrabold text-indigo-700">
                                                <div class="flex items-center justify-end gap-2">
                                                    <span x-text="'%' + r.share"></span>
                                                    <div class="w-12 bg-slate-200 rounded-full h-1.5 overflow-hidden">
                                                        <div class="bg-indigo-600 h-1.5 rounded-full"
                                                            :style="'width: ' + Math.min(r.share, 100) + '%'"></div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="py-3 px-3 text-right font-mono text-slate-700" x-text="r.count">
                                            </td>
                                            <td class="py-3 px-4" x-data="{ expandedUsers: false }">
                                                <div class="flex flex-wrap items-center gap-1">
                                                    <!-- İlk 3 Personel veya Açıkken Tümü -->
                                                    <template
                                                        x-for="u in ((r.top_users || []).slice(0, expandedUsers ? undefined : 3))"
                                                        :key="u">
                                                        <span
                                                            class="bg-indigo-50 text-indigo-700 text-[10px] font-bold px-2 py-0.5 rounded border border-indigo-100"
                                                            x-text="u"></span>
                                                    </template>

                                                    <!-- 3'ten Fazla Varsa "+X daha" Butonu -->
                                                    <template x-if="(r.top_users || []).length > 3 && !expandedUsers">
                                                        <button type="button" @click="expandedUsers = true"
                                                            class="bg-amber-50 hover:bg-amber-100 text-amber-800 text-[10px] font-black px-2 py-0.5 rounded border border-amber-200 transition cursor-pointer"
                                                            :title="'Tüm personelleri gör (' + r.top_users.length + ' kişi)'"
                                                            x-text="'+' + (r.top_users.length - 3) + ' daha'">
                                                        </button>
                                                    </template>

                                                    <!-- Açıkken "Daralt" Butonu -->
                                                    <template x-if="(r.top_users || []).length > 3 && expandedUsers">
                                                        <button type="button" @click="expandedUsers = false"
                                                            class="bg-slate-100 hover:bg-slate-200 text-slate-600 text-[10px] font-bold px-1.5 py-0.5 rounded border border-slate-200 transition cursor-pointer">
                                                            ▴ Kapat
                                                        </button>
                                                    </template>

                                                    <!-- Hiç Personel Yoksa -->
                                                    <template x-if="!(r.top_users && r.top_users.length)">
                                                        <span class="text-slate-400 text-[10px] italic">-</span>
                                                    </template>
                                                </div>
                                            </td>
                                        </tr>
                                    </template>

                                    <!-- Arama Sonucu Bulunamadı -->
                                    <tr x-show="filteredGeoRankings.length === 0">
                                        <td colspan="7" class="py-8 text-center text-slate-400 text-xs">
                                            <div class="flex flex-col items-center justify-center gap-1">
                                                <svg class="w-8 h-8 text-slate-300" fill="none" stroke="currentColor"
                                                    viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2"
                                                        d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                </svg>
                                                <span>Arama kriterine uygun ülke veya personel bulunamadı.</span>
                                                <button type="button" @click="geoSearch = ''; geoPage = 1"
                                                    class="text-indigo-600 font-bold hover:underline mt-1 text-[11px] cursor-pointer">Aramayı
                                                    Temizle</button>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <!-- Sayfalama (Sayfa Yenilemeden İleri / Geri / Sayfa Seçimi) -->
                        <div x-show="filteredGeoRankings.length > 0"
                            class="p-3 bg-slate-50 border-t border-slate-200 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 text-xs">
                            <div class="text-slate-500 text-[11px] text-center sm:text-left">
                                <span>Gösterilen: </span>
                                <strong class="text-slate-800" x-text="((geoPage - 1) * geoPerPage) + 1"></strong> -
                                <strong class="text-slate-800"
                                    x-text="Math.min(geoPage * geoPerPage, filteredGeoRankings.length)"></strong> /
                                <span class="text-slate-700 font-bold"
                                    x-text="filteredGeoRankings.length + ' Ülke'"></span>
                            </div>

                            <!-- Sayfa Düğmeleri -->
                            <div class="flex items-center gap-1 justify-center sm:justify-end">
                                <!-- İlk Sayfa -->
                                <button type="button" @click="setGeoPage(1)" :disabled="geoPage === 1"
                                    class="px-2 py-1 rounded-lg border border-slate-200 bg-white text-slate-600 hover:bg-slate-100 disabled:opacity-30 disabled:pointer-events-none transition cursor-pointer"
                                    title="İlk Sayfa">
                                    «
                                </button>
                                <!-- Önceki Sayfa -->
                                <button type="button" @click="setGeoPage(geoPage - 1)" :disabled="geoPage === 1"
                                    class="px-2.5 py-1 rounded-lg border border-slate-200 bg-white text-slate-600 hover:bg-slate-100 disabled:opacity-30 disabled:pointer-events-none transition cursor-pointer"
                                    title="Önceki Sayfa">
                                    ‹ Önceki
                                </button>

                                <!-- Numaralandırılmış Sayfa Butonları -->
                                <div class="flex items-center gap-1 mx-1">
                                    <template x-for="p in geoPaginationPages" :key="p">
                                        <button type="button" @click="setGeoPage(p)"
                                            :class="geoPage === p ? 'bg-indigo-600 text-white font-black shadow-xs' : 'bg-white text-slate-700 hover:bg-slate-100 border border-slate-200 font-semibold'"
                                            class="w-7 h-7 flex items-center justify-center rounded-lg text-xs transition cursor-pointer"
                                            x-text="p">
                                        </button>
                                    </template>
                                </div>

                                <!-- Sonraki Sayfa -->
                                <button type="button" @click="setGeoPage(geoPage + 1)"
                                    :disabled="geoPage >= geoTotalPages"
                                    class="px-2.5 py-1 rounded-lg border border-slate-200 bg-white text-slate-600 hover:bg-slate-100 disabled:opacity-30 disabled:pointer-events-none transition cursor-pointer"
                                    title="Sonraki Sayfa">
                                    Sonraki ›
                                </button>
                                <!-- Son Sayfa -->
                                <button type="button" @click="setGeoPage(geoTotalPages)"
                                    :disabled="geoPage >= geoTotalPages"
                                    class="px-2 py-1 rounded-lg border border-slate-200 bg-white text-slate-600 hover:bg-slate-100 disabled:opacity-30 disabled:pointer-events-none transition cursor-pointer"
                                    title="Son Sayfa">
                                    »
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

            </div>

            <!-- 6. VERİ BULUNAMADI -->
            <div x-show="!loading && !hasReportData"
                class="bg-white rounded-2xl border border-slate-200 p-12 text-center space-y-3 shadow-xs">
                <div
                    class="w-12 h-12 rounded-2xl bg-slate-100 text-slate-400 flex items-center justify-center mx-auto text-xl">
                    🔍
                </div>
                <h4 class="text-base font-extrabold text-slate-800">Analiz Edilecek Kayıt Bulunamadı</h4>
                <p class="text-xs text-slate-500 max-w-md mx-auto">
                    Seçtiğiniz şablon veya filtre kriterlerine uygun form kaydı bulunamadı. Lütfen filtrelerinizi
                    genişleterek tekrar deneyiniz.
                </p>
            </div>

        </div>

        {{-- ─── MODALLAR ─── --}}

        <!-- 1. Varyant Kaydetme Modalı -->
        <div x-show="saveVariantModal"
            class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/50 backdrop-blur-xs flex items-center justify-center p-4"
            x-cloak>
            <div @click.away="saveVariantModal = false"
                class="bg-white rounded-2xl max-w-md w-full p-6 space-y-4 shadow-xl">
                <h3 class="text-base font-black text-slate-900">Rapor Varyantını Kaydet</h3>
                <div class="space-y-3 text-xs">
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Rapor Başlığı *</label>
                        <input type="text" x-model="ruleForm.title" class="w-full rounded-xl border-slate-200 text-xs">
                    </div>
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Açıklama (Opsiyonel)</label>
                        <textarea x-model="ruleForm.description" rows="2"
                            class="w-full rounded-xl border-slate-200 text-xs"></textarea>
                    </div>
                    <div class="flex items-center gap-2 pt-1">
                        <input type="checkbox" id="is_public" x-model="ruleForm.is_public"
                            class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                        <label for="is_public" class="font-bold text-slate-700">Tüm yetkili kullanıcılarla paylaş (Genel
                            Varyant)</label>
                    </div>
                </div>
                <div class="flex justify-end gap-2 pt-3 border-t border-slate-100">
                    <button type="button" @click="saveVariantModal = false"
                        class="px-4 py-2 rounded-xl border border-slate-200 text-xs font-bold text-slate-700">İptal</button>
                    <button type="button" @click="submitSaveRule()"
                        class="px-5 py-2 rounded-xl bg-indigo-600 text-white text-xs font-black">Kaydet</button>
                </div>
            </div>
        </div>

        <!-- 2. E-Posta Paylaşım Modalı -->
        <div x-show="shareModal"
            class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/50 backdrop-blur-xs flex items-center justify-center p-4"
            x-cloak>
            <div @click.away="shareModal = false" class="bg-white rounded-2xl max-w-md w-full p-6 space-y-4 shadow-xl">
                <h3 class="text-base font-black text-slate-900 flex items-center gap-2">
                    <span>✉️</span>
                    <span>Raporu E-Posta ile Paylaş</span>
                </h3>
                <p class="text-xs text-slate-500">Mevcut analiz tablosu ve Excel eki belirlenen alıcılara kurumsal
                    e-posta formatında anında iletilir.</p>
                <div class="space-y-3 text-xs">
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Rapor Başlığı *</label>
                        <input type="text" x-model="shareForm.title" class="w-full rounded-xl border-slate-200 text-xs">
                    </div>
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Alıcı E-Posta Adresleri * (Virgül veya boşluk
                            ile ayırın)</label>
                        <textarea x-model="shareForm.emails" rows="3" placeholder="ahmet@koksan.com, mehmet@koksan.com"
                            class="w-full rounded-xl border-slate-200 text-xs"></textarea>
                    </div>
                </div>
                <div class="flex justify-end gap-2 pt-3 border-t border-slate-100">
                    <button type="button" @click="shareModal = false"
                        class="px-4 py-2 rounded-xl border border-slate-200 text-xs font-bold text-slate-700">Kapat</button>
                    <button type="button" @click="submitShare()" :disabled="sendingMail"
                        class="px-5 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-black flex items-center gap-1.5">
                        <span x-text="sendingMail ? 'Gönderiliyor...' : 'Şimdi Gönder'"></span>
                    </button>
                </div>
            </div>
        </div>

        <!-- 3. Zamanlanmış Görevler Listesi Modalı -->
        <div x-show="schedulesListModal"
            class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/50 backdrop-blur-xs flex items-center justify-center p-4"
            x-cloak>
            <div @click.away="schedulesListModal = false"
                class="bg-white rounded-2xl max-w-3xl w-full p-6 space-y-4 shadow-xl max-h-[85vh] overflow-y-auto">
                <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                    <h3 class="text-base font-black text-slate-900 flex items-center gap-2">
                        <span>⏰</span>
                        <span>Zamanlanmış Otomatik Rapor Gönderimleri</span>
                    </h3>
                    <button type="button" @click="openCreateScheduleModal()"
                        class="px-3 py-1.5 rounded-xl bg-indigo-600 text-white font-bold text-xs">+ Yeni
                        Zamanlama</button>
                </div>

                @if($schedules->isEmpty())
                    <div class="text-center py-8 text-slate-400 text-xs">Kayıtlı otomatik rapor zamanlaması bulunamadı.
                    </div>
                @else
                    <div class="divide-y divide-slate-100 text-xs">
                        @foreach($schedules as $sc)
                            <div class="py-3 flex items-center justify-between gap-3">
                                <div>
                                    <div class="font-extrabold text-slate-900 flex items-center gap-2">
                                        <span>{{ $sc->title }}</span>
                                        <span
                                            class="px-2 py-0.5 rounded-full text-[10px] font-bold {{ $sc->is_active ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-100 text-slate-600' }}">
                                            {{ $sc->is_active ? 'Aktif' : 'Pasif' }}
                                        </span>
                                    </div>
                                    <div class="text-slate-500 text-[11px] mt-0.5">
                                        Periyot:
                                        <strong>{{ match ($sc->frequency) { 'daily' => 'Günlük', 'weekly' => 'Haftalık', 'monthly' => 'Aylık', default => $sc->frequency} }}
                                            ({{ $sc->time_of_day }})</strong>
                                        &bull; Sonraki Çalışma:
                                        {{ $sc->next_run_at ? $sc->next_run_at->format('d.m.Y H:i') : '-' }}
                                    </div>
                                </div>
                                <div class="flex items-center gap-2">
                                    <button type="button" @click="toggleSchedule({{ $sc->id }})"
                                        class="px-2.5 py-1 rounded-lg border border-slate-200 text-slate-700 font-bold hover:bg-slate-50 text-[11px]">
                                        {{ $sc->is_active ? 'Durdur' : 'Aktifleştir' }}
                                    </button>
                                    <button type="button" @click="deleteSchedule({{ $sc->id }})"
                                        class="p-1.5 rounded-lg text-rose-600 hover:bg-rose-50">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif

                <div class="flex justify-end pt-3 border-t border-slate-100">
                    <button type="button" @click="schedulesListModal = false"
                        class="px-4 py-2 rounded-xl border border-slate-200 text-xs font-bold text-slate-700">Kapat</button>
                </div>
            </div>
        </div>

        <!-- 4. Yeni Zamanlama Oluştur Modalı -->
        <div x-show="createScheduleModal"
            class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/50 backdrop-blur-xs flex items-center justify-center p-4"
            x-cloak>
            <div @click.away="createScheduleModal = false"
                class="bg-white rounded-2xl max-w-lg w-full p-6 space-y-4 shadow-xl">
                <h3 class="text-base font-black text-slate-900">Yeni Otomatik Zamanlama Kur</h3>
                <div class="space-y-3 text-xs">
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Görev Başlığı *</label>
                        <input type="text" x-model="newSchedule.title" placeholder="Örn: Haftalık Tonaj Özeti"
                            class="w-full rounded-xl border-slate-200 text-xs">
                    </div>
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Gönderilecek Rapor Kuralı *</label>
                        <select x-model="newSchedule.report_rule_id"
                            class="w-full rounded-xl border-slate-200 text-xs font-semibold">
                            <option value="">-- Kayıtlı Bir Kural Seçin --</option>
                            <template x-for="r in filteredSavedRules" :key="'sc_rule_' + r.id">
                                <option :value="r.id" x-text="r.title"></option>
                            </template>
                        </select>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Periyot (Sıklık) *</label>
                            <select x-model="newSchedule.frequency" class="w-full rounded-xl border-slate-200 text-xs">
                                <option value="daily">Günlük</option>
                                <option value="weekly">Haftalık (Pazartesi)</option>
                                <option value="monthly">Aylık (Ayın 1'i)</option>
                            </select>
                        </div>
                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Gönderim Saati *</label>
                            <input type="time" x-model="newSchedule.time_of_day"
                                class="w-full rounded-xl border-slate-200 text-xs">
                        </div>
                    </div>
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Alıcı Türü *</label>
                        <select x-model="newSchedule.recipient_type" class="w-full rounded-xl border-slate-200 text-xs">
                            <option value="management">Sistem Yöneticileri & Direktörler</option>
                            <option value="specific_users">Belirli Personeller</option>
                            <option value="custom_emails">Harici E-Posta Listesi</option>
                        </select>
                    </div>
                    <div x-show="newSchedule.recipient_type === 'custom_emails'">
                        <label class="block font-bold text-slate-700 mb-1">E-Posta Adresleri (Virgülle ayırın)</label>
                        <textarea x-model="newSchedule.custom_emails" rows="2"
                            class="w-full rounded-xl border-slate-200 text-xs"></textarea>
                    </div>
                </div>
                <div class="flex justify-end gap-2 pt-3 border-t border-slate-100">
                    <button type="button" @click="createScheduleModal = false"
                        class="px-4 py-2 rounded-xl border border-slate-200 text-xs font-bold text-slate-700">İptal</button>
                    <button type="button" @click="submitCreateSchedule()"
                        class="px-5 py-2 rounded-xl bg-indigo-600 text-white text-xs font-black">Zamanlamayı
                        Başlat</button>
                </div>
            </div>
        </div>

    </div>

    <!-- Alpine.js Raporlama Merkezi Mantığı -->
    <script>
        function reportCenter() {
            return {
                analysisMode: 'multi_pivot', // 'multi_pivot', 'yoy', 'segment', 'geo'
                templateId: '{{ $initialTemplateId }}',
                selectedRuleId: '{{ $selectedRule?->id ?? "" }}',
                templatesMetaMap: @json($templatesMeta),
                savedRulesList: @json($savedRules),
                schedulesCount: {{ $schedules->count() }},
                showFilters: false,

                // Şablon meta
                templateDimensions: @json($initialDimensions),
                templateMetrics: @json($initialMetrics),
                templateFilterFields: [],

                // MOD 1: Çok Boyutlu Pivot Durumu
                rowDimensions: ['field:20', 'field:22'], // Varsayılan İşyeri + Gramaj
                colDimension: 'field:44', // Varsayılan Yıl
                chartType: 'bar',

                // MOD 2: Yıllık Kıyaslama Durumu
                yoyYear1: '2026',
                yoyYear2: '2027',
                yoyEntityDimKey: 'user:0',

                // MOD 3: Segment Benchmark Durumu
                segA: {
                    title: 'PREFORM (Mavi)',
                    filters: { field_filters: { '20': 'PREFORM' } }
                },
                segB: {
                    title: 'KAPAK (Turkuaz)',
                    filters: { field_filters: { '20': 'KAPAK' } }
                },

                // MOD 4: Coğrafi Dünya Haritası Durumu
                geoCountryFieldId: '11',
                isMapFullscreen: false,
                geoSearch: '',
                geoPage: 1,
                geoPerPage: 10,

                // Personel ve Departman Çoktan Seçmeli Açılır Menü Verileri
                allUsers: @json($users->map(fn($u) => ['id' => $u->id, 'name' => $u->name, 'group_name' => $u->group?->name ?? 'Grup Yok'])->values()),
                allGroups: @json($groups->map(fn($g) => ['id' => $g->id, 'name' => $g->name])->values()),
                userDropdownOpen: false,
                groupDropdownOpen: false,

                // Form Durumu ve Statü Seçenekleri
                statusOptions: @json(array_values($statusOptions ?? \App\Services\ReportEngineS::getStatusMeta())),
                statusDropdownOpen: false,

                // Ortak Metrik ve Filtreler
                metricFieldId: '17', // Toplam Teslimat veya Tonaj
                metricAggregation: 'sum',
                filters: {
                    user_ids: [],
                    group_ids: [],
                    status: ['approved'],
                    start_date: '',
                    end_date: '',
                    field_filters: {}
                },

                // Rapor Yanıt Verisi
                reportData: {
                    mode: 'multi_pivot',
                    grand_total: 0,
                    summary: { total_rows: 0, analyzed_records: 0 },
                    rows: [],
                    columns: [],
                    items: [],
                    rankings: []
                },
                loading: false,
                currentAbortController: null,
                multiPivotChartInstance: null,
                yoyChartInstance: null,
                segmentChartInstance: null,

                get hasReportData() {
                    if (this.loading || !this.reportData) return false;
                    if (this.analysisMode === 'multi_pivot') {
                        return Array.isArray(this.reportData.rows) && this.reportData.rows.length > 0;
                    }
                    if (this.analysisMode === 'yoy') {
                        return Array.isArray(this.reportData.items) && this.reportData.items.length > 0;
                    }
                    if (this.analysisMode === 'geo') {
                        return Array.isArray(this.reportData.rankings) && this.reportData.rankings.length > 0;
                    }
                    if (this.analysisMode === 'segment') {
                        return !!(this.reportData.segment_a || this.reportData.segment_b);
                    }
                    return false;
                },

                // Modallar
                saveVariantModal: false,
                shareModal: false,
                schedulesListModal: false,
                createScheduleModal: false,
                sendingMail: false,

                ruleForm: { title: '', description: '', is_public: false },
                shareForm: { title: 'Dinamik Analiz Raporu', emails: '' },
                newSchedule: {
                    title: '',
                    report_rule_id: '',
                    frequency: 'weekly',
                    time_of_day: '08:30',
                    recipient_type: 'management',
                    recipient_user_ids: [],
                    custom_emails: ''
                },

                get filteredSavedRules() {
                    return this.savedRulesList.filter(r => r.form_template_id == this.templateId);
                },

                get filteredGeoRankings() {
                    const list = (this.reportData && this.reportData.rankings) ? this.reportData.rankings : [];
                    if (!this.geoSearch || !this.geoSearch.trim()) {
                        return list;
                    }
                    const q = this.geoSearch.trim().toLowerCase();
                    return list.filter(r => {
                        const nameMatch = (r.name || '').toLowerCase().includes(q);
                        const codeMatch = (r.code || '').toLowerCase().includes(q);
                        const userMatch = (r.top_users || []).some(u => (u || '').toLowerCase().includes(q));
                        return nameMatch || codeMatch || userMatch;
                    });
                },

                get geoTotalPages() {
                    const total = this.filteredGeoRankings.length;
                    return Math.max(1, Math.ceil(total / this.geoPerPage));
                },

                get paginatedGeoRankings() {
                    const filtered = this.filteredGeoRankings;
                    const start = (this.geoPage - 1) * this.geoPerPage;
                    return filtered.slice(start, start + this.geoPerPage);
                },

                get geoPaginationPages() {
                    const total = this.geoTotalPages;
                    const current = this.geoPage;
                    const delta = 2;
                    const range = [];
                    for (let i = Math.max(1, current - delta); i <= Math.min(total, current + delta); i++) {
                        range.push(i);
                    }
                    return range;
                },

                setGeoPage(p) {
                    if (p < 1) p = 1;
                    if (p > this.geoTotalPages) p = this.geoTotalPages;
                    this.geoPage = p;
                },

                toggleMapFullscreen() {
                    this.isMapFullscreen = !this.isMapFullscreen;
                    this.$nextTick(() => {
                        setTimeout(() => {
                            this.renderGeoMap();
                        }, 100);
                    });
                },

                init() {
                    this.loadTemplateMeta(this.templateId);
                    this.updateDynamicSelectOptions();
                    this.runReport();

                    window.addEventListener('resize', () => {
                        if (this.analysisMode === 'geo' && window.reportGeoMapInstance) {
                            clearTimeout(window.__geoMapResizeTimer);
                            window.__geoMapResizeTimer = setTimeout(() => {
                                this.renderGeoMap();
                            }, 200);
                        }
                    });
                },

                setAnalysisMode(mode) {
                    this.analysisMode = mode;
                    this.updateDynamicSelectOptions();
                    this.runReport();
                },

                getActiveFiltersCount(seg) {
                    if (!seg || !seg.filters || !seg.filters.field_filters) return 0;
                    return Object.values(seg.filters.field_filters).filter(v => v !== '' && v !== null && v !== undefined).length;
                },

                clearSegmentFilters(seg) {
                    if (!seg) return;
                    seg.filters = { field_filters: {} };
                },

                loadTemplateMeta(tId) {
                    const meta = this.templatesMetaMap[tId];
                    if (!meta) return;

                    this.templateDimensions = meta.dimensions || [];
                    this.templateMetrics = meta.metrics || [];
                    this.templateFilterFields = meta.filter_fields || [];

                    // Dinamik boyut hapları ve varsayılanlar
                    const fieldDims = this.templateDimensions.filter(d => d.type === 'field');
                    if (fieldDims.length > 0) {
                        if (this.rowDimensions.length === 0) {
                            this.rowDimensions = [fieldDims[0].id];
                        }
                    }

                    const hasMetric = (this.templateMetrics || []).some(m => String(m.field_id) === String(this.metricFieldId));
                    if (!hasMetric) {
                        const firstMetric = this.templateMetrics.find(m => m.id !== 'count');
                        this.metricFieldId = firstMetric ? String(firstMetric.field_id) : '';
                    }

                    this.updateDynamicSelectOptions();
                },

                updateDynamicSelectOptions() {
                    const updateDOM = () => {
                        const fieldDims = (this.templateDimensions || []).filter(d => d.type === 'field');
                        const numMetrics = (this.templateMetrics || []).filter(m => m.id !== 'count');

                        // 1. Sütun Kırılımı (colDimensionDynamicOptgroup)
                        const colGroup = document.getElementById('colDimensionDynamicOptgroup');
                        if (colGroup) {
                            colGroup.innerHTML = '';
                            fieldDims.forEach(dim => {
                                const opt = document.createElement('option');
                                opt.value = 'field:' + dim.field_id;
                                opt.textContent = dim.name;
                                colGroup.appendChild(opt);
                            });
                        }

                        // 2. Sayısal Metrikler (.dynamic-metrics-optgroup)
                        const metricGroups = document.querySelectorAll('.dynamic-metrics-optgroup');
                        metricGroups.forEach(grp => {
                            grp.innerHTML = '';
                            numMetrics.forEach(m => {
                                const opt = document.createElement('option');
                                opt.value = String(m.field_id);
                                opt.textContent = m.name;
                                grp.appendChild(opt);
                            });
                        });

                        // 3. YoY Varlık / Dinamik Form Alanları (yoyEntityDynamicOptgroup)
                        const yoyGroup = document.getElementById('yoyEntityDynamicOptgroup');
                        if (yoyGroup) {
                            yoyGroup.innerHTML = '';
                            fieldDims.forEach(dim => {
                                const opt = document.createElement('option');
                                opt.value = 'field:' + dim.field_id;
                                opt.textContent = dim.name;
                                yoyGroup.appendChild(opt);
                            });
                        }

                        // 4. Harita Ülke Alanı (geoCountryFieldSelect)
                        const geoCountrySelect = document.getElementById('geoCountryFieldSelect');
                        if (geoCountrySelect) {
                            while (geoCountrySelect.options.length > 1) {
                                geoCountrySelect.remove(1);
                            }
                            fieldDims.forEach(dim => {
                                const opt = document.createElement('option');
                                opt.value = String(dim.field_id);
                                opt.textContent = dim.name;
                                geoCountrySelect.appendChild(opt);
                            });
                        }
                    };

                    updateDOM();
                    if (window.Alpine && this.$nextTick) {
                        this.$nextTick(updateDOM);
                    }
                },

                onTemplateChange() {
                    this.selectedRuleId = '';
                    this.loadTemplateMeta(this.templateId);
                    this.updateDynamicSelectOptions();
                    this.runReport();
                },

                toggleRowDimension(dimId) {
                    const idx = this.rowDimensions.indexOf(dimId);
                    if (idx > -1) {
                        if (this.rowDimensions.length > 1) {
                            this.rowDimensions.splice(idx, 1);
                        }
                    } else {
                        this.rowDimensions.push(dimId);
                    }
                    this.runReport();
                },

                removeRowDimension(dimId) {
                    if (this.rowDimensions.length > 1) {
                        this.rowDimensions = this.rowDimensions.filter(d => d !== dimId);
                        this.runReport();
                    }
                },

                getDimensionLabel(dimKey) {
                    const found = this.templateDimensions.find(d => d.id === dimKey || ('field:' + d.field_id) === dimKey);
                    return found ? found.name.replace(/^[^\s]+\s/, '') : dimKey;
                },

                getUserName(id) {
                    const u = this.allUsers.find(x => x.id == id);
                    return u ? u.name : ('Personel #' + id);
                },

                getGroupName(id) {
                    const g = this.allGroups.find(x => x.id == id);
                    return g ? g.name : ('Grup #' + id);
                },

                filteredUsers(search) {
                   if (!search || !search.trim()) return this.allUsers;
                   const q = search.trim().toLowerCase();
                    return this.allUsers.filter(u => 
                        (u.name && u.name.toLowerCase().includes(q)) || 
                        (u.group_name && u.group_name.toLowerCase().includes(q))
                    );
                },

                filteredGroups(search) {
                    if (!search || !search.trim()) return this.allGroups;
                    const q = search.trim().toLowerCase();
                    return this.allGroups.filter(g => g.name && g.name.toLowerCase().includes(q));
                },

                selectAllVisibleUsers(search) {
                    const visible = this.filteredUsers(search);
                    const current = new Set((this.filters.user_ids || []).map(Number));
                    visible.forEach(u => current.add(Number(u.id)));
                    this.filters.user_ids = Array.from(current);
                },

                selectAllVisibleGroups(search) {
                    const visible = this.filteredGroups(search);
                    const current = new Set((this.filters.group_ids || []).map(Number));
                    visible.forEach(g => current.add(Number(g.id)));
                    this.filters.group_ids = Array.from(current);
                },

                removeUser(id) {
                    this.filters.user_ids = (this.filters.user_ids || []).filter(x => x != id);
                },

                removeGroup(id) {
                    this.filters.group_ids = (this.filters.group_ids || []).filter(x => x != id);
                },

                getStatusOption(key) {
                    return this.statusOptions.find(s => s.key === key) || { key: key, label: key, row_label: key, bg_class: 'bg-slate-100', text_class: 'text-slate-700', border_class: 'border-slate-300', dot_class: 'bg-slate-500' };
                },

                getStatusButtonLabel() {
                    if (!this.filters.status || this.filters.status.length === 0) {
                        return 'Durum Seçiniz';
                    }
                    if (this.filters.status.length === 1 && this.filters.status[0] === 'approved') {
                        return 'Onaylananlar (Varsayılan)';
                    }
                    if (this.filters.status.length === 1) {
                        return this.getStatusOption(this.filters.status[0]).label;
                    }
                    if (this.filters.status.length === this.statusOptions.length) {
                        return 'Tüm Durumlar (' + this.filters.status.length + ')';
                    }
                    return this.filters.status.length + ' Durum Seçili';
                },

                toggleStatus(key) {
                    if (!Array.isArray(this.filters.status)) {
                        this.filters.status = [];
                    }
                    const idx = this.filters.status.indexOf(key);
                    if (idx > -1) {
                        this.filters.status.splice(idx, 1);
                    } else {
                        this.filters.status.push(key);
                    }
                },

                setStatusPreset(preset) {
                    if (preset === 'only_approved') {
                        this.filters.status = ['approved'];
                    } else if (preset === 'all') {
                        this.filters.status = this.statusOptions.map(s => s.key);
                    } else if (preset === 'approved_draft') {
                        this.filters.status = ['approved', 'draft'];
                    } else if (preset === 'pending_approved') {
                        this.filters.status = ['approved', 'submitted'];
                    } else if (preset === 'only_draft') {
                        this.filters.status = ['draft'];
                    } else if (preset === 'only_rejected') {
                        this.filters.status = ['rejected'];
                    }
                },

                removeStatus(key) {
                    if (Array.isArray(this.filters.status)) {
                        this.filters.status = this.filters.status.filter(s => s !== key);
                    }
                },

                selectAllStatuses() {
                    this.filters.status = this.statusOptions.map(s => s.key);
                },

                onRuleSelect() {
                    if (!this.selectedRuleId) return;
                    const rule = this.savedRulesList.find(r => r.id == this.selectedRuleId);
                    if (rule) {
                        this.applyRule(rule);
                    }
                },

                applyRule(rule) {
                    this.templateId = rule.form_template_id;
                    this.metricFieldId = rule.metric_field_id || '';
                    this.metricAggregation = rule.metric_aggregation || 'sum';
                    this.chartType = rule.chart_type || 'bar';
                    this.filters = Object.assign({
                        user_ids: [],
                        group_ids: [],
                        status: ['approved'],
                        start_date: '',
                        end_date: '',
                        field_filters: {}
                    }, rule.filters || {});

                    this.runReport();
                },

                resetFilters() {
                    this.filters = {
                        user_ids: [],
                        group_ids: [],
                        status: ['approved'],
                        start_date: '',
                        end_date: '',
                        field_filters: {}
                    };
                },

                getQueryParams() {
                    const params = {
                        analysis_mode: this.analysisMode,
                        form_template_id: parseInt(this.templateId),
                        metric_field_id: this.metricFieldId ? parseInt(this.metricFieldId) : null,
                        metric_aggregation: this.metricAggregation,
                        filters: this.filters
                    };

                    if (this.analysisMode === 'multi_pivot') {
                        params.row_dimensions = this.rowDimensions;
                        params.col_dimension = this.colDimension;
                        params.chart_type = this.chartType;
                    } else if (this.analysisMode === 'yoy') {
                        params.year_1 = this.yoyYear1;
                        params.year_2 = this.yoyYear2;
                        params.entity_dim_key = this.yoyEntityDimKey;
                    } else if (this.analysisMode === 'segment') {
                        params.segment_a = this.segA;
                        params.segment_b = this.segB;
                    } else if (this.analysisMode === 'geo') {
                        params.country_field_id = this.geoCountryFieldId ? parseInt(this.geoCountryFieldId) : null;
                    }

                    return params;
                },

                async runReport() {
                    // Önceki devam eden sorgu varsa iptal et
                    if (this.currentAbortController) {
                        try { this.currentAbortController.abort(); } catch (e) { /* ignore */ }
                    }
                    this.currentAbortController = new AbortController();

                    this.loading = true;
                    try {
                        const params = this.getQueryParams();
                        const res = await fetch(`{{ route('reports.query') }}`, {
                            method: 'POST',
                            signal: this.currentAbortController.signal,
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                            },
                            body: JSON.stringify(params)
                        });

                        const data = await res.json();
                        if (data.error) {
                            alert(data.error);
                            return;
                        }

                        this.reportData = data;
                        this.geoPage = 1;
                        this.loading = false;

                        this.$nextTick(() => {
                            setTimeout(() => {
                                if (this.analysisMode === 'geo') {
                                    this.renderGeoMap();
                                } else {
                                    this.renderChart();
                                }
                            }, 60);
                        });
                    } catch (e) {
                        if (e.name === 'AbortError') {
                            return; // Yeni sorgu başlatıldığı için önceki sessizce iptal edildi
                        }
                        console.error('Rapor hatası:', e);
                        alert('Rapor sorgulanırken bir iletişim hatası oluştu.');
                    } finally {
                        this.loading = false;
                    }
                },

                renderChart(retryCount = 0) {
                    if (!this.reportData || !this.reportData.chartData) return;
                    if (this.analysisMode === 'geo') return;

                    let canvasId = 'multiPivotChartCanvas';
                    if (this.analysisMode === 'yoy') canvasId = 'yoyChartCanvas';
                    if (this.analysisMode === 'segment') canvasId = 'segmentChartCanvas';

                    const canvas = document.getElementById(canvasId);
                    if (!canvas) {
                        if (retryCount < 5) {
                            setTimeout(() => this.renderChart(retryCount + 1), 100);
                        }
                        return;
                    }

                    // Chart.js kütüphanesi henüz yüklenmemişse bekle ve tekrar dene
                    if (typeof Chart === 'undefined') {
                        if (retryCount < 5) {
                            setTimeout(() => this.renderChart(retryCount + 1), 100);
                        }
                        return;
                    }

                    // Canvas element ve getContext kontrolü
                    if (!canvas.getContext) return;

                    // Devam eden animasyonları durdur ve eski chart nesnelerini güvenle temizle
                    const safeDestroy = (inst) => {
                        if (inst && typeof inst.destroy === 'function') {
                            try {
                                if (typeof inst.stop === 'function') {
                                    inst.stop();
                                }
                                inst.destroy();
                            } catch (e) { /* ignore */ }
                        }
                    };

                    safeDestroy(this.multiPivotChartInstance);
                    this.multiPivotChartInstance = null;
                    safeDestroy(this.yoyChartInstance);
                    this.yoyChartInstance = null;
                    safeDestroy(this.segmentChartInstance);
                    this.segmentChartInstance = null;

                    // Chart.js registry'sinde bu canvas'a bağlı aktif chart varsa animasyonunu durdurup temizle
                    if (typeof Chart !== 'undefined' && typeof Chart.getChart === 'function') {
                        try {
                            const existing = Chart.getChart(canvas);
                            if (existing) {
                                if (typeof existing.stop === 'function') {
                                    existing.stop();
                                }
                                existing.destroy();
                            }
                        } catch (e) {
                            /* ignore */
                        }
                    }

                    const cData = this.reportData.chartData;
                    if (!cData || !cData.labels || cData.labels.length === 0) return;

                    let actualType = cData.type || 'bar';
                    let isHorizontal = (this.chartType === 'horizontalBar');
                    let isStacked = (this.chartType === 'stackedBar');
                    if (isHorizontal) actualType = 'bar';

                    try {
                        const chartObj = new Chart(canvas, {
                            type: actualType,
                            data: {
                                labels: cData.labels,
                                datasets: Array.isArray(cData.datasets) ? cData.datasets : []
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: false,
                                animation: {
                                    duration: 300
                                },
                                indexAxis: isHorizontal ? 'y' : 'x',
                                plugins: {
                                    legend: {
                                        display: (cData.datasets && cData.datasets.length > 1) || ['pie', 'doughnut'].includes(actualType),
                                        position: 'top',
                                        labels: { font: { size: 11, weight: 'bold' } }
                                    },
                                    tooltip: {
                                        callbacks: {
                                            label: (context) => {
                                                const label = context.dataset.label || '';
                                                const val = context.parsed.y !== undefined && !isHorizontal ? context.parsed.y : (context.parsed.x !== undefined ? context.parsed.x : context.raw);
                                                return `${label}: ${this.formatDecimal(val)}`;
                                            }
                                        }
                                    }
                                },
                                scales: ['pie', 'doughnut', 'radar'].includes(actualType) ? {} : {
                                    x: { stacked: isStacked, grid: { display: false } },
                                    y: {
                                        stacked: isStacked,
                                        grid: { color: '#f1f5f9' },
                                        ticks: { callback: (val) => this.formatNumber(val) }
                                    }
                                }
                            }
                        });

                        if (this.analysisMode === 'yoy') this.yoyChartInstance = chartObj;
                        else if (this.analysisMode === 'segment') this.segmentChartInstance = chartObj;
                        else this.multiPivotChartInstance = chartObj;
                    } catch (chartErr) {
                        console.error('Chart.js oluşturma hatası:', chartErr);
                    }
                },

                renderGeoMap(retryCount = 0) {
                    if (!this.reportData || !this.reportData.geoCountries) return;
                    const container = document.getElementById('reportWorldMap');
                    if (!container) {
                        if (retryCount < 5) {
                            setTimeout(() => this.renderGeoMap(retryCount + 1), 100);
                        }
                        return;
                    }

                    if (window.reportGeoMapInstance) {
                        try {
                            window.reportGeoMapInstance.destroy();
                        } catch (e) {
                            /* ignore */
                        }
                        window.reportGeoMapInstance = null;
                    }
                    container.innerHTML = '';

                    const geoCountries = this.reportData.geoCountries || {};
                    const countryColors = this.reportData.countryColors || {};

                    const applyColors = (m) => {
                        if (!m || !m.regions) return;
                        Object.keys(countryColors).forEach(code => {
                            const r = m.regions[code];
                            if (r && r.element) {
                                r.element.setStyle('fill', countryColors[code]);
                                if (r.element.shape && r.element.shape.style && r.element.shape.style.initial) {
                                    r.element.shape.style.initial.fill = countryColors[code];
                                }
                            }
                        });
                    };

                    try {
                        window.reportGeoMapInstance = new jsVectorMap({
                            selector: '#reportWorldMap',
                            map: 'world',
                            backgroundColor: 'transparent',
                            draggable: true,
                            zoomButtons: true,
                            zoomOnScroll: true,
                            zoomOnScrollSpeed: 0.8,
                            zoomMax: 15,
                            zoomMin: 1,
                            regionStyle: {
                                initial: {
                                    fill: '#ffffff', // Hiç veri olmayan ülkeler Saf Beyaz
                                    stroke: '#94a3b8', // Net sınır çizgileri
                                    strokeWidth: 0.5,
                                    fillOpacity: 1
                                },
                                hover: {
                                    fill: '#f59e0b',
                                    cursor: 'pointer'
                                }
                            },
                            onLoaded: (m) => {
                                applyColors(m);
                            },
                            onRegionTooltipShow(event, tooltip, code) {
                                const country = geoCountries[code];
                                if (country) {
                                    const userList = country.top_users || [];
                                    let topUsersHtml = '';
                                    if (userList.length === 0) {
                                        topUsersHtml = '<span class="text-slate-400 text-[10px]">Kayıt yok</span>';
                                    } else if (userList.length <= 4) {
                                        topUsersHtml = userList.map(u => `<span class="inline-block bg-slate-800 text-[10px] px-1.5 py-0.5 rounded text-indigo-300 mr-1 mb-1 font-semibold">${u}</span>`).join('');
                                    } else {
                                        const firstFour = userList.slice(0, 4).map(u => `<span class="inline-block bg-slate-800 text-[10px] px-1.5 py-0.5 rounded text-indigo-300 mr-1 mb-1 font-semibold">${u}</span>`).join('');
                                        const extraCount = userList.length - 4;
                                        topUsersHtml = `${firstFour}<span class="inline-block bg-amber-900/60 text-amber-300 text-[10px] font-bold px-1.5 py-0.5 rounded mr-1 mb-1 border border-amber-500/30">+${extraCount} personel daha</span>`;
                                    }
                                    
                                    const topCustHtml = (country.top_customers && country.top_customers.length)
                                        ? country.top_customers.join(', ')
                                        : '-';

                                    tooltip.text(`
                                        <div style="z-index:999999999;" class="p-3 bg-slate-900/95 text-white rounded-xl shadow-2xl border-2 border-indigo-400 text-xs backdrop-blur-md min-w-[220px] max-w-[320px]">
                                            <div class="font-extrabold text-indigo-300 text-sm border-b border-slate-700 pb-1.5 mb-2 flex items-center justify-between">
                                                <div class="flex items-center gap-1.5">
                                                    <span class="w-2.5 h-2.5 rounded-full inline-block" style="background-color: ${country.color || '#6366f1'}"></span>
                                                    <span>${country.name}</span>
                                                </div>
                                                <span class="text-[10px] text-slate-400 font-mono">${code}</span>
                                            </div>
                                            <div class="space-y-1.5">
                                                <div class="flex justify-between text-slate-300">
                                                    <span>Toplam Metrik:</span>
                                                    <strong class="text-emerald-400 font-bold">${Number(country.total).toLocaleString('tr-TR')}</strong>
                                                </div>
                                                <div class="flex justify-between text-slate-300">
                                                    <span>Global Payı:</span>
                                                    <strong class="text-amber-400 font-black">%${country.share}</strong>
                                                </div>
                                                <div class="flex justify-between text-slate-300">
                                                    <span>Sipariş Sayısı:</span>
                                                    <strong class="text-white">${country.count} adet</strong>
                                                </div>
                                                <div class="pt-1.5 border-t border-slate-800 text-slate-300">
                                                    <span class="block text-[10px] text-slate-400 font-bold mb-1">İşlem Yapan Personeller:</span>
                                                    <div>${topUsersHtml}</div>
                                                </div>
                                                ${country.top_customers && country.top_customers.length ? `
                                                <div class="pt-1 border-t border-slate-800 text-slate-300">
                                                    <span class="block text-[10px] text-slate-400 font-bold">Önde Gelen Müşteriler:</span>
                                                    <span class="text-slate-200 text-[11px]">${topCustHtml}</span>
                                                </div>` : ''}
                                            </div>
                                        </div>
                                    `, true);
                                } else {
                                    tooltip.text(`<div style="z-index:999999999;" class="px-3 py-1.5 bg-slate-900/95 text-slate-300 text-xs rounded-xl border border-slate-700 shadow-xl flex items-center gap-2 backdrop-blur-md"><span class="w-2.5 h-2.5 rounded-full bg-white border border-slate-400 shadow-xs"></span><span><strong>${tooltip.text()}</strong>: <span class="text-slate-400">Kayıtlı Veri Yok (0)</span></span></div>`, true);
                                }
                            }
                        });

                        applyColors(window.reportGeoMapInstance);
                        setTimeout(() => applyColors(window.reportGeoMapInstance), 50);
                    } catch (e) {
                        console.error('jsVectorMap init error:', e);
                    }
                },

                downloadExcel() {
                    const params = this.getQueryParams();
                    params.report_title = this.getReportTitle();
                    const queryString = this.serializeParams(params);
                    window.location.href = `{{ route('reports.export.excel') }}?${queryString}`;
                },

                printReport() {
                    const params = this.getQueryParams();
                    params.report_title = this.getReportTitle();
                    const queryString = this.serializeParams(params);
                    window.open(`{{ route('reports.print') }}?${queryString}`, '_blank');
                },

                getReportTitle() {
                    if (this.analysisMode === 'yoy') return `Yıllık Kıyaslama (${this.yoyYear1} vs ${this.yoyYear2})`;
                    if (this.analysisMode === 'segment') return `Segment Kıyaslama (${this.segA.title} vs ${this.segB.title})`;
                    if (this.analysisMode === 'geo') return 'Coğrafi Dünya Satış Dağılımı';
                    return 'Çok Boyutlu Pivot Analizi';
                },

                openSaveRuleModal() {
                    this.ruleForm.title = this.getReportTitle();
                    this.saveVariantModal = true;
                },

                async submitSaveRule() {
                    if (!this.ruleForm.title.trim()) {
                        alert('Lütfen bir başlık giriniz.');
                        return;
                    }
                    try {
                        const payload = {
                            title: this.ruleForm.title,
                            description: this.ruleForm.description,
                            form_template_id: parseInt(this.templateId),
                            dimension_x_type: 'field',
                            dimension_x_field_id: 20,
                            metric_field_id: this.metricFieldId ? parseInt(this.metricFieldId) : null,
                            metric_aggregation: this.metricAggregation,
                            chart_type: this.chartType,
                            filters: this.filters,
                            is_public: this.ruleForm.is_public
                        };
                        const res = await fetch(`{{ route('reports.rules.store') }}`, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                            },
                            body: JSON.stringify(payload)
                        });
                        const data = await res.json();
                        if (data.success) {
                            alert(data.message);
                            this.savedRulesList.push(data.rule);
                            this.selectedRuleId = data.rule.id;
                            this.saveVariantModal = false;
                        } else {
                            alert(data.error || 'Kaydedilemedi.');
                        }
                    } catch (e) {
                        alert('Kaydetme hatası.');
                    }
                },

                async deleteSelectedRule() {
                    if (!this.selectedRuleId) return;
                    if (!confirm('Bu rapor kuralını silmek istediğinize emin misiniz?')) return;
                    try {
                        const res = await fetch(`{{ url('reports/rules') }}/${this.selectedRuleId}`, {
                            method: 'DELETE',
                            headers: {
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                            }
                        });
                        const data = await res.json();
                        if (data.success) {
                            this.savedRulesList = this.savedRulesList.filter(r => r.id != this.selectedRuleId);
                            this.selectedRuleId = '';
                            alert('Varyant silindi.');
                        }
                    } catch (e) {
                        alert('Silinemedi.');
                    }
                },

                async submitShare() {
                    if (!this.shareForm.emails.trim()) {
                        alert('Lütfen en az bir e-posta adresi giriniz.');
                        return;
                    }
                    this.sendingMail = true;
                    try {
                        const params = this.getQueryParams();
                        params.emails = this.shareForm.emails;
                        params.report_title = this.shareForm.title || this.getReportTitle();

                        const res = await fetch(`{{ route('reports.share') }}`, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                            },
                            body: JSON.stringify(params)
                        });
                        const data = await res.json();
                        if (data.success) {
                            alert(data.message);
                            this.shareModal = false;
                            this.shareForm.emails = '';
                        } else {
                            alert(data.error || 'Paylaşılamadı.');
                        }
                    } catch (e) {
                        alert('E-posta paylaşımı sırasında bir hata oluştu.');
                    } finally {
                        this.sendingMail = false;
                    }
                },

                openCreateScheduleModal() {
                    this.newSchedule.title = this.getReportTitle();
                    this.createScheduleModal = true;
                },

                async submitCreateSchedule() {
                    if (!this.newSchedule.title.trim()) {
                        alert('Lütfen bir başlık giriniz.');
                        return;
                    }
                    if (!this.newSchedule.report_rule_id) {
                        alert('Lütfen gönderilecek kayıtlı rapor varyantını seçiniz.');
                        return;
                    }
                    try {
                        const res = await fetch(`{{ route('reports.schedules.store') }}`, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                            },
                            body: JSON.stringify(this.newSchedule)
                        });
                        const data = await res.json();
                        if (data.success) {
                            alert(data.message);
                            window.location.reload();
                        } else {
                            alert(data.error || 'Zamanlama kaydedilemedi.');
                        }
                    } catch (e) {
                        alert('Zamanlama kaydetme hatası.');
                    }
                },

                async toggleSchedule(id) {
                    try {
                        const res = await fetch(`{{ url('reports/schedules') }}/${id}/toggle`, {
                            method: 'POST',
                            headers: {
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                            }
                        });
                        const data = await res.json();
                        if (data.success) {
                            window.location.reload();
                        }
                    } catch (e) {
                        alert('Durum güncellenemedi.');
                    }
                },

                async deleteSchedule(id) {
                    if (!confirm('Bu zamanlanmış görevi silmek istediğinize emin misiniz?')) return;
                    try {
                        const res = await fetch(`{{ url('reports/schedules') }}/${id}`, {
                            method: 'DELETE',
                            headers: {
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                            }
                        });
                        const data = await res.json();
                        if (data.success) {
                            window.location.reload();
                        }
                    } catch (e) {
                        alert('Silinemedi.');
                    }
                },

                serializeParams(obj, prefix) {
                    const str = [];
                    for (let p in obj) {
                        if (obj.hasOwnProperty(p)) {
                            const k = prefix ? prefix + "[" + p + "]" : p;
                            const v = obj[p];
                            if (v !== null && typeof v === "object") {
                                str.push(this.serializeParams(v, k));
                            } else if (v !== null && v !== undefined && v !== '') {
                                str.push(encodeURIComponent(k) + "=" + encodeURIComponent(v));
                            }
                        }
                    }
                    return str.filter(Boolean).join("&");
                },

                formatNumber(val) {
                    if (val === null || val === undefined) return '0';
                    return new Intl.NumberFormat('tr-TR').format(val);
                },

                formatDecimal(val) {
                    if (val === null || val === undefined) return '0,00';
                    return new Intl.NumberFormat('tr-TR', {
                        minimumFractionDigits: 2,
                        maximumFractionDigits: 2
                    }).format(val);
                }
            };
        }
    </script>
</x-app-layout>
