@section('title', 'Veri Detayı')
<style>
    body.cursor-wait,
    body.cursor-wait * {
        cursor: wait !important;
    }

    .btn-loading {
        pointer-events: none !important;
        opacity: 0.70 !important;
        cursor: wait !important;
    }

    .pill-sender-indigo {
        background-color: #e0e7ff !important;
        color: #3730a3 !important;
        border: 1px solid #a5b4fc !important;
    }

    .pill-date-sky {
        background-color: #e0f2fe !important;
        color: #075985 !important;
        border: 1px solid #7dd3fc !important;
    }

    .pill-approver-emerald {
        background-color: #059669 !important;
        color: #ffffff !important;
        border: 1px solid #047857 !important;
    }

    .pill-wait-rose {
        background-color: #e11d48 !important;
        color: #ffffff !important;
        border: 1px solid #be123c !important;
    }

    .pill-elapsed-indigo {
        background-color: #4f46e5 !important;
        color: #ffffff !important;
        border: 1px solid #4338ca !important;
    }

    @keyframes revisionBadgeBlink {

        0%,
        100% {
            opacity: 1;
            background-color: #fef3c7;
            border-color: #f59e0b;
            box-shadow: 0 0 0 0 rgba(245, 158, 11, 0.45);
        }

        50% {
            opacity: 0.45;
            background-color: #fde68a;
            border-color: #d97706;
            box-shadow: 0 0 10px 2px rgba(245, 158, 11, 0.55);
        }
    }

    .revision-blink-badge {
        animation: revisionBadgeBlink 1.15s ease-in-out infinite;
    }
</style>
<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3" x-data>
            <div>
                <h2 class="font-extrabold text-xl sm:text-2xl text-gray-900 tracking-tight">
                    {{ $formEntry->template->name }} {{ __('Detayları') }}
                </h2>
                <p class="text-sm text-gray-500 mt-0.5">{{ $formEntry->entry_date->translatedFormat('d F Y, l') }}</p>
            </div>

            <div class="flex items-center gap-3 flex-wrap">
                @if(empty($pendingRevision))
                    <div class="text-sm">
                        @if($formEntry->status === 'draft')
                            <span
                                class="inline-flex items-center px-3 py-1 font-semibold rounded-full bg-gray-100 text-gray-800 border border-gray-200 text-xs">
                                <span class="w-1.5 h-1.5 bg-gray-400 rounded-full mr-1.5"></span>{{ __('Taslak') }}
                            </span>
                        @elseif($formEntry->status === 'submitted')
                            <span
                                class="inline-flex items-center px-3 py-1 font-semibold rounded-full bg-amber-50 text-amber-700 border border-amber-200 text-xs">
                                <span
                                    class="w-1.5 h-1.5 bg-amber-500 rounded-full mr-1.5 animate-pulse"></span>{{ __('Onay Bekliyor') }}
                            </span>
                        @elseif($formEntry->status === 'approved')
                            <span
                                class="inline-flex items-center px-3 py-1 font-semibold rounded-full bg-emerald-50 text-emerald-700 border border-emerald-200 text-xs">
                                <span class="w-1.5 h-1.5 bg-emerald-500 rounded-full mr-1.5"></span>{{ __('Onaylandı') }}
                            </span>
                        @elseif($formEntry->status === 'rejected')
                            <span
                                class="inline-flex items-center px-3 py-1 font-semibold rounded-full bg-red-50 text-red-700 border border-red-200 text-xs">
                                <span class="w-1.5 h-1.5 bg-red-500 rounded-full mr-1.5"></span>{{ __('Reddedildi') }}
                            </span>
                        @endif
                    </div>
                @endif

                @if(!empty($pendingRevision))
                    <button type="button" disabled
                        class="revision-blink-badge inline-flex items-center px-3.5 py-2 bg-amber-100 text-amber-800 border border-amber-300 text-xs font-bold rounded-lg shadow-sm gap-1.5 cursor-not-allowed select-none"
                        title="{{ __('Düzenleme Talebi Bekliyor') }}">
                        <svg class="w-4 h-4 animate-spin text-amber-700" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4">
                            </circle>
                            <path class="opacity-75" fill="currentColor"
                                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                            </path>
                        </svg>
                        <span>{{ __('Düzenleme Talebi Bekliyor') }}</span>
                    </button>
                @elseif(!empty($canRequestRevision) && $canRequestRevision)
                    <button type="button"
                        onclick="if(this.dataset.clicked) return; this.dataset.clicked = 'true'; this.classList.add('btn-loading'); document.body.classList.add('cursor-wait'); window.dispatchEvent(new CustomEvent('open-revision-modal')); setTimeout(() => { delete this.dataset.clicked; this.classList.remove('btn-loading'); document.body.classList.remove('cursor-wait'); }, 800);"
                        @click="$dispatch('open-revision-modal')"
                        class="inline-flex items-center px-3.5 py-2 bg-amber-500 hover:bg-amber-600 active:scale-95 text-white text-xs font-bold rounded-lg shadow-sm transition gap-1.5 cursor-pointer"
                        title="{{ __('Düzenleme Talep Et (Revizyon)') }}">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z">
                            </path>
                        </svg>
                        <span>{{ __('Düzenleme Talep Et (Revizyon)') }}</span>
                    </button>
                @endif

                @if(!empty($canRevert) && $canRevert)
                    <button type="button"
                        onclick="if(this.dataset.clicked) return; this.dataset.clicked = 'true'; this.classList.add('btn-loading'); document.body.classList.add('cursor-wait'); window.dispatchEvent(new CustomEvent('open-revert-modal')); setTimeout(() => { delete this.dataset.clicked; this.classList.remove('btn-loading'); document.body.classList.remove('cursor-wait'); }, 800);"
                        @click="$dispatch('open-revert-modal')"
                        class="inline-flex items-center px-3.5 py-2 bg-slate-700 hover:bg-slate-800 active:scale-95 text-white text-xs font-bold rounded-lg shadow-sm transition gap-1.5 cursor-pointer"
                        title="{{ __('Onayı Geri Al') }}">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"></path>
                        </svg>
                        <span>{{ __('Onayı Geri Al') }}</span>
                    </button>
                @endif

                @if((!empty($isSelf) && $isSelf) || !empty($isSuperAdmin) || app(\App\Services\PermissionS::class)->hasPermission(Auth::user(), 'exports.manage'))
                    <button type="button"
                        onclick="if(this.dataset.clicked) return; this.dataset.clicked = 'true'; this.classList.add('btn-loading'); document.body.classList.add('cursor-wait'); let origHtml = this.innerHTML; this.innerHTML = '<svg class=\'animate-spin w-4 h-4 mr-1.5 inline-block text-white\' fill=\'none\' viewBox=\'0 0 24 24\'><circle class=\'opacity-25\' cx=\'12\' cy=\'12\' r=\'10\' stroke=\'currentColor\' stroke-width=\'4\'></circle><path class=\'opacity-75\' fill=\'currentColor\' d=\'M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z\'></path></svg> <span>İndiriliyor...</span>'; if(document.getElementById('exportExcelBtn')) document.getElementById('exportExcelBtn').click(); setTimeout(() => { this.innerHTML = origHtml; this.classList.remove('btn-loading'); document.body.classList.remove('cursor-wait'); delete this.dataset.clicked; }, 3500);"
                        class="inline-flex items-center px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-semibold uppercase tracking-widest rounded-lg shadow-sm transition cursor-pointer gap-1.5"
                        title="{{ __('Excel Olarak İndir') }}">
                        <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
                            </path>
                        </svg>
                        <span>{{ __('Excel Olarak İndir') }}</span>
                    </button>
                @endif
            </div>
        </div>
    </x-slot>

    <div class="py-6 sm:py-10">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

            @if($formEntry->is_consolidated)
                <div
                    class="mb-6 bg-gradient-to-r from-indigo-900 via-indigo-800 to-slate-900 text-white rounded-2xl p-5 shadow-lg border border-indigo-700/50">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                        <div class="space-y-1">
                            <div class="flex items-center gap-2">
                                <span
                                    class="px-2.5 py-0.5 rounded-full text-[11px] font-black uppercase tracking-wider bg-amber-400 text-slate-900">
                                    🌟 Birleştirilmiş Konsolide Form
                                </span>
                                <span class="text-xs text-indigo-200">Toplam {{ $formEntry->children->count() }} Parça
                                    &bull; {{ $formEntry->rows->count() }} Satır</span>
                            </div>
                            <h3 class="text-base sm:text-lg font-extrabold text-white">
                                {{ $formEntry->title }}
                            </h3>
                            <p class="text-xs text-indigo-200/90">
                                Bu kayıt, aşağıdaki bağımsız parçalı formların birleştirilmesiyle oluşturulmuştur. Onaylama
                                veya revizyon işlemlerinizi ister bu ana formdan ister parçalardan bağımsız
                                yürütebilirsiniz.
                            </p>
                        </div>

                        <div class="flex items-center gap-2 shrink-0 flex-wrap">

                            @if((Auth::id() === $formEntry->user_id || Auth::user()->id === 1 || (Auth::user()->role && Auth::user()->role->slug === 'sistem_yoneticisi')) && (Auth::user()->id === 1 || (Auth::user()->role && Auth::user()->role->slug === 'sistem_yoneticisi') || app(\App\Services\PermissionS::class)->hasPermission(Auth::user(), 'form_entries.consolidate')))
                                @if(!empty($addableParts) && $addableParts->isNotEmpty())
                                    <div x-data="{ openAddParts: false, selectedAddParts: [], submittingAdd: false }"
                                        class="inline-block text-left shrink-0">
                                        <button type="button" @click="openAddParts = true"
                                            class="px-3.5 py-2 bg-amber-400 hover:bg-amber-300 text-slate-900 text-xs font-black rounded-xl transition shadow-xs flex items-center gap-1.5 cursor-pointer">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                                    d="M12 4v16m8-8H4" />
                                            </svg>
                                            <span>Parça Ekle ({{ $addableParts->count() }})</span>
                                        </button>

                                        <!-- Mevcut Konsolide Forma Ek Parça Ekleme Modalı -->
                                        <div x-show="openAddParts" style="display: none;"
                                            class="fixed inset-0 z-50 overflow-y-auto whitespace-normal"
                                            aria-labelledby="add-parts-modal-title" role="dialog" aria-modal="true">
                                            <div
                                                class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                                                <div x-show="openAddParts" @click="if(!submittingAdd) openAddParts = false"
                                                    x-transition.opacity
                                                    class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs transition-opacity"
                                                    aria-hidden="true"></div>
                                                <span class="hidden sm:inline-block sm:align-middle sm:h-screen"
                                                    aria-hidden="true">&#8203;</span>
                                                <div x-show="openAddParts" x-transition
                                                    class="inline-block align-bottom bg-white rounded-3xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-xl sm:w-full border border-slate-100 whitespace-normal">
                                                    <form action="{{ route('form-entries.consolidate') }}" method="POST"
                                                        @submit="if(submittingAdd || selectedAddParts.length < 1) { $event.preventDefault(); return false; } submittingAdd = true; document.body.classList.add('cursor-wait');">
                                                        @csrf
                                                        <div class="p-6 space-y-5">
                                                            <div class="flex items-start justify-between gap-4">
                                                                <div>
                                                                    <h3 class="text-lg font-black text-slate-900"
                                                                        id="add-parts-modal-title">
                                                                        #{{ $formEntry->id }} Konsolide Forma Parça Ekle
                                                                    </h3>
                                                                    <p class="text-xs text-slate-500 mt-0.5">
                                                                        Yeni bir konsolide form oluşturulmaz; seçtiğiniz parçalar
                                                                        doğrudan mevcut <strong>#{{ $formEntry->id }}</strong>
                                                                        numaralı forma eklenir.
                                                                    </p>
                                                                </div>
                                                                <button type="button"
                                                                    @click="if(!submittingAdd) openAddParts = false"
                                                                    class="text-slate-400 hover:text-slate-600 p-1 rounded-lg cursor-pointer">
                                                                    <svg class="w-5 h-5" fill="none" stroke="currentColor"
                                                                        viewBox="0 0 24 24">
                                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                                            stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                                                    </svg>
                                                                </button>
                                                            </div>

                                                            <div class="flex items-center justify-between text-xs">
                                                                <span class="font-bold text-slate-700">Eklenecek Parçaları
                                                                    Seçiniz:</span>
                                                                <span class="font-extrabold text-indigo-600"
                                                                    x-text="selectedAddParts.length + ' parça seçildi'"></span>
                                                            </div>

                                                            <div
                                                                class="max-h-72 overflow-y-auto space-y-2 pr-1 border border-slate-200 rounded-2xl p-2 bg-slate-50/50">
                                                                @php
                                                                    $totalAddable = $addableParts->count();
                                                                @endphp
                                                                @foreach($addableParts as $partItem)
                                                                    @php
                                                                        $freshness = $totalAddable > 1 ? (1 - ($loop->index / ($totalAddable - 1))) : 1;
                                                                        $bgAlpha = number_format(0.02 + ($freshness * 0.16), 3, '.', '');
                                                                        $borderAlpha = number_format(0.14 + ($freshness * 0.42), 3, '.', '');
                                                                        $leftBorderAlpha = number_format(0.18 + ($freshness * 0.82), 3, '.', '');
                                                                    @endphp
                                                                    <label
                                                                        class="flex items-center justify-between p-3 rounded-xl border hover:shadow-xs transition cursor-pointer"
                                                                        style="background-color: rgba(79, 70, 229, {{ $bgAlpha }}); border-color: rgba(79, 70, 229, {{ $borderAlpha }}); border-left-width: 4px; border-left-color: rgba(79, 70, 229, {{ $leftBorderAlpha }});">
                                                                        <div class="flex items-center gap-3">
                                                                            <input type="checkbox" name="entry_ids[]"
                                                                                value="{{ $partItem->id }}" x-model="selectedAddParts"
                                                                                class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500 w-4 h-4">
                                                                            <div>
                                                                                <span
                                                                                    class="px-2 py-0.5 rounded-md text-[11px] font-extrabold bg-amber-100 text-amber-800 border border-amber-300">
                                                                                    ⚡
                                                                                    {{ $partItem->partition_value ?: ('Parça #' . $partItem->id) }}
                                                                                </span>
                                                                                <div
                                                                                    class="text-[11px] text-slate-600 mt-1 flex flex-wrap items-center gap-x-2 gap-y-0.5">
                                                                                    <span>#{{ $partItem->id }}</span>
                                                                                    <span>&bull;</span>
                                                                                    <span>{{ $partItem->rows->count() }} Satır</span>
                                                                                    <span>&bull;</span>
                                                                                    <span>{{ $partItem->created_at ? $partItem->created_at->format('d.m.Y H:i') : $partItem->entry_date->format('d.m.Y') }}</span>
                                                                                </div>
                                                                            </div>
                                                                        </div>
                                                                        <div>
                                                                            @if($partItem->status === 'approved')
                                                                                <span
                                                                                    class="text-[10px] font-bold bg-emerald-100 text-emerald-800 border border-emerald-300 px-2 py-0.5 rounded-full">Onaylandı</span>
                                                                            @elseif($partItem->status === 'submitted')
                                                                                <span
                                                                                    class="text-[10px] font-bold bg-blue-100 text-blue-800 border border-blue-300 px-2 py-0.5 rounded-full">Onay
                                                                                    Bekliyor</span>
                                                                            @else
                                                                                <span
                                                                                    class="text-[10px] font-bold bg-slate-100 text-slate-700 border border-slate-300 px-2 py-0.5 rounded-full">Taslak</span>
                                                                            @endif
                                                                        </div>
                                                                    </label>
                                                                @endforeach
                                                            </div>
                                                        </div>
                                                        <div
                                                            class="bg-slate-50 px-6 py-4 border-t border-slate-100 flex items-center justify-end gap-3">
                                                            <button type="button" @click="if(!submittingAdd) openAddParts = false"
                                                                :disabled="submittingAdd"
                                                                class="px-4 py-2 rounded-xl border border-slate-200 text-xs font-bold text-slate-700 hover:bg-white transition cursor-pointer">
                                                                İptal
                                                            </button>
                                                            <button type="submit"
                                                                :disabled="submittingAdd || selectedAddParts.length < 1"
                                                                :class="(submittingAdd || selectedAddParts.length < 1) ? 'opacity-50 cursor-not-allowed bg-indigo-400' : 'bg-indigo-600 hover:bg-indigo-700'"
                                                                class="px-5 py-2 rounded-xl text-xs font-black text-white shadow-sm transition inline-flex items-center gap-1.5 cursor-pointer">
                                                                <span
                                                                    x-text="submittingAdd ? 'Ekleniyor...' : 'Seçilen Parçaları #{{ $formEntry->id }}\'ye Ekle'">Seçilen
                                                                    Parçaları #{{ $formEntry->id }}'ye Ekle</span>
                                                            </button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endif

                                <div x-data="{ openDissolve: false }" class="inline-block text-left shrink-0">
                                    <button type="button" @click="openDissolve = true"
                                        class="px-3.5 py-2 bg-white/10 hover:bg-rose-600 border border-white/20 text-white text-xs font-bold rounded-xl transition shadow-xs flex items-center gap-1.5 cursor-pointer">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                                        </svg>
                                        <span>Konsolidasyonu Dağıt</span>
                                    </button>

                                    <!-- Dağıtma Onay Modalı -->
                                    <div x-show="openDissolve" style="display: none;"
                                        class="fixed inset-0 z-50 overflow-y-auto whitespace-normal"
                                        aria-labelledby="dissolve-modal-show-title" role="dialog" aria-modal="true">
                                        <div
                                            class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                                            <div x-show="openDissolve" @click="openDissolve = false" x-transition.opacity
                                                class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs transition-opacity"
                                                aria-hidden="true"></div>
                                            <span class="hidden sm:inline-block sm:align-middle sm:h-screen"
                                                aria-hidden="true">&#8203;</span>
                                            <div x-show="openDissolve" x-transition
                                                class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full border border-slate-100 whitespace-normal">
                                                <div class="bg-white px-5 pt-6 pb-5 sm:p-6 sm:pb-5">
                                                    <div class="sm:flex sm:items-start gap-4">
                                                        <div
                                                            class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-2xl bg-amber-100 text-amber-700 sm:mx-0">
                                                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24"
                                                                stroke="currentColor">
                                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                                    stroke-width="2"
                                                                    d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                                                            </svg>
                                                        </div>
                                                        <div
                                                            class="mt-3 text-center sm:mt-0 sm:text-left flex-1 min-w-0 whitespace-normal">
                                                            <h3 class="text-lg font-black text-slate-900"
                                                                id="dissolve-modal-show-title">Konsolidasyonu Dağıt</h3>
                                                            <div class="mt-2 space-y-2.5">
                                                                <p class="text-sm text-slate-600 whitespace-normal break-words">
                                                                    Bu konsolide formu dağıtmak ve altındaki
                                                                    <strong>{{ $formEntry->children->count() }} bağımsız
                                                                        parçayı</strong> tekrar ayrı ayrı listelenecek hale
                                                                    getirmek
                                                                    istediğinize emin misiniz?
                                                                </p>
                                                                <div
                                                                    class="p-3 bg-amber-50 rounded-xl border border-amber-200 text-xs text-amber-900 flex items-start gap-2.5 whitespace-normal break-words">
                                                                    <span class="shrink-0 text-base leading-none">💡</span>
                                                                    <span
                                                                        class="flex-1 whitespace-normal break-words leading-relaxed">Parçaların
                                                                        girilmiş hiçbir verisi kaybolmaz; sadece ana form
                                                                        bağlantısı
                                                                        çözülerek parçalar tekrar bağımsız hale gelir.</span>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="bg-slate-50 px-5 py-3.5 sm:px-6 sm:flex sm:flex-row-reverse gap-2 border-t border-slate-100"
                                                    x-data="{ dissolving: false }">
                                                    <form action="{{ route('form-entries.dissolve', $formEntry) }}"
                                                        method="POST"
                                                        @submit="if(dissolving) { $event.preventDefault(); return false; } dissolving = true; document.body.classList.add('cursor-wait');">
                                                        @csrf
                                                        <button type="submit" :disabled="dissolving"
                                                            @click="if(dissolving) { $event.preventDefault(); return false; }"
                                                            :class="dissolving ? 'opacity-70 cursor-wait pointer-events-none' : ''"
                                                            class="w-full inline-flex items-center justify-center rounded-xl border border-transparent shadow-sm px-4 py-2.5 bg-amber-600 hover:bg-amber-700 text-sm font-bold text-white focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-amber-500 sm:w-auto transition gap-1.5 cursor-pointer">
                                                            <svg x-show="dissolving" style="display: none;"
                                                                class="animate-spin -ml-1 mr-1.5 h-4 w-4 text-white"
                                                                xmlns="http://www.w3.org/2000/svg" fill="none"
                                                                viewBox="0 0 24 24">
                                                                <circle class="opacity-25" cx="12" cy="12" r="10"
                                                                    stroke="currentColor" stroke-width="4"></circle>
                                                                <path class="opacity-75" fill="currentColor"
                                                                    d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                                                </path>
                                                            </svg>
                                                            <span x-text="dissolving ? 'Dağıtılıyor...' : 'Evet, Dağıt'">Evet,
                                                                Dağıt</span>
                                                        </button>
                                                    </form>
                                                    <button type="button" @click="if(!dissolving) openDissolve = false"
                                                        :disabled="dissolving"
                                                        class="mt-2 sm:mt-0 w-full inline-flex justify-center rounded-xl border border-slate-300 shadow-2xs px-4 py-2.5 bg-white text-sm font-semibold text-slate-700 hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:w-auto disabled:opacity-50 cursor-pointer">
                                                        İptal
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>

                    <!-- Dahil Olan Parçalar Listesi -->
                    <div
                        class="mt-4 pt-4 border-t border-white/10 grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-3">
                        @foreach($formEntry->children as $child)
                            <div
                                class="bg-white/10 hover:bg-white/15 border border-white/10 rounded-xl p-3 transition flex flex-col justify-between gap-2">
                                <div class="flex items-center justify-between gap-1">
                                    <span class="text-xs font-black text-amber-300">
                                        ⚡ {{ $child->partition_value ?: ('Parça #' . $child->id) }}
                                    </span>
                                    @if($child->revisionRequests->firstWhere('status', 'pending'))
                                        <span
                                            class="text-[10px] font-bold bg-amber-400/25 text-amber-200 border border-amber-300/50 px-1.5 py-0.2 rounded">Düzenleme
                                            Talebi</span>
                                    @elseif($child->status === 'approved')
                                        <span
                                            class="text-[10px] font-bold bg-emerald-500/30 text-emerald-200 border border-emerald-400/30 px-1.5 py-0.2 rounded">Onaylandı</span>
                                    @elseif($child->status === 'submitted')
                                        <span
                                            class="text-[10px] font-bold bg-blue-500/30 text-blue-200 border border-blue-400/30 px-1.5 py-0.2 rounded">Onay
                                            Bekliyor</span>
                                    @else
                                        <span
                                            class="text-[10px] font-bold bg-slate-500/30 text-slate-200 border border-slate-400/30 px-1.5 py-0.2 rounded">Taslak</span>
                                    @endif
                                </div>
                                <div class="mt-2 text-[11px] text-indigo-200 flex items-center justify-between gap-1 flex-wrap">
                                    <span class="text-[11px] text-indigo-200">{{ $child->rows->count() }} Satır</span>
                                    <a href="{{ route('form-entries.show', $child) }}"
                                        class="text-amber-300 hover:text-white font-bold underline transition">
                                        Parçayı Aç &rarr;
                                    </a>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @elseif($formEntry->partition_value)
                <div
                    class="mb-4 px-4 py-3 bg-amber-50 border-l-4 border-amber-500 rounded-r-xl shadow-xs flex items-center justify-between">
                    <div class="flex items-center gap-2.5">
                        <span
                            class="w-7 h-7 rounded-lg bg-amber-500 text-slate-900 flex items-center justify-center font-black text-xs shadow-xs">
                            ⚡
                        </span>
                        <div>
                            <span class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">Bölümlenmiş Parçalı
                                Form Kaydı</span>
                            <div class="text-xs font-extrabold text-slate-800 flex items-center gap-1.5">
                                <span>{{ $formEntry->partitionField->label ?? 'Kriter' }}:</span>
                                <span
                                    class="text-indigo-700 bg-indigo-50 border border-indigo-200 px-2 py-0.5 rounded font-mono">{{ $formEntry->partition_value }}</span>
                                @if($formEntry->parent_id)
                                    <span class="text-slate-400">&bull;</span>
                                    <a href="{{ route('form-entries.show', $formEntry->parent_id) }}"
                                        class="text-indigo-600 hover:underline font-bold">
                                        🔗 #{{ $formEntry->parent_id }} Numaralı Konsolide Form Dahilinde
                                    </a>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            @if(isset($errors) && $errors->any())
                <div class="p-4 mb-4 text-sm text-red-800 bg-red-100 border border-red-200 rounded-lg shadow-sm"
                    role="alert">
                    <div class="font-bold mb-1 flex items-center gap-1.5">
                        <svg class="w-4 h-4 text-red-600 flex-shrink-0" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        Lütfen formdaki eksik veya hatalı alanları kontrol ediniz:
                    </div>
                    <ul class="list-disc list-inside space-y-0.5 text-xs">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if(session('error'))
                <div class="p-4 mb-4 text-sm text-red-800 bg-red-100 border border-red-200 rounded-lg shadow-sm flex items-center"
                    role="alert">
                    <svg class="w-5 h-5 mr-2 text-red-600 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd"
                            d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z"
                            clip-rule="evenodd"></path>
                    </svg>
                    <span>{{ session('error') }}</span>
                </div>
            @endif

            @if(session('success'))
                <div class="p-4 mb-4 text-sm text-green-700 bg-green-100 rounded-lg shadow-sm" role="alert">
                    {{ session('success') }}
                </div>
            @endif

            @if(session('warning'))
                <div class="p-4 mb-4 text-sm text-amber-800 bg-amber-100 rounded-lg shadow-sm" role="alert">
                    {{ session('warning') }}
                </div>
            @endif

            @if(session('info'))
                <div class="p-4 mb-4 text-sm text-blue-800 bg-blue-100 rounded-lg shadow-sm" role="alert">
                    {{ session('info') }}
                </div>
            @endif

            {{-- Bekleyen Düzenleme (Revizyon) Talebi Uyarısı --}}
            @if(!empty($pendingRevision))
                <div
                    class="p-4 mb-5 text-sm bg-amber-50 border-l-4 border-amber-500 text-amber-900 rounded-r-xl shadow-sm flex flex-col sm:flex-row sm:items-start justify-between gap-3">
                    <div class="flex items-start gap-3">
                        <div class="p-1.5 bg-amber-100 text-amber-700 rounded-lg mt-0.5 shrink-0">
                            <svg class="w-5 h-5 animate-pulse" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                        <div>
                            <h4 class="font-bold text-slate-700 text-xs uppercase tracking-wider">Düzenleme (Revizyon) Talebi Değerlendirme Aşamasında</h4>
                            <p class="mt-1.5 text-xs text-slate-600 leading-relaxed flex flex-wrap items-center gap-1.5">
                                <span>Bu form için</span>
                                <span class="pill-sender-indigo inline-flex items-center px-2 py-0.5 rounded-md font-extrabold">{{ $pendingRevision->user->name }}</span>
                                <span>tarafından</span>
                                <span class="pill-date-sky inline-flex items-center px-2 py-0.5 rounded-md font-bold font-mono">{{ $pendingRevision->created_at->format('d.m.Y H:i') }}</span>
                                <span>tarihinde düzenleme talebi iletilmiştir.</span>
                            </p>
                            @if(!empty($pendingRevisionApprovers) || !empty($revisionWaitText))
                                <div class="mt-2 flex flex-wrap items-center gap-2 text-xs">
                                    @if(!empty($pendingRevisionApprovers))
                                        <span class="inline-flex items-center gap-1.5 text-slate-500 font-medium">
                                            <span>Onay Beklenen:</span>
                                            <span class="pill-approver-emerald inline-flex items-center px-2.5 py-0.5 rounded-md font-extrabold shadow-xs">{{ implode(', ', $pendingRevisionApprovers) }}</span>
                                        </span>
                                    @endif
                                    @if(!empty($revisionWaitText))
                                        <span
                                            class="pill-wait-rose inline-flex items-center px-2.5 py-0.5 rounded-full font-bold shadow-xs">
                                            ⏱ {{ $revisionWaitText }}
                                        </span>
                                    @endif
                                </div>
                            @endif
                            <p
                                class="mt-2 text-xs text-slate-900 bg-white p-2.5 rounded-lg border border-amber-200 shadow-2xs">
                                <span class="text-slate-500 font-semibold">Talep Gerekçesi:</span>
                                <span class="font-bold text-slate-900">{{ $pendingRevision->reason }}</span>
                            </p>
                        </div>
                    </div>

                    @if((!empty($isSelf) && $isSelf) || !empty($isSuperAdmin) || Auth::id() === $pendingRevision->user_id)
                        <div x-data="{ openCancelRev: false, cancellingRev: false }" class="shrink-0">
                            <button type="button" @click="openCancelRev = true"
                                class="inline-flex items-center px-3.5 py-2 bg-white hover:bg-rose-50 text-rose-700 border border-rose-300 text-xs font-bold rounded-lg shadow-2xs transition gap-1.5 cursor-pointer">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"></path>
                                </svg>
                                <span>Talebi Geri Çek</span>
                            </button>

                            <div x-show="openCancelRev" x-cloak style="display: none;"
                                class="fixed inset-0 z-50 overflow-y-auto whitespace-normal" role="dialog" aria-modal="true">
                                <div
                                    class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                                    <div class="fixed inset-0 transition-opacity bg-slate-900/60 backdrop-blur-xs"
                                        @click="if(!cancellingRev) openCancelRev = false"></div>
                                    <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>
                                    <div
                                        class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-md sm:w-full border border-gray-100">
                                        <form action="{{ route('form-entries.revision-requests.cancel', $pendingRevision) }}"
                                            method="POST"
                                            @submit="if(cancellingRev) { $event.preventDefault(); return false; } cancellingRev = true; document.body.classList.add('cursor-wait');">
                                            @csrf
                                            <div class="bg-rose-600 px-6 py-4 flex items-center justify-between text-white">
                                                <h3 class="text-base font-extrabold tracking-tight">Düzenleme Talebini Geri Çek
                                                </h3>
                                                <button type="button" @click="if(!cancellingRev) openCancelRev = false"
                                                    class="text-rose-100 hover:text-white cursor-pointer"
                                                    :disabled="cancellingRev">
                                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                            d="M6 18L18 6M6 6l12 12"></path>
                                                    </svg>
                                                </button>
                                            </div>
                                            <div class="p-6 space-y-2.5">
                                                <p class="text-sm font-semibold text-gray-800">
                                                    İlettiğiniz düzenleme talebini geri çekmek istediğinize emin misiniz?
                                                </p>
                                                <p class="text-xs text-gray-500 leading-relaxed">
                                                    Talep geri çekildiğinde yöneticilere gönderilen uygulama içi bildirimler de
                                                    kaldırılır ve form önceki <strong>"Onaylandı"</strong> durumuna geri döner.
                                                </p>
                                            </div>
                                            <div
                                                class="bg-gray-50 px-6 py-3 flex items-center justify-end gap-3 border-t border-gray-100">
                                                <button type="button" @click="if(!cancellingRev) openCancelRev = false"
                                                    :disabled="cancellingRev"
                                                    class="px-4 py-2 text-xs font-bold text-gray-600 hover:text-gray-800 bg-white border border-gray-200 rounded-lg cursor-pointer">
                                                    Vazgeç
                                                </button>
                                                <button type="submit" :disabled="cancellingRev"
                                                    :class="cancellingRev ? 'opacity-70 cursor-wait pointer-events-none' : ''"
                                                    class="px-5 py-2 text-xs font-bold text-white bg-rose-600 hover:bg-rose-700 rounded-lg shadow-sm cursor-pointer transition">
                                                    <span
                                                        x-text="cancellingRev ? 'Geri Çekiliyor...' : 'Evet, Talebi Geri Çek'">Evet,
                                                        Talebi Geri Çek</span>
                                                </button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            @endif

            {{-- Reddedilen Revizyon Uyarısı (Kullanıcı Tarafından Kapatılabilir) --}}
            @if(!empty($rejectedRevision))
                <div
                    class="p-4 mb-5 text-sm bg-rose-50 border-l-4 border-rose-500 text-rose-900 rounded-r-xl shadow-sm flex items-start justify-between gap-3">
                    <div class="flex items-start gap-3">
                        <div class="p-1.5 bg-rose-100 text-rose-700 rounded-lg mt-0.5">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z">
                                </path>
                            </svg>
                        </div>
                        <div>
                            @if(!empty($isSelf) && $isSelf)
                                <h4 class="font-bold text-rose-950">Düzenleme (Revizyon) Talebiniz Reddedildi</h4>
                                <p class="mt-1 text-xs text-rose-800 leading-relaxed">
                                    İlettiğiniz düzenleme talebi
                                    <strong>{{ $rejectedRevision->reviewer?->name ?? 'Yönetici' }}</strong> tarafından
                                    reddedilmiştir ({{ $rejectedRevision->reviewed_at?->format('d.m.Y H:i') }}). Dilerseniz
                                    gerekçenizi güncelleyerek yukarıdaki butondan tekrar düzenleme talebinde bulunabilirsiniz.
                                </p>
                            @else
                                <h4 class="font-bold text-rose-950">Düzenleme (Revizyon) Talebi Reddedildi</h4>
                                <p class="mt-1 text-xs text-rose-800 leading-relaxed">
                                    <strong>{{ $rejectedRevision->user?->name ?? 'Personel' }}</strong> tarafından iletilen
                                    düzenleme talebi
                                    <strong>{{ $rejectedRevision->reviewer?->name ?? 'Yönetici' }}</strong> tarafından
                                    reddedilmiştir ({{ $rejectedRevision->reviewed_at?->format('d.m.Y H:i') }}).
                                </p>
                            @endif
                            <p
                                class="mt-2 text-xs font-medium text-rose-950 bg-rose-100/70 p-2.5 rounded-lg border border-rose-200">
                                <strong>Red Gerekçesi:</strong> {{ $rejectedRevision->rejection_reason }}
                            </p>
                        </div>
                    </div>
                    @if(!empty($isSelf) && $isSelf)
                        <form action="{{ route('form-entries.revision-requests.dismiss', $rejectedRevision) }}" method="POST"
                            onsubmit="this.querySelector('button').disabled = true; document.body.classList.add('cursor-wait');"
                            class="flex-shrink-0">
                            @csrf
                            <button type="submit"
                                class="p-1.5 text-rose-400 hover:text-rose-700 hover:bg-rose-100 rounded-lg transition cursor-pointer"
                                title="Bildirimi Kapat">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                            </button>
                        </form>
                    @endif
                </div>
            @endif

            {{-- Onaylanan Revizyon Bilgilendirmesi --}}
            @if(!empty($approvedRevision) && in_array($formEntry->status, ['draft', 'submitted']))
                <div
                    class="p-4 mb-5 text-sm bg-emerald-50 border-l-4 border-emerald-500 text-emerald-900 rounded-r-xl shadow-sm flex items-start justify-between gap-3">
                    <div class="flex items-start gap-3">
                        <div class="p-1.5 bg-emerald-100 text-emerald-700 rounded-lg mt-0.5">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7">
                                </path>
                            </svg>
                        </div>
                        <div>
                            <h4 class="font-bold text-emerald-950">Düzenleme (Revizyon) Talebi Onaylandı</h4>
                            <p class="mt-1 text-xs text-emerald-800 leading-relaxed">
                                Bu form için iletilen revizyon talebi
                                <strong>{{ $approvedRevision->reviewer?->name ?? 'Yönetici' }}</strong> tarafından
                                onaylanmış ve form düzenlemeye açılmıştır
                                ({{ $approvedRevision->reviewed_at?->format('d.m.Y H:i') }}).
                            </p>
                            @if($approvedRevision->reason)
                                <p
                                    class="mt-1.5 text-xs font-medium text-emerald-950 bg-emerald-100/70 p-2.5 rounded-lg border border-emerald-200">
                                    <strong>Kabul Edilen Revizyon Gerekçesi:</strong> {{ $approvedRevision->reason }}
                                </p>
                            @endif
                        </div>
                    </div>
                    @if(!empty($isSelf) || !empty($isSuperAdmin))
                        <a href="{{ route('form-entries.edit', $formEntry) }}"
                            class="inline-flex items-center px-3.5 py-2 bg-emerald-600 hover:bg-emerald-700 active:scale-95 text-white text-xs font-bold rounded-lg shadow-sm transition gap-1.5 flex-shrink-0 cursor-pointer">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z">
                                </path>
                            </svg>
                            Şimdi Düzenle
                        </a>
                    @endif
                </div>
            @endif

            @if($formEntry->status === 'rejected' && $formEntry->rejection_reason)
                <div class="p-4 mb-6 text-sm text-red-700 bg-red-50 border-l-4 border-red-500 shadow-sm" role="alert">
                    <strong>Red Nedeni:</strong> {{ $formEntry->rejection_reason }}
                </div>
            @endif

            @php
                $fieldsToRender = ($formEntry->template_snapshot)
                    ? json_decode(json_encode($formEntry->template_snapshot))
                    : $formEntry->template->fields;

                foreach ($fieldsToRender as $fItem) {
                    if (is_object($fItem) && isset($fItem->label)) {
                        $fItem->label = __($fItem->label);
                    } elseif (is_array($fItem) && isset($fItem['label'])) {
                        $fItem['label'] = __($fItem['label']);
                    }
                }

                $user = Auth::user();
                $canEdit = false;
                if ($formEntry->status === 'approved') {
                    $canEdit = $isSuperAdmin;
                } else {
                    $canEdit = ($formEntry->user_id === $user->id) || (!empty($canApprove) && $canApprove) || $isSuperAdmin;
                }
            @endphp

            <!-- Bilgi Kartları -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                <!-- Kart 1: Veriyi Giren -->
                <div class="bg-white p-4 rounded-xl border border-gray-200 shadow-xs flex items-center justify-between transition-all relative group {{ !empty($canViewHistory) && $canViewHistory ? 'hover:border-indigo-300 hover:shadow-md cursor-pointer' : '' }}"
                    @if(!empty($canViewHistory) && $canViewHistory) @click="$dispatch('open-history-modal')"
                    title="{{ __('Form işlem ve onay tarihçesini görüntülemek için tıklayınız') }}" @endif>
                    <div class="flex items-center min-w-0">
                        <div
                            class="p-3 rounded-xl bg-indigo-50 text-indigo-600 mr-3.5 border border-indigo-100 flex-shrink-0">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                            </svg>
                        </div>
                        <div class="truncate">
                            <p class="text-[11px] text-gray-500 uppercase font-bold tracking-wider">
                                {{ __('Veriyi Giren') }}
                            </p>
                            <a href="{{ route('users.profile', $formEntry->user->id) }}" @click.stop
                                class="font-extrabold text-gray-900 hover:text-indigo-600 hover:underline transition-colors text-sm truncate block">
                                {{ $formEntry->user->name }}
                            </a>
                            <p class="text-xs text-gray-500 truncate">
                                @if($formEntry->user->isGroupManager())
                                    <span class="text-indigo-700 font-semibold">{{ $formEntry->user->display_group_name }}</span> &bull; <span class="text-amber-700 font-bold">👑 {{ __('Grup Yöneticisi') }}</span>
                                @else
                                    {{ $formEntry->user->display_group_name }}
                                @endif
                            </p>
                        </div>
                    </div>
                    @if(!empty($canViewHistory) && $canViewHistory)
                        <span
                            class="inline-flex items-center gap-1 text-[11px] font-bold text-indigo-700 bg-indigo-50/80 border border-indigo-200/60 px-2 py-0.5 rounded-md self-start flex-shrink-0">
                            <svg class="w-3 h-3 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            {{ __('Tarihçe') }}
                        </span>
                    @endif
                </div>

                <!-- Kart 2: Gönderim Tarihi -->
                <div class="bg-white p-4 rounded-xl border border-gray-200 shadow-xs flex items-center justify-between transition-all relative group {{ !empty($canViewHistory) && $canViewHistory ? 'hover:border-amber-300 hover:shadow-md cursor-pointer' : '' }}"
                    @if(!empty($canViewHistory) && $canViewHistory) @click="$dispatch('open-history-modal')"
                    title="{{ __('Eski gönderim ve revizyon kayıtlarını incelemek için tıklayınız') }}" @endif>
                    <div class="flex items-center min-w-0">
                        <div
                            class="p-3 rounded-xl bg-amber-50 text-amber-600 mr-3.5 border border-amber-100 flex-shrink-0">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                        <div class="truncate">
                            <p class="text-[11px] text-gray-500 uppercase font-bold tracking-wider">
                                {{ $formEntry->submitted_at ? __('Gönderim Tarihi') : __('Son Kayıt / Taslak Tarihi') }}
                            </p>
                            <p class="font-extrabold text-gray-900 text-sm font-mono">
                                {{ $formEntry->submitted_at ? $formEntry->submitted_at->format('d.m.Y H:i:s') : ($formEntry->updated_at ? $formEntry->updated_at->format('d.m.Y H:i:s') : '-') }}
                            </p>
                            <p class="text-xs text-gray-500 truncate mt-0.5">
                                @if($formEntry->submitted_at)
                                    <span class="text-amber-700 font-semibold">{{ __('Onaya Gönderildi') }}
                                        (v{{ $formEntry->versions->first()?->version_number ?? 1 }})</span>
                                @else
                                    <span class="text-indigo-600 font-semibold">{{ __('Taslak Sürüm') }}
                                        v{{ $formEntry->versions->first()?->version_number ?? 1 }}</span>
                                    @if($formEntry->versions->count() > 1)
                                        <span class="text-gray-400">({{ $formEntry->versions->count() }}
                                            {{ __('kez kaydedildi') }})</span>
                                    @endif
                                @endif
                            </p>
                        </div>
                    </div>
                    @if(!empty($canViewHistory) && $canViewHistory)
                        <span
                            class="inline-flex items-center gap-1 text-[11px] font-bold text-amber-800 bg-amber-50/80 border border-amber-200/60 px-2 py-0.5 rounded-md self-start flex-shrink-0">
                            <svg class="w-3 h-3 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            {{ __('Eski Kayıtlar') }}
                        </span>
                    @endif
                </div>

                <!-- Kart 3: Onay Durumu & Tarihi -->
                @if(!empty($pendingRevision))
                    {{-- Bekleyen Revizyon Durumu --}}
                    <div class="bg-amber-50/80 p-4 rounded-xl border border-amber-300 shadow-xs flex items-center justify-between transition-all relative group {{ !empty($canViewHistory) && $canViewHistory ? 'hover:border-amber-400 hover:shadow-md cursor-pointer' : '' }}"
                        @if(!empty($canViewHistory) && $canViewHistory) @click="$dispatch('open-history-modal')"
                        title="Tüm onay ve revizyon geçmişini incelemek için tıklayınız" @endif>
                        <div class="flex items-center min-w-0">
                            <div
                                class="p-3 rounded-xl bg-amber-100 text-amber-700 mr-3.5 border border-amber-200 flex-shrink-0">
                                <svg class="w-6 h-6 animate-pulse" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15">
                                    </path>
                                </svg>
                            </div>
                            <div class="min-w-0">
                                <p class="text-[11px] text-slate-500 uppercase font-bold tracking-wider">Düzenleme Durumu
                                </p>
                                <p class="font-extrabold text-amber-900 text-sm">
                                    Talep İnceleniyor
                                </p>
                                <p class="text-xs font-mono font-bold text-sky-700 truncate mt-0.5">
                                    {{ $pendingRevision->created_at->format('d.m.Y H:i') }}
                                </p>
                                @if(!empty($pendingRevisionApprovers))
                                    <p class="text-xs text-slate-500 truncate mt-1 flex items-center gap-1"
                                        title="{{ implode(', ', $pendingRevisionApprovers) }}">
                                        <span class="font-medium shrink-0">Onay Beklenen:</span>
                                        <span class="pill-approver-emerald inline-flex items-center px-2 py-0.5 rounded-md text-[11px] font-extrabold truncate">{{ implode(', ', $pendingRevisionApprovers) }}</span>
                                    </p>
                                @endif
                                @if(!empty($revisionWaitText))
                                    <span
                                        class="pill-wait-rose inline-flex items-center mt-1.5 px-2.5 py-0.5 rounded-full text-[11px] font-bold shadow-xs">
                                        ⏱ {{ $revisionWaitText }}
                                    </span>
                                @endif
                            </div>
                        </div>
                        @if(!empty($canViewHistory) && $canViewHistory)
                            <span
                                class="inline-flex items-center gap-1 text-[11px] font-bold text-amber-800 bg-white/80 border border-amber-200 px-2 py-0.5 rounded-md self-start flex-shrink-0">
                                <svg class="w-3 h-3 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                Geçmiş
                            </span>
                        @endif
                    </div>
                @elseif($formEntry->status === 'approved')
                    {{-- Onaylandı --}}
                    <div class="bg-emerald-50/70 p-4 rounded-xl border border-emerald-200 shadow-xs flex items-center justify-between transition-all relative group {{ !empty($canViewHistory) && $canViewHistory ? 'hover:border-emerald-400 hover:shadow-md cursor-pointer' : '' }}"
                        @if(!empty($canViewHistory) && $canViewHistory) @click="$dispatch('open-history-modal')"
                        title="Onay ve revizyon tarihçesini incelemek için tıklayınız" @endif>
                        <div class="flex items-center min-w-0">
                            <div
                                class="p-3 rounded-xl bg-emerald-100 text-emerald-700 mr-3.5 border border-emerald-200 flex-shrink-0">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </div>
                            <div class="min-w-0">
                                <p class="text-[11px] text-slate-500 uppercase font-bold tracking-wider">Onaylanma Tarihi
                                </p>
                                <p class="font-extrabold text-sky-700 font-mono text-sm">
                                    {{ $formEntry->approved_at ? $formEntry->approved_at->format('d.m.Y H:i') : '-' }}
                                </p>
                                <p class="text-xs text-slate-500 truncate mt-1 flex items-center gap-1">
                                    @if(!empty($approvedByDisplayName))
                                        <span class="font-medium shrink-0">Onaylayan:</span>
                                        @if(!empty($approvedByLinkId))
                                            <a href="{{ route('users.profile', $approvedByLinkId) }}"
                                                class="pill-approver-emerald inline-flex items-center px-2 py-0.5 rounded-md text-[11px] font-extrabold hover:opacity-90 truncate" @click.stop>{{ $approvedByDisplayName }}</a>
                                        @else
                                            <span class="pill-approver-emerald inline-flex items-center px-2 py-0.5 rounded-md text-[11px] font-extrabold truncate">{{ $approvedByDisplayName }}</span>
                                        @endif
                                    @else
                                        <span class="font-semibold text-emerald-700">Form Onaylandı</span>
                                    @endif
                                </p>
                                @if(!empty($approvalElapsedText))
                                    <span
                                        class="pill-elapsed-indigo inline-flex items-center mt-1.5 px-2.5 py-0.5 rounded-full text-[11px] font-bold shadow-xs">
                                        ⏱ {{ $approvalElapsedText }}
                                    </span>
                                @endif
                            </div>
                        </div>
                        @if(!empty($canViewHistory) && $canViewHistory)
                            <span
                                class="inline-flex items-center gap-1 text-[11px] font-bold text-emerald-800 bg-white/80 border border-emerald-200 px-2 py-0.5 rounded-md self-start flex-shrink-0">
                                <svg class="w-3 h-3 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                Geçmiş
                            </span>
                        @endif
                    </div>
                @elseif($formEntry->status === 'submitted')
                    {{-- Onay Bekliyor --}}
                    <div class="bg-amber-50/50 p-4 rounded-xl border border-amber-200 shadow-xs flex items-center justify-between transition-all relative group {{ !empty($canViewHistory) && $canViewHistory ? 'hover:border-amber-300 hover:shadow-md cursor-pointer' : '' }}"
                        @if(!empty($canViewHistory) && $canViewHistory) @click="$dispatch('open-history-modal')"
                        title="Onay ve revizyon tarihçesini incelemek için tıklayınız" @endif>
                        <div class="flex items-center min-w-0">
                            <div
                                class="p-3 rounded-xl bg-amber-100/70 text-amber-700 mr-3.5 border border-amber-200 flex-shrink-0">
                                <svg class="w-6 h-6 animate-pulse" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </div>
                            <div class="min-w-0">
                                <p class="text-[11px] text-slate-500 uppercase font-bold tracking-wider">Onay Durumu</p>
                                <p class="font-extrabold text-amber-900 text-sm">
                                    Onay Bekliyor
                                </p>
                                <p class="text-xs text-slate-500 truncate">
                                    @if(!empty($approvedRevision))
                                        Revizyon sonrası onaya gönderildi
                                    @else
                                        Yönetici incelemesinde
                                    @endif
                                </p>
                                @if(!empty($pendingApprovalApprovers))
                                    <p class="text-xs text-slate-500 truncate mt-1 flex items-center gap-1"
                                        title="{{ implode(', ', $pendingApprovalApprovers) }}">
                                        <span class="font-medium shrink-0">Onay Beklenen:</span>
                                        <span class="pill-approver-emerald inline-flex items-center px-2 py-0.5 rounded-md text-[11px] font-extrabold truncate">{{ implode(', ', $pendingApprovalApprovers) }}</span>
                                    </p>
                                @endif
                                @if(!empty($approvalWaitText))
                                    <span
                                        class="pill-wait-rose inline-flex items-center mt-1.5 px-2.5 py-0.5 rounded-full text-[11px] font-bold shadow-xs">
                                        ⏱ {{ $approvalWaitText }}
                                    </span>
                                @endif
                            </div>
                        </div>
                        @if(!empty($canViewHistory) && $canViewHistory)
                            <span
                                class="inline-flex items-center gap-1 text-[11px] font-bold text-amber-800 bg-white/80 border border-amber-200 px-2 py-0.5 rounded-md self-start flex-shrink-0">
                                <svg class="w-3 h-3 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                Geçmiş
                            </span>
                        @endif
                    </div>
                @elseif($formEntry->status === 'rejected')
                    {{-- Reddedildi --}}
                    <div class="bg-rose-50/70 p-4 rounded-xl border border-rose-200 shadow-xs flex items-center justify-between transition-all relative group {{ !empty($canViewHistory) && $canViewHistory ? 'hover:border-rose-300 hover:shadow-md cursor-pointer' : '' }}"
                        @if(!empty($canViewHistory) && $canViewHistory) @click="$dispatch('open-history-modal')"
                        title="Onay ve red tarihçesini incelemek için tıklayınız" @endif>
                        <div class="flex items-center min-w-0">
                            <div
                                class="p-3 rounded-xl bg-rose-100 text-rose-700 mr-3.5 border border-rose-200 flex-shrink-0">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </div>
                            <div class="truncate">
                                <p class="text-[11px] text-rose-700 uppercase font-bold tracking-wider">Onay Durumu</p>
                                <p class="font-extrabold text-rose-950 text-sm">
                                    Reddedildi
                                </p>
                                <p class="text-xs text-rose-800 truncate" title="{{ $formEntry->rejection_reason }}">
                                    {{ $formEntry->rejection_reason ? \Illuminate\Support\Str::limit($formEntry->rejection_reason, 30) : 'Reddedildi' }}
                                </p>
                            </div>
                        </div>
                        @if(!empty($canViewHistory) && $canViewHistory)
                            <span
                                class="inline-flex items-center gap-1 text-[11px] font-bold text-rose-800 bg-white/80 border border-rose-200 px-2 py-0.5 rounded-md self-start flex-shrink-0">
                                <svg class="w-3 h-3 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                Geçmiş
                            </span>
                        @endif
                    </div>
                @else
                    {{-- Taslak --}}
                    <div class="bg-gray-50/70 p-4 rounded-xl border border-gray-200 shadow-xs flex items-center justify-between transition-all relative group {{ !empty($canViewHistory) && $canViewHistory ? 'hover:border-gray-400 hover:shadow-md cursor-pointer' : '' }}"
                        @if(!empty($canViewHistory) && $canViewHistory) @click="$dispatch('open-history-modal')"
                        title="Kayıt tarihçesini incelemek için tıklayınız" @endif>
                        <div class="flex items-center min-w-0">
                            <div
                                class="p-3 rounded-xl bg-gray-100 text-gray-500 mr-3.5 border border-gray-200 flex-shrink-0">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
                                    </path>
                                </svg>
                            </div>
                            <div class="truncate">
                                <p class="text-[11px] text-gray-500 uppercase font-bold tracking-wider">Onay Durumu</p>
                                <p class="font-extrabold text-gray-800 text-sm">
                                    Taslak
                                </p>
                                <p class="text-xs text-gray-400 truncate">
                                    @if(!empty($approvedRevision))
                                        Düzenlemeye açıldı (revizyon onaylandı)
                                    @else
                                        Henüz onaya gönderilmedi
                                    @endif
                                </p>
                            </div>
                        </div>
                        @if(!empty($canViewHistory) && $canViewHistory)
                            <span
                                class="inline-flex items-center gap-1 text-[11px] font-bold text-gray-600 bg-white/80 border border-gray-200 px-2 py-0.5 rounded-md self-start flex-shrink-0">
                                <svg class="w-3 h-3 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                Geçmiş
                            </span>
                        @endif
                    </div>
                @endif
            </div>

            @php
                // Formun tüm kayıtlı sürümleri (v3, v2, v1...)
                $allVersionsSorted = $formEntry->versions->sortByDesc('version_number');
                $latestVersion = $allVersionsSorted->first();
                $latestVersionNum = $latestVersion ? $latestVersion->version_number : 1;

                $versionsData = $allVersionsSorted->map(function ($v) {
                    return [
                        'id' => $v->id,
                        'version_number' => $v->version_number,
                        'status' => $v->status,
                        'formatted_status' => $v->formatted_status,
                        'created_at_formatted' => $v->created_at ? $v->created_at->format('d.m.Y H:i:s') : '-',
                        'row_count' => $v->row_count,
                        'summary' => $v->summary,
                        'user_name' => $v->user?->name ?? 'Kullanıcı',
                        'rows_data' => $v->rows_data,
                    ];
                })->values();
            @endphp

            <!-- Veri Tablosu -->
            <div x-data="formShowApp({!! htmlspecialchars(json_encode($fieldsToRender), ENT_QUOTES, 'UTF-8') !!}, {{ $formEntry->template->show_row_totals ? 'true' : 'false' }}, {!! htmlspecialchars(json_encode($versionsData), ENT_QUOTES, 'UTF-8') !!}, {{ $formEntry->form_template_id ?? $formEntry->template->id }})"
                @keydown.window.escape="if(isFullscreen) toggleFullscreen()"
                :class="isFullscreen ? 'fixed inset-0 z-[999999] bg-white flex flex-col w-screen h-screen overflow-hidden m-0 p-0 rounded-none shadow-none ring-0' : 'bg-white shadow-xl sm:rounded-xl overflow-hidden ring-1 ring-black/5 mb-8'"
                :style="isFullscreen ? 'position: fixed !important; top: 0 !important; left: 0 !important; right: 0 !important; bottom: 0 !important; width: 100vw !important; height: 100vh !important; z-index: 9999999 !important; margin: 0 !important; padding: 0 !important;' : ''">
                <div class="p-4 sm:p-5 border-b border-gray-200 bg-gradient-to-r from-gray-50 to-white flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 flex-shrink-0 transition-colors"
                    :class="isFullscreen ? 'bg-slate-900 border-slate-800' : ''">
                    <div class="flex items-center space-x-3">
                        <div class="p-2 rounded-lg shadow-inner"
                            :class="isFullscreen ? 'bg-indigo-900/60 text-indigo-300' : 'bg-indigo-100 text-indigo-600'">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M4 6h16M4 10h16M4 14h16M4 18h16"></path>
                            </svg>
                        </div>
                        <div>
                            <h3 class="font-extrabold text-lg tracking-tight"
                                :class="isFullscreen ? 'text-white' : 'text-gray-900'">Girilen Veriler</h3>
                            <span x-show="isFullscreen" class="text-xs text-slate-400"
                                x-text="rows.length + ' Satır'"></span>
                        </div>
                    </div>
                    <div class="flex items-center gap-2 flex-wrap">
                        @if(!empty($canSubmitApproval) && $canSubmitApproval)
                            <button type="button"
                                onclick="if(this.dataset.clicked) return; this.dataset.clicked = 'true'; this.classList.add('btn-loading'); document.body.classList.add('cursor-wait'); window.dispatchEvent(new CustomEvent('open-submit-approval-modal')); setTimeout(() => { delete this.dataset.clicked; this.classList.remove('btn-loading'); document.body.classList.remove('cursor-wait'); }, 800);"
                                @click="$dispatch('open-submit-approval-modal')"
                                class="inline-flex items-center px-3 sm:px-4 py-1.5 sm:py-2 bg-emerald-600 border border-transparent rounded-lg font-semibold text-xs text-white uppercase tracking-widest hover:bg-emerald-700 transition cursor-pointer shadow-xs gap-1.5">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path>
                                </svg>
                                <span>Onaya Gönder</span>
                            </button>
                        @endif

                        @if(!empty($canSubmitChildrenApproval) && $canSubmitChildrenApproval)
                            <button type="button"
                                onclick="if(this.dataset.clicked) return; this.dataset.clicked = 'true'; this.classList.add('btn-loading'); document.body.classList.add('cursor-wait'); window.dispatchEvent(new CustomEvent('open-submit-children-modal')); setTimeout(() => { delete this.dataset.clicked; this.classList.remove('btn-loading'); document.body.classList.remove('cursor-wait'); }, 800);"
                                @click="$dispatch('open-submit-children-modal')"
                                class="inline-flex items-center px-3 sm:px-4 py-1.5 sm:py-2 bg-emerald-600 border border-transparent rounded-lg font-semibold text-xs text-white uppercase tracking-widest hover:bg-emerald-700 transition cursor-pointer shadow-xs gap-1.5"
                                title="Konsolide forma bağlı tüm taslak parçaları onaya gönder">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path>
                                </svg>
                                <span>Taslak Parçaları Onaya Gönder ({{ $draftChildrenCount }})</span>
                            </button>
                        @endif

                        @if(!empty($canWithdraw) && $canWithdraw)
                            <button type="button"
                                onclick="if(this.dataset.clicked) return; this.dataset.clicked = 'true'; this.classList.add('btn-loading'); document.body.classList.add('cursor-wait'); window.dispatchEvent(new CustomEvent('open-withdraw-modal')); setTimeout(() => { delete this.dataset.clicked; this.classList.remove('btn-loading'); document.body.classList.remove('cursor-wait'); }, 800);"
                                @click="$dispatch('open-withdraw-modal')"
                                class="inline-flex items-center px-3 sm:px-4 py-1.5 sm:py-2 {{ $formEntry->status === 'submitted' ? 'bg-amber-600 hover:bg-amber-700' : 'bg-rose-600 hover:bg-rose-700' }} border border-transparent rounded-lg font-semibold text-xs text-white uppercase tracking-widest transition cursor-pointer shadow-xs gap-1.5"
                                title="{{ $formEntry->status === 'submitted' ? 'Formu Onay Sürecinden Geri Çek (Taslağa Al)' : 'Taslağı Kalıcı Olarak Sil' }}">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    @if($formEntry->status === 'submitted')
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"></path>
                                    @else
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16">
                                        </path>
                                    @endif
                                </svg>
                                <span>{{ $formEntry->status === 'submitted' ? 'Geri Çek' : 'Taslağı Sil' }}</span>
                            </button>
                        @endif

                        @if($canEdit)
                            <a href="{{ route('form-entries.edit', $formEntry) }}"
                                onclick="if(this.dataset.clicked) { event.preventDefault(); return false; } this.dataset.clicked = 'true'; this.classList.add('btn-loading'); document.body.classList.add('cursor-wait'); this.innerHTML = '<svg class=\'animate-spin w-4 h-4 mr-1.5 inline-block text-white\' fill=\'none\' viewBox=\'0 0 24 24\'><circle class=\'opacity-25\' cx=\'12\' cy=\'12\' r=\'10\' stroke=\'currentColor\' stroke-width=\'4\'></circle><path class=\'opacity-75\' fill=\'currentColor\' d=\'M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z\'></path></svg> <span>Yönlendiriliyor...</span>';"
                                class="inline-flex items-center px-3 sm:px-4 py-1.5 sm:py-2 bg-indigo-600 border border-transparent rounded-lg font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 transition gap-1.5 cursor-pointer">
                                <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z">
                                    </path>
                                </svg>
                                <span>Düzenle</span>
                            </a>
                        @endif

                        <!-- Sürümleri Kıyasla Butonu (En az 2 sürüm varsa kıyaslama yapılabilir) -->
                        @if($versionsData->count() >= 2)
                            <button type="button" @click="toggleCompareMode()"
                                class="inline-flex items-center px-3 sm:px-4 py-1.5 sm:py-2 rounded-lg font-semibold text-xs transition cursor-pointer shadow-xs gap-1.5 select-none"
                                :class="compareMode ? 'bg-amber-500 hover:bg-amber-600 text-white ring-2 ring-amber-400 shadow-md' : 'bg-amber-50 hover:bg-amber-100 text-amber-900 border border-amber-300'"
                                title="Farklı sürümleri birbiriyle karşılaştır">
                                <svg class="w-4 h-4" :class="compareMode ? 'text-white' : 'text-amber-600'" fill="none"
                                    stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"></path>
                                </svg>
                                <span x-text="compareMode ? 'Kıyas Modu Açık' : 'Sürümleri Kıyasla'"></span>
                                <span class="px-1.5 py-0.2 rounded-full text-[10px] font-black"
                                    :class="compareMode ? 'bg-white/20 text-white' : 'bg-amber-200/80 text-amber-900'"
                                    x-text="versions.length + ' Sürüm'"></span>
                            </button>
                        @endif

                        <!-- Sütunları Göster / Gizle Menüsü -->
                        <div class="relative" x-data="{ colManageOpen: false }">
                            <button type="button" @click.stop="colManageOpen = !colManageOpen"
                                @click.away="colManageOpen = false"
                                class="inline-flex items-center px-3 sm:px-4 py-1.5 sm:py-2 rounded-lg font-semibold text-xs transition cursor-pointer shadow-xs gap-1.5 select-none"
                                :class="hiddenColumns.length > 0 
                                    ? 'bg-amber-100 text-amber-900 border border-amber-300 ring-1 ring-amber-400/50' 
                                    : (isFullscreen ? 'bg-slate-800 hover:bg-slate-700 text-slate-300 border border-slate-700' : 'bg-white hover:bg-gray-100 text-gray-700 border border-gray-300')"
                                title="Tablodaki sütunları göster veya gizle">
                                <svg class="w-3.5 h-3.5 text-gray-500" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                </svg>
                                <span>Sütunlar</span>
                                <span x-show="hiddenColumns.length > 0"
                                    class="px-1.5 py-0.2 bg-amber-500 text-white rounded-full text-[10px] font-black"
                                    x-text="hiddenColumns.length + ' Gizli'"></span>
                            </button>

                            <!-- Dropdown Popover -->
                            <div x-show="colManageOpen" x-transition.opacity @click.stop
                                class="absolute right-0 mt-2 w-72 rounded-xl shadow-2xl bg-white border border-gray-200 z-50 p-3 text-left"
                                style="display: none; max-height: 420px;">
                                <div class="flex items-center justify-between pb-2 border-b border-gray-100">
                                    <span class="text-xs font-black text-gray-800 uppercase tracking-wide">Sütun
                                        Görünürlüğü</span>
                                    <button type="button" @click="showAllColumns()" x-show="hiddenColumns.length > 0"
                                        class="text-[11px] font-bold text-indigo-600 hover:text-indigo-800 cursor-pointer">
                                        Tümünü Göster
                                    </button>
                                </div>
                                <div class="max-h-64 overflow-y-auto py-2 space-y-1">
                                    <template x-for="f in fields" :key="f.id">
                                        <label
                                            class="flex items-center justify-between p-1.5 rounded-lg hover:bg-gray-50 cursor-pointer transition select-none">
                                            <div class="flex items-center gap-2 truncate">
                                                <input type="checkbox" :checked="!isColumnHidden(f.id)"
                                                    @change="toggleColumn(f.id)"
                                                    class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500 h-4 w-4">
                                                <span class="text-xs font-medium text-gray-700 truncate"
                                                    x-text="f.label || f.name"></span>
                                            </div>
                                            <span x-show="f.is_disabled"
                                                class="text-[9px] font-bold text-rose-700 bg-rose-50 border border-rose-200 px-1.5 py-0.2 rounded shrink-0">Pasif</span>
                                        </label>
                                    </template>
                                </div>
                            </div>
                        </div>

                        <!-- Tam Ekran Butonu -->
                        <button type="button" @click="toggleFullscreen()"
                            class="inline-flex items-center px-3 sm:px-4 py-1.5 sm:py-2 rounded-lg font-semibold text-xs transition cursor-pointer shadow-xs gap-1.5 select-none"
                            :class="isFullscreen ? 'bg-indigo-600 hover:bg-indigo-500 text-white ring-2 ring-indigo-400' : 'bg-white hover:bg-indigo-50 text-indigo-700 border border-indigo-200'"
                            :title="isFullscreen ? 'Tam Ekrandan Çık (Esc)' : 'Tam Ekran Moduna Geç'">
                            <template x-if="!isFullscreen">
                                <span class="flex items-center gap-1.5">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4">
                                        </path>
                                    </svg>
                                    <span>Tam Ekran</span>
                                </span>
                            </template>
                            <template x-if="isFullscreen">
                                <span class="flex items-center gap-1.5">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M6 18L18 6M6 6l12 12"></path>
                                    </svg>
                                    <span>Tam Ekrandan Çık</span>
                                </span>
                            </template>
                        </button>
                    </div>
                </div>

                <!-- Sürüm Kıyaslama Modu Açıklama ve Seçim Barı -->
                <div x-show="compareMode" x-cloak
                    class="p-3.5 bg-gradient-to-r from-amber-50 via-amber-100/60 to-orange-50 border-b border-amber-300 flex flex-col md:flex-row justify-between items-start md:items-center gap-3 transition-all">
                    <div class="flex items-center gap-2.5">
                        <span class="relative flex h-3 w-3 shrink-0">
                            <span
                                class="animate-ping absolute inline-flex h-full w-full rounded-full bg-amber-400 opacity-75"></span>
                            <span class="relative inline-flex rounded-full h-3 w-3 bg-amber-500"></span>
                        </span>
                        <div>
                            <h4 class="text-xs font-black text-amber-900 flex items-center gap-1.5 flex-wrap">
                                <span>⚡ Çoklu Sürüm Kıyaslama Modu Açık</span>
                                <span
                                    class="bg-amber-200 text-amber-900 px-2 py-0.5 rounded text-[10px] font-mono font-bold border border-amber-300"
                                    x-text="getSelectedVersionsSummary()"></span>
                            </h4>
                            <p class="text-[11px] text-amber-800 mt-0.5">
                                Seçilen sürümler arasında farklı değer içeren hücreler turuncu vurgulanmıştır. Detaylı
                                sürüm geçmişini görmek için hücrenin <strong>üzerine geliniz (hover)</strong>.
                            </p>
                        </div>
                    </div>

                    <div class="flex items-center gap-2 w-full md:w-auto flex-wrap">
                        <label class="text-xs font-bold text-amber-900 whitespace-nowrap">Kıyaslanacak Sürümler:</label>

                        <!-- Multi-Select Çoktan Seçmeli Dropdown -->
                        <div class="relative" x-data="{ openDropdown: false }" @click.away="openDropdown = false">
                            <button type="button" @click="openDropdown = !openDropdown"
                                class="inline-flex items-center justify-between gap-2 px-3 py-1.5 bg-white border border-amber-300 rounded-xl text-xs font-bold text-slate-800 hover:bg-amber-50 focus:outline-none focus:ring-2 focus:ring-amber-400 shadow-2xs min-w-[210px] cursor-pointer">
                                <div class="flex items-center gap-1.5 truncate">
                                    <svg class="w-3.5 h-3.5 text-amber-600 shrink-0" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10">
                                        </path>
                                    </svg>
                                    <span x-text="getSelectedVersionsLabel()"></span>
                                </div>
                                <svg class="w-3.5 h-3.5 text-slate-500 shrink-0 transition-transform duration-200"
                                    :class="openDropdown ? 'rotate-180' : ''" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M19 9l-7 7-7-7"></path>
                                </svg>
                            </button>

                            <!-- Dropdown Popup Menüsü -->
                            <div x-show="openDropdown" x-cloak x-transition:enter="transition ease-out duration-100"
                                x-transition:enter-start="transform opacity-0 scale-95"
                                x-transition:enter-end="transform opacity-100 scale-100"
                                x-transition:leave="transition ease-in duration-75"
                                x-transition:leave-start="transform opacity-100 scale-100"
                                x-transition:leave-end="transform opacity-0 scale-95"
                                class="absolute right-0 sm:left-0 top-full mt-1.5 z-50 w-72 bg-white rounded-xl shadow-2xl border border-slate-200 py-2 divide-y divide-slate-100 text-xs">

                                <div
                                    class="px-3 py-1.5 flex items-center justify-between text-[11px] font-semibold text-slate-500">
                                    <span>Kıyaslanacak Sürümleri Seçin</span>
                                    <div class="flex items-center gap-2">
                                        <button type="button" @click="selectAllVersions()"
                                            class="text-indigo-600 hover:text-indigo-800 text-[10px] font-bold cursor-pointer">Tümünü
                                            Seç</button>
                                        <span class="text-slate-300">|</span>
                                        <button type="button" @click="clearAllVersions()"
                                            class="text-rose-500 hover:text-rose-700 text-[10px] font-bold cursor-pointer">Temizle</button>
                                    </div>
                                </div>

                                <div class="max-h-56 overflow-y-auto py-1 space-y-0.5">
                                    <template x-for="v in versions" :key="v.id">
                                        <label
                                            class="flex items-center gap-2.5 px-3 py-2 hover:bg-amber-50/80 cursor-pointer transition-colors select-none"
                                            :class="selectedVersionIds.includes(v.id) ? 'bg-amber-50/60 font-semibold text-amber-950' : 'text-slate-700'">
                                            <input type="checkbox" :value="v.id"
                                                :checked="selectedVersionIds.includes(v.id)"
                                                @change="toggleVersion(v.id)"
                                                class="w-4 h-4 rounded text-amber-600 border-gray-300 focus:ring-amber-500 cursor-pointer">
                                            <div class="flex-1 min-w-0">
                                                <div class="flex items-center justify-between">
                                                    <span class="font-bold text-xs"
                                                        :class="selectedVersionIds.includes(v.id) ? 'text-amber-900' : 'text-slate-900'"
                                                        x-text="'v' + v.version_number"></span>
                                                    <span
                                                        class="text-[10px] px-1.5 py-0.2 rounded font-mono font-medium"
                                                        :class="v.status === 'approved' ? 'bg-emerald-100 text-emerald-800' : (v.status === 'submitted' ? 'bg-amber-100 text-amber-800' : 'bg-slate-100 text-slate-700')"
                                                        x-text="v.formatted_status"></span>
                                                </div>
                                                <div
                                                    class="text-[10px] text-slate-400 flex items-center justify-between mt-0.5">
                                                    <span x-text="v.created_at_formatted"></span>
                                                    <span x-text="v.row_count + ' Satır'"></span>
                                                </div>
                                            </div>
                                        </label>
                                    </template>
                                </div>

                                <div
                                    class="px-3 py-1.5 bg-slate-50 text-[10px] text-slate-500 flex justify-between items-center rounded-b-xl">
                                    <span x-text="selectedVersionIds.length + ' sürüm seçili'"></span>
                                    <button type="button" @click="openDropdown = false"
                                        class="px-2.5 py-0.5 bg-amber-500 hover:bg-amber-600 text-white font-bold rounded text-[10px] shadow-2xs cursor-pointer">Kapat</button>
                                </div>
                            </div>
                        </div>

                        <button type="button" @click="toggleCompareMode()"
                            class="px-3 py-1.5 bg-amber-200 hover:bg-amber-300 text-amber-900 rounded-xl text-xs font-extrabold transition cursor-pointer flex items-center gap-1 shadow-2xs">
                            <span>✕ Kıyas Modunu Kapat</span>
                        </button>
                    </div>
                </div>

                <div class="overflow-auto min-h-[200px] pb-2 flex-1" :class="isFullscreen ? 'h-full' : ''">
                    <!-- Gizli form (Excel Export için) -->
                    <form id="exportForm" action="{{ route('form-entries.export', $formEntry) }}" method="GET"
                        style="display:none;">
                        <template x-for="(values, fieldId) in filters" :key="fieldId">
                            <template x-for="val in values" :key="val">
                                <input type="hidden" :name="'filters['+fieldId+'][]'" :value="val">
                            </template>
                        </template>
                        <button type="submit" id="exportExcelBtn">Export</button>
                    </form>

                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-800 sticky top-0 z-20 shadow-md">
                            <tr>
                                <th
                                    class="w-12 px-4 py-3 text-center text-xs font-medium text-white uppercase tracking-wider bg-gray-800 sticky left-0 z-30 border-r border-gray-700 shadow-[2px_0_5px_-2px_rgba(0,0,0,0.3)]">
                                    #</th>

                                <template x-for="field in fields" :key="field.id">
                                    <th :id="'show-th-field-' + field.id" :style="getColumnStickyStyle(field.id)"
                                        :class="getColumnStickyClass(field.id, 'th')"
                                        class="px-2 sm:px-4 py-3 text-left text-[10px] sm:text-xs font-medium text-white uppercase tracking-wider group relative bg-gray-800 min-w-[160px]">
                                        <div class="flex flex-col space-y-1">
                                            <div class="flex items-center justify-between gap-1">
                                                <!-- Sütun Başlığı ve Tıklanabilir Sıralama -->
                                                <div class="flex items-center gap-1.5 cursor-pointer select-none truncate hover:text-indigo-200 transition-colors"
                                                    @click="sortColumn(field.id)"
                                                    :title="field.type === 'number' ? 'Sıralamak için tıklayın (Küçükten Büyüğe / Büyükten Küçüğe)' : 'Sıralamak için tıklayın (A\'dan Z\'ye / Z\'den A\'ya)'">
                                                    <span class="truncate"
                                                        x-html="field.label + (field.is_required ? ' <span class=\'text-red-400\'>*</span>' : '')"></span>
                                                    <span class="inline-flex items-center shrink-0">
                                                        <template
                                                            x-if="sortFieldId === field.id && sortDirection === 'asc'">
                                                            <span
                                                                class="text-[9px] font-black px-1.5 py-0.2 rounded bg-indigo-500 text-white shadow-2xs inline-flex items-center"
                                                                x-text="field.type === 'number' ? '▲ 1-9' : '▲ A-Z'"></span>
                                                        </template>
                                                        <template
                                                            x-if="sortFieldId === field.id && sortDirection === 'desc'">
                                                            <span
                                                                class="text-[9px] font-black px-1.5 py-0.2 rounded bg-amber-500 text-white shadow-2xs inline-flex items-center"
                                                                x-text="field.type === 'number' ? '▼ 9-1' : '▼ Z-A'"></span>
                                                        </template>
                                                        <template x-if="sortFieldId !== field.id || !sortDirection">
                                                            <span
                                                                class="text-gray-400 hover:text-white transition-opacity text-[10px] ml-0.5 opacity-60 hover:opacity-100 font-bold"
                                                                title="Sıralamak için tıklayın (A-Z / 1-9)">⇅</span>
                                                        </template>
                                                    </span>
                                                    <span x-show="field.is_disabled"
                                                        class="text-[8px] font-black px-1 py-0.2 rounded bg-rose-900/90 text-rose-200 border border-rose-500/50 shrink-0 tracking-wider"
                                                        title="Bu alan şablonda pasif (girişe kapalı) olarak tanımlanmıştır">PASİF</span>
                                                </div>

                                                <div class="flex items-center space-x-1 shrink-0">
                                                    <!-- Sütun İşlemleri Menüsü (Sabitle / Sırala / Gizle) -->
                                                    <div class="relative" x-data="{ pinOpen: false }">
                                                        <button type="button" @click.stop="pinOpen = !pinOpen"
                                                            @click.away="pinOpen = false"
                                                            class="flex items-center space-x-0.5 p-1 rounded transition-all cursor-pointer text-xs"
                                                            :class="isColumnPinnedLeft(field.id)
                                                                ? 'text-amber-400 hover:text-amber-300 bg-gray-700/90 shadow-xs ring-1 ring-amber-400/50'
                                                                : (isColumnPinnedRight(field.id)
                                                                    ? 'text-emerald-400 hover:text-emerald-300 bg-gray-700/90 shadow-xs ring-1 ring-emerald-400/50'
                                                                    : 'text-gray-400 hover:text-indigo-200 hover:bg-gray-700/60 opacity-0 group-hover:opacity-100')"
                                                            :title="isColumnPinnedLeft(field.id) 
                                                                ? 'Sola Sabitlendi (Sütun işlemleri için tıklayın)' 
                                                                : (isColumnPinnedRight(field.id) 
                                                                    ? 'Sağa Sabitlendi (Sütun işlemleri için tıklayın)' 
                                                                    : 'Sütun Seçenekleri (Sabitle / Sırala / Gizle)')">
                                                            <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none"
                                                                stroke="currentColor">
                                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                                    stroke-width="2"
                                                                    d="M16 12l2-2a2.828 2.828 0 10-4-4l-2 2m-4 4l-4 4m6-6l-4-4m10 6l-6 6M5 19l4-4" />
                                                            </svg>
                                                            <span x-show="isColumnPinnedLeft(field.id)"
                                                                class="text-[9px] font-bold leading-none">←</span>
                                                            <span x-show="isColumnPinnedRight(field.id)"
                                                                class="text-[9px] font-bold leading-none">→</span>
                                                        </button>

                                                        <!-- Sütun İşlemleri Açılır Menüsü -->
                                                        <div x-show="pinOpen" x-transition.opacity @click.stop
                                                            class="absolute z-50 mt-1 right-0 rounded-xl shadow-2xl bg-gray-900 border border-gray-700 py-1.5 text-xs text-left"
                                                            style="display: none; min-width: 175px;">
                                                            <div
                                                                class="px-2.5 py-1 text-[10px] font-bold text-gray-400 uppercase tracking-wider">
                                                                Sabitleme</div>
                                                            <button type="button"
                                                                @click="setColumnPin(field.id, 'left'); pinOpen = false"
                                                                class="w-full flex items-center px-3 py-1.5 transition-colors cursor-pointer"
                                                                :class="isColumnPinnedLeft(field.id) ? 'bg-amber-500/20 text-amber-300 font-semibold' : 'text-gray-200 hover:bg-gray-800 hover:text-white'">
                                                                <span class="mr-2 text-sm font-bold">←</span>
                                                                <span>Sola Sabitle</span>
                                                                <span x-show="isColumnPinnedLeft(field.id)"
                                                                    class="ml-auto text-amber-400 text-xs">✓</span>
                                                            </button>
                                                            <button type="button"
                                                                @click="setColumnPin(field.id, 'right'); pinOpen = false"
                                                                class="w-full flex items-center px-3 py-1.5 transition-colors cursor-pointer"
                                                                :class="isColumnPinnedRight(field.id) ? 'bg-emerald-500/20 text-emerald-300 font-semibold' : 'text-gray-200 hover:bg-gray-800 hover:text-white'">
                                                                <span class="mr-2 text-sm font-bold">→</span>
                                                                <span>Sağa Sabitle</span>
                                                                <span x-show="isColumnPinnedRight(field.id)"
                                                                    class="ml-auto text-emerald-400 text-xs">✓</span>
                                                            </button>
                                                            <template x-if="isColumnPinned(field.id)">
                                                                <button type="button"
                                                                    @click="setColumnPin(field.id, 'none'); pinOpen = false"
                                                                    class="w-full flex items-center px-3 py-1.5 text-red-300 hover:bg-red-500/20 hover:text-red-200 transition-colors cursor-pointer">
                                                                    <span class="mr-2 text-xs font-bold">✕</span>
                                                                    <span>Sabitlemeyi Kaldır</span>
                                                                </button>
                                                            </template>

                                                            <div class="border-t border-gray-700/80 my-1"></div>
                                                            <div
                                                                class="px-2.5 py-1 text-[10px] font-bold text-gray-400 uppercase tracking-wider">
                                                                Sıralama</div>
                                                            <button type="button"
                                                                @click="setSortDirection(field.id, 'asc'); pinOpen = false"
                                                                class="w-full flex items-center px-3 py-1.5 text-gray-200 hover:bg-gray-800 hover:text-white transition-colors cursor-pointer"
                                                                :class="sortFieldId === field.id && sortDirection === 'asc' ? 'bg-indigo-600/30 text-indigo-300 font-semibold' : ''">
                                                                <span class="mr-2 text-xs font-bold">▲</span>
                                                                <span
                                                                    x-text="field.type === 'number' ? 'Küçükten Büyüğe (1 → 9)' : 'A\'dan Z\'ye Sırala'"></span>
                                                                <span
                                                                    x-show="sortFieldId === field.id && sortDirection === 'asc'"
                                                                    class="ml-auto text-indigo-400 text-xs">✓</span>
                                                            </button>
                                                            <button type="button"
                                                                @click="setSortDirection(field.id, 'desc'); pinOpen = false"
                                                                class="w-full flex items-center px-3 py-1.5 text-gray-200 hover:bg-gray-800 hover:text-white transition-colors cursor-pointer"
                                                                :class="sortFieldId === field.id && sortDirection === 'desc' ? 'bg-indigo-600/30 text-indigo-300 font-semibold' : ''">
                                                                <span class="mr-2 text-xs font-bold">▼</span>
                                                                <span
                                                                    x-text="field.type === 'number' ? 'Büyükten Küçüğe (9 → 1)' : 'Z\'den A\'ya Sırala'"></span>
                                                                <span
                                                                    x-show="sortFieldId === field.id && sortDirection === 'desc'"
                                                                    class="ml-auto text-indigo-400 text-xs">✓</span>
                                                            </button>
                                                            <template x-if="sortFieldId === field.id && sortDirection">
                                                                <button type="button"
                                                                    @click="clearSort(); pinOpen = false"
                                                                    class="w-full flex items-center px-3 py-1.5 text-amber-300 hover:bg-amber-500/20 hover:text-amber-200 transition-colors cursor-pointer">
                                                                    <span class="mr-2 text-xs font-bold">↺</span>
                                                                    <span>Sıralamayı Sıfırla</span>
                                                                </button>
                                                            </template>

                                                            <div class="border-t border-gray-700/80 my-1"></div>
                                                            <button type="button"
                                                                @click="hideColumn(field.id); pinOpen = false"
                                                                class="w-full flex items-center px-3 py-1.5 text-gray-300 hover:bg-gray-800 hover:text-white transition-colors cursor-pointer">
                                                                <span class="mr-2 text-xs">👁️</span>
                                                                <span>Bu Sütunu Gizle</span>
                                                            </button>
                                                        </div>
                                                    </div>

                                                    <!-- Filter Dropdown Button -->
                                                    <div class="relative" x-data="{ open: false }">
                                                        <button type="button"
                                                            @click.stop="open = !open; if(open) calcUniqueValues(field.id)"
                                                            @click.away="open = false"
                                                            class="flex items-center text-gray-400 hover:text-white transition-colors p-1"
                                                            :class="{ 'text-indigo-300': filters[field.id] && filters[field.id].length > 0 }">
                                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor"
                                                                viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                                    stroke-width="2"
                                                                    d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z">
                                                                </path>
                                                            </svg>
                                                            <span
                                                                x-show="filters[field.id] && filters[field.id].length > 0"
                                                                class="ml-1 text-[9px] font-bold bg-indigo-500 text-white rounded-full px-1.5 py-0.5"
                                                                x-text="filters[field.id].length"></span>
                                                        </button>

                                                        <!-- Dropdown Menu -->
                                                        <div x-show="open" x-transition.opacity
                                                            class="absolute z-50 mt-2 w-52 rounded-xl shadow-2xl bg-white border border-gray-200 ring-1 ring-black/5 divide-y divide-gray-100"
                                                            style="display: none; min-width: 210px;">
                                                            <!-- Excel Tarzı Hızlı Sıralama -->
                                                            <div
                                                                class="p-1.5 bg-gray-50/70 rounded-t-xl text-xs space-y-0.5">
                                                                <div
                                                                    class="px-2 py-0.5 text-[10px] font-black text-gray-400 uppercase tracking-wider">
                                                                    Sıralama (Excel)</div>
                                                                <button type="button"
                                                                    @click="setSortDirection(field.id, 'asc'); open = false;"
                                                                    class="w-full flex items-center justify-between px-2.5 py-1.5 text-xs rounded-lg transition-colors cursor-pointer"
                                                                    :class="sortFieldId === Number(field.id) && sortDirection === 'asc' ? 'bg-indigo-50 text-indigo-700 font-bold' : 'text-gray-700 hover:bg-gray-100'">
                                                                    <span class="flex items-center gap-1.5">
                                                                        <span
                                                                            class="text-xs font-bold text-indigo-600">▲</span>
                                                                        <span
                                                                            x-text="field.type === 'number' ? 'Küçükten Büyüğe (1 → 9)' : 'A\'dan Z\'ye Sırala'"></span>
                                                                    </span>
                                                                    <span
                                                                        x-show="sortFieldId === Number(field.id) && sortDirection === 'asc'"
                                                                        class="text-indigo-600 text-xs font-black">✓</span>
                                                                </button>
                                                                <button type="button"
                                                                    @click="setSortDirection(field.id, 'desc'); open = false;"
                                                                    class="w-full flex items-center justify-between px-2.5 py-1.5 text-xs rounded-lg transition-colors cursor-pointer"
                                                                    :class="sortFieldId === Number(field.id) && sortDirection === 'desc' ? 'bg-indigo-50 text-indigo-700 font-bold' : 'text-gray-700 hover:bg-gray-100'">
                                                                    <span class="flex items-center gap-1.5">
                                                                        <span
                                                                            class="text-xs font-bold text-indigo-600">▼</span>
                                                                        <span
                                                                            x-text="field.type === 'number' ? 'Büyükten Küçüğe (9 → 1)' : 'Z\'den A\'ya Sırala'"></span>
                                                                    </span>
                                                                    <span
                                                                        x-show="sortFieldId === Number(field.id) && sortDirection === 'desc'"
                                                                        class="text-indigo-600 text-xs font-black">✓</span>
                                                                </button>
                                                                <template
                                                                    x-if="sortFieldId === Number(field.id) && sortDirection">
                                                                    <button type="button"
                                                                        @click="clearSort(); open = false;"
                                                                        class="w-full flex items-center gap-1.5 px-2.5 py-1 text-[11px] text-amber-700 hover:bg-amber-50 rounded-lg font-semibold transition-colors cursor-pointer">
                                                                        <span>↺</span>
                                                                        <span>Sıralamayı Sıfırla</span>
                                                                    </button>
                                                                </template>
                                                            </div>

                                                            <div
                                                                class="p-2 border-b border-gray-100 flex justify-between gap-2 bg-white">
                                                                <span
                                                                    class="text-[10px] font-black text-gray-400 uppercase tracking-wider self-center">Filtre</span>
                                                                <div class="flex items-center gap-2">
                                                                    <button type="button"
                                                                        @click="selectAllFilters(field.id)"
                                                                        class="text-[10px] font-semibold text-indigo-600 hover:text-indigo-800 cursor-pointer">Tümünü
                                                                        Seç</button>
                                                                    <span class="text-gray-300">|</span>
                                                                    <button type="button" @click="clearFilter(field.id)"
                                                                        class="text-[10px] font-semibold text-red-600 hover:text-red-800 cursor-pointer">Temizle</button>
                                                                </div>
                                                            </div>
                                                            <div
                                                                class="max-h-56 overflow-y-auto p-2 space-y-0.5 bg-white rounded-b-xl">
                                                                <template x-for="val in uniqueValues[field.id] || []"
                                                                    :key="val">
                                                                    <label
                                                                        class="flex items-center space-x-2 p-1 hover:bg-gray-50 rounded cursor-pointer">
                                                                        <input type="checkbox" :value="val"
                                                                            x-model="filters[field.id]"
                                                                            @change="calculateTotals()"
                                                                            class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                                                                        <span class="text-xs text-gray-700 truncate"
                                                                            x-text="val === '' ? '(Boş)' : val"
                                                                            :title="val === '' ? '(Boş)' : val"></span>
                                                                    </label>
                                                                </template>
                                                                <div x-show="!(uniqueValues[field.id] && uniqueValues[field.id].length > 0)"
                                                                    class="text-xs text-gray-400 text-center p-2">Veri
                                                                    yok</div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </th>
                                </template>
                            </tr>
                        </thead>

                        <tbody class="bg-white divide-y divide-gray-200">
                            <template x-for="(row, originalRowIndex) in visibleRows()" :key="row.id">
                                <tr class="hover:bg-indigo-50 transition-colors group"
                                    :class="isRowNew(row._origIndex ?? originalRowIndex) ? 'bg-emerald-50/40' : ''">
                                    <td
                                        class="w-12 px-4 py-3 whitespace-nowrap text-center text-sm font-medium text-gray-500 sticky left-0 z-10 bg-white group-hover:bg-indigo-50 border-r border-gray-200 shadow-[2px_0_5px_-2px_rgba(0,0,0,0.06)]">
                                        <div class="flex flex-col items-center justify-center">
                                            <span x-text="originalRowIndex + 1"></span>
                                            <template x-if="isRowNew(row._origIndex ?? originalRowIndex)">
                                                <span
                                                    class="text-[9px] font-black text-emerald-700 bg-emerald-100 px-1 py-0.2 rounded mt-0.5">YENİ</span>
                                            </template>
                                        </div>
                                    </td>
                                    <template x-for="field in fields" :key="field.id">
                                        <td :style="getColumnStickyStyle(field.id)" :class="[
                                                getColumnStickyClass(field.id, 'td'),
                                                {
                                                    'text-right': field.type === 'number',
                                                    'bg-amber-100/90 text-amber-950 font-black ring-1 ring-amber-400 border border-amber-300': isCellChanged(row._origIndex ?? originalRowIndex, field.id)
                                                }
                                            ]"
                                            class="px-4 py-3 text-sm text-gray-900 whitespace-nowrap relative group/cell hover:z-50 focus-within:z-50 transition-colors">
                                            <div class="flex items-center gap-1.5"
                                                :class="field.type === 'number' ? 'justify-end' : ''">
                                                <span x-text="formatValue(row.values[field.id], field.type)"></span>
                                                <template
                                                    x-if="isCellChanged(row._origIndex ?? originalRowIndex, field.id)">
                                                    <span
                                                        class="inline-flex items-center justify-center w-3.5 h-3.5 rounded-full text-[9px] font-black bg-amber-500 text-white shadow-2xs shrink-0"
                                                        title="Sürümler Arası Farklı Değer">Δ</span>
                                                </template>
                                            </div>

                                            <!-- HOVER TOOLTIP: Sürümler Arası Çoklu Değer Karşılaştırması -->
                                            <template
                                                x-if="isCellChanged(row._origIndex ?? originalRowIndex, field.id)">
                                                <div class="absolute left-1/2 -translate-x-1/2 hidden group-hover/cell:flex flex-col z-50 p-2.5 bg-slate-900 text-white text-[11px] rounded-xl shadow-2xl pointer-events-none min-w-[240px] max-w-[340px] border border-slate-700 animate-in fade-in duration-100"
                                                    :class="originalRowIndex <= 1 ? 'top-full mt-1.5' : 'bottom-full mb-1.5'">
                                                    <div
                                                        class="font-bold text-amber-400 border-b border-slate-800 pb-1 mb-1.5 flex justify-between items-center gap-2">
                                                        <span class="flex items-center gap-1">
                                                            <svg class="w-3.5 h-3.5 text-amber-400 shrink-0" fill="none"
                                                                stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                                    stroke-width="2"
                                                                    d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z">
                                                                </path>
                                                            </svg>
                                                            <span>Sürüm Kıyaslaması</span>
                                                        </span>
                                                        <span
                                                            class="text-[9px] text-slate-400 font-normal truncate max-w-[120px]"
                                                            x-text="field.label"></span>
                                                    </div>
                                                    <div class="space-y-1 py-0.5">
                                                        <template
                                                            x-for="item in getCellVersionHistory(row._origIndex ?? originalRowIndex, field.id)"
                                                            :key="item.version_key">
                                                            <div class="flex items-center justify-between gap-3 py-1 px-1.5 rounded transition-colors"
                                                                :class="item.isDifferent ? 'bg-amber-500/20 border border-amber-400/40 text-amber-200' : 'bg-slate-800/40 text-slate-300'">
                                                                <div class="flex items-center gap-1.5 shrink-0">
                                                                    <span class="font-bold font-mono text-[10px]"
                                                                        :class="item.isCurrent ? 'text-emerald-400' : 'text-amber-300'"
                                                                        x-text="item.label + ':'"></span>
                                                                    <template x-if="item.time">
                                                                        <span
                                                                            class="text-[9px] text-slate-400 font-mono hidden sm:inline"
                                                                            x-text="'(' + item.time + ')'"></span>
                                                                    </template>
                                                                </div>
                                                                <span
                                                                    class="font-mono text-right truncate break-all font-semibold"
                                                                    :class="item.isDifferent ? 'text-amber-200 font-bold' : 'text-slate-300'"
                                                                    x-text="item.formattedVal || '-'"></span>
                                                            </div>
                                                        </template>
                                                    </div>
                                                    <div
                                                        class="mt-1.5 pt-1 border-t border-slate-800 text-[9px] text-slate-400 italic text-center">
                                                        Farklı değer içeren sürümler sarı çerçeve ile belirtilmiştir
                                                    </div>
                                                </div>
                                            </template>
                                        </td>
                                    </template>
                                </tr>
                            </template>
                        </tbody>

                        <template x-if="showTotals">
                            <tfoot class="bg-gray-100">
                                <tr class="bg-indigo-50/50">
                                    <td class="sticky left-0 z-10 bg-indigo-50 px-4 py-3 font-bold text-right text-indigo-800 text-[11px] uppercase border-b border-indigo-100 border-r border-indigo-200 shadow-[2px_0_5px_-2px_rgba(0,0,0,0.06)]"
                                        colspan="1">Görünen Toplam:</td>
                                    <template x-for="field in fields" :key="'filtered-'+field.id">
                                        <td :style="getColumnStickyStyle(field.id)"
                                            :class="getColumnStickyClass(field.id, 'tfoot-sub')"
                                            class="px-4 py-3 font-bold text-indigo-700 text-right whitespace-nowrap border-b border-indigo-100">
                                            <template x-if="field.is_summable && field.type === 'number'">
                                                <span x-text="formatTotalDisplay(filteredTotals[field.id])"></span>
                                            </template>
                                        </td>
                                    </template>
                                </tr>
                                <tr>
                                    <td class="sticky left-0 z-10 bg-gray-100 px-4 py-3 font-bold text-right text-gray-500 text-[10px] uppercase border-r border-gray-200 shadow-[2px_0_5px_-2px_rgba(0,0,0,0.06)]"
                                        colspan="1">Genel Toplam (Tümü):</td>
                                    <template x-for="field in fields" :key="'all-'+field.id">
                                        <td :style="getColumnStickyStyle(field.id)"
                                            :class="getColumnStickyClass(field.id, 'tfoot-total')"
                                            class="px-4 py-3 font-bold text-gray-500 text-right whitespace-nowrap">
                                            <template x-if="field.is_summable && field.type === 'number'">
                                                <span x-text="formatTotalDisplay(totals[field.id])"></span>
                                            </template>
                                        </td>
                                    </template>
                                </tr>
                            </tfoot>
                        </template>
                    </table>
                </div>
            </div>

            <!-- Yönetici Onay/Red Modülü (Model 1: Hiyerarşik Grup Yöneticisi & Superadmin) -->
            @if(!empty($canApprove) && $canApprove && $formEntry->status === 'submitted')
                <div class="bg-white overflow-hidden shadow-lg sm:rounded-xl border border-indigo-100 mt-6"
                    x-data="{ action: '', submitting: false }">
                    <div
                        class="px-6 py-4 bg-gradient-to-r from-indigo-50 to-indigo-100/50 border-b border-indigo-100 flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <span class="w-2.5 h-2.5 rounded-full bg-indigo-600"></span>
                            <h3 class="text-base font-bold text-indigo-950">Form Değerlendirme & Onay Modülü</h3>
                        </div>
                        <span class="text-xs text-indigo-600 font-semibold bg-indigo-100/80 px-2.5 py-1 rounded-md">
                            {{ $formEntry->user->display_group_name }} &bull; {{ $formEntry->user->name }}
                        </span>
                    </div>
                    <div class="p-6">
                        <form action="{{ route('form-entries.review', $formEntry) }}" method="POST"
                            @submit="if(submitting) { $event.preventDefault(); return false; } submitting = true; document.body.classList.add('cursor-wait');">
                            @csrf
                            <input type="hidden" name="status" x-model="action">

                            <div class="mb-4">
                                <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">
                                    Reddetme Gerekçesi (Yalnızca Form Reddediliyorsa Zorunludur):
                                </label>
                                <textarea name="rejection_reason" rows="2"
                                    class="w-full text-sm rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 p-3"
                                    placeholder="Formu reddederken personele rehberlik edecek detaylı red sebebini yazınız..."></textarea>
                            </div>

                            <div class="flex flex-wrap gap-3">
                                <button type="submit"
                                    @click="if(submitting) { $event.preventDefault(); return false; } action = 'approved'"
                                    :disabled="submitting"
                                    :class="submitting ? 'opacity-70 cursor-wait pointer-events-none' : ''"
                                    class="bg-emerald-600 hover:bg-emerald-700 text-white font-bold py-2.5 px-6 rounded-lg shadow-sm transition flex items-center text-xs uppercase tracking-wider cursor-pointer">
                                    <svg x-show="!submitting" class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M5 13l4 4L19 7"></path>
                                    </svg>
                                    <svg x-show="submitting" class="animate-spin -ml-1 mr-1.5 h-4 w-4 text-white"
                                        xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                            stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor"
                                            d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                        </path>
                                    </svg>
                                    <span
                                        x-text="submitting && action === 'approved' ? 'Onaylanıyor...' : 'Veriyi Onayla'">Veriyi
                                        Onayla</span>
                                </button>
                                <button type="submit"
                                    @click="if(submitting) { $event.preventDefault(); return false; } action = 'rejected'"
                                    :disabled="submitting"
                                    :class="submitting ? 'opacity-70 cursor-wait pointer-events-none' : ''"
                                    class="bg-rose-600 hover:bg-rose-700 text-white font-bold py-2.5 px-6 rounded-lg shadow-sm transition flex items-center text-xs uppercase tracking-wider cursor-pointer">
                                    <svg x-show="!submitting" class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M6 18L18 6M6 6l12 12"></path>
                                    </svg>
                                    <svg x-show="submitting" class="animate-spin -ml-1 mr-1.5 h-4 w-4 text-white"
                                        xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                            stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor"
                                            d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                        </path>
                                    </svg>
                                    <span
                                        x-text="submitting && action === 'rejected' ? 'Reddediliyor...' : 'Reddet ve Geri Gönder'">Reddet
                                        ve Geri Gönder</span>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            @endif

            <!-- Personel Düzenleme (Revizyon) Talebi İncelemesi (Grup Yöneticisi / Superadmin) -->
            @if(!empty($canManageRevision) && $canManageRevision && !empty($pendingRevision))
                <div class="bg-white overflow-hidden shadow-lg sm:rounded-xl border border-amber-200 mt-6"
                    x-data="{ revAction: 'approve', submitting: false }">
                    <div
                        class="px-6 py-4 bg-gradient-to-r from-amber-50 to-amber-100/50 border-b border-amber-200 flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <span class="w-2.5 h-2.5 rounded-full bg-amber-600 animate-pulse"></span>
                            <h3 class="text-base font-bold text-amber-950">Personel Düzenleme (Revizyon) Talebi İncelemesi
                            </h3>
                        </div>
                        <span class="text-xs text-amber-800 font-bold bg-amber-100 px-2.5 py-1 rounded-md">
                            Talep Tarihi: {{ $pendingRevision->created_at->format('d.m.Y H:i') }}
                        </span>
                    </div>
                    <div class="p-6 space-y-4">
                        <div
                            class="bg-amber-50/70 p-4 rounded-xl border border-amber-200 text-xs text-amber-950 space-y-1.5">
                            <p><strong>Talep Eden Personel:</strong> {{ $pendingRevision->user->name }}
                                ({{ $pendingRevision->user->display_group_name }})</p>
                            <p><strong>Belirtilen Düzenleme Gerekçesi:</strong> {{ $pendingRevision->reason }}</p>
                        </div>

                        <form action="{{ route('form-entries.revision-requests.review', $pendingRevision) }}" method="POST"
                            @submit="if(submitting) { $event.preventDefault(); return false; } submitting = true; document.body.classList.add('cursor-wait');">
                            @csrf
                            <input type="hidden" name="action" x-model="revAction">

                            <div class="mb-4" x-show="revAction === 'reject'">
                                <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">
                                    Red Gerekçesi (Personele Bildirilecektir):
                                </label>
                                <textarea name="rejection_reason" rows="2"
                                    class="w-full text-sm rounded-lg border-gray-300 shadow-sm focus:border-rose-500 focus:ring-rose-500 p-3"
                                    placeholder="Talebi neden reddettiğinizi açıklayınız..."></textarea>
                            </div>

                            <div class="flex flex-wrap gap-3">
                                <button type="submit"
                                    @click="if(submitting) { $event.preventDefault(); return false; } revAction = 'approve'"
                                    :disabled="submitting"
                                    :class="submitting ? 'opacity-70 cursor-wait pointer-events-none' : ''"
                                    class="bg-emerald-600 hover:bg-emerald-700 text-white font-bold py-2.5 px-6 rounded-lg shadow-sm transition flex items-center text-xs uppercase tracking-wider cursor-pointer">
                                    <svg x-show="!submitting" class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M5 13l4 4L19 7"></path>
                                    </svg>
                                    <svg x-show="submitting" class="animate-spin -ml-1 mr-1.5 h-4 w-4 text-white"
                                        xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                            stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor"
                                            d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                        </path>
                                    </svg>
                                    <span
                                        x-text="submitting && revAction === 'approve' ? 'Onaylanıyor...' : 'Revizyonu Onayla ve Düzenlemeye Aç'">Revizyonu
                                        Onayla ve Düzenlemeye Aç</span>
                                </button>
                                <button type="button" @click="revAction = 'reject'" x-show="revAction !== 'reject'"
                                    :disabled="submitting"
                                    class="bg-rose-600 hover:bg-rose-700 text-white font-bold py-2.5 px-6 rounded-lg shadow-sm transition flex items-center text-xs uppercase tracking-wider cursor-pointer">
                                    <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M6 18L18 6M6 6l12 12"></path>
                                    </svg>
                                    Talebi Reddet
                                </button>
                                <button type="submit" x-show="revAction === 'reject'"
                                    @click="if(submitting) { $event.preventDefault(); return false; }"
                                    :disabled="submitting"
                                    :class="submitting ? 'opacity-70 cursor-wait pointer-events-none' : ''"
                                    class="bg-rose-700 hover:bg-rose-800 text-white font-bold py-2.5 px-6 rounded-lg shadow-sm transition flex items-center text-xs uppercase tracking-wider cursor-pointer">
                                    <svg x-show="submitting" class="animate-spin -ml-1 mr-1.5 h-4 w-4 text-white"
                                        xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                            stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor"
                                            d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                        </path>
                                    </svg>
                                    <span
                                        x-text="submitting && revAction === 'reject' ? 'Reddediliyor...' : 'Reddi Onayla ve Gönder'">Reddi
                                        Onayla ve Gönder</span>
                                </button>
                                <button type="button" @click="revAction = 'approve'" x-show="revAction === 'reject'"
                                    :disabled="submitting"
                                    class="bg-gray-100 hover:bg-gray-200 text-gray-700 font-bold py-2.5 px-4 rounded-lg transition text-xs cursor-pointer disabled:opacity-50">
                                    Vazgeç
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            @endif

            <!-- Revizyon Talepleri Geçmişi Tablosu -->
            @if($formEntry->revisionRequests->count() > 0)
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-xl border border-gray-200 mt-8">
                    <div class="p-4 border-b border-gray-100 bg-gray-50 flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2">
                                </path>
                            </svg>
                            <h4 class="text-sm font-bold text-gray-800">Form Revizyon (Düzenleme) Talepleri Geçmişi</h4>
                        </div>
                        <span class="text-xs font-semibold px-2 py-0.5 rounded-full bg-gray-100 text-gray-600">
                            {{ $formEntry->revisionRequests->count() }} Talep
                        </span>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 text-xs">
                            <thead class="bg-gray-50/50">
                                <tr>
                                    <th class="px-6 py-3 text-left font-bold text-gray-500 uppercase tracking-wider">Talep
                                        Tarihi</th>
                                    <th class="px-6 py-3 text-left font-bold text-gray-500 uppercase tracking-wider">Talep
                                        Eden</th>
                                    <th class="px-6 py-3 text-left font-bold text-gray-500 uppercase tracking-wider">Gerekçe
                                    </th>
                                    <th class="px-6 py-3 text-left font-bold text-gray-500 uppercase tracking-wider">Durum
                                    </th>
                                    <th class="px-6 py-3 text-left font-bold text-gray-500 uppercase tracking-wider">
                                        Değerlendiren Yönetici</th>
                                    <th class="px-6 py-3 text-left font-bold text-gray-500 uppercase tracking-wider">Red
                                        Gerekçesi</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-100">
                                @foreach($formEntry->revisionRequests as $rev)
                                    <tr class="hover:bg-gray-50 transition-colors">
                                        <td class="px-6 py-3 whitespace-nowrap text-gray-600 font-medium">
                                            {{ $rev->created_at->format('d.m.Y H:i') }}
                                        </td>
                                        <td class="px-6 py-3 whitespace-nowrap font-bold text-gray-900">
                                            {{ $rev->user->name }}
                                        </td>
                                        <td class="px-6 py-3 text-gray-700 max-w-xs break-words">
                                            {{ $rev->reason }}
                                        </td>
                                        <td class="px-6 py-3 whitespace-nowrap">
                                            @if($rev->status === 'approved')
                                                <span
                                                    class="px-2.5 py-1 inline-flex text-[11px] font-bold rounded-full bg-emerald-50 text-emerald-700 border border-emerald-200">
                                                    Onaylandı
                                                </span>
                                            @elseif($rev->status === 'rejected')
                                                <span
                                                    class="px-2.5 py-1 inline-flex text-[11px] font-bold rounded-full bg-rose-50 text-rose-700 border border-rose-200">
                                                    Reddedildi
                                                </span>
                                            @else
                                                <span
                                                    class="px-2.5 py-1 inline-flex text-[11px] font-bold rounded-full bg-amber-50 text-amber-700 border border-amber-200 animate-pulse">
                                                    İnceleniyor
                                                </span>
                                            @endif
                                        </td>
                                        <td class="px-6 py-3 whitespace-nowrap text-gray-700">
                                            {{ $rev->reviewer?->name ?? '-' }}
                                        </td>
                                        <td class="px-6 py-3 text-rose-700 max-w-xs break-words">
                                            {{ $rev->rejection_reason ?? '-' }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif

            <!-- Form İşlem Geçmişi (Log Tablosu) -->
            @if(isset($canViewLogs) && $canViewLogs)
                @php
                    $distinctUsers = $logs->map(function ($l) {
                        return [
                            'id' => $l->user_id ? (string) $l->user_id : 'system',
                            'name' => $l->user ? $l->user->name : 'Sistem'
                        ];
                    })->unique('id')->sortBy('name');

                    $distinctActions = $logs->map(function ($l) {
                        return [
                            'slug' => (string) $l->action,
                            'label' => $l->action_label
                        ];
                    })->unique('slug')->sortBy('label');
                @endphp
                <div x-data="{ 
                                    searchQuery: '',
                                    selectedUser: '',
                                    selectedDate: '',
                                    selectedAction: '',
                                    showAllLogs: false, 
                                    showLogModal: false, 
                                    activeLogDetail: { date: '', user: '', action: '', badgeClass: '', description: '' },
                                    logsMeta: [
                                        @foreach($logs as $index => $log)
                                            {
                                                index: {{ $index }},
                                                userId: '{{ $log->user_id ?? 'system' }}',
                                                dateStr: '{{ $log->created_at->format('Y-m-d') }}',
                                                action: '{{ $log->action }}',
                                                searchable: @js(mb_strtolower($log->created_at->format('d.m.Y H:i:s') . ' ' . ($log->user->name ?? 'Sistem') . ' ' . $log->action_label . ' ' . $log->description))
                                            },
                                        @endforeach
                                    ],
                                    get filteredLogs() {
                                        return this.logsMeta.filter(item => {
                                            if (this.selectedUser && this.selectedUser !== item.userId) return false;
                                            if (this.selectedDate && this.selectedDate !== item.dateStr) return false;
                                            if (this.selectedAction && this.selectedAction !== item.action) return false;
                                            if (this.searchQuery) {
                                                const q = this.searchQuery.toLowerCase().trim();
                                                if (!item.searchable.includes(q)) return false;
                                            }
                                            return true;
                                        });
                                    },
                                    get isFiltering() {
                                        return Boolean(this.searchQuery || this.selectedUser || this.selectedDate || this.selectedAction);
                                    },
                                    isRowVisible(index) {
                                        const match = this.filteredLogs.some(item => item.index === index);
                                        if (!match) return false;
                                        if (!this.isFiltering && !this.showAllLogs && index >= 10) return false;
                                        return true;
                                    },
                                    resetFilters() {
                                        this.searchQuery = '';
                                        this.selectedUser = '';
                                        this.selectedDate = '';
                                        this.selectedAction = '';
                                    }
                                }" class="bg-white overflow-hidden shadow-sm sm:rounded-lg border border-gray-200 mt-8">
                    <div
                        class="p-4 border-b border-gray-100 bg-gray-50 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                        <div class="flex items-center">
                            <svg class="w-5 h-5 text-gray-500 mr-2 shrink-0" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            <span class="text-gray-700 font-bold">Form İşlem Geçmişi</span>
                        </div>
                        @if($logs->count() > 0)
                            <div class="flex items-center gap-2">
                                <span class="text-xs font-semibold px-2.5 py-0.5 rounded-full bg-gray-100 text-gray-600"
                                    x-show="!isFiltering">
                                    Toplam {{ $logs->count() }} Kayıt
                                </span>
                                <span
                                    class="text-xs font-semibold px-2.5 py-0.5 rounded-full bg-indigo-50 text-indigo-700 border border-indigo-100"
                                    x-show="isFiltering" x-cloak>
                                    Filtrelenen: <span x-text="filteredLogs.length"></span> / {{ $logs->count() }} Kayıt
                                </span>
                            </div>
                        @endif
                    </div>

                    @if($logs->count() > 0)
                        <!-- Anlık Arama ve Filtreleme Çubuğu -->
                        <div class="p-3 bg-slate-50/70 border-b border-gray-100 flex flex-wrap items-center gap-2.5">
                            <!-- Anlık Arama Inputu -->
                            <div class="relative flex-1 min-w-[200px]">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                    </svg>
                                </div>
                                <input type="text" x-model="searchQuery"
                                    placeholder="İşlem, kullanıcı veya açıklamada anlık ara..."
                                    class="w-full pl-9 pr-3 py-1.5 text-xs rounded-xl border border-gray-200 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 bg-white shadow-2xs placeholder-gray-400">
                            </div>

                            <!-- Kullanıcı Filtresi -->
                            @if($distinctUsers->count() > 1)
                                <div class="min-w-[150px]">
                                    <select x-model="selectedUser"
                                        class="w-full text-xs py-1.5 px-3 rounded-xl border border-gray-200 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 bg-white text-gray-700 shadow-2xs">
                                        <option value="">Tüm Kullanıcılar ({{ $distinctUsers->count() }})</option>
                                        @foreach($distinctUsers as $u)
                                            <option value="{{ $u['id'] }}">{{ $u['name'] }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            @endif

                            <!-- Tarih Filtresi -->
                            <div class="min-w-[130px]">
                                <input type="date" x-model="selectedDate" title="Tarihe Göre Filtrele"
                                    class="w-full text-xs py-1.5 px-3 rounded-xl border border-gray-200 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 bg-white text-gray-700 shadow-2xs">
                            </div>

                            <!-- İşlem Filtresi -->
                            @if($distinctActions->count() > 1)
                                <div class="min-w-[140px]">
                                    <select x-model="selectedAction"
                                        class="w-full text-xs py-1.5 px-3 rounded-xl border border-gray-200 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 bg-white text-gray-700 shadow-2xs">
                                        <option value="">Tüm İşlemler</option>
                                        @foreach($distinctActions as $a)
                                            <option value="{{ $a['slug'] }}">{{ $a['label'] }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            @endif

                            <!-- Filtreleri Temizle Butonu -->
                            <button type="button" x-show="isFiltering" @click="resetFilters()" x-cloak
                                class="inline-flex items-center gap-1 text-xs text-rose-600 hover:text-rose-800 bg-rose-50 hover:bg-rose-100 px-3 py-1.5 rounded-xl border border-rose-200 transition-colors font-bold shadow-2xs cursor-pointer">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M6 18L18 6M6 6l12 12" />
                                </svg>
                                <span>Temizle</span>
                            </button>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50/50">
                                    <tr>
                                        <th
                                            class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">
                                            Tarih</th>
                                        <th
                                            class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">
                                            Kullanıcı</th>
                                        <th
                                            class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">
                                            İşlem</th>
                                        <th
                                            class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">
                                            Detay / Açıklama</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-100">
                                    <!-- Arama/Filtre Sonucu Bulunamadı Durumu -->
                                    <tr x-show="isFiltering && filteredLogs.length === 0" x-cloak>
                                        <td colspan="4" class="px-6 py-10 text-center text-gray-500">
                                            <div class="flex flex-col items-center justify-center gap-2">
                                                <div
                                                    class="w-10 h-10 rounded-full bg-slate-100 flex items-center justify-center text-slate-400">
                                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                            d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                                    </svg>
                                                </div>
                                                <p class="text-xs font-bold text-slate-700">Arama kriterlerine uygun işlem kaydı
                                                    bulunamadı.</p>
                                                <p class="text-[11px] text-slate-400">Farklı bir arama terimi, kullanıcı veya
                                                    tarih deneyebilir ya da filtreleri temizleyebilirsiniz.</p>
                                                <button type="button" @click="resetFilters()"
                                                    class="mt-1 inline-flex items-center gap-1 px-3 py-1 text-xs font-bold text-indigo-600 hover:text-indigo-800 bg-indigo-50 hover:bg-indigo-100 rounded-lg transition-colors cursor-pointer">
                                                    Filtreleri Temizle
                                                </button>
                                            </div>
                                        </td>
                                    </tr>

                                    @foreach($logs as $index => $log)
                                        <tr x-show="isRowVisible({{ $index }})" x-cloak class="hover:bg-gray-50 transition-colors">
                                            <td class="px-6 py-3 whitespace-nowrap text-xs font-medium text-gray-500">
                                                {{ $log->created_at->format('d.m.Y H:i:s') }}
                                            </td>
                                            <td class="px-6 py-3 whitespace-nowrap">
                                                @if($log->user)
                                                    <div class="flex items-center">
                                                        <div
                                                            class="flex-shrink-0 h-6 w-6 bg-gradient-to-br from-indigo-50 to-purple-50 rounded-full flex items-center justify-center text-indigo-700 font-bold text-xs border border-indigo-200">
                                                            {{ mb_substr($log->user->name, 0, 1) }}
                                                        </div>
                                                        <div class="ml-2 text-xs font-bold text-gray-900">
                                                            <a href="{{ route('users.profile', $log->user->id) }}"
                                                                class="hover:text-indigo-600 hover:underline transition-colors">
                                                                {{ $log->user->name }}
                                                            </a>
                                                        </div>
                                                    </div>
                                                @else
                                                    <span class="text-xs text-gray-400 italic">Sistem</span>
                                                @endif
                                            </td>
                                            <td class="px-6 py-3 whitespace-nowrap">
                                                <span
                                                    class="px-2.5 py-0.5 inline-flex text-xs leading-5 font-bold rounded-full border {{ $log->action_badge_class }}">
                                                    {{ $log->action_label }}
                                                </span>
                                            </td>
                                            <td class="px-6 py-3 text-xs text-gray-600">
                                                @php
                                                    $desc = (string) $log->description;
                                                    $isLong = strlen($desc) > 120 || str_contains($desc, "\n") || str_contains($desc, '•');
                                                    $summaryLine = $desc;
                                                    if (str_contains($desc, "\n")) {
                                                        $lines = explode("\n", $desc);
                                                        $summaryLine = trim($lines[0]);
                                                    }
                                                    $diffCount = substr_count($desc, '•');
                                                @endphp

                                                @if(!$isLong)
                                                    <span class="text-slate-700 font-medium">{{ $desc }}</span>
                                                @else
                                                    <div class="space-y-1">
                                                        <p class="text-slate-800 font-medium line-clamp-1 leading-snug">
                                                            {{ $summaryLine }}
                                                        </p>
                                                        <div class="flex items-center gap-2">
                                                            @if($diffCount > 0)
                                                                <span
                                                                    class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-bold bg-indigo-50 text-indigo-700 border border-indigo-100">
                                                                    {{ $diffCount }} Alan Değişikliği
                                                                </span>
                                                            @endif
                                                            <button type="button"
                                                                @click="activeLogDetail = {
                                                                                                                                    date: '{{ $log->created_at->format('d.m.Y H:i:s') }}',
                                                                                                                                    user: '{{ $log->user->name ?? 'Sistem' }}',
                                                                                                                                    action: '{{ $log->action_label }}',
                                                                                                                                    badgeClass: '{{ $log->action_badge_class }}',
                                                                                                                                    description: @js($log->description)
                                                                                                                                }; showLogModal = true;"
                                                                class="inline-flex items-center gap-1 text-[11px] font-bold text-indigo-600 hover:text-indigo-800 hover:underline cursor-pointer">
                                                                <svg class="w-3.5 h-3.5 text-indigo-600" fill="none"
                                                                    stroke="currentColor" viewBox="0 0 24 24">
                                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                                        stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                                        stroke-width="2"
                                                                        d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                                                </svg>
                                                                <span>Detayları İncele</span>
                                                            </button>
                                                        </div>
                                                    </div>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <!-- İŞLEM DETAY MODALI (UZUN LOGLAR İÇİN) -->
                        <div x-show="showLogModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto"
                            aria-labelledby="log-detail-modal-title" role="dialog" aria-modal="true" style="display: none;">
                            <div
                                class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                                <div x-show="showLogModal" x-transition:enter="ease-out duration-200"
                                    x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                                    x-transition:leave="ease-in duration-150" x-transition:leave-start="opacity-100"
                                    x-transition:leave-end="opacity-0"
                                    class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs transition-opacity"
                                    @click="showLogModal = false"></div>

                                <span class="hidden sm:inline-block sm:align-middle sm:h-screen"
                                    aria-hidden="true">&#8203;</span>

                                <div x-show="showLogModal" x-transition:enter="ease-out duration-200"
                                    x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                                    x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                                    x-transition:leave="ease-in duration-150"
                                    x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                                    x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                                    class="inline-block align-bottom bg-white rounded-3xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full border border-slate-100">

                                    <div
                                        class="bg-gradient-to-r from-slate-50 via-indigo-50/30 to-white px-6 py-4 border-b border-slate-100 flex items-center justify-between">
                                        <div class="flex items-center gap-3">
                                            <div
                                                class="w-10 h-10 rounded-2xl bg-indigo-50 text-indigo-600 flex items-center justify-center shadow-2xs">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                                </svg>
                                            </div>
                                            <div>
                                                <h3 class="text-base font-extrabold text-slate-800" id="log-detail-modal-title">
                                                    İşlem & Değişiklik Detayı</h3>
                                                <p class="text-xs text-slate-500 mt-0.5">
                                                    <span x-text="activeLogDetail.user" class="font-bold text-slate-700"></span>
                                                    &bull;
                                                    <span x-text="activeLogDetail.date"></span>
                                                </p>
                                            </div>
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <span :class="activeLogDetail.badgeClass"
                                                class="px-2.5 py-1 text-xs font-bold rounded-full border"
                                                x-text="activeLogDetail.action"></span>
                                            <button type="button" @click="showLogModal = false"
                                                class="p-2 text-slate-400 hover:text-slate-600 hover:bg-slate-100 rounded-xl transition-colors">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M6 18L18 6M6 6l12 12" />
                                                </svg>
                                            </button>
                                        </div>
                                    </div>

                                    <div class="p-6 max-h-[60vh] overflow-y-auto space-y-4 bg-slate-50/50">
                                        <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-2xs">
                                            <div class="text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-2">
                                                Kayıtlı Değişiklik / Açıklama İçeriği</div>
                                            <div class="text-xs sm:text-sm font-medium text-slate-800 whitespace-pre-wrap leading-relaxed font-mono bg-slate-50 p-3 rounded-xl border border-slate-100 overflow-x-auto select-text"
                                                x-text="activeLogDetail.description"></div>
                                        </div>
                                    </div>

                                    <div class="bg-white px-6 py-4 border-t border-slate-100 flex justify-between items-center">
                                        <button type="button"
                                            @click="navigator.clipboard.writeText(activeLogDetail.description); alert('Log detayı panoya kopyalandı.');"
                                            class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl text-xs font-bold text-slate-700 hover:text-indigo-600 hover:bg-indigo-50 border border-slate-200 transition-colors">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3" />
                                            </svg>
                                            <span>Detayı Kopyala</span>
                                        </button>
                                        <button type="button" @click="showLogModal = false"
                                            class="px-5 py-2 bg-slate-800 hover:bg-slate-900 text-white rounded-xl text-xs font-bold transition-all shadow-xs">
                                            Kapat
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        @if($logs->count() > 10)
                            <div x-show="!isFiltering" class="p-3 bg-gray-50/80 border-t border-gray-100 text-center">
                                <button type="button" @click="showAllLogs = !showAllLogs"
                                    class="inline-flex items-center px-4 py-1.5 text-xs font-bold text-indigo-600 hover:text-indigo-800 bg-white hover:bg-indigo-50 border border-gray-200 hover:border-indigo-200 rounded-lg transition-all shadow-2xs">
                                    <span
                                        x-text="showAllLogs ? 'Daha Az Göster' : 'Devamını Görüntüle ({{ $logs->count() - 10 }} Kayıt Daha)'"></span>
                                    <svg class="w-3.5 h-3.5 ml-1.5 transform transition-transform"
                                        :class="showAllLogs ? 'rotate-180' : ''" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7">
                                        </path>
                                    </svg>
                                </button>
                            </div>
                        @endif
                    @else
                        <div class="p-6 text-center text-gray-500 text-sm">
                            Bu form için henüz bir işlem geçmişi bulunmuyor.
                        </div>
                    @endif
                </div>
            @endif

        </div>
    </div>

    {{-- Form İşlem ve Onay Tarihçesi (Eski Kayıtlar) Modalı --}}
    @if(!empty($canViewHistory) && $canViewHistory)
        <div x-data="{ open: false }" @open-history-modal.window="open = true" x-show="open" x-cloak
            class="fixed inset-0 z-50 overflow-y-auto" style="display: none;">
            <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 transition-opacity bg-slate-900/60 backdrop-blur-xs" @click="open = false"></div>
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>
                <div
                    class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-3xl sm:w-full border border-gray-100">

                    {{-- Modal Header --}}
                    <div
                        class="bg-gradient-to-r from-slate-900 via-indigo-950 to-slate-900 px-6 py-4 flex items-center justify-between text-white border-b border-indigo-900/40">
                        <div class="flex items-center gap-3">
                            <div class="p-2 rounded-xl bg-white/10 backdrop-blur-xs text-indigo-300 border border-white/10">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-base font-extrabold tracking-tight text-white flex items-center gap-2">
                                    Form İşlem ve Onay Tarihçesi
                                    <span
                                        class="text-xs px-2 py-0.5 rounded-full bg-indigo-500/30 text-indigo-200 border border-indigo-400/30">Kayıt
                                        #{{ $formEntry->id }}</span>
                                </h3>
                                <p class="text-xs text-indigo-200/80">{{ $formEntry->template->name }} -
                                    {{ $formEntry->entry_date->format('d.m.Y') }}
                                </p>
                            </div>
                        </div>
                        <button type="button" @click="open = false"
                            class="text-indigo-200 hover:text-white p-1 rounded-lg hover:bg-white/10 transition cursor-pointer">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>

                    {{-- Modal Body: Timeline --}}
                    <div class="p-6 max-h-[70vh] overflow-y-auto space-y-6 bg-slate-50/50">
                        @if(!empty($historyLogs) && $historyLogs->isNotEmpty())
                            <div
                                class="relative pl-6 sm:pl-8 space-y-6 before:absolute before:left-3 sm:before:left-4 before:top-2 before:bottom-2 before:w-0.5 before:bg-slate-200">
                                @foreach($historyLogs as $idx => $item)
                                    @php
                                        $actionInfo = match ($item->action) {
                                            'form_entry.create' => [
                                                'bg' => 'bg-blue-500',
                                                'badge' => 'bg-blue-50 text-blue-700 border-blue-200',
                                                'title' => 'Form Oluşturuldu (Taslak)'
                                            ],
                                            'form_entry.submit' => [
                                                'bg' => 'bg-amber-500',
                                                'badge' => 'bg-amber-50 text-amber-700 border-amber-200',
                                                'title' => 'Onaya Gönderildi'
                                            ],
                                            'form_entry.approved' => [
                                                'bg' => 'bg-emerald-500',
                                                'badge' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                                                'title' => 'Onaylandı'
                                            ],
                                            'form_entry.rejected' => [
                                                'bg' => 'bg-rose-500',
                                                'badge' => 'bg-rose-50 text-rose-700 border-rose-200',
                                                'title' => 'Form Reddedildi'
                                            ],
                                            'form_entry.revert' => [
                                                'bg' => 'bg-slate-700',
                                                'badge' => 'bg-slate-100 text-slate-800 border-slate-300',
                                                'title' => 'Onay/Red Kararı Geri Alındı'
                                            ],
                                            'form_entry.update' => [
                                                'bg' => 'bg-indigo-600',
                                                'badge' => 'bg-indigo-50 text-indigo-700 border-indigo-200',
                                                'title' => 'Veri Güncellendi / Düzenlendi'
                                            ],
                                            'form_entry.revision_requested' => [
                                                'bg' => 'bg-amber-500',
                                                'badge' => 'bg-amber-50 text-amber-800 border-amber-300',
                                                'title' => 'Düzenleme (Revizyon) Talebi İletildi'
                                            ],
                                            'form_entry.revision_approved' => [
                                                'bg' => 'bg-emerald-600',
                                                'badge' => 'bg-emerald-50 text-emerald-800 border-emerald-300',
                                                'title' => 'Düzenleme Talebi Onaylandı'
                                            ],
                                            'form_entry.revision_rejected' => [
                                                'bg' => 'bg-rose-600',
                                                'badge' => 'bg-rose-50 text-rose-800 border-rose-300',
                                                'title' => 'Düzenleme Talebi Reddedildi'
                                            ],
                                            'form_entry.withdrawn' => [
                                                'bg' => 'bg-slate-600',
                                                'badge' => 'bg-slate-100 text-slate-700 border-slate-300',
                                                'title' => 'Onay Sürecinden Geri Çekildi'
                                            ],
                                            default => [
                                                'bg' => 'bg-slate-500',
                                                'badge' => 'bg-gray-100 text-gray-700 border-gray-200',
                                                'title' => ucwords(str_replace(['form_entry.', '_'], ['', ' '], $item->action))
                                            ]
                                        };
                                    @endphp
                                    <div class="relative group">
                                        {{-- Dot --}}
                                        <div
                                            class="absolute -left-6 sm:-left-8 top-1.5 w-6 h-6 rounded-full {{ $actionInfo['bg'] }} text-white flex items-center justify-center ring-4 ring-white shadow-xs">
                                            <span class="text-[10px] font-extrabold">{{ $historyLogs->count() - $idx }}</span>
                                        </div>

                                        {{-- Card --}}
                                        <div
                                            class="bg-white p-4 rounded-xl border border-slate-200/80 shadow-xs hover:border-slate-300 transition-all">
                                            <div
                                                class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 border-b border-slate-100 pb-2.5 mb-2.5">
                                                <div class="flex items-center gap-2 flex-wrap">
                                                    <span
                                                        class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold border {{ $actionInfo['badge'] }}">
                                                        {{ $actionInfo['title'] }}
                                                    </span>
                                                    <span class="text-xs font-bold text-gray-900">
                                                        {{ $item->user->name ?? 'Sistem' }}
                                                    </span>
                                                    @if($item->user)
                                                        <span
                                                            class="text-[11px] text-gray-500 font-medium bg-gray-100 px-2 py-0.5 rounded-md">
                                                            {{ $item->user->display_group_name }}
                                                        </span>
                                                    @endif
                                                </div>
                                                <div
                                                    class="text-xs text-gray-500 font-semibold flex items-center gap-1.5 flex-shrink-0">
                                                    <svg class="w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor"
                                                        viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                            d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                                    </svg>
                                                    {{ $item->created_at->format('d.m.Y H:i:s') }}
                                                </div>
                                            </div>

                                            {{-- Description / Diff --}}
                                            <div class="text-xs text-gray-700 leading-relaxed">
                                                @if(str_contains($item->description, 'Değiştirilen Alanlar:'))
                                                    @php
                                                        $parts = explode("Değiştirilen Alanlar:\n• ", $item->description);
                                                        $lead = $parts[0] ?? '';
                                                        $diffs = isset($parts[1]) ? explode("\n• ", $parts[1]) : [];
                                                    @endphp
                                                    <p class="font-medium text-gray-900 mb-2">{{ trim($lead) }}</p>
                                                    @if(!empty($diffs))
                                                        <div
                                                            class="bg-slate-50 rounded-lg p-3 border border-slate-200/80 space-y-1.5 font-mono text-[11px]">
                                                            <p
                                                                class="font-bold text-slate-800 text-[11px] uppercase tracking-wider mb-1 font-sans">
                                                                Değiştirilen Veriler:</p>
                                                            @foreach($diffs as $diffLine)
                                                                <div class="text-slate-800 flex items-start gap-1.5">
                                                                    <span class="text-indigo-600 font-bold">•</span>
                                                                    <span>{{ $diffLine }}</span>
                                                                </div>
                                                            @endforeach
                                                        </div>
                                                    @endif
                                                @elseif(str_contains($item->description, 'Gerekçe:'))
                                                    @php
                                                        $parts = explode('Gerekçe:', $item->description);
                                                    @endphp
                                                    <p class="font-medium text-gray-900 mb-1.5">{{ trim($parts[0] ?? '') }}</p>
                                                    @if(isset($parts[1]))
                                                        <div
                                                            class="bg-amber-50/80 p-2.5 rounded-lg border border-amber-200 text-amber-950 font-medium">
                                                            <strong>Gerekçe:</strong> {{ trim($parts[1]) }}
                                                        </div>
                                                    @endif
                                                @else
                                                    <p class="font-medium text-gray-800">{{ $item->description }}</p>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="text-center py-10 text-gray-500">
                                <svg class="w-12 h-12 mx-auto text-gray-300 mb-3" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                        d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                <p class="text-sm font-semibold text-gray-700">Henüz Kayıtlı Bir İşlem Geçmişi Bulunmuyor</p>
                                <p class="text-xs text-gray-400 mt-1">Form üzerinde işlem yapıldıkça tüm hareketler burada
                                    kronolojik olarak listelenecektir.</p>
                            </div>
                        @endif
                    </div>

                    {{-- Modal Footer --}}
                    <div class="bg-gray-50 px-6 py-3 flex items-center justify-between border-t border-gray-100">
                        <span class="text-xs text-gray-500 font-semibold">
                            Toplam <strong>{{ !empty($historyLogs) ? $historyLogs->count() : 0 }}</strong> hareket kaydı
                            bulundu
                        </span>
                        <button type="button" @click="open = false"
                            class="px-5 py-2 text-xs font-bold text-gray-700 bg-white hover:bg-gray-100 border border-gray-200 rounded-lg shadow-xs cursor-pointer transition">
                            Kapat
                        </button>
                    </div>

                </div>
            </div>
        </div>
    @endif

    {{-- Revizyon Talebi Modalı --}}
    @if(!empty($canRequestRevision) && $canRequestRevision)
        <div x-data="{ open: false, submitting: false }" @open-revision-modal.window="open = true; submitting = false"
            x-show="open" x-cloak class="fixed inset-0 z-50 overflow-y-auto" style="display: none;">
            <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 transition-opacity bg-slate-900/60 backdrop-blur-xs"
                    @click="if(!submitting) open = false"></div>
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>
                <div
                    class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full border border-gray-100">
                    <form action="{{ route('form-entries.request-revision', $formEntry) }}" method="POST"
                        @submit="if(submitting) { $event.preventDefault(); return false; } submitting = true; document.body.classList.add('cursor-wait');">
                        @csrf
                        <div class="bg-indigo-600 px-6 py-4 flex items-center justify-between text-white">
                            <div class="flex items-center gap-2">
                                <svg class="w-5 h-5 text-indigo-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z">
                                    </path>
                                </svg>
                                <h3 class="text-base font-extrabold tracking-tight">Form Düzenleme (Revizyon) Talebi</h3>
                            </div>
                            <button type="button" @click="if(!submitting) open = false"
                                class="text-indigo-200 hover:text-white cursor-pointer" :disabled="submitting">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                            </button>
                        </div>
                        <div class="p-6 space-y-4">
                            <p class="text-xs text-gray-600 leading-relaxed">
                                Onaylanmış formlar doğrudan düzenlenemez. Form verisinde düzeltme yapabilmek için lütfen
                                geçerli bir gerekçe belirtiniz. Talebiniz sorumlu yöneticinize iletilecektir.
                            </p>
                            <div>
                                <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">
                                    Düzenleme Gerekçesi <span class="text-rose-500">*</span>:
                                </label>
                                <textarea name="reason" rows="4" required minlength="5" maxlength="1000"
                                    class="w-full text-sm rounded-xl border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 p-3"
                                    placeholder="Örn: Levha tonajı yanlışlıkla 12 yerine 21 yazılmıştır, düzeltilmesi gerekmektedir..."></textarea>
                            </div>
                        </div>
                        <div class="bg-gray-50 px-6 py-3 flex items-center justify-end gap-3 border-t border-gray-100">
                            <button type="button" @click="if(!submitting) open = false" :disabled="submitting"
                                class="px-4 py-2 text-xs font-bold text-gray-600 hover:text-gray-800 bg-white border border-gray-200 rounded-lg cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed">
                                İptal
                            </button>
                            <button type="submit" :disabled="submitting"
                                @click="if(submitting) { $event.preventDefault(); return false; }"
                                :class="submitting ? 'opacity-70 cursor-wait pointer-events-none' : ''"
                                class="px-5 py-2 text-xs font-bold text-white bg-indigo-600 hover:bg-indigo-700 rounded-lg shadow-sm cursor-pointer inline-flex items-center gap-2 transition">
                                <svg x-show="submitting" class="animate-spin -ml-1 mr-1 h-3.5 w-3.5 text-white"
                                    xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                        stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor"
                                        d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                    </path>
                                </svg>
                                <span x-text="submitting ? 'Talebiniz İletiliyor...' : 'Talebi İlet &rarr;'">Talebi İlet
                                    &rarr;</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
    @endif

        {{-- Onayı Geri Al Modalı --}}
        @if(!empty($canRevert) && $canRevert)
            <div x-data="{ open: false, submitting: false }" @open-revert-modal.window="open = true; submitting = false"
                x-show="open" x-cloak class="fixed inset-0 z-50 overflow-y-auto" style="display: none;">
                <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                    <div class="fixed inset-0 transition-opacity bg-slate-900/60 backdrop-blur-xs"
                        @click="if(!submitting) open = false"></div>
                    <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>
                    <div
                        class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-md sm:w-full border border-gray-100">
                        <form action="{{ route('form-entries.revert-review', $formEntry) }}" method="POST"
                            @submit="if(submitting) { $event.preventDefault(); return false; } submitting = true; document.body.classList.add('cursor-wait');">
                            @csrf
                            <div class="bg-slate-800 px-6 py-4 flex items-center justify-between text-white">
                                <div class="flex items-center gap-2">
                                    <svg class="w-5 h-5 text-amber-400" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z">
                                        </path>
                                    </svg>
                                    <h3 class="text-base font-extrabold tracking-tight">Onay Durumunu Geri Al</h3>
                                </div>
                                <button type="button" @click="if(!submitting) open = false"
                                    class="text-slate-400 hover:text-white cursor-pointer" :disabled="submitting">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M6 18L18 6M6 6l12 12"></path>
                                    </svg>
                                </button>
                            </div>
                            <div class="p-6 space-y-3">
                                <p class="text-sm font-semibold text-gray-800">
                                    Bu formun {{ $formEntry->status === 'approved' ? 'onayını' : 'reddini' }} geri almak
                                    istediğinize emin misiniz?
                                </p>
                                <p class="text-xs text-gray-500 leading-relaxed">
                                    İşlem onaylandığında form statüsü tekrar <strong>"Onay Bekliyor"</strong> aşamasına
                                    çekilecek ve varsa KPI hedeflerine eklenen gerçekleşmeler otomatik olarak geri
                                    alınacaktır.
                                </p>
                            </div>
                            <div class="bg-gray-50 px-6 py-3 flex items-center justify-end gap-3 border-t border-gray-100">
                                <button type="button" @click="if(!submitting) open = false" :disabled="submitting"
                                    class="px-4 py-2 text-xs font-bold text-gray-600 hover:text-gray-800 bg-white border border-gray-200 rounded-lg cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed">
                                    Vazgeç
                                </button>
                                <button type="submit" :disabled="submitting"
                                    @click="if(submitting) { $event.preventDefault(); return false; }"
                                    :class="submitting ? 'opacity-70 cursor-wait pointer-events-none' : ''"
                                    class="px-5 py-2 text-xs font-bold text-white bg-amber-600 hover:bg-amber-700 rounded-lg shadow-sm cursor-pointer inline-flex items-center gap-2 transition">
                                    <svg x-show="submitting" class="animate-spin -ml-1 mr-1 h-3.5 w-3.5 text-white"
                                        xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                            stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor"
                                            d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                        </path>
                                    </svg>
                                    <span x-text="submitting ? 'Geri Alınıyor...' : 'Onayı Geri Al'">Onayı Geri Al</span>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
        @endif

            {{-- Onaya Gönder Modalı --}}
            @if(!empty($canSubmitApproval) && $canSubmitApproval)
                <div x-data="{ open: false, submitting: false }"
                    @open-submit-approval-modal.window="open = true; submitting = false" x-show="open" x-cloak
                    class="fixed inset-0 z-50 overflow-y-auto" style="display: none;">
                    <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                        <div class="fixed inset-0 transition-opacity bg-slate-900/60 backdrop-blur-xs"
                            @click="if(!submitting) open = false"></div>
                        <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>
                        <div
                            class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-md sm:w-full border border-gray-100">
                            <form action="{{ route('form-entries.submit-approval', $formEntry) }}" method="POST"
                                @submit="if(submitting) { $event.preventDefault(); return false; } submitting = true; document.body.classList.add('cursor-wait');">
                                @csrf
                                <div class="bg-emerald-700 px-6 py-4 flex items-center justify-between text-white">
                                    <div class="flex items-center gap-2">
                                        <svg class="w-5 h-5 text-emerald-200" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path>
                                        </svg>
                                        <h3 class="text-base font-extrabold tracking-tight">Formu Onaya Gönder</h3>
                                    </div>
                                    <button type="button" @click="if(!submitting) open = false"
                                        class="text-emerald-200 hover:text-white cursor-pointer" :disabled="submitting">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M6 18L18 6M6 6l12 12"></path>
                                        </svg>
                                    </button>
                                </div>
                                <div class="p-6 space-y-3">
                                    <p class="text-sm font-semibold text-gray-800">
                                        Bu taslak form kaydını onaya göndermek istediğinize emin misiniz?
                                    </p>
                                    <p class="text-xs text-gray-500 leading-relaxed">
                                        Form onaya gönderildiğinde yöneticinizin onayına sunulacak ve durumu <strong>"Onay
                                            Bekliyor"</strong> olarak güncellenecektir.
                                    </p>
                                </div>
                                <div
                                    class="bg-gray-50 px-6 py-3 flex items-center justify-end gap-3 border-t border-gray-100">
                                    <button type="button" @click="if(!submitting) open = false" :disabled="submitting"
                                        class="px-4 py-2 text-xs font-bold text-gray-600 hover:text-gray-800 bg-white border border-gray-200 rounded-lg cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed">
                                        Vazgeç
                                    </button>
                                    <button type="submit" :disabled="submitting"
                                        @click="if(submitting) { $event.preventDefault(); return false; }"
                                        :class="submitting ? 'opacity-70 cursor-wait pointer-events-none' : ''"
                                        class="px-5 py-2 text-xs font-bold text-white bg-emerald-600 hover:bg-emerald-700 rounded-lg shadow-sm cursor-pointer inline-flex items-center gap-2 transition">
                                        <svg x-show="submitting" class="animate-spin -ml-1 mr-1 h-3.5 w-3.5 text-white"
                                            xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                                stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor"
                                                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                            </path>
                                        </svg>
                                        <span x-text="submitting ? 'Gönderiliyor...' : 'Evet, Onaya Gönder'">Evet, Onaya
                                            Gönder</span>
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            @endif

            {{-- Konsolide Parçaları Onaya Gönder Modalı --}}
            @if(!empty($canSubmitChildrenApproval) && $canSubmitChildrenApproval)
                <div x-data="{ open: false, submitting: false }"
                    @open-submit-children-modal.window="open = true; submitting = false" x-show="open" x-cloak
                    class="fixed inset-0 z-50 overflow-y-auto" style="display: none;">
                    <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                        <div class="fixed inset-0 transition-opacity bg-slate-900/60 backdrop-blur-xs"
                            @click="if(!submitting) open = false"></div>
                        <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>
                        <div
                            class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-md sm:w-full border border-gray-100">
                            <form action="{{ route('form-entries.submit-children-approval', $formEntry) }}" method="POST"
                                @submit="if(submitting) { $event.preventDefault(); return false; } submitting = true; document.body.classList.add('cursor-wait');">
                                @csrf
                                <div class="bg-emerald-700 px-6 py-4 flex items-center justify-between text-white">
                                    <div class="flex items-center gap-2">
                                        <svg class="w-5 h-5 text-emerald-200" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path>
                                        </svg>
                                        <h3 class="text-base font-extrabold tracking-tight">Taslak Parçaları Onaya Gönder
                                        </h3>
                                    </div>
                                    <button type="button" @click="if(!submitting) open = false"
                                        class="text-emerald-200 hover:text-white cursor-pointer" :disabled="submitting">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M6 18L18 6M6 6l12 12"></path>
                                        </svg>
                                    </button>
                                </div>
                                <div class="p-6 space-y-3">
                                    <p class="text-sm font-semibold text-gray-800">
                                        Bu konsolide forma bağlı <strong>{{ $draftChildrenCount }} adet taslak
                                            parçayı</strong> onaya göndermek istediğinize emin misiniz?
                                    </p>
                                    <div
                                        class="p-3 bg-slate-50 rounded-xl border border-slate-200 text-xs text-slate-700 space-y-1.5">
                                        <div class="font-bold text-slate-800">Onaya Gönderilecek Parçalar:</div>
                                        <div class="flex flex-wrap gap-1.5">
                                            @foreach($draftChildren as $dc)
                                                <span
                                                    class="px-2 py-0.5 bg-amber-100 text-amber-900 border border-amber-300 font-bold rounded">
                                                    ⚡ {{ $dc->partition_value ?: ('Parça #' . $dc->id) }}
                                                </span>
                                            @endforeach
                                        </div>
                                    </div>
                                    <p class="text-xs text-gray-500 leading-relaxed">
                                        Tüm taslak parçaların durumu <strong>"Onay Bekliyor"</strong> olarak güncellenecek
                                        ve yöneticinizin incelemesine sunulacaktır.
                                    </p>
                                </div>
                                <div
                                    class="bg-gray-50 px-6 py-3 flex items-center justify-end gap-3 border-t border-gray-100">
                                    <button type="button" @click="if(!submitting) open = false" :disabled="submitting"
                                        class="px-4 py-2 text-xs font-bold text-gray-600 hover:text-gray-800 bg-white border border-gray-200 rounded-lg cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed">
                                        Vazgeç
                                    </button>
                                    <button type="submit" :disabled="submitting"
                                        @click="if(submitting) { $event.preventDefault(); return false; }"
                                        :class="submitting ? 'opacity-70 cursor-wait pointer-events-none' : ''"
                                        class="px-5 py-2 text-xs font-bold text-white bg-emerald-600 hover:bg-emerald-700 rounded-lg shadow-sm cursor-pointer inline-flex items-center gap-2 transition">
                                        <svg x-show="submitting" class="animate-spin -ml-1 mr-1 h-3.5 w-3.5 text-white"
                                            xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                                stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor"
                                                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                            </path>
                                        </svg>
                                        <span x-text="submitting ? 'Gönderiliyor...' : 'Evet, Onaya Gönder'">Evet, Onaya
                                            Gönder</span>
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            @endif

            {{-- Geri Çek Modalı --}}
            @if(!empty($canWithdraw) && $canWithdraw)
                <div x-data="{ open: false, submitting: false }"
                    @open-withdraw-modal.window="open = true; submitting = false" x-show="open" x-cloak
                    class="fixed inset-0 z-50 overflow-y-auto" style="display: none;">
                    <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                        <div class="fixed inset-0 transition-opacity bg-slate-900/60 backdrop-blur-xs"
                            @click="if(!submitting) open = false"></div>
                        <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>
                        <div
                            class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-md sm:w-full border border-gray-100">
                            <form action="{{ route('form-entries.withdraw', $formEntry) }}" method="POST"
                                @submit="if(submitting) { $event.preventDefault(); return false; } submitting = true; document.body.classList.add('cursor-wait');">
                                @csrf
                                <div
                                    class="{{ $formEntry->status === 'submitted' ? 'bg-amber-600' : 'bg-rose-700' }} px-6 py-4 flex items-center justify-between text-white">
                                    <div class="flex items-center gap-2">
                                        <svg class="w-5 h-5 text-white/90" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            @if($formEntry->status === 'submitted')
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"></path>
                                            @else
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16">
                                                </path>
                                            @endif
                                        </svg>
                                        <h3 class="text-base font-extrabold tracking-tight">
                                            {{ $formEntry->status === 'submitted' ? 'Formu Onay Sürecinden Geri Çek' : 'Taslak Kaydını Sil' }}
                                        </h3>
                                    </div>
                                    <button type="button" @click="if(!submitting) open = false"
                                        class="text-white/80 hover:text-white cursor-pointer" :disabled="submitting">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M6 18L18 6M6 6l12 12"></path>
                                        </svg>
                                    </button>
                                </div>
                                <div class="p-6 space-y-3">
                                    @if($formEntry->status === 'submitted')
                                        <p class="text-sm font-semibold text-gray-800">
                                            Bu formu onay sürecinden geri çekip tekrar <strong>"Taslak"</strong> durumuna almak
                                            istediğinize emin misiniz?
                                        </p>
                                        <p class="text-xs text-gray-500 leading-relaxed">
                                            Form onay sürecinden geri çekildiğinde tekrar taslak durumuna dönecek, yöneticinin
                                            onay listesinden kaldırılacak ve üzerinde dilediğiniz düzenlemeyi yapabileceksiniz.
                                        </p>
                                    @else
                                        <p class="text-sm font-semibold text-gray-800">
                                            Bu taslak kaydını silmek istediğinize emin misiniz?
                                        </p>
                                        <p class="text-xs text-rose-600 font-medium leading-relaxed">
                                            ⚠️ Bu işlem geri alınamaz. Taslak ve formdaki girilen tüm satırlar sistemden kalıcı
                                            olarak silinecektir.
                                            @if($formEntry->is_consolidated)
                                                <br><span class="text-gray-600 font-normal">Bu konsolide taslak silindiğinde,
                                                    birleştirilen parçalar bağımsız taslak formlar olarak korunur.</span>
                                            @endif
                                        </p>
                                    @endif
                                </div>
                                <div
                                    class="bg-gray-50 px-6 py-3 flex items-center justify-end gap-3 border-t border-gray-100">
                                    <button type="button" @click="if(!submitting) open = false" :disabled="submitting"
                                        class="px-4 py-2 text-xs font-bold text-gray-600 hover:text-gray-800 bg-white border border-gray-200 rounded-lg cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed">
                                        Vazgeç
                                    </button>
                                    <button type="submit" :disabled="submitting"
                                        @click="if(submitting) { $event.preventDefault(); return false; }"
                                        :class="submitting ? 'opacity-70 cursor-wait pointer-events-none' : ''"
                                        class="px-5 py-2 text-xs font-bold text-white {{ $formEntry->status === 'submitted' ? 'bg-amber-600 hover:bg-amber-700' : 'bg-rose-600 hover:bg-rose-700' }} rounded-lg shadow-sm cursor-pointer inline-flex items-center gap-2 transition">
                                        <svg x-show="submitting" class="animate-spin -ml-1 mr-1 h-3.5 w-3.5 text-white"
                                            xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                                stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor"
                                                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                            </path>
                                        </svg>
                                        <span
                                            x-text="submitting ? '{{ $formEntry->status === 'submitted' ? 'İşleniyor...' : 'Siliniyor...' }}' : '{{ $formEntry->status === 'submitted' ? 'Evet, Geri Çek' : 'Evet, Taslağı Sil' }}'">{{ $formEntry->status === 'submitted' ? 'Evet, Geri Çek' : 'Evet, Taslağı Sil' }}</span>
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            @endif

            <!-- AlpineJS Grid Logic for Show -->
            <script>
                document.addEventListener('alpine:init', () => {
                    Alpine.data('formShowApp', (fields, showTotals, versions = [], templateId = null) => ({
                        fields: fields,
                        showTotals: showTotals,
                        versions: versions || [],
                        templateId: templateId || {{ $formEntry->form_template_id ?? $formEntry->template->id }},
                        pinnedColumns: (() => {
                            try {
                                let tid = templateId || {{ $formEntry->form_template_id ?? $formEntry->template->id }};
                                let stored = localStorage.getItem(`pinned_columns_template_${tid}`);
                                if (stored) {
                                    let parsed = JSON.parse(stored);
                                    if (Array.isArray(parsed)) return { left: parsed.map(Number), right: [] };
                                    if (parsed && typeof parsed === 'object') {
                                        return {
                                            left: Array.isArray(parsed.left) ? parsed.left.map(Number) : [],
                                            right: Array.isArray(parsed.right) ? parsed.right.map(Number) : []
                                        };
                                    }
                                }
                            } catch (e) { }
                            return { left: [], right: [] };
                        })(),
                        pinnedLeftOffsets: {},
                        pinnedRightOffsets: {},
                        hiddenColumns: (() => {
                            try {
                                let tid = templateId || {{ $formEntry->form_template_id ?? $formEntry->template->id }};
                                let stored = localStorage.getItem(` hidde n_columns_template_${tid}`);
                                if (stored) return JSON.parse(stored).map(Number);
                            } catch (e) { }
                            return [];
                        })(),
                        sortFieldId: null,
                        sortDirection: null, // 'asc' | 'desc' | null
                        rows: [],
                        totals: {},
                        filteredTotals: {},
                        filters: {},
                        uniqueValues: {},
                        isFullscreen: false,
                        compareMode: false,
                        selectedVersionIds: [],
                        compareMap: {},

                        areValuesEqual(v1, v2) {
                            const s1 = (v1 === null || v1 === undefined) ? '' : String(v1).trim();
                            const s2 = (v2 === null || v2 === undefined) ? '' : String(v2).trim();
                            if (s1 === s2) return true;

                            // Sayısal format toleransı (1000 ile 1.000, 1000.00 gibi farkları eşit kabul et)
                            const clean1 = s1.replace(/\./g, '').replace(',', '.');
                            const clean2 = s2.replace(/\./g, '').replace(',', '.');
                            const n1 = parseFloat(clean1);
                            const n2 = parseFloat(clean2);
                            if (!isNaN(n1) && !isNaN(n2) && clean1.match(/^-?\d+(\.\d+)?$/) && clean2.match(/^-?\d+(\.\d+)?$/)) {
                                return Math.abs(n1 - n2) < 0.00001;
                            }
                            return false;
                        },

                        toggleCompareMode() {
                            this.compareMode = !this.compareMode;
                            if (this.compareMode) {
                                if (this.selectedVersionIds.length === 0 && this.versions.length >= 2) {
                                    // Varsayılan olarak en yeni 2 sürümü seç (örn: v3 ve v2)
                                    this.selectedVersionIds = [this.versions[0].id, this.versions[1].id];
                                } else if (this.selectedVersionIds.length === 0 && this.versions.length === 1) {
                                    this.selectedVersionIds = [this.versions[0].id];
                                }
                                this.buildCompareMap();
                            } else {
                                this.selectedVersionIds = [];
                                this.compareMap = {};
                            }
                        },

                        toggleVersion(vId) {
                            const id = parseInt(vId);
                            const idx = this.selectedVersionIds.indexOf(id);
                            if (idx > -1) {
                                this.selectedVersionIds.splice(idx, 1);
                            } else {
                                this.selectedVersionIds.push(id);
                            }
                            this.buildCompareMap();
                        },

                        selectAllVersions() {
                            this.selectedVersionIds = this.versions.map(v => v.id);
                            this.buildCompareMap();
                        },

                        clearAllVersions() {
                            this.selectedVersionIds = [];
                            this.buildCompareMap();
                        },

                        getSelectedVersionsLabel() {
                            if (!this.selectedVersionIds || this.selectedVersionIds.length === 0) {
                                return 'Sürüm Seçiniz...';
                            }
                            const selected = this.versions
                                .filter(v => this.selectedVersionIds.includes(v.id))
                                .sort((a, b) => a.version_number - b.version_number);

                            if (selected.length <= 3) {
                                return selected.map(v => 'v' + v.version_number).join(', ') + ' (' + selected.length + ' Seçili)';
                            }
                            return selected.slice(0, 2).map(v => 'v' + v.version_number).join(', ') + ' + ' + (selected.length - 2) + ' daha (' + selected.length + ' Seçili)';
                        },

                        getSelectedVersionsSummary() {
                            if (!this.selectedVersionIds || this.selectedVersionIds.length === 0) {
                                return '[Sürüm Seçilmedi]';
                            }
                            const selected = this.versions
                                .filter(v => this.selectedVersionIds.includes(v.id))
                                .sort((a, b) => a.version_number - b.version_number);

                            return '[' + selected.map(v => 'v' + v.version_number).join(' vs ') + ' kıyaslanıyor]';
                        },

                        buildCompareMap() {
                            if (!this.compareMode || !this.selectedVersionIds || this.selectedVersionIds.length === 0) {
                                this.compareMap = {};
                                return;
                            }

                            // Seçilen sürümleri version_number artan sıraya göre al (örn: v1, v2, v3...)
                            const selVersions = this.versions
                                .filter(v => this.selectedVersionIds.includes(v.id))
                                .sort((a, b) => a.version_number - b.version_number);

                            let map = {};

                            this.rows.forEach((row, rIdx) => {
                                map[rIdx] = {
                                    fields: {}
                                };

                                this.fields.forEach(f => {
                                    const currentVal = (row.values && row.values[f.id] !== undefined && row.values[f.id] !== null) ? String(row.values[f.id]).trim() : '';

                                    // Her seçilen versiyondaki değeri topla
                                    const versionEntries = selVersions.map(v => {
                                        const oldRow = (v.rows_data && v.rows_data[rIdx]) ? v.rows_data[rIdx] : null;
                                        const val = (oldRow && oldRow.values && oldRow.values[f.id] !== undefined && oldRow.values[f.id] !== null) ? String(oldRow.values[f.id]).trim() : '';
                                        const hasRow = !!oldRow;
                                        return {
                                            version_id: v.id,
                                            version_number: v.version_number,
                                            time: v.created_at_formatted ? v.created_at_formatted.split(' ')[1] : '',
                                            hasRow: hasRow,
                                            rawVal: val,
                                            formattedVal: hasRow ? this.formatValue(val, f.type) : '-'
                                        };
                                    });

                                    // Değişiklik kontrolü:
                                    let isChanged = false;
                                    if (versionEntries.length === 1) {
                                        isChanged = !this.areValuesEqual(versionEntries[0].rawVal, currentVal);
                                    } else if (versionEntries.length >= 2) {
                                        const firstVal = versionEntries[0].rawVal;
                                        for (let i = 1; i < versionEntries.length; i++) {
                                            if (!this.areValuesEqual(versionEntries[i].rawVal, firstVal)) {
                                                isChanged = true;
                                                break;
                                            }
                                        }
                                    }

                                    // Tooltip için sürüm bazlı tarihçe listesi oluştur
                                    let history = [];
                                    if (versionEntries.length >= 2) {
                                        history = versionEntries.map((ve, idx) => {
                                            const isDiff = idx > 0 ? !this.areValuesEqual(ve.rawVal, versionEntries[idx - 1].rawVal) : false;
                                            return {
                                                version_key: 'v_' + ve.version_id,
                                                label: 'v' + ve.version_number,
                                                time: ve.time,
                                                formattedVal: ve.formattedVal,
                                                isDifferent: isDiff,
                                                isCurrent: false
                                            };
                                        });
                                    } else if (versionEntries.length === 1) {
                                        history = [
                                            {
                                                version_key: 'v_' + versionEntries[0].version_id,
                                                label: 'v' + versionEntries[0].version_number,
                                                time: versionEntries[0].time,
                                                formattedVal: versionEntries[0].formattedVal,
                                                isDifferent: isChanged,
                                                isCurrent: false
                                            },
                                            {
                                                version_key: 'current',
                                                label: 'Güncel',
                                                time: '',
                                                formattedVal: this.formatValue(currentVal, f.type),
                                                isDifferent: isChanged,
                                                isCurrent: true
                                            }
                                        ];
                                    }

                                    map[rIdx].fields[f.id] = {
                                        isChanged: isChanged,
                                        history: history
                                    };
                                });
                            });

                            this.compareMap = map;
                        },

                        isCellChanged(rIdx, fId) {
                            if (!this.compareMode || !this.selectedVersionIds || this.selectedVersionIds.length === 0) return false;
                            return !!(this.compareMap[rIdx] && this.compareMap[rIdx].fields && this.compareMap[rIdx].fields[fId] && this.compareMap[rIdx].fields[fId].isChanged);
                        },

                        getCellVersionHistory(rIdx, fId) {
                            if (!this.compareMode || !this.compareMap[rIdx] || !this.compareMap[rIdx].fields || !this.compareMap[rIdx].fields[fId]) return [];
                            return this.compareMap[rIdx].fields[fId].history || [];
                        },

                        isRowNew(rIdx) {
                            if (!this.compareMode || !this.selectedVersionIds || this.selectedVersionIds.length === 0) return false;
                            const selVersions = this.versions.filter(v => this.selectedVersionIds.includes(v.id));
                            if (selVersions.length === 0) return false;
                            const oldestSel = selVersions.sort((a, b) => a.version_number - b.version_number)[0];
                            return oldestSel && (!oldestSel.rows_data || rIdx >= oldestSel.rows_data.length);
                        },

                        toggleFullscreen() {
                            this.isFullscreen = !this.isFullscreen;
                            if (this.isFullscreen) {
                                document.body.classList.add('overflow-hidden');
                                try {
                                    if (document.documentElement.requestFullscreen && !document.fullscreenElement) {
                                        document.documentElement.requestFullscreen().catch(() => { });
                                    }
                                } catch (e) { }
                            } else {
                                document.body.classList.remove('overflow-hidden');
                                try {
                                    if (document.exitFullscreen && document.fullscreenElement) {
                                        document.exitFullscreen().catch(() => { });
                                    }
                                } catch (e) { }
                            }
                            this.$nextTick(() => {
                                this.updatePinnedOffsets();
                            });
                        },

                        isColumnPinned(fieldId) {
                            return this.isColumnPinnedLeft(fieldId) || this.isColumnPinnedRight(fieldId);
                        },

                        isColumnPinnedLeft(fieldId) {
                            if (!fieldId || !this.pinnedColumns || !Array.isArray(this.pinnedColumns.left)) return false;
                            const numId = Number(fieldId);
                            return this.pinnedColumns.left.some(id => Number(id) === numId);
                        },

                        isColumnPinnedRight(fieldId) {
                            if (!fieldId || !this.pinnedColumns || !Array.isArray(this.pinnedColumns.right)) return false;
                            const numId = Number(fieldId);
                            return this.pinnedColumns.right.some(id => Number(id) === numId);
                        },

                        isLastPinnedLeft(fieldId) {
                            const numId = Number(fieldId);
                            if (!this.isColumnPinnedLeft(numId)) return false;
                            for (let i = this.fields.length - 1; i >= 0; i--) {
                                if (this.isColumnPinnedLeft(this.fields[i].id) && !this.isColumnHidden(this.fields[i].id)) {
                                    return Number(this.fields[i].id) === numId;
                                }
                            }
                            return false;
                        },

                        isFirstPinnedRight(fieldId) {
                            const numId = Number(fieldId);
                            if (!this.isColumnPinnedRight(numId)) return false;
                            for (let i = 0; i < this.fields.length; i++) {
                                if (this.isColumnPinnedRight(this.fields[i].id) && !this.isColumnHidden(this.fields[i].id)) {
                                    return Number(this.fields[i].id) === numId;
                                }
                            }
                            return false;
                        },

                        getPinnedLeftOffset(fieldId) {
                            const idStr = String(fieldId);
                            if (!this.isColumnPinnedLeft(idStr)) return '';
                            return this.pinnedLeftOffsets[idStr] || '48px';
                        },

                        getPinnedRightOffset(fieldId) {
                            const idStr = String(fieldId);
                            if (!this.isColumnPinnedRight(idStr)) return '';
                            return this.pinnedRightOffsets[idStr] || '0px';
                        },

                        getColumnStickyStyle(fieldId) {
                            const idStr = String(fieldId);
                            if (this.isColumnHidden(idStr)) {
                                return { display: 'none' };
                            }
                            if (this.isColumnPinnedLeft(idStr)) {
                                return { left: this.getPinnedLeftOffset(idStr) };
                            }
                            if (this.isColumnPinnedRight(idStr)) {
                                return { right: this.getPinnedRightOffset(idStr) };
                            }
                            return {};
                        },

                        getColumnStickyClass(fieldId, type = 'td') {
                            const isLeft = this.isColumnPinnedLeft(fieldId);
                            const isRight = this.isColumnPinnedRight(fieldId);
                            if (!isLeft && !isRight) return '';

                            const isLastLeft = isLeft && this.isLastPinnedLeft(fieldId);
                            const isFirstRight = isRight && this.isFirstPinnedRight(fieldId);

                            if (type === 'th') {
                                let cls = 'sticky z-30 bg-gray-800 ';
                                if (isLastLeft) {
                                    cls += 'border-r-2 border-indigo-400 shadow-[4px_0_8px_-2px_rgba(0,0,0,0.3)]';
                                } else if (isLeft) {
                                    cls += 'border-r border-gray-700 shadow-[2px_0_5px_-2px_rgba(0,0,0,0.2)]';
                                } else if (isFirstRight) {
                                    cls += 'border-l-2 border-indigo-400 shadow-[-4px_0_8px_-2px_rgba(0,0,0,0.3)]';
                                } else if (isRight) {
                                    cls += 'border-l border-gray-700 shadow-[-2px_0_5px_-2px_rgba(0,0,0,0.2)]';
                                }
                                return cls;
                            }

                            if (type === 'td') {
                                let cls = 'sticky z-10 bg-white group-hover:bg-indigo-50 ';
                                if (isLastLeft) {
                                    cls += 'border-r-2 border-indigo-400 shadow-[4px_0_8px_-2px_rgba(0,0,0,0.08)]';
                                } else if (isLeft) {
                                    cls += 'border-r border-gray-200 shadow-[2px_0_5px_-2px_rgba(0,0,0,0.04)]';
                                } else if (isFirstRight) {
                                    cls += 'border-l-2 border-indigo-400 shadow-[-4px_0_8px_-2px_rgba(0,0,0,0.08)]';
                                } else if (isRight) {
                                    cls += 'border-l border-gray-200 shadow-[-2px_0_5px_-2px_rgba(0,0,0,0.04)]';
                                }
                                return cls;
                            }

                            if (type === 'tfoot-sub') {
                                let cls = 'sticky z-10 bg-indigo-50 ';
                                if (isLastLeft) {
                                    cls += 'border-r-2 border-indigo-400 shadow-[4px_0_8px_-2px_rgba(0,0,0,0.08)]';
                                } else if (isLeft) {
                                    cls += 'border-r border-indigo-100 shadow-[2px_0_5px_-2px_rgba(0,0,0,0.04)]';
                                } else if (isFirstRight) {
                                    cls += 'border-l-2 border-indigo-400 shadow-[-4px_0_8px_-2px_rgba(0,0,0,0.08)]';
                                } else if (isRight) {
                                    cls += 'border-l border-indigo-100 shadow-[-2px_0_5px_-2px_rgba(0,0,0,0.04)]';
                                }
                                return cls;
                            }

                            if (type === 'tfoot-total') {
                                let cls = 'sticky z-10 bg-gray-100 ';
                                if (isLastLeft) {
                                    cls += 'border-r-2 border-indigo-400 shadow-[4px_0_8px_-2px_rgba(0,0,0,0.08)]';
                                } else if (isLeft) {
                                    cls += 'border-r border-gray-200 shadow-[2px_0_5px_-2px_rgba(0,0,0,0.04)]';
                                } else if (isFirstRight) {
                                    cls += 'border-l-2 border-indigo-400 shadow-[-4px_0_8px_-2px_rgba(0,0,0,0.08)]';
                                } else if (isRight) {
                                    cls += 'border-l border-gray-200 shadow-[-2px_0_5px_-2px_rgba(0,0,0,0.04)]';
                                }
                                return cls;
                            }

                            return '';
                        },

                        updatePinnedOffsets() {
                            this.$nextTick(() => {
                                // 1. Sola sabitlenen sütunların ofsetlerini hesapla (soldan sağa)
                                let currentLeft = 48; // '#' sütunu w-12 = 48px
                                let leftOffsets = {};
                                for (let i = 0; i < this.fields.length; i++) {
                                    const f = this.fields[i];
                                    if (this.isColumnPinnedLeft(f.id) && !this.isColumnHidden(f.id)) {
                                        leftOffsets[String(f.id)] = currentLeft + 'px';
                                        const thEl = document.getElementById('show-th-field-' + f.id);
                                        const w = thEl ? thEl.offsetWidth : 160;
                                        currentLeft += w;
                                    }
                                }
                                this.pinnedLeftOffsets = leftOffsets;

                                // 2. Sağa sabitlenen sütunların ofsetlerini hesapla (sağdan sola)
                                let currentRight = 0; // show ekranında sağda sil sütunu yok
                                let rightOffsets = {};
                                for (let i = this.fields.length - 1; i >= 0; i--) {
                                    const f = this.fields[i];
                                    if (this.isColumnPinnedRight(f.id) && !this.isColumnHidden(f.id)) {
                                        rightOffsets[String(f.id)] = currentRight + 'px';
                                        const thEl = document.getElementById('show-th-field-' + f.id);
                                        const w = thEl ? thEl.offsetWidth : 160;
                                        currentRight += w;
                                    }
                                }
                                this.pinnedRightOffsets = rightOffsets;
                            });
                        },

                        setColumnPin(fieldId, side) {
                            const numId = Number(fieldId);
                            if (!this.pinnedColumns || typeof this.pinnedColumns !== 'object') {
                                this.pinnedColumns = { left: [], right: [] };
                            }
                            this.pinnedColumns.left = (this.pinnedColumns.left || []).filter(id => Number(id) !== numId);
                            this.pinnedColumns.right = (this.pinnedColumns.right || []).filter(id => Number(id) !== numId);

                            if (side === 'left') {
                                this.pinnedColumns.left.push(numId);
                            } else if (side === 'right') {
                                this.pinnedColumns.right.push(numId);
                            }

                            this.updatePinnedOffsets();

                            try {
                                localStorage.setItem(`pinned_columns_template_${this.templateId}`, JSON.stringify(this.pinnedColumns));
                            } catch (e) { }
                        },

                        isColumnHidden(fieldId) {
                            if (!fieldId || !this.hiddenColumns) return false;
                            const numId = Number(fieldId);
                            return this.hiddenColumns.some(id => Number(id) === numId);
                        },

                        hideColumn(fieldId) {
                            const numId = Number(fieldId);
                            if (!this.isColumnHidden(numId)) {
                                this.hiddenColumns.push(numId);
                                this.saveHiddenColumns();
                                this.updatePinnedOffsets();
                            }
                        },

                        showColumn(fieldId) {
                            const numId = Number(fieldId);
                            this.hiddenColumns = this.hiddenColumns.filter(id => Number(id) !== numId);
                            this.saveHiddenColumns();
                            this.updatePinnedOffsets();
                        },

                        toggleColumn(fieldId) {
                            if (this.isColumnHidden(fieldId)) {
                                this.showColumn(fieldId);
                            } else {
                                this.hideColumn(fieldId);
                            }
                        },

                        showAllColumns() {
                            this.hiddenColumns = [];
                            this.saveHiddenColumns();
                            this.updatePinnedOffsets();
                        },

                        saveHiddenColumns() {
                            try {
                                localStorage.setItem(`hidden_columns_template_${this.templateId}`, JSON.stringify(this.hiddenColumns));
                            } catch (e) { }
                        },

                        sortColumn(fieldId) {
                            const numId = Number(fieldId);
                            const field = this.fields.find(f => Number(f.id) === numId);
                            if (!field) return;

                            if (this.sortFieldId === numId) {
                                if (this.sortDirection === 'asc') {
                                    this.sortDirection = 'desc';
                                } else if (this.sortDirection === 'desc') {
                                    this.sortDirection = null;
                                    this.sortFieldId = null;
                                }
                            } else {
                                this.sortFieldId = numId;
                                this.sortDirection = 'asc';
                            }

                            this.applySort();
                        },

                        setSortDirection(fieldId, dir) {
                            this.sortFieldId = Number(fieldId);
                            this.sortDirection = dir;
                            this.applySort();
                        },

                        applySort() {
                            if (!this.sortDirection || !this.sortFieldId) {
                                this.rows.sort((a, b) => (a._origIndex ?? 0) - (b._origIndex ?? 0));
                                this.calculateTotals();
                                return;
                            }

                            const field = this.fields.find(f => Number(f.id) === Number(this.sortFieldId));
                            if (!field) return;

                            const dir = this.sortDirection === 'asc' ? 1 : -1;
                            const isNum = field.type === 'number';

                            this.rows.sort((a, b) => {
                                let valA = a.values ? a.values[field.id] : '';
                                let valB = b.values ? b.values[field.id] : '';

                                let emptyA = valA === undefined || valA === null || String(valA).trim() === '' || String(valA).trim() === '-';
                                let emptyB = valB === undefined || valB === null || String(valB).trim() === '' || String(valB).trim() === '-';

                                if (emptyA && emptyB) return 0;
                                if (emptyA) return 1;
                                if (emptyB) return -1;

                                if (isNum) {
                                    let numA = parseFloat(String(valA).replace(/\./g, '').replace(',', '.'));
                                    let numB = parseFloat(String(valB).replace(/\./g, '').replace(',', '.'));
                                    if (isNaN(numA)) numA = 0;
                                    if (isNaN(numB)) numB = 0;
                                    return (numA - numB) * dir;
                                } else {
                                    return String(valA).localeCompare(String(valB), 'tr', { numeric: true, sensitivity: 'base' }) * dir;
                                }
                            });

                            this.calculateTotals();
                        },

                        clearSort() {
                            this.sortFieldId = null;
                            this.sortDirection = null;
                            this.rows.sort((a, b) => (a._origIndex ?? 0) - (b._origIndex ?? 0));
                            this.calculateTotals();
                        },

                        init() {
                            // Blade'den gelen verileri JSON objesine aktarma
                            let existingRows = {!! json_encode($formEntry->rows->map(function ($row) {
    $values = [];
    foreach ($row->values as $val) {
        $values[$val->form_field_id] = $val->value;
    }
    return ['id' => $row->id, 'values' => (object) $values];
})) !!};
                            existingRows.forEach((r, idx) => { r._origIndex = idx; });
                            this.rows = existingRows;

                            this.fields.forEach(f => {
                                this.filters[f.id] = [];
                                this.uniqueValues[f.id] = [];
                            });

                            this.calculateTotals();
                            this.updatePinnedOffsets();
                            window.addEventListener('resize', () => {
                                this.updatePinnedOffsets();
                            });
                        },

                        calcUniqueValues(fieldId) {
                            let values = new Set();
                            this.rows.forEach(r => {
                                let val = r.values[fieldId];
                                if (val === undefined || val === null) val = '';
                                values.add(String(val).trim());
                            });
                            this.uniqueValues[fieldId] = Array.from(values).sort();

                            if (this.filters[fieldId]) {
                                this.filters[fieldId] = this.filters[fieldId].filter(v => this.uniqueValues[fieldId].includes(v));
                            }
                        },

                        selectAllFilters(fieldId) {
                            this.calcUniqueValues(fieldId);
                            this.filters[fieldId] = [...this.uniqueValues[fieldId]];
                            this.calculateTotals();
                        },

                        clearFilter(fieldId) {
                            this.filters[fieldId] = [];
                            this.calculateTotals();
                        },

                        visibleRows() {
                            return this.rows.filter(row => {
                                for (const fieldId in this.filters) {
                                    if (this.filters[fieldId] && this.filters[fieldId].length > 0) {
                                        let val = String(row.values[fieldId] || '').trim();
                                        if (!this.filters[fieldId].includes(val)) {
                                            return false;
                                        }
                                    }
                                }
                                return true;
                            });
                        },

                        calculateTotals() {
                            if (!this.showTotals) return;

                            let newTotals = {};
                            let newFilteredTotals = {};

                            this.fields.forEach(f => {
                                if (f.is_summable && f.type === 'number') {
                                    newTotals[f.id] = 0;
                                    newFilteredTotals[f.id] = 0;
                                }
                            });

                            // Genel Toplam Hesapla (Tümü)
                            this.rows.forEach(r => {
                                this.fields.forEach(f => {
                                    if (f.is_summable && f.type === 'number') {
                                        let val = parseFloat(this.parseRawNumber(r.values[f.id]));
                                        if (!isNaN(val)) {
                                            newTotals[f.id] = (parseFloat(newTotals[f.id] || 0) + val).toFixed(2);
                                        }
                                    }
                                });
                            });

                            // Filtrelenmiş Toplam Hesapla
                            let vRows = this.visibleRows();
                            vRows.forEach(r => {
                                this.fields.forEach(f => {
                                    if (f.is_summable && f.type === 'number') {
                                        let val = parseFloat(this.parseRawNumber(r.values[f.id]));
                                        if (!isNaN(val)) {
                                            newFilteredTotals[f.id] = (parseFloat(newFilteredTotals[f.id] || 0) + val).toFixed(2);
                                        }
                                    }
                                });
                            });

                            Object.keys(newTotals).forEach(k => {
                                newTotals[k] = parseFloat(newTotals[k]).toFixed(2);
                                newFilteredTotals[k] = parseFloat(newFilteredTotals[k]).toFixed(2);
                            });

                            this.totals = newTotals;
                            this.filteredTotals = newFilteredTotals;
                        },

                        parseRawNumber(val) {
                            if (val === undefined || val === null || val === '') return '';
                            let str = String(val).trim();
                            if (str === '' || str === '-') return '';

                            let isNegative = str.startsWith('-');
                            if (isNegative) str = str.substring(1);

                            if (str.includes(',')) {
                                let parts = str.replace(/[^\d,]/g, '').split(',');
                                let intPart = parts[0].replace(/\D/g, '') || '0';
                                let decPart = parts.slice(1).join('').replace(/\D/g, '');
                                return (isNegative ? '-' : '') + intPart + (decPart !== '' ? '.' + decPart : '');
                            }

                            if (str.includes('.')) {
                                let parts = str.split('.');
                                if (parts.length === 2 && parts[1].length <= 2) {
                                    return (isNegative ? '-' : '') + parts[0].replace(/\D/g, '') + '.' + parts[1].replace(/\D/g, '');
                                }
                                return (isNegative ? '-' : '') + str.replace(/\D/g, '');
                            }

                            let clean = str.replace(/\D/g, '');
                            return isNegative ? '-' + clean : clean;
                        },

                        formatValue(val, type) {
                            if (val === undefined || val === null || val === '') return '-';
                            if (type === 'number') {
                                let clean = this.parseRawNumber(val);
                                let fVal = parseFloat(clean);
                                if (!isNaN(fVal)) {
                                    return Number.isInteger(fVal)
                                        ? fVal.toLocaleString('tr-TR')
                                        : fVal.toLocaleString('tr-TR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                                }
                            }
                            return val;
                        },

                        formatTotalDisplay(val) {
                            if (val === undefined || val === null || val === '') return '0,00';
                            let clean = this.parseRawNumber(val);
                            let fVal = parseFloat(clean);
                            if (isNaN(fVal)) return '0,00';
                            return fVal.toLocaleString('tr-TR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                        }
                    }));

                    window.addEventListener('pageshow', () => {
                        document.body.classList.remove('cursor-wait');
                    });
                });
            </script>
</x-app-layout>