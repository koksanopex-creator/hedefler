@section('title', $formTemplate->name . ' - ' . ($partitionField->label ?? 'Bölüm') . ' Seçimi')
<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div class="flex items-center space-x-4">
                <a href="{{ route('form-entries.select-template') }}"
                    class="text-slate-500 hover:text-slate-700 transition">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                    </svg>
                </a>
                <div>
                    <div class="flex items-center gap-2">
                        <span
                            class="text-xs font-black uppercase tracking-wider text-indigo-600 bg-indigo-50 border border-indigo-200 px-2 py-0.5 rounded-md">Parçalı
                            Veri Girişi</span>
                        <h2 class="font-extrabold text-xl text-slate-900 leading-tight">
                            {{ $formTemplate->name }}
                        </h2>
                    </div>
                    <p class="text-xs text-slate-500 mt-0.5">Lütfen veri girişi yapmak istediğiniz
                        <strong>{{ $partitionField->label }}</strong> değerini seçiniz</p>
                </div>
            </div>

            <a href="{{ route('form-entries.index') }}"
                class="text-xs font-bold text-slate-600 hover:text-indigo-600 bg-white border border-slate-200 px-3.5 py-2 rounded-xl shadow-2xs transition">
                Tüm Veri Girişleri Listesi &rarr;
            </a>
        </div>
    </x-slot>

    <div class="py-10">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            @if(session('info'))
                <div
                    class="p-4 rounded-2xl bg-sky-50 border border-sky-200 text-sky-900 text-xs font-bold flex items-center gap-2 shadow-2xs">
                    <svg class="w-5 h-5 text-sky-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span>{{ session('info') }}</span>
                </div>
            @endif

            @if(session('warning'))
                <div
                    class="p-4 rounded-2xl bg-amber-50 border border-amber-200 text-amber-900 text-xs font-bold flex items-center gap-2 shadow-2xs">
                    <svg class="w-5 h-5 text-amber-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                    <span>{{ session('warning') }}</span>
                </div>
            @endif

            <!-- BİLGİLENDİRME KUTUSU -->
            <div
                class="bg-gradient-to-r from-indigo-900 via-indigo-800 to-slate-900 text-white rounded-3xl p-6 sm:p-8 shadow-xl relative overflow-hidden">
                <div class="absolute -right-8 -bottom-8 opacity-10 pointer-events-none">
                    <svg class="w-64 h-64" fill="currentColor" viewBox="0 0 24 24">
                        <path
                            d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-5 14H7v-2h7v2zm3-4H7v-2h10v2zm0-4H7V7h10v2z" />
                    </svg>
                </div>
                <div class="max-w-2xl relative z-10 space-y-2">
                    <span
                        class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[11px] font-black uppercase tracking-wider bg-white/20 text-white backdrop-blur-md">
                        <span>⚡ Bölümlenmiş Giriş Kriteri:</span>
                        <strong class="text-amber-300">{{ $partitionField->label }}</strong>
                    </span>
                    <h3 class="text-xl sm:text-2xl font-black">Hangi {{ $partitionField->label }} için veri girişi
                        yapacaksınız?</h3>
                    <p class="text-xs sm:text-sm text-indigo-100/90 leading-relaxed">
                        Sistem yöneticiniz bu form için verileri <strong>{{ $partitionField->label }}</strong> bazında
                        parçalara ayırmıştır.
                        Aşağıdaki seçeneklerden birini seçerek ilgili kritere kilitli formunuzu doldurabilir veya
                        kayıtlı taslağınıza devam edebilirsiniz.
                    </p>
                </div>
            </div>

            <!-- KRİTER SEÇENEKLERİ IZGARASI -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
                @forelse($partitionOptions as $option)
                    @php
                        $entry = $userEntries[$option['name']] ?? null;
                        $hasDraft = $entry && $entry->status === 'draft';
                        $hasSubmitted = $entry && $entry->status === 'submitted';
                        $hasApproved = $entry && $entry->status === 'approved';
                        $hasRejected = $entry && $entry->status === 'rejected';

                        $cardBg = 'bg-white border-slate-200 hover:border-indigo-400';
                        $statusBadge = null;

                        if ($hasDraft) {
                            $cardBg = 'bg-white border-slate-300 shadow-slate-500/5 hover:border-slate-400';
                            $statusBadge = ['bg' => 'bg-slate-100 text-slate-800 border-slate-300', 'dot' => 'bg-slate-600', 'text' => 'Kayıtlı Taslak'];
                        } elseif ($hasSubmitted) {
                            $cardBg = 'bg-white border-amber-300 shadow-amber-500/5 hover:border-amber-400';
                            $statusBadge = ['bg' => 'bg-amber-100 text-amber-900 border-amber-300', 'dot' => 'bg-amber-500', 'text' => 'Onay Bekliyor'];
                        } elseif ($hasApproved) {
                            $cardBg = 'bg-white border-emerald-300 shadow-emerald-500/5 hover:border-emerald-400';
                            $statusBadge = ['bg' => 'bg-emerald-100 text-emerald-900 border-emerald-300', 'dot' => 'bg-emerald-500', 'text' => 'Onaylandı'];
                        } elseif ($hasRejected) {
                            $cardBg = 'bg-white border-rose-300 shadow-rose-500/5 hover:border-rose-400';
                            $statusBadge = ['bg' => 'bg-rose-100 text-rose-900 border-rose-300', 'dot' => 'bg-rose-500', 'text' => 'Reddedildi / Düzenle'];
                        }
                    @endphp

                    <div
                        class="p-5 rounded-2xl border-2 {{ $cardBg }} shadow-sm transition-all flex flex-col justify-between hover:shadow-md group">
                        <div class="space-y-3">
                            <div class="flex items-start justify-between gap-2">
                                <div class="flex items-center gap-2.5">
                                    <span
                                        class="w-10 h-10 rounded-xl bg-indigo-50 text-indigo-700 flex items-center justify-center font-black text-sm border border-indigo-100 group-hover:bg-indigo-600 group-hover:text-white transition-colors">
                                        {{ mb_substr($option['name'], 0, 2) }}
                                    </span>
                                    <div>
                                        <h4
                                            class="text-base font-black text-slate-900 group-hover:text-indigo-600 transition-colors">
                                            {{ $option['name'] }}
                                        </h4>
                                        <span class="text-[11px] font-semibold text-slate-400">
                                            {{ $partitionField->label }}
                                        </span>
                                    </div>
                                </div>

                                @if($statusBadge)
                                    <span
                                        class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[10px] font-bold border {{ $statusBadge['bg'] }} shrink-0">
                                        <span class="w-1.5 h-1.5 rounded-full {{ $statusBadge['dot'] }}"></span>
                                        <span>{{ $statusBadge['text'] }}</span>
                                    </span>
                                @else
                                    <span
                                        class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[10px] font-bold bg-slate-100 text-slate-500 border border-slate-200">
                                        Giriş Yapılmadı
                                    </span>
                                @endif
                            </div>

                            @if($entry)
                                <div class="p-2.5 rounded-xl bg-slate-50 border border-slate-200/80 text-[11px] space-y-1">
                                    <div class="flex justify-between text-slate-500 font-semibold">
                                        <span>Kayıt No:</span>
                                        <strong class="text-slate-800 font-mono">#{{ $entry->id }}</strong>
                                    </div>
                                    <div class="flex justify-between text-slate-500 font-semibold">
                                        <span>Satır Sayısı:</span>
                                        <strong class="text-slate-800">{{ $entry->rows()->count() }} Satır</strong>
                                    </div>
                                    <div class="flex justify-between text-slate-500 font-semibold">
                                        <span>Son Güncelleme:</span>
                                        <span
                                            class="text-slate-700">{{ $entry->updated_at?->format('d.m.Y H:i') ?? '-' }}</span>
                                    </div>
                                </div>
                            @else
                                <p class="text-xs text-slate-500 italic py-1">
                                    Bu {{ $partitionField->label }} için henüz bir veri kaydı bulunmuyor. Yeni bir form açarak
                                    satırlarınızı girebilirsiniz.
                                </p>
                            @endif
                        </div>

                        <!-- AKSİYON BUTONLARI -->
                        <div class="mt-4 pt-3 border-t border-slate-100 flex items-center justify-between gap-2"
                            x-data="{ clicking: false }">
                            @if($hasDraft)
                                <a href="{{ route('form-entries.edit', $entry) }}"
                                    @click="if(clicking) { $event.preventDefault(); return false; } clicking = true;"
                                    onclick="if(this.dataset.clicked === 'true'){ return false; } this.dataset.clicked = 'true'; this.classList.add('pointer-events-none', 'opacity-70', 'cursor-not-allowed');"
                                    :class="clicking ? 'pointer-events-none opacity-70 cursor-not-allowed' : ''"
                                    class="w-full inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl text-xs font-black bg-slate-800 hover:bg-slate-900 text-white shadow-sm shadow-slate-800/20 transition active:scale-98 select-none">
                                    <svg class="w-4 h-4 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                    </svg>
                                    <span
                                        x-text="clicking ? 'Yönlendiriliyor...' : 'Mevcut Taslağı Düzenle (#{{ $entry->id }})'">Mevcut
                                        Taslağı Düzenle (#{{ $entry->id }})</span>
                                </a>
                            @elseif($hasSubmitted)
                                <a href="{{ route('form-entries.show', $entry) }}"
                                    @click="if(clicking) { $event.preventDefault(); return false; } clicking = true;"
                                    onclick="if(this.dataset.clicked === 'true'){ return false; } this.dataset.clicked = 'true'; this.classList.add('pointer-events-none', 'opacity-70', 'cursor-not-allowed');"
                                    :class="clicking ? 'pointer-events-none opacity-70 cursor-not-allowed' : ''"
                                    class="w-full inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl text-xs font-black bg-amber-500 hover:bg-amber-600 text-white shadow-sm shadow-amber-500/25 transition active:scale-98 select-none ring-1 ring-amber-400">
                                    <svg class="w-4 h-4 text-amber-100" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    <span x-text="clicking ? 'Yönlendiriliyor...' : 'Onay Bekliyor - İncele (#{{ $entry->id }})'">Onay Bekliyor - İncele (#{{ $entry->id }})</span>
                                </a>
                            @elseif($hasApproved)
                                <div class="w-full flex items-center gap-2">
                                    <a href="{{ route('form-entries.show', $entry) }}"
                                        @click="if(clicking) { $event.preventDefault(); return false; } clicking = true;"
                                        onclick="if(this.dataset.clicked === 'true'){ return false; } this.dataset.clicked = 'true'; this.classList.add('pointer-events-none', 'opacity-70', 'cursor-not-allowed');"
                                        :class="clicking ? 'pointer-events-none opacity-70 cursor-not-allowed' : ''"
                                        class="flex-1 inline-flex items-center justify-center gap-1.5 px-3 py-2.5 rounded-xl text-xs font-black bg-emerald-600 hover:bg-emerald-700 text-white shadow-sm transition active:scale-98 select-none">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                        <span x-text="clicking ? 'Yönlendiriliyor...' : 'Onaylı Kaydı Gör'">Onaylı Kaydı
                                            Gör</span>
                                    </a>
                                </div>
                            @else
                                <a href="{{ route('form-entries.create', ['formTemplate' => $formTemplate, 'partition_value' => $option['name']]) }}"
                                    @click="if(clicking) { $event.preventDefault(); return false; } clicking = true;"
                                    onclick="if(this.dataset.clicked === 'true'){ return false; } this.dataset.clicked = 'true'; this.classList.add('pointer-events-none', 'opacity-70', 'cursor-not-allowed');"
                                    :class="clicking ? 'pointer-events-none opacity-70 cursor-not-allowed' : ''"
                                    class="w-full inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl text-xs font-black bg-indigo-600 hover:bg-indigo-700 text-white shadow-sm shadow-indigo-500/20 transition active:scale-98 select-none">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M12 4v16m8-8H4" />
                                    </svg>
                                    <span
                                        x-text="clicking ? 'Yönlendiriliyor...' : '{{ $option['name'] }} İçin Veri Girişi Başlat'">{{ $option['name'] }}
                                        İçin Veri Girişi Başlat</span>
                                </a>
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="col-span-full bg-white rounded-2xl border border-slate-200 p-12 text-center text-slate-500">
                        {{ $partitionField->label }} alanına tanımlı herhangi bir seçenek bulunamadı.
                    </div>
                @endforelse
            </div>

            <!-- GENEL TÜM KAYITLARI GÖRÜNTÜLE -->
            <div class="flex justify-between items-center bg-white p-4 rounded-2xl border border-slate-200 shadow-2xs">
                <div class="flex items-center gap-3">
                    <span class="w-2.5 h-2.5 rounded-full bg-indigo-600"></span>
                    <span class="text-xs font-bold text-slate-700">Daha önce girilmiş tüm formlarınızı incelemek veya
                        birleştirmek mi istiyorsunuz?</span>
                </div>
                <a href="{{ route('form-entries.index') }}"
                    class="text-xs font-black text-indigo-600 hover:text-indigo-800 hover:underline">
                    Veri Girişleri Listesine Git &rarr;
                </a>
            </div>

        </div>
    </div>
</x-app-layout>