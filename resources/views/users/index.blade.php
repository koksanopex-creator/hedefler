@section('title', 'Kullanıcılar')
<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            <div>
                <h2 class="font-bold text-2xl text-transparent bg-clip-text bg-gradient-to-r from-indigo-600 to-purple-600 leading-tight">
                    {{ __('Kullanıcı Yönetimi') }}
                </h2>
                <p class="text-xs text-gray-500 mt-1">Sistem yöneticileri, grup yöneticileri ve operasyonel ekiplerin hiyerarşik yönetimi.</p>
            </div>
            @if($canManage)
            <div class="flex flex-wrap items-center gap-3">
                <a href="{{ route('users.sync') }}" class="inline-flex items-center gap-1.5 px-4 py-2.5 bg-white/90 hover:bg-white text-indigo-700 border border-indigo-200 rounded-xl text-xs font-bold transition-all shadow-xs hover:shadow-sm">
                    <svg class="w-4 h-4 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 1121.21 7.89M21 3v5h-5"></path></svg>
                    Merkezi Senkronizasyon
                </a>

                <a href="{{ route('users.applications.index') }}" class="inline-flex items-center gap-1.5 px-4 py-2.5 bg-white/90 hover:bg-white text-purple-700 border border-purple-200 rounded-xl text-xs font-bold transition-all shadow-xs hover:shadow-sm">
                    <svg class="w-4 h-4 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                    Başvuru Yönetimi
                </a>

                <a href="{{ route('users.create') }}" class="relative inline-flex items-center justify-center px-5 py-2.5 overflow-hidden font-medium text-white transition-all duration-300 bg-indigo-600 rounded-xl hover:bg-indigo-700 group hover:shadow-[0_0_20px_rgba(79,70,229,0.4)] text-xs">
                    <span class="absolute w-0 h-0 transition-all duration-500 ease-out bg-white rounded-full group-hover:w-56 group-hover:h-56 opacity-10"></span>
                    <span class="relative flex items-center gap-1.5">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                        Yeni Kullanıcı
                    </span>
                </a>
            </div>
            @endif
        </div>
    </x-slot>

    @php
        $allUsersSearchText = $users->map(fn($u) => $u->name . ' ' . $u->email . ' ' . ($u->role->name ?? '') . ' ' . ($u->group->name ?? ''))->join(' | ');
        $systemUsersSearchText = $systemUsers->map(fn($u) => $u->name . ' ' . $u->email . ' ' . ($u->role->name ?? ''))->join(' | ');
        $unassignedSearchText = $unassignedUsers->map(fn($u) => $u->name . ' ' . $u->email . ' ' . ($u->role->name ?? ''))->join(' | ');
    @endphp

    <!-- Glassmorphism Background Wrapper -->
    <div class="relative py-8 min-h-[calc(100vh-65px)] bg-slate-50/50" 
         x-data="{ 
             view: 'grouped', 
             searchQuery: '',
             deleteUserId: null, 
             deleteUserName: '', 
             deleteUserEmail: '', 
             showDeleteModal: false,

             normalize(text) {
                 if (!text) return '';
                 return text.toString()
                     .toLocaleLowerCase('tr-TR')
                     .replace(/ı/g, 'i')
                     .replace(/ğ/g, 'g')
                     .replace(/ü/g, 'u')
                     .replace(/ş/g, 's')
                     .replace(/ö/g, 'o')
                     .replace(/ç/g, 'c')
                     .trim();
             },

             matches(text) {
                 if (!this.searchQuery.trim()) return true;
                 const q = this.normalize(this.searchQuery);
                 return this.normalize(text).includes(q);
             }
         }"
         @open-user-delete-modal.window="deleteUserId = $event.detail.id; deleteUserName = $event.detail.name; deleteUserEmail = $event.detail.email; showDeleteModal = true">
        <!-- Background Decorative Blobs -->
        <div class="absolute top-20 left-20 w-96 h-96 bg-purple-300 rounded-full mix-blend-multiply filter blur-[80px] opacity-30 animate-pulse pointer-events-none"></div>
        <div class="absolute top-40 right-20 w-96 h-96 bg-indigo-300 rounded-full mix-blend-multiply filter blur-[80px] opacity-30 animate-pulse pointer-events-none" style="animation-delay: 2s;"></div>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10 space-y-6">
            
            @if(session('success'))
                <div class="backdrop-blur-md bg-green-50/80 border border-green-200/50 text-green-700 px-6 py-4 rounded-2xl shadow-sm flex items-center gap-3">
                    <svg class="w-6 h-6 text-green-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    <span>{{ session('success') }}</span>
                </div>
            @endif

            @if(session('error'))
                <div class="backdrop-blur-md bg-rose-50/80 border border-rose-200/50 text-rose-700 px-6 py-4 rounded-2xl shadow-sm flex items-center gap-3">
                    <svg class="w-6 h-6 text-rose-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    <span>{{ session('error') }}</span>
                </div>
            @endif

            <!-- 1. ANLIK CANLI ARAMA ÇUBUĞU (LIVE INSTANT SEARCH) -->
            <div class="bg-white/80 backdrop-blur-xl border border-indigo-100/80 shadow-[0_4px_20px_rgba(99,102,241,0.06)] rounded-2xl p-3 sm:p-4">
                <div class="relative flex items-center">
                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-indigo-500">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </div>
                    <input type="text"
                           x-model="searchQuery"
                           placeholder="Kullanıcı adı, e-posta, grup veya rol ara... (Örn: Mithat, Erhan, Aytaç, Yönetici)"
                           class="w-full pl-11 pr-28 py-2.5 bg-slate-50/80 hover:bg-white focus:bg-white border border-slate-200 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 rounded-xl text-sm font-medium text-slate-800 placeholder-slate-400 transition-all shadow-inner"
                    >
                    <div class="absolute inset-y-0 right-0 pr-2 flex items-center gap-1.5">
                        <button type="button" 
                                x-show="searchQuery.trim().length > 0" 
                                @click="searchQuery = ''" 
                                class="p-1 text-slate-400 hover:text-slate-600 rounded-lg hover:bg-slate-200/60 transition cursor-pointer"
                                title="Aramayı Temizle">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                        <span x-show="searchQuery.trim().length > 0" class="text-[11px] font-bold text-indigo-600 bg-indigo-50 border border-indigo-100 px-2 py-0.5 rounded-lg" x-text="'Filtreleniyor'"></span>
                    </div>
                </div>
            </div>

            <!-- 2. Mini İstatistik ve Görünüm Seçici -->
            <div class="flex flex-col sm:flex-row justify-between items-center gap-4 bg-white/70 backdrop-blur-xl border border-white/60 p-4 rounded-2xl shadow-sm">
                <!-- Sol: Hızlı İstatistikler -->
                <div class="flex flex-wrap items-center gap-3 text-xs">
                    <div class="flex items-center gap-2 bg-indigo-50/80 text-indigo-700 font-semibold px-3.5 py-2 rounded-xl border border-indigo-100">
                        <svg class="w-4 h-4 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                        <span>Toplam: <strong class="text-indigo-900">{{ $users->count() }}</strong> Kullanıcı</span>
                    </div>
                    @if($systemUsers->isNotEmpty())
                    <div class="flex items-center gap-2 bg-purple-50/80 text-purple-700 font-semibold px-3.5 py-2 rounded-xl border border-purple-100">
                        <svg class="w-4 h-4 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path></svg>
                        <span>Sistem & Yönetim: <strong class="text-purple-900">{{ $systemUsers->count() }}</strong></span>
                    </div>
                    @endif
                    <div class="flex items-center gap-2 bg-emerald-50/80 text-emerald-700 font-semibold px-3.5 py-2 rounded-xl border border-emerald-100">
                        <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
                        <span>Gruplar: <strong class="text-emerald-900">{{ $groups->count() }}</strong> Tanımlı Grup</span>
                    </div>
                </div>

                <!-- Sağ: Görünüm Değiştirme Butonları -->
                <div class="inline-flex p-1 bg-slate-100/80 rounded-xl border border-slate-200 shrink-0">
                    <button type="button" @click="view = 'grouped'" :class="view === 'grouped' ? 'bg-white text-indigo-700 shadow-sm font-bold' : 'text-gray-500 hover:text-gray-900 font-medium'" class="px-3 py-1.5 rounded-lg text-xs transition-all flex items-center gap-1.5 cursor-pointer">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
                        Gruplu Hiyerarşik Görünüm
                    </button>
                    <button type="button" @click="view = 'flat'" :class="view === 'flat' ? 'bg-white text-indigo-700 shadow-sm font-bold' : 'text-gray-500 hover:text-gray-900 font-medium'" class="px-3 py-1.5 rounded-lg text-xs transition-all flex items-center gap-1.5 cursor-pointer">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path></svg>
                        Düz Liste (Tümü)
                    </button>
                </div>
            </div>

            <!-- ARAMA SONUCU BOŞ DURUMU (EMPTY STATE) -->
            <div x-show="searchQuery.trim() && !matches(@js($allUsersSearchText))" class="p-12 text-center bg-white/80 backdrop-blur-xl rounded-3xl border border-slate-200 shadow-sm">
                <div class="w-14 h-14 rounded-2xl bg-indigo-50 text-indigo-500 mx-auto flex items-center justify-center mb-3">
                    <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                </div>
                <h3 class="text-base font-extrabold text-slate-800">Eşleşen Kullanıcı Bulunamadı</h3>
                <p class="text-xs text-slate-500 mt-1 max-w-md mx-auto">
                    "<span x-text="searchQuery" class="font-semibold text-indigo-600"></span>" aramasına uygun herhangi bir kullanıcı, yönetici veya grup kaydı bulunamadı.
                </p>
                <button type="button" @click="searchQuery = ''" class="mt-4 inline-flex items-center gap-1.5 px-4 py-2 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 text-xs font-bold rounded-xl transition cursor-pointer">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    Aramayı Temizle
                </button>
            </div>

            <!-- ========================================== -->
            <!-- 1) GRUPLU HİYERARŞİK GÖRÜNÜM (VARSAYILAN) -->
            <!-- ========================================== -->
            <div x-show="view === 'grouped'" class="space-y-6">
                
                @if($systemUsers->isNotEmpty())
                <!-- BÖLÜM 1: SİSTEM VE GENEL YÖNETİM (EN ÜSTTE) -->
                <div x-show="!searchQuery.trim() || matches(@js($systemUsersSearchText))"
                     class="backdrop-blur-xl bg-white/80 border border-purple-100 shadow-[0_8px_30px_rgb(0,0,0,0.04)] rounded-3xl overflow-hidden transition-all duration-300 hover:shadow-purple-500/5">
                    <!-- Kart Başlığı -->
                    <div class="bg-gradient-to-r from-purple-50/90 via-indigo-50/40 to-white px-6 py-4 border-b border-purple-100/60 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-2xl bg-gradient-to-br from-purple-600 to-indigo-600 flex items-center justify-center text-white shadow-md shadow-purple-500/20">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path></svg>
                            </div>
                            <div>
                                <h3 class="text-base font-extrabold text-gray-900 tracking-tight flex items-center gap-2">
                                    Sistem ve Genel Yönetim
                                    <span class="text-[11px] font-semibold text-purple-700 bg-purple-100/80 px-2.5 py-0.5 rounded-full">Grup Bağımsız</span>
                                </h3>
                                <p class="text-xs text-gray-500">Tüm sistemi yöneten Sistem Yöneticisi ve genel izleme yetkisine sahip Yönetim rolleri.</p>
                            </div>
                        </div>
                        <span class="text-xs font-bold text-purple-700 bg-white/80 border border-purple-200/60 px-3 py-1 rounded-xl shadow-xs">
                            {{ $systemUsers->count() }} Yönetici
                        </span>
                    </div>

                    <!-- Masaüstü Tablo (Yatay Scroll Yok, Tam Ekran Ekrana Sığar) -->
                    <div class="w-full hidden sm:block">
                        <table class="w-full text-left border-collapse table-auto">
                            <thead>
                                <tr class="bg-gray-50/50 border-b border-gray-100 text-xs font-bold text-gray-500 uppercase tracking-wider">
                                    <th class="px-4 py-3 text-left">Kullanıcı Adı</th>
                                    <th class="px-4 py-3 text-left">E-Posta</th>
                                    <th class="px-4 py-3 text-left">Gruptaki Konumu / Rol</th>
                                    @if($canManage)
                                        <th class="px-4 py-3 text-left">Sistem Rolü</th>
                                        <th class="px-4 py-3 text-left">Yetki Kapsamı</th>
                                        <th class="px-4 py-3 text-center w-24">Durum</th>
                                        <th class="px-4 py-3 text-right w-24">İşlemler</th>
                                    @endif
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100/60">
                                @forelse($systemUsers as $user)
                                    @php
                                        $isTargetSuperAdmin = ($user->role && $user->role->slug === 'sistem_yoneticisi') || $user->id === 1;
                                        $maskName = $isTargetSuperAdmin && !$isSuperAdmin;
                                        $displayName = $maskName ? 'Yönetim' : $user->name;
                                    @endphp
                                    <tr x-show="!searchQuery.trim() || matches(@js($user->name . ' ' . $user->email . ' ' . ($user->role->name ?? '')))" 
                                        class="hover:bg-purple-50/30 transition-colors">
                                        <td class="px-4 py-3.5">
                                            <div class="flex items-center gap-3">
                                                <div class="h-9 w-9 rounded-full bg-gradient-to-br from-purple-500 to-indigo-600 flex items-center justify-center text-white font-bold shadow-sm shrink-0">
                                                    {{ substr($displayName, 0, 1) }}
                                                </div>
                                                <div class="min-w-0">
                                                    @if($maskName)
                                                        <span class="text-sm font-bold text-gray-900 block truncate">{{ $displayName }}</span>
                                                    @else
                                                        <a href="{{ route('users.profile', $user->id) }}" class="text-sm font-bold text-gray-900 hover:text-indigo-600 transition-colors inline-flex items-center gap-1 group">
                                                            <span class="truncate">{{ $displayName }}</span>
                                                            <svg class="w-3.5 h-3.5 text-gray-400 group-hover:text-indigo-600 opacity-0 group-hover:opacity-100 transition-opacity shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                                                        </a>
                                                    @endif
                                                    <span class="text-[11px] font-medium text-purple-600 block mt-0.5">Üst Düzey Yönetici</span>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-4 py-3.5 text-xs text-gray-700 font-medium">
                                            <a href="mailto:{{ $user->email }}" class="hover:text-indigo-600 hover:underline truncate block max-w-xs">{{ $user->email }}</a>
                                        </td>
                                        <td class="px-4 py-3.5">
                                            <span class="px-2.5 py-1 inline-flex text-xs font-bold rounded-full bg-purple-50 text-purple-700 border border-purple-200">
                                                Sistem ve Genel Yönetim
                                            </span>
                                        </td>
                                        @if($canManage)
                                            <td class="px-4 py-3.5">
                                                @if($user->role?->slug === 'sistem_yoneticisi')
                                                    <span class="px-2.5 py-1 inline-flex text-xs font-bold rounded-full bg-purple-100 text-purple-800 border border-purple-200">
                                                        🛡️ {{ $user->role->name }}
                                                    </span>
                                                @else
                                                    <span class="px-2.5 py-1 inline-flex text-xs font-bold rounded-full bg-sky-100 text-sky-800 border border-sky-200">
                                                        👁️ {{ $user->role->name ?? 'Yönetim' }}
                                                    </span>
                                                @endif
                                            </td>
                                            <td class="px-4 py-3.5 text-xs text-gray-600 font-semibold">
                                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-slate-100 text-slate-700">
                                                    <svg class="w-3.5 h-3.5 text-slate-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                                    Tüm Sistem
                                                </span>
                                            </td>
                                            <td class="px-4 py-3.5 text-center">
                                                @if($user->is_active)
                                                    <span class="px-2.5 py-0.5 inline-flex text-xs font-bold rounded-full bg-emerald-100 text-emerald-800 border border-emerald-200">Aktif</span>
                                                @else
                                                    <span class="px-2.5 py-0.5 inline-flex text-xs font-bold rounded-full bg-gray-100 text-gray-700">Pasif</span>
                                                @endif
                                            </td>
                                            <td class="px-4 py-3.5 text-right text-sm">
                                                <div class="inline-flex items-center justify-end gap-1">
                                                    <a href="{{ route('users.edit', $user) }}" class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-purple-50 text-purple-700 hover:bg-purple-600 hover:text-white transition-all shadow-xs border border-purple-100" title="Düzenle">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path></svg>
                                                    </a>
                                                    @if($user->id !== 1 && !$user->isSuperAdmin() && !auth()->user()?->isYonetim())
                                                    <button type="button" 
                                                            @click="$dispatch('open-user-delete-modal', { id: {{ $user->id }}, name: '{{ addslashes($user->name) }}', email: '{{ addslashes($user->email) }}' })"
                                                            class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-rose-50 text-rose-600 hover:bg-rose-600 hover:text-white transition-all shadow-xs border border-rose-100 cursor-pointer" 
                                                            title="Kullanıcıyı Sil">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                                    </button>
                                                    @endif
                                                </div>
                                            </td>
                                        @endif
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="{{ $canManage ? 7 : 3 }}" class="px-6 py-6 text-center text-sm text-gray-500">Kayıtlı sistem yöneticisi bulunamadı.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <!-- Mobil Görünüm (Kart Listesi) -->
                    <div class="sm:hidden divide-y divide-gray-100 p-3 space-y-3">
                        @foreach($systemUsers as $user)
                            @php
                                $isTargetSuperAdmin = ($user->role && $user->role->slug === 'sistem_yoneticisi') || $user->id === 1;
                                $maskName = $isTargetSuperAdmin && !$isSuperAdmin;
                                $displayName = $maskName ? 'Yönetim' : $user->name;
                            @endphp
                            <div x-show="!searchQuery.trim() || matches(@js($user->name . ' ' . $user->email . ' ' . ($user->role->name ?? '')))"
                                 class="bg-purple-50/20 p-3.5 rounded-2xl border border-purple-100/80 space-y-2">
                                <div class="flex items-center justify-between gap-2">
                                    <div class="flex items-center gap-2.5 min-w-0">
                                        <div class="h-8 w-8 rounded-full bg-gradient-to-br from-purple-500 to-indigo-600 flex items-center justify-center text-white text-xs font-bold shrink-0">
                                            {{ substr($displayName, 0, 1) }}
                                        </div>
                                        <div class="truncate">
                                            <div class="text-sm font-bold text-gray-900 truncate">{{ $displayName }}</div>
                                            <div class="text-[11px] text-gray-500 truncate">{{ $user->email }}</div>
                                        </div>
                                    </div>
                                    @if($user->is_active)
                                        <span class="px-2 py-0.5 text-[10px] font-bold rounded-full bg-emerald-100 text-emerald-800 shrink-0">Aktif</span>
                                    @else
                                        <span class="px-2 py-0.5 text-[10px] font-bold rounded-full bg-gray-100 text-gray-700 shrink-0">Pasif</span>
                                    @endif
                                </div>
                                <div class="flex items-center justify-between text-xs pt-1 border-t border-purple-100/60">
                                    <span class="font-bold text-purple-700">{{ $user->role->name ?? 'Yönetici' }}</span>
                                    @if($canManage)
                                    <div class="flex items-center gap-1.5">
                                        <a href="{{ route('users.edit', $user) }}" class="p-1.5 rounded-lg bg-purple-50 text-purple-700 hover:bg-purple-600 hover:text-white transition">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path></svg>
                                        </a>
                                        @if($user->id !== 1 && !$user->isSuperAdmin() && !auth()->user()?->isYonetim())
                                        <button type="button" 
                                                @click="$dispatch('open-user-delete-modal', { id: {{ $user->id }}, name: '{{ addslashes($user->name) }}', email: '{{ addslashes($user->email) }}' })"
                                                class="p-1.5 rounded-lg bg-rose-50 text-rose-600 hover:bg-rose-600 hover:text-white transition">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                        </button>
                                        @endif
                                    </div>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
                @endif

                <!-- BÖLÜM 2: GRUPLAR VE BAĞLI KULLANICILAR (YÖNETİCİ ÜSTTE, ÜYELER ALTTA) -->
                @foreach($groups as $group)
                    @php
                        $managerIds = $group->managers->pluck('id')->toArray();
                        // Normal üyeler A'dan Z'ye alfabetik sıralı
                        $regularMembers = $group->users->filter(fn($u) => !in_array($u->id, $managerIds))->sortBy('name', SORT_LOCALE_STRING)->values();
                        $groupManagersSearch = $group->managers->map(fn($m) => $m->name . ' ' . $m->email . ' ' . ($m->role->name ?? ''))->join(' | ');
                        $groupMembersSearch = $regularMembers->map(fn($m) => $m->name . ' ' . $m->email . ' ' . ($m->role->name ?? ''))->join(' | ');
                        $groupFullSearch = $group->name . ' | ' . $groupManagersSearch . ' | ' . $groupMembersSearch;
                    @endphp
                    <div x-show="!searchQuery.trim() || matches(@js($groupFullSearch))"
                         class="backdrop-blur-xl bg-white/80 border border-gray-200/70 shadow-[0_8px_30px_rgb(0,0,0,0.04)] rounded-3xl overflow-hidden transition-all duration-300 hover:shadow-indigo-500/5">
                        
                        <!-- Grup Kartı Başlığı -->
                        <div class="bg-gradient-to-r from-slate-50 via-indigo-50/30 to-white px-6 py-4 border-b border-gray-200/70 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-2xl bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center text-white shadow-md shadow-indigo-500/20">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
                                </div>
                                <div>
                                    <div class="flex items-center gap-2">
                                        <h3 class="text-base font-extrabold text-gray-900 tracking-tight">{{ $group->name }}</h3>
                                        @if($group->is_active)
                                            <span class="text-[10px] font-bold text-emerald-700 bg-emerald-100/80 px-2 py-0.5 rounded-full">Aktif Grup</span>
                                        @else
                                            <span class="text-[10px] font-bold text-gray-600 bg-gray-200 px-2 py-0.5 rounded-full">Pasif Grup</span>
                                        @endif
                                    </div>
                                    <p class="text-xs text-gray-500">{{ $group->description ?? 'Grup veri girişi ve yönetim alanı' }}</p>
                                </div>
                            </div>
                            
                            <!-- İstatistik Rozetleri -->
                            <div class="flex items-center gap-2 text-xs">
                                <span class="bg-amber-50 text-amber-800 border border-amber-200/70 font-bold px-3 py-1 rounded-xl flex items-center gap-1.5">
                                    <span>👑</span> {{ $group->managers->count() }} Yönetici
                                </span>
                                <span class="bg-indigo-50 text-indigo-800 border border-indigo-200/70 font-bold px-3 py-1 rounded-xl flex items-center gap-1.5">
                                    <span>👥</span> {{ $regularMembers->count() }} Üye
                                </span>
                            </div>
                        </div>

                        <!-- Grup Tablosu (Masaüstü: Ekrana Tam Sığan Akıcı Tablo, Yatay Scroll Yok) -->
                        <div class="w-full hidden sm:block">
                            <table class="w-full text-left border-collapse table-auto">
                                <thead>
                                    <tr class="bg-gray-50/50 border-b border-gray-100 text-xs font-bold text-gray-500 uppercase tracking-wider">
                                        <th class="px-4 py-3 text-left">Kullanıcı Adı</th>
                                        <th class="px-4 py-3 text-left">E-Posta</th>
                                        <th class="px-4 py-3 text-left">Gruptaki Konumu / Rol</th>
                                        <th class="px-4 py-3 text-left">Bağlı Olduğu Grup</th>
                                        @if($canManage)
                                            <th class="px-4 py-3 text-center w-24">Durum</th>
                                            <th class="px-4 py-3 text-right w-24">İşlemler</th>
                                        @endif
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                    
                                    <!-- 1. GRUP YÖNETİCİSİ: ARAMADA DA HER ZAMAN SABİT OLARAK GÖRÜNÜR! -->
                                    @forelse($group->managers as $manager)
                                        @php
                                            $isTargetSuperAdmin = ($manager->role && $manager->role->slug === 'sistem_yoneticisi') || $manager->id === 1;
                                            $maskName = $isTargetSuperAdmin && !$isSuperAdmin;
                                            $displayName = $maskName ? 'Yönetim' : $manager->name;
                                        @endphp
                                        <tr class="bg-gradient-to-r from-amber-50/70 via-indigo-50/30 to-white hover:bg-amber-50/90 transition-colors border-b border-indigo-100">
                                            <td class="px-4 py-3.5">
                                                <div class="flex items-center gap-3">
                                                    <div class="relative shrink-0">
                                                        <div class="h-9 w-9 rounded-full bg-gradient-to-br from-amber-400 to-indigo-600 flex items-center justify-center text-white font-bold shadow-md border-2 border-white shrink-0">
                                                            {{ substr($displayName, 0, 1) }}
                                                        </div>
                                                        <div class="absolute -top-1 -right-1 bg-amber-400 text-white p-0.5 rounded-full text-[9px] shadow-xs" title="Grup Yöneticisi">
                                                            👑
                                                        </div>
                                                    </div>
                                                    <div class="min-w-0">
                                                        <div class="text-sm font-extrabold text-gray-900 flex items-center gap-1.5 flex-wrap">
                                                            @if($maskName)
                                                                <span class="truncate">{{ $displayName }}</span>
                                                            @else
                                                                <a href="{{ route('users.profile', $manager->id) }}" class="hover:text-indigo-600 transition-colors inline-flex items-center gap-1 group">
                                                                    <span class="truncate">{{ $displayName }}</span>
                                                                    <svg class="w-3.5 h-3.5 text-gray-400 group-hover:text-indigo-600 opacity-0 group-hover:opacity-100 transition-opacity shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                                                                </a>
                                                            @endif
                                                            <span class="bg-amber-100 text-amber-800 text-[10px] font-bold px-2 py-0.5 rounded-full border border-amber-200 shrink-0">
                                                                👑 GRUP YÖNETİCİSİ
                                                            </span>
                                                        </div>
                                                        <span class="text-[11px] font-semibold text-indigo-600 block mt-0.5">Veri Giriş Yöneticisi</span>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="px-4 py-3.5 text-xs text-gray-700 font-medium">
                                                <a href="mailto:{{ $manager->email }}" class="hover:text-indigo-600 hover:underline truncate block max-w-xs">{{ $manager->email }}</a>
                                            </td>
                                            <td class="px-4 py-3.5">
                                                <span class="px-2.5 py-1 inline-flex text-xs font-bold rounded-full bg-indigo-100 text-indigo-800 border border-indigo-200">
                                                    {{ $manager->role->name ?? 'Veri Giriş Yöneticisi' }}
                                                </span>
                                            </td>
                                            <td class="px-4 py-3.5 text-xs font-semibold text-indigo-700">
                                                <div class="flex items-center gap-1.5">
                                                    <svg class="w-4 h-4 text-indigo-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
                                                    <span class="truncate">{{ $group->name }} (Yönettiği Grup)</span>
                                                </div>
                                            </td>
                                            @if($canManage)
                                                <td class="px-4 py-3.5 text-center">
                                                    @if($manager->is_active)
                                                        <span class="px-2.5 py-0.5 inline-flex text-xs font-bold rounded-full bg-emerald-100 text-emerald-800 border border-emerald-200">Aktif</span>
                                                    @else
                                                        <span class="px-2.5 py-0.5 inline-flex text-xs font-bold rounded-full bg-gray-100 text-gray-700">Pasif</span>
                                                    @endif
                                                </td>
                                                <td class="px-4 py-3.5 text-right text-sm">
                                                    <div class="inline-flex items-center justify-end gap-1">
                                                        <a href="{{ route('users.edit', $manager) }}" class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-indigo-50 text-indigo-700 hover:bg-indigo-600 hover:text-white transition-all shadow-xs border border-indigo-100" title="Düzenle">
                                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path></svg>
                                                        </a>
                                                        @if($manager->id !== 1 && !$manager->isSuperAdmin() && !auth()->user()?->isYonetim())
                                                        <button type="button" 
                                                                @click="$dispatch('open-user-delete-modal', { id: {{ $manager->id }}, name: '{{ addslashes($manager->name) }}', email: '{{ addslashes($manager->email) }}' })"
                                                                class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-rose-50 text-rose-600 hover:bg-rose-600 hover:text-white transition-all shadow-xs border border-rose-100 cursor-pointer" 
                                                                title="Kullanıcıyı Sil">
                                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                                        </button>
                                                        @endif
                                                    </div>
                                                </td>
                                            @endif
                                        </tr>
                                    @empty
                                        <tr class="bg-amber-50/40">
                                            <td colspan="{{ $canManage ? 6 : 4 }}" class="px-6 py-3 text-center text-xs font-medium text-amber-700">
                                                ⚠️ Bu gruba henüz atanmış bir <strong>Veri Giriş Yöneticisi</strong> bulunmuyor.
                                            </td>
                                        </tr>
                                    @endforelse

                                    <!-- 2. GRUP ÜYELERİ / VERİ GİRİŞİ (YÖNETİCİNİN ALTINDA, ALFABETİK SIRALI) -->
                                    @forelse($regularMembers as $member)
                                        @php
                                            $isTargetSuperAdmin = ($member->role && $member->role->slug === 'sistem_yoneticisi') || $member->id === 1;
                                            $maskName = $isTargetSuperAdmin && !$isSuperAdmin;
                                            $displayName = $maskName ? 'Yönetim' : $member->name;
                                        @endphp
                                        <tr x-show="!searchQuery.trim() || matches(@js($member->name . ' ' . $member->email . ' ' . ($member->role->name ?? '')))"
                                            class="hover:bg-gray-50/70 transition-colors">
                                            <td class="px-4 py-3.5">
                                                <div class="flex items-center gap-3 pl-3">
                                                    <svg class="w-4 h-4 text-gray-300 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                                                    <div class="h-8 w-8 rounded-full bg-gradient-to-br from-slate-100 to-indigo-100 flex items-center justify-center text-indigo-600 font-bold border border-white shadow-xs shrink-0 text-xs">
                                                        {{ substr($displayName, 0, 1) }}
                                                    </div>
                                                    <div class="min-w-0">
                                                        @if($maskName)
                                                            <div class="text-sm font-bold text-gray-900 truncate">{{ $displayName }}</div>
                                                        @else
                                                            <a href="{{ route('users.profile', $member->id) }}" class="text-sm font-bold text-gray-900 hover:text-indigo-600 transition-colors inline-flex items-center gap-1 group">
                                                                <span class="truncate">{{ $displayName }}</span>
                                                                <svg class="w-3.5 h-3.5 text-gray-400 group-hover:text-indigo-600 opacity-0 group-hover:opacity-100 transition-opacity shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                                                            </a>
                                                        @endif
                                                        <span class="text-[11px] font-medium text-gray-400 block mt-0.5">Grup Üyesi</span>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="px-4 py-3.5 text-xs text-gray-700 font-medium">
                                                <a href="mailto:{{ $member->email }}" class="hover:text-indigo-600 hover:underline truncate block max-w-xs">{{ $member->email }}</a>
                                            </td>
                                            <td class="px-4 py-3.5">
                                                <span class="px-2.5 py-1 inline-flex text-xs font-semibold rounded-full bg-slate-100 text-slate-700 border border-slate-200">
                                                    {{ $member->role->name ?? 'Veri Girişi' }}
                                                </span>
                                            </td>
                                            <td class="px-4 py-3.5 text-xs text-gray-600 font-medium">
                                                <div class="flex items-center gap-1.5">
                                                    <span class="w-2 h-2 rounded-full bg-indigo-400 shrink-0"></span>
                                                    <span class="truncate">{{ $group->name }}</span>
                                                </div>
                                            </td>
                                            @if($canManage)
                                                <td class="px-4 py-3.5 text-center">
                                                    @if($member->is_active)
                                                        <span class="px-2.5 py-0.5 inline-flex text-xs font-bold rounded-full bg-emerald-100 text-emerald-800 border border-emerald-200">Aktif</span>
                                                    @else
                                                        <span class="px-2.5 py-0.5 inline-flex text-xs font-bold rounded-full bg-gray-100 text-gray-700">Pasif</span>
                                                    @endif
                                                </td>
                                                <td class="px-4 py-3.5 text-right text-sm">
                                                    <div class="inline-flex items-center justify-end gap-1">
                                                        <a href="{{ route('users.edit', $member) }}" class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-gray-50 text-gray-600 hover:bg-indigo-600 hover:text-white transition-all shadow-xs border border-gray-100" title="Düzenle">
                                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path></svg>
                                                        </a>
                                                        @if($member->id !== 1 && !$member->isSuperAdmin() && !auth()->user()?->isYonetim())
                                                        <button type="button" 
                                                                @click="$dispatch('open-user-delete-modal', { id: {{ $member->id }}, name: '{{ addslashes($member->name) }}', email: '{{ addslashes($member->email) }}' })"
                                                                class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-rose-50 text-rose-600 hover:bg-rose-600 hover:text-white transition-all shadow-xs border border-rose-100 cursor-pointer" 
                                                                title="Kullanıcıyı Sil">
                                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                                        </button>
                                                        @endif
                                                    </div>
                                                </td>
                                            @endif
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="{{ $canManage ? 6 : 4 }}" class="px-6 py-4 text-center text-xs text-gray-500 bg-gray-50/30">
                                                Bu grupta henüz kayıtlı <strong>veri girişi personeli</strong> bulunmuyor.
                                            </td>
                                        </tr>
                                    @endforelse

                                </tbody>
                            </table>
                        </div>

                        <!-- Mobil Kart Listesi (sm:hidden) -->
                        <div class="sm:hidden divide-y divide-gray-100 p-3 space-y-3">
                            <!-- Yöneticiler (Sabit) -->
                            @foreach($group->managers as $manager)
                                @php
                                    $isTargetSuperAdmin = ($manager->role && $manager->role->slug === 'sistem_yoneticisi') || $manager->id === 1;
                                    $maskName = $isTargetSuperAdmin && !$isSuperAdmin;
                                    $displayName = $maskName ? 'Yönetim' : $manager->name;
                                @endphp
                                <div class="bg-amber-50/40 p-3 rounded-2xl border border-amber-200/60 space-y-2">
                                    <div class="flex items-center justify-between gap-2">
                                        <div class="flex items-center gap-2 min-w-0">
                                            <div class="h-8 w-8 rounded-full bg-gradient-to-br from-amber-400 to-indigo-600 flex items-center justify-center text-white text-xs font-bold shrink-0">
                                                {{ substr($displayName, 0, 1) }}
                                            </div>
                                            <div class="truncate">
                                                <div class="text-sm font-bold text-gray-900 truncate flex items-center gap-1">
                                                    <span>{{ $displayName }}</span>
                                                    <span class="text-[9px] bg-amber-100 text-amber-800 font-bold px-1.5 py-0.2 rounded-full">👑 Yönetici</span>
                                                </div>
                                                <div class="text-[11px] text-gray-500 truncate">{{ $manager->email }}</div>
                                            </div>
                                        </div>
                                        @if($manager->is_active)
                                            <span class="px-2 py-0.5 text-[10px] font-bold rounded-full bg-emerald-100 text-emerald-800 shrink-0">Aktif</span>
                                        @else
                                            <span class="px-2 py-0.5 text-[10px] font-bold rounded-full bg-gray-100 text-gray-700 shrink-0">Pasif</span>
                                        @endif
                                    </div>
                                    <div class="flex items-center justify-between text-xs pt-1 border-t border-amber-200/50">
                                        <span class="font-bold text-indigo-700 text-[11px]">{{ $manager->role->name ?? 'Veri Giriş Yöneticisi' }}</span>
                                        @if($canManage)
                                        <div class="flex items-center gap-1.5">
                                            <a href="{{ route('users.edit', $manager) }}" class="p-1.5 rounded-lg bg-indigo-50 text-indigo-700 hover:bg-indigo-600 hover:text-white transition">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path></svg>
                                            </a>
                                            @if($manager->id !== 1 && !$manager->isSuperAdmin() && !auth()->user()?->isYonetim())
                                            <button type="button" 
                                                    @click="$dispatch('open-user-delete-modal', { id: {{ $manager->id }}, name: '{{ addslashes($manager->name) }}', email: '{{ addslashes($manager->email) }}' })"
                                                    class="p-1.5 rounded-lg bg-rose-50 text-rose-600 hover:bg-rose-600 hover:text-white transition">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                            </button>
                                            @endif
                                        </div>
                                        @endif
                                    </div>
                                </div>
                            @endforeach

                            <!-- Üyeler (Aramaya göre süzülür) -->
                            @foreach($regularMembers as $member)
                                @php
                                    $isTargetSuperAdmin = ($member->role && $member->role->slug === 'sistem_yoneticisi') || $member->id === 1;
                                    $maskName = $isTargetSuperAdmin && !$isSuperAdmin;
                                    $displayName = $maskName ? 'Yönetim' : $member->name;
                                @endphp
                                <div x-show="!searchQuery.trim() || matches(@js($member->name . ' ' . $member->email . ' ' . ($member->role->name ?? '')))"
                                     class="bg-slate-50/70 p-3 rounded-2xl border border-slate-100 space-y-2">
                                    <div class="flex items-center justify-between gap-2">
                                        <div class="flex items-center gap-2 min-w-0">
                                            <div class="h-8 w-8 rounded-full bg-slate-200 flex items-center justify-center text-slate-700 text-xs font-bold shrink-0">
                                                {{ substr($displayName, 0, 1) }}
                                            </div>
                                            <div class="truncate">
                                                <div class="text-sm font-bold text-gray-900 truncate">{{ $displayName }}</div>
                                                <div class="text-[11px] text-gray-500 truncate">{{ $member->email }}</div>
                                            </div>
                                        </div>
                                        @if($member->is_active)
                                            <span class="px-2 py-0.5 text-[10px] font-bold rounded-full bg-emerald-100 text-emerald-800 shrink-0">Aktif</span>
                                        @else
                                            <span class="px-2 py-0.5 text-[10px] font-bold rounded-full bg-gray-100 text-gray-700 shrink-0">Pasif</span>
                                        @endif
                                    </div>
                                    <div class="flex items-center justify-between text-xs pt-1 border-t border-slate-200/50">
                                        <span class="text-slate-600 text-[11px] font-medium">{{ $member->role->name ?? 'Veri Girişi' }}</span>
                                        @if($canManage)
                                        <div class="flex items-center gap-1.5">
                                            <a href="{{ route('users.edit', $member) }}" class="p-1.5 rounded-lg bg-gray-50 text-gray-600 hover:bg-indigo-600 hover:text-white transition">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path></svg>
                                            </a>
                                            @if($member->id !== 1 && !$member->isSuperAdmin() && !auth()->user()?->isYonetim())
                                            <button type="button" 
                                                    @click="$dispatch('open-user-delete-modal', { id: {{ $member->id }}, name: '{{ addslashes($member->name) }}', email: '{{ addslashes($member->email) }}' })"
                                                    class="p-1.5 rounded-lg bg-rose-50 text-rose-600 hover:bg-rose-600 hover:text-white transition">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                            </button>
                                            @endif
                                        </div>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endforeach

                <!-- BÖLÜM 3: BAĞIMSIZ / HERHANGİ BİR GRUBA ATANMAMIŞ KULLANICILAR (VARSA) -->
                @if($unassignedUsers->isNotEmpty())
                    <div x-show="!searchQuery.trim() || matches(@js($unassignedSearchText))"
                         class="backdrop-blur-xl bg-white/80 border border-amber-200/60 shadow-[0_8px_30px_rgb(0,0,0,0.04)] rounded-3xl overflow-hidden">
                        <div class="bg-amber-50/70 px-6 py-4 border-b border-amber-100 flex justify-between items-center">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-2xl bg-amber-500 flex items-center justify-center text-white shadow-sm">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                                </div>
                                <div>
                                    <h3 class="text-base font-extrabold text-gray-900">Grubu Olmayan / Atanmamış Kullanıcılar</h3>
                                    <p class="text-xs text-gray-500">Herhangi bir gruba dahil edilmemiş veya yönetici olarak atanmamış kullanıcılar.</p>
                                </div>
                            </div>
                            <span class="text-xs font-bold text-amber-800 bg-amber-100 px-3 py-1 rounded-xl">
                                {{ $unassignedUsers->count() }} Kullanıcı
                            </span>
                        </div>
                        <div class="w-full">
                            <table class="w-full text-left border-collapse table-auto">
                                <thead>
                                    <tr class="bg-gray-50/50 border-b border-gray-100 text-xs font-bold text-gray-500 uppercase tracking-wider">
                                        <th class="px-4 py-3 text-left">Kullanıcı Adı</th>
                                        <th class="px-4 py-3 text-left">E-Posta</th>
                                        <th class="px-4 py-3 text-left">Gruptaki Konumu / Rol</th>
                                        @if($canManage)
                                            <th class="px-4 py-3 text-center w-24">Durum</th>
                                            <th class="px-4 py-3 text-right w-24">İşlemler</th>
                                        @endif
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                    @foreach($unassignedUsers as $user)
                                        @php
                                            $isTargetSuperAdmin = ($user->role && $user->role->slug === 'sistem_yoneticisi') || $user->id === 1;
                                            $maskName = $isTargetSuperAdmin && !$isSuperAdmin;
                                            $displayName = $maskName ? 'Yönetim' : $user->name;
                                        @endphp
                                        <tr x-show="!searchQuery.trim() || matches(@js($user->name . ' ' . $user->email . ' ' . ($user->role->name ?? '')))"
                                            class="hover:bg-amber-50/20 transition-colors">
                                            <td class="px-4 py-3.5">
                                                <div class="flex items-center gap-3">
                                                    <div class="h-9 w-9 rounded-full bg-gray-200 flex items-center justify-center text-gray-700 font-bold shrink-0 text-xs">
                                                        {{ substr($displayName, 0, 1) }}
                                                    </div>
                                                    <div class="min-w-0">
                                                        @if($maskName)
                                                            <div class="text-sm font-bold text-gray-900 truncate">{{ $displayName }}</div>
                                                        @else
                                                            <a href="{{ route('users.profile', $user->id) }}" class="text-sm font-bold text-gray-900 hover:text-indigo-600 transition-colors inline-flex items-center gap-1 group">
                                                                <span class="truncate">{{ $displayName }}</span>
                                                                <svg class="w-3.5 h-3.5 text-gray-400 group-hover:text-indigo-600 opacity-0 group-hover:opacity-100 transition-opacity shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                                                            </a>
                                                        @endif
                                                        <span class="text-[11px] font-medium text-gray-400 block mt-0.5">Bağımsız Kullanıcı</span>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="px-4 py-3.5 text-xs text-gray-700 font-medium">
                                                <a href="mailto:{{ $user->email }}" class="hover:text-indigo-600 hover:underline truncate block max-w-xs">{{ $user->email }}</a>
                                            </td>
                                            <td class="px-4 py-3.5">
                                                <span class="px-2.5 py-1 inline-flex text-xs font-bold rounded-full bg-gray-100 text-gray-700 border border-gray-200">
                                                    {{ $user->role->name ?? 'Bağımsız' }}
                                                </span>
                                            </td>
                                            @if($canManage)
                                                <td class="px-4 py-3.5 text-center">
                                                    @if($user->is_active)
                                                        <span class="px-2.5 py-0.5 inline-flex text-xs font-bold rounded-full bg-emerald-100 text-emerald-800">Aktif</span>
                                                    @else
                                                        <span class="px-2.5 py-0.5 inline-flex text-xs font-bold rounded-full bg-gray-100 text-gray-700">Pasif</span>
                                                    @endif
                                                </td>
                                                <td class="px-4 py-3.5 text-right text-sm">
                                                    <div class="inline-flex items-center justify-end gap-1">
                                                        <a href="{{ route('users.edit', $user) }}" class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-indigo-50 text-indigo-700 hover:bg-indigo-600 hover:text-white transition-all shadow-xs border border-indigo-100" title="Düzenle">
                                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path></svg>
                                                        </a>
                                                        @if($user->id !== 1 && !$user->isSuperAdmin() && !auth()->user()?->isYonetim())
                                                        <button type="button" 
                                                                @click="$dispatch('open-user-delete-modal', { id: {{ $user->id }}, name: '{{ addslashes($user->name) }}', email: '{{ addslashes($user->email) }}' })"
                                                                class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-rose-50 text-rose-600 hover:bg-rose-600 hover:text-white transition-all shadow-xs border border-rose-100 cursor-pointer" 
                                                                title="Kullanıcıyı Sil">
                                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                                        </button>
                                                        @endif
                                                    </div>
                                                </td>
                                            @endif
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endif

            </div>

            <!-- ========================================== -->
            <!-- 2) DÜZ LİSTE GÖRÜNÜMÜ (SEÇENEK OLARAK)    -->
            <!-- ========================================== -->
            <div x-show="view === 'flat'" class="backdrop-blur-xl bg-white/80 border border-white/50 shadow-[0_8px_30px_rgb(0,0,0,0.04)] sm:rounded-3xl overflow-hidden" style="display: none;">
                <!-- Masaüstü Düz Liste Tablosu (Yatay Scroll Yok, Tam Ekran Sığar) -->
                <div class="w-full hidden sm:block">
                    <table class="w-full text-left border-collapse table-auto">
                        <thead>
                            <tr class="bg-gray-50/50 border-b border-gray-100 text-xs font-bold text-gray-500 uppercase tracking-wider">
                                <th class="px-4 py-3 text-left">Kullanıcı Adı</th>
                                <th class="px-4 py-3 text-left">Email</th>
                                <th class="px-4 py-3 text-left">Grup</th>
                                <th class="px-4 py-3 text-left">Gruptaki Konumu / Rol</th>
                                @if($canManage)
                                    <th class="px-4 py-3 text-left">Sistem Rolü</th>
                                    <th class="px-4 py-3 text-center w-24">Durum</th>
                                    <th class="px-4 py-3 text-right w-24">İşlemler</th>
                                @endif
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100/50">
                            @foreach($users as $user)
                                @php
                                    $isTargetSuperAdmin = ($user->role && $user->role->slug === 'sistem_yoneticisi') || $user->id === 1;
                                    $maskName = $isTargetSuperAdmin && !$isSuperAdmin;
                                    $displayName = $maskName ? 'Yönetim' : $user->name;
                                @endphp
                                <tr x-show="!searchQuery.trim() || matches(@js($user->name . ' ' . $user->email . ' ' . ($user->group->name ?? '') . ' ' . ($user->role->name ?? '')))"
                                    class="group hover:bg-white/80 transition-colors">
                                    <td class="px-4 py-3.5">
                                        <div class="flex items-center">
                                            <div class="h-9 w-9 flex-shrink-0">
                                                <div class="h-9 w-9 rounded-full bg-gradient-to-br from-indigo-100 to-purple-100 flex items-center justify-center text-indigo-600 font-bold border border-white shadow-sm shrink-0 text-xs">
                                                    {{ substr($displayName, 0, 1) }}
                                                </div>
                                            </div>
                                            <div class="ml-3 min-w-0">
                                                @if($maskName)
                                                    <div class="text-sm font-bold text-gray-900 truncate">{{ $displayName }}</div>
                                                @else
                                                    <a href="{{ route('users.profile', $user->id) }}" class="text-sm font-bold text-gray-900 group-hover:text-indigo-600 transition-colors inline-flex items-center gap-1">
                                                        <span class="truncate">{{ $displayName }}</span>
                                                        <svg class="w-3.5 h-3.5 text-gray-400 group-hover:text-indigo-600 opacity-0 group-hover:opacity-100 transition-opacity shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                                                    </a>
                                                @endif
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3.5 text-xs text-gray-700 font-medium">
                                        <a href="mailto:{{ $user->email }}" class="hover:text-indigo-600 hover:underline truncate block max-w-xs">{{ $user->email }}</a>
                                    </td>
                                    <td class="px-4 py-3.5 text-xs text-gray-600 font-medium">
                                        <div class="flex items-center gap-2">
                                            <svg class="w-4 h-4 text-gray-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                                            <span class="truncate">{{ $user->group->name ?? 'Bağımsız' }}</span>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3.5">
                                        @if($user->managedGroups->isNotEmpty())
                                            <span class="px-2.5 py-1 inline-flex text-xs font-bold rounded-full bg-amber-100 text-amber-800 border border-amber-200">
                                                👑 GRUP YÖNETİCİSİ
                                            </span>
                                        @elseif($user->group_id)
                                            <span class="px-2.5 py-1 inline-flex text-xs font-medium rounded-full bg-slate-100 text-slate-700">
                                                Grup Üyesi
                                            </span>
                                        @else
                                            <span class="px-2.5 py-1 inline-flex text-xs font-medium rounded-full bg-purple-50 text-purple-700">
                                                Sistem / Bağımsız
                                            </span>
                                        @endif
                                    </td>
                                    @if($canManage)
                                        <td class="px-4 py-3.5">
                                            <span class="px-2.5 py-1 inline-flex text-xs font-semibold rounded-full bg-indigo-50/80 text-indigo-700 border border-indigo-100/50 shadow-xs">
                                                {{ $user->role->name ?? '-' }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-3.5 text-center">
                                            @if($user->is_active)
                                                <span class="px-2.5 py-0.5 inline-flex text-xs font-bold rounded-full bg-emerald-100 text-emerald-800 border border-emerald-200">Aktif</span>
                                            @else
                                                <span class="px-2.5 py-0.5 inline-flex text-xs font-bold rounded-full bg-gray-100 text-gray-700">Pasif</span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3.5 text-right text-sm font-medium">
                                            <div class="inline-flex items-center justify-end gap-1">
                                                <a href="{{ route('users.edit', $user) }}" class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-gray-50 text-gray-600 hover:bg-indigo-600 hover:text-white transition-all shadow-xs border border-gray-100" title="Düzenle">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path></svg>
                                                </a>
                                                @if($user->id !== 1 && !$user->isSuperAdmin() && !auth()->user()?->isYonetim())
                                                <button type="button" 
                                                        @click="$dispatch('open-user-delete-modal', { id: {{ $user->id }}, name: '{{ addslashes($user->name) }}', email: '{{ addslashes($user->email) }}' })"
                                                        class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-rose-50 text-rose-600 hover:bg-rose-600 hover:text-white transition-all shadow-xs border border-rose-100 cursor-pointer" 
                                                        title="Kullanıcıyı Sil">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                                </button>
                                                @endif
                                            </div>
                                        </td>
                                    @endif
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- Mobil Düz Liste (sm:hidden) -->
                <div class="sm:hidden divide-y divide-gray-100 p-3 space-y-3">
                    @foreach($users as $user)
                        @php
                            $isTargetSuperAdmin = ($user->role && $user->role->slug === 'sistem_yoneticisi') || $user->id === 1;
                            $maskName = $isTargetSuperAdmin && !$isSuperAdmin;
                            $displayName = $maskName ? 'Yönetim' : $user->name;
                        @endphp
                        <div x-show="!searchQuery.trim() || matches(@js($user->name . ' ' . $user->email . ' ' . ($user->group->name ?? '') . ' ' . ($user->role->name ?? '')))"
                             class="bg-white p-3.5 rounded-2xl border border-gray-100 shadow-xs space-y-2">
                            <div class="flex items-center justify-between gap-2">
                                <div class="flex items-center gap-2.5 min-w-0">
                                    <div class="h-8 w-8 rounded-full bg-gradient-to-br from-indigo-100 to-purple-100 flex items-center justify-center text-indigo-600 text-xs font-bold shrink-0">
                                        {{ substr($displayName, 0, 1) }}
                                    </div>
                                    <div class="truncate">
                                        <div class="text-sm font-bold text-gray-900 truncate">{{ $displayName }}</div>
                                        <div class="text-[11px] text-gray-500 truncate">{{ $user->email }}</div>
                                    </div>
                                </div>
                                @if($user->is_active)
                                    <span class="px-2 py-0.5 text-[10px] font-bold rounded-full bg-emerald-100 text-emerald-800 shrink-0">Aktif</span>
                                @else
                                    <span class="px-2 py-0.5 text-[10px] font-bold rounded-full bg-gray-100 text-gray-700 shrink-0">Pasif</span>
                                @endif
                            </div>
                            <div class="flex items-center justify-between text-xs pt-1.5 border-t border-gray-100">
                                <div class="flex items-center gap-1.5 truncate">
                                    <span class="text-indigo-600 font-bold text-[11px] truncate">{{ $user->group->name ?? 'Bağımsız' }}</span>
                                    <span class="text-gray-300">•</span>
                                    <span class="text-gray-500 text-[11px] truncate">{{ $user->role->name ?? '-' }}</span>
                                </div>
                                @if($canManage)
                                <div class="flex items-center gap-1.5 shrink-0">
                                    <a href="{{ route('users.edit', $user) }}" class="p-1.5 rounded-lg bg-gray-50 text-gray-600 hover:bg-indigo-600 hover:text-white transition">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path></svg>
                                    </a>
                                    @if($user->id !== 1 && !$user->isSuperAdmin() && !auth()->user()?->isYonetim())
                                    <button type="button" 
                                            @click="$dispatch('open-user-delete-modal', { id: {{ $user->id }}, name: '{{ addslashes($user->name) }}', email: '{{ addslashes($user->email) }}' })"
                                            class="p-1.5 rounded-lg bg-rose-50 text-rose-600 hover:bg-rose-600 hover:text-white transition">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                    </button>
                                    @endif
                                </div>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

        </div>

        <!-- KULLANICI SİLME ONAY MODALI -->
        <div x-cloak x-show="showDeleteModal" class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="delete-user-modal-title" role="dialog" aria-modal="true">
            <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                <div x-show="showDeleteModal" 
                     x-transition:enter="ease-out duration-300" 
                     x-transition:enter-start="opacity-0" 
                     x-transition:enter-end="opacity-100" 
                     x-transition:leave="ease-in duration-200" 
                     x-transition:leave-start="opacity-100" 
                     x-transition:leave-end="opacity-0" 
                     class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs transition-opacity" 
                     @click="showDeleteModal = false"></div>

                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

                <div x-show="showDeleteModal" 
                     x-transition:enter="ease-out duration-300" 
                     x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" 
                     x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100" 
                     x-transition:leave="ease-in duration-200" 
                     x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100" 
                     x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" 
                     class="inline-block align-bottom bg-white rounded-3xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-md sm:w-full border border-slate-100">
                    
                    <form :action="'{{ url('users') }}/' + deleteUserId" method="POST">
                        @csrf
                        @method('DELETE')
                        
                        <div class="bg-white p-6 sm:p-7">
                            <div class="flex items-start gap-4">
                                <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-2xl bg-rose-50 text-rose-600 sm:mx-0 border border-rose-100">
                                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                </div>
                                <div class="mt-0 text-left flex-1">
                                    <h3 class="text-base font-extrabold text-slate-900" id="delete-user-modal-title">
                                        Kullanıcıyı Sil
                                    </h3>
                                    <p class="text-xs text-slate-500 mt-1">
                                        Bu kullanıcıyı sistemden kalıcı olarak silmek istediğinize emin misiniz?
                                    </p>
                                </div>
                            </div>

                            <div class="mt-4 p-3.5 bg-slate-50 rounded-2xl border border-slate-100">
                                <div class="text-[11px] text-slate-500 font-semibold uppercase tracking-wider mb-1">Silinecek Kullanıcı:</div>
                                <div class="text-sm font-extrabold text-slate-900" x-text="deleteUserName"></div>
                                <div class="text-xs text-slate-600 font-medium" x-text="deleteUserEmail"></div>
                            </div>

                            <div class="mt-4 p-3 bg-rose-50 rounded-xl border border-rose-100 text-[11px] text-rose-700 flex items-start gap-2">
                                <svg class="w-4 h-4 text-rose-500 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                                <span>Bu işlem geri alınamaz. Kullanıcının oturumları, grup ve rol yetkileri temizlenecektir.</span>
                            </div>
                        </div>

                        <div class="bg-slate-50 px-6 py-4 flex flex-col sm:flex-row-reverse gap-2 sm:gap-3 rounded-b-3xl border-t border-slate-100">
                            <button type="submit" class="w-full sm:w-auto inline-flex justify-center items-center px-5 py-2.5 bg-rose-600 hover:bg-rose-700 text-white text-xs font-bold rounded-xl shadow-sm transition cursor-pointer">
                                Evet, Kullanıcıyı Sil
                            </button>
                            <button type="button" @click="showDeleteModal = false" class="w-full sm:w-auto inline-flex justify-center items-center px-4 py-2.5 bg-white hover:bg-slate-100 text-slate-700 text-xs font-bold rounded-xl border border-slate-200 shadow-xs transition cursor-pointer">
                                Vazgeç
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>