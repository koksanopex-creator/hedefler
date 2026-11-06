@section('title', 'Hedeflerim ve Performans Karnem')
<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            <div>
                <div class="flex items-center gap-2.5">
                    <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-indigo-600 to-purple-600 text-white flex items-center justify-center shadow-md shadow-indigo-100">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z">
                            </path>
                        </svg>
                    </div>
                    <div>
                        <h2 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight">
                            Hedeflerim & Performans Karnem
                        </h2>
                        <p class="text-xs text-slate-500 font-medium mt-0.5">
                            {{ $user->name }} &bull; {{ $user->group->name ?? 'Bağımsız Personel' }} &bull; {{ $selectedYear }} Yılı KPI Göstergeleri
                        </p>
                    </div>
                </div>
            </div>

            {{-- Yıl Seçici & Arşiv Butonları --}}
            <div class="flex items-center gap-1.5 bg-slate-100/80 p-1.5 rounded-xl border border-slate-200 shadow-2xs">
                <span class="text-xs font-bold text-slate-500 px-2 flex items-center gap-1">
                    <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                    Yıl:
                </span>
                @foreach($availableYears as $year)
                    <a href="{{ route('my-targets.index', ['year' => $year]) }}"
                        class="px-3 py-1.5 rounded-lg text-xs font-bold transition-all {{ $selectedYear == $year ? 'bg-indigo-600 text-white shadow-xs' : 'text-slate-600 hover:text-indigo-600 hover:bg-white' }}">
                        {{ $year }}
                        @if($year == date('Y'))
                            <span class="text-[9px] font-normal opacity-80">(Mevcut)</span>
                        @endif
                    </a>
                @endforeach
            </div>
        </div>
    </x-slot>

    <div class="py-6 sm:py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            @if(session('success'))
                <div class="p-4 text-sm text-emerald-800 bg-emerald-50 border border-emerald-200 rounded-2xl flex items-center shadow-xs" role="alert">
                    <svg class="w-5 h-5 mr-2 text-emerald-500 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg>
                    <span>{{ session('success') }}</span>
                </div>
            @endif

            @if(session('warning'))
                <div class="p-4 text-sm text-amber-800 bg-amber-50 border border-amber-200 rounded-2xl flex items-center shadow-xs" role="alert">
                    <svg class="w-5 h-5 mr-2 text-amber-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                    <span>{{ session('warning') }}</span>
                </div>
            @endif

            {{-- 1. GENEL PERFORMANS SKOR KARTI --}}
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                {{-- Toplam Hedef --}}
                <div class="bg-white p-4 sm:p-5 rounded-2xl border border-slate-200/80 shadow-xs flex flex-col justify-between">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Tanımlı Hedef</span>
                        <span class="w-7 h-7 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path></svg>
                        </span>
                    </div>
                    <div class="mt-3">
                        <p class="text-2xl sm:text-3xl font-black text-slate-900">{{ $performanceScore['total_targets'] }}</p>
                        <span class="text-[11px] text-slate-500 font-medium">{{ $selectedYear }} Yılı KPI Sayısı</span>
                    </div>
                </div>

                {{-- Ortalama Başarı Oranı --}}
                <div class="bg-white p-4 sm:p-5 rounded-2xl border border-slate-200/80 shadow-xs flex flex-col justify-between">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Ortalama Başarı</span>
                        @php
                            $rate = $performanceScore['average_rate'];
                            if ($rate >= 100) {
                                $rateBadge = 'bg-emerald-50 text-emerald-700 border-emerald-200';
                                $rateLabel = 'Hedef Aşıldı';
                            } elseif ($rate >= 70) {
                                $rateBadge = 'bg-amber-50 text-amber-700 border-amber-200';
                                $rateLabel = 'İyi Düzeyde';
                            } else {
                                $rateBadge = 'bg-rose-50 text-rose-700 border-rose-200';
                                $rateLabel = 'Geliştirilmeli';
                            }
                        @endphp
                        <span class="text-[10px] font-extrabold px-2 py-0.5 rounded-full border {{ $rateBadge }}">
                            {{ $rateLabel }}
                        </span>
                    </div>
                    <div class="mt-3">
                        <p class="text-2xl sm:text-3xl font-black text-slate-900">%{{ number_format($performanceScore['average_rate'], 1, ',', '.') }}</p>
                        <span class="text-[11px] text-slate-500 font-medium">Kümülatif Gerçekleşme</span>
                    </div>
                </div>

                {{-- Başarılı Hedefler --}}
                <div class="bg-white p-4 sm:p-5 rounded-2xl border border-slate-200/80 shadow-xs flex flex-col justify-between">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold text-emerald-600 uppercase tracking-wider">Hedefe Ulaşan</span>
                        <span class="w-7 h-7 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"></path></svg>
                        </span>
                    </div>
                    <div class="mt-3">
                        <p class="text-2xl sm:text-3xl font-black text-emerald-700">{{ $performanceScore['achieved_count'] }}</p>
                        <span class="text-[11px] text-emerald-600/80 font-medium">%100 ve üzeri gerçekleşme</span>
                    </div>
                </div>

                {{-- Devam Eden / Kritik --}}
                <div class="bg-white p-4 sm:p-5 rounded-2xl border border-slate-200/80 shadow-xs flex flex-col justify-between">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold text-amber-600 uppercase tracking-wider">Devam Eden / Kritik</span>
                        <span class="w-7 h-7 rounded-lg bg-amber-50 text-amber-600 flex items-center justify-center">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        </span>
                    </div>
                    <div class="mt-3">
                        <p class="text-2xl sm:text-3xl font-black text-amber-700">
                            {{ $performanceScore['in_progress_count'] + $performanceScore['at_risk_count'] }}
                        </p>
                        <span class="text-[11px] text-amber-600/80 font-medium">Hedefe yaklaşan hedefler</span>
                    </div>
                </div>
            </div>

            {{-- 2. ETKİLEŞİMLİ COĞRAFİ HARİTA & RADAR ANALİZİ --}}
            @if(isset($geoData) && isset($radarData))
                @include('kpi_targets.partials.geo_map_and_radar', ['geoData' => $geoData, 'radarData' => $radarData])
            @endif

            {{-- 3. HEDEFLER LİSTESİ VEYA BOŞ DURUM --}}
            @if($targets->count() === 0)
                {{-- BOŞ DURUM (HEDEF ATANMADIYSA) --}}
                <div class="bg-white rounded-3xl border border-slate-200/80 shadow-xs p-10 sm:p-14 text-center">
                    <div class="w-16 h-16 rounded-2xl bg-indigo-50 text-indigo-600 flex items-center justify-center mx-auto shadow-inner">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                                d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
                            </path>
                        </svg>
                    </div>

                    <h3 class="text-lg sm:text-xl font-black text-slate-900 mt-4 tracking-tight">
                        {{ $selectedYear }} Yılı İçin Hedef Ataması Yapılmadı
                    </h3>

                    <p class="text-sm text-slate-500 max-w-lg mx-auto mt-2 leading-relaxed">
                        {{ $selectedYear }} yılı için adınıza veya bağlı olduğunuz departmana henüz tanımlanmış aktif bir KPI hedefi bulunmamaktadır.
                        Yöneticiniz veya sistem yöneticisi tarafından adınıza hedef tanımlandığında hedeflerinizi, gerçekleşme yüzdelerini ve 12 aylık periyot dağılımlarını bu sayfadan anlık olarak takip edebilirsiniz.
                    </p>

                    @if(count($availableYears) > 1)
                        <div class="mt-6 pt-6 border-t border-slate-100 flex flex-wrap items-center justify-center gap-2">
                            <span class="text-xs text-slate-400 font-medium">Geçmiş hedeflerinizi ve arşivinizi incelemek için:</span>
                            @foreach($availableYears as $year)
                                @if($year != $selectedYear)
                                    <a href="{{ route('my-targets.index', ['year' => $year]) }}"
                                        class="inline-flex items-center gap-1 text-xs font-bold text-indigo-600 hover:text-indigo-800 bg-indigo-50 hover:bg-indigo-100 px-3 py-1 rounded-lg transition">
                                        <span>{{ $year }} Hedef Arşivi</span>
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                                    </a>
                                @endif
                            @endforeach
                        </div>
                    @endif
                </div>
            @else
                {{-- AKTİF / GEÇMİŞ HEDEFLER LİSTESİ --}}
                <div class="space-y-6">
                    <div class="flex items-center justify-between">
                        <h3 class="text-base font-extrabold text-slate-900 flex items-center gap-2">
                            <span class="w-2.5 h-2.5 rounded-full bg-indigo-600"></span>
                            {{ $selectedYear }} Yılı Hedef Kartları ve Aylık Gerçekleşmeler
                        </h3>
                        <span class="text-xs text-slate-400 font-medium">Toplam {{ $targets->count() }} hedef listeleniyor</span>
                    </div>

                    <div class="grid grid-cols-1 gap-6">
                        @foreach($targets as $target)
                            @php
                                $annualReal = $target->realizations->where('realization_month', 0)->first();
                                $actualValue = $annualReal ? floatval($annualReal->actual_value) : 0;
                                $achieveRate = $annualReal ? floatval($annualReal->achievement_rate) : 0;

                                if ($achieveRate >= 100) {
                                    $progressBg = 'bg-gradient-to-r from-emerald-500 to-teal-500';
                                    $statusBadge = 'bg-emerald-50 text-emerald-700 border-emerald-200';
                                    $statusText = 'Hedef Aşıldı';
                                } elseif ($achieveRate >= 70) {
                                    $progressBg = 'bg-gradient-to-r from-amber-500 to-indigo-500';
                                    $statusBadge = 'bg-amber-50 text-amber-700 border-amber-200';
                                    $statusText = 'Yolunda';
                                } else {
                                    $progressBg = 'bg-gradient-to-r from-rose-500 to-amber-500';
                                    $statusBadge = 'bg-rose-50 text-rose-700 border-rose-200';
                                    $statusText = 'Kritik';
                                }
                            @endphp

                            <div class="bg-white rounded-3xl border border-slate-200/80 shadow-xs hover:shadow-md transition-all p-6 sm:p-7 space-y-5"
                                x-data="{ showMonthlyDetails: false }">
                                
                                {{-- Üst Başlık & Rozetler --}}
                                <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3">
                                    <div class="space-y-1">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <span class="px-2.5 py-0.5 rounded-md text-[10px] font-extrabold uppercase tracking-wide bg-indigo-50 text-indigo-700 border border-indigo-100">
                                                {{ $target->template->name }}
                                            </span>
                                            <span class="px-2.5 py-0.5 rounded-md text-[10px] font-extrabold uppercase tracking-wide bg-purple-50 text-purple-700 border border-purple-100">
                                                Hedef Alanı: {{ $target->targetField->label }}
                                            </span>
                                            <span class="px-2.5 py-0.5 rounded-md text-[10px] font-extrabold uppercase tracking-wide bg-slate-100 text-slate-700">
                                                {{ $target->assignee_type === 'user' ? 'Bireysel Personel Hedefi' : ($target->assignee_type === 'group' ? 'Grup Hedefi' : 'Yönetici Hedefi') }}
                                            </span>
                                        </div>
                                        <h4 class="text-base sm:text-lg font-black text-slate-900 tracking-tight">
                                            {{ $target->title }}
                                        </h4>
                                    </div>

                                    <div class="flex items-center gap-3 shrink-0">
                                        <div class="text-right">
                                            <span class="text-xs font-black px-2.5 py-1 rounded-lg border {{ $statusBadge }}">
                                                %{{ number_format($achieveRate, 1, ',', '.') }}
                                            </span>
                                            <p class="text-[10px] font-bold text-slate-400 mt-0.5">{{ $statusText }}</p>
                                        </div>
                                    </div>
                                </div>

                                {{-- Kriterler / Kırılımlar Rozetleri --}}
                                @php $badges = app(\App\Services\KpiTargetS::class)->getFormattedCriteriaBadges($target->criteria_json); @endphp
                                @if(!empty($badges))
                                    <div class="p-3 bg-slate-50/80 rounded-2xl border border-slate-200/70 space-y-2">
                                        <div class="flex items-center justify-between text-[11px] font-bold text-slate-500">
                                            <span class="flex items-center gap-1.5 text-indigo-700">
                                                <span>🔍</span> Geçerli Kriter Kırılımları (Master Data)
                                            </span>
                                            <span class="text-[10px] text-slate-400 font-mono">{{ count($badges) }} Kriter</span>
                                        </div>
                                        <div class="flex flex-wrap items-center gap-1.5">
                                            @foreach($badges as $badge)
                                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-bold border {{ $badge['bg_class'] }} shadow-2xs">
                                                    <span>{{ $badge['icon'] }}</span>
                                                    <span class="opacity-75 font-semibold">{{ $badge['label'] }}:</span>
                                                    <strong class="font-black">{{ $badge['value'] }}</strong>
                                                </span>
                                            @endforeach
                                        </div>
                                    </div>
                                @endif

                                {{-- Rakamlar & İlerleme Çubuğu --}}
                                <div class="space-y-2">
                                    <div class="flex justify-between items-baseline">
                                        <div class="text-xs text-slate-500 font-medium">
                                            Gerçekleşen:
                                            <span class="text-slate-900 text-base sm:text-lg font-black ml-1">
                                                {{ number_format($actualValue, 2, ',', '.') }} {{ $target->unit }}
                                            </span>
                                        </div>
                                        <div class="text-xs text-slate-500 font-medium">
                                            Hedef:
                                            <span class="text-slate-900 text-base sm:text-lg font-black ml-1">
                                                {{ number_format($target->target_value, 2, ',', '.') }} {{ $target->unit }}
                                            </span>
                                        </div>
                                    </div>

                                    <div class="w-full bg-slate-100 rounded-full h-3 overflow-hidden shadow-inner">
                                        <div class="{{ $progressBg }} h-3 rounded-full transition-all duration-700 ease-out"
                                            style="width: {{ min(100, $achieveRate) }}%">
                                        </div>
                                    </div>
                                </div>

                                {{-- Alt Aksiyonlar & 12 Aylık Dağılım Genişletme --}}
                                <div class="pt-3 border-t border-slate-100 flex flex-wrap items-center justify-between gap-3">
                                    <button type="button" @click="showMonthlyDetails = !showMonthlyDetails"
                                        class="inline-flex items-center gap-1.5 text-xs font-bold text-indigo-600 hover:text-indigo-800 transition">
                                        <span x-text="showMonthlyDetails ? 'Aylık Dağılımı Gizle ▲' : '12 Aylık Dağılım ve Karnesi ▼'"></span>
                                    </button>

                                    <div class="flex items-center gap-2">
                                        {{-- Forma Veri Girme Butonu --}}
                                        <a href="{{ route('form-entries.create', $target->form_template_id) }}"
                                            class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-bold bg-indigo-50 text-indigo-700 hover:bg-indigo-100 transition shadow-2xs">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                                            Forma Veri Gir
                                        </a>

                                        {{-- Detaylı İlerleme Sayfası --}}
                                        <a href="{{ route('kpi-targets.progress', $target->id) }}"
                                            class="inline-flex items-center gap-1 px-3 py-1.5 rounded-xl text-xs font-bold bg-slate-100 hover:bg-slate-200 text-slate-700 transition">
                                            <span>Detay</span>
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                                        </a>
                                    </div>
                                </div>

                                {{-- 12 AYLIK PERİYOT TABLOSU (AÇILIR KAPANIR) --}}
                                <div x-show="showMonthlyDetails" x-transition.duration.250ms class="pt-2">
                                    <div class="overflow-x-auto rounded-2xl border border-slate-100 bg-slate-50/50 p-3">
                                        <p class="text-[11px] font-extrabold text-slate-700 uppercase tracking-wider mb-2">12 Aylık Gerçekleşme Tablosu ({{ $target->target_year }})</p>
                                        <table class="min-w-full text-center text-xs">
                                            <thead>
                                                <tr class="text-[10px] text-slate-400 font-bold uppercase border-b border-slate-200/60">
                                                    @foreach(['Oca','Şub','Mar','Nis','May','Haz','Tem','Ağu','Eyl','Eki','Kas','Ara'] as $mName)
                                                        <th class="py-1 px-2">{{ $mName }}</th>
                                                    @endforeach
                                                </tr>
                                            </thead>
                                            <tbody class="divide-y divide-slate-100">
                                                {{-- Hedef Satırı --}}
                                                <tr>
                                                    @for($m = 1; $m <= 12; $m++)
                                                        @php
                                                            $p = $target->periods->where('period_month', $m)->first();
                                                            $pval = $p ? floatval($p->period_target_value) : 0;
                                                        @endphp
                                                        <td class="py-1.5 px-2 text-[11px] font-medium text-slate-600">
                                                            {{ $pval > 0 ? number_format($pval, 0, ',', '.') : '-' }}
                                                        </td>
                                                    @endfor
                                                </tr>
                                                {{-- Gerçekleşen Satırı --}}
                                                <tr class="font-bold">
                                                    @for($m = 1; $m <= 12; $m++)
                                                        @php
                                                            $mr = $target->realizations->where('realization_month', $m)->first();
                                                            $mval = $mr ? floatval($mr->actual_value) : 0;
                                                            $mrate = $mr ? floatval($mr->achievement_rate) : 0;
                                                            $clr = $mrate >= 100 ? 'text-emerald-700' : ($mrate >= 70 ? 'text-amber-700' : 'text-slate-700');
                                                        @endphp
                                                        <td class="py-1.5 px-2 text-[11px] {{ $clr }}">
                                                            {{ $mval > 0 ? number_format($mval, 0, ',', '.') : '-' }}
                                                        </td>
                                                    @endfor
                                                </tr>
                                                {{-- Başarı Yüzdesi --}}
                                                <tr class="text-[10px] font-extrabold">
                                                    @for($m = 1; $m <= 12; $m++)
                                                        @php
                                                            $mr = $target->realizations->where('realization_month', $m)->first();
                                                            $mrate = $mr ? floatval($mr->achievement_rate) : 0;
                                                            $bgC = $mrate >= 100 ? 'bg-emerald-100 text-emerald-800' : ($mrate >= 70 ? 'bg-amber-100 text-amber-800' : 'bg-slate-100 text-slate-500');
                                                        @endphp
                                                        <td class="py-1 px-1">
                                                            <span class="inline-block px-1.5 py-0.5 rounded {{ $bgC }}">
                                                                %{{ number_format($mrate, 0, ',', '.') }}
                                                            </span>
                                                        </td>
                                                    @endfor
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>

                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

        </div>
    </div>
</x-app-layout>

