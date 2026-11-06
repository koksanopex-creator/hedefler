@section('title', $user->name . ' - Kullanıcı Profili')
<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            <div class="flex items-center gap-4">
                @if($user->avatar && \Illuminate\Support\Facades\Storage::disk('public')->exists($user->avatar))
                    <img src="{{ asset('storage/' . $user->avatar) }}" alt="{{ $user->name }}" class="w-16 h-16 rounded-2xl object-cover border-2 border-white shadow-lg shadow-indigo-200">
                @else
                    <div class="w-16 h-16 rounded-2xl bg-gradient-to-tr from-indigo-600 to-purple-600 flex items-center justify-center text-white text-2xl font-black shadow-lg shadow-indigo-200">
                        {{ mb_substr($user->name, 0, 1) }}
                    </div>
                @endif
                <div>
                    <div class="flex items-center gap-3">
                        <h2 class="text-2xl font-black text-slate-900 tracking-tight">{{ $user->name }}</h2>
                        @if($user->is_active)
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                <span class="w-1.5 h-1.5 bg-emerald-500 rounded-full mr-1.5 animate-pulse"></span>
                                Aktif
                            </span>
                        @else
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-rose-50 text-rose-700 border border-rose-200">
                                Pasif
                            </span>
                        @endif
                    </div>
                    <p class="text-xs text-slate-500 mt-1 font-medium flex items-center gap-2">
                        <span>{{ $user->email }}</span>
                        <span>&bull;</span>
                        <span>Kayıt Tarihi: {{ $user->created_at->format('d.m.Y') }}</span>
                    </p>
                </div>
            </div>

            <!-- Kullanıcı Rol & Grup Rozetleri -->
            <div class="flex flex-wrap items-center gap-2">
                @if($user->role)
                    <span class="px-3 py-1 rounded-xl text-xs font-bold bg-indigo-50 text-indigo-700 border border-indigo-200 shadow-2xs">
                        Rol: {{ $user->role->name }}
                    </span>
                @endif
                @if($user->group)
                    <span class="px-3 py-1 rounded-xl text-xs font-bold bg-purple-50 text-purple-700 border border-purple-200 shadow-2xs">
                        Grup: {{ $user->group->name }}
                    </span>
                @endif
                @if($user->managedGroups->count() > 0)
                    <span class="px-3 py-1 rounded-xl text-xs font-bold bg-amber-50 text-amber-700 border border-amber-200 shadow-2xs">
                        👑 Grup Yöneticisi ({{ $user->managedGroups->pluck('name')->implode(', ') }})
                    </span>
                @endif
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-8">

            <!-- 1. İSTATİSTİK VE ÖZET KARTLARI -->
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 sm:gap-6">
                <!-- Toplam Giriş -->
                <div class="bg-white p-5 rounded-2xl border border-slate-100 shadow-xs">
                    <p class="text-xs font-bold text-slate-400 uppercase tracking-wider">Toplam Form Girişi</p>
                    <p class="text-3xl font-black text-slate-900 mt-2">{{ $stats['total_entries'] }}</p>
                    <span class="text-[11px] text-slate-500 mt-1 block">Tüm şablonlarda</span>
                </div>

                <!-- Onaylanan -->
                <div class="bg-white p-5 rounded-2xl border border-slate-100 shadow-xs">
                    <p class="text-xs font-bold text-emerald-600 uppercase tracking-wider">Onaylanan Kayıtlar</p>
                    <p class="text-3xl font-black text-emerald-700 mt-2">{{ $stats['approved_entries'] }}</p>
                    <span class="text-[11px] text-emerald-600/80 mt-1 block">Nihai onaylı</span>
                </div>

                <!-- Onay Bekleyen -->
                <div class="bg-white p-5 rounded-2xl border border-slate-100 shadow-xs">
                    <p class="text-xs font-bold text-blue-600 uppercase tracking-wider">Onay Bekleyen</p>
                    <p class="text-3xl font-black text-blue-700 mt-2">{{ $stats['pending_entries'] }}</p>
                    <span class="text-[11px] text-blue-600/80 mt-1 block">İnceleme sürecinde</span>
                </div>

                <!-- Taslaklar -->
                <div class="bg-white p-5 rounded-2xl border border-slate-100 shadow-xs">
                    <p class="text-xs font-bold text-amber-600 uppercase tracking-wider">Taslaklar</p>
                    <p class="text-3xl font-black text-amber-700 mt-2">{{ $stats['draft_entries'] }}</p>
                    <span class="text-[11px] text-amber-600/80 mt-1 block">Henüz iletilmemiş</span>
                </div>
            </div>

            <!-- 2. HOSHIN KANRI & HEDEF (KPI) PERFORMANS KARNESİ ALANI (YETKİ KONTROLLÜ) -->
            @if($canViewKpi)
                <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-6 sm:p-8 space-y-6">
                    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 pb-6 border-b border-slate-100">
                        <div>
                            <div class="flex items-center gap-2">
                                <span class="w-8 h-8 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path></svg>
                                </span>
                                <h3 class="text-lg font-black text-slate-900 tracking-tight">
                                    Personel KPI & Performans Karnesi ({{ $selectedKpiYear }})
                                </h3>
                            </div>
                            <p class="text-xs text-slate-500 mt-1">Dinamik form alanlarına göre personele atanmış yıllık hedefler, gerçekleşme oranları ve performans düzeyi.</p>
                        </div>

                        {{-- Yıl Seçici & Geçmiş Yıl Arşivi --}}
                        <div class="flex items-center gap-1.5 bg-slate-100/80 p-1.5 rounded-xl border border-slate-200 shadow-2xs">
                            <span class="text-xs font-bold text-slate-500 px-2 flex items-center gap-1">
                                <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                                Yıl:
                            </span>
                            @foreach($availableKpiYears as $y)
                                <a href="{{ route('users.profile', ['user' => $user->id, 'kpi_year' => $y]) }}"
                                    class="px-2.5 py-1 rounded-lg text-xs font-bold transition-all {{ $selectedKpiYear == $y ? 'bg-indigo-600 text-white shadow-xs' : 'text-slate-600 hover:text-indigo-600 hover:bg-white' }}">
                                    {{ $y }}
                                </a>
                            @endforeach
                        </div>
                    </div>

                    {{-- Performans Özet Şeridi --}}
                    @if($kpiTargets->isNotEmpty())
                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 p-4 bg-gradient-to-r from-indigo-50/70 via-purple-50/50 to-slate-50 rounded-2xl border border-indigo-100/70">
                            <div>
                                <span class="text-[10px] font-extrabold uppercase tracking-wider text-slate-400">Performans Derecesi</span>
                                <p class="text-sm sm:text-base font-black text-indigo-950 mt-0.5">{{ $kpiStats['performance_grade'] }}</p>
                            </div>
                            <div>
                                <span class="text-[10px] font-extrabold uppercase tracking-wider text-slate-400">Ortalama KPI Başarısı</span>
                                <p class="text-sm sm:text-base font-black text-indigo-600 mt-0.5">%{{ number_format($kpiStats['average_rate'], 1, ',', '.') }}</p>
                            </div>
                            <div>
                                <span class="text-[10px] font-extrabold uppercase tracking-wider text-slate-400">Tamamlanan Hedefler</span>
                                <p class="text-sm sm:text-base font-black text-emerald-700 mt-0.5">{{ $kpiStats['achieved_count'] }} / {{ $kpiStats['total_targets'] }}</p>
                            </div>
                            <div>
                                <span class="text-[10px] font-extrabold uppercase tracking-wider text-slate-400">Risk Altındaki Hedefler</span>
                                <p class="text-sm sm:text-base font-black text-amber-700 mt-0.5">{{ $kpiStats['at_risk_count'] }} Hedef</p>
                            </div>
                        </div>
                    @endif

                    <!-- Hedef Kartları -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        @forelse($kpiTargets as $target)
                            @php
                                $real = $target->realizations->where('realization_month', 0)->first();
                                $act = $real ? floatval($real->actual_value) : 0;
                                $rt = $real ? floatval($real->achievement_rate) : 0;

                                if ($rt >= 100) {
                                    $barG = 'from-emerald-500 to-teal-500';
                                    $badgeC = 'text-emerald-700 bg-emerald-50 border-emerald-200';
                                } elseif ($rt >= 70) {
                                    $barG = 'from-amber-500 to-amber-600';
                                    $badgeC = 'text-amber-700 bg-amber-50 border-amber-200';
                                } else {
                                    $barG = 'from-rose-500 to-rose-600';
                                    $badgeC = 'text-rose-700 bg-rose-50 border-rose-200';
                                }
                            @endphp
                            <div class="p-5 rounded-2xl bg-slate-50/70 border border-slate-200/80 hover:border-indigo-200 hover:bg-slate-50 transition relative group flex flex-col justify-between">
                                <div>
                                    <div class="flex justify-between items-start">
                                        <div class="pr-2">
                                            <span class="text-[10px] font-extrabold uppercase text-indigo-600 tracking-wider">
                                                {{ $target->targetField->label }} ({{ $target->target_year }})
                                            </span>
                                            <h4 class="text-sm font-bold text-slate-800 mt-1 line-clamp-1" title="{{ $target->title }}">
                                                <a href="{{ route('kpi-targets.progress', $target->id) }}" class="hover:text-indigo-600 transition">
                                                    {{ $target->title }}
                                                </a>
                                            </h4>
                                        </div>
                                        <span class="text-xs font-black px-2 py-0.5 rounded-md border {{ $badgeC }}">
                                            %{{ number_format($rt, 1, ',', '.') }}
                                        </span>
                                    </div>

                                    @php $badges = app(\App\Services\KpiTargetS::class)->getFormattedCriteriaBadges($target->criteria_json); @endphp
                                    @if(!empty($badges))
                                        <div class="flex flex-wrap items-center gap-1 mt-2.5">
                                            @foreach(array_slice($badges, 0, 4) as $badge)
                                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[10px] font-bold border {{ $badge['bg_class'] }} shadow-2xs">
                                                    <span>{{ $badge['icon'] }}</span>
                                                    <span class="opacity-75 font-semibold">{{ $badge['label'] }}:</span>
                                                    <strong>{{ $badge['value'] }}</strong>
                                                </span>
                                            @endforeach
                                            @if(count($badges) > 4)
                                                <span class="inline-flex items-center px-1.5 py-0.5 rounded-md text-[10px] font-bold bg-slate-100 text-slate-600 border border-slate-200 cursor-pointer"
                                                    title="{{ app(\App\Services\KpiTargetS::class)->formatCriteriaSummary($target->criteria_json) }}">
                                                    +{{ count($badges) - 4 }} daha
                                                </span>
                                            @endif
                                        </div>
                                    @endif
                                </div>

                                <div class="mt-4">
                                    <div class="flex items-baseline justify-between text-xs">
                                        <span class="text-slate-500">Gerçekleşen: <strong class="text-slate-900 font-bold">{{ number_format($act, 2, ',', '.') }} {{ $target->unit }}</strong></span>
                                        <span class="text-slate-500">Hedef: <strong class="text-slate-900 font-bold">{{ number_format($target->target_value, 2, ',', '.') }} {{ $target->unit }}</strong></span>
                                    </div>
                                    <div class="w-full bg-slate-200 rounded-full h-2 mt-2 overflow-hidden">
                                        <div class="bg-gradient-to-r {{ $barG }} h-2 rounded-full transition-all duration-500" style="width: {{ min(100, $rt) }}%"></div>
                                    </div>
                                    <div class="mt-3 text-right">
                                        <a href="{{ route('kpi-targets.progress', $target->id) }}" class="text-[11px] font-bold text-indigo-600 hover:text-indigo-800 hover:underline inline-flex items-center gap-1">
                                            Aylık Karne ve Detaylar &rarr;
                                        </a>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="col-span-3 text-center py-10 text-slate-400 bg-slate-50/50 rounded-2xl border border-dashed border-slate-200">
                                <div class="w-10 h-10 rounded-full bg-slate-100 text-slate-400 mx-auto flex items-center justify-center mb-2">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                                </div>
                                <p class="text-sm font-bold text-slate-700">Bu personel için {{ $selectedKpiYear }} yılına ait tanımlanmış bir hedef bulunamadı.</p>
                                <p class="text-xs text-slate-400 mt-0.5">Personelin geçmiş yıllardaki hedeflerini görmek için yukarıdaki yıl butonlarını kullanabilirsiniz.</p>
                            </div>
                        @endforelse
                    </div>

                    {{-- Coğrafi Harita & Radar Analizi --}}
                    @if(isset($geoData) && isset($radarData))
                        <div class="mt-6">
                            @include('kpi_targets.partials.geo_map_and_radar', ['geoData' => $geoData, 'radarData' => $radarData])
                        </div>
                    @endif
                </div>
            @endif

            <!-- 3. KULLANICININ VERİ GİRİŞLERİ TABLOSU -->
            <div class="bg-white rounded-2xl border border-slate-100 shadow-xs overflow-hidden">
                <div class="p-5 sm:p-6 border-b border-slate-100 flex justify-between items-center">
                    <div>
                        <h3 class="text-base font-black text-slate-900 tracking-tight">Kullanıcının Doldurduğu Formlar</h3>
                        <p class="text-xs text-slate-500 mt-0.5">Son yapılan veri girişleri ve durumları</p>
                    </div>
                    @if($stats['last_entry_date'])
                        <span class="text-xs text-slate-400 font-medium">Son Giriş: {{ \Carbon\Carbon::parse($stats['last_entry_date'])->format('d.m.Y') }}</span>
                    @endif
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-100">
                        <thead class="bg-slate-50/60">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-bold text-slate-500 uppercase tracking-wider">Tarih</th>
                                <th class="px-6 py-3 text-left text-xs font-bold text-slate-500 uppercase tracking-wider">Form Şablonu</th>
                                <th class="px-6 py-3 text-left text-xs font-bold text-slate-500 uppercase tracking-wider">Durum</th>
                                <th class="px-6 py-3 text-right text-xs font-bold text-slate-500 uppercase tracking-wider">İşlem</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            @forelse($formEntries as $entry)
                                <tr class="hover:bg-slate-50/80 transition-colors">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-700 font-medium">
                                        {{ $entry->entry_date ? $entry->entry_date->format('d.m.Y') : '-' }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-slate-900">
                                        {{ $entry->template->name ?? 'Belirtilmemiş' }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        @if($entry->status === 'approved')
                                            <span class="px-2.5 py-1 text-xs font-bold rounded-full bg-emerald-50 text-emerald-700 border border-emerald-200">Onaylandı</span>
                                        @elseif($entry->status === 'submitted')
                                            <span class="px-2.5 py-1 text-xs font-bold rounded-full bg-blue-50 text-blue-700 border border-blue-200">Onay Bekliyor</span>
                                        @elseif($entry->status === 'rejected')
                                            <span class="px-2.5 py-1 text-xs font-bold rounded-full bg-rose-50 text-rose-700 border border-rose-200">Reddedildi</span>
                                        @else
                                            <span class="px-2.5 py-1 text-xs font-bold rounded-full bg-amber-50 text-amber-700 border border-amber-200">Taslak</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <a href="{{ route('form-entries.show', $entry) }}" class="inline-flex items-center text-xs font-bold text-indigo-600 hover:text-indigo-800 bg-indigo-50/70 hover:bg-indigo-100 px-3 py-1.5 rounded-lg transition-colors">
                                            İncele &rarr;
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-6 py-10 text-center text-sm text-slate-400">
                                        Kullanıcıya ait henüz bir veri girişi bulunmuyor.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if($formEntries->hasPages())
                    <div class="p-4 border-t border-slate-100 bg-slate-50/50">
                        {{ $formEntries->links() }}
                    </div>
                @endif
            </div>

        </div>
    </div>
</x-app-layout>

