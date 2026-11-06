@section('title', 'KPI Performans & İlerleme')
<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            <div>
                <h2 class="font-extrabold text-2xl text-gray-900 tracking-tight flex items-center gap-2">
                    <a href="{{ route('kpi-targets.index') }}" class="text-gray-400 hover:text-gray-600 transition">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                    </a>
                    {{ $kpiTarget->title }}
                </h2>
                <div class="flex items-center gap-3 text-xs text-gray-500 mt-1">
                    <span>Dönem: <strong class="text-gray-700">{{ $kpiTarget->target_year }} Yılı</strong></span>
                    <span>•</span>
                    <span>Şablon: <strong class="text-gray-700">{{ $kpiTarget->template->name }}</strong></span>
                    <span>•</span>
                    <span>Alan: <strong class="text-indigo-600 font-mono">{{ $kpiTarget->targetField->label }}</strong></span>
                    <span>•</span>
                    <span>Sahip: <strong class="text-purple-700">{{ $kpiTarget->assignee_name }}</strong></span>
                </div>
            </div>

            <div class="flex items-center gap-2">
                @if($canRecalculate)
                    <form action="{{ route('kpi-targets.recalculate', $kpiTarget->id) }}" method="POST">
                        @csrf
                        <button type="submit" class="px-3.5 py-2 bg-white hover:bg-gray-50 text-gray-700 text-xs font-bold rounded-xl border border-gray-200 shadow-2xs transition flex items-center gap-1.5">
                            <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                            Verileri Güncelle
                        </button>
                    </form>
                @endif

                @if($canDistribute)
                    <a href="{{ route('kpi-targets.distribute', $kpiTarget->id) }}" class="px-3.5 py-2 bg-teal-50 hover:bg-teal-100 text-teal-700 text-xs font-bold rounded-xl border border-teal-200 shadow-2xs transition flex items-center gap-1.5">
                        <svg class="w-4 h-4 text-teal-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                        Aylık Dağıtım
                    </a>
                @endif

                @if($canEdit)
                    <a href="{{ route('kpi-targets.edit', $kpiTarget->id) }}" class="px-3.5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold rounded-xl shadow-2xs transition flex items-center gap-1.5">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                        Hedefi Düzenle
                    </a>
                @endif
            </div>
        </div>
    </x-slot>

    <!-- Chart.js CDN -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>

    <div class="py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            @if(session('success'))
                <div class="p-4 text-sm text-emerald-800 bg-emerald-50 border border-emerald-200 rounded-xl flex items-center shadow-xs" role="alert">
                    <svg class="w-5 h-5 mr-2 text-emerald-500 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg>
                    <span>{{ session('success') }}</span>
                </div>
            @endif

            {{-- Kriter Özeti Rozetleri --}}
            @php $badges = app(\App\Services\KpiTargetS::class)->getFormattedCriteriaBadges($kpiTarget->criteria_json); @endphp
            @if(!empty($badges))
                <div class="bg-indigo-50/60 border border-indigo-100 rounded-2xl p-4 space-y-2.5">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <span class="w-6 h-6 rounded-lg bg-indigo-600 text-white flex items-center justify-center text-xs font-bold shrink-0">
                                🔍
                            </span>
                            <span class="text-xs font-extrabold text-indigo-950">Bu Hedefe Uygulanan Mikro Kriter Kırılımları:</span>
                        </div>
                        <span class="text-[11px] font-bold text-indigo-600 bg-white px-3 py-0.5 rounded-full border border-indigo-200">
                            {{ count($badges) }} Aktif Filtre
                        </span>
                    </div>
                    <div class="flex flex-wrap items-center gap-2 pt-1">
                        @foreach($badges as $badge)
                            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-lg text-xs font-bold border {{ $badge['bg_class'] }} shadow-2xs">
                                <span>{{ $badge['icon'] }}</span>
                                <span class="opacity-75 font-semibold">{{ $badge['label'] }}:</span>
                                <strong class="font-black">{{ $badge['value'] }}</strong>
                            </span>
                        @endforeach
                    </div>
                </div>
            @endif

            {{-- 4 Temel Metrik Kartı --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                {{-- Hedef --}}
                <div class="bg-white p-5 rounded-2xl border border-gray-100 shadow-xs">
                    <div class="flex items-center justify-between text-gray-500">
                        <span class="text-xs font-bold uppercase tracking-wider">Hedeflenen</span>
                        <span class="p-1.5 rounded-lg bg-gray-50 text-gray-500">🎯</span>
                    </div>
                    <div class="text-2xl font-black text-gray-900 mt-2">
                        {{ number_format($chartData['total_target'], 2, ',', '.') }}
                        <span class="text-xs font-semibold text-gray-500">{{ $chartData['unit'] }}</span>
                    </div>
                    <div class="text-[11px] text-gray-400 mt-1">{{ $kpiTarget->target_year }} Yıllık Toplam</div>
                </div>

                {{-- Gerçekleşen --}}
                <div class="bg-white p-5 rounded-2xl border border-gray-100 shadow-xs">
                    <div class="flex items-center justify-between text-gray-500">
                        <span class="text-xs font-bold uppercase tracking-wider">Gerçekleşen</span>
                        <span class="p-1.5 rounded-lg bg-emerald-50 text-emerald-600">📈</span>
                    </div>
                    <div class="text-2xl font-black text-gray-900 mt-2">
                        {{ number_format($chartData['total_actual'], 2, ',', '.') }}
                        <span class="text-xs font-semibold text-gray-500">{{ $chartData['unit'] }}</span>
                    </div>
                    <div class="text-[11px] text-emerald-600 font-bold mt-1">Onaylanmış Form Kayıtları</div>
                </div>

                {{-- Başarı Oranı --}}
                @php
                    $rate = $chartData['achievement_rate'];
                    if ($rate >= 100) {
                        $rateColor = 'text-emerald-600';
                        $rateBadge = 'bg-emerald-50 text-emerald-700 border-emerald-200';
                    } elseif ($rate >= 70) {
                        $rateColor = 'text-amber-600';
                        $rateBadge = 'bg-amber-50 text-amber-700 border-amber-200';
                    } else {
                        $rateColor = 'text-rose-600';
                        $rateBadge = 'bg-rose-50 text-rose-700 border-rose-200';
                    }
                @endphp
                <div class="bg-white p-5 rounded-2xl border border-gray-100 shadow-xs">
                    <div class="flex items-center justify-between text-gray-500">
                        <span class="text-xs font-bold uppercase tracking-wider">Başarı Oranı</span>
                        <span class="px-2 py-0.5 rounded-full text-xs font-black border {{ $rateBadge }}">
                            %{{ number_format($rate, 1, ',', '.') }}
                        </span>
                    </div>
                    <div class="text-2xl font-black {{ $rateColor }} mt-2">
                        %{{ number_format($rate, 1, ',', '.') }}
                    </div>
                    <div class="w-full bg-gray-100 rounded-full h-1.5 mt-2 overflow-hidden">
                        <div class="h-1.5 rounded-full transition-all duration-700 {{ $rate >= 100 ? 'bg-emerald-500' : ($rate >= 70 ? 'bg-amber-500' : 'bg-rose-500') }}"
                             style="width: {{ min(100, $rate) }}%"></div>
                    </div>
                </div>

                {{-- Kalan Hedef --}}
                <div class="bg-white p-5 rounded-2xl border border-gray-100 shadow-xs">
                    <div class="flex items-center justify-between text-gray-500">
                        <span class="text-xs font-bold uppercase tracking-wider">Kalan Hedef</span>
                        <span class="p-1.5 rounded-lg bg-indigo-50 text-indigo-600">⏳</span>
                    </div>
                    <div class="text-2xl font-black text-gray-900 mt-2">
                        {{ number_format($chartData['remaining_value'], 2, ',', '.') }}
                        <span class="text-xs font-semibold text-gray-500">{{ $chartData['unit'] }}</span>
                    </div>
                    <div class="text-[11px] text-gray-400 mt-1">Hedefe ulaşmak için gereken</div>
                </div>
            </div>

            {{-- İnteraktif Grafik Kartı --}}
            <div class="bg-white rounded-2xl p-6 shadow-xs border border-gray-100 space-y-4" x-data="{ viewMode: 'monthly' }">
                <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3 border-b border-gray-100 pb-4">
                    <div>
                        <h3 class="text-base font-extrabold text-gray-900">Hedef vs Gerçekleşme Trend Analizi</h3>
                        <p class="text-xs text-gray-500 mt-0.5">Aylık hedef miktarları ve fiili veri girişlerinin kıyaslaması</p>
                    </div>

                    {{-- Toggle: Aylık vs Kümülatif --}}
                    <div class="flex items-center gap-1 bg-gray-100 p-1 rounded-xl">
                        <button type="button" @click="viewMode = 'monthly'; renderChart('monthly')"
                            class="px-3 py-1.5 rounded-lg text-xs font-bold transition"
                            :class="viewMode === 'monthly' ? 'bg-white text-indigo-700 shadow-2xs' : 'text-gray-600 hover:text-gray-900'">
                            📊 Aylık Dağılım
                        </button>
                        <button type="button" @click="viewMode = 'cumulative'; renderChart('cumulative')"
                            class="px-3 py-1.5 rounded-lg text-xs font-bold transition"
                            :class="viewMode === 'cumulative' ? 'bg-white text-indigo-700 shadow-2xs' : 'text-gray-600 hover:text-gray-900'">
                            📈 Kümülatif Eğri (Hoshin)
                        </button>
                    </div>
                </div>

                <div class="h-80 w-full relative">
                    <canvas id="kpiProgressChart"></canvas>
                </div>
            </div>

            {{-- 12 Aylık Detay Tablosu --}}
            <div class="bg-white rounded-2xl shadow-xs border border-gray-100 overflow-hidden">
                <div class="p-5 border-b border-gray-100 flex items-center justify-between">
                    <div>
                        <h3 class="text-base font-extrabold text-gray-900">Aylık Gerçekleşme Dökümü</h3>
                        <p class="text-xs text-gray-500 mt-0.5">Her ayın hedefi, gerçekleşen verisi ve başarı yüzdesi</p>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-100 text-sm">
                        <thead>
                            <tr class="bg-gray-50/80">
                                <th class="px-5 py-3 text-left text-xs font-bold text-gray-500 uppercase">Ay</th>
                                <th class="px-5 py-3 text-right text-xs font-bold text-gray-500 uppercase">Aylık Hedef</th>
                                <th class="px-5 py-3 text-right text-xs font-bold text-gray-500 uppercase">Gerçekleşen</th>
                                <th class="px-5 py-3 text-right text-xs font-bold text-gray-500 uppercase">Fark</th>
                                <th class="px-5 py-3 text-center text-xs font-bold text-gray-500 uppercase">Aylık Başarı</th>
                                <th class="px-5 py-3 text-right text-xs font-bold text-gray-500 uppercase">Kümülatif Gerçekleşen</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 bg-white">
                            @for($i = 0; $i < 12; $i++)
                                @php
                                    $monthName = $chartData['labels'][$i];
                                    $tVal = $chartData['target_series'][$i];
                                    $aVal = $chartData['actual_series'][$i];
                                    $diff = $aVal - $tVal;
                                    $mRate = $tVal > 0 ? round(($aVal / $tVal) * 100, 1) : 0;
                                    $cumA = $chartData['cumulative_actual'][$i];
                                @endphp
                                <tr class="hover:bg-slate-50/60 transition">
                                    <td class="px-5 py-3.5 font-bold text-gray-900 text-xs">
                                        {{ $monthName }}
                                    </td>
                                    <td class="px-5 py-3.5 text-right font-medium text-gray-700">
                                        {{ number_format($tVal, 2, ',', '.') }} {{ $chartData['unit'] }}
                                    </td>
                                    <td class="px-5 py-3.5 text-right font-bold {{ $aVal > 0 ? 'text-indigo-600' : 'text-gray-400' }}">
                                        {{ number_format($aVal, 2, ',', '.') }} {{ $chartData['unit'] }}
                                    </td>
                                    <td class="px-5 py-3.5 text-right font-semibold text-xs {{ $diff >= 0 ? 'text-emerald-600' : 'text-rose-600' }}">
                                        {{ $diff >= 0 ? '+' : '' }}{{ number_format($diff, 2, ',', '.') }}
                                    </td>
                                    <td class="px-5 py-3.5 text-center">
                                        @if($tVal > 0)
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-extrabold {{ $mRate >= 100 ? 'bg-emerald-50 text-emerald-700' : ($mRate >= 70 ? 'bg-amber-50 text-amber-700' : 'bg-gray-100 text-gray-500') }}">
                                                %{{ number_format($mRate, 1, ',', '.') }}
                                            </span>
                                        @else
                                            <span class="text-xs text-gray-400">-</span>
                                        @endif
                                    </td>
                                    <td class="px-5 py-3.5 text-right font-extrabold text-gray-900">
                                        {{ number_format($cumA, 2, ',', '.') }} {{ $chartData['unit'] }}
                                    </td>
                                </tr>
                            @endfor
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Eğer bağlı alt hedefler varsa (Hiyerarşik Alt Hedefler) --}}
            @if($kpiTarget->childTargets->count() > 0)
                <div class="bg-white rounded-2xl shadow-xs border border-gray-100 p-6 space-y-4">
                    <h3 class="text-base font-extrabold text-gray-900 border-b border-gray-100 pb-3 flex items-center gap-2">
                        <span>🌱</span> Bu Hedefi Besleyen Alt Hedefler (Hiyerarşik Kümülasyon)
                    </h3>

                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                        @foreach($kpiTarget->childTargets as $child)
                            @php
                                $cReal = $child->latest_realization;
                                $cActual = $cReal ? floatval($cReal->actual_value) : 0;
                                $cRate = $cReal ? floatval($cReal->achievement_rate) : 0;
                            @endphp
                            <div class="p-4 rounded-xl border border-gray-100 bg-slate-50/50 hover:bg-slate-50 transition space-y-2">
                                <div class="flex items-center justify-between">
                                    <a href="{{ route('kpi-targets.progress', $child->id) }}" class="font-bold text-xs text-gray-900 hover:text-indigo-600 line-clamp-1">
                                        {{ $child->title }}
                                    </a>
                                    <span class="text-[10px] font-extrabold px-1.5 py-0.5 rounded-full {{ $cRate >= 100 ? 'bg-emerald-100 text-emerald-800' : 'bg-indigo-100 text-indigo-800' }}">
                                        %{{ number_format($cRate, 1, ',', '.') }}
                                    </span>
                                </div>
                                <div class="text-[11px] text-gray-500">
                                    Sahip: <strong>{{ $child->assignee_name }}</strong>
                                </div>
                                <div class="text-xs font-bold text-gray-800 flex justify-between">
                                    <span>{{ number_format($cActual, 2, ',', '.') }} / {{ number_format($child->target_value, 2, ',', '.') }} {{ $child->unit }}</span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

        </div>
    </div>

    <script>
    const chartLabels = {!! json_encode($chartData['labels']) !!};
    const targetMonthly = {!! json_encode($chartData['target_series']) !!};
    const actualMonthly = {!! json_encode($chartData['actual_series']) !!};
    const targetCum = {!! json_encode($chartData['cumulative_target']) !!};
    const actualCum = {!! json_encode($chartData['cumulative_actual']) !!};
    const unit = "{{ $chartData['unit'] }}";

    let kpiChart = null;

    function renderChart(mode) {
        const ctx = document.getElementById('kpiProgressChart').getContext('2d');

        if (kpiChart) {
            kpiChart.destroy();
        }

        if (mode === 'monthly') {
            kpiChart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: chartLabels,
                    datasets: [
                        {
                            label: 'Aylık Hedef (' + unit + ')',
                            data: targetMonthly,
                            backgroundColor: 'rgba(203, 213, 225, 0.65)',
                            borderColor: 'rgba(148, 163, 184, 1)',
                            borderWidth: 1.5,
                            borderRadius: 6,
                        },
                        {
                            label: 'Gerçekleşen Veri (' + unit + ')',
                            data: actualMonthly,
                            backgroundColor: 'rgba(99, 102, 241, 0.85)',
                            borderColor: 'rgba(79, 70, 229, 1)',
                            borderWidth: 1.5,
                            borderRadius: 6,
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { position: 'top' },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return context.dataset.label + ': ' + new Intl.NumberFormat('tr-TR').format(context.raw) + ' ' + unit;
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: { color: 'rgba(241, 245, 249, 1)' }
                        },
                        x: {
                            grid: { display: false }
                        }
                    }
                }
            });
        } else {
            kpiChart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: chartLabels,
                    datasets: [
                        {
                            label: 'Kümülatif Hedef (' + unit + ')',
                            data: targetCum,
                            borderColor: 'rgba(148, 163, 184, 1)',
                            backgroundColor: 'transparent',
                            borderDash: [5, 5],
                            borderWidth: 2,
                            tension: 0.2,
                        },
                        {
                            label: 'Kümülatif Gerçekleşen (' + unit + ')',
                            data: actualCum,
                            borderColor: 'rgba(16, 185, 129, 1)',
                            backgroundColor: 'rgba(16, 185, 129, 0.1)',
                            fill: true,
                            borderWidth: 2.5,
                            tension: 0.3,
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { position: 'top' },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return context.dataset.label + ': ' + new Intl.NumberFormat('tr-TR').format(context.raw) + ' ' + unit;
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: { color: 'rgba(241, 245, 249, 1)' }
                        },
                        x: {
                            grid: { display: false }
                        }
                    }
                }
            });
        }
    }

    document.addEventListener('DOMContentLoaded', function() {
        renderChart('monthly');
    });
    </script>
</x-app-layout>

