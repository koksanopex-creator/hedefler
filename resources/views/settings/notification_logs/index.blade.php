@section('title', 'Bildirim ve E-Posta Gönderim Geçmişi')
<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h2 class="font-extrabold text-2xl text-transparent bg-clip-text bg-gradient-to-r from-indigo-700 via-purple-700 to-pink-600 tracking-tight flex items-center gap-2">
                    <svg class="w-7 h-7 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                    {{ __('Bildirim ve E-Posta Gönderim Geçmişi') }}
                </h2>
                <p class="text-xs text-gray-500 mt-1">Sistem tarafından gönderilen tüm Zil (sistem içi) ve E-Posta bildirimlerinin rol, işlem ve içerik bazlı denetim kayıtları.</p>
            </div>
            <div class="flex items-center gap-3">
                @if(!auth()->user()?->isYonetim())
                <button type="button" @click="$dispatch('open-clear-modal')" class="inline-flex items-center px-4 py-2 rounded-xl text-xs font-bold text-rose-700 bg-rose-50 border border-rose-200 hover:bg-rose-100 hover:text-rose-800 transition shadow-xs cursor-pointer">
                    <svg class="w-4 h-4 mr-1.5 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                    Geçmiş Logları Temizle
                </button>
                @endif
                <a href="{{ route('settings.notifications.index') }}" class="inline-flex items-center px-4 py-2 rounded-xl text-xs font-bold text-indigo-700 bg-indigo-50 border border-indigo-200 hover:bg-indigo-100 transition shadow-xs">
                    <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path></svg>
                    Bildirim Matrisi &rarr;
                </a>
                <a href="{{ route('settings.index') }}" class="inline-flex items-center px-4 py-2 rounded-xl text-xs font-bold text-gray-700 bg-white border border-gray-200 hover:bg-gray-50 transition shadow-xs">
                    Ayarlar Menüsü
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-8 bg-slate-50/50 min-h-screen" x-data="{ showClearModal: false }" @open-clear-modal.window="showClearModal = true">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @if(session('success'))
                <div class="p-4 bg-emerald-50 border border-emerald-200 rounded-2xl flex items-center justify-between text-emerald-800 text-xs font-semibold shadow-xs">
                    <div class="flex items-center gap-2">
                        <svg class="w-5 h-5 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        <span>{{ session('success') }}</span>
                    </div>
                </div>
            @endif
            @if(session('error'))
                <div class="p-4 bg-rose-50 border border-rose-200 rounded-2xl flex items-center justify-between text-rose-800 text-xs font-semibold shadow-xs">
                    <div class="flex items-center gap-2">
                        <svg class="w-5 h-5 text-rose-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        <span>{{ session('error') }}</span>
                    </div>
                </div>
            @endif

            <!-- 1. ÖZET İSTATİSTİK KARTLARI -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="bg-white/80 backdrop-blur-md p-5 rounded-2xl border border-gray-100 shadow-xs flex items-center justify-between">
                    <div>
                        <span class="text-xs font-bold uppercase tracking-wider text-gray-500">Toplam Gönderim</span>
                        <div class="text-2xl font-black text-gray-900 mt-1">{{ number_format($totalCount, 0, ',', '.') }}</div>
                    </div>
                    <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center text-white shadow-sm">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                    </div>
                </div>

                <div class="bg-white/80 backdrop-blur-md p-5 rounded-2xl border border-amber-100 shadow-xs flex items-center justify-between">
                    <div>
                        <span class="text-xs font-bold uppercase tracking-wider text-amber-700">🔔 Zil (Sistem İçi)</span>
                        <div class="text-2xl font-black text-amber-900 mt-1">{{ number_format($inAppCount, 0, ',', '.') }}</div>
                    </div>
                    <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-amber-400 to-orange-500 flex items-center justify-center text-white shadow-sm">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path></svg>
                    </div>
                </div>

                <div class="bg-white/80 backdrop-blur-md p-5 rounded-2xl border border-sky-100 shadow-xs flex items-center justify-between">
                    <div>
                        <span class="text-xs font-bold uppercase tracking-wider text-sky-700">✉️ E-Posta (Mail)</span>
                        <div class="text-2xl font-black text-sky-900 mt-1">{{ number_format($mailCount, 0, ',', '.') }}</div>
                    </div>
                    <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-sky-400 to-blue-600 flex items-center justify-center text-white shadow-sm">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
                    </div>
                </div>

                <div class="bg-white/80 backdrop-blur-md p-5 rounded-2xl border border-purple-100 shadow-xs flex items-center justify-between">
                    <div>
                        <span class="text-xs font-bold uppercase tracking-wider text-purple-700">İşlem / Kategori</span>
                        <div class="text-2xl font-black text-purple-900 mt-1">{{ $eventsCount }} Olay</div>
                    </div>
                    <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-purple-500 to-pink-500 flex items-center justify-center text-white shadow-sm">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
                    </div>
                </div>
            </div>

            <!-- 2. ÜST FİLTRELEME ÇUBUĞU (TOP FILTER BAR) -->
            <div class="bg-white/90 backdrop-blur-md p-6 rounded-3xl border border-gray-100 shadow-[0_8px_30px_rgb(0,0,0,0.04)]">
                <form method="GET" action="{{ route('settings.notification_logs.index') }}" class="space-y-4">
                    <input type="hidden" name="view_mode" value="{{ $viewMode }}">

                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-12 gap-4 items-end">
                        
                        <!-- 1. Kanal Filtresi (Zil / Mail / Tümü) -->
                        <div class="lg:col-span-2">
                            <label class="block text-xs font-bold uppercase tracking-wider text-gray-600 mb-1.5 flex items-center gap-1">
                                <span>Kanal Türü</span>
                            </label>
                            <select name="channel" class="w-full rounded-xl border-gray-200 text-xs font-semibold focus:border-indigo-500 focus:ring-indigo-500 bg-gray-50/50 py-2.5">
                                <option value="" {{ empty($channel) ? 'selected' : '' }}>Tüm Kanallar (Zil & Mail)</option>
                                <option value="in_app" {{ $channel === 'in_app' ? 'selected' : '' }}>🔔 Sadece Zil (In-App)</option>
                                <option value="mail" {{ $channel === 'mail' ? 'selected' : '' }}>✉️ Sadece E-Posta (Mail)</option>
                            </select>
                        </div>

                        <!-- 2. Kategori / İşlem Filtresi -->
                        <div class="lg:col-span-3">
                            <label class="block text-xs font-bold uppercase tracking-wider text-gray-600 mb-1.5">
                                Yapılan İşlem / Kategori
                            </label>
                            <select name="event_slug" class="w-full rounded-xl border-gray-200 text-xs font-semibold focus:border-indigo-500 focus:ring-indigo-500 bg-gray-50/50 py-2.5">
                                <option value="">Tüm İşlemler / Olaylar</option>
                                @foreach($events as $ev)
                                    <option value="{{ $ev->slug }}" {{ $eventSlug === $ev->slug ? 'selected' : '' }}>
                                        {{ $ev->name }} ({{ $ev->slug }})
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <!-- 3. Rol Filtresi -->
                        <div class="lg:col-span-2">
                            <label class="block text-xs font-bold uppercase tracking-wider text-gray-600 mb-1.5">
                                Alıcı Rolü
                            </label>
                            <select name="role" class="w-full rounded-xl border-gray-200 text-xs font-semibold focus:border-indigo-500 focus:ring-indigo-500 bg-gray-50/50 py-2.5">
                                <option value="">Tüm Roller</option>
                                @foreach($distinctRoles as $rName)
                                    <option value="{{ $rName }}" {{ $role === $rName ? 'selected' : '' }}>
                                        {{ $rName }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <!-- 4. Arama Kutusu -->
                        <div class="lg:col-span-3">
                            <label class="block text-xs font-bold uppercase tracking-wider text-gray-600 mb-1.5">
                                Arama (Alıcı, Konu, İçerik)
                            </label>
                            <div class="relative">
                                <input type="text" name="search" value="{{ $search }}" placeholder="İsim, e-posta veya anahtar kelime..."
                                    class="w-full rounded-xl border-gray-200 text-xs focus:border-indigo-500 focus:ring-indigo-500 bg-gray-50/50 pl-8 pr-3 py-2.5">
                                <svg class="w-4 h-4 text-gray-400 absolute left-2.5 top-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                            </div>
                        </div>

                        <!-- 5. Filtrele Butonları -->
                        <div class="lg:col-span-2 flex items-center gap-2">
                            <button type="submit" class="w-full inline-flex items-center justify-center px-4 py-2.5 bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-700 hover:to-purple-700 text-white text-xs font-bold rounded-xl shadow-sm transition">
                                <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"></path></svg>
                                Filtrele
                            </button>
                            <a href="{{ route('settings.notification_logs.index') }}" class="p-2.5 rounded-xl border border-gray-200 text-gray-500 hover:text-red-600 hover:bg-red-50 transition" title="Filtreleri Temizle">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                            </a>
                        </div>
                    </div>

                    <!-- Tarih Aralığı Satırı -->
                    <div class="pt-3 border-t border-gray-100 flex flex-wrap items-center justify-between gap-4 text-xs">
                        <div class="flex items-center gap-3">
                            <span class="font-bold text-gray-500">Tarih Aralığı:</span>
                            <div class="flex items-center gap-2">
                                <input type="date" name="date_from" value="{{ $dateFrom }}" class="rounded-lg border-gray-200 text-xs py-1 px-2.5 focus:ring-indigo-500 focus:border-indigo-500">
                                <span class="text-gray-400">-</span>
                                <input type="date" name="date_to" value="{{ $dateTo }}" class="rounded-lg border-gray-200 text-xs py-1 px-2.5 focus:ring-indigo-500 focus:border-indigo-500">
                            </div>
                        </div>

                        <!-- Hızlı Tarih Seçicileri -->
                        <div class="flex items-center gap-1.5">
                            <span class="text-gray-400 mr-1">Hızlı Seçim:</span>
                            <a href="{{ route('settings.notification_logs.index', array_merge(request()->query(), ['date_from' => now()->format('Y-m-d'), 'date_to' => now()->format('Y-m-d')])) }}" class="px-2 py-0.5 rounded-md bg-gray-100 hover:bg-indigo-50 hover:text-indigo-600 transition text-[11px] font-medium text-gray-600">Bugün</a>
                            <a href="{{ route('settings.notification_logs.index', array_merge(request()->query(), ['date_from' => now()->startOfWeek()->format('Y-m-d'), 'date_to' => now()->endOfWeek()->format('Y-m-d')])) }}" class="px-2 py-0.5 rounded-md bg-gray-100 hover:bg-indigo-50 hover:text-indigo-600 transition text-[11px] font-medium text-gray-600">Bu Hafta</a>
                            <a href="{{ route('settings.notification_logs.index', array_merge(request()->query(), ['date_from' => now()->startOfMonth()->format('Y-m-d'), 'date_to' => now()->endOfMonth()->format('Y-m-d')])) }}" class="px-2 py-0.5 rounded-md bg-gray-100 hover:bg-indigo-50 hover:text-indigo-600 transition text-[11px] font-medium text-gray-600">Bu Ay</a>
                        </div>
                    </div>
                </form>
            </div>

            <!-- 3. GÖRÜNÜM SEÇENEKLERİ (LİSTE vs İŞLEME GÖRE GRUPLU) -->
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-1 p-1 bg-white border border-gray-200/80 rounded-xl shadow-xs">
                    <a href="{{ route('settings.notification_logs.index', array_merge(request()->query(), ['view_mode' => 'list'])) }}"
                       class="px-3.5 py-1.5 rounded-lg text-xs font-bold transition flex items-center gap-1.5 {{ $viewMode === 'list' ? 'bg-indigo-600 text-white shadow-xs' : 'text-gray-600 hover:text-indigo-600' }}">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"></path></svg>
                        Kronolojik Liste
                    </a>
                    <a href="{{ route('settings.notification_logs.index', array_merge(request()->query(), ['view_mode' => 'grouped'])) }}"
                       class="px-3.5 py-1.5 rounded-lg text-xs font-bold transition flex items-center gap-1.5 {{ $viewMode === 'grouped' ? 'bg-indigo-600 text-white shadow-xs' : 'text-gray-600 hover:text-indigo-600' }}">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
                        İşleme Göre Gruplu
                    </a>
                </div>

                <div class="text-xs text-gray-500 font-medium">
                    Toplam <span class="font-bold text-gray-900">{{ $logs->total() }}</span> log kaydı listeleniyor
                </div>
            </div>

            <!-- 4. TABLO VEYA GRUPLU GÖRÜNÜM -->
            <div x-data="{ 
                    selectedLog: null,
                    modalOpen: false,
                    showDetails(logData) {
                        this.selectedLog = logData;
                        this.modalOpen = true;
                    }
                 }">

                @if($viewMode === 'grouped' && $groupedLogs)
                    <!-- ========================================== -->
                    <!-- İŞLEME GÖRE GRUPLANMIŞ GÖRÜNÜM             -->
                    <!-- ========================================== -->
                    <div class="space-y-6">
                        @forelse($groupedLogs as $eventName => $items)
                            <div class="bg-white rounded-3xl border border-gray-100 shadow-[0_8px_30px_rgb(0,0,0,0.03)] overflow-hidden">
                                <div class="bg-gradient-to-r from-slate-50 to-indigo-50/40 px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                                    <div class="flex items-center gap-3">
                                        <span class="w-2.5 h-2.5 rounded-full bg-indigo-600 ring-4 ring-indigo-100"></span>
                                        <h3 class="text-sm font-black text-gray-900 uppercase tracking-wider">{{ $eventName ?: 'Kategorisiz İşlem' }}</h3>
                                    </div>
                                    <span class="text-xs font-extrabold text-indigo-700 bg-indigo-100/70 px-3 py-1 rounded-full">
                                        {{ $items->count() }} Bildirim
                                    </span>
                                </div>
                                <div class="overflow-x-auto">
                                    <table class="min-w-full divide-y divide-gray-100">
                                        <thead class="bg-gray-50/50">
                                            <tr>
                                                <th class="px-5 py-3 text-left text-[11px] font-bold text-gray-400 uppercase tracking-wider">Kanal</th>
                                                <th class="px-5 py-3 text-left text-[11px] font-bold text-gray-400 uppercase tracking-wider">Alıcı</th>
                                                <th class="px-5 py-3 text-left text-[11px] font-bold text-gray-400 uppercase tracking-wider">Rol</th>
                                                <th class="px-5 py-3 text-left text-[11px] font-bold text-gray-400 uppercase tracking-wider">Başlık / Konu</th>
                                                <th class="px-5 py-3 text-left text-[11px] font-bold text-gray-400 uppercase tracking-wider">Tarih</th>
                                                <th class="px-5 py-3 text-right text-[11px] font-bold text-gray-400 uppercase tracking-wider">İşlem</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-gray-50">
                                            @foreach($items as $log)
                                                @include('settings.notification_logs._row', ['log' => $log])
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        @empty
                            <div class="bg-white rounded-3xl p-12 text-center text-gray-400 border border-gray-100 shadow-xs">
                                Filtrelere uygun kayıt bulunamadı.
                            </div>
                        @endforelse
                    </div>

                @else
                    <!-- ========================================== -->
                    <!-- KRONOLOJİK DÜZ LİSTE TABLOSU               -->
                    <!-- ========================================== -->
                    <div class="bg-white rounded-3xl border border-gray-100 shadow-[0_8px_30px_rgb(0,0,0,0.03)] overflow-hidden">
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-100">
                                <thead class="bg-gray-50/60">
                                    <tr>
                                        <th class="px-6 py-3.5 text-left text-[11px] font-bold text-gray-400 uppercase tracking-wider">Kanal</th>
                                        <th class="px-6 py-3.5 text-left text-[11px] font-bold text-gray-400 uppercase tracking-wider">İşlem (Kategori)</th>
                                        <th class="px-6 py-3.5 text-left text-[11px] font-bold text-gray-400 uppercase tracking-wider">Alıcı</th>
                                        <th class="px-6 py-3.5 text-left text-[11px] font-bold text-gray-400 uppercase tracking-wider">Rol</th>
                                        <th class="px-6 py-3.5 text-left text-[11px] font-bold text-gray-400 uppercase tracking-wider">Başlık / Konu</th>
                                        <th class="px-6 py-3.5 text-left text-[11px] font-bold text-gray-400 uppercase tracking-wider">Gönderim Tarihi</th>
                                        <th class="px-6 py-3.5 text-right text-[11px] font-bold text-gray-400 uppercase tracking-wider">İçerik</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100/70">
                                    @forelse($logs as $log)
                                        @include('settings.notification_logs._row', ['log' => $log, 'showCategory' => true])
                                    @empty
                                        <tr>
                                            <td colspan="7" class="px-6 py-12 text-center text-gray-400">
                                                <div class="flex flex-col items-center justify-center">
                                                    <svg class="w-12 h-12 text-gray-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path></svg>
                                                    <p class="text-sm font-semibold text-gray-600">Henüz kaydedilmiş bildirim veya e-posta gönderim kaydı bulunamadı.</p>
                                                    <p class="text-xs text-gray-400 mt-1">Sistemde form doldurulduğunda veya onaylandığında kayıtlar otomatik buraya yansıyacaktır.</p>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        <!-- Sayfalama (Pagination) -->
                        @if($logs->hasPages())
                            <div class="px-6 py-4 bg-gray-50/50 border-t border-gray-100">
                                {{ $logs->links() }}
                            </div>
                        @endif
                    </div>
                @endif

                <!-- ========================================== -->
                <!-- 5. BİLDİRİM İÇERİĞİ VE DETAY MODALI        -->
                <!-- ========================================== -->
                <div x-show="modalOpen" 
                     x-cloak
                     style="display: none;"
                     class="fixed inset-0 z-50 overflow-y-auto" 
                     aria-labelledby="modal-title" role="dialog" aria-modal="true">
                    
                    <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                        <div x-show="modalOpen" 
                             @click="modalOpen = false" 
                             x-transition.opacity 
                             class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity" 
                             aria-hidden="true"></div>

                        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

                        <div x-show="modalOpen" 
                             x-transition 
                             class="inline-block align-bottom bg-white rounded-3xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-3xl sm:w-full border border-gray-100">
                            
                            <!-- Modal Başlık Çubuğu -->
                            <div class="bg-gradient-to-r from-slate-50 via-indigo-50/30 to-purple-50/30 px-6 py-5 border-b border-gray-100 flex items-start justify-between">
                                <div class="space-y-1">
                                    <div class="flex items-center gap-2">
                                        <template x-if="selectedLog?.channel === 'mail'">
                                            <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-sky-100 text-sky-800 border border-sky-200 flex items-center gap-1">
                                                ✉️ E-Posta
                                            </span>
                                        </template>
                                        <template x-if="selectedLog?.channel === 'in_app'">
                                            <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-amber-100 text-amber-800 border border-amber-200 flex items-center gap-1">
                                                🔔 Zil Bildirimi
                                            </span>
                                        </template>
                                        <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-purple-100 text-purple-800 border border-purple-200" x-text="selectedLog?.event_name || 'İşlem'"></span>
                                    </div>
                                    <h3 class="text-base font-extrabold text-gray-900 leading-snug pt-1" x-text="selectedLog?.subject"></h3>
                                </div>
                                <button type="button" @click="modalOpen = false" class="text-gray-400 hover:text-gray-600 bg-white rounded-full p-1.5 border border-gray-200 hover:bg-gray-50 transition shadow-xs">
                                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </button>
                            </div>

                            <!-- Modal Üst Meta Bilgileri -->
                            <div class="px-6 py-3 bg-gray-50/60 border-b border-gray-100 grid grid-cols-1 sm:grid-cols-3 gap-3 text-xs">
                                <div>
                                    <span class="text-gray-400 font-semibold block">Alıcı Kişi:</span>
                                    <span class="font-bold text-gray-800" x-text="selectedLog?.recipient_name"></span>
                                    <span class="text-gray-500 block text-[11px]" x-text="selectedLog?.recipient_email"></span>
                                </div>
                                <div>
                                    <span class="text-gray-400 font-semibold block">Alıcı Rolü:</span>
                                    <span class="inline-block mt-0.5 px-2 py-0.5 rounded bg-indigo-50 text-indigo-700 font-bold border border-indigo-100" x-text="selectedLog?.recipient_role || 'Belirtilmedi'"></span>
                                </div>
                                <div>
                                    <span class="text-gray-400 font-semibold block">Gönderim Zamanı:</span>
                                    <span class="font-bold text-gray-800" x-text="selectedLog?.sent_at_formatted"></span>
                                </div>
                            </div>

                            <!-- Modal Gövdesi (Zengin Metin / HTML) -->
                            <div class="p-6 bg-white space-y-4">
                                <label class="block text-xs font-bold uppercase tracking-wider text-gray-500">Bildirim İçeriği (Gönderilen Metin):</label>
                                <div class="p-5 rounded-2xl bg-slate-50/70 border border-slate-200/80 prose prose-sm max-w-none text-gray-800 leading-relaxed font-sans shadow-inner"
                                     x-html="selectedLog?.body">
                                </div>

                                <!-- Varsa Buton / Aksiyon Linki -->
                                <template x-if="selectedLog?.action_url">
                                    <div class="pt-2 flex items-center justify-between p-3 rounded-xl bg-indigo-50/60 border border-indigo-100 text-xs">
                                        <div class="flex items-center gap-2 text-indigo-900 font-medium">
                                            <svg class="w-4 h-4 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"></path></svg>
                                            <span>Sisteme Git Bağlantısı:</span>
                                            <span class="font-mono text-[11px] text-indigo-700 truncate max-w-md" x-text="selectedLog?.action_url"></span>
                                        </div>
                                        <a :href="selectedLog?.action_url" target="_blank" class="px-3 py-1 rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white font-bold transition shadow-xs">
                                            Aç &rarr;
                                        </a>
                                    </div>
                                </template>
                            </div>

                            <!-- Modal Alt Kapat Butonu -->
                            <div class="bg-gray-50 px-6 py-4 border-t border-gray-100 flex justify-end">
                                <button type="button" @click="modalOpen = false" class="px-5 py-2 rounded-xl bg-gray-200 hover:bg-gray-300 text-gray-700 font-bold text-xs transition">
                                    Kapat
                                </button>
                            </div>

                        </div>
                    </div>
                </div>

            </div>

        </div>

        <!-- GEÇMİŞ LOGLARI TEMİZLEME MODALI -->
        <div x-cloak x-show="showClearModal" class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="clear-modal-title" role="dialog" aria-modal="true">
            <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                <!-- Arka Plan Karartması -->
                <div x-show="showClearModal" 
                     x-transition:enter="ease-out duration-300" 
                     x-transition:enter-start="opacity-0" 
                     x-transition:enter-end="opacity-100" 
                     x-transition:leave="ease-in duration-200" 
                     x-transition:leave-start="opacity-100" 
                     x-transition:leave-end="opacity-0" 
                     class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs transition-opacity" 
                     @click="showClearModal = false"></div>

                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

                <!-- Modal Kutusu -->
                <div x-show="showClearModal" 
                     x-transition:enter="ease-out duration-300" 
                     x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" 
                     x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100" 
                     x-transition:leave="ease-in duration-200" 
                     x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100" 
                     x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" 
                     class="inline-block align-bottom bg-white rounded-3xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full border border-slate-100">
                    
                    <form method="POST" action="{{ route('settings.notification_logs.clear') }}">
                        @csrf
                        <div class="bg-white p-6 sm:p-7">
                            <div class="flex items-start gap-4">
                                <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-2xl bg-rose-50 text-rose-600 sm:mx-0 border border-rose-100">
                                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                    </svg>
                                </div>
                                <div class="mt-0 text-left flex-1">
                                    <h3 class="text-base font-extrabold text-slate-900" id="clear-modal-title">
                                        Geçmiş Bildirim Loglarını Temizle
                                    </h3>
                                    <p class="text-xs text-slate-500 mt-1">
                                        Sistemde biriken denetim ve gönderim loglarını kalıcı olarak silebilirsiniz. Lütfen silmek istediğiniz kapsamı seçiniz.
                                    </p>
                                </div>
                            </div>

                            <!-- Temizleme Kapsamı Seçimi -->
                            <div class="mt-6 space-y-3">
                                <label class="block text-xs font-bold uppercase tracking-wider text-slate-600 mb-2">
                                    Temizleme Kapsamı
                                </label>
                                
                                <label class="flex items-center gap-3 p-3 rounded-xl border border-slate-200 hover:bg-slate-50 cursor-pointer transition">
                                    <input type="radio" name="period" value="all" class="text-rose-600 focus:ring-rose-500 h-4 w-4" checked>
                                    <div>
                                        <div class="text-xs font-bold text-slate-900">Tüm Geçmiş Logları Temizle</div>
                                        <div class="text-[11px] text-slate-500">Mevcut tüm bildirim ve e-posta denetim kayıtları kalıcı olarak silinir.</div>
                                    </div>
                                </label>

                                <label class="flex items-center gap-3 p-3 rounded-xl border border-slate-200 hover:bg-slate-50 cursor-pointer transition">
                                    <input type="radio" name="period" value="30_days" class="text-rose-600 focus:ring-rose-500 h-4 w-4">
                                    <div>
                                        <div class="text-xs font-bold text-slate-900">30 Günden Eski Logları Temizle</div>
                                        <div class="text-[11px] text-slate-500">Son 30 günün kayıtları korunur, daha eskiler silinir.</div>
                                    </div>
                                </label>

                                <label class="flex items-center gap-3 p-3 rounded-xl border border-slate-200 hover:bg-slate-50 cursor-pointer transition">
                                    <input type="radio" name="period" value="90_days" class="text-rose-600 focus:ring-rose-500 h-4 w-4">
                                    <div>
                                        <div class="text-xs font-bold text-slate-900">90 Günden Eski Logları Temizle</div>
                                        <div class="text-[11px] text-slate-500">Son 90 günün kayıtları korunur, daha eskiler silinir.</div>
                                    </div>
                                </label>

                                <!-- Ekstra Seçenek: Zil Bildirimleri Tablosu -->
                                <div class="pt-3 border-t border-slate-100">
                                    <label class="flex items-start gap-2.5 cursor-pointer">
                                        <input type="checkbox" name="include_in_app" value="1" class="mt-0.5 rounded border-slate-300 text-rose-600 focus:ring-rose-500">
                                        <span class="text-xs text-slate-600">
                                            <strong class="text-slate-800">Zil Bildirimleri Tablosunu da Dahil Et:</strong> Kullanıcıların zil ikonunda görünen sistem içi bildirim geçmişini de seçilen kapsamda temizle.
                                        </span>
                                    </label>
                                </div>

                                <div class="p-3 bg-amber-50 rounded-xl border border-amber-200/80 text-[11px] text-amber-800 flex items-start gap-2">
                                    <svg class="w-4 h-4 text-amber-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                                    <span>Bu işlem geri alınamaz. Silinen gönderim logları raporlardan ve geçmiş listelerinden kaldırılacaktır.</span>
                                </div>
                            </div>
                        </div>

                        <div class="bg-slate-50 px-6 py-4 flex flex-col sm:flex-row-reverse gap-2 sm:gap-3 rounded-b-3xl border-t border-slate-100">
                            <button type="submit" class="w-full sm:w-auto inline-flex justify-center items-center px-5 py-2.5 bg-rose-600 hover:bg-rose-700 text-white text-xs font-bold rounded-xl shadow-sm transition">
                                <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                Logları Kalıcı Olarak Temizle
                            </button>
                            <button type="button" @click="showClearModal = false" class="w-full sm:w-auto inline-flex justify-center items-center px-4 py-2.5 bg-white hover:bg-slate-100 text-slate-700 text-xs font-bold rounded-xl border border-slate-200 shadow-xs transition">
                                Vazgeç
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>

