@section('title', 'KPI Hedefleri')
<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            <div>
                <h2 class="font-extrabold text-2xl text-gray-900 tracking-tight flex items-center gap-2">
                    <span
                        class="w-8 h-8 rounded-lg bg-indigo-600 text-white flex items-center justify-center shadow-md">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z">
                            </path>
                        </svg>
                    </span>
                    {{ __('Dinamik KPI Hedef Yönetimi') }}
                </h2>
                <p class="text-sm text-gray-500 mt-1">Form alanlarına bağlı Hoshin Kanri hedef yayılımı ve gerçekleşme
                    takibi</p>
            </div>

            <div class="flex items-center gap-2">
                @if($canCreate)
                    <a href="{{ route('kpi-targets.create') }}"
                        class="inline-flex items-center px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-sm rounded-xl shadow-sm transition hover:shadow focus:outline-none">
                        <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                        </svg>
                        + Yeni Hedef Tanımla
                    </a>
                @endif
            </div>
        </div>
    </x-slot>

    <div class="py-6 sm:py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            @if(session('success'))
                <div class="p-4 text-sm text-emerald-800 bg-emerald-50 border border-emerald-200 rounded-xl flex items-center shadow-xs"
                    role="alert">
                    <svg class="w-5 h-5 mr-2 text-emerald-500 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd"
                            d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                            clip-rule="evenodd"></path>
                    </svg>
                    <span>{{ session('success') }}</span>
                </div>
            @endif

            @if(session('error'))
                <div class="p-4 text-sm text-rose-800 bg-rose-50 border border-rose-200 rounded-xl flex items-center shadow-xs"
                    role="alert">
                    <svg class="w-5 h-5 mr-2 text-rose-500 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd"
                            d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z"
                            clip-rule="evenodd"></path>
                    </svg>
                    <span>{{ session('error') }}</span>
                </div>
            @endif

            @if(session('warning'))
                <div class="p-4 text-sm text-amber-800 bg-amber-50 border border-amber-200 rounded-xl flex items-center shadow-xs"
                    role="alert">
                    <svg class="w-5 h-5 mr-2 text-amber-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z">
                        </path>
                    </svg>
                    <span>{{ session('warning') }}</span>
                </div>
            @endif

            {{-- 1. ÜST KPI ÖZET KARTLARI (SCORECARD) --}}
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                {{-- Toplam Hedef --}}
                <div
                    class="bg-white p-4 sm:p-5 rounded-2xl border border-slate-200/80 shadow-xs flex flex-col justify-between">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Tanımlı Hedef</span>
                        <span
                            class="w-8 h-8 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center font-bold">🎯</span>
                    </div>
                    <div class="mt-3">
                        <p class="text-2xl sm:text-3xl font-black text-slate-900">{{ $totalTargetsCount }}</p>
                        <span class="text-[11px] text-slate-500 font-medium">{{ $filters['year'] ?? date('Y') }} Yılı
                            Toplam KPI</span>
                    </div>
                </div>

                {{-- Bireysel Hedefler --}}
                <div
                    class="bg-white p-4 sm:p-5 rounded-2xl border border-slate-200/80 shadow-xs flex flex-col justify-between">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Bireysel Hedefler</span>
                        <span
                            class="w-8 h-8 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center font-bold">👤</span>
                    </div>
                    <div class="mt-3">
                        <p class="text-2xl sm:text-3xl font-black text-slate-900">{{ $userTargetsCount }}</p>
                        <span class="text-[11px] text-slate-500 font-medium">Personele Özel Atananlar</span>
                    </div>
                </div>

                {{-- Grup / Departman Hedefleri --}}
                <div
                    class="bg-white p-4 sm:p-5 rounded-2xl border border-slate-200/80 shadow-xs flex flex-col justify-between">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Grup & Departman</span>
                        <span
                            class="w-8 h-8 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center font-bold">👥</span>
                    </div>
                    <div class="mt-3">
                        <p class="text-2xl sm:text-3xl font-black text-slate-900">{{ $groupTargetsCount }}</p>
                        <span class="text-[11px] text-slate-500 font-medium">Bölüm & Takım Hedefleri</span>
                    </div>
                </div>

                {{-- Ortalama Başarı --}}
                <div
                    class="bg-white p-4 sm:p-5 rounded-2xl border border-slate-200/80 shadow-xs flex flex-col justify-between">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Ortalama Başarı</span>
                        <span
                            class="w-8 h-8 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center font-bold">📊</span>
                    </div>
                    <div class="mt-3">
                        <div class="flex items-baseline gap-1.5">
                            <p class="text-2xl sm:text-3xl font-black text-emerald-600">
                                %{{ number_format($avgRate, 1, ',', '.') }}</p>
                            <span class="text-[10px] font-bold text-slate-400 uppercase">Kümülatif</span>
                        </div>
                        <div class="w-full bg-slate-100 rounded-full h-1.5 mt-2 overflow-hidden">
                            <div class="bg-emerald-500 h-1.5 rounded-full transition-all duration-500"
                                style="width: {{ min(100, $avgRate) }}%"></div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- 2. SEGMENTLİ SEKME ÇUBUĞU (HİYERARŞİ TABS) --}}
            <div
                class="flex items-center gap-1.5 p-1 bg-slate-100/90 rounded-2xl border border-slate-200/80 overflow-x-auto">
                <a href="{{ route('kpi-targets.index', array_merge(request()->except('tab'), ['tab' => 'all'])) }}"
                    class="px-4 py-2.5 rounded-xl text-xs font-bold transition-all flex items-center gap-2 shrink-0 {{ $activeTab === 'all' ? 'bg-white text-indigo-700 shadow-sm font-black' : 'text-slate-600 hover:text-slate-900' }}">
                    <span>🌐</span> Tüm Hedefler
                    <span
                        class="px-2 py-0.5 rounded-full text-[10px] {{ $activeTab === 'all' ? 'bg-indigo-100 text-indigo-800' : 'bg-slate-200 text-slate-600' }}">{{ $totalTargetsCount }}</span>
                </a>
                <a href="{{ route('kpi-targets.index', array_merge(request()->except('tab'), ['tab' => 'user'])) }}"
                    class="px-4 py-2.5 rounded-xl text-xs font-bold transition-all flex items-center gap-2 shrink-0 {{ $activeTab === 'user' ? 'bg-white text-indigo-700 shadow-sm font-black' : 'text-slate-600 hover:text-slate-900' }}">
                    <span>👤</span> Bireysel Personel Hedefleri
                    <span
                        class="px-2 py-0.5 rounded-full text-[10px] {{ $activeTab === 'user' ? 'bg-indigo-100 text-indigo-800' : 'bg-slate-200 text-slate-600' }}">{{ $userTargetsCount }}</span>
                </a>
                <a href="{{ route('kpi-targets.index', array_merge(request()->except('tab'), ['tab' => 'group'])) }}"
                    class="px-4 py-2.5 rounded-xl text-xs font-bold transition-all flex items-center gap-2 shrink-0 {{ $activeTab === 'group' ? 'bg-white text-indigo-700 shadow-sm font-black' : 'text-slate-600 hover:text-slate-900' }}">
                    <span>👥</span> Departman / Grup Hedefleri
                    <span
                        class="px-2 py-0.5 rounded-full text-[10px] {{ $activeTab === 'group' ? 'bg-indigo-100 text-indigo-800' : 'bg-slate-200 text-slate-600' }}">{{ $groupTargetsCount }}</span>
                </a>
                <a href="{{ route('kpi-targets.index', array_merge(request()->except('tab'), ['tab' => 'geo'])) }}"
                    class="px-4 py-2.5 rounded-xl text-xs font-bold transition-all flex items-center gap-2 shrink-0 {{ $activeTab === 'geo' ? 'bg-indigo-600 text-white shadow-sm font-black' : 'text-slate-600 hover:text-indigo-600' }}">
                    <span>🌍</span> Coğrafi Dağılım & Radar Analizi
                    <span
                        class="px-2 py-0.5 rounded-full text-[10px] bg-amber-400 text-slate-900 font-black">Harita</span>
                </a>
            </div>

            {{-- 3. COĞRAFİ HARİTA & RADAR GÖRÜNÜMÜ (Sekme Görünümü) --}}
            @if($activeTab === 'geo')
                @include('kpi_targets.partials.geo_map_and_radar', [
                    'geoData' => $geoData,
                    'radarData' => $radarData,
                    'users' => $users,
                    'groups' => $groups,
                    'geoUserId' => $geoUserId ?? null,
                    'geoGroupId' => $geoGroupId ?? null
                ])
            @endif

            {{-- Filtre Çubuğu --}}
            <div class="bg-white rounded-2xl p-5 shadow-xs border border-gray-100">
                <form action="{{ route('kpi-targets.index') }}" method="GET"
                    class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-3">
                    <input type="hidden" name="tab" value="{{ $activeTab }}">

                    {{-- Yıl --}}
                    <div>
                        <label class="block text-xs font-bold text-gray-600 mb-1">Hedef Yılı</label>
                        <select name="year"
                            class="w-full text-xs rounded-xl border-gray-200 focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="">Tüm Yıllar</option>
                            @foreach($years as $y)
                                <option value="{{ $y }}" {{ ($filters['year'] ?? '') == $y ? 'selected' : '' }}>{{ $y }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Form Şablonu --}}
                    <div>
                        <label class="block text-xs font-bold text-gray-600 mb-1">Form Şablonu</label>
                        <select name="template_id"
                            class="w-full text-xs rounded-xl border-gray-200 focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="">Tüm Şablonlar</option>
                            @foreach($templates as $t)
                                <option value="{{ $t->id }}" {{ ($filters['template_id'] ?? '') == $t->id ? 'selected' : '' }}>{{ $t->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Hedef Sahibi (Personel / Grup) --}}
                    <div>
                        <label class="block text-xs font-bold text-gray-600 mb-1">Personel / Atanan</label>
                        <select name="assignee_id"
                            class="w-full text-xs rounded-xl border-gray-200 focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="">-- Tüm Kişiler --</option>
                            @foreach($users as $u)
                                <option value="{{ $u->id }}" {{ ($filters['assignee_id'] ?? '') == $u->id ? 'selected' : '' }}>
                                    {{ $u->name }} ({{ $u->group->name ?? 'Bağımsız' }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Durum --}}
                    <div>
                        <label class="block text-xs font-bold text-gray-600 mb-1">Durum</label>
                        <select name="status"
                            class="w-full text-xs rounded-xl border-gray-200 focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="">Tüm Durumlar</option>
                            <option value="active" {{ ($filters['status'] ?? '') == 'active' ? 'selected' : '' }}>Aktif
                            </option>
                            <option value="paused" {{ ($filters['status'] ?? '') == 'paused' ? 'selected' : '' }}>
                                Duraklatıldı</option>
                            <option value="completed" {{ ($filters['status'] ?? '') == 'completed' ? 'selected' : '' }}>
                                Tamamlandı</option>
                            <option value="cancelled" {{ ($filters['status'] ?? '') == 'cancelled' ? 'selected' : '' }}>
                                İptal</option>
                        </select>
                    </div>

                    {{-- Arama --}}
                    <div class="lg:col-span-2">
                        <label class="block text-xs font-bold text-gray-600 mb-1">Arama</label>
                        <div class="flex gap-2">
                            <input type="text" name="search" value="{{ $filters['search'] ?? '' }}"
                                placeholder="Hedef adı, kriter..."
                                class="w-full text-xs rounded-xl border-gray-200 focus:border-indigo-500 focus:ring-indigo-500">
                            <button type="submit"
                                class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-bold shrink-0 transition">
                                Filtrele
                            </button>
                            @if(!empty(array_filter($filters)))
                                <a href="{{ route('kpi-targets.index', ['tab' => $activeTab]) }}"
                                    class="px-3 py-2 bg-gray-100 hover:bg-gray-200 text-gray-600 rounded-xl text-xs font-bold shrink-0 transition flex items-center"
                                    title="Temizle">
                                    ✕
                                </a>
                            @endif
                        </div>
                    </div>
                </form>
            </div>

            {{-- Hedef Tablosu --}}
            <div class="bg-white rounded-2xl shadow-xs border border-gray-100 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-100 text-sm">
                        <thead>
                            <tr class="bg-gray-50/80">
                                <th
                                    class="px-5 py-3.5 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">
                                    Hedef & Kapsam</th>
                                <th
                                    class="px-5 py-3.5 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">
                                    Şablon / Alan</th>
                                <th
                                    class="px-5 py-3.5 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">
                                    Hedef Sahibi</th>
                                <th
                                    class="px-5 py-3.5 text-right text-xs font-bold text-gray-500 uppercase tracking-wider">
                                    Hedef</th>
                                <th
                                    class="px-5 py-3.5 text-right text-xs font-bold text-gray-500 uppercase tracking-wider">
                                    Gerçekleşen</th>
                                <th
                                    class="px-5 py-3.5 text-center text-xs font-bold text-gray-500 uppercase tracking-wider min-w-[160px]">
                                    Başarı Oranı</th>
                                <th
                                    class="px-5 py-3.5 text-center text-xs font-bold text-gray-500 uppercase tracking-wider">
                                    Durum</th>
                                <th
                                    class="px-5 py-3.5 text-right text-xs font-bold text-gray-500 uppercase tracking-wider">
                                    İşlemler</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 bg-white">
                            @forelse($targets as $target)
                                @php
                                    $realization = $target->realizations->first(); // month = 0
                                    $actual = $realization ? floatval($realization->actual_value) : 0;
                                    $rate = $realization ? floatval($realization->achievement_rate) : 0;

                                    if ($rate >= 100) {
                                        $barColor = 'bg-emerald-500';
                                        $badgeClass = 'bg-emerald-50 text-emerald-700 border-emerald-200';
                                    } elseif ($rate >= 70) {
                                        $barColor = 'bg-amber-500';
                                        $badgeClass = 'bg-amber-50 text-amber-700 border-amber-200';
                                    } else {
                                        $barColor = 'bg-rose-500';
                                        $badgeClass = 'bg-rose-50 text-rose-700 border-rose-200';
                                    }
                                @endphp
                                <tr class="hover:bg-slate-50/70 transition-colors">
                                    {{-- Hedef Başlığı --}}
                                    <td class="px-5 py-4">
                                        <div class="flex items-start gap-2.5">
                                            <span
                                                class="inline-flex items-center justify-center w-7 h-7 rounded-lg bg-indigo-50 text-indigo-700 font-bold text-xs shrink-0 mt-0.5">
                                                {{ $target->target_year }}
                                            </span>
                                            <div>
                                                <a href="{{ route('kpi-targets.progress', $target->id) }}"
                                                    class="font-bold text-gray-900 hover:text-indigo-600 transition">
                                                    {{ $target->title }}
                                                </a>
                                                @php $badges = app(\App\Services\KpiTargetS::class)->getFormattedCriteriaBadges($target->criteria_json); @endphp
                                                @if(!empty($badges))
                                                    <div class="flex flex-wrap items-center gap-1 mt-1.5">
                                                        @foreach(array_slice($badges, 0, 3) as $badge)
                                                            <span
                                                                class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[10px] font-bold border {{ $badge['bg_class'] }} shadow-2xs">
                                                                <span>{{ $badge['icon'] }}</span>
                                                                <span class="opacity-75 font-semibold">{{ $badge['label'] }}:</span>
                                                                <strong>{{ $badge['value'] }}</strong>
                                                            </span>
                                                        @endforeach
                                                        @if(count($badges) > 3)
                                                            <span
                                                                class="inline-flex items-center px-1.5 py-0.5 rounded-md text-[10px] font-bold bg-slate-100 text-slate-600 border border-slate-200 cursor-pointer"
                                                                title="{{ app(\App\Services\KpiTargetS::class)->formatCriteriaSummary($target->criteria_json) }}">
                                                                +{{ count($badges) - 3 }} daha
                                                            </span>
                                                        @endif
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                    </td>

                                    {{-- Şablon & Alan --}}
                                    <td class="px-5 py-4">
                                        <div class="font-medium text-gray-800 text-xs">{{ $target->template->name ?? '-' }}
                                        </div>
                                        <span
                                            class="inline-flex items-center text-[10px] font-mono px-2 py-0.5 rounded-md bg-gray-100 text-gray-600 mt-1">
                                            {{ $target->targetField->label ?? '-' }}
                                        </span>
                                    </td>

                                    {{-- Hedef Sahibi --}}
                                    <td class="px-5 py-4">
                                        @if($target->assignee_type === 'user')
                                            @php $u = \App\Models\UserM::find($target->assignee_id); @endphp
                                            @if($u)
                                                <a href="{{ url('/kullanici-profil/' . $u->id) }}"
                                                    class="inline-flex items-center gap-1.5 text-xs font-semibold text-gray-900 hover:text-indigo-600">
                                                    <span
                                                        class="w-5 h-5 rounded-full bg-slate-200 text-slate-700 flex items-center justify-center text-[10px]">
                                                        {{ mb_substr($u->name, 0, 1) }}
                                                    </span>
                                                    {{ $u->name }}
                                                </a>
                                            @else
                                                <span class="text-xs text-gray-400">Bilinmiyor</span>
                                            @endif
                                        @elseif($target->assignee_type === 'group')
                                            <span
                                                class="inline-flex items-center px-2 py-0.5 rounded-lg text-xs font-medium bg-purple-50 text-purple-700 border border-purple-200">
                                                👥 {{ $target->assignee_name }}
                                            </span>
                                        @elseif($target->assignee_type === 'manager')
                                            <span
                                                class="inline-flex items-center px-2 py-0.5 rounded-lg text-xs font-medium bg-blue-50 text-blue-700 border border-blue-200">
                                                👔 {{ $target->assignee_name }}
                                            </span>
                                        @endif
                                    </td>

                                    {{-- Hedef Değer --}}
                                    <td class="px-5 py-4 text-right">
                                        <div class="font-bold text-gray-900">
                                            {{ number_format($target->target_value, 2, ',', '.') }}</div>
                                        <span class="text-[10px] text-gray-500 font-semibold">{{ $target->unit }}</span>
                                    </td>

                                    {{-- Gerçekleşen --}}
                                    <td class="px-5 py-4 text-right">
                                        <div class="font-bold text-gray-900">{{ number_format($actual, 2, ',', '.') }}</div>
                                        <span class="text-[10px] text-gray-500 font-semibold">{{ $target->unit }}</span>
                                    </td>

                                    {{-- Başarı Oranı & Bar --}}
                                    <td class="px-5 py-4">
                                        <div class="flex flex-col items-center">
                                            <span
                                                class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-extrabold border {{ $badgeClass }}">
                                                %{{ number_format($rate, 1, ',', '.') }}
                                            </span>
                                            <div class="w-full bg-gray-100 rounded-full h-1.5 mt-2 overflow-hidden">
                                                <div class="{{ $barColor }} h-1.5 rounded-full transition-all duration-500"
                                                    style="width: {{ min(100, $rate) }}%"></div>
                                            </div>
                                        </div>
                                    </td>

                                    {{-- Durum --}}
                                    <td class="px-5 py-4 text-center">
                                        @if($target->status === 'active')
                                            <span
                                                class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-bold bg-emerald-50 text-emerald-700">Aktif</span>
                                        @elseif($target->status === 'paused')
                                            <span
                                                class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-bold bg-amber-50 text-amber-700">Duraklatıldı</span>
                                        @elseif($target->status === 'completed')
                                            <span
                                                class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-bold bg-indigo-50 text-indigo-700">Tamamlandı</span>
                                        @else
                                            <span
                                                class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-bold bg-gray-100 text-gray-500">İptal</span>
                                        @endif
                                    </td>

                                    {{-- İşlemler --}}
                                    <td class="px-5 py-4 text-right">
                                        <div class="flex items-center justify-end gap-1.5">
                                            {{-- İlerleme Grafiği --}}
                                            <a href="{{ route('kpi-targets.progress', $target->id) }}"
                                                class="p-1.5 text-indigo-600 hover:bg-indigo-50 rounded-lg transition"
                                                title="Performans & İlerleme Grafiği">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z">
                                                    </path>
                                                </svg>
                                            </a>

                                            @if($canDistribute)
                                                <a href="{{ route('kpi-targets.distribute', $target->id) }}"
                                                    class="p-1.5 text-teal-600 hover:bg-teal-50 rounded-lg transition"
                                                    title="12 Aylık Dağıtım">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                            d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z">
                                                        </path>
                                                    </svg>
                                                </a>
                                            @endif

                                            @if($canRecalculate)
                                                <form action="{{ route('kpi-targets.recalculate', $target->id) }}" method="POST"
                                                    class="inline">
                                                    @csrf
                                                    <button type="submit"
                                                        class="p-1.5 text-gray-500 hover:text-indigo-600 hover:bg-gray-100 rounded-lg transition"
                                                        title="Verileri Yeniden Hesapla">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                                            viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                                stroke-width="2"
                                                                d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15">
                                                            </path>
                                                        </svg>
                                                    </button>
                                                </form>
                                            @endif

                                            @if($canEdit)
                                                <a href="{{ route('kpi-targets.edit', $target->id) }}"
                                                    class="p-1.5 text-blue-600 hover:bg-blue-50 rounded-lg transition"
                                                    title="Düzenle">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                            d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z">
                                                        </path>
                                                    </svg>
                                                </a>
                                            @endif

                                            @if($canDelete)
                                                <form action="{{ route('kpi-targets.destroy', $target->id) }}" method="POST"
                                                    class="inline"
                                                    onsubmit="return confirm('Bu hedefi silmek istediğinize emin misiniz?');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit"
                                                        class="p-1.5 text-rose-600 hover:bg-rose-50 rounded-lg transition"
                                                        title="Sil">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                                            viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                                stroke-width="2"
                                                                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16">
                                                            </path>
                                                        </svg>
                                                    </button>
                                                </form>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="px-6 py-12 text-center text-gray-400">
                                        <div class="flex flex-col items-center justify-center">
                                            <div
                                                class="w-12 h-12 rounded-full bg-gray-100 flex items-center justify-center text-gray-400 mb-3">
                                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z">
                                                    </path>
                                                </svg>
                                            </div>
                                            <p class="font-bold text-gray-600">Henüz tanımlı KPI hedefi bulunamadı.</p>
                                            <p class="text-xs text-gray-400 mt-1">Filtreleri değiştirebilir veya yeni bir
                                                hedef ekleyebilirsiniz.</p>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if($targets->hasPages())
                    <div class="p-4 border-t border-gray-100">
                        {{ $targets->links() }}
                    </div>
                @endif
            </div>

        </div>
    </div>
</x-app-layout>