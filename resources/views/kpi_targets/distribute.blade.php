@section('title', 'Aylık Hedef Dağıtımı')
<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            <div>
                <h2 class="font-extrabold text-2xl text-gray-900 tracking-tight flex items-center gap-2">
                    <a href="{{ route('kpi-targets.index') }}" class="text-gray-400 hover:text-gray-600 transition">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                    </a>
                    {{ __('12 Aylık Hedef Dağıtımı') }}
                </h2>
                <p class="text-sm text-gray-500 mt-1">
                    <strong class="text-gray-800">{{ $kpiTarget->title }}</strong> ({{ $kpiTarget->target_year }} Yılı)
                </p>
            </div>

            <div class="flex items-center gap-2">
                <form action="{{ route('kpi-targets.distribute.evenly', $kpiTarget->id) }}" method="POST">
                    @csrf
                    <button type="submit" class="px-4 py-2 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 font-bold text-xs rounded-xl border border-indigo-200 shadow-2xs transition">
                        ⚡ 12 Aya Eşit Dağıt ({{ number_format($kpiTarget->target_value / 12, 2, ',', '.') }} {{ $kpiTarget->unit }}/ay)
                    </button>
                </form>
            </div>
        </div>
    </x-slot>

    <div class="py-8" x-data="kpiDistribute({{ $kpiTarget->target_value }})">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            @if(session('success'))
                <div class="p-4 text-sm text-emerald-800 bg-emerald-50 border border-emerald-200 rounded-xl flex items-center shadow-xs" role="alert">
                    <svg class="w-5 h-5 mr-2 text-emerald-500 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg>
                    <span>{{ session('success') }}</span>
                </div>
            @endif

            {{-- Hedef Özeti & Canlı Toplam Karşılaştırması --}}
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div class="bg-white p-5 rounded-2xl border border-gray-100 shadow-xs">
                    <div class="text-xs font-bold text-gray-500 uppercase tracking-wider">Yıllık Toplam Hedef</div>
                    <div class="text-2xl font-black text-gray-900 mt-1">
                        {{ number_format($kpiTarget->target_value, 2, ',', '.') }} <span class="text-sm font-semibold text-gray-500">{{ $kpiTarget->unit }}</span>
                    </div>
                    <div class="text-[11px] text-gray-400 mt-0.5">{{ $kpiTarget->assignee_name }}</div>
                </div>

                <div class="bg-white p-5 rounded-2xl border border-gray-100 shadow-xs">
                    <div class="text-xs font-bold text-gray-500 uppercase tracking-wider">Aylara Dağıtılan Toplam</div>
                    <div class="text-2xl font-black mt-1" :class="isMatched ? 'text-emerald-600' : 'text-amber-600'">
                        <span x-text="formatNumber(currentSum)"></span>
                        <span class="text-sm font-semibold text-gray-500">{{ $kpiTarget->unit }}</span>
                    </div>
                    <div class="text-[11px] mt-0.5" :class="isMatched ? 'text-emerald-500 font-bold' : 'text-amber-500'">
                        <span x-show="isMatched">✓ Yıllık hedefle tam örtüşüyor (%100)</span>
                        <span x-show="!isMatched">Fark: <strong x-text="formatNumber(difference)"></strong> {{ $kpiTarget->unit }}</span>
                    </div>
                </div>

                <div class="bg-white p-5 rounded-2xl border border-gray-100 shadow-xs">
                    <div class="text-xs font-bold text-gray-500 uppercase tracking-wider">Şablon / Kırılım</div>
                    <div class="text-sm font-bold text-gray-900 mt-1 line-clamp-1">
                        {{ $kpiTarget->template->name }}
                    </div>
                    <div class="text-[11px] text-indigo-600 font-mono mt-0.5">{{ $kpiTarget->targetField->label }}</div>
                </div>
            </div>

            {{-- Dağıtım Formu --}}
            <form action="{{ route('kpi-targets.distribute.store', $kpiTarget->id) }}" method="POST" class="bg-white rounded-2xl p-6 shadow-xs border border-gray-100 space-y-6">
                @csrf

                <div class="border-b border-gray-100 pb-3 flex items-center justify-between">
                    <div>
                        <h3 class="text-base font-extrabold text-gray-900">12 Aylık Hedef Dağılım Matrisi</h3>
                        <p class="text-xs text-gray-500 mt-0.5">Her ay için beklenen hedef miktarını giriniz</p>
                    </div>

                    <div class="flex items-center gap-2">
                        <button type="button" @click="distributeRandomlyLocal()" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-bold text-amber-700 bg-amber-50 hover:bg-amber-100 border border-amber-200 rounded-lg transition shadow-2xs cursor-pointer">
                            <svg class="w-3.5 h-3.5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"></path>
                            </svg>
                            <span>🎲 Rastgele Dağıt</span>
                        </button>
                        <button type="button" @click="distributeEvenlyLocal()" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-bold text-indigo-700 bg-indigo-50 hover:bg-indigo-100 border border-indigo-200 rounded-lg transition shadow-2xs cursor-pointer">
                            <svg class="w-3.5 h-3.5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                            </svg>
                            <span>⚖️ Değerleri Eşit Dağıt</span>
                        </button>
                    </div>
                </div>

                @php
                    $monthNames = [
                        1 => 'Ocak', 2 => 'Şubat', 3 => 'Mart', 4 => 'Nisan',
                        5 => 'Mayıs', 6 => 'Haziran', 7 => 'Temmuz', 8 => 'Ağustos',
                        9 => 'Eylül', 10 => 'Ekim', 11 => 'Kasım', 12 => 'Aralık'
                    ];
                @endphp

                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
                    @for($m = 1; $m <= 12; $m++)
                        @php
                            $val = $periods[$m]->period_target_value ?? 0;
                        @endphp
                        <div class="p-3.5 rounded-xl border border-gray-200 bg-slate-50/50 hover:bg-slate-50 transition space-y-2">
                            <div class="flex items-center justify-between">
                                <span class="font-extrabold text-xs text-gray-800 flex items-center gap-1.5">
                                    <span class="w-5 h-5 rounded-md bg-indigo-100 text-indigo-700 flex items-center justify-center text-[10px] font-black">
                                        {{ $m }}
                                    </span>
                                    {{ $monthNames[$m] }}
                                </span>
                                <span class="text-[10px] text-gray-400 font-mono" x-text="getPercent({{ $m }}) + '%'"></span>
                            </div>

                            <div class="relative">
                                <input type="hidden"
                                    name="months[{{ $m }}]"
                                    :value="months[{{ $m }}]">
                                <input type="text"
                                    inputmode="decimal"
                                    :value="displayMonths[{{ $m }}]"
                                    @input="handleMonthInput($event, {{ $m }})"
                                    @keydown="handleMonthKeydown($event, {{ $m }})"
                                    @blur="handleMonthBlur($event, {{ $m }})"
                                    class="w-full text-sm font-bold rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 pr-12 text-right bg-white">
                                <span class="absolute right-3 top-2.5 text-xs text-gray-400 font-semibold pointer-events-none">
                                    {{ $kpiTarget->unit }}
                                </span>
                            </div>
                        </div>
                    @endfor
                </div>

                {{-- Butonlar --}}
                <div class="flex items-center justify-between pt-4 border-t border-gray-100">
                    <a href="{{ route('kpi-targets.index') }}" class="px-5 py-2.5 rounded-xl border border-gray-200 text-gray-600 hover:bg-gray-50 text-sm font-semibold transition">
                        ← Hedef Listesine Dön
                    </a>

                    <div class="flex items-center gap-3">
                        <button type="submit" class="px-6 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-sm rounded-xl shadow-md transition hover:shadow-lg">
                            Aylık Dağıtımı Kaydet
                        </button>
                    </div>
                </div>
            </form>

        </div>
    </div>

    <script>
    function kpiDistribute(targetTotal) {
        return {
            targetTotal: targetTotal,
            months: {
                @for($m = 1; $m <= 12; $m++)
                    {{ $m }}: {{ floatval($periods[$m]->period_target_value ?? 0) }},
                @endfor
            },
            displayMonths: {},
            currentSum: 0,
            difference: 0,
            isMatched: false,
            init() {
                for (let i = 1; i <= 12; i++) {
                    this.displayMonths[i] = this.formatDisplay(this.months[i]);
                }
                this.calculateSum();
            },
            formatDisplay(val) {
                if (val === null || val === undefined || val === '' || isNaN(val)) return '0';
                let num = parseFloat(val);
                if (num === 0) return '0';
                return new Intl.NumberFormat('tr-TR', { minimumFractionDigits: 0, maximumFractionDigits: 2 }).format(num);
            },
            handleMonthKeydown(e, m) {
                if (e.key === '.' && !e.target.value.includes(',')) {
                    e.preventDefault();
                    let start = e.target.selectionStart;
                    let end = e.target.selectionEnd;
                    let currentVal = e.target.value;
                    e.target.value = currentVal.substring(0, start) + ',' + currentVal.substring(end);
                    e.target.setSelectionRange(start + 1, start + 1);
                    this.handleMonthInput(e, m);
                }
            },
            handleMonthInput(e, m) {
                let input = e.target;
                let val = input.value;
                let cursor = input.selectionStart;

                let digitsBeforeCursor = val.slice(0, cursor).replace(/\D/g, '').length;
                let hadCommaBefore = val.slice(0, cursor).includes(',');

                let cleaned = val.replace(/[^0-9,]/g, '');
                let parts = cleaned.split(',');
                if (parts.length > 2) {
                    parts = [parts[0], parts.slice(1).join('')];
                }

                let intPart = parts[0].replace(/\D/g, '');
                let formattedInt = intPart.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
                let hasComma = parts.length > 1;
                let decPart = hasComma ? ',' + parts[1].replace(/\D/g, '').slice(0, 2) : '';

                let display = formattedInt + decPart;
                this.displayMonths[m] = display;
                input.value = display;

                let raw = 0;
                if (intPart) {
                    raw = parseFloat(hasComma && parts[1] !== '' ? intPart + '.' + parts[1].replace(/\D/g, '').slice(0, 2) : intPart);
                }
                this.months[m] = isNaN(raw) ? 0 : raw;
                this.calculateSum();

                this.$nextTick(() => {
                    let newPos = 0;
                    let digitsSeen = 0;
                    for (let i = 0; i < display.length; i++) {
                        if (display[i] === ',') {
                            if (hadCommaBefore) {
                                newPos = i + 1;
                                break;
                            }
                        }
                        if (/\d/.test(display[i])) {
                            digitsSeen++;
                        }
                        if (digitsSeen === digitsBeforeCursor) {
                            newPos = i + 1;
                            break;
                        }
                    }
                    if (newPos === 0 && display.length > 0) {
                        newPos = display.length;
                    }
                    input.setSelectionRange(newPos, newPos);
                });
            },
            handleMonthBlur(e, m) {
                if (String(this.displayMonths[m] || '').endsWith(',')) {
                    this.displayMonths[m] = this.displayMonths[m].slice(0, -1);
                    e.target.value = this.displayMonths[m];
                }
            },
            calculateSum() {
                let sum = 0;
                for (let i = 1; i <= 12; i++) {
                    sum += parseFloat(this.months[i] || 0);
                }
                this.currentSum = Math.round(sum * 100) / 100;
                this.difference = Math.round((this.targetTotal - this.currentSum) * 100) / 100;
                this.isMatched = Math.abs(this.difference) < 0.05;
            },
            getPercent(month) {
                if (this.targetTotal <= 0) return 0;
                const val = parseFloat(this.months[month] || 0);
                return Math.round((val / this.targetTotal) * 1000) / 10;
            },
            distributeEvenlyLocal() {
                if (this.targetTotal <= 0) return;
                const perMonth = Math.floor((this.targetTotal / 12) * 100) / 100;
                let running = 0;
                for (let i = 1; i <= 11; i++) {
                    this.months[i] = perMonth;
                    this.displayMonths[i] = this.formatDisplay(perMonth);
                    running += perMonth;
                }
                const remainder = Math.round((this.targetTotal - running) * 100) / 100;
                this.months[12] = remainder;
                this.displayMonths[12] = this.formatDisplay(remainder);
                this.calculateSum();
            },
            distributeRandomlyLocal() {
                if (this.targetTotal <= 0) return;

                // Hedef büyüklüğüne göre yuvarlama adımı (büyük rakamlar için temiz binlik yuvarlama)
                let step = 0.01;
                if (this.targetTotal >= 1000000) step = 1000;
                else if (this.targetTotal >= 100000) step = 500;
                else if (this.targetTotal >= 10000) step = 100;
                else if (this.targetTotal >= 1000) step = 10;
                else if (this.targetTotal >= 100) step = 1;

                let weights = [];
                let sumWeights = 0;
                for (let i = 0; i < 12; i++) {
                    // Doğal aylık dalgalanma (0.70 ile 1.30 arası rastgele katsayı)
                    let w = 0.70 + Math.random() * 0.60;
                    weights.push(w);
                    sumWeights += w;
                }

                let runningSum = 0;
                for (let i = 1; i <= 11; i++) {
                    let rawVal = (weights[i - 1] / sumWeights) * this.targetTotal;
                    let rounded = Math.round(rawVal / step) * step;
                    if (step < 1) rounded = Math.round(rounded * 100) / 100;
                    this.months[i] = rounded;
                    this.displayMonths[i] = this.formatDisplay(rounded);
                    runningSum += rounded;
                }

                // 12. ay kalan farkı kapatır ve toplamın yıllık hedefe %100 tam oturmasını sağlar
                let remainder = Math.round((this.targetTotal - runningSum) * 100) / 100;
                if (remainder <= 0) {
                    let diff = Math.abs(remainder) + (step >= 1 ? step : 1);
                    this.months[11] = Math.round((this.months[11] - diff) * 100) / 100;
                    this.displayMonths[11] = this.formatDisplay(this.months[11]);
                    remainder += diff;
                }
                this.months[12] = remainder;
                this.displayMonths[12] = this.formatDisplay(remainder);

                this.calculateSum();
            },
            formatNumber(num) {
                return new Intl.NumberFormat('tr-TR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(num);
            }
        }
    }
    </script>
</x-app-layout>

