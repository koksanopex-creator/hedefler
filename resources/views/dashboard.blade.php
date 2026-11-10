@section('title', 'Dashboard')
<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-2">
            <div>
                <h2 class="font-extrabold text-2xl text-gray-900 tracking-tight">Dashboard</h2>
                <p class="text-sm text-gray-500 mt-0.5">Hoş geldiniz, {{ Auth::user()->name }}</p>
            </div>
            <span class="text-xs text-gray-400 bg-gray-50 px-3 py-1.5 rounded-full border border-gray-100">{{ now()->translatedFormat('d F Y, l') }}</span>
        </div>
    </x-slot>

    <div class="py-6 sm:py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-8">

            {{-- ─── 0. DİNAMİK ZAMAN & KPI DÖNEM FİLTRESİ ─── --}}
            @if(!empty($permissions['date_cockpit']))
            <div class="bg-white rounded-2xl border border-slate-200/90 shadow-sm p-4 sm:p-5">
                <form method="GET" action="{{ route('dashboard') }}" id="dashboardFilterForm" class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-indigo-50 border border-indigo-100 flex items-center justify-center text-indigo-600 shadow-sm flex-shrink-0">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                        </div>
                        <div>
                            <div class="flex items-center gap-2">
                                <h3 class="text-sm font-bold text-slate-900">
                                    @if(!empty($permissions['kpi_cockpit']))
                                        Dashboard & KPI Dönem Kokpiti
                                    @else
                                        Dashboard Dönem Filtresi
                                    @endif
                                </h3>
                                <span class="text-[11px] font-semibold text-indigo-700 bg-indigo-50 px-2 py-0.5 rounded-md border border-indigo-100">
                                    {{ $filters['year'] }} @if(!empty($filters['month'])) &bull; {{ \Carbon\Carbon::create(2024, $filters['month'], 1)->translatedFormat('F') }} @else &bull; Yıllık Kümülatif @endif
                                </span>
                            </div>
                            <p class="text-xs text-slate-500 mt-0.5">Tüm form özetleri, grafikler ve KPI gerçekleşmeleri seçilen döneme göre anlık hesaplanır.</p>
                        </div>
                    </div>

                    <div class="flex flex-wrap items-center gap-2 sm:gap-3">
                        {{-- Yıl Seçici --}}
                        <div class="flex items-center gap-1.5 bg-slate-50 px-2.5 py-1.5 rounded-xl border border-slate-200 shadow-sm">
                            <span class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">Yıl:</span>
                            <select name="year" onchange="document.getElementById('dashboardFilterForm').submit()" class="border-0 bg-transparent py-0 pl-1 pr-7 text-xs font-bold text-slate-800 focus:ring-0 cursor-pointer">
                                @foreach($availableYears as $y)
                                    <option value="{{ $y }}" {{ ($filters['year'] == $y) ? 'selected' : '' }}>{{ $y }}</option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Dönem / Ay Seçici --}}
                        <div class="flex items-center gap-1.5 bg-slate-50 px-2.5 py-1.5 rounded-xl border border-slate-200 shadow-sm">
                            <span class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">Dönem:</span>
                            <select name="month" onchange="document.getElementById('dashboardFilterForm').submit()" class="border-0 bg-transparent py-0 pl-1 pr-7 text-xs font-bold text-slate-800 focus:ring-0 cursor-pointer">
                                <option value="">Tüm Yıl (Kümülatif)</option>
                                @for($m = 1; $m <= 12; $m++)
                                    <option value="{{ $m }}" {{ ($filters['month'] == $m) ? 'selected' : '' }}>
                                        {{ \Carbon\Carbon::create(2024, $m, 1)->translatedFormat('F') }}
                                    </option>
                                @endfor
                            </select>
                        </div>

                        {{-- Özel Tarih Aralığı Açılır/Kapanır (Modal/Dropdown) --}}
                        <div x-data="{ openRange: {{ (!empty($filters['start_date']) || !empty($filters['end_date'])) ? 'true' : 'false' }} }" class="relative">
                            <button type="button" @click="openRange = !openRange" class="flex items-center gap-1.5 px-3 py-1.5 rounded-xl border text-xs font-semibold transition-colors shadow-sm" :class="openRange ? 'bg-indigo-50 border-indigo-200 text-indigo-700' : 'bg-slate-50 border-slate-200 text-slate-600 hover:bg-slate-100'">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                <span>Tarih Aralığı</span>
                                @if(!empty($filters['start_date']) || !empty($filters['end_date']))
                                    <span class="w-1.5 h-1.5 rounded-full bg-indigo-600"></span>
                                @endif
                            </button>

                            <div x-show="openRange" x-cloak @click.away="openRange = false" class="absolute right-0 mt-2 p-3 bg-white rounded-xl shadow-xl border border-slate-200 z-30 w-72 space-y-2">
                                <div>
                                    <label class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">Başlangıç Tarihi</label>
                                    <input type="date" name="start_date" value="{{ $filters['start_date'] ?? '' }}" class="mt-0.5 w-full text-xs rounded-lg border-slate-300 focus:ring-indigo-500">
                                </div>
                                <div>
                                    <label class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">Bitiş Tarihi</label>
                                    <input type="date" name="end_date" value="{{ $filters['end_date'] ?? '' }}" class="mt-0.5 w-full text-xs rounded-lg border-slate-300 focus:ring-indigo-500">
                                </div>
                                <div class="flex items-center justify-between pt-1">
                                    <button type="button" @click="openRange = false" class="text-xs text-slate-500 hover:text-slate-700">Kapat</button>
                                    <button type="submit" class="px-3 py-1 bg-indigo-600 text-white text-xs font-bold rounded-lg hover:bg-indigo-700 shadow-sm">Uygula</button>
                                </div>
                            </div>
                        </div>

                        {{-- Personel İnceleme Dropdown'u (Yönetim & Superadmin için) --}}
                        @if(!empty($permissions['kpi_cockpit']) && !empty($kpiCockpit['allUsersList']) && count($kpiCockpit['allUsersList']) > 0)
                        <div class="flex items-center gap-1.5 bg-indigo-50/80 px-2.5 py-1.5 rounded-xl border border-indigo-200 shadow-sm">
                            <span class="text-[11px] font-bold text-indigo-700 uppercase tracking-wider flex items-center gap-1">
                                <svg class="w-3.5 h-3.5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                                Personel:
                            </span>
                            <select name="inspect_user_id" onchange="document.getElementById('dashboardFilterForm').submit()" class="border-0 bg-transparent py-0 pl-1 pr-7 text-xs font-bold text-indigo-950 focus:ring-0 cursor-pointer max-w-[210px]">
                                @if($kpiCockpit['isSuperAdmin'] || $kpiCockpit['isYonetim'])
                                    <option value="">👤 Personel İncele (Seçiniz)</option>
                                @else
                                    <option value="">👤 Kendim ({{ Auth::user()->name }})</option>
                                @endif
                                @foreach($kpiCockpit['allUsersList'] as $u)
                                    <option value="{{ $u->id }}" {{ ($kpiCockpit['inspectUserId'] == $u->id) ? 'selected' : '' }}>
                                        👤 {{ $u->name }} ({{ $u->group?->name ?? 'Grup Yok' }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        @endif

                        {{-- Sıfırla Linki --}}
                        @if(!empty($filters['month']) || !empty($filters['start_date']) || !empty($filters['end_date']) || !empty($kpiCockpit['inspectUserId']) || $filters['year'] != date('Y'))
                        <a href="{{ route('dashboard') }}" class="p-2 text-slate-400 hover:text-rose-600 hover:bg-rose-50 rounded-xl transition-colors shadow-sm border border-slate-100" title="Tüm Filtreleri Sıfırla">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </a>
                        @endif
                    </div>
                </form>
            </div>
            @endif

            {{-- ─── 0.1 ROL BAZLI KPI KOKPİTİ (BİREYSEL KÜMÜLATİF + AKORDEON + GRUP / YÖNETİM) ─── --}}
            @if(!empty($permissions['kpi_cockpit']) && !empty($kpiCockpit))
            <div class="space-y-6">

                {{-- Şirket Geneli KPI Başarısı (Yönetim & Superadmin) --}}
                @if(!empty($kpiCockpit['companyScorecard']))
                <div class="bg-gradient-to-r from-slate-900 via-indigo-950 to-slate-900 rounded-2xl p-5 sm:p-6 text-white shadow-xl relative overflow-hidden">
                    <div class="absolute -right-10 -bottom-10 w-60 h-60 bg-indigo-500/10 rounded-full blur-3xl pointer-events-none"></div>
                    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-6 relative z-10">
                        <div class="space-y-1">
                            <div class="flex items-center gap-2">
                                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-extrabold uppercase tracking-wider bg-indigo-500/30 text-indigo-200 border border-indigo-400/30">
                                    Yönetim Özeti
                                </span>
                                <span class="text-xs text-slate-400">{{ $filters['year'] }} Yılı Kurumsal KPI Durumu</span>
                            </div>
                            <h3 class="text-xl sm:text-2xl font-black tracking-tight text-white">Şirket Geneli Hedef Başarısı</h3>
                            <p class="text-xs text-slate-300 max-w-xl">
                                Tüm departman ve kullanıcıların aktif KPI hedeflerinin kümülatif gerçekleşme performansı.
                            </p>
                        </div>

                        <div class="flex flex-wrap items-center gap-6 sm:gap-8">
                            <div class="text-center sm:text-left">
                                <div class="text-3xl sm:text-4xl font-black text-indigo-400">
                                    %{{ $kpiCockpit['companyScorecard']['average_rate'] }}
                                </div>
                                <div class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider">Genel Ortalama</div>
                            </div>
                            <div class="h-10 w-px bg-slate-800 hidden sm:block"></div>
                            <div class="text-center sm:text-left">
                                <div class="text-2xl sm:text-3xl font-black text-white">
                                    {{ $kpiCockpit['companyScorecard']['total_targets'] }}
                                </div>
                                <div class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider">Aktif Hedef</div>
                            </div>
                            <div class="h-10 w-px bg-slate-800 hidden sm:block"></div>
                            <div class="text-center sm:text-left">
                                <div class="text-2xl sm:text-3xl font-black text-emerald-400">
                                    {{ $kpiCockpit['companyScorecard']['achieved_count'] }}
                                </div>
                                <div class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider">Hedefe Ulaşan</div>
                            </div>
                            <a href="{{ route('kpi-targets.index') }}" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-bold rounded-xl shadow-lg shadow-indigo-600/30 transition-all flex items-center gap-1.5 self-center">
                                <span>KPI Yönetimi</span>
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                            </a>
                        </div>
                    </div>
                </div>
                @endif

                {{-- BİREYSEL KÜMÜLATİF PERFORMANS KARTI (Hero Scorecard) --}}
                @if(!empty($kpiCockpit['myScorecard']))
                <div class="bg-white rounded-2xl border border-slate-200/90 shadow-sm p-6 sm:p-7 relative overflow-hidden" x-data="{ openTargets: false }">
                    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-6 pb-6 border-b border-slate-100">
                        {{-- Sol: Personel & Başlık --}}
                        <div class="space-y-2">
                            <div class="flex items-center gap-2">
                                @if(!empty($kpiCockpit['inspectUserId']) && $kpiCockpit['inspectUserId'] != Auth::id())
                                    <span class="px-2.5 py-1 rounded-lg text-xs font-bold bg-amber-50 text-amber-700 border border-amber-200 flex items-center gap-1">
                                        <svg class="w-3.5 h-3.5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                        Yönetici İnceleme Modu
                                    </span>
                                @else
                                    <span class="px-2.5 py-1 rounded-lg text-xs font-bold bg-indigo-50 text-indigo-700 border border-indigo-100">
                                        Bireysel Performans
                                    </span>
                                @endif
                                <span class="px-2.5 py-1 rounded-lg text-xs font-bold border {{ $kpiCockpit['myScorecard']['grade_color'] }}">
                                    {{ $kpiCockpit['myScorecard']['grade'] }}
                                </span>
                            </div>

                            <div>
                                <h2 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight flex items-center gap-2">
                                    <span>🎯 {{ $kpiCockpit['myScorecard']['user']->name }}</span>
                                    @if($kpiCockpit['myScorecard']['user']->group)
                                        <span class="text-xs font-normal text-slate-500">({{ $kpiCockpit['myScorecard']['user']->group->name }})</span>
                                    @endif
                                </h2>
                                <p class="text-xs text-slate-500 mt-1">
                                    {{ $filters['year'] }} Yılı @if(!empty($filters['month'])) {{ \Carbon\Carbon::create(2024, $filters['month'], 1)->translatedFormat('F') }} Ayı @else Kümülatif Yıllık @endif Hedef Gerçekleşme Özeti
                                </p>
                            </div>
                        </div>

                        {{-- Sağ: Kümülatif Başarı Göstergesi & Oran --}}
                        <div class="flex items-center gap-6 sm:gap-8 bg-slate-50/80 p-4 sm:p-5 rounded-2xl border border-slate-200/80">
                            <div class="space-y-1 min-w-[140px] text-right">
                                <span class="text-[11px] font-bold text-slate-500 uppercase tracking-wider block">Kümülatif Başarı</span>
                                <div class="text-3xl sm:text-4xl font-black {{ $kpiCockpit['myScorecard']['average_rate'] >= 100 ? 'text-emerald-600' : ($kpiCockpit['myScorecard']['average_rate'] >= 70 ? 'text-indigo-600' : ($kpiCockpit['myScorecard']['total_targets'] > 0 ? 'text-rose-600' : 'text-slate-400')) }}">
                                    %{{ $kpiCockpit['myScorecard']['average_rate'] }}
                                </div>
                                <span class="text-[10px] text-slate-400 block">Tüm hedeflerin ağırlıklı ortalaması</span>
                            </div>

                            <div class="w-16 h-16 sm:w-20 sm:h-20 rounded-full flex items-center justify-center relative flex-shrink-0 bg-white border-4 {{ $kpiCockpit['myScorecard']['average_rate'] >= 100 ? 'border-emerald-500 text-emerald-600' : ($kpiCockpit['myScorecard']['average_rate'] >= 70 ? 'border-indigo-500 text-indigo-600' : ($kpiCockpit['myScorecard']['total_targets'] > 0 ? 'border-rose-500 text-rose-600' : 'border-slate-200 text-slate-400')) }} shadow-sm">
                                <span class="text-xs sm:text-sm font-black">%{{ round($kpiCockpit['myScorecard']['average_rate']) }}</span>
                            </div>
                        </div>
                    </div>

                    {{-- İlerleme Çubuğu & Mini İstatistikler --}}
                    <div class="py-5 space-y-4">
                        <div class="space-y-1.5">
                            <div class="flex justify-between items-center text-xs font-bold">
                                <span class="text-slate-600">Genel Hedef Ulaşma Düzeyi</span>
                                <span class="text-slate-900">%{{ $kpiCockpit['myScorecard']['average_rate'] }}</span>
                            </div>
                            <div class="w-full bg-slate-100 rounded-full h-3 overflow-hidden p-0.5 border border-slate-200/60">
                                <div class="h-2 rounded-full transition-all duration-700 {{ $kpiCockpit['myScorecard']['average_rate'] >= 100 ? 'bg-emerald-500' : ($kpiCockpit['myScorecard']['average_rate'] >= 70 ? 'bg-indigo-600' : ($kpiCockpit['myScorecard']['total_targets'] > 0 ? 'bg-rose-500' : 'bg-slate-300')) }}" style="width: min(100%, {{ $kpiCockpit['myScorecard']['average_rate'] }}%)"></div>
                            </div>
                        </div>

                        {{-- 4'lü Özet Sayaçlar --}}
                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 pt-2">
                            <div class="bg-slate-50 p-3 rounded-xl border border-slate-200/70 text-center">
                                <div class="text-xl font-black text-slate-800">{{ $kpiCockpit['myScorecard']['total_targets'] }}</div>
                                <div class="text-[10px] font-bold text-slate-500 uppercase tracking-wider mt-0.5">Tanımlı Hedef</div>
                            </div>
                            <div class="bg-emerald-50/60 p-3 rounded-xl border border-emerald-100 text-center">
                                <div class="text-xl font-black text-emerald-700">{{ $kpiCockpit['myScorecard']['achieved_count'] }}</div>
                                <div class="text-[10px] font-bold text-emerald-600 uppercase tracking-wider mt-0.5">Hedefe Ulaşan</div>
                            </div>
                            <div class="bg-indigo-50/60 p-3 rounded-xl border border-indigo-100 text-center">
                                <div class="text-xl font-black text-indigo-700">{{ $kpiCockpit['myScorecard']['in_progress_count'] }}</div>
                                <div class="text-[10px] font-bold text-indigo-600 uppercase tracking-wider mt-0.5">Devam Eden</div>
                            </div>
                            <div class="bg-rose-50/60 p-3 rounded-xl border border-rose-100 text-center">
                                <div class="text-xl font-black text-rose-700">{{ $kpiCockpit['myScorecard']['at_risk_count'] }}</div>
                                <div class="text-[10px] font-bold text-rose-600 uppercase tracking-wider mt-0.5">Kritik / Riskli</div>
                            </div>
                        </div>
                    </div>

                    {{-- AKORDEON BUTONU: Kalem Kalem Hedefleri Aç/Kapa --}}
                    @if($kpiCockpit['myScorecard']['total_targets'] > 0)
                    <div class="pt-2">
                        <button type="button" @click="openTargets = !openTargets" class="w-full flex items-center justify-between p-3.5 bg-slate-50 hover:bg-slate-100/80 rounded-xl border border-slate-200/80 transition-all group">
                            <div class="flex items-center gap-2.5">
                                <div class="w-6 h-6 rounded-lg bg-indigo-100 text-indigo-700 flex items-center justify-center font-bold text-xs">
                                    {{ $kpiCockpit['myScorecard']['total_targets'] }}
                                </div>
                                <span class="text-xs font-bold text-slate-800 group-hover:text-indigo-600 transition-colors">
                                    Kalem Kalem Hedef Dağılımını & Başarı Oranlarını İncele
                                </span>
                            </div>
                            <div class="flex items-center gap-2 text-xs font-bold text-indigo-600">
                                <span x-text="openTargets ? 'Kapat' : 'Detayları Göster'"></span>
                                <svg class="w-4 h-4 transform transition-transform duration-200" :class="openTargets ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                            </div>
                        </button>

                        {{-- Akordeon İçeriği (Kalem Kalem Hedef Kartları) --}}
                        <div x-show="openTargets" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 -translate-y-2" x-transition:enter-end="opacity-100 translate-y-0" x-cloak class="mt-4 space-y-3.5">
                            @foreach($kpiCockpit['myScorecard']['targets'] as $tgt)
                            <div class="p-4 rounded-xl border border-slate-200 bg-slate-50/50 hover:bg-white hover:border-indigo-200 hover:shadow-sm transition-all space-y-3">
                                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                                    <div class="space-y-1">
                                        <div class="flex items-center gap-2">
                                            <h4 class="text-sm font-black text-slate-900">{{ $tgt['title'] }}</h4>
                                            <span class="text-[11px] font-extrabold px-2 py-0.5 rounded-full border {{ $tgt['badge_class'] }}">
                                                %{{ $tgt['achievement_rate'] }}
                                            </span>
                                        </div>
                                        <p class="text-xs text-slate-500">
                                            Şablon: <span class="font-medium text-slate-700">{{ $tgt['template'] }}</span> &bull; Hedef Alanı: <span class="font-medium text-slate-700">{{ $tgt['target_field'] }}</span>
                                        </p>
                                    </div>

                                    <div class="flex items-center gap-4 text-right">
                                        <div>
                                            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Hedef / Gerçekleşen</span>
                                            <span class="text-xs font-black text-slate-800">
                                                {{ number_format($tgt['actual_value'], 0, ',', '.') }} / {{ number_format($tgt['target_value'], 0, ',', '.') }} {{ $tgt['unit'] }}
                                            </span>
                                        </div>
                                        <a href="{{ route('kpi-targets.progress', $tgt['id']) }}" class="px-3 py-1.5 bg-white text-indigo-600 hover:bg-indigo-50 border border-indigo-200 rounded-lg text-xs font-bold transition-colors shadow-xs" title="Detaylı İlerleme Çizelgesi">
                                            İlerleme &rarr;
                                        </a>
                                    </div>
                                </div>

                                {{-- Bireysel Hedef İlerleme Barı --}}
                                <div class="space-y-1">
                                    <div class="w-full bg-slate-200/70 rounded-full h-2 overflow-hidden">
                                        <div class="h-2 rounded-full transition-all duration-500 {{ $tgt['bar_color'] }}" style="width: min(100%, {{ $tgt['achievement_rate'] }}%)"></div>
                                    </div>
                                </div>

                                {{-- Kriter Badges --}}
                                @if(!empty($tgt['badges']) && count($tgt['badges']) > 0)
                                <div class="pt-1 flex flex-wrap items-center gap-1.5">
                                    <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mr-1">Kriterler:</span>
                                    @foreach($tgt['badges'] as $b)
                                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-semibold border {{ $b['bg_class'] ?? 'bg-slate-50 text-slate-700 border-slate-200' }}">
                                            <span>{{ $b['icon'] ?? '🏷️' }}</span>
                                            <span class="font-bold">{{ $b['label'] }}:</span>
                                            <span>{{ $b['value'] }}</span>
                                        </span>
                                    @endforeach
                                </div>
                                @endif
                            </div>
                            @endforeach
                        </div>
                    </div>
                    @else
                    <div class="pt-2">
                        <div class="p-5 rounded-xl border border-dashed border-slate-200 bg-slate-50 text-center space-y-2">
                            <p class="text-xs font-bold text-slate-600">
                                Seçilen dönem ({{ $filters['year'] }}@if(!empty($filters['month'])) / {{ \Carbon\Carbon::create(2024, $filters['month'], 1)->translatedFormat('F') }} @endif) için henüz tanımlı bir KPI hedefi bulunmamaktadır.
                            </p>
                            @if($kpiCockpit['canManageKpi'])
                                <a href="{{ route('kpi-targets.create') }}" class="inline-flex items-center gap-1 text-xs font-bold text-indigo-600 hover:text-indigo-800">
                                    <span>+ Yeni Hedef Tanımla</span>
                                </a>
                            @endif
                        </div>
                    </div>
                    @endif
                </div>
                @endif

                {{-- GRUP / DEPARTMAN HEDEFLERİ (Grup Yöneticisi & Yönetim) --}}
                @if(!empty($kpiCockpit['groupScorecard']) && !empty($kpiCockpit['groupScorecard']['groups']) && count($kpiCockpit['groupScorecard']['groups']) > 0)
                <div class="bg-white rounded-2xl border border-slate-200/90 shadow-sm p-6 space-y-4">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2.5">
                            <div class="p-2 bg-teal-50 rounded-xl text-teal-600 border border-teal-100">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                            </div>
                            <div>
                                <h3 class="text-base font-bold text-slate-900">Departman & Grup KPI Başarı Durumu</h3>
                                <p class="text-xs text-slate-500">Sorumlu olduğunuz grupların hedef sayısı ve ortalama başarı oranları.</p>
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 pt-2">
                        @foreach($kpiCockpit['groupScorecard']['groups'] as $grp)
                        <div class="p-4 rounded-xl border border-slate-200 bg-slate-50/60 hover:bg-white hover:border-teal-200 hover:shadow-sm transition-all space-y-3">
                            <div class="flex items-start justify-between">
                                <div>
                                    <h4 class="text-sm font-black text-slate-900">{{ $grp['name'] }}</h4>
                                    <div class="flex items-center gap-2 text-xs text-slate-500 mt-0.5">
                                        <span>👥 {{ $grp['user_count'] }} Üye</span>
                                        <span>&bull;</span>
                                        <span>🎯 {{ $grp['target_count'] }} Hedef</span>
                                    </div>
                                </div>
                                <span class="text-base font-black {{ $grp['average_rate'] >= 100 ? 'text-emerald-600' : ($grp['average_rate'] >= 70 ? 'text-indigo-600' : ($grp['target_count'] > 0 ? 'text-rose-600' : 'text-slate-400')) }}">
                                    %{{ $grp['average_rate'] }}
                                </span>
                            </div>

                            <div class="w-full bg-slate-200/80 rounded-full h-2 overflow-hidden">
                                <div class="h-2 rounded-full {{ $grp['average_rate'] >= 100 ? 'bg-emerald-500' : ($grp['average_rate'] >= 70 ? 'bg-indigo-600' : ($grp['target_count'] > 0 ? 'bg-rose-500' : 'bg-slate-300')) }}" style="width: min(100%, {{ $grp['average_rate'] }}%)"></div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
                @endif

            </div>
            @endif

            {{-- ─── 0.9 ONAY BEKLEYENLER DİKKAT BANNERI (YÖNETİCİ & APPROVER İÇİN EN ÜSTTE) ─── --}}
            @if(!empty($permissions['pending_approvals']) && isset($pendingApprovals) && $pendingApprovals->count() > 0)
            <div class="bg-gradient-to-r from-amber-500 via-amber-600 to-orange-600 rounded-2xl p-4 sm:p-5 text-white shadow-lg shadow-amber-500/20 flex flex-col sm:flex-row sm:items-center justify-between gap-4 border border-amber-300">
                <div class="flex items-center gap-3.5">
                    <div class="relative flex-shrink-0">
                        <div class="w-12 h-12 rounded-2xl bg-white/20 backdrop-blur-md flex items-center justify-center text-white font-black text-2xl shadow-inner">
                            🔔
                        </div>
                        <span class="absolute -top-1 -right-1 flex h-4 w-4">
                            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-white opacity-80"></span>
                            <span class="relative inline-flex rounded-full h-4 w-4 bg-rose-500 text-[10px] font-black text-white items-center justify-center">
                                {{ $pendingApprovals->count() }}
                            </span>
                        </span>
                    </div>
                    <div>
                        <div class="flex items-center gap-2">
                            <h4 class="text-base font-black tracking-tight">Onayınızı Bekleyen {{ $pendingApprovals->count() }} Adet Form Var!</h4>
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-extrabold uppercase bg-white/25 text-white border border-white/30">
                                Acil İnceleme
                            </span>
                        </div>
                        <p class="text-xs text-amber-100 mt-0.5">
                            Grup üyeleriniz veya sorumluluk alanınızdaki personeller onay bekleyen veri girişleri iletmiştir.
                        </p>
                    </div>
                </div>
                <div class="flex items-center gap-2 self-end sm:self-auto flex-shrink-0">
                    <a href="{{ route('form-entries.index', ['status' => 'submitted']) }}" class="inline-flex items-center gap-2 px-4 py-2 bg-white text-amber-700 hover:bg-amber-50 font-bold text-xs rounded-xl shadow-md transition-all hover:scale-105">
                        <span>Onay Bekleyenleri İncele</span>
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                    </a>
                </div>
            </div>
            @endif

            {{-- ─── 0.95 REVİZYON TALEPLERİ DİKKAT BANNERI (YÖNETİCİ & APPROVER İÇİN) ─── --}}
            @if(!empty($permissions['pending_approvals']) && isset($pendingRevisionRequests) && $pendingRevisionRequests->count() > 0)
            <div class="bg-gradient-to-r from-orange-500 via-orange-600 to-amber-600 rounded-2xl p-4 sm:p-5 text-white shadow-lg shadow-orange-500/20 flex flex-col sm:flex-row sm:items-center justify-between gap-4 border border-orange-300">
                <div class="flex items-center gap-3.5">
                    <div class="relative flex-shrink-0">
                        <div class="w-12 h-12 rounded-2xl bg-white/20 backdrop-blur-md flex items-center justify-center text-white font-black text-2xl shadow-inner">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                        </div>
                        <span class="absolute -top-1 -right-1 flex h-4 w-4">
                            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-white opacity-80"></span>
                            <span class="relative inline-flex rounded-full h-4 w-4 bg-rose-500 text-[10px] font-black text-white items-center justify-center">
                                {{ $pendingRevisionRequests->count() }}
                            </span>
                        </span>
                    </div>
                    <div>
                        <div class="flex items-center gap-2">
                            <h4 class="text-base font-black tracking-tight">Değerlendirilmeyi Bekleyen {{ $pendingRevisionRequests->count() }} Adet Revizyon Talebi Var!</h4>
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-extrabold uppercase bg-white/25 text-white border border-white/30">
                                Revizyon Talebi
                            </span>
                        </div>
                        <p class="text-xs text-orange-100 mt-0.5">
                            Onaylanmış formlar için personel tarafından iletilen düzenleme taleplerini inceleyip değerlendirebilirsiniz.
                        </p>
                    </div>
                </div>
                <div class="flex items-center gap-2 self-end sm:self-auto flex-shrink-0">
                    <a href="{{ route('form-entries.index', ['status' => 'revision_pending']) }}" class="inline-flex items-center gap-2 px-4 py-2 bg-white text-orange-700 hover:bg-orange-50 font-bold text-xs rounded-xl shadow-md transition-all hover:scale-105">
                        <span>Revizyon Taleplerini İncele</span>
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                    </a>
                </div>
            </div>
            @endif

            {{-- ─── 1. ÖZET KARTLARI (Tıklanabilir ve İlişkili Sayfalara Yönlendirir) ─── --}}
            @if($permissions['summary_cards'] && isset($summaryCards))
            <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
                {{-- Toplam Giriş --}}
                <a href="{{ route('form-entries.index') }}" class="group bg-white rounded-xl border border-gray-100 shadow-sm p-5 hover:shadow-md hover:border-indigo-200 transition-all block">
                    <div class="flex items-center justify-between mb-3">
                        <div class="p-2 bg-indigo-50 group-hover:bg-indigo-100 rounded-lg transition-colors"><svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg></div>
                        <span class="text-3xl font-black text-gray-900 group-hover:text-indigo-600 transition-colors">{{ $summaryCards['totalEntries'] }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Toplam Giriş</p>
                        <span class="text-[10px] text-indigo-500 font-bold opacity-0 group-hover:opacity-100 transition-opacity flex items-center">Gör &rarr;</span>
                    </div>
                </a>

                {{-- Onay Bekleyen --}}
                <a href="{{ route('form-entries.index', ['status' => 'submitted']) }}" class="group bg-white rounded-xl border {{ $summaryCards['pendingCount'] > 0 ? 'border-amber-300 ring-2 ring-amber-400/40 shadow-amber-100' : 'border-amber-100' }} shadow-sm p-5 hover:shadow-md hover:border-amber-400 transition-all block relative overflow-hidden">
                    @if($summaryCards['pendingCount'] > 0)
                        <span class="absolute top-2 right-2 flex h-2.5 w-2.5">
                            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-amber-400 opacity-75"></span>
                            <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-amber-500"></span>
                        </span>
                    @endif
                    <div class="flex items-center justify-between mb-3">
                        <div class="p-2 bg-amber-50 group-hover:bg-amber-100 rounded-lg transition-colors"><svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg></div>
                        <div class="flex items-baseline gap-1.5">
                            <span class="text-3xl font-black text-amber-600 {{ $summaryCards['pendingCount'] > 0 ? 'animate-pulse' : '' }}">{{ $summaryCards['pendingCount'] }}</span>
                            @if($summaryCards['pendingCount'] > 0)
                                <span class="text-[10px] font-black text-amber-600 bg-amber-50 px-1.5 py-0.5 rounded-full border border-amber-200 uppercase">Bekliyor</span>
                            @endif
                        </div>
                    </div>
                    <div class="flex items-center justify-between">
                        <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Onay Bekliyor</p>
                        <span class="text-[10px] text-amber-600 font-bold opacity-0 group-hover:opacity-100 transition-opacity flex items-center">İncele &rarr;</span>
                    </div>
                </a>

                {{-- Revizyon Talebi Bekleyen --}}
                <a href="{{ route('form-entries.index', ['status' => 'revision_pending']) }}" class="group bg-white rounded-xl border {{ ($summaryCards['revisionPendingCount'] ?? 0) > 0 ? 'border-orange-300 ring-2 ring-orange-400/40 shadow-orange-100' : 'border-orange-100' }} shadow-sm p-5 hover:shadow-md hover:border-orange-400 transition-all block relative overflow-hidden">
                    @if(($summaryCards['revisionPendingCount'] ?? 0) > 0)
                        <span class="absolute top-2 right-2 flex h-2.5 w-2.5">
                            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-orange-400 opacity-75"></span>
                            <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-orange-500"></span>
                        </span>
                    @endif
                    <div class="flex items-center justify-between mb-3">
                        <div class="p-2 bg-orange-50 group-hover:bg-orange-100 rounded-lg transition-colors">
                            <svg class="w-5 h-5 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                            </svg>
                        </div>
                        <div class="flex items-baseline gap-1.5">
                            <span class="text-3xl font-black text-orange-600 {{ ($summaryCards['revisionPendingCount'] ?? 0) > 0 ? 'animate-pulse' : '' }}">{{ $summaryCards['revisionPendingCount'] ?? 0 }}</span>
                            @if(($summaryCards['revisionPendingCount'] ?? 0) > 0)
                                <span class="text-[10px] font-black text-orange-600 bg-orange-50 px-1.5 py-0.5 rounded-full border border-orange-200 uppercase">Revizyon</span>
                            @endif
                        </div>
                    </div>
                    <div class="flex items-center justify-between">
                        <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Revizyon Bekliyor</p>
                        <span class="text-[10px] text-orange-600 font-bold opacity-0 group-hover:opacity-100 transition-opacity flex items-center">İncele &rarr;</span>
                    </div>
                </a>

                {{-- Onaylanan --}}
                <a href="{{ route('form-entries.index', ['status' => 'approved']) }}" class="group bg-white rounded-xl border border-emerald-100 shadow-sm p-5 hover:shadow-md hover:border-emerald-300 transition-all block">
                    <div class="flex items-center justify-between mb-3">
                        <div class="p-2 bg-emerald-50 group-hover:bg-emerald-100 rounded-lg transition-colors"><svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg></div>
                        <span class="text-3xl font-black text-emerald-600 group-hover:text-emerald-700 transition-colors">{{ $summaryCards['approvedCount'] }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Onaylandı</p>
                        <span class="text-[10px] text-emerald-600 font-bold opacity-0 group-hover:opacity-100 transition-opacity flex items-center">Gör &rarr;</span>
                    </div>
                </a>

                {{-- Taslak --}}
                <a href="{{ route('form-entries.index', ['status' => 'draft']) }}" class="group bg-white rounded-xl border border-gray-100 shadow-sm p-5 hover:shadow-md hover:border-gray-300 transition-all block">
                    <div class="flex items-center justify-between mb-3">
                        <div class="p-2 bg-gray-100 group-hover:bg-gray-200 rounded-lg transition-colors"><svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg></div>
                        <span class="text-3xl font-black text-gray-600 group-hover:text-gray-800 transition-colors">{{ $summaryCards['draftCount'] }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Taslak</p>
                        <span class="text-[10px] text-gray-500 font-bold opacity-0 group-hover:opacity-100 transition-opacity flex items-center">Gör &rarr;</span>
                    </div>
                </a>

                {{-- Reddedilen --}}
                <a href="{{ route('form-entries.index', ['status' => 'rejected']) }}" class="group bg-white rounded-xl border border-red-100 shadow-sm p-5 hover:shadow-md hover:border-red-300 transition-all block">
                    <div class="flex items-center justify-between mb-3">
                        <div class="p-2 bg-red-50 group-hover:bg-red-100 rounded-lg transition-colors"><svg class="w-5 h-5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg></div>
                        <span class="text-3xl font-black text-red-500 group-hover:text-red-700 transition-colors">{{ $summaryCards['rejectedCount'] }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Reddedildi</p>
                        <span class="text-[10px] text-red-500 font-bold opacity-0 group-hover:opacity-100 transition-opacity flex items-center">Gör &rarr;</span>
                    </div>
                </a>

                {{-- Bugünkü Girişler --}}
                <a href="{{ route('form-entries.index', ['filter_date' => 'today']) }}" class="group bg-white rounded-xl border border-purple-100 shadow-sm p-5 hover:shadow-md hover:border-purple-300 transition-all block">
                    <div class="flex items-center justify-between mb-3">
                        <div class="p-2 bg-purple-50 group-hover:bg-purple-100 rounded-lg transition-colors"><svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg></div>
                        <span class="text-3xl font-black text-purple-600 group-hover:text-purple-700 transition-colors">{{ $summaryCards['todayEntries'] }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Bugün</p>
                        <span class="text-[10px] text-purple-600 font-bold opacity-0 group-hover:opacity-100 transition-opacity flex items-center">Gör &rarr;</span>
                    </div>
                </a>

                {{-- Form Şablonu --}}
                <a href="{{ route('form-templates.index') }}" class="group bg-white rounded-xl border border-sky-100 shadow-sm p-5 hover:shadow-md hover:border-sky-300 transition-all block">
                    <div class="flex items-center justify-between mb-3">
                        <div class="p-2 bg-sky-50 group-hover:bg-sky-100 rounded-lg transition-colors"><svg class="w-5 h-5 text-sky-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zM16 13a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z"/></svg></div>
                        <span class="text-3xl font-black text-sky-600 group-hover:text-sky-700 transition-colors">{{ $summaryCards['templateCount'] }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Form Şablonu</p>
                        <span class="text-[10px] text-sky-600 font-bold opacity-0 group-hover:opacity-100 transition-opacity flex items-center">Yönet &rarr;</span>
                    </div>
                </a>

                {{-- Kullanıcı --}}
                <a href="{{ route('users.index') }}" class="group bg-white rounded-xl border border-teal-100 shadow-sm p-5 hover:shadow-md hover:border-teal-300 transition-all block">
                    <div class="flex items-center justify-between mb-3">
                        <div class="p-2 bg-teal-50 group-hover:bg-teal-100 rounded-lg transition-colors"><svg class="w-5 h-5 text-teal-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg></div>
                        <span class="text-3xl font-black text-teal-600 group-hover:text-teal-700 transition-colors">{{ $summaryCards['userCount'] }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Kullanıcı</p>
                        <span class="text-[10px] text-teal-600 font-bold opacity-0 group-hover:opacity-100 transition-opacity flex items-center">Rehber &rarr;</span>
                    </div>
                </a>
            </div>
            @endif

            {{-- ─── 2. GRAFİKLER SATIRI (Durum Pasta + Aylık Trend) ─── --}}
            @if($permissions['status_chart'] || $permissions['trend_chart'])
            <div class="grid grid-cols-1 lg:grid-cols-5 gap-6">
                @if($permissions['status_chart'] && isset($statusChart))
                <div class="lg:col-span-2 bg-white rounded-xl border border-gray-100 shadow-sm p-6">
                    <h3 class="text-sm font-bold text-gray-700 uppercase tracking-wider mb-4">Durum Dağılımı</h3>
                    <div class="flex justify-center"><canvas id="statusChart" class="max-h-[260px]"></canvas></div>
                </div>
                @endif

                @if($permissions['trend_chart'] && isset($trendChart))
                <div class="lg:col-span-3 bg-white rounded-xl border border-gray-100 shadow-sm p-6">
                    <h3 class="text-sm font-bold text-gray-700 uppercase tracking-wider mb-4">Aylık Giriş Trendi (Son 12 Ay)</h3>
                    <canvas id="trendChart" class="max-h-[260px]"></canvas>
                </div>
                @endif
            </div>
            @endif

            {{-- ─── 3. GRAFİKLER SATIRI (Form Dağılımı + Grup Karşılaştırma) ─── --}}
            @if($permissions['template_chart'] || $permissions['group_chart'])
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                @if($permissions['template_chart'] && isset($templateChart))
                <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-6">
                    <h3 class="text-sm font-bold text-gray-700 uppercase tracking-wider mb-4">Form Şablonu Bazlı Dağılım</h3>
                    <canvas id="templateChart" class="max-h-[280px]"></canvas>
                </div>
                @endif

                @if($permissions['group_chart'] && isset($groupChart))
                <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-6">
                    <h3 class="text-sm font-bold text-gray-700 uppercase tracking-wider mb-4">Grup Bazlı Karşılaştırma</h3>
                    <canvas id="groupChart" class="max-h-[280px]"></canvas>
                </div>
                @endif
            </div>
            @endif

            {{-- ─── 4. ALT BÖLÜM: Liderlik + Onay Bekleyenler + Son Aktivite + Kişisel ─── --}}
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

                {{-- Kişisel İstatistikler (Tıklanabilir İlgili Filtreli Rotalara Yönlendirir) --}}
                @if($permissions['my_stats'] && isset($myStats))
                <div class="bg-gradient-to-br from-indigo-600 to-purple-700 rounded-xl shadow-lg p-6 text-white">
                    <h3 class="text-sm font-bold uppercase tracking-wider mb-4 text-indigo-200">Kişisel İstatistiklerim</h3>
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-5">
                        <a href="{{ route('form-entries.index') }}" class="text-center group p-2 rounded-xl hover:bg-white/10 transition-colors block">
                            <p class="text-2xl sm:text-3xl font-black group-hover:scale-105 transition-transform">{{ $myStats['total'] }}</p>
                            <p class="text-xs text-indigo-200 mt-1">Toplam</p>
                        </a>
                        <a href="{{ route('form-entries.index', ['status' => 'approved']) }}" class="text-center group p-2 rounded-xl hover:bg-white/10 transition-colors block">
                            <p class="text-2xl sm:text-3xl font-black text-emerald-300 group-hover:scale-105 transition-transform">{{ $myStats['approved'] }}</p>
                            <p class="text-xs text-indigo-200 mt-1">Onaylı</p>
                        </a>
                        <a href="{{ route('form-entries.index', ['status' => 'submitted']) }}" class="text-center group p-2 rounded-xl hover:bg-white/10 transition-colors relative block">
                            @if($myStats['pending'] > 0)
                                <span class="absolute top-1 right-2 flex h-2 w-2">
                                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-amber-300 opacity-75"></span>
                                    <span class="relative inline-flex rounded-full h-2 w-2 bg-amber-400"></span>
                                </span>
                            @endif
                            <p class="text-2xl sm:text-3xl font-black text-amber-300 group-hover:scale-105 transition-transform {{ $myStats['pending'] > 0 ? 'animate-pulse' : '' }}">{{ $myStats['pending'] }}</p>
                            <p class="text-xs text-indigo-200 mt-1">Bekleyen</p>
                        </a>
                        <a href="{{ route('form-entries.index', ['status' => 'revision_pending']) }}" class="text-center group p-2 rounded-xl hover:bg-white/10 transition-colors relative block">
                            @if(($myStats['revisionPending'] ?? 0) > 0)
                                <span class="absolute top-1 right-2 flex h-2 w-2">
                                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-orange-300 opacity-75"></span>
                                    <span class="relative inline-flex rounded-full h-2 w-2 bg-orange-400"></span>
                                </span>
                            @endif
                            <p class="text-2xl sm:text-3xl font-black text-orange-300 group-hover:scale-105 transition-transform {{ ($myStats['revisionPending'] ?? 0) > 0 ? 'animate-pulse' : '' }}">{{ $myStats['revisionPending'] ?? 0 }}</p>
                            <p class="text-xs text-indigo-200 mt-1">Revizyon</p>
                        </a>
                    </div>
                    <div class="bg-white/10 rounded-lg p-3 backdrop-blur-sm">
                        <div class="flex justify-between items-center text-sm mb-2">
                            <span class="text-indigo-200">Onay Oranı</span>
                            <span class="font-black text-white">%{{ $myStats['rate'] }}</span>
                        </div>
                        <div class="w-full bg-white/20 rounded-full h-2.5">
                            <div class="bg-emerald-400 h-2.5 rounded-full transition-all duration-500" style="width: {{ $myStats['rate'] }}%"></div>
                        </div>
                    </div>
                    @if($myStats['lastEntry'])
                    <p class="text-xs text-indigo-300 mt-3">Son giriş: {{ $myStats['lastEntry']->entry_date->format('d.m.Y') }}</p>
                    @endif
                </div>
                @endif

                {{-- Liderlik Tablosu --}}
                @if($permissions['leaderboard'] && isset($leaderboard))
                <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-6">
                    <h3 class="text-sm font-bold text-gray-700 uppercase tracking-wider mb-4">En Aktif Kullanıcılar</h3>
                    <div class="space-y-2">
                        @foreach($leaderboard as $i => $row)
                        <div class="flex items-center justify-between py-2 px-3 rounded-lg {{ $i < 3 ? 'bg-indigo-50/50' : 'hover:bg-gray-50' }} transition-colors">
                            <div class="flex items-center gap-3">
                                <span class="w-7 h-7 flex items-center justify-center rounded-full text-xs font-black {{ $i === 0 ? 'bg-amber-400 text-white' : ($i === 1 ? 'bg-gray-300 text-gray-700' : ($i === 2 ? 'bg-amber-700 text-white' : 'bg-gray-100 text-gray-500')) }}">
                                    {{ $i + 1 }}
                                </span>
                                <span class="text-sm font-semibold text-gray-900">{{ $row['name'] }}</span>
                            </div>
                            <div class="flex items-center gap-3 text-xs">
                                <span class="text-gray-500">{{ $row['total'] }} giriş</span>
                                <span class="text-emerald-600 font-bold bg-emerald-50 px-2 py-0.5 rounded-full">{{ $row['approved'] }} onaylı</span>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
                @endif
            </div>

            {{-- ─── 4. ALT BÖLÜM: Onay Bekleyenler (Varsa) ─── --}}
            @if($permissions['pending_approvals'] && isset($pendingApprovals) && $pendingApprovals->count() > 0)
            <div class="mb-6">
                <div class="bg-white rounded-2xl border border-amber-200/80 shadow-xs p-6">
                    <h3 class="text-sm font-extrabold text-amber-800 uppercase tracking-wider mb-4 flex items-center justify-between">
                        <span class="flex items-center">
                            <span class="w-2.5 h-2.5 bg-amber-500 rounded-full mr-2.5 animate-pulse"></span>
                            Onay Bekleyen Formlar
                        </span>
                        <span class="text-xs bg-amber-100 text-amber-800 font-bold px-2.5 py-0.5 rounded-full">{{ $pendingApprovals->count() }} Form Bekliyor</span>
                    </h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-3">
                        @foreach($pendingApprovals as $entry)
                        <a href="{{ route('form-entries.show', $entry) }}" class="flex items-center justify-between py-2.5 px-3.5 rounded-xl hover:bg-amber-50 transition-colors border border-amber-100/70 hover:border-amber-300 group bg-amber-50/30">
                            <div class="flex items-center gap-3 min-w-0">
                                <div class="flex-shrink-0 w-8 h-8 bg-gradient-to-br from-amber-100 to-amber-200 rounded-full flex items-center justify-center text-amber-800 font-bold text-xs border border-amber-300">
                                    {{ mb_substr($entry->user->name, 0, 1) }}
                                </div>
                                <div class="min-w-0 flex-1">
                                    <p class="text-sm font-bold text-gray-900 group-hover:text-amber-700 transition-colors truncate">{{ $entry->user->name }}</p>
                                    <p class="text-xs text-gray-500 truncate">{{ $entry->template->name }}</p>
                                </div>
                            </div>
                            <div class="text-right shrink-0 ml-2">
                                <p class="text-xs font-semibold text-gray-700">{{ $entry->entry_date->format('d.m.Y') }}</p>
                                <p class="text-[10px] text-gray-400">{{ $entry->created_at->diffForHumans() }}</p>
                            </div>
                        </a>
                        @endforeach
                    </div>
                </div>
            </div>
            @endif

            {{-- ─── 4.2 SON AKTİVİTELER (Yatayda Yayılmış Tam Genişlik) ─── --}}
            @if($permissions['recent_activity'] && isset($recentActivity) && $recentActivity->count() > 0)
            <div x-data="{ showDashLogModal: false, activeDashLog: { date: '', user: '', action: '', badgeClass: '', description: '' } }"
                class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-6 mb-6 w-full">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 mb-4 border-b border-slate-100 pb-3">
                    <h3 class="text-sm font-extrabold text-slate-800 uppercase tracking-wider flex items-center gap-2">
                        <span class="w-7 h-7 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                        </span>
                        <span>Son Aktiviteler & Sistem Günlüğü</span>
                    </h3>
                    <div class="flex items-center gap-2">
                        <span class="text-xs font-bold text-slate-400 bg-slate-50 border border-slate-100 px-2.5 py-1 rounded-lg">
                            Son {{ $recentActivity->count() }} İşlem
                        </span>
                        @if(auth()->user()->can('settings.manage') || auth()->user()->can('logs.view'))
                        <a href="{{ route('settings.activity_logs.index') }}" 
                           class="inline-flex items-center gap-1 px-3 py-1 rounded-lg text-xs font-bold text-indigo-600 hover:text-indigo-800 bg-indigo-50 hover:bg-indigo-100 border border-indigo-200 transition-colors shadow-2xs">
                            <span>Tüm Geçmişi Gör (Sistem Günlüğü)</span>
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                        </a>
                        @endif
                    </div>
                </div>
                
                <div class="space-y-2 max-h-[420px] overflow-y-auto divide-y divide-slate-100/70">
                    @foreach($recentActivity as $log)
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 py-3 px-3 rounded-xl hover:bg-slate-50/80 transition-colors group">
                        <div class="flex items-start sm:items-center gap-3 min-w-0 flex-1">
                            <div class="flex-shrink-0 w-8 h-8 bg-gradient-to-br from-indigo-50 to-purple-50 text-indigo-700 rounded-xl flex items-center justify-center font-black text-xs border border-indigo-100/80 shadow-2xs mt-0.5 sm:mt-0">
                                {{ $log->user ? mb_substr($log->user->name, 0, 1) : 'S' }}
                            </div>
                            <div class="min-w-0 flex-1">
                                <div class="flex items-center gap-2 flex-wrap">
                                    <span class="text-xs font-bold text-slate-900">{{ $log->user->name ?? 'Sistem' }}</span>
                                    <span class="text-[10px] px-2 py-0.5 rounded-full font-bold border {{ $log->action_badge_class }}">
                                        {{ $log->action_label }}
                                    </span>
                                </div>
                                <div class="text-xs text-slate-600 mt-1 flex items-center gap-2 flex-wrap">
                                    @php
                                        $dDesc = (string) $log->description;
                                        $isLong = strlen($dDesc) > 140 || str_contains($dDesc, "\n");
                                        $dSummary = $dDesc;
                                        if (str_contains($dDesc, "\n")) {
                                            $dSummary = trim(explode("\n", $dDesc)[0]);
                                        }
                                    @endphp
                                    <span class="line-clamp-1 text-slate-700 leading-snug">{{ $dSummary }}</span>
                                    @if($isLong)
                                        <button type="button"
                                            @click="activeDashLog = {
                                                date: '{{ $log->created_at->format('d.m.Y H:i:s') }}',
                                                user: '{{ $log->user->name ?? 'Sistem' }}',
                                                action: '{{ $log->action_label }}',
                                                badgeClass: '{{ $log->action_badge_class }}',
                                                description: @js($log->description)
                                            }; showDashLogModal = true;"
                                            class="text-[11px] font-bold text-indigo-600 hover:text-indigo-800 hover:underline inline-flex items-center gap-0.5 cursor-pointer shrink-0">
                                            <span>Detay</span>
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                                        </button>
                                    @endif
                                </div>
                            </div>
                        </div>
                        <div class="flex items-center gap-2 shrink-0 pl-11 sm:pl-0 sm:self-center">
                            <span class="text-[11px] font-medium text-slate-400 whitespace-nowrap" title="{{ $log->created_at->format('d.m.Y H:i:s') }}">
                                {{ $log->created_at->diffForHumans() }}
                            </span>
                        </div>
                    </div>
                    @endforeach
                </div>

                @if(auth()->user()->can('settings.manage') || auth()->user()->can('logs.view'))
                <div class="pt-3 border-t border-slate-100 mt-2 text-center">
                    <a href="{{ route('settings.activity_logs.index') }}" class="text-xs font-bold text-indigo-600 hover:text-indigo-800 hover:underline inline-flex items-center gap-1">
                        <span>Tüm Sistem Günlüğü & Denetim İzi Kayıtlarını İncele (Sayfalama ve Filtreler ile)</span>
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                    </a>
                </div>
                @endif

                <!-- Dashboard Log Detay Modalı -->
                <div x-show="showDashLogModal" x-cloak
                    class="fixed inset-0 z-50 overflow-y-auto"
                    aria-labelledby="dash-log-modal-title" role="dialog" aria-modal="true"
                    style="display: none;">
                    <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                        <div x-show="showDashLogModal"
                            x-transition:enter="ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                            x-transition:leave="ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
                            class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs transition-opacity"
                            @click="showDashLogModal = false"></div>

                        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

                        <div x-show="showDashLogModal"
                            x-transition:enter="ease-out duration-200" x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                            x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                            x-transition:leave="ease-in duration-150" x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                            x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                            class="inline-block align-bottom bg-white rounded-3xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full border border-slate-100">

                            <div class="bg-gradient-to-r from-slate-50 via-indigo-50/30 to-white px-6 py-4 border-b border-slate-100 flex items-center justify-between">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-2xl bg-indigo-50 text-indigo-600 flex items-center justify-center shadow-2xs">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                                        </svg>
                                    </div>
                                    <div>
                                        <h3 class="text-base font-extrabold text-slate-800" id="dash-log-modal-title">Aktivite Detayı</h3>
                                        <p class="text-xs text-slate-500 mt-0.5">
                                            <span x-text="activeDashLog.user" class="font-bold text-slate-700"></span> &bull; 
                                            <span x-text="activeDashLog.date"></span>
                                        </p>
                                    </div>
                                </div>
                                <div class="flex items-center gap-2">
                                    <span :class="activeDashLog.badgeClass" class="px-2.5 py-1 text-xs font-bold rounded-full border" x-text="activeDashLog.action"></span>
                                    <button type="button" @click="showDashLogModal = false" class="p-2 text-slate-400 hover:text-slate-600 hover:bg-slate-100 rounded-xl transition-colors">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                    </button>
                                </div>
                            </div>

                            <div class="p-6 max-h-[60vh] overflow-y-auto space-y-4 bg-slate-50/50">
                                <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-2xs">
                                    <div class="text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-2">İşlem Kayıt İçeriği</div>
                                    <div class="text-xs sm:text-sm font-medium text-slate-800 whitespace-pre-wrap leading-relaxed font-mono bg-slate-50 p-3 rounded-xl border border-slate-100 overflow-x-auto select-text" x-text="activeDashLog.description"></div>
                                </div>
                            </div>

                            <div class="bg-white px-6 py-4 border-t border-slate-100 flex justify-between items-center">
                                <button type="button"
                                    @click="navigator.clipboard.writeText(activeDashLog.description); alert('Aktivite detayı panoya kopyalandı.');"
                                    class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl text-xs font-bold text-slate-700 hover:text-indigo-600 hover:bg-indigo-50 border border-slate-200 transition-colors">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3"/></svg>
                                    <span>Detayı Kopyala</span>
                                </button>
                                <button type="button" @click="showDashLogModal = false"
                                    class="px-5 py-2 bg-slate-800 hover:bg-slate-900 text-white rounded-xl text-xs font-bold transition-all shadow-xs">
                                    Kapat
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            @endif

            {{-- ─── 5. GRUP BİLGİLERİ ─── --}}
            @if($permissions['view_group_users'] || $permissions['view_group_manager'])
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                @if($permissions['view_group_manager'] && isset($groupManagers))
                <div class="bg-white rounded-xl border border-blue-100 shadow-sm p-6">
                    <h3 class="text-sm font-bold text-blue-700 uppercase tracking-wider mb-4 flex items-center">
                        <svg class="w-4 h-4 mr-2 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                        Grup Yöneticileri
                    </h3>
                    <div class="space-y-3">
                        @forelse($groupManagers as $manager)
                        <div class="flex items-center gap-3 py-2 px-3 rounded-lg bg-blue-50/50">
                            <div class="flex-shrink-0 w-8 h-8 bg-blue-200 rounded-full flex items-center justify-center text-blue-800 font-bold text-xs border border-blue-300">
                                {{ mb_substr($manager->name, 0, 1) }}
                            </div>
                            <div>
                                <p class="text-sm font-bold text-gray-900">{{ $manager->name }}</p>
                                <p class="text-[10px] text-gray-500 uppercase tracking-wider">{{ $manager->email }}</p>
                            </div>
                        </div>
                        @empty
                        <p class="text-sm text-gray-500 py-2">Grubunuza atanmış bir yönetici bulunmuyor.</p>
                        @endforelse
                    </div>
                </div>
                @endif

                @if($permissions['view_group_users'] && isset($groupUsers))
                <div class="bg-white rounded-xl border border-teal-100 shadow-sm p-6">
                    <h3 class="text-sm font-bold text-teal-700 uppercase tracking-wider mb-4 flex items-center">
                        <svg class="w-4 h-4 mr-2 text-teal-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        Ekip Arkadaşlarım ({{ $groupUsers->count() }})
                    </h3>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 max-h-[250px] overflow-y-auto pr-1">
                        @foreach($groupUsers as $u)
                        <div class="flex items-center gap-3 py-2 px-3 rounded-lg border border-gray-100 {{ $u->id === Auth::id() ? 'bg-teal-50 border-teal-200' : 'hover:bg-gray-50' }}">
                            <div class="flex-shrink-0 w-7 h-7 {{ $u->id === Auth::id() ? 'bg-teal-500 text-white' : 'bg-gray-200 text-gray-600' }} rounded-full flex items-center justify-center font-bold text-[10px]">
                                {{ mb_substr($u->name, 0, 1) }}
                            </div>
                            <div class="truncate">
                                <p class="text-xs font-bold {{ $u->id === Auth::id() ? 'text-teal-900' : 'text-gray-900' }} truncate" title="{{ $u->name }}">{{ $u->name }} {!! $u->id === Auth::id() ? '<span class="text-[9px] text-teal-600 font-normal">(Sen)</span>' : '' !!}</p>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
                @endif
            </div>
            @endif

            @if(empty(array_filter($permissions)))
            <div class="bg-white rounded-2xl border border-slate-200/90 shadow-sm p-8 text-center max-w-xl mx-auto my-12">
                <div class="w-14 h-14 bg-indigo-50 text-indigo-600 rounded-2xl flex items-center justify-center mx-auto mb-4 border border-indigo-100 shadow-2xs">
                    <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
                <h3 class="text-base font-bold text-slate-800">Görüntülenecek Modül Bulunmuyor</h3>
                <p class="text-xs text-slate-500 mt-2 leading-relaxed">
                    Mevcut rolünüz ({{ Auth::user()->role ? Auth::user()->role->name : 'Rol Tanımsız' }}) için Dashboard üzerinde aktif bir gösterge paneli yetkilendirilmemiş. Yetki Matrisi üzerinden yöneticiniz ile iletişime geçebilir veya üst menüdeki modülleri kullanabilirsiniz.
                </p>
                <div class="mt-5 flex justify-center gap-3">
                    <a href="{{ route('form-entries.index') }}" class="inline-flex items-center gap-1.5 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold rounded-xl shadow-sm transition">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        <span>Veri Girişlerine Git</span>
                    </a>
                </div>
            </div>
            @endif

        </div>
    </div>

    {{-- ─── Chart.js CDN ─── --}}
    @if($permissions['status_chart'] || $permissions['trend_chart'] || $permissions['template_chart'] || $permissions['group_chart'])
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4"></script>
    <script>
        Chart.defaults.font.family = "'Figtree', sans-serif";
        Chart.defaults.plugins.legend.labels.usePointStyle = true;
        Chart.defaults.plugins.legend.labels.padding = 16;

        @if($permissions['status_chart'] && isset($statusChart))
        const statusChartEl = document.getElementById('statusChart');
        if (statusChartEl) {
            new Chart(statusChartEl, {
                type: 'doughnut',
                data: {
                    labels: @json($statusChart['labels']),
                    datasets: [{
                        data: @json($statusChart['data']),
                        backgroundColor: @json($statusChart['colors']),
                        borderWidth: 0,
                        hoverOffset: 8
                    }]
                },
                options: {
                    responsive: true,
                    cutout: '65%',
                    plugins: {
                        legend: { position: 'bottom', labels: { font: { size: 11 } } }
                    }
                }
            });
        }
        @endif

        @if($permissions['trend_chart'] && isset($trendChart))
        const trendChartEl = document.getElementById('trendChart');
        if (trendChartEl) {
            new Chart(trendChartEl, {
                type: 'line',
                data: {
                    labels: @json($trendChart['labels']),
                    datasets: [
                        {
                            label: 'Toplam Giriş',
                            data: @json($trendChart['total']),
                            borderColor: '#6366f1',
                            backgroundColor: 'rgba(99, 102, 241, 0.08)',
                            tension: 0.4,
                            fill: true,
                            pointRadius: 4,
                            pointHoverRadius: 6,
                            borderWidth: 2.5
                        },
                        {
                            label: 'Onaylanan',
                            data: @json($trendChart['approved']),
                            borderColor: '#10b981',
                            backgroundColor: 'rgba(16, 185, 129, 0.08)',
                            tension: 0.4,
                            fill: true,
                            pointRadius: 4,
                            pointHoverRadius: 6,
                            borderWidth: 2.5
                        }
                    ]
                },
                options: {
                    responsive: true,
                    interaction: { mode: 'index', intersect: false },
                    plugins: { legend: { position: 'top', labels: { font: { size: 11 } } } },
                    scales: {
                        y: { beginAtZero: true, ticks: { precision: 0 } },
                        x: { ticks: { font: { size: 10 } } }
                    }
                }
            });
        }
        @endif

        @if($permissions['template_chart'] && isset($templateChart))
        const templateChartEl = document.getElementById('templateChart');
        if (templateChartEl) {
            new Chart(templateChartEl, {
                type: 'bar',
                data: {
                    labels: @json($templateChart['labels']),
                    datasets: [{
                        label: 'Giriş Sayısı',
                        data: @json($templateChart['data']),
                        backgroundColor: ['#6366f1','#8b5cf6','#a78bfa','#c4b5fd','#818cf8','#6366f1','#7c3aed','#4f46e5','#4338ca','#3730a3'],
                        borderRadius: 6,
                        borderSkipped: false,
                        barThickness: 32
                    }]
                },
                options: {
                    responsive: true,
                    indexAxis: 'y',
                    plugins: { legend: { display: false } },
                    scales: {
                        x: { beginAtZero: true, ticks: { precision: 0 } },
                        y: { ticks: { font: { size: 11 } } }
                    }
                }
            });
        }
        @endif

        @if($permissions['group_chart'] && isset($groupChart))
        const groupChartEl = document.getElementById('groupChart');
        if (groupChartEl) {
            new Chart(groupChartEl, {
                type: 'bar',
                data: {
                    labels: @json($groupChart['labels']),
                    datasets: [{
                        label: 'Giriş Sayısı',
                        data: @json($groupChart['data']),
                        backgroundColor: ['#14b8a6','#06b6d4','#0ea5e9','#6366f1','#8b5cf6','#a855f7','#d946ef'],
                        borderRadius: 6,
                        borderSkipped: false,
                        barThickness: 32
                    }]
                },
                options: {
                    responsive: true,
                    plugins: { legend: { display: false } },
                    scales: {
                        x: { beginAtZero: true, ticks: { precision: 0 } },
                        y: { ticks: { font: { size: 11 } } }
                    }
                }
            });
        }
        @endif
                scales: {
                    y: { beginAtZero: true, ticks: { precision: 0 } },
                    x: { ticks: { font: { size: 11 } } }
                }
            }
        });
        @endif
    </script>
    @endif
</x-app-layout>
