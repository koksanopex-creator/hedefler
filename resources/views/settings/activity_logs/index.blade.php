@section('title', 'Sistem Günlüğü & Denetim İzi (Audit Logs)')
<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h2 class="font-extrabold text-2xl text-transparent bg-clip-text bg-gradient-to-r from-indigo-700 via-purple-700 to-pink-600 tracking-tight flex items-center gap-2">
                    <svg class="w-7 h-7 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path>
                    </svg>
                    {{ __('Sistem Günlüğü & Denetim İzi') }}
                </h2>
                <p class="text-xs text-gray-500 mt-1">Sistem genelinde gerçekleştirilen tüm işlemlerin, veri girişlerinin, onayların ve kullanıcı hareketlerinin denetim kayıtları.</p>
            </div>
            <div class="flex items-center flex-wrap gap-2.5">
                <!-- Logları Arşivle (Excel İndir) -->
                <a href="{{ route('settings.activity_logs.archive', request()->all()) }}" 
                   class="inline-flex items-center px-4 py-2 rounded-xl text-xs font-bold text-emerald-700 bg-emerald-50 border border-emerald-200 hover:bg-emerald-100 transition shadow-xs"
                   title="Filtrelenmiş veya tüm sistem denetim kayıtlarını Excel (.xlsx) olarak indir">
                    <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                    Logları Arşivle (Excel)
                </a>

                <!-- Logları Temizle (Sadece Sistem Yöneticisi) -->
                @if(!auth()->user()?->isYonetim() && (auth()->user()?->role?->slug === 'sistem_yoneticisi' || auth()->user()?->id === 1 || auth()->user()?->hasPermission('settings.manage')))
                    <button type="button" 
                            @click="showClearModal = true"
                            class="inline-flex items-center px-4 py-2 rounded-xl text-xs font-bold text-rose-700 bg-rose-50 border border-rose-200 hover:bg-rose-100 transition shadow-xs cursor-pointer">
                        <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                        </svg>
                        Logları Temizle
                    </button>
                @endif

                <a href="{{ route('settings.notification_logs.index') }}" class="inline-flex items-center px-4 py-2 rounded-xl text-xs font-bold text-indigo-700 bg-indigo-50 border border-indigo-200 hover:bg-indigo-100 transition shadow-xs">
                    <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path></svg>
                    Bildirim Logları &rarr;
                </a>
                <a href="{{ route('settings.index') }}" class="inline-flex items-center px-4 py-2 rounded-xl text-xs font-bold text-gray-700 bg-white border border-gray-200 hover:bg-gray-50 transition shadow-xs">
                    Ayarlar Menüsü
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-8 bg-slate-50/50 min-h-screen" 
         x-data="{ 
             showLogModal: false, 
             showClearModal: false,
             activeLogDetail: { date: '', user: '', action: '', badgeClass: '', description: '', ip: '', model: '' } 
         }">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <!-- BİLDİRİM / ALERT MESAJLARI -->
            @if(session('success'))
                <div class="p-4 rounded-2xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs font-bold flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <svg class="w-5 h-5 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        <span>{{ session('success') }}</span>
                    </div>
                </div>
            @endif
            @if(session('error'))
                <div class="p-4 rounded-2xl bg-rose-50 border border-rose-200 text-rose-800 text-xs font-bold flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <svg class="w-5 h-5 text-rose-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        <span>{{ session('error') }}</span>
                    </div>
                </div>
            @endif

            <!-- ÜST İSTATİSTİK KARTLARI -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs flex items-center justify-between">
                    <div>
                        <div class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Toplam İşlem Kaydı</div>
                        <div class="text-2xl font-black text-slate-800 mt-1">{{ number_format($totalCount, 0, ',', '.') }}</div>
                    </div>
                    <div class="w-12 h-12 rounded-2xl bg-indigo-50 text-indigo-600 flex items-center justify-center">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    </div>
                </div>

                <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs flex items-center justify-between">
                    <div>
                        <div class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Bugünkü İşlemler</div>
                        <div class="text-2xl font-black text-emerald-600 mt-1">{{ number_format($todayCount, 0, ',', '.') }}</div>
                    </div>
                    <div class="w-12 h-12 rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                </div>

                <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs flex items-center justify-between">
                    <div>
                        <div class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Aktif Kullanıcı</div>
                        <div class="text-2xl font-black text-purple-600 mt-1">{{ number_format($activeUsersCount, 0, ',', '.') }}</div>
                    </div>
                    <div class="w-12 h-12 rounded-2xl bg-purple-50 text-purple-600 flex items-center justify-center">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                    </div>
                </div>
            </div>

            <!-- FİLTRELEME & ARAMA KARTI -->
            <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs">
                <form method="GET" action="{{ route('settings.activity_logs.index') }}" class="space-y-4">
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3">
                        <!-- Serbest Kelime Arama -->
                        <div class="lg:col-span-2">
                            <label class="block text-[11px] font-bold text-slate-600 uppercase tracking-wider mb-1">Arama</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                                </div>
                                <input type="text" 
                                       name="search" 
                                       value="{{ $search }}" 
                                       placeholder="Açıklama, işlem veya IP adresi ara..." 
                                       class="w-full pl-9 pr-3 py-2 text-xs rounded-xl border border-slate-200 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 bg-white placeholder-slate-400">
                            </div>
                        </div>

                        <!-- Kullanıcı Filtresi -->
                        <div>
                            <label class="block text-[11px] font-bold text-slate-600 uppercase tracking-wider mb-1">Kullanıcı</label>
                            <select name="user_id" class="w-full text-xs py-2 px-3 rounded-xl border border-slate-200 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 bg-white text-slate-700">
                                <option value="">Tüm Kullanıcılar</option>
                                <option value="system" {{ $userId === 'system' ? 'selected' : '' }}>Sistem (Otomatik)</option>
                                @foreach($filterUsers as $u)
                                    <option value="{{ $u->id }}" {{ (string)$userId === (string)$u->id ? 'selected' : '' }}>
                                        {{ $u->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <!-- İşlem (Aksiyon) Filtresi -->
                        <div>
                            <label class="block text-[11px] font-bold text-slate-600 uppercase tracking-wider mb-1">İşlem Türü</label>
                            <select name="action" class="w-full text-xs py-2 px-3 rounded-xl border border-slate-200 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 bg-white text-slate-700">
                                <option value="">Tüm İşlemler</option>
                                @foreach($filterActions as $act)
                                    <option value="{{ $act['slug'] }}" {{ $action === $act['slug'] ? 'selected' : '' }}>
                                        {{ $act['label'] }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Tarih Aralığı -->
                        <div>
                            <label class="block text-[11px] font-bold text-slate-600 uppercase tracking-wider mb-1">Başlangıç Tarihi</label>
                            <input type="date" 
                                   name="date_from" 
                                   value="{{ $dateFrom }}" 
                                   class="w-full text-xs py-2 px-3 rounded-xl border border-slate-200 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 bg-white text-slate-700">
                        </div>
                    </div>

                    <div class="flex flex-col sm:flex-row items-center justify-between gap-3 pt-2 border-t border-slate-100">
                        <div class="flex items-center gap-2 w-full sm:w-auto">
                            <label class="text-[11px] font-bold text-slate-600 uppercase tracking-wider">Bitiş Tarihi:</label>
                            <input type="date" 
                                   name="date_to" 
                                   value="{{ $dateTo }}" 
                                   class="text-xs py-1.5 px-3 rounded-xl border border-slate-200 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 bg-white text-slate-700">
                        </div>

                        <div class="flex items-center gap-2 w-full sm:w-auto justify-end">
                            @if($search || $userId !== null && $userId !== '' || $action || $dateFrom || $dateTo)
                                <a href="{{ route('settings.activity_logs.index') }}" 
                                   class="inline-flex items-center px-4 py-2 rounded-xl text-xs font-bold text-slate-600 bg-slate-100 hover:bg-slate-200 transition">
                                    Filtreleri Temizle
                                </a>
                            @endif
                            <button type="submit" 
                                    class="inline-flex items-center px-5 py-2 rounded-xl text-xs font-bold text-white bg-indigo-600 hover:bg-indigo-700 transition shadow-xs cursor-pointer">
                                <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/></svg>
                                Filtrele
                            </button>
                        </div>
                    </div>
                </form>
            </div>

            <!-- LOG TABLOSU -->
            <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
                <div class="p-4 border-b border-slate-100 bg-slate-50/50 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                    <div class="flex items-center gap-2">
                        <span class="text-xs font-bold text-slate-700">Denetim İzi Kayıtları</span>
                        <span class="text-xs font-semibold px-2 py-0.5 rounded-full bg-slate-200 text-slate-700">
                            Sayfa Başına 20 Kayıt
                        </span>
                    </div>
                    <div class="text-xs text-slate-500 font-medium">
                        Toplam <span class="font-bold text-slate-700">{{ $logs->total() }}</span> kayıttan 
                        <span class="font-bold text-slate-700">{{ $logs->firstItem() ?? 0 }} - {{ $logs->lastItem() ?? 0 }}</span> arası gösteriliyor
                    </div>
                </div>

                @if($logs->count() > 0)
                    <div class="overflow-x-auto w-full">
                        <table class="min-w-full divide-y divide-slate-100 text-left text-xs">
                            <thead class="bg-slate-50 text-slate-500 font-bold uppercase tracking-wider text-[11px]">
                                <tr>
                                    <th class="px-5 py-3.5 whitespace-nowrap">Tarih / Saat</th>
                                    <th class="px-5 py-3.5 whitespace-nowrap">Kullanıcı</th>
                                    <th class="px-5 py-3.5 whitespace-nowrap">İşlem</th>
                                    <th class="px-5 py-3.5 whitespace-nowrap">İlişkili Kayıt / Hedef</th>
                                    <th class="px-5 py-3.5 min-w-[280px]">Detay / Açıklama</th>
                                    <th class="px-5 py-3.5 whitespace-nowrap">IP Adresi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 bg-white">
                                @foreach($logs as $log)
                                    @php
                                        $desc = (string) $log->description;
                                        $isLong = strlen($desc) > 120 || str_contains($desc, "\n") || str_contains($desc, '•');
                                        $summaryLine = $desc;
                                        if (str_contains($desc, "\n")) {
                                            $lines = explode("\n", $desc);
                                            $summaryLine = trim($lines[0]);
                                        }
                                        $diffCount = substr_count($desc, '•');

                                        // Varsayılan Model Etiketi
                                        $modelName = 'Genel';
                                        if ($log->model_type) {
                                            $shortModel = class_basename($log->model_type);
                                            $shortModel = preg_replace('/M$/', '', $shortModel);
                                            $modelName = $shortModel . ($log->model_id ? " #{$log->model_id}" : '');
                                        }
                                    @endphp
                                    <tr class="hover:bg-slate-50/70 transition-colors">
                                        <!-- Tarih -->
                                        <td class="px-5 py-3.5 whitespace-nowrap">
                                            <div class="font-bold text-slate-800">{{ $log->created_at->format('d.m.Y H:i:s') }}</div>
                                            <div class="text-[10px] text-slate-400 mt-0.5">{{ $log->created_at->diffForHumans() }}</div>
                                        </td>

                                        <!-- Kullanıcı -->
                                        <td class="px-5 py-3.5 whitespace-nowrap">
                                            @if($log->user)
                                                <div class="flex items-center gap-2">
                                                    <div class="w-7 h-7 rounded-full bg-indigo-50 border border-indigo-200 text-indigo-700 font-bold flex items-center justify-center text-xs shrink-0">
                                                        {{ mb_substr($log->user->name, 0, 1) }}
                                                    </div>
                                                    <div>
                                                        <a href="{{ route('users.profile', $log->user->id) }}" class="font-bold text-slate-800 hover:text-indigo-600 hover:underline transition-colors block">
                                                            {{ $log->user->name }}
                                                        </a>
                                                        <span class="text-[10px] text-slate-400 block">{{ $log->user->email }}</span>
                                                    </div>
                                                </div>
                                            @else
                                                <span class="inline-flex items-center gap-1 text-slate-400 italic text-[11px]">
                                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                                                    Sistem (Otomatik)
                                                </span>
                                            @endif
                                        </td>

                                        <!-- İşlem -->
                                        <td class="px-5 py-3.5 whitespace-nowrap">
                                            <span class="px-2.5 py-1 inline-flex text-xs leading-4 font-bold rounded-full border {{ $log->action_badge_class }}">
                                                {{ $log->action_label }}
                                            </span>
                                        </td>

                                        <!-- İlişkili Kayıt / Hedef -->
                                        <td class="px-5 py-3.5 whitespace-nowrap">
                                            @if($log->model_type === \App\Models\FormEntryM::class && $log->model_id)
                                                @php
                                                    $entry = $relatedFormEntries[$log->model_id] ?? null;
                                                @endphp
                                                @if($entry)
                                                    <div class="flex flex-col gap-0.5">
                                                        <a href="{{ route('form-entries.show', $entry->id) }}" class="inline-flex items-center gap-1.5 font-bold text-indigo-600 hover:text-indigo-800 hover:underline">
                                                            <svg class="w-3.5 h-3.5 text-indigo-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                                            <span>{{ $entry->template?->name ?? 'Dinamik Form' }}</span>
                                                        </a>
                                                        <span class="text-[10px] text-slate-400">#{{ $entry->id }} &bull; {{ $entry->entry_date ? $entry->entry_date->format('d.m.Y') : '-' }} &bull; {{ $entry->user?->name ?? 'Kullanıcı' }}</span>
                                                    </div>
                                                @else
                                                    <div class="flex flex-col gap-0.5">
                                                        <div class="inline-flex items-center gap-1.5">
                                                            <span class="px-1.5 py-0.5 rounded text-[10px] font-bold bg-rose-50 text-rose-700 border border-rose-200">Silinmiş Kayıt</span>
                                                            <span class="font-bold text-slate-700">Form #{{ $log->model_id }}</span>
                                                        </div>
                                                        <span class="text-[10px] text-slate-400">Veri tabanından silinmiş</span>
                                                    </div>
                                                @endif
                                            @elseif($log->model_type === \App\Models\KpiTargetM::class && $log->model_id)
                                                @php
                                                    $target = $relatedKpiTargets[$log->model_id] ?? null;
                                                @endphp
                                                @if($target)
                                                    <div class="flex flex-col gap-0.5">
                                                        <a href="{{ route('kpi-targets.progress', $target->id) }}" class="inline-flex items-center gap-1.5 font-bold text-blue-600 hover:text-blue-800 hover:underline">
                                                            <svg class="w-3.5 h-3.5 text-blue-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>
                                                            <span>{{ $target->title }}</span>
                                                        </a>
                                                        <span class="text-[10px] text-slate-400">KPI #{{ $target->id }} &bull; {{ $target->target_year }} &bull; {{ $target->assignee_name }}</span>
                                                    </div>
                                                @else
                                                    <div class="flex flex-col gap-0.5">
                                                        <div class="inline-flex items-center gap-1.5">
                                                            <span class="px-1.5 py-0.5 rounded text-[10px] font-bold bg-rose-50 text-rose-700 border border-rose-200">Silinmiş Hedef</span>
                                                            <span class="font-bold text-slate-700">KPI #{{ $log->model_id }}</span>
                                                        </div>
                                                        <span class="text-[10px] text-slate-400">Hedef kaydı silinmiş</span>
                                                    </div>
                                                @endif
                                            @elseif($log->model_type === \App\Models\UserM::class && $log->model_id)
                                                @php
                                                    $targetUser = $relatedUsers[$log->model_id] ?? null;
                                                @endphp
                                                @if($targetUser)
                                                    <a href="{{ route('users.profile', $targetUser->id) }}" class="inline-flex items-center gap-1.5 font-bold text-slate-800 hover:text-indigo-600 hover:underline">
                                                        <svg class="w-3.5 h-3.5 text-slate-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                                                        <span>{{ $targetUser->name }}</span>
                                                    </a>
                                                @else
                                                    <span class="px-1.5 py-0.5 rounded text-[10px] font-bold bg-rose-50 text-rose-700 border border-rose-200">Silinmiş Kullanıcı (#{{ $log->model_id }})</span>
                                                @endif
                                            @elseif($log->model_type === \App\Models\FormTemplateM::class && $log->model_id)
                                                @php
                                                    $template = $relatedFormTemplates[$log->model_id] ?? null;
                                                @endphp
                                                @if($template)
                                                    <a href="{{ route('form-templates.edit', $template->id) }}" class="inline-flex items-center gap-1.5 font-bold text-purple-600 hover:text-purple-800 hover:underline">
                                                        <span>{{ $template->name }}</span>
                                                    </a>
                                                @else
                                                    <span class="px-1.5 py-0.5 rounded text-[10px] font-bold bg-rose-50 text-rose-700 border border-rose-200">Silinmiş Şablon (#{{ $log->model_id }})</span>
                                                @endif
                                            @else
                                                <span class="px-2 py-0.5 rounded text-[11px] font-bold bg-slate-100 text-slate-600 border border-slate-200">
                                                    {{ $modelName }}
                                                </span>
                                            @endif
                                        </td>

                                        <!-- Detay / Açıklama -->
                                        <td class="px-5 py-3.5 text-slate-700 max-w-lg">
                                            @if(!$isLong)
                                                <div class="font-medium text-slate-800 break-words whitespace-normal leading-relaxed text-xs">{{ $desc ?: '-' }}</div>
                                            @else
                                                <div class="space-y-1">
                                                    <div class="font-medium text-slate-800 break-words whitespace-normal leading-relaxed text-xs">{{ $summaryLine }}</div>
                                                    <div class="flex items-center gap-2 pt-0.5">
                                                        @if($diffCount > 0)
                                                            <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-bold bg-indigo-50 text-indigo-700 border border-indigo-100">
                                                                {{ $diffCount }} Alan Değişikliği
                                                            </span>
                                                        @endif
                                                        <button type="button"
                                                                @click="activeLogDetail = {
                                                                    date: '{{ $log->created_at->format('d.m.Y H:i:s') }}',
                                                                    user: '{{ $log->user->name ?? 'Sistem' }}',
                                                                    action: '{{ $log->action_label }}',
                                                                    badgeClass: '{{ $log->action_badge_class }}',
                                                                    description: @js($log->description),
                                                                    ip: '{{ $log->ip_address ?? '-' }}',
                                                                    model: '{{ $modelName }}'
                                                                }; showLogModal = true;"
                                                                class="inline-flex items-center gap-1 text-[11px] font-bold text-indigo-600 hover:text-indigo-800 hover:underline cursor-pointer">
                                                            <svg class="w-3.5 h-3.5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                                            <span>Detayları İncele</span>
                                                        </button>
                                                    </div>
                                                </div>
                                            @endif
                                        </td>

                                        <!-- IP Adresi -->
                                        <td class="px-5 py-3.5 whitespace-nowrap text-[11px] font-mono text-slate-500">
                                            {{ $log->ip_address ?: '-' }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <!-- SAYFALAMA NAVİGASYONU -->
                    <div class="p-4 border-t border-slate-100 bg-white">
                        {{ $logs->links() }}
                    </div>
                @else
                    <div class="p-12 text-center text-slate-500">
                        <div class="flex flex-col items-center justify-center gap-2">
                            <div class="w-12 h-12 rounded-2xl bg-slate-100 flex items-center justify-center text-slate-400">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                            </div>
                            <h3 class="text-sm font-bold text-slate-700">Kayıt Bulunamadı</h3>
                            <p class="text-xs text-slate-400">Seçilen filtre kriterlerine uygun herhangi bir denetim kaydı bulunamadı.</p>
                            @if($search || $userId !== null && $userId !== '' || $action || $dateFrom || $dateTo)
                                <a href="{{ route('settings.activity_logs.index') }}" class="mt-2 text-xs font-bold text-indigo-600 hover:text-indigo-800 underline">
                                    Filtreleri Temizle
                                </a>
                            @endif
                        </div>
                    </div>
                @endif
            </div>

        </div>

        <!-- İŞLEM DETAY MODALI -->
        <div x-show="showLogModal" x-cloak
            class="fixed inset-0 z-50 overflow-y-auto"
            aria-labelledby="activity-log-modal-title" role="dialog" aria-modal="true"
            style="display: none;">
            <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                <div x-show="showLogModal"
                    x-transition:enter="ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                    x-transition:leave="ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
                    class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs transition-opacity"
                    @click="showLogModal = false"></div>

                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

                <div x-show="showLogModal"
                    x-transition:enter="ease-out duration-200" x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                    x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                    x-transition:leave="ease-in duration-150" x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                    x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                    class="inline-block align-bottom bg-white rounded-3xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full border border-slate-100">

                    <div class="bg-gradient-to-r from-slate-50 via-indigo-50/30 to-white px-6 py-4 border-b border-slate-100 flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-2xl bg-indigo-50 text-indigo-600 flex items-center justify-center shadow-2xs">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-base font-extrabold text-slate-800" id="activity-log-modal-title">Denetim İzi & İşlem Detayı</h3>
                                <p class="text-xs text-slate-500 mt-0.5">
                                    <span x-text="activeLogDetail.user" class="font-bold text-slate-700"></span> &bull; 
                                    <span x-text="activeLogDetail.date"></span>
                                </p>
                            </div>
                        </div>
                        <div class="flex items-center gap-2">
                            <span :class="activeLogDetail.badgeClass" class="px-2.5 py-1 text-xs font-bold rounded-full border" x-text="activeLogDetail.action"></span>
                            <button type="button" @click="showLogModal = false" class="p-2 text-slate-400 hover:text-slate-600 hover:bg-slate-100 rounded-xl transition-colors">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                        </div>
                    </div>

                    <div class="p-6 max-h-[60vh] overflow-y-auto space-y-4 bg-slate-50/50">
                        <div class="grid grid-cols-2 gap-3 text-xs bg-white p-3 rounded-2xl border border-slate-200/80">
                            <div>
                                <span class="text-slate-400 font-bold block">İlgili Model / Kayıt:</span>
                                <span class="font-semibold text-slate-800" x-text="activeLogDetail.model"></span>
                            </div>
                            <div>
                                <span class="text-slate-400 font-bold block">IP Adresi:</span>
                                <span class="font-mono text-slate-700" x-text="activeLogDetail.ip"></span>
                            </div>
                        </div>

                        <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-2xs">
                            <div class="text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-2">Kayıtlı İşlem İçeriği & Değişiklikler</div>
                            <div class="text-xs sm:text-sm font-medium text-slate-800 whitespace-pre-wrap leading-relaxed font-mono bg-slate-50 p-3 rounded-xl border border-slate-100 overflow-x-auto select-text" x-text="activeLogDetail.description"></div>
                        </div>
                    </div>

                    <div class="bg-white px-6 py-4 border-t border-slate-100 flex justify-between items-center">
                        <button type="button"
                            @click="navigator.clipboard.writeText(activeLogDetail.description); alert('İşlem detayı panoya kopyalandı.');"
                            class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl text-xs font-bold text-slate-700 hover:text-indigo-600 hover:bg-indigo-50 border border-slate-200 transition-colors">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3"/></svg>
                            <span>Detayı Kopyala</span>
                        </button>
                        <button type="button" @click="showLogModal = false"
                            class="px-5 py-2 bg-slate-800 hover:bg-slate-900 text-white rounded-xl text-xs font-bold transition-all shadow-xs cursor-pointer">
                            Kapat
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- LOGLARI TEMİZLEME ONAY MODALI -->
        <div x-show="showClearModal" x-cloak
            class="fixed inset-0 z-50 overflow-y-auto"
            aria-labelledby="clear-log-modal-title" role="dialog" aria-modal="true"
            style="display: none;">
            <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                <div x-show="showClearModal"
                    x-transition:enter="ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                    x-transition:leave="ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
                    class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs transition-opacity"
                    @click="showClearModal = false"></div>

                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

                <div x-show="showClearModal"
                    x-transition:enter="ease-out duration-200" x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                    x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                    x-transition:leave="ease-in duration-150" x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                    x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                    class="inline-block align-bottom bg-white rounded-3xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full border border-slate-100">

                    <form method="POST" action="{{ route('settings.activity_logs.clear') }}">
                        @csrf
                        <div class="bg-gradient-to-r from-rose-50 via-red-50/40 to-white px-6 py-5 border-b border-rose-100 flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <div class="w-11 h-11 rounded-2xl bg-rose-100 text-rose-600 flex items-center justify-center shadow-xs">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="text-base font-extrabold text-slate-800" id="clear-log-modal-title">Denetim Günlüğünü Temizle</h3>
                                    <p class="text-xs text-rose-600 font-semibold mt-0.5">Dikkat: Bu işlem kalıcıdır ve geri alınamaz!</p>
                                </div>
                            </div>
                            <button type="button" @click="showClearModal = false" class="p-2 text-slate-400 hover:text-slate-600 hover:bg-slate-100 rounded-xl transition-colors">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                        </div>

                        <div class="p-6 space-y-4">
                            <div class="p-3.5 bg-amber-50 border border-amber-200 rounded-2xl text-xs text-amber-900 flex items-start gap-3">
                                <svg class="w-5 h-5 text-amber-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                                <div>
                                    <span class="font-bold">Öneri:</span> Logları temizlemeden önce sistemin geçmiş kayıtlarını korumak için <strong>"Logları Arşivle (Excel)"</strong> butonunu kullanarak yedek alabilirsiniz.
                                </div>
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-2">Temizleme Kapsamı Seçiniz:</label>
                                <div class="space-y-2">
                                    <label class="flex items-center p-3 rounded-xl border border-slate-200 hover:bg-slate-50 cursor-pointer transition">
                                        <input type="radio" name="scope" value="30_days" checked class="text-rose-600 focus:ring-rose-500 h-4 w-4">
                                        <div class="ml-3">
                                            <div class="text-xs font-bold text-slate-800">Son 30 Günden Eski Kayıtları Temizle</div>
                                            <div class="text-[11px] text-slate-500">Son 1 aya ait denetim kayıtları korunur. (Tavsiye Edilen)</div>
                                        </div>
                                    </label>
                                    <label class="flex items-center p-3 rounded-xl border border-slate-200 hover:bg-slate-50 cursor-pointer transition">
                                        <input type="radio" name="scope" value="90_days" class="text-rose-600 focus:ring-rose-500 h-4 w-4">
                                        <div class="ml-3">
                                            <div class="text-xs font-bold text-slate-800">Son 90 Günden Eski Kayıtları Temizle</div>
                                            <div class="text-[11px] text-slate-500">Son 3 aya ait denetim kayıtları korunur.</div>
                                        </div>
                                    </label>
                                    <label class="flex items-center p-3 rounded-xl border border-slate-200 hover:bg-slate-50 cursor-pointer transition">
                                        <input type="radio" name="scope" value="180_days" class="text-rose-600 focus:ring-rose-500 h-4 w-4">
                                        <div class="ml-3">
                                            <div class="text-xs font-bold text-slate-800">Son 180 Günden Eski Kayıtları Temizle</div>
                                            <div class="text-[11px] text-slate-500">Son 6 aya ait denetim kayıtları korunur.</div>
                                        </div>
                                    </label>
                                    <label class="flex items-center p-3 rounded-xl border border-rose-200 bg-rose-50/40 hover:bg-rose-50 cursor-pointer transition">
                                        <input type="radio" name="scope" value="all" class="text-rose-600 focus:ring-rose-500 h-4 w-4">
                                        <div class="ml-3">
                                            <div class="text-xs font-bold text-rose-800">Tüm Zamanların Loglarını Temizle</div>
                                            <div class="text-[11px] text-rose-600">Sistemdeki tüm denetim izi kayıtları tamamen silinir.</div>
                                        </div>
                                    </label>
                                </div>
                            </div>
                        </div>

                        <div class="bg-slate-50 px-6 py-4 border-t border-slate-100 flex justify-end items-center gap-2">
                            <button type="button" @click="showClearModal = false"
                                class="px-4 py-2 bg-white border border-slate-200 hover:bg-slate-100 text-slate-700 rounded-xl text-xs font-bold transition">
                                İptal
                            </button>
                            <button type="submit"
                                onclick="return confirm('Seçili kapsamdaki log kayıtları kalıcı olarak silinecektir. Devam etmek istediğinize emin misiniz?');"
                                class="px-5 py-2 bg-rose-600 hover:bg-rose-700 text-white rounded-xl text-xs font-bold transition shadow-xs cursor-pointer">
                                Seçili Logları Sil
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

    </div>
</x-app-layout>
