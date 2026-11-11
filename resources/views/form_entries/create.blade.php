@section('title', 'Veri Girişi')
<x-app-layout>
    <style>
        .fast-row {
            content-visibility: auto;
            contain-intrinsic-size: 1px 48px;
        }
        .fill-handle-dot {
            width: 8px;
            height: 8px;
            background-color: #4f46e5;
            position: absolute;
            bottom: 3px;
            right: 3px;
            border-radius: 1px;
            cursor: crosshair;
            box-shadow: 0 0 0 1px #fff;
            z-index: 20;
        }
        .fill-handle-dot:hover {
            background-color: #312e81;
            transform: scale(1.35);
        }
        /* Tablo Yatay ve Dikey Scrollbar - Her zaman belirgin ve kolay tutulabilir */
        .table-scroll-container {
            scrollbar-width: auto;
            scrollbar-color: #6366f1 #f1f5f9;
        }
        .table-scroll-container::-webkit-scrollbar {
            height: 14px;
            width: 10px;
        }
        .table-scroll-container::-webkit-scrollbar-track {
            background: #f8fafc;
            border-top: 1px solid #e2e8f0;
        }
        .table-scroll-container::-webkit-scrollbar-thumb {
            background: #818cf8;
            border-radius: 6px;
            border: 2px solid #f8fafc;
        }
        .table-scroll-container::-webkit-scrollbar-thumb:hover {
            background: #4f46e5;
        }

        /* Veri girişi ekranında üst başlık ve padding optimizasyonu: Tablo maksimum dikey alanı kullansın */
        header.bg-white.shadow {
            padding-top: 0.5rem !important;
            padding-bottom: 0.5rem !important;
        }
        header.bg-white.shadow .py-6 {
            padding-top: 0.25rem !important;
            padding-bottom: 0.25rem !important;
        }
    </style>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-1.5">
            <div>
                <h2 class="font-extrabold text-lg sm:text-xl text-gray-900 tracking-tight">
                    {{ $formTemplate->name }} - Veri Girişi
                </h2>
            </div>
            <div class="text-xs text-gray-500 flex flex-wrap items-center gap-2 sm:gap-3">
                <span>Tarih: {{ $date ?? now()->format('Y-m-d') }}</span>
                @if(isset($existingEntry) && $existingEntry->status === 'draft')
                    <span class="text-indigo-600 font-semibold">
                        Son Kayıt: {{ $existingEntry->updated_at->format('d.m.Y H:i') }} (Taslak)
                    </span>
                @endif
            </div>
        </div>
    </x-slot>

    @php
        $fieldsToRender = ($existingEntry && !empty($existingEntry->template_snapshot))
            ? $existingEntry->template_snapshot
            : $formTemplate->fields->toArray();

        // Geriye dönük uyumluluk: Eski taslaklarda yeni eklenen "Koşullu Hesaplama" alanları eksik olabilir.
        if ($existingEntry && !empty($existingEntry->template_snapshot)) {
            $liveFields = $formTemplate->fields->keyBy('id');
            $updatedSnapshot = [];
            foreach ($fieldsToRender as $fieldData) {
                if (isset($fieldData['id']) && isset($liveFields[$fieldData['id']])) {
                    $liveField = $liveFields[$fieldData['id']];

                    // Eksik olan formül ve şart alanlarını canlı şablondan tamamla
                    $keysToCheck = ['is_calculated', 'calculation_formula', 'calc_condition_field_id', 'calc_condition_value', 'filter_by_user_group'];
                    foreach ($keysToCheck as $key) {
                        if (!isset($fieldData[$key])) {
                            $fieldData[$key] = $liveField->$key;
                        }
                    }
                }
                $updatedSnapshot[] = $fieldData;
            }
            $fieldsToRender = $updatedSnapshot;
        }
    @endphp

    <div class="py-1.5 sm:py-2"
        x-data="formEntryApp({{ json_encode($fieldsToRender) }}, {{ $formTemplate->show_row_totals ? 'true' : 'false' }}, {{ json_encode($masterDataTree ?? []) }}, {{ json_encode($currentUserScope ?? ['is_admin' => true, 'is_manager' => true, 'allowed_user_ids' => [], 'allowed_user_names' => []]) }}, {{ json_encode($usersList ?? []) }}, {{ $formTemplate->id }}, {{ json_encode($pinnedColumns ?? ['left' => [], 'right' => []]) }}, {{ $partitionField ? $partitionField->id : 'null' }}, {{ json_encode($partitionValue ?? null) }})">
        <div :class="isWide ? 'w-full px-2 sm:px-4' : 'max-w-full mx-auto px-4 sm:px-6 lg:px-8'" class="transition-all duration-200">

            @if(session('success'))
                <div class="bg-green-50 text-green-800 p-3 rounded-lg mb-2 shadow-xs border border-green-200 flex items-center text-xs sm:text-sm" role="alert">
                    <svg class="w-5 h-5 mr-2 text-green-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <div>
                        <p class="font-semibold">{{ session('success') }}</p>
                    </div>
                </div>
            @endif

            @if(session('error'))
                <div class="bg-red-50 text-red-800 p-3 rounded-lg mb-2 shadow-xs border border-red-200 flex items-center text-xs sm:text-sm" role="alert">
                    <svg class="w-5 h-5 mr-2 text-red-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                    </svg>
                    <div>
                        <p class="font-semibold">{{ session('error') }}</p>
                    </div>
                </div>
            @endif

            @if(isset($errors) && $errors->any())
                <div class="bg-red-50 text-red-800 p-3 rounded-lg mb-2 shadow-xs border border-red-200 text-xs sm:text-sm" role="alert">
                    <div class="flex items-center mb-1 font-semibold text-red-700">
                        <svg class="w-4 h-4 mr-1.5 text-red-600 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                        </svg>
                        Lütfen aşağıdaki hataları kontrol ediniz:
                    </div>
                    <ul class="list-disc list-inside space-y-0.5 text-xs text-red-700 ml-2">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if($existingEntry || !empty($partitionValue))
                @php
                    $statusMap = [
                        'draft' => 'Taslak',
                        'submitted' => 'Onay Bekliyor',
                        'approved' => 'Onaylandı',
                        'rejected' => 'Reddedildi',
                        'revised' => 'Revize Edildi',
                    ];
                    $entryStatusLabel = $existingEntry ? ($statusMap[strtolower($existingEntry->status)] ?? $existingEntry->status) : null;
                @endphp
                <div class="mb-1.5 px-3 py-1 bg-gradient-to-r from-slate-900 via-indigo-950 to-slate-900 text-white rounded-lg shadow-2xs flex flex-wrap items-center justify-between gap-2 text-xs">
                    <div class="flex items-center gap-2 flex-wrap">
                        @if(!empty($partitionValue))
                            <span class="inline-flex items-center gap-1 text-[10px] font-black uppercase tracking-wider bg-amber-400 text-slate-950 px-2 py-0.5 rounded shadow-xs">
                                <span>⚡ PARÇALI GİRİŞ</span>
                            </span>
                            <span class="text-indigo-200 text-xs">{{ $partitionField->label ?? 'Kriter' }}:</span>
                            <strong class="text-xs font-black text-amber-300 bg-amber-400/20 border border-amber-300/40 px-2 py-0.5 rounded">{{ $partitionValue }}</strong>
                        @endif

                        @if($existingEntry)
                            @if(!empty($partitionValue))
                                <span class="text-slate-500 font-bold">&bull;</span>
                            @endif
                            <span class="text-slate-300 text-xs">Düzenleme Modu:</span>
                            <span class="px-2 py-0.5 rounded text-[11px] font-extrabold {{ $existingEntry->status === 'draft' ? 'bg-amber-400/20 text-amber-300 border border-amber-400/40' : 'bg-blue-400/20 text-blue-200 border border-blue-400/40' }}">
                                {{ $entryStatusLabel }}
                            </span>
                        @endif
                    </div>

                    @if(!$existingEntry && $formTemplate->isPartitioned())
                        <a href="{{ route('form-entries.create', $formTemplate) }}" class="text-[11px] font-bold text-indigo-200 hover:text-white underline shrink-0">
                            ← Bölüm Değiştir
                        </a>
                    @endif
                </div>
            @endif

            <form
                id="entry-form"
                action="{{ $existingEntry ? route('form-entries.update', $existingEntry) : route('form-entries.store', $formTemplate) }}"
                method="POST"
                novalidate
                @submit.prevent
                @keydown.window.escape="handleEscapeKey()">
                @csrf
                @if($existingEntry)
                    @method('PUT')
                @endif
                <input type="hidden" name="entry_date" value="{{ $date }}">
                <input type="hidden" name="action" id="form_action_input" value="draft">
                <input type="hidden" name="entry_id" id="form_entry_id_input" :value="entryId || ''">
                <input type="hidden" name="rows_json" id="form_rows_json_input" value="">
                <input type="hidden" name="partition_value" value="{{ $partitionValue ?? ($existingEntry->partition_value ?? '') }}">

                <div x-ref="mainCardContainer"
                     :class="isFullscreen ? 'fixed inset-0 z-[999999] bg-white flex flex-col w-screen h-screen overflow-hidden m-0 p-0' : 'bg-white shadow-xl sm:rounded-lg border border-gray-200 flex flex-col transition-all'"
                     :style="isFullscreen ? 'position: fixed !important; top: 0 !important; left: 0 !important; right: 0 !important; bottom: 0 !important; width: 100vw !important; height: 100vh !important; z-index: 9999999 !important; margin: 0 !important; padding: 0 !important;' : 'height: ' + normalCardHeight + '; max-height: ' + normalCardHeight + '; min-height: 420px;'">
                    
                    <!-- Üst Eylem & Hızlı Satır Çubuğu (Top Action Bar - Konumu Asla Aşağı Kaymaz) -->
                    <div class="px-4 py-2.5 flex flex-wrap items-center justify-between gap-3 flex-shrink-0 transition-colors"
                        :class="isFullscreen ? 'bg-slate-900 text-white border-b border-slate-800 shadow-md' : 'bg-slate-50 border-b border-gray-200 sm:rounded-t-lg'">
                        <div class="flex items-center gap-2.5">
                            <span class="text-xs font-bold" :class="isFullscreen ? 'text-white' : 'text-gray-800'">
                                {{ $formTemplate->name }}
                            </span>
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-extrabold"
                                :class="isFullscreen ? 'bg-indigo-500/30 text-indigo-200 border border-indigo-400/30' : 'bg-indigo-100 text-indigo-800 border border-indigo-200'"
                                x-text="rows.length + ' Satır'"></span>
                            <span x-show="isFullscreen" class="text-xs text-slate-400 hidden sm:inline">&bull; {{ $date ?? now()->format('d.m.Y') }}</span>
                            @if(isset($existingEntry) && $existingEntry->status === 'draft')
                                <span x-show="isFullscreen" class="text-xs text-amber-400 font-semibold hidden md:inline">&bull; Taslak Modu</span>
                            @endif

                            <!-- Otomatik Taslak Kayıt Rozeti -->
                            <div class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-semibold border transition-all"
                                 :class="{
                                     'bg-emerald-500/20 text-emerald-200 border-emerald-400/30': isFullscreen && autoSaveStatus === 'saved',
                                     'bg-emerald-50 text-emerald-700 border-emerald-200': !isFullscreen && autoSaveStatus === 'saved',
                                     'bg-amber-500/20 text-amber-200 border-amber-400/30': isFullscreen && autoSaveStatus === 'saving',
                                     'bg-amber-50 text-amber-700 border-amber-200': !isFullscreen && autoSaveStatus === 'saving',
                                     'bg-rose-500/20 text-rose-200 border-rose-400/30': isFullscreen && autoSaveStatus === 'error',
                                     'bg-rose-50 text-rose-700 border-rose-200': !isFullscreen && autoSaveStatus === 'error',
                                     'bg-slate-800 text-slate-400 border-slate-700': isFullscreen && autoSaveStatus === 'idle' && !hasUnsavedChanges,
                                     'bg-slate-100 text-slate-600 border-slate-200': !isFullscreen && autoSaveStatus === 'idle' && !hasUnsavedChanges,
                                     'bg-blue-500/20 text-blue-200 border-blue-400/30': isFullscreen && autoSaveStatus === 'idle' && hasUnsavedChanges,
                                     'bg-blue-50 text-blue-700 border-blue-200': !isFullscreen && autoSaveStatus === 'idle' && hasUnsavedChanges
                                 }"
                                 :title="autoSaveLastTime ? 'Son kayıt: ' + autoSaveLastTime : 'Otomatik taslak kaydetme koruması aktif'">
                                <template x-if="autoSaveStatus === 'saving'">
                                    <span class="flex items-center gap-1.5">
                                        <svg class="animate-spin w-3 h-3 text-amber-500" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path></svg>
                                        <span class="hidden sm:inline">Taslak Kaydediliyor...</span>
                                    </span>
                                </template>
                                <template x-if="autoSaveStatus === 'saved'">
                                    <span class="flex items-center gap-1.5">
                                        <svg class="w-3.5 h-3.5 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                        <span x-text="'Taslak Kaydedildi (' + autoSaveLastTime + ')'"></span>
                                    </span>
                                </template>
                                <template x-if="autoSaveStatus === 'error'">
                                    <span class="flex items-center gap-1.5 text-rose-500">
                                        <span>⚠️</span>
                                        <span class="hidden sm:inline">Kayıt Başarısız</span>
                                    </span>
                                </template>
                                <template x-if="autoSaveStatus === 'idle'">
                                    <span class="flex items-center gap-1.5">
                                        <span class="w-1.5 h-1.5 rounded-full" :class="hasUnsavedChanges ? 'bg-amber-400 animate-pulse' : 'bg-slate-400'"></span>
                                        <span x-text="hasUnsavedChanges ? 'Kaydedilmemiş Değişiklik' : (autoSaveLastTime ? 'Taslak Kaydedildi (' + autoSaveLastTime + ')' : 'Otomatik Taslak Aktif')"></span>
                                    </span>
                                </template>
                            </div>

                            <!-- İçe Aktarma / Yapıştırma Doğrulama Uyarıları Butonu -->
                            <template x-if="importValidationData && (importValidationData.partitionMismatches.length > 0 || importValidationData.unmatchedDropdowns.length > 0)">
                                <button type="button" @click="showImportValidationModal = true"
                                    class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-bold bg-amber-500/15 hover:bg-amber-500/25 text-amber-700 border border-amber-300 transition shadow-xs cursor-pointer select-none"
                                    :class="isFullscreen ? 'bg-amber-900/60 text-amber-200 border-amber-600' : ''"
                                    title="Yapıştırma veya içe aktarmadaki doğrulama uyarılarını görüntüle">
                                    <svg class="w-3.5 h-3.5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                    </svg>
                                    <span>Aktarım Uyarıları (<span x-text="importValidationData.partitionMismatches.length + importValidationData.unmatchedDropdowns.length"></span>)</span>
                                </button>
                            </template>
                        </div>

                        <div class="flex items-center gap-2">
                            <!-- Seri Satır Ekle (Üst bar - mouse pozisyonu sabit kalır) -->
                            <div class="inline-flex rounded-lg shadow-xs select-none">
                                <button type="button" @mousedown.prevent @click="addRow(1)"
                                    class="bg-indigo-600 hover:bg-indigo-700 active:bg-indigo-800 text-white font-bold py-1.5 px-3 rounded-l-lg text-xs shadow-xs transition flex items-center gap-1 select-none !cursor-pointer"
                                    title="1 Satır Ekle">
                                    <svg class="w-4 h-4 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg>
                                    <span class="pointer-events-none">+ Satır Ekle</span>
                                </button>
                                <button type="button" @mousedown.prevent @click="addRow(5)"
                                    class="bg-indigo-700 hover:bg-indigo-800 active:bg-indigo-900 text-indigo-100 font-bold py-1.5 px-2 border-l border-indigo-500 text-xs transition select-none !cursor-pointer"
                                    title="Hızlıca 5 Satır Ekle">
                                    +5
                                </button>
                                <button type="button" @mousedown.prevent @click="addRow(10)"
                                    class="bg-indigo-700 hover:bg-indigo-800 active:bg-indigo-900 text-indigo-100 font-bold py-1.5 px-2 rounded-r-lg border-l border-indigo-500 text-xs transition select-none !cursor-pointer"
                                    title="Hızlıca 10 Satır Ekle">
                                    +10
                                </button>
                            </div>

                            <!-- Geri Al Butonu (Üst Bar) -->
                            <button type="button" @mousedown.prevent @click="undo()" x-show="history.length > 0"
                                class="py-1.5 px-2.5 rounded-lg text-xs font-bold transition flex items-center gap-1 border select-none !cursor-pointer"
                                :class="isFullscreen ? 'bg-slate-800 hover:bg-slate-700 text-orange-400 border-slate-700' : 'bg-white hover:bg-orange-50 text-orange-600 border-orange-200'"
                                style="display: none;"
                                :title="'Geri Al (Kayıtlı: ' + history.length + ' işlem, Maksimum 50 işlem saklanır)'">
                                <svg class="w-3.5 h-3.5 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"></path></svg>
                                <span class="pointer-events-none" x-text="'Geri Al (' + history.length + ')'"></span>
                            </button>

                            <div class="h-5 border-l mx-1" :class="isFullscreen ? 'border-slate-700' : 'border-gray-300'"></div>

                            <!-- Geniş Ekran Geçiş Butonu -->
                            <button type="button" @click="toggleWide()"
                                class="py-1.5 px-2.5 rounded-lg text-xs font-bold transition flex items-center gap-1.5 shadow-xs border cursor-pointer select-none"
                                :class="isWide ? 'bg-indigo-100 text-indigo-800 border-indigo-300 ring-1 ring-indigo-400/40' : (isFullscreen ? 'bg-slate-800 hover:bg-slate-700 text-slate-300 border-slate-700' : 'bg-white hover:bg-gray-100 text-gray-700 border-gray-300')"
                                :title="isWide ? 'Standart Genişliğe Dön' : 'Geniş Ekran Modu (Tam Genişlik)'">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"></path>
                                </svg>
                                <span class="hidden sm:inline" x-text="isWide ? 'Standart Ekran' : 'Geniş Ekran'"></span>
                            </button>

                            <!-- Tam Ekran Geçiş Butonu -->
                            <button type="button" @click="toggleFullscreen()"
                                class="py-1.5 px-3 rounded-lg text-xs font-bold transition flex items-center gap-1.5 shadow-xs border cursor-pointer select-none"
                                :class="isFullscreen ? 'bg-rose-600 hover:bg-rose-700 text-white border-rose-500 ring-2 ring-rose-400/40' : 'bg-white hover:bg-gray-100 text-gray-700 border-gray-300'"
                                :title="isFullscreen ? 'Tam Ekrandan Çık (ESC tuşuna da basabilirsiniz)' : 'Tam Ekran Veri Giriş Modu'">
                                <template x-if="!isFullscreen">
                                    <span class="flex items-center gap-1.5">
                                        <svg class="w-4 h-4 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4"></path></svg>
                                        <span>Tam Ekran</span>
                                    </span>
                                </template>
                                <template x-if="isFullscreen">
                                    <span class="flex items-center gap-1.5 text-white">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                                        <span>Tam Ekrandan Çık (ESC)</span>
                                    </span>
                                </template>
                            </button>

                            <!-- Tam Ekrandayken Hızlı Kaydet & Gönder Butonları (Üst Bar) -->
                            <div x-show="isFullscreen" class="flex items-center gap-1.5 pl-1.5 border-l border-slate-700">
                                <button type="button" @click="saveDraftAjax(false)" :disabled="isSubmitting || isAutoSaving"
                                    class="bg-slate-800 hover:bg-slate-700 text-slate-200 hover:text-white font-bold py-1.5 px-2.5 rounded-lg text-xs shadow-xs transition flex items-center gap-1 cursor-pointer border border-slate-700"
                                    title="Taslak Olarak Kaydet">
                                    <svg class="w-3.5 h-3.5 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"></path></svg>
                                    <span class="hidden md:inline" x-text="isAutoSaving ? 'Kaydediliyor...' : 'Taslak Kaydet'"></span>
                                </button>
                                <button type="button" @click="submitForm('submit')" :disabled="isSubmitting"
                                    class="bg-emerald-600 hover:bg-emerald-700 active:bg-emerald-800 text-white font-bold py-1.5 px-3 rounded-lg text-xs shadow-xs transition flex items-center gap-1 cursor-pointer"
                                    title="Onaya Gönder">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                    <span>Onaya Gönder</span>
                                </button>
                            </div>
                        </div>
                    </div>

                    <div x-ref="tableContainer"
                        :class="isFullscreen ? 'flex-1 overflow-auto w-full bg-white relative table-scroll-container' : 'flex-1 min-h-0 overflow-auto w-full bg-white relative table-scroll-container'">
                        <table class="min-w-full divide-y divide-gray-200 border-separate border-spacing-0">
                            <thead class="bg-gray-800 sticky top-0 z-20 shadow-md">
                                <tr>
                                    <th
                                        class="w-12 px-2 py-3 text-center text-xs font-medium text-white uppercase tracking-wider bg-gray-800 sticky left-0 z-30 border-r border-gray-700 shadow-[2px_0_5px_-2px_rgba(0,0,0,0.3)]">
                                        #</th>

                                    <template x-for="field in fields" :key="field.id">
                                        <th :id="'th-field-' + field.id"
                                            :style="getColumnStickyStyle(field.id)"
                                            :class="getColumnStickyClass(field.id, 'th')"
                                            class="px-2 sm:px-4 py-3 text-left text-[10px] sm:text-xs font-medium text-white uppercase tracking-wider group relative bg-gray-800 min-w-[160px]">
                                            <div class="flex flex-col space-y-1">
                                                <div class="flex items-center justify-between">
                                                    <span
                                                        x-html="field.label + (field.is_required ? ' <span class=\'text-red-400\'>*</span>' : '')"></span>
                                                    <div class="flex items-center space-x-1">
                                                        <!-- Sütunu Sabitle Menüsü (Sola / Sağa / Kaldır) -->
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
                                                                    ? 'Sola Sabitlendi (Değiştirmek için tıklayın)' 
                                                                    : (isColumnPinnedRight(field.id) 
                                                                        ? 'Sağa Sabitlendi (Değiştirmek için tıklayın)' 
                                                                        : 'Sütunu Sabitle (Sola veya Sağa)')">
                                                                <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                                                          d="M16 12l2-2a2.828 2.828 0 10-4-4l-2 2m-4 4l-4 4m6-6l-4-4m10 6l-6 6M5 19l4-4" />
                                                                </svg>
                                                                <span x-show="isColumnPinnedLeft(field.id)" class="text-[9px] font-bold leading-none">←</span>
                                                                <span x-show="isColumnPinnedRight(field.id)" class="text-[9px] font-bold leading-none">→</span>
                                                            </button>

                                                            <!-- Sabitleme Seçenekleri Menüsü -->
                                                            <div x-show="pinOpen" x-transition.opacity
                                                                @click.stop
                                                                class="absolute z-50 mt-1 right-0 rounded-md shadow-xl bg-gray-900 border border-gray-700 py-1 text-xs text-left"
                                                                style="display: none; min-width: 140px;">
                                                                <button type="button"
                                                                    @click="setColumnPin(field.id, 'left'); pinOpen = false"
                                                                    class="w-full flex items-center px-3 py-1.5 transition-colors cursor-pointer"
                                                                    :class="isColumnPinnedLeft(field.id) ? 'bg-amber-500/20 text-amber-300 font-semibold' : 'text-gray-200 hover:bg-gray-800 hover:text-white'">
                                                                    <span class="mr-2 text-sm font-bold">←</span>
                                                                    <span>Sola Sabitle</span>
                                                                    <span x-show="isColumnPinnedLeft(field.id)" class="ml-auto text-amber-400 text-xs">✓</span>
                                                                </button>
                                                                <button type="button"
                                                                    @click="setColumnPin(field.id, 'right'); pinOpen = false"
                                                                    class="w-full flex items-center px-3 py-1.5 transition-colors cursor-pointer"
                                                                    :class="isColumnPinnedRight(field.id) ? 'bg-emerald-500/20 text-emerald-300 font-semibold' : 'text-gray-200 hover:bg-gray-800 hover:text-white'">
                                                                    <span class="mr-2 text-sm font-bold">→</span>
                                                                    <span>Sağa Sabitle</span>
                                                                    <span x-show="isColumnPinnedRight(field.id)" class="ml-auto text-emerald-400 text-xs">✓</span>
                                                                </button>
                                                                <template x-if="isColumnPinned(field.id)">
                                                                    <div class="border-t border-gray-700/80 my-1"></div>
                                                                </template>
                                                                <template x-if="isColumnPinned(field.id)">
                                                                    <button type="button"
                                                                        @click="setColumnPin(field.id, 'none'); pinOpen = false"
                                                                        class="w-full flex items-center px-3 py-1.5 text-red-300 hover:bg-red-500/20 hover:text-red-200 transition-colors cursor-pointer">
                                                                        <span class="mr-2 text-xs font-bold">✕</span>
                                                                        <span>Sabitlemeyi Kaldır</span>
                                                                    </button>
                                                                </template>
                                                            </div>
                                                        </div>

                                                        <!-- Filter Dropdown Button -->
                                                        <div class="relative" x-data="{ open: false }">
                                                            <button type="button"
                                                                @click.stop="open = !open; if(open) calcUniqueValues(field.id)"
                                                                @click.away="open = false"
                                                                class="flex items-center text-gray-400 hover:text-white transition-colors p-1"
                                                                :class="{ 'text-indigo-300': filters[field.id] && filters[field.id].length > 0 }">
                                                                <svg class="w-3.5 h-3.5" fill="none"
                                                                    stroke="currentColor" viewBox="0 0 24 24">
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
                                                                class="absolute z-50 mt-2 w-48 rounded-md shadow-lg bg-white ring-1 ring-black ring-opacity-5"
                                                                style="display: none; min-width: 200px;">
                                                                <div
                                                                    class="p-2 border-b border-gray-100 flex justify-between gap-2">
                                                                    <button type="button"
                                                                        @click="selectAllFilters(field.id)"
                                                                        class="text-[10px] font-semibold text-indigo-600 hover:text-indigo-800">Tümünü
                                                                        Seç</button>
                                                                    <button type="button" @click="clearFilter(field.id)"
                                                                        class="text-[10px] font-semibold text-red-600 hover:text-red-800">Temizle</button>
                                                                </div>
                                                                <div class="max-h-60 overflow-y-auto p-2">
                                                                    <template
                                                                        x-for="val in uniqueValues[field.id] || []"
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
                                                                        class="text-xs text-gray-400 text-center p-2">
                                                                        Veri yok</div>
                                                                </div>
                                                            </div>
                                                        </div>

                                                        <template x-if="field.type !== 'auto_user' && !field.is_calculated">
                                                            <div class="flex items-center space-x-0.5">
                                                                <!-- İlk Satırdaki Değeri Tüm Sütuna Yay (Aşağı Doldur) -->
                                                                <button type="button" @click.stop="promptFillColumnFromFirstRow(field.id)"
                                                                    class="opacity-0 group-hover:opacity-100 text-gray-400 hover:text-indigo-300 hover:bg-gray-700/70 transition-all p-1 rounded"
                                                                    title="İlk Satırdaki Değeri Tüm Sütuna Yay (Onaylı)">
                                                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 14l-7 7m0 0l-7-7m7 7V3"></path>
                                                                    </svg>
                                                                </button>

                                                                <!-- Bu Sütunu Temizle (Modal Onaylı) -->
                                                                <button type="button" @click.stop="promptClearColumn(field.id)"
                                                                    class="opacity-0 group-hover:opacity-100 text-gray-400 hover:text-red-400 hover:bg-gray-700/70 transition-all p-1 rounded cursor-pointer"
                                                                    title="Bu Sütundaki Verileri Temizle">
                                                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor"
                                                                        viewBox="0 0 24 24">
                                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                                            stroke-width="2"
                                                                            d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16">
                                                                        </path>
                                                                    </svg>
                                                                </button>
                                                            </div>
                                                        </template>
                                                    </div>
                                                </div>
                                            </div>
                                        </th>
                                    </template>

                                    <th
                                        class="w-16 px-2 sm:px-4 py-3 text-center text-xs font-medium text-white uppercase tracking-wider bg-gray-800 sticky right-0 z-30 border-l border-gray-700 shadow-[-2px_0_5px_-2px_rgba(0,0,0,0.3)]">
                                        Sil</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <template x-for="(row, rIndex) in visibleRows()" :key="row.id">
                                    <tr class="hover:bg-indigo-50 transition-colors group fast-row">
                                        <td
                                            class="px-1 py-1 whitespace-nowrap text-center align-middle w-12 border-r border-gray-200 bg-gray-50 sticky left-0 z-10 shadow-[2px_0_5px_-2px_rgba(0,0,0,0.06)] group-hover:bg-indigo-50">
                                            <div class="inline-flex items-center justify-center gap-0.5">
                                                <button type="button" @click="moveRowUp(row)"
                                                    :disabled="rIndex === 0"
                                                    class="text-gray-400 hover:text-indigo-600 disabled:opacity-10 opacity-0 group-hover:opacity-100 transition-opacity p-0.5"
                                                    title="Yukarı Taşı">
                                                    <svg class="w-3.5 h-3.5 mx-auto" fill="none" stroke="currentColor"
                                                        viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2" d="M5 15l7-7 7 7"></path>
                                                    </svg>
                                                </button>

                                                <input type="number" min="1" :max="rows.length" :value="rIndex + 1"
                                                    @change="moveRowTo(row, $event.target.value)"
                                                    class="w-8 h-7 text-center text-xs font-black text-indigo-950 bg-white rounded border border-gray-300 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 p-0 shadow-2xs transition-all [appearance:textfield] [&::-webkit-outer-spin-button]:appearance-none [&::-webkit-inner-spin-button]:appearance-none cursor-pointer hover:bg-gray-50"
                                                    title="Sıra numarası (Rakamı değiştirin)">

                                                <button type="button" @click="moveRowDown(row)"
                                                    :disabled="rIndex === rows.length - 1"
                                                    class="text-gray-400 hover:text-indigo-600 disabled:opacity-10 opacity-0 group-hover:opacity-100 transition-opacity p-0.5"
                                                    title="Aşağı Taşı">
                                                    <svg class="w-3.5 h-3.5 mx-auto" fill="none" stroke="currentColor"
                                                        viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                                    </svg>
                                                </button>
                                            </div>
                                        </td>

                                        @foreach ($fieldsToRender as $colIndex => $fieldItem)
                                            @php
                                                $field = is_array($fieldItem) ? (object) $fieldItem : $fieldItem;
                                                $fId = $field->id;
                                                $fType = $field->type;
                                                $fRequired = !empty($field->is_required);
                                                $fCalculated = !empty($field->is_calculated);
                                            @endphp
                                            <td :style="getColumnStickyStyle('{{ $fId }}')"
                                                :class="getColumnStickyClass('{{ $fId }}', 'td')"
                                                class="px-2 sm:px-4 py-2 whitespace-nowrap min-w-[160px] align-middle relative group/cell"
                                                :title="getCellDisabledReason(fields[{{ $colIndex }}], row)">

                                                @if (isset($partitionField) && $fId == $partitionField->id && !empty($partitionValue))
                                                    <div class="relative w-full flex items-center justify-between gap-1.5 bg-amber-50/90 border border-amber-300 text-amber-900 rounded-md px-3 py-1.5 text-xs font-bold shadow-2xs select-none"
                                                         title="Bu form için {{ $partitionField->label }} değeri '{{ $partitionValue }}' olarak kilitlenmiştir.">
                                                        <div class="flex items-center gap-1.5 truncate">
                                                            <svg class="w-3.5 h-3.5 text-amber-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                                                            <span class="truncate font-mono">{{ $partitionValue }}</span>
                                                        </div>
                                                        <span class="text-[9px] text-amber-700 bg-amber-200/80 px-1.5 py-0.5 rounded font-black tracking-wider uppercase shrink-0">KİLİTLİ</span>
                                                    </div>
                                                @elseif ($fType === 'text')
                                                    <input type="text"
                                                        :id="'cell_' + rIndex + '_{{ $fId }}'"
                                                        :data-field-id="'{{ $fId }}'"
                                                        :data-row-index="rIndex"
                                                        :required="isFieldRequired(fields[{{ $colIndex }}], row)"
                                                        x-model="row.values['{{ $fId }}']"
                                                        @paste="handlePaste($event, rIndex, {{ $colIndex }})"
                                                        @dblclick="promptFillDown(row, '{{ $fId }}')"
                                                        @input="calculateTotals()"
                                                        :title="getCellDisabledReason(fields[{{ $colIndex }}], row)"
                                                        :class="{'border-l-4 border-l-red-500 border-gray-300': isFieldRequired(fields[{{ $colIndex }}], row) && !{{ $fRequired ? 'true' : 'false' }}, 'border-gray-300': !(isFieldRequired(fields[{{ $colIndex }}], row) && !{{ $fRequired ? 'true' : 'false' }})}"
                                                        class="w-full rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                                                @elseif ($fType === 'number')
                                                    <div class="relative w-full" :title="getCellDisabledReason(fields[{{ $colIndex }}], row)">
                                                        <input type="text"
                                                            inputmode="decimal"
                                                            :id="'cell_' + rIndex + '_{{ $fId }}'"
                                                            :data-field-id="'{{ $fId }}'"
                                                            :data-row-index="rIndex"
                                                            :required="isFieldRequired(fields[{{ $colIndex }}], row) && !isFieldCalculated(fields[{{ $colIndex }}], row)"
                                                            :value="formatInputDisplay(row.values['{{ $fId }}'])"
                                                            @input="handleNumberInput($event, rIndex, '{{ $fId }}')"
                                                            @blur="handleNumberBlur($event, rIndex, '{{ $fId }}')"
                                                            @paste="handlePaste($event, rIndex, {{ $colIndex }})"
                                                            @dblclick="if (!isFieldCalculated(fields[{{ $colIndex }}], row)) promptFillDown(row, '{{ $fId }}')"
                                                            :title="getCellDisabledReason(fields[{{ $colIndex }}], row)"
                                                            :readonly="isFieldCalculated(fields[{{ $colIndex }}], row)"
                                                            :class="{'bg-gray-100 cursor-not-allowed text-gray-700 font-semibold': isFieldCalculated(fields[{{ $colIndex }}], row), 'border-l-4 border-l-red-500 border-gray-300': isFieldRequired(fields[{{ $colIndex }}], row) && !{{ $fRequired ? 'true' : 'false' }} && !isFieldCalculated(fields[{{ $colIndex }}], row), 'border-gray-300': !(isFieldRequired(fields[{{ $colIndex }}], row) && !{{ $fRequired ? 'true' : 'false' }} && !isFieldCalculated(fields[{{ $colIndex }}], row))}"
                                                            class="w-full rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm font-medium text-right pr-2">
                                                    </div>
                                                @elseif ($fType === 'textarea')
                                                    <textarea
                                                        :id="'cell_' + rIndex + '_{{ $fId }}'"
                                                        :data-field-id="'{{ $fId }}'"
                                                        :data-row-index="rIndex"
                                                        :required="isFieldRequired(fields[{{ $colIndex }}], row)"
                                                        x-model="row.values['{{ $fId }}']"
                                                        @paste="handlePaste($event, rIndex, {{ $colIndex }})"
                                                        @dblclick="promptFillDown(row, '{{ $fId }}')"
                                                        @input="calculateTotals()"
                                                        :title="getCellDisabledReason(fields[{{ $colIndex }}], row)"
                                                        rows="1"
                                                        :class="{'border-l-4 border-l-red-500 border-gray-300': isFieldRequired(fields[{{ $colIndex }}], row) && !{{ $fRequired ? 'true' : 'false' }}, 'border-gray-300': !(isFieldRequired(fields[{{ $colIndex }}], row) && !{{ $fRequired ? 'true' : 'false' }})}"
                                                        class="w-full rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm resize-y"></textarea>
                                                @elseif ($fType === 'date')
                                                    <input type="date"
                                                        :id="'cell_' + rIndex + '_{{ $fId }}'"
                                                        :data-field-id="'{{ $fId }}'"
                                                        :data-row-index="rIndex"
                                                        :required="isFieldRequired(fields[{{ $colIndex }}], row)"
                                                        x-model="row.values['{{ $fId }}']"
                                                        @paste="handlePaste($event, rIndex, {{ $colIndex }})"
                                                        @dblclick="promptFillDown(row, '{{ $fId }}')"
                                                        @change="calculateTotals()"
                                                        :title="getCellDisabledReason(fields[{{ $colIndex }}], row)"
                                                        :class="{'border-l-4 border-l-red-500 border-gray-300': isFieldRequired(fields[{{ $colIndex }}], row) && !{{ $fRequired ? 'true' : 'false' }}, 'border-gray-300': !(isFieldRequired(fields[{{ $colIndex }}], row) && !{{ $fRequired ? 'true' : 'false' }})}"
                                                        class="w-full rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                                                @elseif ($fType === 'year')
                                                    @php
                                                        $currentYear = (int) date('Y');
                                                        $yearOpts = $field->options ?? [];
                                                        if (is_string($yearOpts)) {
                                                             $yearOpts = json_decode($yearOpts, true) ?? [];
                                                        }
                                                        $rawYears = [];
                                                        if (($yearOpts['year_mode'] ?? '') === 'custom' && !empty($yearOpts['year_custom_list'])) {
                                                            $rawYears = array_filter(array_map('intval', explode(',', $yearOpts['year_custom_list'])));
                                                        } else {
                                                            $past = isset($yearOpts['year_past']) ? (int)$yearOpts['year_past'] : 1;
                                                            $future = isset($yearOpts['year_future']) ? (int)$yearOpts['year_future'] : 2;
                                                            for ($y = $currentYear - $past; $y <= $currentYear + $future; $y++) {
                                                                $rawYears[] = $y;
                                                            }
                                                        }
                                                        $rawYears = array_values(array_unique($rawYears));
                                                        sort($rawYears);
                                                    @endphp
                                                    <div class="relative w-full" :title="getCellDisabledReason(fields[{{ $colIndex }}], row)">
                                                        <select
                                                            :id="'cell_' + rIndex + '_{{ $fId }}'"
                                                            :data-field-id="'{{ $fId }}'"
                                                            :data-row-index="rIndex"
                                                            :required="isFieldRequired(fields[{{ $colIndex }}], row)"
                                                            x-model="row.values['{{ $fId }}']"
                                                            @paste="handlePaste($event, rIndex, {{ $colIndex }})"
                                                            @keydown.delete.prevent="clearSelect(row, '{{ $fId }}', fields[{{ $colIndex }}])"
                                                            @keydown.backspace.prevent="clearSelect(row, '{{ $fId }}', fields[{{ $colIndex }}])"
                                                            @change="calculateTotals()"
                                                            :title="getCellDisabledReason(fields[{{ $colIndex }}], row)"
                                                            class="w-full rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm transition-colors pr-7"
                                                            :class="{ 'bg-indigo-50 border-indigo-300 text-indigo-700 font-bold': row.values['{{ $fId }}'], 'bg-white border-gray-300 text-gray-700': !row.values['{{ $fId }}'], 'border-l-4 border-l-red-500': isFieldRequired(fields[{{ $colIndex }}], row) && !{{ $fRequired ? 'true' : 'false' }} }">
                                                            <option value="">Yıl Seçiniz...</option>
                                                            @foreach ($rawYears as $y)
                                                                @php
                                                                    $lbl = (string)$y;
                                                                    if ($y === $currentYear + 1) $lbl .= ' (Hedef Yılı)';
                                                                    elseif ($y === $currentYear) $lbl .= ' (Bu Yıl)';
                                                                @endphp
                                                                <option value="{{ $y }}">{{ $lbl }}</option>
                                                            @endforeach
                                                        </select>
                                                        <button type="button"
                                                            x-show="row.values['{{ $fId }}']"
                                                            @click.stop.prevent="clearSelect(row, '{{ $fId }}', fields[{{ $colIndex }}])"
                                                            tabindex="-1"
                                                            title="Seçimi Temizle (Delete veya Backspace ile de silebilirsiniz)"
                                                            class="absolute right-6 top-1/2 -translate-y-1/2 w-4 h-4 rounded-full bg-gray-200/90 hover:bg-rose-500 hover:text-white text-gray-500 flex items-center justify-center text-[10px] font-black transition z-10 cursor-pointer shadow-2xs">
                                                            &times;
                                                        </button>
                                                    </div>
                                                @elseif ($fType === 'select')
                                                    @php
                                                        $selectOpts = $field->options ?? [];
                                                        if (is_string($selectOpts)) {
                                                            $selectOpts = json_decode($selectOpts, true) ?? [];
                                                        }
                                                    @endphp
                                                    <div class="relative w-full" :title="getCellDisabledReason(fields[{{ $colIndex }}], row)">
                                                        <select
                                                            :id="'cell_' + rIndex + '_{{ $fId }}'"
                                                            :data-field-id="'{{ $fId }}'"
                                                            :data-row-index="rIndex"
                                                            :required="isFieldRequired(fields[{{ $colIndex }}], row)"
                                                            x-model="row.values['{{ $fId }}']"
                                                            @paste="handlePaste($event, rIndex, {{ $colIndex }})"
                                                            @keydown.ctrl.v.prevent="handleSelectPaste($event, rIndex, {{ $colIndex }})"
                                                            @keydown.delete.prevent="clearSelect(row, '{{ $fId }}', fields[{{ $colIndex }}])"
                                                            @keydown.backspace.prevent="clearSelect(row, '{{ $fId }}', fields[{{ $colIndex }}])"
                                                            @change="calculateTotals()"
                                                            :title="getCellDisabledReason(fields[{{ $colIndex }}], row)"
                                                            class="w-full rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm transition-colors pr-7"
                                                            :class="{ 'bg-indigo-50 border-indigo-300 text-indigo-700 font-bold': row.values['{{ $fId }}'], 'bg-white border-gray-300 text-gray-700': !row.values['{{ $fId }}'], 'border-l-4 border-l-red-500': isFieldRequired(fields[{{ $colIndex }}], row) && !{{ $fRequired ? 'true' : 'false' }} }">
                                                            <option value="">Seçiniz...</option>
                                                            @if (is_array($selectOpts))
                                                                @foreach ($selectOpts as $k => $opt)
                                                                    <option value="{{ is_numeric($k) ? $opt : $k }}">{{ $opt }}</option>
                                                                @endforeach
                                                            @endif
                                                        </select>
                                                        <button type="button"
                                                            x-show="row.values['{{ $fId }}']"
                                                            @click.stop.prevent="clearSelect(row, '{{ $fId }}', fields[{{ $colIndex }}])"
                                                            tabindex="-1"
                                                            title="Seçimi Temizle (Delete veya Backspace ile de silebilirsiniz)"
                                                            class="absolute right-6 top-1/2 -translate-y-1/2 w-4 h-4 rounded-full bg-gray-200/90 hover:bg-rose-500 hover:text-white text-gray-500 flex items-center justify-center text-[10px] font-black transition z-10 cursor-pointer shadow-2xs">
                                                            &times;
                                                        </button>
                                                    </div>
                                                @elseif ($fType === 'master_data')
                                                    <div class="relative w-full" :title="getCellDisabledReason(fields[{{ $colIndex }}], row)">
                                                        <select
                                                            :id="'cell_' + rIndex + '_{{ $fId }}'"
                                                            :data-field-id="'{{ $fId }}'"
                                                            :data-row-index="rIndex"
                                                            :required="isFieldRequired(fields[{{ $colIndex }}], row)"
                                                            x-model="row.values['{{ $fId }}']"
                                                            @paste="handlePaste($event, rIndex, {{ $colIndex }})"
                                                            @keydown.delete.prevent="clearSelect(row, '{{ $fId }}', fields[{{ $colIndex }}])"
                                                            @keydown.backspace.prevent="clearSelect(row, '{{ $fId }}', fields[{{ $colIndex }}])"
                                                            @change="handleMasterDataChange(row, '{{ $fId }}'); calculateTotals()"
                                                            :title="getCellDisabledReason(fields[{{ $colIndex }}], row)"
                                                            :disabled="isMasterDataDisabled(row, fields[{{ $colIndex }}])"
                                                            class="w-full rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm transition-colors pr-7"
                                                            :class="{ 
                                                                'bg-gray-100 text-gray-400 border-gray-200 cursor-not-allowed': isMasterDataDisabled(row, fields[{{ $colIndex }}]),
                                                                'bg-indigo-50 border-indigo-300 text-indigo-700 font-bold': !isMasterDataDisabled(row, fields[{{ $colIndex }}]) && row.values['{{ $fId }}'], 
                                                                'bg-white border-gray-300 text-gray-700': !isMasterDataDisabled(row, fields[{{ $colIndex }}]) && !row.values['{{ $fId }}'], 
                                                                'border-l-4 border-l-red-500': isFieldRequired(fields[{{ $colIndex }}], row) && !{{ $fRequired ? 'true' : 'false' }} 
                                                            }">
                                                            <option value="" x-text="getMasterDataPlaceholder(row, fields[{{ $colIndex }}])"></option>
                                                            <template x-for="opt in getMasterDataOptions(row, fields[{{ $colIndex }}])"
                                                                :key="opt.id">
                                                                <option :value="opt.name" x-text="opt.name"
                                                                    :selected="row.values['{{ $fId }}'] == opt.name"></option>
                                                            </template>
                                                        </select>
                                                        <button type="button"
                                                            x-show="row.values['{{ $fId }}'] && !isMasterDataDisabled(row, fields[{{ $colIndex }}])"
                                                            @click.stop.prevent="clearSelect(row, '{{ $fId }}', fields[{{ $colIndex }}])"
                                                            tabindex="-1"
                                                            title="Seçimi Temizle (Delete veya Backspace ile de silebilirsiniz)"
                                                            class="absolute right-6 top-1/2 -translate-y-1/2 w-4 h-4 rounded-full bg-gray-200/90 hover:bg-rose-500 hover:text-white text-gray-500 flex items-center justify-center text-[10px] font-black transition z-10 cursor-pointer shadow-2xs">
                                                            &times;
                                                        </button>
                                                    </div>
                                                @elseif ($fType === 'auto_user')
                                                    <div :title="getCellDisabledReason(fields[{{ $colIndex }}], row)">
                                                        <input type="text" value="{{ Auth::user()->name }}" readonly
                                                            :id="'cell_' + rIndex + '_{{ $fId }}'"
                                                            :data-field-id="'{{ $fId }}'"
                                                            :data-row-index="rIndex"
                                                            :title="getCellDisabledReason(fields[{{ $colIndex }}], row)"
                                                            class="w-full rounded-md border-gray-200 bg-gray-100 text-gray-500 shadow-sm text-sm cursor-not-allowed">
                                                    </div>
                                                @elseif ($fType === 'user_list')
                                                    <div class="relative w-full" :title="getCellDisabledReason(fields[{{ $colIndex }}], row)">
                                                        <select
                                                            :id="'cell_' + rIndex + '_{{ $fId }}'"
                                                            :data-field-id="'{{ $fId }}'"
                                                            :data-row-index="rIndex"
                                                            :required="isFieldRequired(fields[{{ $colIndex }}], row)"
                                                            x-model="row.values['{{ $fId }}']"
                                                            @paste="handlePaste($event, rIndex, {{ $colIndex }})"
                                                            @keydown.delete.prevent="clearSelect(row, '{{ $fId }}', fields[{{ $colIndex }}])"
                                                            @keydown.backspace.prevent="clearSelect(row, '{{ $fId }}', fields[{{ $colIndex }}])"
                                                            @change="calculateTotals()"
                                                            :title="getCellDisabledReason(fields[{{ $colIndex }}], row)"
                                                            class="w-full rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm transition-colors pr-7"
                                                            :class="{ 'bg-indigo-50 border-indigo-300 text-indigo-700 font-bold': row.values['{{ $fId }}'], 'bg-white border-gray-300 text-gray-700': !row.values['{{ $fId }}'], 'border-l-4 border-l-red-500': isFieldRequired(fields[{{ $colIndex }}], row) && !{{ $fRequired ? 'true' : 'false' }} }">
                                                            <option value="">Kullanıcı Seçiniz...</option>
                                                            <template x-for="u in getUserListOptions(row, fields[{{ $colIndex }}])" :key="u.id">
                                                                <option :value="u.name" x-text="u.name" :selected="row.values['{{ $fId }}'] == u.name"></option>
                                                            </template>
                                                        </select>
                                                        <button type="button"
                                                            x-show="row.values['{{ $fId }}']"
                                                            @click.stop.prevent="clearSelect(row, '{{ $fId }}', fields[{{ $colIndex }}])"
                                                            tabindex="-1"
                                                            title="Seçimi Temizle (Delete veya Backspace ile de silebilirsiniz)"
                                                            class="absolute right-6 top-1/2 -translate-y-1/2 w-4 h-4 rounded-full bg-gray-200/90 hover:bg-rose-500 hover:text-white text-gray-500 flex items-center justify-center text-[10px] font-black transition z-10 cursor-pointer shadow-2xs">
                                                            &times;
                                                        </button>
                                                    </div>
                                                @endif

                                                <!-- Excel Doldurma Noktası (Fill Handle) -->
                                                @if ($fType !== 'auto_user' && !$fCalculated)
                                                    <div @dblclick.stop="promptFillDown(row, '{{ $fId }}')"
                                                         class="fill-handle-dot opacity-0 group-hover/cell:opacity-100 transition-opacity"
                                                         title="Aşağı doğru doldurmak için çift tıklayın (Onaylı)"></div>
                                                @endif

                                            </td>
                                        @endforeach

                                        <td class="w-12 px-2 py-1 whitespace-nowrap text-center sticky right-0 z-10 bg-white group-hover:bg-indigo-50 border-l border-gray-200 shadow-[-2px_0_5px_-2px_rgba(0,0,0,0.06)]">
                                            <button type="button" @click.stop="promptDeleteRow(row)"
                                                class="text-red-500 hover:text-red-700 bg-red-50 hover:bg-red-100 p-1.5 rounded-lg transition cursor-pointer"
                                                title="Satırı Sil (Doluysa Onay İster)" x-show="rows.length > 1">
                                                <svg class="w-3.5 h-3.5 pointer-events-none" fill="none" stroke="currentColor"
                                                    viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2"
                                                        d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16">
                                                    </path>
                                                </svg>
                                            </button>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>

                            <template x-if="showTotals">
                                <tfoot class="bg-gray-100 font-bold">
                                    <tr class="bg-indigo-50/50">
                                        <td class="sticky left-0 z-10 bg-indigo-50 px-4 py-3 font-bold text-right text-indigo-800 text-[11px] uppercase border-b border-indigo-100 border-r border-indigo-200 shadow-[2px_0_5px_-2px_rgba(0,0,0,0.06)]"
                                            colspan="1">Görünen Toplam:</td>
                                        @foreach ($fieldsToRender as $fieldItem)
                                            @php
                                                $f = is_array($fieldItem) ? (object) $fieldItem : $fieldItem;
                                                $isSummable = !empty($f->is_summable) && $f->type === 'number';
                                            @endphp
                                            <td :style="getColumnStickyStyle('{{ $f->id }}')"
                                                :class="getColumnStickyClass('{{ $f->id }}', 'tfoot-sub')"
                                                class="px-4 py-3 font-bold text-indigo-700 text-right whitespace-nowrap border-b border-indigo-100">
                                                @if ($isSummable)
                                                    <span x-text="formatTotalDisplay(filteredTotals['{{ $f->id }}'])"></span>
                                                @endif
                                            </td>
                                        @endforeach
                                        <td class="w-16 sticky right-0 z-10 bg-indigo-50 border-b border-indigo-100 border-l border-indigo-200 shadow-[-2px_0_5px_-2px_rgba(0,0,0,0.06)]"></td>
                                    </tr>
                                    <tr>
                                        <td class="sticky left-0 z-10 bg-gray-100 px-4 py-3 font-bold text-right text-gray-500 text-[10px] uppercase border-r border-gray-200 shadow-[2px_0_5px_-2px_rgba(0,0,0,0.06)]"
                                            colspan="1">Genel Toplam (Tümü):</td>
                                        @foreach ($fieldsToRender as $fieldItem)
                                            @php
                                                $f = is_array($fieldItem) ? (object) $fieldItem : $fieldItem;
                                                $isSummable = !empty($f->is_summable) && $f->type === 'number';
                                            @endphp
                                            <td :style="getColumnStickyStyle('{{ $f->id }}')"
                                                :class="getColumnStickyClass('{{ $f->id }}', 'tfoot-total')"
                                                class="px-4 py-3 font-bold text-gray-500 text-right whitespace-nowrap">
                                                @if ($isSummable)
                                                    <span x-text="formatTotalDisplay(totals['{{ $f->id }}'])"></span>
                                                @endif
                                            </td>
                                        @endforeach
                                        <td class="w-16 sticky right-0 z-10 bg-gray-100 border-l border-gray-200 shadow-[-2px_0_5px_-2px_rgba(0,0,0,0.06)]"></td>
                                    </tr>
                                </tfoot>
                            </template>
                        </table>
                    </div>

                    <!-- Bottom Toolbar (En altta her zaman sabit & hazır) -->
                    <div
                        class="px-3 sm:px-4 py-1.5 sm:py-2 bg-gray-50/95 backdrop-blur-xs border-t border-gray-200 flex flex-row justify-between items-center gap-2 flex-shrink-0 sticky bottom-0 z-30 shadow-[0_-4px_16px_rgba(0,0,0,0.06)]"
                        :class="isFullscreen ? 'shadow-lg bg-slate-100 border-slate-300' : 'sm:rounded-b-lg'">
                        <div class="flex items-center gap-1.5 sm:gap-2 flex-wrap lg:flex-nowrap">
                            <!-- Seri Satır Ekle (Alt Bar) -->
                            <div class="inline-flex rounded-lg shadow-xs select-none">
                                <button type="button" @mousedown.prevent @click="addRow(1)"
                                    class="bg-indigo-600 hover:bg-indigo-700 active:bg-indigo-800 text-white font-semibold py-1.5 px-3 rounded-l-lg shadow-xs transition flex items-center gap-1 select-none text-xs !cursor-pointer">
                                    <svg class="w-4 h-4 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                    </svg>
                                    <span class="pointer-events-none">Satır Ekle</span>
                                </button>
                                <button type="button" @mousedown.prevent @click="addRow(5)"
                                    class="bg-indigo-700 hover:bg-indigo-800 active:bg-indigo-900 text-indigo-100 font-bold py-1.5 px-2 border-l border-indigo-500 text-xs transition select-none !cursor-pointer"
                                    title="5 Satır Ekle">
                                    +5
                                </button>
                                <button type="button" @mousedown.prevent @click="addRow(10)"
                                    class="bg-indigo-700 hover:bg-indigo-800 active:bg-indigo-900 text-indigo-100 font-bold py-1.5 px-2 rounded-r-lg border-l border-indigo-500 text-xs transition select-none !cursor-pointer"
                                    title="10 Satır Ekle">
                                    +10
                                </button>
                            </div>

                            <button type="button" @click.stop="promptClearAll()"
                                class="bg-white hover:bg-red-50 text-red-600 font-semibold py-1.5 px-3 border border-red-200 rounded-lg shadow-xs transition flex items-center text-xs cursor-pointer select-none">
                                Tümünü Temizle
                            </button>

                            <button type="button" @mousedown.prevent @click="undo()" x-show="history.length > 0"
                                class="bg-white hover:bg-orange-50 text-orange-600 font-semibold py-1.5 px-2.5 border border-orange-200 rounded-lg shadow-xs transition flex items-center gap-1 text-xs select-none !cursor-pointer"
                                style="display: none;"
                                :title="'Geri Al (Kayıtlı: ' + history.length + ' işlem, Maksimum 50 işlem saklanır)'">
                                <svg class="w-4 h-4 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"></path>
                                </svg>
                                <span class="pointer-events-none" x-text="'Geri Al (' + history.length + ')'"></span>
                            </button>

                            <div class="h-5 border-l border-gray-300 mx-0.5 hidden xl:block"></div>

                            <a href="{{ route('form-templates.download-empty', $formTemplate) }}"
                                class="bg-white hover:bg-green-50 text-green-700 font-semibold py-1.5 px-3 border border-green-200 rounded-lg shadow-xs transition flex items-center gap-1 text-xs">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                                </svg>
                                Şablon İndir
                            </a>

                            <button type="button" @click="$refs.excelInput.click()" :disabled="isUploading"
                                class="bg-white hover:bg-indigo-50 text-indigo-700 font-semibold py-1.5 px-3 border border-indigo-200 rounded-lg shadow-xs transition flex items-center gap-1 text-xs cursor-pointer">
                                <svg class="w-4 h-4 group-hover:scale-110 transition-transform" fill="none"
                                    stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path>
                                </svg>
                                Excel'den Yükle
                            </button>
                            <input type="file" x-ref="excelInput" @change="handleExcelUpload" accept=".xlsx,.xls"
                                class="hidden">

                            <!-- Tam Ekran Butonu (Alt Bar) -->
                            <button type="button" @click="toggleFullscreen()"
                                class="bg-white hover:bg-gray-100 text-gray-700 font-semibold py-1.5 px-3 border border-gray-300 rounded-lg shadow-xs transition flex items-center gap-1.5 cursor-pointer text-xs"
                                :title="isFullscreen ? 'Tam Ekrandan Çık (ESC)' : 'Tam Ekran Modu (ESC)'">
                                <template x-if="!isFullscreen">
                                    <span class="flex items-center gap-1.5 text-xs font-bold">
                                        <svg class="w-4 h-4 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4"></path></svg>
                                        Tam Ekran
                                    </span>
                                </template>
                                <template x-if="isFullscreen">
                                    <span class="flex items-center gap-1.5 text-xs text-rose-600 font-bold">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                                        Tam Ekrandan Çık (ESC)
                                    </span>
                                </template>
                            </button>
                        </div>

                        <div class="flex items-center space-x-2 flex-shrink-0">
                            <button type="button"
                                @click="saveDraftAjax(false)"
                                :disabled="isSubmitting || isAutoSaving"
                                :class="{'opacity-50 cursor-not-allowed': isSubmitting || isAutoSaving}"
                                class="bg-white hover:bg-gray-100 active:bg-gray-200 text-gray-700 font-semibold py-1.5 px-3.5 border border-gray-300 rounded-lg shadow-xs transition-colors cursor-pointer select-none inline-flex items-center justify-center text-xs"
                                title="Formu zorunlu alan kontrolü olmadan taslak olarak kaydeder (Daha sonra devam edebilirsiniz)">
                                <span x-show="isAutoSaving || (isSubmitting && submittingAction === 'draft')" class="inline-flex items-center">
                                    <svg class="animate-spin -ml-1 mr-1.5 h-3.5 w-3.5 text-gray-700" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    <span>Taslak Kaydediliyor...</span>
                                </span>
                                <span x-show="!isAutoSaving && (!isSubmitting || submittingAction !== 'draft')">Taslak Olarak Kaydet</span>
                            </button>
                            <button type="button"
                                @click="submitForm('submit')"
                                :disabled="isSubmitting"
                                :class="{'opacity-50 cursor-not-allowed': isSubmitting}"
                                class="bg-green-600 hover:bg-green-700 active:bg-green-800 text-white font-bold py-1.5 px-4 rounded-lg shadow-xs transition-colors cursor-pointer select-none inline-flex items-center justify-center text-xs"
                                title="Formu doğrular ve onaya gönderir">
                                <span x-show="isSubmitting && submittingAction === 'submit'" class="inline-flex items-center">
                                    <svg class="animate-spin -ml-1 mr-1.5 h-3.5 w-3.5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    <span>Kaydediliyor...</span>
                                </span>
                                <span x-show="!isSubmitting || submittingAction !== 'submit'">
                                    @if($existingEntry && $existingEntry->status === 'approved')
                                        Onaylı Formu Güncelle
                                    @else
                                        Onaya Gönder
                                    @endif
                                </span>
                            </button>
                        </div>
                    </div>

            <!-- Excel Yükleme İlerleme Modalı (Upload Progress Modal) -->
            <div x-show="showUploadModal"
                 x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 x-transition:leave="transition ease-in duration-200"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0"
                 class="fixed inset-0 z-[99999999] flex items-center justify-center p-4 bg-slate-900/70 backdrop-blur-sm"
                 style="display: none; z-index: 99999999 !important;">
                
                <div class="bg-white rounded-2xl shadow-2xl border border-gray-100 p-6 sm:p-8 w-full max-w-md transform transition-all text-center">
                    <!-- İkon / Animasyon -->
                    <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-2xl bg-indigo-50 text-indigo-600 mb-4 ring-8 ring-indigo-50/50">
                        <template x-if="uploadStep < 4">
                            <svg class="w-8 h-8 animate-spin text-indigo-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                            </svg>
                        </template>
                        <template x-if="uploadStep === 4">
                            <svg class="w-8 h-8 text-emerald-600 animate-bounce" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"></path>
                            </svg>
                        </template>
                    </div>

                    <h3 class="text-lg font-bold text-gray-900 mb-1" x-text="uploadModalTitle || 'Excel Dosyası İçe Aktarılıyor'">
                    </h3>
                    <p class="text-xs text-gray-500 mb-4" x-text="uploadModalSubtitle || 'Büyük veri setleri optimize edilerek tabloya aktarılmaktadır.'">
                    </p>

                    <!-- Büyük Veri Seti Bilgilendirme Kartı -->
                    <div x-show="isLargeDataset" 
                         x-transition:enter="transition ease-out duration-200"
                         x-transition:enter-start="opacity-0 -translate-y-2"
                         x-transition:enter-end="opacity-100 translate-y-0"
                         class="mb-4 p-3.5 bg-amber-50/95 border border-amber-300 rounded-xl text-left shadow-sm">
                        <div class="flex items-start gap-2.5">
                            <span class="text-xl shrink-0 mt-0.5">⚠️</span>
                            <div class="text-[12px] text-amber-950 leading-relaxed flex-1">
                                <p class="font-bold text-amber-900">
                                    Yüksek Hacimli Veri Seti (<span x-text="totalImportRows"></span> Satır)
                                </p>
                                <p class="mt-0.5 text-amber-800 text-[11px]">
                                    Tarayıcınızın donmaması ve bellek güvenliği için veriler parçalı olarak optimize edilerek aktarılmaktadır.
                                </p>
                                <div class="mt-2 pt-1.5 border-t border-amber-200/80 flex flex-wrap items-center justify-between text-[11px] font-semibold text-amber-900 gap-2">
                                    <div class="flex items-center gap-2">
                                        <span>⏱️ Geçen Süre: <strong class="text-indigo-800 font-bold" x-text="liveTimerText"></strong></span>
                                        <span>•</span>
                                        <span>Tahmini Kalan: <strong class="text-emerald-700 font-bold" x-text="estimatedRemainingText"></strong></span>
                                    </div>
                                    <span class="text-red-600 font-medium animate-pulse">Sayfayı kapatmayın</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- İlerleme Çubuğu ve Yüzde -->
                    <div class="space-y-2 mb-4">
                        <div class="flex justify-between items-center text-xs font-semibold">
                            <span class="text-gray-600" x-text="uploadStatusText"></span>
                            <span class="text-indigo-600 font-bold" x-text="uploadProgress + '%'"></span>
                        </div>
                        <div class="w-full bg-gray-100 rounded-full h-3 overflow-hidden p-0.5 border border-gray-200">
                            <div class="bg-gradient-to-r from-indigo-500 via-indigo-600 to-emerald-500 h-full rounded-full transition-all duration-300 ease-out"
                                 :style="'width: ' + uploadProgress + '%'"></div>
                        </div>
                    </div>

                    <!-- Aşamalar Göstergesi -->
                    <div class="grid grid-cols-3 gap-2 pt-3 border-t border-gray-100 text-[11px] font-medium text-gray-400">
                        <div class="flex flex-col items-center gap-1" :class="uploadStep >= 1 ? 'text-indigo-600 font-bold' : ''">
                            <span class="w-2 h-2 rounded-full" :class="uploadStep >= 1 ? 'bg-indigo-600 ring-2 ring-indigo-200' : 'bg-gray-300'"></span>
                            <span x-text="uploadStepNames[0] || '1. Yükleme'"></span>
                        </div>
                        <div class="flex flex-col items-center gap-1" :class="uploadStep >= 2 ? 'text-indigo-600 font-bold' : ''">
                            <span class="w-2 h-2 rounded-full" :class="uploadStep >= 2 ? 'bg-indigo-600 ring-2 ring-indigo-200' : 'bg-gray-300'"></span>
                            <span x-text="uploadStepNames[1] || '2. Ayrıştırma'"></span>
                        </div>
                        <div class="flex flex-col items-center gap-1" :class="uploadStep >= 3 ? 'text-emerald-600 font-bold' : ''">
                            <span class="w-2 h-2 rounded-full" :class="uploadStep >= 3 ? 'bg-emerald-600 ring-2 ring-emerald-200' : 'bg-gray-300'"></span>
                            <span x-text="uploadStepNames[2] || '3. Tablo'"></span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Hızlı Doldurma Onay Modalı (Fill Confirmation Modal) -->
            <div x-show="showFillConfirmModal"
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0 scale-95"
                 x-transition:enter-end="opacity-100 scale-100"
                 x-transition:leave="transition ease-in duration-150"
                 x-transition:leave-start="opacity-100 scale-100"
                 x-transition:leave-end="opacity-0 scale-95"
                 class="fixed inset-0 z-[99999999] flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm"
                 style="display: none; z-index: 99999999 !important;"
                 @click.self="if (!isFillingColumn) showFillConfirmModal = false"
                 @keydown.escape.window="if (!isFillingColumn) showFillConfirmModal = false">
                
                <div class="bg-white rounded-2xl shadow-2xl border border-gray-100 p-6 max-w-md w-full">
                    <div class="flex items-start gap-4">
                        <div class="w-12 h-12 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center shrink-0 border border-amber-200">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                            </svg>
                        </div>
                        <div class="flex-1">
                            <h4 class="text-base font-bold text-gray-900 mb-1" x-text="fillConfirmData.type === 'fillDown' ? 'Aşağı Boşlukları Doldurma Onayı' : 'Tüm Sütunu Doldurma Onayı'">
                            </h4>
                            <p class="text-xs text-gray-600 leading-relaxed mb-3">
                                <span class="font-semibold text-indigo-700" x-text="'[' + fillConfirmData.fieldLabel + ']'"></span> sütunundaki 
                                <strong class="font-mono bg-indigo-50 text-indigo-900 px-1.5 py-0.5 rounded border border-indigo-200 text-[11px]" x-text="fillConfirmData.valToCopy || '(Boş Değer)'"></strong> 
                                değeri yayılacaktır:
                            </p>

                            <!-- Satır Aralığı Seçim Kartı -->
                            <div class="bg-slate-50 border border-slate-200 rounded-xl p-3 text-xs text-gray-700 space-y-2.5 mb-3 shadow-2xs">
                                <div class="flex items-center justify-between">
                                    <span class="text-gray-600 font-medium">Başlangıç Satırı:</span>
                                    <span class="font-bold text-gray-800 bg-white px-2.5 py-1 rounded-md border border-gray-300 text-xs shadow-2xs" x-text="'Satır ' + fillConfirmData.startRowNumber"></span>
                                </div>
                                
                                <div class="flex items-center justify-between">
                                    <label for="fillEndRowInput" class="text-gray-700 font-semibold flex items-center gap-1 cursor-pointer">
                                        Bitiş Satırı:
                                        <span class="text-[10px] text-indigo-600 font-normal">(Değiştirilebilir)</span>
                                    </label>
                                    <div class="flex items-center gap-1.5">
                                        <span class="text-xs text-gray-500 font-medium">Satır</span>
                                        <input id="fillEndRowInput" type="number" 
                                               :min="fillConfirmData.startRowNumber" 
                                               :max="rows.length"
                                               :disabled="isFillingColumn"
                                               x-model.number="fillConfirmData.endRowNumber"
                                               @input="updateFillConfirmCounts()"
                                               @keydown.enter.prevent="if (!isFillingColumn && !isFillInvalid()) executeFillConfirm()"
                                               class="w-20 text-center font-bold text-indigo-900 bg-white border border-indigo-300 rounded-lg px-2 py-1 text-xs shadow-2xs focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 disabled:bg-gray-100 disabled:cursor-not-allowed">
                                        <span class="text-[11px] text-gray-400 font-medium" x-text="'/ ' + rows.length"></span>
                                    </div>
                                </div>

                                <div class="flex items-center justify-between pt-1.5 border-t border-slate-200">
                                    <span class="text-gray-600 font-medium">Etkilenecek Satır:</span>
                                    <span class="font-bold text-indigo-700 text-xs bg-indigo-50 px-2.5 py-0.5 rounded-full border border-indigo-200" x-text="fillConfirmData.targetRowCount + ' Satır'"></span>
                                </div>

                                <!-- Hata / Geçersiz Giriş Uyarısı -->
                                <template x-if="isFillInvalid()">
                                    <div class="text-[11px] text-red-600 font-semibold bg-red-50 p-2 rounded-lg border border-red-200 space-y-0.5">
                                        <p x-show="fillConfirmData.endRowNumber < fillConfirmData.startRowNumber">⚠️ Bitiş satırı, başlangıç satırından (Satır <span x-text="fillConfirmData.startRowNumber"></span>) küçük olamaz.</p>
                                        <p x-show="fillConfirmData.endRowNumber > rows.length">⚠️ Bitiş satırı, tablodaki toplam satır sayısını (<span x-text="rows.length"></span>) aşamaz.</p>
                                        <p x-show="!fillConfirmData.endRowNumber || fillConfirmData.targetRowCount <= 0">⚠️ Geçerli bir bitiş satırı numarası giriniz.</p>
                                    </div>
                                </template>

                                <p class="text-[10px] text-amber-700 font-medium pt-1 border-t border-slate-200" x-show="fillConfirmData.type === 'fillDown'">
                                    ℹ️ Not: Varsayılan olarak ilk dolu hücreye kadar olan boşluklar seçilmiştir. İsteğe bağlı olarak bitiş satırını değiştirebilirsiniz.
                                </p>
                                <p class="text-[10px] text-slate-500 font-medium pt-1 border-t border-slate-200" x-show="!isFillingColumn">
                                    💡 İpucu: Bu işlemi onayladıktan sonra gerekirse alttaki "Geri Al" butonu ile önceki durumuna döndürebilirsiniz.
                                </p>
                            </div>

                            <!-- Dinamik İlerleme Çubuğu (Doldurma Esnasında Görünür) -->
                            <div x-show="isFillingColumn" 
                                 x-transition:enter="transition ease-out duration-200"
                                 x-transition:enter-start="opacity-0 -translate-y-1"
                                 x-transition:enter-end="opacity-100 translate-y-0"
                                 class="mb-3.5 p-3 bg-indigo-50/90 border border-indigo-200 rounded-xl space-y-2">
                                <div class="flex justify-between items-center text-xs font-semibold">
                                    <span class="text-indigo-900 flex items-center gap-1.5">
                                        <svg class="animate-spin w-3.5 h-3.5 text-indigo-600 shrink-0" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                                        </svg>
                                        <span x-text="fillProgressText"></span>
                                    </span>
                                    <span class="text-indigo-700 font-bold" x-text="fillProgressPct + '%'"></span>
                                </div>
                                <div class="w-full bg-indigo-100 rounded-full h-2.5 overflow-hidden p-0.5 border border-indigo-200">
                                    <div class="bg-gradient-to-r from-indigo-500 to-emerald-500 h-full rounded-full transition-all duration-150 ease-out"
                                         :style="'width: ' + fillProgressPct + '%'"></div>
                                </div>
                            </div>

                            <div class="flex justify-end gap-2.5">
                                <button type="button" @click="if (!isFillingColumn) showFillConfirmModal = false" :disabled="isFillingColumn"
                                    class="px-4 py-2 text-xs font-semibold text-gray-700 bg-white hover:bg-gray-100 border border-gray-300 rounded-lg transition disabled:opacity-50 disabled:cursor-not-allowed">
                                    Vazgeç
                                </button>
                                <button type="button" @click="executeFillConfirm()" :disabled="isFillingColumn || isFillInvalid()"
                                    class="px-4 py-2 text-xs font-bold text-white bg-indigo-600 hover:bg-indigo-700 rounded-lg shadow-sm transition flex items-center gap-1.5 disabled:opacity-50 disabled:cursor-not-allowed cursor-pointer">
                                    <template x-if="!isFillingColumn">
                                        <span class="flex items-center gap-1.5">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                            </svg>
                                            <span>Evet, Sütunu Doldur</span>
                                        </span>
                                    </template>
                                    <template x-if="isFillingColumn">
                                        <span class="flex items-center gap-1.5">
                                            <svg class="animate-spin w-4 h-4 text-white" fill="none" viewBox="0 0 24 24">
                                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                                            </svg>
                                            <span>Dolduruluyor...</span>
                                        </span>
                                    </template>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Evrensel Veri Temizleme & Silme Onay Modalı (Custom Confirmation Modal) -->
            <div x-show="showConfirmModal"
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0 scale-95"
                 x-transition:enter-end="opacity-100 scale-100"
                 x-transition:leave="transition ease-in duration-150"
                 x-transition:leave-start="opacity-100 scale-100"
                 x-transition:leave-end="opacity-0 scale-95"
                 class="fixed inset-0 z-[99999999] flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs"
                 style="display: none; z-index: 99999999 !important;"
                 @click.self="showConfirmModal = false"
                 @keydown.escape.window="showConfirmModal = false">
                
                <div class="bg-white rounded-2xl shadow-2xl border border-gray-100 p-6 max-w-md w-full">
                    <div class="flex items-start gap-4">
                        <div class="w-12 h-12 rounded-xl bg-rose-100 text-rose-600 flex items-center justify-center flex-shrink-0">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                            </svg>
                        </div>
                        <div class="flex-1 min-w-0">
                            <h3 class="text-base font-bold text-gray-900" x-text="confirmModalData.title"></h3>
                            <p class="text-xs text-gray-500 mt-0.5" x-text="confirmModalData.subtitle"></p>

                            <div class="mt-4 p-3 bg-rose-50 border border-rose-200 rounded-xl text-xs text-rose-900 leading-relaxed"
                                 x-html="confirmModalData.message">
                            </div>

                            <!-- Bilgi Rozetleri -->
                            <div class="mt-3 flex flex-wrap items-center gap-2">
                                <template x-if="confirmModalData.filledCount !== undefined && confirmModalData.filledCount !== null">
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-bold bg-amber-100 text-amber-800 border border-amber-200">
                                        <svg class="w-3.5 h-3.5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                        <span x-text="'Dolu Hücre: ' + confirmModalData.filledCount + ' Adet'"></span>
                                    </span>
                                </template>
                                <template x-if="confirmModalData.rowCount !== undefined && confirmModalData.rowCount !== null">
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-bold bg-blue-100 text-blue-800 border border-blue-200">
                                        <span x-text="'Satır Sayısı: ' + confirmModalData.rowCount"></span>
                                    </span>
                                </template>
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[11px] font-medium text-gray-500 bg-gray-100">
                                    ↩ Geri alınabilir (50 işlem)
                                </span>
                            </div>

                            <div class="mt-6 flex justify-end gap-2.5">
                                <button type="button" @click="showConfirmModal = false"
                                    class="px-4 py-2 text-xs font-semibold text-gray-700 bg-white hover:bg-gray-100 border border-gray-300 rounded-lg transition cursor-pointer">
                                    Vazgeç
                                </button>
                                <button type="button" @click="executeConfirm()"
                                    class="px-4 py-2 text-xs font-bold text-white bg-rose-600 hover:bg-rose-700 active:bg-rose-800 rounded-lg shadow-sm transition flex items-center gap-1.5 cursor-pointer">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                    </svg>
                                    <span x-text="confirmModalData.confirmBtnText"></span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- İçe Aktarma & Yapıştırma Doğrulama Modalı (Import & Paste Validation Modal) -->
            <div x-show="showImportValidationModal"
                 x-transition:enter="transition ease-out duration-250"
                 x-transition:enter-start="opacity-0 scale-95"
                 x-transition:enter-end="opacity-100 scale-100"
                 x-transition:leave="transition ease-in duration-150"
                 x-transition:leave-start="opacity-100 scale-100"
                 x-transition:leave-end="opacity-0 scale-95"
                 class="fixed inset-0 z-[99999999] flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs"
                 style="display: none; z-index: 99999999 !important;"
                 @click.self="showImportValidationModal = false"
                 @keydown.escape.window="showImportValidationModal = false">
                
                <div class="bg-white rounded-2xl shadow-2xl border border-gray-100 p-6 max-w-2xl w-full max-h-[85vh] flex flex-col">
                    <!-- Modal Başlık -->
                    <div class="flex items-start justify-between gap-4 pb-4 border-b border-gray-100 shrink-0">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl bg-amber-100 text-amber-600 flex items-center justify-center shrink-0">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-base font-bold text-gray-900">İçe Aktarma / Yapıştırma Doğrulama Bildirimi</h3>
                                <p class="text-xs text-gray-500 mt-0.5">Veriler aktarılırken form kuralına uymayan kriterler veya eşleşmeyen seçenekler tespit edildi.</p>
                            </div>
                        </div>
                        <button type="button" @click="showImportValidationModal = false" class="text-gray-400 hover:text-gray-600 p-1 rounded-lg hover:bg-gray-100 transition cursor-pointer">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>

                    <!-- Modal İçeriği (Kaydırılabilir) -->
                    <div class="flex-1 overflow-y-auto py-4 space-y-4 pr-1">
                        <!-- 1. Kilitli Kriter (İşyeri) Uyuşmazlığı Bölümü -->
                        <div x-show="importValidationData.partitionMismatches && importValidationData.partitionMismatches.length > 0"
                             class="p-4 bg-amber-50/90 border border-amber-300 rounded-xl space-y-2.5">
                            <div class="flex items-center gap-2 text-amber-900 font-bold text-xs">
                                <span>🔒</span>
                                <span>Kilitli Kriter (<span x-text="partitionFieldId ? (fields.find(f => f.id == partitionFieldId)?.label || 'Kriter') : 'Kriter'"></span>) Uyarısı</span>
                            </div>
                            <p class="text-xs text-amber-900 leading-relaxed">
                                Bu form <strong class="text-amber-950 font-bold underline" x-text="partitionValue"></strong> kriteri için açılmıştır. Yapıştırdığınız / aktardığınız verilerde farklı kriter değerleri tespit edilmiş olup, form bütünlüğünü korumak adına bu alanlar otomatik olarak kilitli kriter olan <strong class="text-amber-950 font-bold" x-text="partitionValue"></strong> değerine dönüştürülmüştür.
                            </p>
                            
                            <!-- Özet Listesi -->
                            <div class="mt-2 pt-2 border-t border-amber-200/80 space-y-1.5">
                                <template x-for="(item, idx) in getPartitionMismatchSummary()" :key="idx">
                                    <div class="flex items-center justify-between text-xs bg-white/80 px-3 py-1.5 rounded-lg border border-amber-200">
                                        <div class="flex items-center gap-2">
                                            <span class="text-amber-600 font-bold">•</span>
                                            <span class="text-gray-700">Excel / Panoda Tespit Edilen: <strong class="text-amber-900 font-mono font-bold" x-text="item.val"></strong></span>
                                        </div>
                                        <span class="text-xs font-semibold text-amber-800" x-text="item.count + ' satırda ➔ ' + partitionValue + ' yapıldı'"></span>
                                    </div>
                                </template>
                            </div>
                        </div>

                        <!-- 2. Eşleşmeyen Açılır Kutu (Dropdown / Master Data) Değerleri Bölümü -->
                        <div x-show="importValidationData.unmatchedDropdowns && importValidationData.unmatchedDropdowns.length > 0"
                             class="space-y-2.5">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-2 text-rose-800 font-bold text-xs">
                                    <span>⚠️</span>
                                    <span>Tanımlı Seçeneklerde Bulunamayan Değerler (<span x-text="importValidationData.unmatchedDropdowns.length"></span> Adet)</span>
                                </div>
                                <span class="text-[11px] text-gray-500 font-medium">Bu hücreler 'Seçiniz...' olarak ayarlanmıştır</span>
                            </div>
                            <p class="text-xs text-gray-600">
                                Aşağıdaki satırlarda kopyalanan / yüklenen veriler form şablonunda tanımlı seçenekler arasında bulunamadığı için boş bırakılmıştır. Lütfen kontrol edip geçerli bir seçenek seçiniz:
                            </p>

                            <div class="overflow-hidden rounded-xl border border-gray-200 max-h-56 overflow-y-auto">
                                <table class="w-full text-left text-xs">
                                    <thead class="bg-gray-50 text-gray-700 font-bold sticky top-0 border-b border-gray-200">
                                        <tr>
                                            <th class="py-2 px-3 w-16">Satır</th>
                                            <th class="py-2 px-3">Sütun / Alan</th>
                                            <th class="py-2 px-3">Excel / Panodan Gelen</th>
                                            <th class="py-2 px-3 text-right">Durum</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-100 bg-white">
                                        <template x-for="(item, idx) in importValidationData.unmatchedDropdowns" :key="idx">
                                            <tr class="hover:bg-amber-50/40 transition">
                                                <td class="py-2 px-3 font-semibold text-gray-600" x-text="'Satır ' + item.rowNum"></td>
                                                <td class="py-2 px-3 font-bold text-gray-900" x-text="item.fieldLabel"></td>
                                                <td class="py-2 px-3">
                                                    <span class="inline-block px-2 py-0.5 rounded bg-rose-50 text-rose-700 font-mono text-[11px] border border-rose-200 font-semibold" x-text="item.pastedVal"></span>
                                                </td>
                                                <td class="py-2 px-3 text-right">
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-gray-100 text-gray-600 border border-gray-200">
                                                        Seçiniz... (Boş)
                                                    </span>
                                                </td>
                                            </tr>
                                        </template>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Modal Alt Aksiyon Butonları -->
                    <div class="mt-4 pt-3 border-t border-gray-100 flex items-center justify-between shrink-0">
                        <button type="button" @click="clearValidationWarnings()"
                            class="px-3 py-1.5 text-xs font-semibold text-gray-500 hover:text-gray-700 hover:bg-gray-100 rounded-lg transition cursor-pointer">
                            Uyarıları Temizle
                        </button>
                        <button type="button" @click="showImportValidationModal = false"
                            class="px-5 py-2 text-xs font-bold text-white bg-indigo-600 hover:bg-indigo-700 active:bg-indigo-800 rounded-xl shadow-sm transition flex items-center gap-1.5 cursor-pointer">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            <span>Anladım, Tabloyu Düzenlemeye Devam Et</span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Excel Fill Toast Notification -->
            <div x-show="toastMessage"
                 x-transition:enter="transition ease-out duration-250"
                 x-transition:enter-start="opacity-0 translate-y-3"
                 x-transition:enter-end="opacity-100 translate-y-0"
                 x-transition:leave="transition ease-in duration-200"
                 x-transition:leave-start="opacity-100 translate-y-0"
                 x-transition:leave-end="opacity-0 translate-y-3"
                 class="fixed bottom-6 right-6 z-[99999999] bg-slate-900/90 text-white px-4 py-2.5 rounded-xl shadow-2xl flex items-center gap-2.5 text-xs font-semibold backdrop-blur-sm border border-slate-700/60"
                 style="display: none; z-index: 99999999 !important;">
                <span class="flex h-2 w-2 relative">
                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                    <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"></span>
                </span>
                <span x-text="toastMessage"></span>
            </div>
                </div>
            </form>


        </div>
    </div>

    <!-- AlpineJS Grid Logic -->
    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('formEntryApp', (fields, showTotals, masterDataTree, currentUserScope, usersList, formTemplateId, initialPinnedColumnIds, partitionFieldId = null, partitionValue = null) => ({
                fields: fields,
                showTotals: showTotals,
                masterDataTree: masterDataTree || {},
                currentUserScope: currentUserScope || { is_admin: true, is_manager: true, allowed_user_ids: [], allowed_user_names: [] },
                usersList: usersList || [],
                formTemplateId: formTemplateId || {{ $formTemplate->id }},
                partitionFieldId: partitionFieldId,
                partitionValue: partitionValue,
                pinnedColumns: (() => {
                    let norm = { left: [], right: [] };
                    if (Array.isArray(initialPinnedColumnIds)) {
                        norm.left = initialPinnedColumnIds.map(Number);
                    } else if (initialPinnedColumnIds && typeof initialPinnedColumnIds === 'object') {
                        norm.left = Array.isArray(initialPinnedColumnIds.left) ? initialPinnedColumnIds.left.map(Number) : [];
                        norm.right = Array.isArray(initialPinnedColumnIds.right) ? initialPinnedColumnIds.right.map(Number) : [];
                    }
                    return norm;
                })(),
                pinnedLeftOffsets: {},
                pinnedRightOffsets: {},
                rows: [],
                rowNumberMap: new Map(),
                rowIndexMap: new Map(),
                totals: {},
                filteredTotals: {},
                history: [],
                isWide: false,
                isFullscreen: false,
                normalCardHeight: 'calc(100vh - 180px)',
                autoSaveStatus: 'idle', // 'idle' | 'saving' | 'saved' | 'error'
                autoSaveLastTime: @if(isset($existingEntry) && $existingEntry->status === 'draft') '{{ $existingEntry->updated_at->format("H:i") }}' @else null @endif,
                hasUnsavedChanges: false,
                isAutoSaving: false,
                entryId: {{ $existingEntry ? $existingEntry->id : 'null' }},
                saveUrl: '{{ $existingEntry ? route('form-entries.update', $existingEntry) : route('form-entries.store', $formTemplate) }}',
                isEditMode: {{ $existingEntry ? 'true' : 'false' }},
                autoSaveInterval: null,
                showConfirmModal: false,
                confirmModalData: {
                    type: '',
                    title: '',
                    subtitle: '',
                    message: '',
                    confirmBtnText: 'Evet, Temizle',
                    fieldId: null,
                    fieldName: '',
                    filledCount: 0,
                    rowCount: 0,
                    rowIndex: null,
                    rowNumber: null
                },
                isUploading: false,
                showUploadModal: false,
                uploadModalTitle: 'Excel Dosyası İçe Aktarılıyor',
                uploadModalSubtitle: 'Büyük veri setleri optimize edilerek tabloya aktarılmaktadır.',
                uploadStepNames: ['1. Yükleme', '2. Ayrıştırma', '3. Tablo'],
                uploadProgress: '0',
                uploadStep: 1,
                uploadStatusText: '',
                totalImportRows: 0,
                estimatedTimeText: '',
                isLargeDataset: false,
                liveTimerText: '0 sn',
                estimatedRemainingText: 'Hesaplanıyor...',
                importStartTime: null,
                elapsedTimerInterval: null,
                isSubmitting: false,
                submittingAction: null,
                showFillConfirmModal: false,
                isFillingColumn: false,
                fillProgressPct: 0,
                fillProgressText: '',
                fillConfirmData: {
                    type: '',
                    fieldId: null,
                    fieldLabel: '',
                    sourceRow: null,
                    valToCopy: '',
                    targetRowCount: 0,
                    startRowNumber: 1,
                    endRowNumber: 1
                },
                cachedUserLists: {},
                cachedMasterData: {},
                filters: {},
                uniqueValues: {},
                toastMessage: '',
                toastTimeout: null,
                showImportValidationModal: false,
                importValidationData: {
                    partitionMismatches: [],
                    unmatchedDropdowns: []
                },

                getPartitionMismatchSummary() {
                    let counts = {};
                    (this.importValidationData?.partitionMismatches || []).forEach(m => {
                        let val = m.foundVal || '(Boş)';
                        counts[val] = (counts[val] || 0) + 1;
                    });
                    return Object.entries(counts).map(([val, count]) => ({
                        val: val,
                        count: count
                    }));
                },

                clearValidationWarnings() {
                    this.importValidationData = {
                        partitionMismatches: [],
                        unmatchedDropdowns: []
                    };
                    this.showImportValidationModal = false;
                },

                updateRowMaps() {
                    this.rowNumberMap.clear();
                    this.rowIndexMap.clear();
                    for (let i = 0; i < this.rows.length; i++) {
                        const r = this.rows[i];
                        this.rowNumberMap.set(r.id, i + 1);
                        this.rowIndexMap.set(r.id, i);
                    }
                },

                getRowNumber(rowOrIndex) {
                    if (typeof rowOrIndex === 'object' && rowOrIndex !== null) {
                        return this.rowNumberMap.get(rowOrIndex.id) || (this.rows.indexOf(rowOrIndex) + 1);
                    }
                    return (parseInt(rowOrIndex) || 0) + 1;
                },

                getRowIndex(rowOrIndex) {
                    if (typeof rowOrIndex === 'object' && rowOrIndex !== null) {
                        const idx = this.rowIndexMap.get(rowOrIndex.id);
                        return idx !== undefined ? idx : this.rows.indexOf(rowOrIndex);
                    }
                    return parseInt(rowOrIndex) || 0;
                },

                getRow(rowOrIndex) {
                    if (typeof rowOrIndex === 'object' && rowOrIndex !== null) return rowOrIndex;
                    return this.rows[rowOrIndex];
                },

                showToast(msg) {
                    this.toastMessage = msg;
                    if (this.toastTimeout) clearTimeout(this.toastTimeout);
                    this.toastTimeout = setTimeout(() => {
                        this.toastMessage = '';
                    }, 3500);
                },

                startLiveTimer(totalRows) {
                    this.importStartTime = Date.now();
                    this.liveTimerText = '0 sn';
                    if (this.elapsedTimerInterval) {
                        clearInterval(this.elapsedTimerInterval);
                        this.elapsedTimerInterval = null;
                    }

                    let initialEstSec = Math.max(1, Math.ceil((totalRows || 0) * 0.005));
                    this.estimatedRemainingText = initialEstSec >= 60 
                        ? `~${Math.ceil(initialEstSec / 60)} dakika` 
                        : `~${initialEstSec} sn`;

                    this.elapsedTimerInterval = setInterval(() => {
                        if (!this.showUploadModal) {
                            this.stopLiveTimer();
                            return;
                        }
                        const elapsedSec = Math.floor((Date.now() - this.importStartTime) / 1000);
                        this.liveTimerText = elapsedSec < 60 
                            ? `${elapsedSec} sn` 
                            : `${Math.floor(elapsedSec / 60)} dk ${elapsedSec % 60} sn`;
                    }, 500);
                },

                updateLiveRemainingTime(processed, totalRows) {
                    if (!this.importStartTime || processed <= 0) return;
                    const elapsedMs = Date.now() - this.importStartTime;
                    if (elapsedMs > 250) {
                        const msPerRow = elapsedMs / processed;
                        const remainingRows = totalRows - processed;
                        const remSec = Math.ceil((remainingRows * msPerRow) / 1000);
                        if (remSec > 0) {
                            this.estimatedRemainingText = remSec < 60 
                                ? `~${remSec} sn` 
                                : `~${Math.ceil(remSec / 60)} dk`;
                        } else {
                            this.estimatedRemainingText = 'Tamamlanıyor...';
                        }
                    }
                },

                stopLiveTimer() {
                    if (this.elapsedTimerInterval) {
                        clearInterval(this.elapsedTimerInterval);
                        this.elapsedTimerInterval = null;
                    }
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

                getColumnPinState(fieldId) {
                    if (this.isColumnPinnedLeft(fieldId)) return 'left';
                    if (this.isColumnPinnedRight(fieldId)) return 'right';
                    return 'none';
                },

                isLastPinnedLeft(fieldId) {
                    const numId = Number(fieldId);
                    if (!this.isColumnPinnedLeft(numId)) return false;
                    for (let i = this.fields.length - 1; i >= 0; i--) {
                        if (this.isColumnPinnedLeft(this.fields[i].id)) {
                            return Number(this.fields[i].id) === numId;
                        }
                    }
                    return false;
                },

                isFirstPinnedRight(fieldId) {
                    const numId = Number(fieldId);
                    if (!this.isColumnPinnedRight(numId)) return false;
                    for (let i = 0; i < this.fields.length; i++) {
                        if (this.isColumnPinnedRight(this.fields[i].id)) {
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
                    return this.pinnedRightOffsets[idStr] || '64px';
                },

                getColumnStickyStyle(fieldId) {
                    const idStr = String(fieldId);
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
                            if (this.isColumnPinnedLeft(f.id)) {
                                leftOffsets[String(f.id)] = currentLeft + 'px';
                                const thEl = document.getElementById('th-field-' + f.id);
                                const w = thEl ? thEl.offsetWidth : 160;
                                currentLeft += w;
                            }
                        }
                        this.pinnedLeftOffsets = leftOffsets;

                        // 2. Sağa sabitlenen sütunların ofsetlerini hesapla (sağdan sola)
                        let currentRight = 64; // 'Sil' sütunu w-16 = 64px
                        let rightOffsets = {};
                        for (let i = this.fields.length - 1; i >= 0; i--) {
                            const f = this.fields[i];
                            if (this.isColumnPinnedRight(f.id)) {
                                rightOffsets[String(f.id)] = currentRight + 'px';
                                const thEl = document.getElementById('th-field-' + f.id);
                                const w = thEl ? thEl.offsetWidth : 160;
                                currentRight += w;
                            }
                        }
                        this.pinnedRightOffsets = rightOffsets;
                    });
                },

                updateCardHeight() {
                    if (this.isFullscreen) return;
                    this.$nextTick(() => {
                        const el = this.$refs.mainCardContainer;
                        if (!el) return;
                        const rect = el.getBoundingClientRect();
                        const docTop = rect.top + window.scrollY;
                        // Ekranın en altına 8px pay bırakarak kart yüksekliğini dinamik hesaplar: Butonlar ve yatay scroll her zaman ekranda sabit kalır
                        const available = Math.floor(window.innerHeight - docTop - 8);
                        this.normalCardHeight = Math.max(available, 440) + 'px';
                    });
                },

                setColumnPin(fieldId, side) {
                    const numId = Number(fieldId);
                    if (!this.pinnedColumns || typeof this.pinnedColumns !== 'object') {
                        this.pinnedColumns = { left: [], right: [] };
                    }
                    this.pinnedColumns.left = (this.pinnedColumns.left || []).filter(id => Number(id) !== numId);
                    this.pinnedColumns.right = (this.pinnedColumns.right || []).filter(id => Number(id) !== numId);

                    const targetField = this.fields.find(f => Number(f.id) === numId);
                    const fieldLabel = targetField ? (targetField.label || targetField.name) : 'Sütun';

                    if (side === 'left') {
                        this.pinnedColumns.left.push(numId);
                        this.showToast(`📌 [${fieldLabel}] sütunu sola sabitlendi.`);
                    } else if (side === 'right') {
                        this.pinnedColumns.right.push(numId);
                        this.showToast(`📌 [${fieldLabel}] sütunu sağa sabitlendi.`);
                    } else {
                        this.showToast(`✓ [${fieldLabel}] sütununun sabitlemesi kaldırıldı.`);
                    }

                    this.updatePinnedOffsets();

                    try {
                        localStorage.setItem(`pinned_columns_template_${this.formTemplateId}`, JSON.stringify(this.pinnedColumns));
                    } catch(e) {}

                    this.savePinnedColumnsToServer();
                },

                togglePinColumn(fieldId) {
                    if (this.isColumnPinnedLeft(fieldId)) {
                        this.setColumnPin(fieldId, 'none');
                    } else {
                        this.setColumnPin(fieldId, 'left');
                    }
                },

                savePinnedColumnsToServer() {
                    fetch('{{ route('user-preferences.pinned-columns') }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                        },
                        body: JSON.stringify({
                            template_id: this.formTemplateId,
                            pinned_columns: this.pinnedColumns
                        })
                    }).catch(err => {
                        console.warn('Sütun sabitleme tercihi kaydedilemedi:', err);
                    });
                },

                getFieldFormulaHumanText(field) {
                    if (!field || !field.calculation_formula) return '';
                    if (field._formulaHumanText !== undefined) return field._formulaHumanText;
                    let text = field.calculation_formula;
                    this.fields.forEach(f => {
                        text = text.replaceAll('#{' + f.id + '}#', '[' + (f.label || f.name) + ']');
                    });
                    field._formulaHumanText = text;
                    return text;
                },

                getCellDisabledReason(field, rowOrIndex) {
                    if (!field) return '';
                    let row = this.getRow(rowOrIndex);

                    // 1. Otomatik Kullanıcı
                    if (field.type === 'auto_user') {
                        return '🔒 Bu alan sisteme giriş yapan kullanıcıyı ({{ Auth::user()->name }}) otomatik gösterir ve elle değiştirilemez.';
                    }

                    // 2. Formüllü / Koşullu Hesaplama
                    if (this.isFieldCalculated(field, row)) {
                        let formula = this.getFieldFormulaHumanText(field);
                        let reason = '🔒 Bu alan formül ile otomatik hesaplanmaktadır.\n• Formül: ' + formula;
                        if (field.calc_condition_field_id && field.calc_condition_value) {
                            let condField = this.fields.find(f => f.id == field.calc_condition_field_id);
                            let condLabel = condField ? (condField.label || condField.name) : 'Koşul Alanı';
                            reason += '\n• Koşul: [' + condLabel + '] = ' + field.calc_condition_value + ' seçildiğinde formül aktifleşir.';
                        }
                        return reason;
                    }

                    // 3. Master Data (Bağlı/Kademeli Alan)
                    if (field.type === 'master_data') {
                        let status = this.getMasterDataStatus(row, field);
                        if (status.hasParent && !status.isParentSelected) {
                            return '⚠️ Bu alanı seçebilmek için önce \'' + status.parentLabel + '\' seçilmelidir.';
                        }
                        if (status.optionsCount === 0) {
                            return '⚠️ Bu kategoriye ait tanımlı veri bulunmamaktadır.';
                        }
                    }

                    // 4. Kullanıcı Listesi
                    if (field.type === 'user_list') {
                        let opts = this.getUserListOptions(row, field);
                        if (!opts || opts.length === 0) {
                            return '⚠️ Grubunuza atanmış veya yetkili kullanıcı bulunamadı.';
                        }
                    }

                    return '';
                },

                promptFillDown(sourceRow, fieldId) {
                    const startIdx = this.getRowIndex(sourceRow);
                    if (startIdx < 0 || startIdx >= this.rows.length - 1) {
                        this.showToast('Aşağıda doldurulacak başka satır bulunamadı.');
                        return;
                    }
                    const valToCopy = sourceRow.values[fieldId];
                    if (valToCopy === undefined || valToCopy === null || String(valToCopy).trim() === '') {
                        this.showToast('Kopyalanacak hücre boş olduğu için doldurma yapılamaz.');
                        return;
                    }

                    // Sadece aralıktaki ardışık boş hücreleri bul; ilk dolu hücreye rastlayınca dur (break)!
                    let emptyEndIdx = -1;
                    for (let i = startIdx + 1; i < this.rows.length; i++) {
                        let cellVal = this.rows[i].values ? this.rows[i].values[fieldId] : '';
                        let isEmpty = (cellVal === undefined || cellVal === null || String(cellVal).trim() === '');
                        if (isEmpty) {
                            emptyEndIdx = i;
                        } else {
                            // Dolu bir hücreye (örn: ADT) rastlandı, DUR!
                            break;
                        }
                    }

                    if (emptyEndIdx === -1) {
                        this.showToast('Altındaki ilk hücre zaten dolu veya doldurulacak boş satır yok.');
                        return;
                    }

                    const targetCount = (emptyEndIdx - startIdx);
                    const targetField = this.fields.find(f => f.id == fieldId);
                    const fieldLabel = targetField ? (targetField.label || targetField.name) : 'Sütun';

                    this.isFillingColumn = false;
                    this.fillProgressPct = 0;
                    this.fillProgressText = '';
                    this.fillConfirmData = {
                        type: 'fillDown',
                        fieldId: fieldId,
                        fieldLabel: fieldLabel,
                        sourceRow: sourceRow,
                        valToCopy: valToCopy,
                        targetRowCount: targetCount,
                        startRowNumber: startIdx + 2,
                        endRowNumber: emptyEndIdx + 1
                    };
                    this.showFillConfirmModal = true;
                },

                promptFillColumnFromFirstRow(fieldId) {
                    if (!this.rows || this.rows.length === 0) return;
                    if (this.rows.length === 1) {
                        this.showToast('Sütunda doldurulacak başka satır yok.');
                        return;
                    }
                    const firstRow = this.rows[0];
                    const valToCopy = firstRow.values[fieldId];
                    const targetField = this.fields.find(f => f.id == fieldId);
                    const fieldLabel = targetField ? (targetField.label || targetField.name) : 'Sütun';

                    this.isFillingColumn = false;
                    this.fillProgressPct = 0;
                    this.fillProgressText = '';
                    this.fillConfirmData = {
                        type: 'fillColumnFromFirstRow',
                        fieldId: fieldId,
                        fieldLabel: fieldLabel,
                        sourceRow: firstRow,
                        valToCopy: valToCopy,
                        targetRowCount: this.rows.length - 1,
                        startRowNumber: 2,
                        endRowNumber: this.rows.length
                    };
                    this.showFillConfirmModal = true;
                },

                updateFillConfirmCounts() {
                    let start = parseInt(this.fillConfirmData.startRowNumber) || 1;
                    let end = parseInt(this.fillConfirmData.endRowNumber);
                    if (isNaN(end) || end < start) {
                        this.fillConfirmData.targetRowCount = 0;
                        return;
                    }
                    if (end > this.rows.length) {
                        end = this.rows.length;
                    }
                    this.fillConfirmData.targetRowCount = (end - start + 1);
                },

                isFillInvalid() {
                    let start = parseInt(this.fillConfirmData.startRowNumber) || 1;
                    let end = parseInt(this.fillConfirmData.endRowNumber);
                    return isNaN(end) || end < start || end > this.rows.length || this.fillConfirmData.targetRowCount <= 0;
                },

                executeFillConfirm() {
                    if (this.isFillingColumn) return;
                    if (this.isFillInvalid()) return;

                    const { fieldId, valToCopy, startRowNumber, endRowNumber } = this.fillConfirmData;
                    if (!fieldId) {
                        this.showFillConfirmModal = false;
                        return;
                    }

                    const startIdx = parseInt(startRowNumber) - 1;
                    const endIdx = parseInt(endRowNumber) - 1;
                    const totalToFill = endIdx - startIdx + 1;

                    if (totalToFill <= 0 || startIdx < 0 || endIdx >= this.rows.length) {
                        this.showFillConfirmModal = false;
                        return;
                    }

                    this.isFillingColumn = true;
                    this.hasUnsavedChanges = true;
                    this.fillProgressPct = 0;
                    this.fillProgressText = 'İşlem başlatılıyor...';
                    this.saveHistory();

                    // Küçük veri setleri (<= 25 satır) için doğrudan hızlı işlem
                    if (totalToFill <= 25) {
                        let filledCount = 0;
                        for (let i = startIdx; i <= endIdx; i++) {
                            if (this.rows[i]) {
                                this.rows[i].values[fieldId] = valToCopy;
                                this.handleMasterDataChange(this.rows[i], fieldId);
                                filledCount++;
                            }
                        }
                        this.calculateTotals();
                        this.isFillingColumn = false;
                        this.showFillConfirmModal = false;
                        this.showToast(`✓ "${valToCopy || '(Boş)'}" değeri ${filledCount} satıra başarıyla dolduruldu.`);
                        return;
                    }

                    // Büyük veri setleri (> 25 satır) için tarayıcı donmasını önleyen parçalı (chunked) işlem
                    const CHUNK_SIZE = 40;
                    let currentIdx = startIdx;
                    let filledCount = 0;

                    const processFillChunk = () => {
                        let nextBatchEnd = Math.min(currentIdx + CHUNK_SIZE, endIdx + 1);
                        for (let i = currentIdx; i < nextBatchEnd; i++) {
                            if (this.rows[i]) {
                                this.rows[i].values[fieldId] = valToCopy;
                                this.handleMasterDataChange(this.rows[i], fieldId);
                                filledCount++;
                            }
                        }
                        currentIdx = nextBatchEnd;
                        let completedCount = currentIdx - startIdx;
                        let pct = Math.min(99, Math.round((completedCount / totalToFill) * 100));
                        this.fillProgressPct = pct;
                        this.fillProgressText = `${completedCount} / ${totalToFill} satır dolduruldu... (%${pct})`;

                        if (currentIdx <= endIdx) {
                            setTimeout(processFillChunk, 10);
                        } else {
                            this.fillProgressPct = 100;
                            this.fillProgressText = `Tamamlandı! ${filledCount} satır dolduruldu.`;
                            this.calculateTotals();
                            setTimeout(() => {
                                this.isFillingColumn = false;
                                this.showFillConfirmModal = false;
                                this.showToast(`✓ "${valToCopy || '(Boş)'}" değeri ${filledCount} satıra başarıyla dolduruldu.`);
                            }, 350);
                        }
                    };

                    setTimeout(processFillChunk, 20);
                },

                // Geriye dönük uyumluluk için alias
                fillDown(sourceRow, fieldId) {
                    this.promptFillDown(sourceRow, fieldId);
                },

                fillColumnFromFirstRow(fieldId) {
                    this.promptFillColumnFromFirstRow(fieldId);
                },

                initFilters() {
                    this.fields.forEach(f => {
                        this.filters[f.id] = [];
                        this.uniqueValues[f.id] = [];
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
                    let hasFilter = false;
                    for (const fieldId in this.filters) {
                        if (this.filters[fieldId] && this.filters[fieldId].length > 0) {
                            hasFilter = true;
                            break;
                        }
                    }
                    if (!hasFilter) return this.rows;

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

                clearColumn(fieldId) {
                    this.promptClearColumn(fieldId);
                },

                promptClearColumn(fieldId) {
                    const field = this.fields.find(f => f.id == fieldId);
                    const fieldName = field ? (field.label || field.name) : 'Sütun';

                    // Bu sütundaki dolu hücre sayısını hesapla
                    let filledCount = 0;
                    for (let i = 0; i < this.rows.length; i++) {
                        const val = this.rows[i].values ? this.rows[i].values[fieldId] : null;
                        if (val !== undefined && val !== null && String(val).trim() !== '') {
                            filledCount++;
                        }
                    }

                    this.confirmModalData = {
                        type: 'column',
                        title: 'Sütun Verilerini Temizle',
                        subtitle: `"${fieldName}" sütunu için temizleme onayı`,
                        fieldId: fieldId,
                        fieldName: fieldName,
                        filledCount: filledCount,
                        rowCount: this.rows.length,
                        message: filledCount > 0
                            ? `Bu sütunda <strong>${filledCount} adet</strong> hücrede veri girişi bulunmaktadır. Sütundaki tüm veriler silinecektir. Devam etmek istiyor musunuz?`
                            : `Bu sütunda henüz herhangi bir veri girişi bulunmamaktadır. Temizlemek istiyor musunuz?`,
                        confirmBtnText: 'Evet, Sütunu Temizle',
                        rowIndex: null,
                        rowNumber: null
                    };
                    this.showConfirmModal = true;
                },

                executeClearColumn(fieldId) {
                    this.saveHistory();
                    this.hasUnsavedChanges = true;
                    this.rows.forEach(r => {
                        if (r.values) r.values[fieldId] = '';
                    });
                    this.calculateTotals();
                    this.showConfirmModal = false;
                    this.showToast(`✓ "${this.confirmModalData.fieldName}" sütunu temizlendi.`);
                },

                init() {
                    this.initFilters();

                    // LocalStorage kontrolü (veritabanında henüz kayıt yoksa veya yedek olarak)
                    const hasLeft = this.pinnedColumns && Array.isArray(this.pinnedColumns.left) && this.pinnedColumns.left.length > 0;
                    const hasRight = this.pinnedColumns && Array.isArray(this.pinnedColumns.right) && this.pinnedColumns.right.length > 0;
                    if (!hasLeft && !hasRight && this.formTemplateId) {
                        try {
                            const local = localStorage.getItem('pinned_columns_template_' + this.formTemplateId);
                            if (local) {
                                const parsed = JSON.parse(local);
                                if (Array.isArray(parsed) && parsed.length > 0) {
                                    this.pinnedColumns = { left: parsed.map(Number), right: [] };
                                } else if (parsed && typeof parsed === 'object') {
                                    this.pinnedColumns = {
                                        left: Array.isArray(parsed.left) ? parsed.left.map(Number) : [],
                                        right: Array.isArray(parsed.right) ? parsed.right.map(Number) : []
                                    };
                                }
                            }
                        } catch(e) {}
                    }

                    this.updatePinnedOffsets();
                    this.updateCardHeight();
                    window.addEventListener('resize', () => {
                        this.updatePinnedOffsets();
                        this.updateCardHeight();
                    });
                    setTimeout(() => this.updateCardHeight(), 50);
                    setTimeout(() => this.updateCardHeight(), 250);
                    setTimeout(() => this.updateCardHeight(), 600);

                    // Otomatik Taslak Kaydetme: Her 45 saniyede bir kaydedilmemiş değişiklik varsa arka planda sessizce kaydeder
                    this.autoSaveInterval = setInterval(() => {
                        if (this.hasUnsavedChanges && !this.isAutoSaving && !this.isSubmitting && !this.isUploading) {
                            this.saveDraftAjax(true);
                        }
                    }, 45000);

                    // Sayfadan ayrılma veya sekme kapatma koruması
                    window.addEventListener('beforeunload', (e) => {
                        if (this.hasUnsavedChanges && !this.isSubmitting) {
                            e.preventDefault();
                            e.returnValue = 'Kaydedilmemiş değişiklikleriniz bulunmaktadır. Ayrılmak istediğinize emin misiniz?';
                            return e.returnValue;
                        }
                    });

                    @if($existingEntry && $existingEntry->rows->count() > 0)
                        let rawRows = {!! json_encode($existingEntry->rows->map(function ($row) {
                            $values = [];
                            foreach ($row->values as $val) {
                                $values[$val->form_field_id] = $val->value;
                            }
                            return ['id' => $row->id, 'values' => (object) $values];
                        })) !!};

                        if (rawRows.length > 50) {
                            // Büyük kayıtlı veri seti: Sayfa açılışında parçalı olarak render et
                            this.uploadModalTitle = 'Kayıtlı Form Verileri Yükleniyor';
                            this.uploadModalSubtitle = 'Kayıtlı satırlar optimize edilerek tabloya aktarılmaktadır.';
                            this.uploadStepNames = ['1. Hazırlık', '2. Tablo', '3. Tamamlandı'];
                            this.showUploadModal = true;
                            this.totalImportRows = rawRows.length;
                            this.isLargeDataset = true;
                            let estSec = Math.max(2, Math.ceil(rawRows.length * 0.016));
                            this.estimatedTimeText = estSec >= 60 ? `~${Math.ceil(estSec / 60)} dakika` : `~${estSec} saniye`;
                            this.uploadProgress = '10.0';
                            this.uploadStep = 2;
                            this.uploadStatusText = `${rawRows.length} satır tabloya yerleştiriliyor...`;

                            const CHUNK_SIZE = 35;
                            let chunkIdx = 0;
                            const loadChunk = () => {
                                let start = chunkIdx * CHUNK_SIZE;
                                let end = Math.min(start + CHUNK_SIZE, rawRows.length);
                                this.rows.push(...rawRows.slice(start, end));

                                let pct = (10.0 + ((end / rawRows.length) * 89.8)).toFixed(1);
                                this.uploadProgress = pct;
                                this.uploadStatusText = `${end} / ${rawRows.length} satır yüklendi (%${pct})...`;
                                chunkIdx++;

                                if (end < rawRows.length) {
                                    setTimeout(loadChunk, 10);
                                } else {
                                    this.updateRowMaps();
                                    this.calculateTotals();
                                    this.uploadProgress = '100';
                                    this.uploadStep = 3;
                                    this.uploadStatusText = 'Tamamlandı!';
                                    setTimeout(() => {
                                        this.showUploadModal = false;
                                        this.isLargeDataset = false;
                                    }, 400);
                                }
                            };
                            setTimeout(loadChunk, 20);
                        } else {
                            this.rows = rawRows;
                            this.updateRowMaps();
                            this.calculateTotals();
                        }
                    @elseif(old('rows_json') || old('rows'))
                        @php
                            $oldRowsData = old('rows_json') ? json_decode(old('rows_json'), true) : old('rows');
                            $formattedOldRows = [];
                            if (is_array($oldRowsData)) {
                                $rowCounter = 1;
                                foreach ($oldRowsData as $r) {
                                    $formattedOldRows[] = [
                                        'id' => $rowCounter++,
                                        'values' => (object) $r
                                    ];
                                }
                            }
                        @endphp
                        let rawRows = {!! json_encode($formattedOldRows) !!};
                        if (rawRows.length > 50) {
                            this.uploadModalTitle = 'Form Verileri Geri Yükleniyor';
                            this.uploadModalSubtitle = 'Daha önce girdiğiniz veriler optimize edilerek tabloya aktarılmaktadır.';
                            this.uploadStepNames = ['1. Hazırlık', '2. Tablo', '3. Tamamlandı'];
                            this.showUploadModal = true;
                            this.totalImportRows = rawRows.length;
                            this.isLargeDataset = true;
                            let estSec = Math.max(2, Math.ceil(rawRows.length * 0.016));
                            this.estimatedTimeText = estSec >= 60 ? `~${Math.ceil(estSec / 60)} dakika` : `~${estSec} saniye`;
                            this.uploadProgress = '10.0';
                            this.uploadStep = 2;
                            this.uploadStatusText = `${rawRows.length} satır tabloya yerleştiriliyor...`;

                            const CHUNK_SIZE = 35;
                            let chunkIdx = 0;
                            const loadChunk = () => {
                                let start = chunkIdx * CHUNK_SIZE;
                                let end = Math.min(start + CHUNK_SIZE, rawRows.length);
                                this.rows.push(...rawRows.slice(start, end));

                                let pct = (10.0 + ((end / rawRows.length) * 89.8)).toFixed(1);
                                this.uploadProgress = pct;
                                this.uploadStatusText = `${end} / ${rawRows.length} satır yüklendi (%${pct})...`;
                                chunkIdx++;

                                if (end < rawRows.length) {
                                    setTimeout(loadChunk, 10);
                                } else {
                                    this.updateRowMaps();
                                    this.calculateTotals();
                                    this.uploadProgress = '100';
                                    this.uploadStep = 3;
                                    this.uploadStatusText = 'Tamamlandı!';
                                    setTimeout(() => {
                                        this.showUploadModal = false;
                                        this.isLargeDataset = false;
                                    }, 400);
                                }
                            };
                            setTimeout(loadChunk, 20);
                        } else if (rawRows.length > 0) {
                            this.rows = rawRows;
                            this.updateRowMaps();
                            this.calculateTotals();
                        } else {
                            this.addRow();
                            this.calculateTotals();
                        }
                    @else
                        this.addRow();
                        this.calculateTotals();
                    @endif

                    if (this.partitionFieldId && this.partitionValue) {
                        this.rows.forEach(r => {
                            if (r && r.values) r.values[this.partitionFieldId] = this.partitionValue;
                        });
                    }
                },

                saveHistory() {
                    if (this.history.length >= 50) this.history.shift();
                    this.history.push(JSON.parse(JSON.stringify(this.rows)));
                },

                undo() {
                    if (this.history.length > 0) {
                        this.rows = this.history.pop();
                        this.updateRowMaps();
                        this.calculateTotals();
                        this.hasUnsavedChanges = true;
                        this.showToast('İşlem geri alındı. (Hafızada kalan: ' + this.history.length + ' işlem)');
                    }
                },

                createEmptyRowObject() {
                    const newRow = { id: Date.now() + Math.random().toString(36).substr(2, 9), values: {} };
                    this.fields.forEach(f => {
                        if (this.partitionFieldId && (f.id == this.partitionFieldId) && this.partitionValue) {
                            newRow.values[f.id] = this.partitionValue;
                        } else if (f.type === 'auto_user') {
                            newRow.values[f.id] = '{{ Auth::user()->name }}';
                        } else if ((f.type === 'user_list' || f.type === 'master_data') && f.filter_by_user_group && !this.currentUserScope.is_admin && this.currentUserScope.allowed_user_names && this.currentUserScope.allowed_user_names.length === 1) {
                            newRow.values[f.id] = this.currentUserScope.allowed_user_names[0];
                        } else if (f.type === 'year') {
                            newRow.values[f.id] = this.getDefaultYear(f);
                        } else {
                            newRow.values[f.id] = '';
                        }
                    });
                    return newRow;
                },

                addRow(count = 1) {
                    this.hasUnsavedChanges = true;
                    const numToAdd = Math.max(1, parseInt(count) || 1);
                    const newRows = [];
                    for (let i = 0; i < numToAdd; i++) {
                        newRows.push(this.createEmptyRowObject());
                    }
                    this.rows.push(...newRows);
                    this.updateRowMaps();

                    // Boş satır eklendiğinde genel toplamlar değişmez (boş değer 0'dır), bu yüzden formül döngüsünü çalıştırmaya gerek yoktur
                    if (this.$refs.tableContainer) {
                        requestAnimationFrame(() => {
                            if (this.$refs.tableContainer) {
                                this.$refs.tableContainer.scrollTop = this.$refs.tableContainer.scrollHeight;
                            }
                        });
                    }
                },

                toggleWide() {
                    this.isWide = !this.isWide;
                    this.$nextTick(() => {
                        this.updatePinnedOffsets();
                        this.updateCardHeight();
                    });
                },

                toggleFullscreen() {
                    this.isFullscreen = !this.isFullscreen;
                    if (this.isFullscreen) {
                        document.body.classList.add('overflow-hidden');
                        try {
                            if (document.documentElement.requestFullscreen && !document.fullscreenElement) {
                                document.documentElement.requestFullscreen().catch(() => {});
                            }
                        } catch(e) {}
                    } else {
                        document.body.classList.remove('overflow-hidden');
                        try {
                            if (document.exitFullscreen && document.fullscreenElement) {
                                document.exitFullscreen().catch(() => {});
                            }
                        } catch(e) {}
                    }
                    this.$nextTick(() => {
                        this.updatePinnedOffsets();
                        this.updateCardHeight();
                    });
                },

                handleEscapeKey() {
                    if (this.showConfirmModal) {
                        this.showConfirmModal = false;
                        return;
                    }
                    if (this.showFillConfirmModal) {
                        if (!this.isFillingColumn) this.showFillConfirmModal = false;
                        return;
                    }
                    if (this.showUploadModal) {
                        this.showUploadModal = false;
                        return;
                    }
                    if (this.isFullscreen) {
                        this.toggleFullscreen();
                    }
                },

                removeRow(rowOrIndex) {
                    this.promptDeleteRow(rowOrIndex);
                },

                promptDeleteRow(rowOrIndex) {
                    let index = this.getRowIndex(rowOrIndex);
                    if (index < 0 || index >= this.rows.length) return;
                    const row = this.rows[index];

                    // Satırdaki dolu alan sayısını hesapla
                    let filledCount = 0;
                    const rowValues = row.values || {};
                    for (const fId in rowValues) {
                        const val = rowValues[fId];
                        if (val !== undefined && val !== null && String(val).trim() !== '') {
                            filledCount++;
                        }
                    }

                    if (filledCount === 0) {
                        // Satır tamamen boşsa doğrudan sil
                        this.executeDeleteRow(index);
                        this.showToast('Boş satır silindi.');
                        return;
                    }

                    this.confirmModalData = {
                        type: 'row',
                        title: 'Satırı Sil',
                        subtitle: `Satır #${index + 1} için silme onayı`,
                        rowIndex: index,
                        rowNumber: index + 1,
                        filledCount: filledCount,
                        rowCount: this.rows.length,
                        message: `Bu satırda <strong>${filledCount} adet</strong> alanda girilmiş veri bulunmaktadır. Satırı silmek istediğinize emin misiniz?`,
                        confirmBtnText: 'Evet, Satırı Sil',
                        fieldId: null,
                        fieldName: ''
                    };
                    this.showConfirmModal = true;
                },

                executeDeleteRow(index) {
                    if (index >= 0 && index < this.rows.length) {
                        this.saveHistory();
                        this.hasUnsavedChanges = true;
                        this.rows.splice(index, 1);
                        if (this.rows.length === 0) {
                            this.addRow(1);
                        }
                        this.updateRowMaps();
                        this.calculateTotals();
                    }
                    this.showConfirmModal = false;
                },

                clearAll() {
                    this.promptClearAll();
                },

                promptClearAll() {
                    let totalFilled = 0;
                    for (let i = 0; i < this.rows.length; i++) {
                        const rowValues = this.rows[i].values || {};
                        for (const fId in rowValues) {
                            const val = rowValues[fId];
                            if (val !== undefined && val !== null && String(val).trim() !== '') {
                                totalFilled++;
                            }
                        }
                    }

                    this.confirmModalData = {
                        type: 'all',
                        title: 'Tüm Tabloyu Temizle',
                        subtitle: 'Tüm satır ve verilerin temizlenmesi',
                        fieldId: null,
                        fieldName: '',
                        filledCount: totalFilled,
                        rowCount: this.rows.length,
                        message: `Formdaki tüm satırlar silinecektir. Toplam <strong>${this.rows.length} satır</strong> ve <strong>${totalFilled} adet veri girişi</strong> temizlenecektir. Devam etmek istiyor musunuz?`,
                        confirmBtnText: 'Evet, Tümünü Temizle',
                        rowIndex: null,
                        rowNumber: null
                    };
                    this.showConfirmModal = true;
                },

                executeClearAll() {
                    this.saveHistory();
                    this.hasUnsavedChanges = true;
                    this.rows = [];
                    this.addRow(1);
                    this.calculateTotals();
                    this.showConfirmModal = false;
                    this.showToast('✓ Tüm satırlar temizlendi ve yeni bir satır açıldı.');
                },

                executeConfirm() {
                    if (this.confirmModalData.type === 'column') {
                        this.executeClearColumn(this.confirmModalData.fieldId);
                    } else if (this.confirmModalData.type === 'row') {
                        this.executeDeleteRow(this.confirmModalData.rowIndex);
                    } else if (this.confirmModalData.type === 'all') {
                        this.executeClearAll();
                    }
                },

                moveRowUp(rowOrIndex) {
                    let index = this.getRowIndex(rowOrIndex);
                    if (index > 0) {
                        this.saveHistory();
                        const row = this.rows.splice(index, 1)[0];
                        this.rows.splice(index - 1, 0, row);
                        this.updateRowMaps();
                    }
                },

                moveRowDown(rowOrIndex) {
                    let index = this.getRowIndex(rowOrIndex);
                    if (index < this.rows.length - 1) {
                        this.saveHistory();
                        const row = this.rows.splice(index, 1)[0];
                        this.rows.splice(index + 1, 0, row);
                        this.updateRowMaps();
                    }
                },

                moveRowTo(rowOrIndex, targetValue) {
                    let currentIndex = this.getRowIndex(rowOrIndex);
                    let targetIndex = parseInt(targetValue) - 1;
                    if (isNaN(targetIndex)) {
                        if (event && event.target) event.target.value = currentIndex + 1;
                        return;
                    }
                    if (targetIndex < 0) targetIndex = 0;
                    if (targetIndex >= this.rows.length) targetIndex = this.rows.length - 1;
                    if (targetIndex === currentIndex) {
                        if (event && event.target) event.target.value = currentIndex + 1;
                        return;
                    }
                    this.saveHistory();
                    const row = this.rows.splice(currentIndex, 1)[0];
                    this.rows.splice(targetIndex, 0, row);
                    this.updateRowMaps();
                },

                hasRealUserData() {
                    if (!this.rows || this.rows.length === 0) return false;
                    for (let r of this.rows) {
                        if (!r.values) continue;
                        for (let f of this.fields) {
                            if (f.type === 'auto_user') continue;
                            if (this.partitionFieldId && f.id == this.partitionFieldId) continue;
                            if (f.type === 'year') continue;
                            const v = r.values[f.id];
                            if (v !== null && v !== undefined && String(v).trim() !== '') {
                                return true;
                            }
                        }
                    }
                    return false;
                },

                submitForm(actionType) {
                    if (this.isSubmitting) return;

                    // Kullanıcı hiçbir gerçek veri girmemişse (sadece boş şablon satırları varsa) engelle
                    if (!this.hasRealUserData()) {
                        this.isSubmitting = false;
                        this.submittingAction = null;
                        this.showToast('Lütfen kaydetmeden veya onaya göndermeden önce en az bir satır veri giriniz.', 'warning');
                        return;
                    }

                    // 1. Onaya Gönderiliyorsa (action === 'submit') zorunlu alan kontrolü yap:
                    if (actionType === 'submit') {
                        let firstMissing = null;
                        for (let rIdx = 0; rIdx < this.rows.length; rIdx++) {
                            const row = this.rows[rIdx];
                            const rowNum = rIdx + 1;

                            // Satır tamamen boşsa ve tabloda başka satırlar varsa bu satırı atla
                            const hasData = Object.values(row.values || {}).some(v => v !== null && v !== undefined && String(v).trim() !== '');
                            if (!hasData && this.rows.length > 1) {
                                continue;
                            }

                            for (let fIdx = 0; fIdx < this.fields.length; fIdx++) {
                                const field = this.fields[fIdx];

                                // Hesaplanmış alan ise kullanıcı dolduramaz, atla
                                if (this.isFieldCalculated(field, row)) {
                                    continue;
                                }

                                // Bu alan bu satır için zorunlu mu?
                                if (this.isFieldRequired(field, row)) {
                                    const val = row.values ? row.values[field.id] : null;
                                    const isBlank = val === null || val === undefined || String(val).trim() === '';

                                    if (isBlank) {
                                        firstMissing = {
                                            rIndex: rIdx,
                                            rowNum: rowNum,
                                            label: field.label || field.name,
                                            fieldId: field.id
                                        };
                                        break; // İlk eksiği bulduk
                                    }
                                }
                            }

                            if (firstMissing) break; // İlk eksik satırı bulunca döngüden çık
                        }

                        if (firstMissing) {
                            this.isSubmitting = false;
                            this.submittingAction = null;

                            // Eğer aktif filtre varsa ve hedef satır gizlenmiş olabilirse, filtreyi temizle
                            let hasActiveFilters = false;
                            for (const fId in this.filters) {
                                if (this.filters[fId] && this.filters[fId].length > 0) {
                                    hasActiveFilters = true;
                                    break;
                                }
                            }
                            if (hasActiveFilters) {
                                this.filters = {};
                            }

                            this.$nextTick(() => {
                                const targetId = `cell_${firstMissing.rIndex}_${firstMissing.fieldId}`;
                                const targetEl = document.getElementById(targetId);

                                if (targetEl) {
                                    // İlgili alana yumuşak şekilde kaydır
                                    targetEl.scrollIntoView({ behavior: 'smooth', block: 'center', inline: 'center' });

                                    setTimeout(() => {
                                        targetEl.focus();

                                        const isSelect = targetEl.tagName === 'SELECT';
                                        const validationMsg = isSelect
                                            ? `Lütfen bu alanı seçiniz (${firstMissing.label})`
                                            : `Lütfen bu alanı doldurunuz (${firstMissing.label})`;

                                        // Tarayıcının yerel doğrulama baloncuğunu göster
                                        targetEl.setCustomValidity(validationMsg);
                                        targetEl.reportValidity();

                                        // Görsel dikkat çekici kırmızı ring ve sınır efekti
                                        targetEl.classList.add('ring-2', 'ring-red-500', 'border-red-500');

                                        const clearValidation = () => {
                                            targetEl.setCustomValidity('');
                                            targetEl.classList.remove('ring-2', 'ring-red-500', 'border-red-500');
                                            targetEl.removeEventListener('input', clearValidation);
                                            targetEl.removeEventListener('change', clearValidation);
                                            targetEl.removeEventListener('blur', clearValidation);
                                        };

                                        targetEl.addEventListener('input', clearValidation);
                                        targetEl.addEventListener('change', clearValidation);
                                        targetEl.addEventListener('blur', clearValidation);
                                    }, 200);
                                }
                            });

                            return;
                        }
                    }

                    // 2. Taslak veya Onay için formu gönder
                    this.isSubmitting = true;
                    this.submittingAction = actionType;

                    // Alpine'daki tüm satırların değerler objesini JSON olarak hazırla
                    const rowsData = this.rows.map(r => r.values);

                    const actionInput = document.getElementById('form_action_input');
                    const rowsJsonInput = document.getElementById('form_rows_json_input');
                    const entryIdInput = document.getElementById('form_entry_id_input');
                    const form = document.getElementById('entry-form');

                    if (actionInput) actionInput.value = actionType;
                    if (rowsJsonInput) rowsJsonInput.value = JSON.stringify(rowsData);
                    if (entryIdInput && this.entryId) entryIdInput.value = this.entryId;

                    // Eğer önceden otomatik kayıtla bir taslak oluştuysa, formun action ve methodunu PUT olarak güncelle
                    if (this.entryId && this.saveUrl && form) {
                        form.action = this.saveUrl;
                        let methodInput = form.querySelector('input[name="_method"]');
                        if (!methodInput) {
                            methodInput = document.createElement('input');
                            methodInput.type = 'hidden';
                            methodInput.name = '_method';
                            methodInput.value = 'PUT';
                            form.appendChild(methodInput);
                        }
                    }

                    if (form) {
                        HTMLFormElement.prototype.submit.call(form);
                    }
                },

                async saveDraftAjax(isSilent = true, callback = null) {
                    if (this.isAutoSaving || this.isSubmitting) {
                        if (typeof callback === 'function') callback(null, { skipped: true });
                        return;
                    }
                    if (this.isUploading) {
                        if (typeof callback === 'function') callback(null, { skipped: true });
                        return;
                    }
                    if (!this.rows || this.rows.length === 0) {
                        if (typeof callback === 'function') callback(null, { skipped: true });
                        return;
                    }

                    // Tabloda en az bir hücrede kullanıcının girdiği gerçek veri var mı kontrol et (kilitli bölüm ve otomatik alanlar hariç)
                    if (!this.hasRealUserData()) {
                        this.isAutoSaving = false;
                        if (!isSilent) {
                            this.showToast('Lütfen kaydetmeden önce en az bir satır veri giriniz.', 'warning');
                        }
                        if (typeof callback === 'function') callback(null, { skipped: true });
                        return;
                    }

                    this.isAutoSaving = true;
                    this.autoSaveStatus = 'saving';

                    try {
                        const rowsData = this.rows.map(r => r.values);
                        const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
                        const payload = {
                            entry_date: '{{ $date }}',
                            action: 'draft',
                            rows_json: JSON.stringify(rowsData),
                            partition_value: this.partitionValue || '',
                            _token: token
                        };

                        const headers = {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-CSRF-TOKEN': token
                        };

                        if (this.isEditMode && this.entryId) {
                            payload._method = 'PUT';
                            headers['X-HTTP-METHOD-OVERRIDE'] = 'PUT';
                        }

                        const response = await fetch(this.saveUrl, {
                            method: 'POST',
                            headers: headers,
                            body: JSON.stringify(payload)
                        });

                        const data = await response.json();

                        if (response.ok && data.success) {
                            this.autoSaveStatus = 'saved';
                            this.autoSaveLastTime = data.saved_at || new Date().toLocaleTimeString('tr-TR', { hour: '2-digit', minute: '2-digit' });
                            this.hasUnsavedChanges = false;

                            // Eğer create modundaysak sunucudan gelen entry_id ile edit moduna geç ve URL'yi güncelle
                            if (!this.isEditMode && data.entry_id) {
                                this.entryId = data.entry_id;
                                this.isEditMode = true;
                                if (data.update_url) this.saveUrl = data.update_url;
                                if (data.edit_url) {
                                    window.history.replaceState({ entry_id: data.entry_id }, '', data.edit_url);
                                }

                                // Form DOM elemanını da güncelle (Böylece kullanıcı Onaya Gönder'e bastığında yeni kopya değil, bu taslak güncellenir)
                                const formEl = document.getElementById('entry-form');
                                if (formEl) {
                                    if (data.update_url) formEl.action = data.update_url;
                                    let methodInput = formEl.querySelector('input[name="_method"]');
                                    if (!methodInput) {
                                        methodInput = document.createElement('input');
                                        methodInput.type = 'hidden';
                                        methodInput.name = '_method';
                                        methodInput.value = 'PUT';
                                        formEl.appendChild(methodInput);
                                    }
                                }
                                const idInput = document.getElementById('form_entry_id_input');
                                if (idInput) idInput.value = data.entry_id;
                            }

                            if (!isSilent) {
                                this.showToast('✓ ' + (data.message || 'Taslak başarıyla kaydedildi.'));
                            }
                            if (typeof callback === 'function') callback(null, data);
                        } else {
                            this.autoSaveStatus = 'error';
                            if (!isSilent) {
                                this.showToast('⚠️ Taslak kaydedilemedi: ' + (data.message || 'Bilinmeyen hata'), 'error');
                            }
                            if (typeof callback === 'function') callback(new Error(data.message || 'Hata'));
                        }
                    } catch (err) {
                        console.error('AutoSave draft error:', err);
                        this.autoSaveStatus = 'error';
                        if (typeof callback === 'function') callback(err);
                    } finally {
                        this.isAutoSaving = false;
                        setTimeout(() => {
                            if (this.autoSaveStatus === 'saved' && !this.hasUnsavedChanges) {
                                this.autoSaveStatus = 'idle';
                            }
                        }, 5000);
                    }
                },

                isFieldRequired(field, rowOrIndex) {
                    if (field.type === 'master_data') {
                        let status = this.getMasterDataStatus(rowOrIndex, field);
                        if (status.isDisabled) {
                            return false;
                        }
                    }

                    if (field.is_required) return true;
                    if (field.condition_field_id && field.condition_value) {
                        if (!field._parsedConditions) {
                            field._parsedConditions = field.condition_value.split(',').map(s => s.trim().toLowerCase());
                        }
                        let row = this.getRow(rowOrIndex);
                        let targetVal = row && row.values ? row.values[field.condition_field_id] : null;
                        if (targetVal) {
                            return field._parsedConditions.includes(String(targetVal).toLowerCase());
                        }
                    }
                    return false;
                },

                isFieldCalculated(field, rowOrIndex) {
                    if (!field.is_calculated) return false;
                    if (field.calc_condition_field_id && field.calc_condition_value) {
                        if (!field._parsedCalcConditions) {
                            field._parsedCalcConditions = field.calc_condition_value.split(',').map(s => s.trim().toLowerCase());
                        }
                        let row = this.getRow(rowOrIndex);
                        let val = row && row.values ? row.values[field.calc_condition_field_id] : null;
                        if (val) {
                            return field._parsedCalcConditions.includes(String(val).toLowerCase());
                        }
                        return false;
                    }
                    return true;
                },

                getMasterDataOptions(rowOrIndex, field) {
                    if (!field.master_data_type_id) return [];
                    let typeId = field.master_data_type_id;
                    let typeObj = this.masterDataTree[typeId];
                    if (!typeObj) return [];

                    let row = this.getRow(rowOrIndex);
                    let parentKey = '';

                    // Cascading kontrolü: Bu tipin parent'ı var mı?
                    if (typeObj.parent_id) {
                        let parentField = this.fields.find(f => f.type === 'master_data' && f.master_data_type_id == typeObj.parent_id);
                        if (parentField) {
                            parentKey = row && row.values ? (row.values[parentField.id] || '') : '';
                            if (!parentKey) return [];
                        }
                    }

                    if (!this.cachedMasterData) this.cachedMasterData = {};
                    let cacheKey = field.id + '_' + parentKey;
                    if (this.cachedMasterData[cacheKey]) return this.cachedMasterData[cacheKey];

                    let items = typeObj.items || [];

                    if (typeObj.parent_id && parentKey) {
                        let parentTypeObj = this.masterDataTree[typeObj.parent_id];
                        let selectedParentItem = parentTypeObj && parentTypeObj.items ? parentTypeObj.items.find(i => i.name === parentKey) : null;

                        if (selectedParentItem) {
                            items = items.filter(i => i.parent_id == selectedParentItem.id);
                        } else {
                            this.cachedMasterData[cacheKey] = [];
                            return [];
                        }
                    }

                    // Kullanıcı / Grup Yetkisine Göre Filtreleme:
                    if (field.filter_by_user_group && !this.currentUserScope.is_admin) {
                        let allowedNames = (this.currentUserScope.allowed_user_names || []).map(n => (n || '').toLowerCase().trim());
                        let allowedIds = this.currentUserScope.allowed_user_ids || [];

                        items = items.filter(i => {
                            // 1. user_id eşleşmesi
                            if (i.user_id && allowedIds.includes(i.user_id)) {
                                return true;
                            }
                            // 2. İsim eşleşmesi (büyük/küçük harf duyarsız)
                            let itemName = (i.name || '').toLowerCase().trim();
                            return allowedNames.some(allowedName => itemName === allowedName || itemName.includes(allowedName) || allowedName.includes(itemName));
                        });
                    }

                    this.cachedMasterData[cacheKey] = items;
                    return items;
                },

                getMasterDataStatus(rowOrIndex, field) {
                    if (!field.master_data_type_id) {
                        return { hasParent: false, isParentSelected: false, parentLabel: '', optionsCount: 0, isDisabled: false, placeholder: 'Seçiniz...' };
                    }
                    let typeId = field.master_data_type_id;
                    let typeObj = this.masterDataTree ? this.masterDataTree[typeId] : null;
                    if (!typeObj) {
                        return { hasParent: false, isParentSelected: false, parentLabel: '', optionsCount: 0, isDisabled: true, placeholder: '-- Tanımlı Veri Yok --' };
                    }

                    let hasParent = !!typeObj.parent_id;
                    let isParentSelected = false;
                    let parentLabel = '';
                    let row = this.getRow(rowOrIndex);

                    if (hasParent) {
                        let parentField = this.fields.find(f => f.type === 'master_data' && f.master_data_type_id == typeObj.parent_id);
                        if (parentField) {
                            parentLabel = parentField.label || 'Üst Kategori';
                            let selectedParentName = row && row.values ? row.values[parentField.id] : '';
                            if (selectedParentName && String(selectedParentName).trim() !== '') {
                                isParentSelected = true;
                            }
                        } else {
                            hasParent = false;
                        }
                    }

                    let isDisabled = false;
                    let placeholder = 'Seçiniz...';

                    if (hasParent && !isParentSelected) {
                        isDisabled = true;
                        placeholder = '-- Önce ' + parentLabel + ' Seçiniz --';
                    } else {
                        let options = this.getMasterDataOptions(rowOrIndex, field);
                        let optionsCount = options ? options.length : 0;
                        if (optionsCount === 0) {
                            isDisabled = true;
                            placeholder = '-- Tanımlı Veri Yok --';
                        }
                    }

                    return {
                        hasParent: hasParent,
                        isParentSelected: isParentSelected,
                        parentLabel: parentLabel,
                        isDisabled: isDisabled,
                        placeholder: placeholder
                    };
                },

                isMasterDataDisabled(rowOrIndex, field) {
                    return this.getMasterDataStatus(rowOrIndex, field).isDisabled;
                },

                getMasterDataPlaceholder(rowOrIndex, field) {
                    return this.getMasterDataStatus(rowOrIndex, field).placeholder;
                },

                getUserListOptions(rowOrIndex, field) {
                    if (!this.cachedUserLists) this.cachedUserLists = {};
                    if (this.cachedUserLists[field.id]) return this.cachedUserLists[field.id];

                    let list = this.usersList || [];

                    // 1. Listeden muaf (hariç) tutulan kullanıcıları filtrele
                    let excludedIds = [];
                    if (field.options) {
                        try {
                            let opts = typeof field.options === 'string' ? JSON.parse(field.options) : field.options;
                            if (opts && opts.excluded_user_ids && Array.isArray(opts.excluded_user_ids)) {
                                excludedIds = opts.excluded_user_ids.map(id => parseInt(id));
                            }
                        } catch(e) {}
                    }
                    if (excludedIds.length > 0) {
                        list = list.filter(u => !excludedIds.includes(parseInt(u.id)));
                    }

                    // 2. Kullanıcı / Grup Yetkisine Göre Filtreleme:
                    if (field.filter_by_user_group && !this.currentUserScope.is_admin) {
                        let allowedIds = (this.currentUserScope.allowed_user_ids || []).map(id => parseInt(id));
                        let allowedNames = (this.currentUserScope.allowed_user_names || []).map(n => this.toLocaleLower(n));

                        list = list.filter(u => {
                            if (u.id && allowedIds.includes(parseInt(u.id))) {
                                return true;
                            }
                            let uName = this.toLocaleLower(u.name);
                            return allowedNames.some(a => uName === a || uName.includes(a) || a.includes(uName));
                        });
                    }

                    this.cachedUserLists[field.id] = list;
                    return list;
                },

                getYearOptions(field) {
                    const currentYear = new Date().getFullYear();
                    let opts = null;
                    if (field.options) {
                        try {
                            opts = typeof field.options === 'string' ? JSON.parse(field.options) : field.options;
                        } catch(e) {}
                    }
                    opts = opts || {};

                    let rawYears = [];
                    if (opts.year_mode === 'custom' && opts.year_custom_list) {
                        rawYears = opts.year_custom_list.split(',').map(y => parseInt(y.trim())).filter(y => !isNaN(y));
                    } else {
                        const past = parseInt(opts.year_past !== undefined ? opts.year_past : 1);
                        const future = parseInt(opts.year_future !== undefined ? opts.year_future : 2);
                        for (let y = currentYear - past; y <= currentYear + future; y++) {
                            rawYears.push(y);
                        }
                    }

                    // Sırala (Küçükten büyüğe)
                    rawYears = [...new Set(rawYears)].sort((a, b) => a - b);

                    return rawYears.map(y => {
                        let label = String(y);
                        if (y === currentYear + 1) {
                            label += ' (Hedef Yılı)';
                        } else if (y === currentYear) {
                            label += ' (Bu Yıl)';
                        }
                        return { value: String(y), label: label };
                    });
                },

                getDefaultYear(field) {
                    const currentYear = new Date().getFullYear();
                    let opts = null;
                    if (field.options) {
                        try {
                            opts = typeof field.options === 'string' ? JSON.parse(field.options) : field.options;
                        } catch(e) {}
                    }
                    opts = opts || {};

                    if (opts.year_default === 'empty') {
                        return '';
                    } else if (opts.year_default === 'current') {
                        return String(currentYear);
                    } else {
                        // Varsayılan: future_1 (Hedef Yılı: Mevcut Yıl + 1)
                        return String(currentYear + 1);
                    }
                },

                handleMasterDataChange(rowOrIndex, fieldId) {
                    let changedField = this.fields.find(f => f.id == fieldId);
                    if (changedField && changedField.type === 'master_data') {
                        let typeId = changedField.master_data_type_id;
                        let childFields = this.fields.filter(f => f.type === 'master_data' && this.masterDataTree[f.master_data_type_id] && this.masterDataTree[f.master_data_type_id].parent_id == typeId);

                        let row = this.getRow(rowOrIndex);
                        childFields.forEach(cf => {
                            if (row && row.values) {
                                row.values[cf.id] = '';
                                this.handleMasterDataChange(row, cf.id);
                            }
                        });
                    }
                },

                clearSelect(rowOrIndex, fieldId, field) {
                    let row = this.getRow(rowOrIndex);
                    if (!row || !row.values) return;

                    row.values[fieldId] = '';
                    this.hasUnsavedChanges = true;

                    if (field && field.type === 'master_data') {
                        this.handleMasterDataChange(row, fieldId);
                    }

                    this.calculateTotals();
                },

                calculateTotals() {
                    // 1. Önce formülleri hesapla (Sadece formül içeren alan varsa çalıştır!)
                    const calculatedFields = this.fields.filter(f => f.is_calculated && f.calculation_formula);
                    if (calculatedFields.length > 0) {
                        for (let pass = 0; pass < 2; pass++) {
                            for (let rIndex = 0; rIndex < this.rows.length; rIndex++) {
                                const r = this.rows[rIndex];
                                for (let fIndex = 0; fIndex < calculatedFields.length; fIndex++) {
                                    const f = calculatedFields[fIndex];
                                    if (this.isFieldCalculated(f, r)) {
                                        let formula = f.calculation_formula;
                                        let canCalculate = true;

                                        for (let oIndex = 0; oIndex < this.fields.length; oIndex++) {
                                            const otherField = this.fields[oIndex];
                                            let placeholder = '#{' + otherField.id + '}#';
                                            if (formula.includes(placeholder)) {
                                                let val = r.values[otherField.id];
                                                if (val === '' || val === null || val === undefined) {
                                                    canCalculate = false;
                                                    break;
                                                } else {
                                                    let cleanVal = this.parseRawNumber(val);
                                                    let numVal = parseFloat(cleanVal);
                                                    if (isNaN(numVal)) {
                                                        canCalculate = false;
                                                        break;
                                                    } else {
                                                        formula = formula.replaceAll(placeholder, numVal);
                                                    }
                                                }
                                            }
                                        }

                                        if (canCalculate) {
                                            try {
                                                let sanitized = formula.replace(/[^0-9+\-*\/().\s]/g, '');
                                                let res = Function('"use strict";return (' + sanitized + ')')();
                                                if (!isNaN(res) && isFinite(res)) {
                                                    r.values[f.id] = Number.isInteger(res) ? res : parseFloat(res.toFixed(2));
                                                } else {
                                                    r.values[f.id] = '';
                                                }
                                            } catch (e) {
                                                r.values[f.id] = '';
                                            }
                                        } else {
                                            // Formül aktif ancak bağımlı alanlar henüz doldurulmamışsa hücreyi sıfırla/boşalt
                                            r.values[f.id] = '';
                                        }
                                    }
                                }
                            }
                        }
                    }

                    // 2. Alt toplamları hesapla
                    if (!this.showTotals) return;

                    const summableFields = this.fields.filter(f => f.is_summable && f.type === 'number');
                    if (summableFields.length === 0) return;

                    let newTotals = {};
                    let newFilteredTotals = {};

                    summableFields.forEach(f => {
                        newTotals[f.id] = 0;
                        newFilteredTotals[f.id] = 0;
                    });

                    // Genel Toplam (Tüm satırlar)
                    for (let i = 0; i < this.rows.length; i++) {
                        const r = this.rows[i];
                        for (let j = 0; j < summableFields.length; j++) {
                            const f = summableFields[j];
                            let raw = r.values[f.id];
                            if (raw !== undefined && raw !== null && raw !== '') {
                                let val = parseFloat(this.parseRawNumber(raw));
                                if (!isNaN(val)) {
                                    newTotals[f.id] = (parseFloat(newTotals[f.id] || 0) + val).toFixed(2);
                                }
                            }
                        }
                    }

                    // Filtreli Toplam (Aktif filtre var mı kontrol et)
                    const hasActiveFilters = Object.values(this.filters).some(arr => arr && arr.length > 0);
                    if (hasActiveFilters) {
                        let vRows = this.visibleRows();
                        for (let i = 0; i < vRows.length; i++) {
                            const r = vRows[i];
                            for (let j = 0; j < summableFields.length; j++) {
                                const f = summableFields[j];
                                let raw = r.values[f.id];
                                if (raw !== undefined && raw !== null && raw !== '') {
                                    let val = parseFloat(this.parseRawNumber(raw));
                                    if (!isNaN(val)) {
                                        newFilteredTotals[f.id] = (parseFloat(newFilteredTotals[f.id] || 0) + val).toFixed(2);
                                    }
                                }
                            }
                        }
                    } else {
                        newFilteredTotals = { ...newTotals };
                    }

                    summableFields.forEach(f => {
                        newTotals[f.id] = parseFloat(newTotals[f.id] || 0).toFixed(2);
                        newFilteredTotals[f.id] = parseFloat(newFilteredTotals[f.id] || 0).toFixed(2);
                    });

                    this.totals = newTotals;
                    this.filteredTotals = newFilteredTotals;
                },

                combineAndRound(isNegative, intPart, decPart) {
                    if (!decPart) return (isNegative ? '-' : '') + (intPart || '0');
                    if (decPart.length > 2) {
                        let num = parseFloat(intPart + '.' + decPart);
                        if (!isNaN(num)) {
                            let rounded = Math.round(num * 100) / 100;
                            let parts = rounded.toFixed(2).split('.');
                            return (isNegative ? '-' : '') + parts[0] + '.' + parts[1];
                        }
                    }
                    return (isNegative ? '-' : '') + (intPart || '0') + '.' + decPart;
                },

                parseRawNumber(val) {
                    if (val === undefined || val === null || val === '') return '';
                    let str = String(val).trim();
                    if (str === '' || str === '-') return '';

                    let isNegative = str.startsWith('-');
                    if (isNegative) str = str.substring(1).trim();

                    // 1. Hem virgül hem nokta varsa (Örn: 4.582.478,846985 veya 1,250.50)
                    if (str.includes(',') && str.includes('.')) {
                        let lastComma = str.lastIndexOf(',');
                        let lastDot = str.lastIndexOf('.');
                        if (lastComma > lastDot) {
                            // Türkçe format: nokta binlik, virgül ondalık
                            let intPart = str.substring(0, lastComma).replace(/\D/g, '') || '0';
                            let decPart = str.substring(lastComma + 1).replace(/\D/g, '');
                            return this.combineAndRound(isNegative, intPart, decPart);
                        } else {
                            // US format: virgül binlik, nokta ondalık
                            let intPart = str.substring(0, lastDot).replace(/\D/g, '') || '0';
                            let decPart = str.substring(lastDot + 1).replace(/\D/g, '');
                            return this.combineAndRound(isNegative, intPart, decPart);
                        }
                    }

                    // 2. Sadece virgül varsa (Örn: 1257870,685 veya 4582,5) -> Virgül KESİNLİKLE küsürattır!
                    if (str.includes(',')) {
                        let parts = str.split(',');
                        let intPart = parts[0].replace(/\D/g, '') || '0';
                        let decPart = parts.slice(1).join('').replace(/\D/g, '');
                        return this.combineAndRound(isNegative, intPart, decPart);
                    }

                    // 3. Sadece nokta varsa
                    if (str.includes('.')) {
                        let parts = str.split('.');
                        // Çoklu nokta (Örn: 4.582.478 veya 1.000.000) -> KESİNLİKLE BİNLİK AYIRICIDIR (TAM SAYI)!
                        if (parts.length > 2) {
                            let clean = str.replace(/\D/g, '');
                            return clean ? ((isNegative ? '-' : '') + clean) : '';
                        }
                        // Tek nokta ve tam 3 basamak (Örn: 4.582 veya 1.250) -> BİNLİK AYIRICI (TAM SAYI)!
                        if (parts[1].length === 3 && parts[0].length >= 1 && parts[0].length <= 3) {
                            let clean = parts[0].replace(/\D/g, '') + parts[1].replace(/\D/g, '');
                            return clean ? ((isNegative ? '-' : '') + clean) : '';
                        }
                        // Ham float (Örn: 622641.51 veya 336734.69 veya 16.5) -> ONDALIK NOKTASI
                        let intPart = parts[0].replace(/\D/g, '') || '0';
                        let decPart = parts[1].replace(/\D/g, '');
                        return this.combineAndRound(isNegative, intPart, decPart);
                    }

                    // 4. Nokta veya virgül yoksa (Düz tam sayı: 4582478 veya 4500000)
                    let clean = str.replace(/\D/g, '');
                    return clean ? ((isNegative ? '-' : '') + clean) : '';
                },

                formatInputDisplay(val) {
                    if (val === undefined || val === null || val === '') return '';
                    let str = String(val).trim();
                    if (str === '' || str === '-') return str;

                    let isNegative = str.startsWith('-');
                    if (isNegative) str = str.substring(1);

                    let intPart = '';
                    let decPart = null;

                    if (str.includes(',')) {
                        let parts = str.split(',');
                        intPart = parts[0].replace(/\D/g, '') || '0';
                        decPart = parts.length > 1 ? parts.slice(1).join('').replace(/\D/g, '') : '';
                    } else if (str.includes('.')) {
                        let parts = str.split('.');
                        if (parts.length > 2) {
                            intPart = str.replace(/\D/g, '') || '0';
                            decPart = null;
                        } else if (parts[1].length === 3 && parts[0].length >= 1 && parts[0].length <= 3 && !isNaN(parts[0])) {
                            intPart = parts[0].replace(/\D/g, '') + parts[1].replace(/\D/g, '');
                            decPart = null;
                        } else {
                            intPart = parts[0].replace(/\D/g, '') || '0';
                            decPart = parts[1].replace(/\D/g, '');
                        }
                    } else {
                        intPart = str.replace(/\D/g, '') || '0';
                        decPart = null;
                    }

                    if (intPart.length > 1 && intPart.startsWith('0')) {
                        intPart = intPart.replace(/^0+/, '') || '0';
                    }

                    let formattedInt = intPart.replace(/\B(?=(\d{3})+(?!\d))/g, ".");
                    let res = (isNegative ? '-' : '') + formattedInt;

                    if (decPart !== null) {
                        if (decPart.length > 2) {
                            let num = parseFloat(intPart + '.' + decPart);
                            if (!isNaN(num)) {
                                let rounded = Math.round(num * 100) / 100;
                                let rParts = rounded.toFixed(2).split('.');
                                let rInt = rParts[0].replace(/\B(?=(\d{3})+(?!\d))/g, ".");
                                return (isNegative ? '-' : '') + rInt + ',' + rParts[1];
                            }
                        }
                        res += ',' + decPart;
                    } else if (String(val).endsWith(',')) {
                        res += ',';
                    }

                    return res;
                },

                handleNumberInput(e, rowOrIndex, fieldId) {
                    let input = e.target;
                    let raw = input.value;
                    let row = this.getRow(rowOrIndex);
                    this.hasUnsavedChanges = true;

                    if (!raw || !raw.trim()) {
                        if (row && row.values) row.values[fieldId] = '';
                        this.calculateTotals();
                        return;
                    }

                    // Numpad nokta tuşuna basıldığında ve henüz virgül yoksa virgüle dönüştür
                    if (raw.endsWith('.') && !raw.slice(0, -1).includes(',')) {
                        raw = raw.slice(0, -1) + ',';
                    }

                    let isNegative = raw.trim().startsWith('-');
                    let hasComma = raw.includes(',');
                    let intDigits = '';
                    let decDigits = null;

                    if (hasComma) {
                        let parts = raw.split(',');
                        intDigits = parts[0].replace(/\D/g, '');
                        decDigits = parts.slice(1).join('').replace(/\D/g, '');
                    } else {
                        // Virgül yoksa TÜM RAKAMLAR TAM SAYIDIR (noktalar binlik ayracıdır)
                        intDigits = raw.replace(/\D/g, '');
                        decDigits = null;
                    }

                    // Küsürat 2 basamaktan fazlaysa (yapıştırma durumunda) 3. basamağa göre yuvarla
                    if (decDigits && decDigits.length > 2) {
                        let rounded = this.combineAndRound(isNegative, intDigits, decDigits);
                        let rParts = rounded.split('.');
                        intDigits = rParts[0].replace(/\D/g, '');
                        decDigits = rParts.length > 1 ? rParts[1] : '';
                    }

                    if (intDigits.length > 1 && intDigits.startsWith('0')) {
                        intDigits = intDigits.replace(/^0+/, '') || '0';
                    }

                    let formattedInt = intDigits.replace(/\B(?=(\d{3})+(?!\d))/g, ".");
                    let formattedDisplay = (isNegative ? '-' : '') + formattedInt + (hasComma ? ',' + (decDigits !== null ? decDigits : '') : '');

                    // Model değerini güncelle (tam sayı ise tamsayı, ondalık varsa noktalı ondalık veya devam eden virgül)
                    let cleanStored = (isNegative ? '-' : '') + (intDigits || '') + (hasComma ? (decDigits !== null && decDigits !== '' ? '.' + decDigits : ',') : '');
                    if (row && row.values) row.values[fieldId] = cleanStored;

                    // İmleç pozisyonunu koru
                    let cursorPos = input.selectionStart || 0;
                    let rawBeforeCursor = raw.substring(0, cursorPos);
                    let digitsBeforeCursor = rawBeforeCursor.replace(/\D/g, '').length;
                    let cursorWasAfterComma = hasComma && cursorPos > raw.indexOf(',');
                    let decDigitsBeforeCursor = cursorWasAfterComma 
                        ? rawBeforeCursor.substring(raw.indexOf(',') + 1).replace(/\D/g, '').length 
                        : 0;

                    input.value = formattedDisplay;

                    let newPos = formattedDisplay.length;
                    if (cursorWasAfterComma) {
                        let commaIdx = formattedDisplay.indexOf(',');
                        if (commaIdx !== -1) {
                            newPos = commaIdx + 1 + decDigitsBeforeCursor;
                        }
                    } else {
                        let digitCount = 0;
                        for (let i = 0; i < formattedDisplay.length; i++) {
                            if (formattedDisplay[i] === ',') {
                                newPos = i;
                                break;
                            }
                            if (/\d/.test(formattedDisplay[i])) {
                                digitCount++;
                                if (digitCount === digitsBeforeCursor) {
                                    newPos = i + 1;
                                    break;
                                }
                            }
                        }
                        if (digitsBeforeCursor === 0) {
                            newPos = (isNegative && formattedDisplay.startsWith('-')) ? 1 : 0;
                        }
                    }

                    if (newPos > formattedDisplay.length) newPos = formattedDisplay.length;
                    input.setSelectionRange(newPos, newPos);

                    this.calculateTotals();
                },

                handleNumberBlur(e, rowOrIndex, fieldId) {
                    let input = e.target;
                    let raw = input.value;
                    let row = this.getRow(rowOrIndex);
                    if (!raw || !raw.trim()) {
                        if (row && row.values) row.values[fieldId] = '';
                        input.value = '';
                        this.calculateTotals();
                        return;
                    }

                    let clean = this.parseRawNumber(raw);
                    if (row && row.values) row.values[fieldId] = clean;
                    input.value = this.formatInputDisplay(clean);
                    this.calculateTotals();
                },

                formatTotalDisplay(val) {
                    if (val === undefined || val === null || val === '') return '0,00';
                    let clean = this.parseRawNumber(val);
                    let num = parseFloat(clean);
                    if (isNaN(num)) return '0,00';
                    return num.toLocaleString('tr-TR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                },

                toLocaleLower(str) {
                    return str ? String(str).toLocaleLowerCase('tr-TR').trim().replace(/\s+/g, ' ') : '';
                },

                normalizeSearch(str) {
                    if (!str) return '';
                    return String(str)
                        .toLocaleLowerCase('tr-TR')
                        .trim()
                        .replace(/ı/g, 'i')
                        .replace(/ğ/g, 'g')
                        .replace(/ü/g, 'u')
                        .replace(/ş/g, 's')
                        .replace(/ö/g, 'o')
                        .replace(/ç/g, 'c')
                        .replace(/\s+/g, ' ');
                },

                handlePaste(event, startRowIndex, startColIndex) {
                    let clipboardData = event.clipboardData || window.clipboardData;
                    if (!clipboardData) return;
                    let pastedText = clipboardData.getData('text');
                    if (!pastedText) return;

                    if (!pastedText.includes('\t') && !pastedText.includes('\n') && !pastedText.includes('\r')) {
                        return; // Tek hücreye normal yapıştırma
                    }

                    event.preventDefault();
                    this.saveHistory();

                    let rowsData = pastedText.split(/\r\n|\n|\r/);
                    if (rowsData.length > 0 && rowsData[rowsData.length - 1].trim() === '') {
                        rowsData.pop();
                    }
                    if (rowsData.length === 0) return;

                    // Çoklu veri yapıştırma için ilerleme modalını yapılandır ve aç
                    const totalPasteRows = rowsData.length;
                    this.totalImportRows = totalPasteRows;
                    this.isLargeDataset = totalPasteRows >= 50;
                    this.startLiveTimer(totalPasteRows);

                    this.uploadModalTitle = 'Panodan Veri Yapıştırılıyor';
                    this.uploadModalSubtitle = 'Kopyalanan veriler optimize edilerek tabloya aktarılmaktadır.';
                    this.uploadStepNames = ['1. Pano Okuma', '2. Haritalama', '3. Tablo & Hesaplama'];
                    this.showUploadModal = true;
                    this.uploadProgress = '20.0';
                    this.uploadStep = 1;
                    this.uploadStatusText = `${totalPasteRows} satır veri panodan okundu... %20`;

                    setTimeout(() => {
                        this.uploadProgress = '55.0';
                        this.uploadStep = 2;
                        this.uploadStatusText = 'Tablo satırları oluşturuluyor ve haritalanıyor... %55';

                        setTimeout(() => {
                            // 1. Eksik satırları oluştur
                            let requiredRowCount = startRowIndex + totalPasteRows;
                            if (requiredRowCount > this.rows.length) {
                                let missingCount = requiredRowCount - this.rows.length;
                                let newRowsBatch = [];
                                for (let k = 0; k < missingCount; k++) {
                                    newRowsBatch.push(this.createEmptyRowObject());
                                }
                                this.rows.push(...newRowsBatch);
                                this.updateRowMaps();
                            }

                            // 2. Sütunlar için O(1) önbellekli arama haritaları hazırla
                            let selectLookupMaps = {};
                            let masterDataLookupMaps = {};
                            let userListLookupMap = new Map();

                            (this.usersList || []).forEach(u => {
                                if (u && u.name) {
                                    let cleanName = u.name.trim();
                                    userListLookupMap.set(this.toLocaleLower(cleanName), cleanName);
                                    userListLookupMap.set(this.normalizeSearch(cleanName), cleanName);
                                    if (u.email) {
                                        userListLookupMap.set(this.toLocaleLower(u.email.trim()), cleanName);
                                        userListLookupMap.set(this.normalizeSearch(u.email.trim()), cleanName);
                                    }
                                }
                            });

                            this.fields.forEach((field, fIdx) => {
                                if (field.type === 'select' && field.options) {
                                    let map = new Map();
                                    try {
                                        let opts = typeof field.options === 'string' ? JSON.parse(field.options) : field.options;
                                        if (Array.isArray(opts)) {
                                            opts.forEach(o => {
                                                let oStr = String(o).trim();
                                                map.set(this.toLocaleLower(oStr), o);
                                                map.set(this.normalizeSearch(oStr), o);
                                            });
                                        } else {
                                            for (let key in opts) {
                                                let keyStr = String(key).trim();
                                                let valStr = String(opts[key]).trim();
                                                map.set(this.toLocaleLower(valStr), key);
                                                map.set(this.normalizeSearch(valStr), key);
                                                map.set(this.toLocaleLower(keyStr), key);
                                                map.set(this.normalizeSearch(keyStr), key);
                                            }
                                        }
                                    } catch(e) {}
                                    selectLookupMaps[fIdx] = map;
                                } else if (field.type === 'master_data' && field.master_data_type_id) {
                                    let map = new Map();
                                    let typeObj = this.masterDataTree[field.master_data_type_id];
                                    if (typeObj && typeObj.items) {
                                        typeObj.items.forEach(item => {
                                            let itemName = item.name.trim();
                                            map.set(this.toLocaleLower(itemName), itemName);
                                            map.set(this.normalizeSearch(itemName), itemName);
                                            if (itemName.includes(',')) {
                                                let dotName = itemName.replace(',', '.');
                                                map.set(this.toLocaleLower(dotName), itemName);
                                                map.set(this.normalizeSearch(dotName), itemName);
                                            }
                                            if (itemName.includes('.')) {
                                                let commaName = itemName.replace('.', ',');
                                                map.set(this.toLocaleLower(commaName), itemName);
                                                map.set(this.normalizeSearch(commaName), itemName);
                                            }
                                        });
                                    }
                                    masterDataLookupMaps[fIdx] = map;
                                }
                            });

                            this.uploadStep = 3;

                            // 3. Verileri hücrelere parçalı (chunked) olarak yerleştir
                            const CHUNK_SIZE = 35;
                            let currentBatchIndex = 0;
                            let partitionMismatches = [];
                            let unmatchedDropdowns = [];

                            const processPasteChunk = () => {
                                let start = currentBatchIndex * CHUNK_SIZE;
                                let end = Math.min(start + CHUNK_SIZE, totalPasteRows);

                                for (let i = start; i < end; i++) {
                                    let currentRowIndex = startRowIndex + i;
                                    let currentRow = this.rows[currentRowIndex];
                                    let colsData = rowsData[i].split('\t');

                                    for (let j = 0; j < colsData.length; j++) {
                                        let currentColIndex = startColIndex + j;
                                        if (currentColIndex < this.fields.length) {
                                            let targetField = this.fields[currentColIndex];

                                            if (targetField.type !== 'auto_user') {
                                                let pastedVal = colsData[j] !== undefined && colsData[j] !== null ? colsData[j].trim() : '';

                                                // Bölümlenmiş formda kilitli alan kontrolü (Örn: İŞYERİ)
                                                if (this.partitionFieldId && targetField.id == this.partitionFieldId && this.partitionValue) {
                                                    if (pastedVal !== '' && this.toLocaleLower(pastedVal) !== this.toLocaleLower(this.partitionValue)) {
                                                        partitionMismatches.push({
                                                            rowNum: currentRowIndex + 1,
                                                            fieldLabel: targetField.label || 'Kriter',
                                                            foundVal: pastedVal,
                                                            lockedVal: this.partitionValue
                                                        });
                                                    }
                                                    currentRow.values[targetField.id] = this.partitionValue;
                                                    continue;
                                                }

                                                if (this.isFieldCalculated(targetField, currentRow)) {
                                                    currentRow.values[targetField.id] = '';
                                                    continue;
                                                }

                                                if (targetField.type === 'select') {
                                                    let map = selectLookupMaps[currentColIndex];
                                                    let searchVal = this.toLocaleLower(pastedVal);
                                                    let normVal = this.normalizeSearch(pastedVal);
                                                    if (pastedVal !== '') {
                                                        if (map && map.has(searchVal)) {
                                                            currentRow.values[targetField.id] = map.get(searchVal);
                                                        } else if (map && map.has(normVal)) {
                                                            currentRow.values[targetField.id] = map.get(normVal);
                                                        } else {
                                                            currentRow.values[targetField.id] = '';
                                                            unmatchedDropdowns.push({
                                                                rowNum: currentRowIndex + 1,
                                                                fieldLabel: targetField.label || 'Açılır Liste',
                                                                pastedVal: pastedVal,
                                                                type: 'select'
                                                            });
                                                        }
                                                    } else {
                                                        currentRow.values[targetField.id] = '';
                                                    }
                                                } else if (targetField.type === 'master_data') {
                                                    let map = masterDataLookupMaps[currentColIndex];
                                                    let searchVal = this.toLocaleLower(pastedVal);
                                                    let normVal = this.normalizeSearch(pastedVal);
                                                    if (pastedVal !== '') {
                                                        if (map && map.has(searchVal)) {
                                                            currentRow.values[targetField.id] = map.get(searchVal);
                                                            this.handleMasterDataChange(currentRow, targetField.id);
                                                        } else if (map && map.has(normVal)) {
                                                            currentRow.values[targetField.id] = map.get(normVal);
                                                            this.handleMasterDataChange(currentRow, targetField.id);
                                                        } else {
                                                            currentRow.values[targetField.id] = '';
                                                            unmatchedDropdowns.push({
                                                                rowNum: currentRowIndex + 1,
                                                                fieldLabel: targetField.label || 'Master Data',
                                                                pastedVal: pastedVal,
                                                                type: 'master_data'
                                                            });
                                                        }
                                                    } else {
                                                        currentRow.values[targetField.id] = '';
                                                    }
                                                } else if (targetField.type === 'user_list') {
                                                    let searchVal = this.toLocaleLower(pastedVal);
                                                    let normVal = this.normalizeSearch(pastedVal);
                                                    if (pastedVal !== '') {
                                                        if (userListLookupMap.has(searchVal)) {
                                                            currentRow.values[targetField.id] = userListLookupMap.get(searchVal);
                                                        } else if (userListLookupMap.has(normVal)) {
                                                            currentRow.values[targetField.id] = userListLookupMap.get(normVal);
                                                        } else {
                                                            let matched = null;
                                                            for (let [k, canonicalName] of userListLookupMap.entries()) {
                                                                if (k && (k === searchVal || k === normVal || k.includes(normVal) || normVal.includes(k))) {
                                                                    matched = canonicalName;
                                                                    break;
                                                                }
                                                            }
                                                            if (matched) {
                                                                currentRow.values[targetField.id] = matched;
                                                            } else {
                                                                currentRow.values[targetField.id] = '';
                                                                unmatchedDropdowns.push({
                                                                    rowNum: currentRowIndex + 1,
                                                                    fieldLabel: targetField.label || 'Kullanıcı Listesi',
                                                                    pastedVal: pastedVal,
                                                                    type: 'user_list'
                                                                });
                                                            }
                                                        }
                                                    } else {
                                                        currentRow.values[targetField.id] = '';
                                                    }
                                                } else if (targetField.type === 'number') {
                                                    currentRow.values[targetField.id] = this.parseRawNumber(pastedVal);
                                                } else {
                                                    currentRow.values[targetField.id] = pastedVal;
                                                }
                                            }
                                        }
                                    }
                                }

                                let processed = end;
                                this.updateLiveRemainingTime(processed, totalPasteRows);
                                let pct = (60.0 + ((processed / totalPasteRows) * 39.8)).toFixed(1);
                                if (parseFloat(pct) > 99.8) pct = '99.8';
                                this.uploadProgress = pct;
                                this.uploadStatusText = `${processed} / ${totalPasteRows} satır aktarıldı (%${pct})...`;

                                currentBatchIndex++;

                                if (processed < totalPasteRows) {
                                    setTimeout(processPasteChunk, 10);
                                } else {
                                    this.stopLiveTimer();

                                    // Bölümlenmiş kriter alanını (Örn: İŞYERİ) tüm yapıştırılan satırlar için kesinleştir
                                    if (this.partitionFieldId && this.partitionValue) {
                                        this.rows.forEach(r => {
                                            if (r && r.values) r.values[this.partitionFieldId] = this.partitionValue;
                                        });
                                    }

                                    // Kaydedilmemiş değişiklik durumunu aktifleştir
                                    this.hasUnsavedChanges = true;
                                    this.autoSaveStatus = 'unsaved';

                                    // 4. Hesaplamaları TEK SEFERDE çalıştır ve kullanıcıya bildir
                                    this.updateRowMaps();
                                    this.calculateTotals();

                                    this.uploadProgress = '100';
                                    this.uploadStep = 4;
                                    this.uploadStatusText = `Tamamlandı! ${totalPasteRows} satır başarıyla aktarıldı.`;

                                    setTimeout(() => {
                                        this.showUploadModal = false;
                                        this.isLargeDataset = false;
                                        this.showToast(`✓ ${totalPasteRows} satır veri başarıyla yapıştırıldı.`);
                                        // Yapıştırılan verileri arka planda sessizce hemen taslağa kaydet
                                        this.saveDraftAjax(true);

                                        // Doğrulama uyuşmazlığı varsa uyarı modalını aç
                                        if (partitionMismatches.length > 0 || unmatchedDropdowns.length > 0) {
                                            this.importValidationData = {
                                                partitionMismatches: partitionMismatches,
                                                unmatchedDropdowns: unmatchedDropdowns
                                            };
                                            setTimeout(() => {
                                                this.showImportValidationModal = true;
                                            }, 300);
                                        }
                                    }, 450);
                                }
                            };

                            setTimeout(processPasteChunk, 15);
                        }, 30);
                    }, 30);
                },

                async handleSelectPaste(event, startRowIndex, startColIndex) {
                    try {
                        let text = await navigator.clipboard.readText();
                        this.handlePaste({
                            clipboardData: { getData: () => text },
                            preventDefault: () => { }
                        }, startRowIndex, startColIndex);
                    } catch (err) {
                        console.error('Panoya erişilemedi: ', err);
                        alert("Pano (Clipboard) okunamadı. Lütfen tarayıcınızın panoya erişim izni verdiğinden emin olun veya normal bir metin kutusuna tıklayıp yapıştırın.");
                    }
                },

                handleExcelUpload(event) {
                    const file = event.target.files[0];
                    if (!file) return;

                    this.isUploading = true;
                    this.uploadModalTitle = 'Excel Dosyası İçe Aktarılıyor';
                    this.uploadModalSubtitle = 'Büyük veri setleri optimize edilerek tabloya aktarılmaktadır.';
                    this.uploadStepNames = ['1. Yükleme', '2. Ayrıştırma', '3. Tablo'];
                    this.showUploadModal = true;
                    this.uploadProgress = 0;
                    this.uploadStep = 1;
                    this.uploadStatusText = 'Excel dosyası sunucuya yükleniyor... %0';
                    this.startLiveTimer(0);

                    const formData = new FormData();
                    formData.append('excel_file', file);
                    formData.append('_token', document.querySelector('meta[name="csrf-token"]').getAttribute('content'));

                    const xhr = new XMLHttpRequest();
                    xhr.open('POST', `{{ route('form-templates.import-excel', $formTemplate) }}`, true);
                    xhr.setRequestHeader('Accept', 'application/json');

                    // Gerçek dosya yükleme yüzdesi takibi (%0 - %88)
                    xhr.upload.onprogress = (e) => {
                        if (e.lengthComputable) {
                            const percent = Math.min(88, Math.round((e.loaded / e.total) * 90));
                            this.uploadProgress = String(percent);
                            this.uploadStatusText = `Excel dosyası sunucuya yükleniyor... %${percent}`;
                        }
                    };

                    xhr.upload.onload = () => {
                        this.uploadProgress = '90.0';
                        this.uploadStep = 2;
                        this.uploadStatusText = 'Dosya yüklendi. Excel satırları ayrıştırılıyor ve doğrulanıyor...';
                    };

                    xhr.onload = () => {
                        if (xhr.status >= 200 && xhr.status < 300) {
                            try {
                                const data = JSON.parse(xhr.responseText);
                                if (data.success) {
                                    if (data.rows && data.rows.length > 0) {
                                        const totalRows = data.rows.length;
                                        this.totalImportRows = totalRows;
                                        this.isLargeDataset = totalRows >= 50;
                                        this.startLiveTimer(totalRows);

                                        this.uploadProgress = '92.0';
                                        this.uploadStep = 2;
                                        this.uploadStatusText = `${totalRows} satır doğrulandı, normalizasyon yapılıyor...`;

                                        // UI iş parçacığının aşama metnini ve uyarı kartını çizmesine fırsat ver (40ms)
                                        setTimeout(() => {
                                            this.saveHistory();

                                            // Normalizasyon için O(1) lookup haritalarını oluştur
                                            let selectLookupMaps = {};
                                            let masterDataLookupMaps = {};
                                            let userListLookupMap = new Map();

                                            (this.usersList || []).forEach(u => {
                                                if (u && u.name) {
                                                    let cleanName = u.name.trim();
                                                    userListLookupMap.set(this.toLocaleLower(cleanName), cleanName);
                                                    userListLookupMap.set(this.normalizeSearch(cleanName), cleanName);
                                                    if (u.email) {
                                                        userListLookupMap.set(this.toLocaleLower(u.email.trim()), cleanName);
                                                        userListLookupMap.set(this.normalizeSearch(u.email.trim()), cleanName);
                                                    }
                                                }
                                            });

                                            this.fields.forEach((field) => {
                                                if (field.type === 'select' && field.options) {
                                                    let map = new Map();
                                                    try {
                                                        let opts = typeof field.options === 'string' ? JSON.parse(field.options) : field.options;
                                                        if (Array.isArray(opts)) {
                                                            opts.forEach(o => {
                                                                let oStr = String(o).trim();
                                                                map.set(this.toLocaleLower(oStr), o);
                                                                map.set(this.normalizeSearch(oStr), o);
                                                            });
                                                        } else {
                                                            for (let key in opts) {
                                                                let keyStr = String(key).trim();
                                                                let valStr = String(opts[key]).trim();
                                                                map.set(this.toLocaleLower(valStr), key);
                                                                map.set(this.normalizeSearch(valStr), key);
                                                                map.set(this.toLocaleLower(keyStr), key);
                                                                map.set(this.normalizeSearch(keyStr), key);
                                                            }
                                                        }
                                                    } catch(e) {}
                                                    selectLookupMaps[field.id] = map;
                                                } else if (field.type === 'master_data' && field.master_data_type_id) {
                                                    let map = new Map();
                                                    let typeObj = this.masterDataTree[field.master_data_type_id];
                                                    if (typeObj && typeObj.items) {
                                                        typeObj.items.forEach(item => {
                                                            let itemName = item.name.trim();
                                                            map.set(this.toLocaleLower(itemName), itemName);
                                                            map.set(this.normalizeSearch(itemName), itemName);
                                                            if (itemName.includes(',')) {
                                                                let dotName = itemName.replace(',', '.');
                                                                map.set(this.toLocaleLower(dotName), itemName);
                                                                map.set(this.normalizeSearch(dotName), itemName);
                                                            }
                                                            if (itemName.includes('.')) {
                                                                let commaName = itemName.replace('.', ',');
                                                                map.set(this.toLocaleLower(commaName), itemName);
                                                                map.set(this.normalizeSearch(commaName), itemName);
                                                            }
                                                        });
                                                    }
                                                    masterDataLookupMaps[field.id] = map;
                                                }
                                            });

                                            // Otomatik kullanıcı alanlarını ve eksik ID'leri güvenceye al
                                            const autoUserFields = this.fields.filter(f => f.type === 'auto_user');
                                            let partitionMismatches = [];
                                            let unmatchedDropdowns = [];

                                            data.rows.forEach((r, rIdx) => {
                                                if (!r.id) r.id = Date.now() + Math.random().toString(36).substr(2, 9);
                                                if (!r.values) r.values = {};
                                                if (autoUserFields.length > 0) {
                                                    autoUserFields.forEach(af => {
                                                        if (!r.values[af.id] || !String(r.values[af.id]).trim()) {
                                                            r.values[af.id] = '{{ Auth::user()->name }}';
                                                        }
                                                    });
                                                }

                                                // Değer normalizasyonu, kilitli kriter koruması ve formül kontrolü
                                                this.fields.forEach(f => {
                                                    // Bölümlenmiş kriter uyuşmazlığı kontrolü (Örn: İŞYERİ)
                                                    if (this.partitionFieldId && f.id == this.partitionFieldId && this.partitionValue) {
                                                        let currentVal = r.values && r.values[f.id] !== undefined && r.values[f.id] !== null ? String(r.values[f.id]).trim() : '';
                                                        if (currentVal !== '' && this.toLocaleLower(currentVal) !== this.toLocaleLower(this.partitionValue)) {
                                                            partitionMismatches.push({
                                                                rowNum: rIdx + 1,
                                                                fieldLabel: f.label || 'Kriter',
                                                                foundVal: currentVal,
                                                                lockedVal: this.partitionValue
                                                            });
                                                        }
                                                        r.values[f.id] = this.partitionValue;
                                                        return;
                                                    }

                                                    if (this.isFieldCalculated(f, r)) {
                                                        r.values[f.id] = '';
                                                    } else if (r.values[f.id] !== undefined && r.values[f.id] !== null && String(r.values[f.id]).trim() !== '') {
                                                        let val = String(r.values[f.id]).trim();
                                                        if (f.type === 'user_list') {
                                                            let sVal = this.toLocaleLower(val);
                                                            let nVal = this.normalizeSearch(val);
                                                            if (userListLookupMap.has(sVal)) {
                                                                r.values[f.id] = userListLookupMap.get(sVal);
                                                            } else if (userListLookupMap.has(nVal)) {
                                                                r.values[f.id] = userListLookupMap.get(nVal);
                                                            } else {
                                                                let matched = null;
                                                                for (let [k, canonicalName] of userListLookupMap.entries()) {
                                                                    if (k && (k === sVal || k === nVal || k.includes(nVal) || nVal.includes(k))) {
                                                                        matched = canonicalName;
                                                                        break;
                                                                    }
                                                                }
                                                                if (matched) {
                                                                    r.values[f.id] = matched;
                                                                } else {
                                                                    r.values[f.id] = '';
                                                                    unmatchedDropdowns.push({
                                                                        rowNum: rIdx + 1,
                                                                        fieldLabel: f.label || 'Kullanıcı Listesi',
                                                                        pastedVal: val,
                                                                        type: 'user_list'
                                                                    });
                                                                }
                                                            }
                                                        } else if (f.type === 'master_data' && masterDataLookupMaps[f.id]) {
                                                            let sVal = this.toLocaleLower(val);
                                                            let nVal = this.normalizeSearch(val);
                                                            let map = masterDataLookupMaps[f.id];
                                                            if (map.has(sVal)) {
                                                                r.values[f.id] = map.get(sVal);
                                                            } else if (map.has(nVal)) {
                                                                r.values[f.id] = map.get(nVal);
                                                            } else {
                                                                r.values[f.id] = '';
                                                                unmatchedDropdowns.push({
                                                                    rowNum: rIdx + 1,
                                                                    fieldLabel: f.label || 'Master Data',
                                                                    pastedVal: val,
                                                                    type: 'master_data'
                                                                });
                                                            }
                                                        } else if (f.type === 'select' && selectLookupMaps[f.id]) {
                                                            let sVal = this.toLocaleLower(val);
                                                            let nVal = this.normalizeSearch(val);
                                                            let map = selectLookupMaps[f.id];
                                                            if (map.has(sVal)) {
                                                                r.values[f.id] = map.get(sVal);
                                                            } else if (map.has(nVal)) {
                                                                r.values[f.id] = map.get(nVal);
                                                            } else {
                                                                r.values[f.id] = '';
                                                                unmatchedDropdowns.push({
                                                                    rowNum: rIdx + 1,
                                                                    fieldLabel: f.label || 'Açılır Liste',
                                                                    pastedVal: val,
                                                                    type: 'select'
                                                                });
                                                            }
                                                        } else if (f.type === 'number') {
                                                            let clean = this.parseRawNumber(val);
                                                            let num = parseFloat(clean);
                                                            if (!isNaN(num)) {
                                                                r.values[f.id] = Math.abs(num - Math.round(num)) < 0.00001 ? String(Math.round(num)) : String(parseFloat(num.toFixed(2)));
                                                            } else {
                                                                r.values[f.id] = clean;
                                                            }
                                                        }
                                                    }
                                                });
                                            });

                                            // Filtreleri sıfırla ve parçalı render başlat
                                            this.initFilters();
                                            this.uploadStep = 3;
                                            this.rows = [];

                                            const CHUNK_SIZE = 35;
                                            let chunkIndex = 0;

                                            const renderChunk = () => {
                                                let start = chunkIndex * CHUNK_SIZE;
                                                let end = Math.min(start + CHUNK_SIZE, totalRows);
                                                let chunk = data.rows.slice(start, end);

                                                if (chunk.length > 0) {
                                                    this.rows.push(...chunk);
                                                }

                                                let processed = end;
                                                this.updateLiveRemainingTime(processed, totalRows);
                                                // %93.0 ile %99.8 arasında dinamik, virgüllü yüzdelik ilerleme
                                                let pct = (93.0 + ((processed / totalRows) * 6.8)).toFixed(1);
                                                if (parseFloat(pct) > 99.8) pct = '99.8';
                                                this.uploadProgress = pct;
                                                this.uploadStatusText = `${processed} / ${totalRows} satır yerleştirildi (%${pct})...`;

                                                chunkIndex++;

                                                if (processed < totalRows) {
                                                    // Event loop'a kontrolü devret (yielding): Chrome asla donmaz, UI canlı güncellenir!
                                                    setTimeout(renderChunk, 10);
                                                } else {
                                                    this.stopLiveTimer();
                                                    // Tüm satırlar DOM'a yerleşti! Haritaları ve toplamları güncelle
                                                    this.updateRowMaps();
                                                    this.calculateTotals();

                                                    this.uploadProgress = '100';
                                                    this.uploadStep = 4;
                                                    this.uploadStatusText = `Tamamlandı! ${totalRows} satır başarıyla aktarıldı.`;
                                                    this.isUploading = false;

                                                    setTimeout(() => {
                                                        this.showUploadModal = false;
                                                        this.isLargeDataset = false;

                                                        // Doğrulama uyuşmazlığı varsa uyarı modalını aç
                                                        if (partitionMismatches.length > 0 || unmatchedDropdowns.length > 0) {
                                                            this.importValidationData = {
                                                                partitionMismatches: partitionMismatches,
                                                                unmatchedDropdowns: unmatchedDropdowns
                                                            };
                                                            setTimeout(() => {
                                                                this.showImportValidationModal = true;
                                                            }, 300);
                                                        }
                                                    }, 450);

                                                    this.hasUnsavedChanges = true;
                                                    this.saveDraftAjax(true, (err, res) => {
                                                        if (!err) {
                                                            this.showToast(`✓ ${totalRows} satır içe aktarıldı ve taslak olarak kaydedildi.`);
                                                        } else {
                                                            this.showToast(`✓ ${totalRows} satır başarıyla içe aktarıldı.`);
                                                        }
                                                    });
                                                }
                                            };

                                            // İlk paketi başlat
                                            setTimeout(renderChunk, 15);
                                        }, 40);
                                    } else {
                                        this.stopLiveTimer();
                                        this.showUploadModal = false;
                                        this.isUploading = false;
                                        this.isLargeDataset = false;
                                        this.showToast('Uyarı: Excel dosyasında okunabilir veri bulunamadı.');
                                    }
                                } else {
                                    this.stopLiveTimer();
                                    this.showUploadModal = false;
                                    this.isUploading = false;
                                    this.isLargeDataset = false;
                                    alert('Hata: ' + (data.message || 'Bilinmeyen bir hata oluştu.'));
                                }
                            } catch (err) {
                                this.stopLiveTimer();
                                this.showUploadModal = false;
                                this.isUploading = false;
                                this.isLargeDataset = false;
                                alert('Sunucu yanıtı işlenirken hata oluştu: ' + err.message);
                            }
                        } else {
                            this.stopLiveTimer();
                            this.showUploadModal = false;
                            this.isUploading = false;
                            this.isLargeDataset = false;
                            let msg = 'Yükleme başarısız oldu (HTTP ' + xhr.status + ')';
                            try {
                                const errData = JSON.parse(xhr.responseText);
                                if (errData.message) msg = errData.message;
                            } catch(e){}
                            alert('Hata: ' + msg);
                        }
                    };

                    xhr.onerror = () => {
                        this.stopLiveTimer();
                        this.showUploadModal = false;
                        this.isUploading = false;
                        this.isLargeDataset = false;
                        alert('Ağ hatası: Excel yüklenemedi. Lütfen internet bağlantınızı ve sunucuyu kontrol edin.');
                    };

                    xhr.send(formData);
                    event.target.value = '';
                }
            }));
        });
    </script>
</x-app-layout>