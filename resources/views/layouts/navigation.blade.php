<div x-data="{ mobileOpen: false }">
    <!-- Üst Bölüm: Logo ve Profil (Sabit Değil) -->
    <header class="bg-white border-b border-gray-100 relative z-[60]">
        <div class="w-full px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-20">
                <!-- Logo -->
                <div class="shrink-0 flex items-center">
                    <a href="{{ route('dashboard') }}" class="flex items-center group">
                        @if(file_exists(public_path('storage/logo.png')))
                            <img src="{{ asset('storage/logo.png') }}?v={{ time() }}"
                                class="block h-12 w-auto object-contain transition-transform duration-200 group-hover:scale-105"
                                alt="Logo" />
                        @else
                            <div
                                class="flex items-center justify-center bg-indigo-600 text-white rounded-lg h-10 w-10 font-bold text-xl shadow-md transition-transform duration-200 group-hover:scale-105">
                                H
                            </div>
                            <span
                                class="ml-3 font-bold text-xl text-gray-800 tracking-wider group-hover:text-indigo-600 transition-colors">HEDEFLER</span>
                        @endif
                    </a>
                </div>

                <!-- Sağ Taraf: Bildirim Zili + Profil + Mobil Hamburger -->
                <div class="flex items-center gap-2 sm:gap-3">
                    <!-- Bildirim Zili (Hem Mobilde Hem Masaüstünde Her Zaman Görünür) -->
                    <div class="relative" x-data="headerNotifications()">
                        <x-dropdown align="right" width="w-[calc(100vw-2rem)] sm:w-[26rem] max-w-[26rem]"
                            contentClasses="py-0 bg-white shadow-xl rounded-2xl border border-slate-100 overflow-hidden">
                            <x-slot name="trigger">
                                <button
                                    class="relative p-2 text-slate-500 hover:text-indigo-600 hover:bg-indigo-50/80 rounded-xl transition-all focus:outline-none">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9">
                                        </path>
                                    </svg>
                                    <template x-if="count > 0">
                                        <span
                                            class="absolute top-1.5 right-1.5 inline-flex items-center justify-center min-w-[1.15rem] h-[1.15rem] px-1 text-[10px] font-extrabold leading-none text-white transform translate-x-1/4 -translate-y-1/4 bg-rose-600 rounded-full shadow-sm ring-2 ring-white"
                                            x-text="count > 9 ? '9+' : count">
                                        </span>
                                    </template>
                                    @if(Auth::user()->unreadNotifications->count() > 0)
                                        <span x-show="count > 0 && !initialized"
                                            class="absolute top-1.5 right-1.5 inline-flex items-center justify-center min-w-[1.15rem] h-[1.15rem] px-1 text-[10px] font-extrabold leading-none text-white transform translate-x-1/4 -translate-y-1/4 bg-rose-600 rounded-full shadow-sm ring-2 ring-white">
                                            {{ Auth::user()->unreadNotifications->count() > 9 ? '9+' : Auth::user()->unreadNotifications->count() }}
                                        </span>
                                    @endif
                                </button>
                            </x-slot>

                            <x-slot name="content">
                                <!-- Başlık ve Tümünü Okundu İşaretle -->
                                <div
                                    class="px-4 py-3 border-b border-slate-100 flex justify-between items-center bg-slate-50/80">
                                    <div class="flex items-center gap-2">
                                        <div
                                            class="w-7 h-7 rounded-lg bg-indigo-100/70 text-indigo-600 flex items-center justify-center shadow-2xs">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9">
                                                </path>
                                            </svg>
                                        </div>
                                        <h3 class="text-xs sm:text-sm font-extrabold text-slate-800">Bildirimler</h3>
                                        <span x-show="count > 0" x-text="count + ' Okunmamış'"
                                            class="text-[10px] font-extrabold px-1.5 py-0.5 rounded-full bg-rose-50 text-rose-600 border border-rose-100">
                                            {{ Auth::user()->unreadNotifications->count() }} Okunmamış
                                        </span>
                                    </div>
                                    <template x-if="count > 0">
                                        <button type="button" @click.stop="markAllAsRead($event)"
                                            class="text-[11px] text-indigo-600 hover:text-indigo-800 font-bold hover:underline inline-flex items-center gap-1 transition-colors">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M5 13l4 4L19 7"></path>
                                            </svg>
                                            <span>Tümünü Okundu İşaretle</span>
                                        </button>
                                    </template>
                                </div>

                                <!-- Bildirim Listesi -->
                                <div class="max-h-[305px] overflow-y-auto p-3 space-y-2.5 bg-slate-100/60 divide-y-0">
                                    @php
                                        $unreadList = Auth::user()->unreadNotifications()->limit(5)->get();
                                    @endphp
                                    @forelse($unreadList as $notification)
                                        <div id="notif-row-{{ $notification->id }}"
                                            class="notif-item-card p-3.5 bg-white rounded-xl border border-slate-200/90 shadow-2xs hover:shadow-xs hover:border-indigo-300 transition-all duration-200 relative group">
                                            <div class="flex items-start gap-3">
                                                <!-- Sol İkon -->
                                                <div
                                                    class="w-8 h-8 rounded-lg bg-indigo-50 border border-indigo-100/70 text-indigo-600 flex items-center justify-center shrink-0 mt-0.5">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                                        viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2"
                                                            d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9">
                                                        </path>
                                                    </svg>
                                                </div>

                                                <!-- İçerik -->
                                                <div class="flex-1 min-w-0">
                                                    <div class="flex items-start justify-between gap-1.5">
                                                        <p
                                                            class="text-xs font-bold text-slate-800 line-clamp-1 leading-snug">
                                                            {{ $notification->data['subject'] ?? 'Sistem Bildirimi' }}
                                                        </p>
                                                        <span
                                                            class="text-[10px] font-semibold text-slate-400 bg-slate-50 px-1.5 py-0.5 rounded border border-slate-100 shrink-0">
                                                            {{ $notification->created_at->diffForHumans() }}
                                                        </span>
                                                    </div>

                                                    <div
                                                        class="text-xs text-slate-600 mt-1 line-clamp-2 leading-relaxed prose prose-sm max-w-none">
                                                        {!! $notification->data['body'] ?? '' !!}
                                                    </div>

                                                    <!-- Alt Butonlar -->
                                                    <div
                                                        class="mt-2.5 pt-2 border-t border-slate-100 flex items-center justify-between gap-2">
                                                        <div>
                                                            @if(!empty($notification->data['actionUrl']))
                                                                @php
                                                                    $rawUrl = $notification->data['actionUrl'];
                                                                    $parsedUrl = parse_url($rawUrl);
                                                                    if (isset($parsedUrl['path'])) {
                                                                        $pathWithQuery = $parsedUrl['path'] . (isset($parsedUrl['query']) ? '?' . $parsedUrl['query'] : '');
                                                                        $safeUrl = url($pathWithQuery);
                                                                    } else {
                                                                        $safeUrl = $rawUrl;
                                                                    }
                                                                @endphp
                                                                <a href="{{ $safeUrl }}"
                                                                    class="inline-flex items-center gap-1 text-[11px] font-bold text-indigo-700 bg-indigo-50 hover:bg-indigo-100 border border-indigo-200/70 px-2.5 py-1 rounded-lg transition-colors">
                                                                    <span>Git</span>
                                                                    <svg class="w-3 h-3" fill="none" stroke="currentColor"
                                                                        viewBox="0 0 24 24">
                                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                                            stroke-width="2" d="M9 5l7 7-7 7"></path>
                                                                    </svg>
                                                                </a>
                                                            @endif
                                                        </div>

                                                        <!-- Okundu Olarak İşaretle Butonu -->
                                                        <button type="button"
                                                            @click.stop="markOneAsRead('{{ $notification->id }}', $event)"
                                                            title="Okundu olarak işaretle ve listeden kaldır"
                                                            class="inline-flex items-center gap-1.5 text-[11px] font-bold text-emerald-700 hover:text-emerald-900 bg-emerald-50 hover:bg-emerald-100 border border-emerald-200/80 px-2.5 py-1 rounded-lg transition-all shadow-2xs">
                                                            <svg class="w-3.5 h-3.5 text-emerald-600" fill="none"
                                                                stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                                    stroke-width="2.5" d="M5 13l4 4L19 7"></path>
                                                            </svg>
                                                            <span>Okundu Olarak İşaretle</span>
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    @empty
                                        <!-- Boş Durum -->
                                    @endforelse

                                    <!-- Boş Durum (Okunmamış kalmadığında) -->
                                    <div id="notif-empty-state"
                                        class="{{ $unreadList->count() === 0 ? '' : 'hidden' }} py-8 px-4 text-center">
                                        <div
                                            class="w-10 h-10 rounded-full bg-emerald-50 text-emerald-600 mx-auto flex items-center justify-center mb-2 shadow-2xs">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M5 13l4 4L19 7"></path>
                                            </svg>
                                        </div>
                                        <p class="text-xs font-bold text-slate-700">Tüm Bildirimler Okundu</p>
                                        <p class="text-[10px] text-slate-400 mt-0.5">Şu anda okunmamış yeni bildiriminiz
                                            bulunmuyor.</p>
                                    </div>
                                </div>

                                <!-- Alt Bağlantı: Tüm Bildirimleri Gör -->
                                <div
                                    class="px-3 py-2 text-center border-t border-slate-100 bg-slate-50/90 rounded-b-xl hover:bg-slate-100/80 transition-colors">
                                    <a href="{{ route('notifications.index') }}"
                                        class="text-xs text-slate-700 hover:text-indigo-600 font-bold w-full flex items-center justify-center gap-1">
                                        <span>Tüm Bildirimleri Gör</span>
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M9 5l7 7-7 7"></path>
                                        </svg>
                                    </a>
                                </div>
                            </x-slot>
                        </x-dropdown>
                    </div>

                    <script>
                        function headerNotifications() {
                            return {
                                count: {{ Auth::user()->unreadNotifications->count() }},
                                initialized: false,
                                init() {
                                    this.initialized = true;
                                },
                                async markOneAsRead(id, event) {
                                    if (event) { event.stopPropagation(); event.preventDefault(); }
                                    const el = document.getElementById('notif-row-' + id);
                                    try {
                                        const response = await fetch(`{{ url('/notifications') }}/${id}/mark-read`, {
                                            method: 'POST',
                                            headers: {
                                                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                                'Accept': 'application/json',
                                                'X-Requested-With': 'XMLHttpRequest'
                                            }
                                        });
                                        if (response.ok) {
                                            const data = await response.json();
                                            this.count = data.unread_count;
                                            if (el) {
                                                el.style.transition = 'all 0.25s ease-out';
                                                el.style.opacity = '0';
                                                el.style.transform = 'translateX(15px)';
                                                setTimeout(() => {
                                                    el.remove();
                                                    const remaining = document.querySelectorAll('.notif-item-card').length;
                                                    if (remaining === 0) {
                                                        const emptyEl = document.getElementById('notif-empty-state');
                                                        if (emptyEl) emptyEl.classList.remove('hidden');
                                                    }
                                                }, 250);
                                            }
                                        }
                                    } catch (err) {
                                        console.error(err);
                                    }
                                },
                                async markAllAsRead(event) {
                                    if (event) { event.stopPropagation(); event.preventDefault(); }
                                    try {
                                        const response = await fetch('{{ route("notifications.mark-all-read") }}', {
                                            method: 'POST',
                                            headers: {
                                                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                                'Accept': 'application/json',
                                                'X-Requested-With': 'XMLHttpRequest'
                                            }
                                        });
                                        if (response.ok) {
                                            this.count = 0;
                                            const items = document.querySelectorAll('.notif-item-card');
                                            items.forEach(el => el.remove());
                                            const emptyEl = document.getElementById('notif-empty-state');
                                            if (emptyEl) emptyEl.classList.remove('hidden');
                                        }
                                    } catch (err) {
                                        console.error(err);
                                    }
                                }
                            }
                        }
                    </script>

                    <!-- Masaüstü Kullanıcı Profil Menüsü (sm ve üstü) -->
                    <div class="hidden sm:flex sm:items-center">
                        <x-dropdown align="right" width="48">
                            <x-slot name="trigger">
                                <button
                                    class="inline-flex items-center px-4 py-2 border border-transparent text-sm leading-4 font-bold rounded-xl text-gray-700 bg-gray-50 hover:text-indigo-600 hover:bg-indigo-50 focus:outline-none transition ease-in-out duration-150 shadow-sm">
                                    @if(Auth::user()->avatar && \Illuminate\Support\Facades\Storage::disk('public')->exists(Auth::user()->avatar))
                                        <img src="{{ asset('storage/' . Auth::user()->avatar) }}"
                                            alt="{{ Auth::user()->name }}"
                                            class="w-8 h-8 rounded-full object-cover mr-3 border border-indigo-200 shadow-inner">
                                    @else
                                        <div
                                            class="w-8 h-8 rounded-full bg-indigo-600 text-white flex items-center justify-center mr-3 font-black text-xs shadow-inner">
                                            {{ mb_substr(Auth::user()->name, 0, 1) }}
                                        </div>
                                    @endif
                                    <div class="text-left flex flex-col">
                                        <span>{{ Auth::user()->name }}</span>
                                        <span
                                            class="text-[10px] font-normal text-gray-500">{{ Auth::user()->email }}</span>
                                    </div>

                                    <div class="ml-3">
                                        <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg"
                                            viewBox="0 0 20 20">
                                            <path fill-rule="evenodd"
                                                d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                                                clip-rule="evenodd" />
                                        </svg>
                                    </div>
                                </button>
                            </x-slot>

                            <x-dropdown-link :href="route('users.profile', Auth::id())"
                                class="font-bold text-indigo-700 bg-indigo-50/50 hover:bg-indigo-100/60">
                                <svg class="w-4 h-4 inline mr-2 text-indigo-600" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                </svg>
                                {{ __('Kullanıcı Profilim') }}
                            </x-dropdown-link>

                            <x-dropdown-link :href="route('profile.edit')" class="font-medium text-slate-600">
                                <svg class="w-4 h-4 inline mr-2 text-gray-400" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z">
                                    </path>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                </svg>
                                {{ __('Hesap Ayarları') }}
                            </x-dropdown-link>

                            <!-- Authentication -->
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <x-dropdown-link :href="route('logout')"
                                    onclick="event.preventDefault(); this.closest('form').submit();"
                                    class="text-red-600 hover:text-red-700 hover:bg-red-50 font-bold">
                                    <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1">
                                        </path>
                                    </svg>
                                    {{ __('Çıkış Yap') }}
                                </x-dropdown-link>
                            </form>
                        </x-dropdown>
                    </div>

                    <!-- Mobile / Tablet Hamburger Butonu -->
                    <div class="-mr-1 flex items-center lg:hidden">
                        <button @click="mobileOpen = !mobileOpen" type="button"
                            class="inline-flex items-center justify-center p-2 rounded-xl text-slate-600 hover:text-indigo-600 hover:bg-indigo-50 border border-slate-200 focus:outline-none transition-all duration-150"
                            aria-label="Menüyü Aç/Kapat">
                            <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                                <path x-show="!mobileOpen" stroke-linecap="round" stroke-linejoin="round"
                                    stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                                <path x-show="mobileOpen" x-cloak stroke-linecap="round" stroke-linejoin="round"
                                    stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <!-- Alt Bölüm: Menü Linkleri (Desktop: Sabit Navbar | Mobil: Accordion Menü) -->
    <nav class="bg-white lg:border-b lg:border-gray-200 lg:shadow-xs lg:sticky lg:top-0 z-50">
        <div class="w-full px-3 sm:px-6 lg:px-8">
            <div class="hidden lg:flex h-12 sm:h-14 items-center justify-center">
                <div class="flex flex-1 items-center justify-center w-full">

                    @php
                        $navClasses = "inline-flex items-center px-2.5 xl:px-3.5 py-1.5 text-xs xl:text-[13px] font-semibold rounded-lg transition-all duration-150 whitespace-nowrap shrink-0";
                        $activeClasses = "bg-indigo-50 text-indigo-700 shadow-2xs ring-1 ring-indigo-200";
                        $inactiveClasses = "text-gray-600 hover:text-indigo-600 hover:bg-gray-50";

                        $navUser = Auth::user();
                        $navPendingCount = 0;
                        if ($navUser) {
                            $permS = app(\App\Services\PermissionS::class);
                            if ($permS->hasPermission($navUser, 'form_entries.approve')) {
                                $navPendingCount = \App\Models\FormEntryM::where('status', 'submitted')->count();
                            } elseif ($permS->hasPermission($navUser, 'form_entries.approve_group')) {
                                $managedGIds = $navUser->managedGroups->pluck('id')->toArray();
                                $allGIds = array_unique(array_filter(array_merge([$navUser->group_id], $managedGIds)));
                                $navPendingCount = \App\Models\FormEntryM::where('status', 'submitted')
                                    ->whereHas('user', fn($q) => $q->whereIn('group_id', $allGIds))
                                    ->count();
                            }
                        }

                        $uRoleSlug = Auth::user()?->role?->slug ?? '';
                        $isSuperAdminUser = Auth::id() === 1 || Auth::user()?->role_id === 1 || $uRoleSlug === 'sistem_yoneticisi';

                        $canAccessUsers = Auth::user()->can('users.view_all') || Auth::user()->can('users.view_group') || Auth::user()->can('users.view') || Auth::user()->can('users.manage') || (Auth::user()->managedGroups && Auth::user()->managedGroups->isNotEmpty());
                        $canAccessGroups = Auth::user()->can('groups.manage');
                        $canAccessMasterData = Auth::user()->can('master_data.manage');
                        $canAccessRoles = Auth::user()->can('roles.manage');
                        $canAccessActiveSessions = Auth::user()->can('system.active_sessions');
                        $canAccessSettings = Auth::user()->can('settings.manage');

                        $canAccessYonetim = $canAccessUsers || $canAccessGroups || $canAccessMasterData || $canAccessRoles || $canAccessActiveSessions || $canAccessSettings;
                        $isYonetimActive = request()->routeIs('users.*', 'groups.*', 'master_data.*', 'roles.*', 'system.active_sessions*', 'settings.*');
                    @endphp

                    <!-- Masaüstü Linkler: SIFIR TAŞMA / SCROLLBAR YOK -->
                    <div class="hidden lg:flex items-center justify-center gap-1.5 xl:gap-2.5 mx-auto">
                        <!-- 1. Dashboard -->
                        <a href="{{ route('dashboard') }}"
                            class="{{ $navClasses }} {{ request()->routeIs('dashboard') ? $activeClasses : $inactiveClasses }}">
                            <svg class="w-3.5 h-3.5 mr-1.5 shrink-0" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6">
                                </path>
                            </svg>
                            {{ __('Dashboard') }}
                        </a>

                        <!-- 2. Veri Girişleri -->
                        <a href="{{ route('form-entries.index') }}"
                            class="{{ $navClasses }} {{ request()->routeIs('form-entries.*') ? $activeClasses : $inactiveClasses }} relative">
                            <svg class="w-3.5 h-3.5 mr-1.5 shrink-0" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2">
                                </path>
                            </svg>
                            <span>{{ __('Veri Girişleri') }}</span>
                            @if($navPendingCount > 0)
                                <span class="ml-1.5 flex items-center gap-1">
                                    <span class="relative flex h-2 w-2">
                                        <span
                                            class="animate-ping absolute inline-flex h-full w-full rounded-full bg-rose-400 opacity-75"></span>
                                        <span class="relative inline-flex rounded-full h-2 w-2 bg-rose-600"></span>
                                    </span>
                                    <span
                                        class="px-1.5 py-0.5 text-[10px] font-black rounded-full bg-rose-600 text-white shadow-xs animate-pulse">
                                        {{ $navPendingCount }}
                                    </span>
                                </span>
                            @endif
                        </a>

                        <!-- 3. Hedeflerim -->
                        @if(!$isSuperAdminUser && (Auth::user()->can('kpi_targets.view_own') || Auth::user()->can('kpi_targets.manage')))
                            <a href="{{ route('my-targets.index') }}"
                                class="{{ $navClasses }} {{ request()->routeIs('my-targets.*') ? $activeClasses : $inactiveClasses }}">
                                <svg class="w-3.5 h-3.5 mr-1.5 shrink-0" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138z">
                                    </path>
                                </svg>
                                {{ __('Hedeflerim') }}
                            </a>
                        @endif

                        <!-- 4. KPI Yönetimi -->
                        @if(Auth::user()->can('kpi_targets.manage') || Auth::user()->can('kpi_targets.view_all') || Auth::user()->can('kpi_targets.view_group') || Auth::user()->can('kpi_targets.create'))
                            <a href="{{ route('kpi-targets.index') }}"
                                class="{{ $navClasses }} {{ request()->routeIs('kpi-targets.*') ? $activeClasses : $inactiveClasses }}">
                                <svg class="w-3.5 h-3.5 mr-1.5 shrink-0" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z">
                                    </path>
                                </svg>
                                {{ __('KPI Yönetimi') }}
                            </a>
                        @endif

                        <!-- 5. Form Şablonları -->
                        @can('form_templates.manage')
                            <a href="{{ route('form-templates.index') }}"
                                class="{{ $navClasses }} {{ request()->routeIs('form-templates.*') ? $activeClasses : $inactiveClasses }}">
                                <svg class="w-3.5 h-3.5 mr-1.5 shrink-0" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10">
                                    </path>
                                </svg>
                                {{ __('Form Şablonları') }}
                            </a>
                        @endcan

                        <!-- 6. Raporlar -->
                        @if(Auth::user()->can('reports.view'))
                            <a href="{{ route('reports.index') }}"
                                class="{{ $navClasses }} {{ request()->routeIs('reports.*') ? $activeClasses : $inactiveClasses }}">
                                <svg class="w-3.5 h-3.5 mr-1.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z">
                                    </path>
                                </svg>
                                <span>{{ __('Raporlar') }}</span>
                            </a>
                        @endif

                        <!-- 6. Yönetim & Sistem Menüsü (Açılır Dropdown Menü) -->
                        @if($canAccessYonetim)
                            <div class="relative" x-data="{ open: false }" @click.outside="open = false"
                                @keydown.escape.window="open = false">
                                <button type="button" @click="open = !open"
                                    class="{{ $navClasses }} {{ $isYonetimActive ? $activeClasses : $inactiveClasses }} flex items-center gap-1.5 focus:outline-none cursor-pointer">
                                    <svg class="w-3.5 h-3.5 shrink-0 {{ $isYonetimActive ? 'text-indigo-600' : 'text-slate-500' }}"
                                        fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z">
                                        </path>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                    </svg>
                                    <span>{{ __('Yönetim') }}</span>
                                    <svg class="w-3.5 h-3.5 transition-transform duration-200"
                                        :class="{ 'rotate-180': open }" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M19 9l-7 7-7-7"></path>
                                    </svg>
                                </button>

                                <!-- Dropdown İçeriği -->
                                <div x-show="open" x-cloak x-transition:enter="transition ease-out duration-150"
                                    x-transition:enter-start="opacity-0 translate-y-1 scale-95"
                                    x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                                    x-transition:leave="transition ease-in duration-100"
                                    x-transition:leave-start="opacity-100 translate-y-0 scale-100"
                                    x-transition:leave-end="opacity-0 translate-y-1 scale-95"
                                    class="absolute right-0 top-full mt-2 w-72 bg-white rounded-2xl shadow-xl border border-slate-100 p-2 z-50 ring-1 ring-black/5 divide-y divide-slate-50"
                                    style="display: none;" @click="open = false">

                                    <div class="px-3 py-1.5 text-[10px] font-black uppercase tracking-wider text-slate-400">
                                        Sistem & Yönetim
                                    </div>

                                    <div class="py-1 space-y-0.5">
                                        @if($canAccessUsers)
                                            <a href="{{ route('users.index') }}"
                                                class="flex items-center gap-3 px-3 py-2 rounded-xl text-xs transition-colors group {{ request()->routeIs('users.*') ? 'bg-indigo-50/80 text-indigo-900 font-bold' : 'text-slate-700 hover:bg-slate-50 hover:text-indigo-600 font-medium' }}">
                                                <div
                                                    class="w-8 h-8 rounded-lg flex items-center justify-center shrink-0 {{ request()->routeIs('users.*') ? 'bg-indigo-600 text-white shadow-xs' : 'bg-indigo-50 text-indigo-600 group-hover:bg-indigo-100' }}">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                            d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z">
                                                        </path>
                                                    </svg>
                                                </div>
                                                <div class="flex-1 min-w-0">
                                                    <div class="leading-tight">{{ __('Kullanıcılar') }}</div>
                                                    <div class="text-[10px] text-slate-400 group-hover:text-slate-500 truncate">
                                                        Kullanıcı & rol yönetimi</div>
                                                </div>
                                            </a>
                                        @endif

                                        @if($canAccessGroups)
                                            <a href="{{ route('groups.index') }}"
                                                class="flex items-center gap-3 px-3 py-2 rounded-xl text-xs transition-colors group {{ request()->routeIs('groups.*') ? 'bg-indigo-50/80 text-indigo-900 font-bold' : 'text-slate-700 hover:bg-slate-50 hover:text-indigo-600 font-medium' }}">
                                                <div
                                                    class="w-8 h-8 rounded-lg flex items-center justify-center shrink-0 {{ request()->routeIs('groups.*') ? 'bg-blue-600 text-white shadow-xs' : 'bg-blue-50 text-blue-600 group-hover:bg-blue-100' }}">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                            d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z">
                                                        </path>
                                                    </svg>
                                                </div>
                                                <div class="flex-1 min-w-0">
                                                    <div class="leading-tight">{{ __('Gruplar') }}</div>
                                                    <div class="text-[10px] text-slate-400 group-hover:text-slate-500 truncate">
                                                        Departman ve grup hiyerarşisi</div>
                                                </div>
                                            </a>
                                        @endif

                                        @if($canAccessMasterData)
                                            <a href="{{ route('master_data.index') }}"
                                                class="flex items-center gap-3 px-3 py-2 rounded-xl text-xs transition-colors group {{ request()->routeIs('master_data.*') ? 'bg-indigo-50/80 text-indigo-900 font-bold' : 'text-slate-700 hover:bg-slate-50 hover:text-indigo-600 font-medium' }}">
                                                <div
                                                    class="w-8 h-8 rounded-lg flex items-center justify-center shrink-0 {{ request()->routeIs('master_data.*') ? 'bg-emerald-600 text-white shadow-xs' : 'bg-emerald-50 text-emerald-600 group-hover:bg-emerald-100' }}">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                            d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zm10 0a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zm10 0a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z">
                                                        </path>
                                                    </svg>
                                                </div>
                                                <div class="flex-1 min-w-0">
                                                    <div class="leading-tight">{{ __('Master Data') }}</div>
                                                    <div class="text-[10px] text-slate-400 group-hover:text-slate-500 truncate">
                                                        Şirket, tesis ve lokasyonlar</div>
                                                </div>
                                            </a>
                                        @endif

                                        @if($canAccessRoles)
                                            <a href="{{ route('roles.matrix') }}"
                                                class="flex items-center gap-3 px-3 py-2 rounded-xl text-xs transition-colors group {{ request()->routeIs('roles.*') ? 'bg-indigo-50/80 text-indigo-900 font-bold' : 'text-slate-700 hover:bg-slate-50 hover:text-indigo-600 font-medium' }}">
                                                <div
                                                    class="w-8 h-8 rounded-lg flex items-center justify-center shrink-0 {{ request()->routeIs('roles.*') ? 'bg-amber-600 text-white shadow-xs' : 'bg-amber-50 text-amber-600 group-hover:bg-amber-100' }}">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                            d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z">
                                                        </path>
                                                    </svg>
                                                </div>
                                                <div class="flex-1 min-w-0">
                                                    <div class="leading-tight">{{ __('Yetki Matrisi') }}</div>
                                                    <div class="text-[10px] text-slate-400 group-hover:text-slate-500 truncate">
                                                        Rol ve modül erişim izinleri</div>
                                                </div>
                                            </a>
                                        @endif

                                        @if($canAccessActiveSessions)
                                            <a href="{{ route('system.active_sessions') }}"
                                                class="flex items-center gap-3 px-3 py-2 rounded-xl text-xs transition-colors group {{ request()->routeIs('system.active_sessions*') ? 'bg-indigo-50/80 text-indigo-900 font-bold' : 'text-slate-700 hover:bg-slate-50 hover:text-indigo-600 font-medium' }}">
                                                <div
                                                    class="w-8 h-8 rounded-lg flex items-center justify-center shrink-0 {{ request()->routeIs('system.active_sessions*') ? 'bg-violet-600 text-white shadow-xs' : 'bg-violet-50 text-violet-600 group-hover:bg-violet-100' }}">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                            d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z">
                                                        </path>
                                                    </svg>
                                                </div>
                                                <div class="flex-1 min-w-0">
                                                    <div class="leading-tight">{{ __('Aktif Oturumlar') }}</div>
                                                    <div class="text-[10px] text-slate-400 group-hover:text-slate-500 truncate">
                                                        Canlı oturumlar ve IP logları</div>
                                                </div>
                                            </a>
                                        @endif

                                        @if(Auth::user()->can('settings.manage') || Auth::user()->can('logs.view'))
                                            <a href="{{ route('settings.activity_logs.index') }}"
                                                class="flex items-center gap-3 px-3 py-2 rounded-xl text-xs transition-colors group {{ request()->routeIs('settings.activity_logs.*') ? 'bg-indigo-50/80 text-indigo-900 font-bold' : 'text-slate-700 hover:bg-slate-50 hover:text-indigo-600 font-medium' }}">
                                                <div
                                                    class="w-8 h-8 rounded-lg flex items-center justify-center shrink-0 {{ request()->routeIs('settings.activity_logs.*') ? 'bg-indigo-600 text-white shadow-xs' : 'bg-indigo-50 text-indigo-600 group-hover:bg-indigo-100' }}">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                            d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01">
                                                        </path>
                                                    </svg>
                                                </div>
                                                <div class="flex-1 min-w-0">
                                                    <div class="leading-tight">{{ __('Sistem Günlüğü') }}</div>
                                                    <div class="text-[10px] text-slate-400 group-hover:text-slate-500 truncate">
                                                        Audit logs ve denetim kayıtları</div>
                                                </div>
                                            </a>
                                        @endif

                                        @if($canAccessSettings)
                                            <a href="{{ route('settings.index') }}"
                                                class="flex items-center gap-3 px-3 py-2 rounded-xl text-xs transition-colors group {{ request()->routeIs('settings.*') ? 'bg-indigo-50/80 text-indigo-900 font-bold' : 'text-slate-700 hover:bg-slate-50 hover:text-indigo-600 font-medium' }}">
                                                <div
                                                    class="w-8 h-8 rounded-lg flex items-center justify-center shrink-0 {{ request()->routeIs('settings.*') ? 'bg-slate-700 text-white shadow-xs' : 'bg-slate-100 text-slate-600 group-hover:bg-slate-200' }}">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                            d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z">
                                                        </path>
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                            d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                                    </svg>
                                                </div>
                                                <div class="flex-1 min-w-0">
                                                    <div class="leading-tight">{{ __('Ayarlar') }}</div>
                                                    <div class="text-[10px] text-slate-400 group-hover:text-slate-500 truncate">
                                                        E-posta, bildirim ve ayarlar</div>
                                                </div>
                                            </a>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- Mobil / Tablet Çekmecesi (< 1024px) -->
        <div x-show="mobileOpen" x-cloak x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 -translate-y-2" x-transition:enter-end="opacity-100 translate-y-0"
            x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100 translate-y-0"
            x-transition:leave-end="opacity-0 -translate-y-2"
            class="lg:hidden border-t border-slate-200/80 bg-white shadow-2xl max-h-[calc(100vh-5rem)] overflow-y-auto px-4 py-4 space-y-4"
            style="display: none;">

            <!-- Bölüm 1: Ana Menü -->
            <div>
                <p class="px-2 pb-1.5 text-[10px] font-black uppercase tracking-wider text-slate-400">Ana Menü</p>
                <div class="space-y-1">
                    <a href="{{ route('dashboard') }}"
                        class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs font-semibold transition-colors {{ request()->routeIs('dashboard') ? 'bg-indigo-50 text-indigo-700 font-bold border-l-4 border-indigo-600' : 'text-slate-700 hover:bg-slate-50 hover:text-indigo-600' }}">
                        <span
                            class="w-7 h-7 rounded-lg flex items-center justify-center shrink-0 {{ request()->routeIs('dashboard') ? 'bg-indigo-600 text-white' : 'bg-slate-100 text-slate-500' }}">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6">
                                </path>
                            </svg>
                        </span>
                        <span>{{ __('Dashboard') }}</span>
                    </a>

                    <a href="{{ route('form-entries.index') }}"
                        class="flex items-center justify-between px-3 py-2.5 rounded-xl text-xs font-semibold transition-colors {{ request()->routeIs('form-entries.*') ? 'bg-indigo-50 text-indigo-700 font-bold border-l-4 border-indigo-600' : 'text-slate-700 hover:bg-slate-50 hover:text-indigo-600' }}">
                        <span class="flex items-center gap-3">
                            <span
                                class="w-7 h-7 rounded-lg flex items-center justify-center shrink-0 {{ request()->routeIs('form-entries.*') ? 'bg-indigo-600 text-white' : 'bg-slate-100 text-slate-500' }}">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2">
                                    </path>
                                </svg>
                            </span>
                            <span>{{ __('Veri Girişleri') }}</span>
                        </span>
                        @if($navPendingCount > 0)
                            <span
                                class="px-2 py-0.5 text-[10px] font-black rounded-full bg-rose-600 text-white animate-pulse">
                                {{ $navPendingCount }} Onay Bekliyor
                            </span>
                        @endif
                    </a>

                    @if(!$isSuperAdminUser && (Auth::user()->can('kpi_targets.view_own') || Auth::user()->can('kpi_targets.manage')))
                        <a href="{{ route('my-targets.index') }}"
                            class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs font-semibold transition-colors {{ request()->routeIs('my-targets.*') ? 'bg-indigo-50 text-indigo-700 font-bold border-l-4 border-indigo-600' : 'text-slate-700 hover:bg-slate-50 hover:text-indigo-600' }}">
                            <span
                                class="w-7 h-7 rounded-lg flex items-center justify-center shrink-0 {{ request()->routeIs('my-targets.*') ? 'bg-indigo-600 text-white' : 'bg-slate-100 text-slate-500' }}">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138z">
                                    </path>
                                </svg>
                            </span>
                            <span>{{ __('Hedeflerim') }}</span>
                        </a>
                    @endif

                    @if(Auth::user()->can('kpi_targets.manage') || Auth::user()->can('kpi_targets.view_all') || Auth::user()->can('kpi_targets.view_group') || Auth::user()->can('kpi_targets.create'))
                        <a href="{{ route('kpi-targets.index') }}"
                            class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs font-semibold transition-colors {{ request()->routeIs('kpi-targets.*') ? 'bg-indigo-50 text-indigo-700 font-bold border-l-4 border-indigo-600' : 'text-slate-700 hover:bg-slate-50 hover:text-indigo-600' }}">
                            <span
                                class="w-7 h-7 rounded-lg flex items-center justify-center shrink-0 {{ request()->routeIs('kpi-targets.*') ? 'bg-indigo-600 text-white' : 'bg-slate-100 text-slate-500' }}">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z">
                                    </path>
                                </svg>
                            </span>
                            <span>{{ __('KPI Yönetimi') }}</span>
                        </a>
                    @endif

                    @can('form_templates.manage')
                        <a href="{{ route('form-templates.index') }}"
                            class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs font-semibold transition-colors {{ request()->routeIs('form-templates.*') ? 'bg-indigo-50 text-indigo-700 font-bold border-l-4 border-indigo-600' : 'text-slate-700 hover:bg-slate-50 hover:text-indigo-600' }}">
                            <span
                                class="w-7 h-7 rounded-lg flex items-center justify-center shrink-0 {{ request()->routeIs('form-templates.*') ? 'bg-indigo-600 text-white' : 'bg-slate-100 text-slate-500' }}">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10">
                                    </path>
                                </svg>
                            </span>
                            <span>{{ __('Form Şablonları') }}</span>
                        </a>
                    @endcan

                    @if(Auth::user()->can('reports.view'))
                        <a href="{{ route('reports.index') }}"
                            class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs font-semibold transition-colors {{ request()->routeIs('reports.*') ? 'bg-indigo-50 text-indigo-700 font-bold border-l-4 border-indigo-600' : 'text-slate-700 hover:bg-slate-50 hover:text-indigo-600' }}">
                            <span
                                class="w-7 h-7 rounded-lg flex items-center justify-center shrink-0 {{ request()->routeIs('reports.*') ? 'bg-indigo-600 text-white' : 'bg-slate-100 text-slate-500' }}">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z">
                                    </path>
                                </svg>
                            </span>
                            <span>{{ __('Raporlar') }}</span>
                        </a>
                    @endif
                </div>
            </div>

            <!-- Bölüm 2: Yönetim & Sistem -->
            @if($canAccessYonetim)
                <div class="pt-3 border-t border-slate-100">
                    <p class="px-2 pb-1.5 text-[10px] font-black uppercase tracking-wider text-slate-400">Yönetim & Sistem
                    </p>
                    <div class="space-y-1">
                        @if($canAccessUsers)
                            <a href="{{ route('users.index') }}"
                                class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs font-semibold transition-colors {{ request()->routeIs('users.*') ? 'bg-indigo-50 text-indigo-700 font-bold border-l-4 border-indigo-600' : 'text-slate-700 hover:bg-slate-50 hover:text-indigo-600' }}">
                                <span
                                    class="w-7 h-7 rounded-lg flex items-center justify-center shrink-0 {{ request()->routeIs('users.*') ? 'bg-indigo-600 text-white' : 'bg-indigo-50 text-indigo-600' }}">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z">
                                        </path>
                                    </svg>
                                </span>
                                <span>{{ __('Kullanıcılar') }}</span>
                            </a>
                        @endif

                        @if($canAccessGroups)
                            <a href="{{ route('groups.index') }}"
                                class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs font-semibold transition-colors {{ request()->routeIs('groups.*') ? 'bg-indigo-50 text-indigo-700 font-bold border-l-4 border-indigo-600' : 'text-slate-700 hover:bg-slate-50 hover:text-indigo-600' }}">
                                <span
                                    class="w-7 h-7 rounded-lg flex items-center justify-center shrink-0 {{ request()->routeIs('groups.*') ? 'bg-blue-600 text-white' : 'bg-blue-50 text-blue-600' }}">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z">
                                        </path>
                                    </svg>
                                </span>
                                <span>{{ __('Gruplar') }}</span>
                            </a>
                        @endif

                        @if($canAccessMasterData)
                            <a href="{{ route('master_data.index') }}"
                                class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs font-semibold transition-colors {{ request()->routeIs('master_data.*') ? 'bg-indigo-50 text-indigo-700 font-bold border-l-4 border-indigo-600' : 'text-slate-700 hover:bg-slate-50 hover:text-indigo-600' }}">
                                <span
                                    class="w-7 h-7 rounded-lg flex items-center justify-center shrink-0 {{ request()->routeIs('master_data.*') ? 'bg-emerald-600 text-white' : 'bg-emerald-50 text-emerald-600' }}">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zm10 0a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zm10 0a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z">
                                        </path>
                                    </svg>
                                </span>
                                <span>{{ __('Master Data') }}</span>
                            </a>
                        @endif

                        @if($canAccessRoles)
                            <a href="{{ route('roles.matrix') }}"
                                class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs font-semibold transition-colors {{ request()->routeIs('roles.*') ? 'bg-indigo-50 text-indigo-700 font-bold border-l-4 border-indigo-600' : 'text-slate-700 hover:bg-slate-50 hover:text-indigo-600' }}">
                                <span
                                    class="w-7 h-7 rounded-lg flex items-center justify-center shrink-0 {{ request()->routeIs('roles.*') ? 'bg-amber-600 text-white' : 'bg-amber-50 text-amber-600' }}">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z">
                                        </path>
                                    </svg>
                                </span>
                                <span>{{ __('Yetki Matrisi') }}</span>
                            </a>
                        @endif

                        @if($canAccessActiveSessions)
                            <a href="{{ route('system.active_sessions') }}"
                                class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs font-semibold transition-colors {{ request()->routeIs('system.active_sessions*') ? 'bg-indigo-50 text-indigo-700 font-bold border-l-4 border-indigo-600' : 'text-slate-700 hover:bg-slate-50 hover:text-indigo-600' }}">
                                <span
                                    class="w-7 h-7 rounded-lg flex items-center justify-center shrink-0 {{ request()->routeIs('system.active_sessions*') ? 'bg-violet-600 text-white' : 'bg-violet-50 text-violet-600' }}">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z">
                                        </path>
                                    </svg>
                                </span>
                                <span>{{ __('Aktif Oturumlar') }}</span>
                            </a>
                        @endif

                        @if(Auth::user()->can('settings.manage') || Auth::user()->can('logs.view'))
                            <a href="{{ route('settings.activity_logs.index') }}"
                                class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs font-semibold transition-colors {{ request()->routeIs('settings.activity_logs.*') ? 'bg-indigo-50 text-indigo-700 font-bold border-l-4 border-indigo-600' : 'text-slate-700 hover:bg-slate-50 hover:text-indigo-600' }}">
                                <span
                                    class="w-7 h-7 rounded-lg flex items-center justify-center shrink-0 {{ request()->routeIs('settings.activity_logs.*') ? 'bg-indigo-600 text-white' : 'bg-indigo-50 text-indigo-600' }}">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01">
                                        </path>
                                    </svg>
                                </span>
                                <span>{{ __('Sistem Günlüğü (Audit Logs)') }}</span>
                            </a>
                        @endif

                        @if($canAccessSettings)
                            <a href="{{ route('settings.index') }}"
                                class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs font-semibold transition-colors {{ request()->routeIs('settings.*') ? 'bg-indigo-50 text-indigo-700 font-bold border-l-4 border-indigo-600' : 'text-slate-700 hover:bg-slate-50 hover:text-indigo-600' }}">
                                <span
                                    class="w-7 h-7 rounded-lg flex items-center justify-center shrink-0 {{ request()->routeIs('settings.*') ? 'bg-slate-700 text-white' : 'bg-slate-100 text-slate-600' }}">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z">
                                        </path>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                    </svg>
                                </span>
                                <span>{{ __('Ayarlar') }}</span>
                            </a>
                        @endif
                    </div>
                </div>
            @endif

            <!-- Bölüm 3: Kullanıcı Profili & İşlemler -->
            <div class="pt-3 border-t border-slate-100">
                <div class="p-3 rounded-2xl bg-slate-50 border border-slate-200/80 flex items-center gap-3 mb-2">
                    @if(Auth::user()->avatar && \Illuminate\Support\Facades\Storage::disk('public')->exists(Auth::user()->avatar))
                        <img src="{{ asset('storage/' . Auth::user()->avatar) }}" alt="{{ Auth::user()->name }}"
                            class="w-10 h-10 rounded-xl object-cover border border-indigo-200 shadow-inner shrink-0">
                    @else
                        <div
                            class="w-10 h-10 rounded-xl bg-indigo-600 text-white flex items-center justify-center font-black text-sm shadow-inner shrink-0">
                            {{ mb_substr(Auth::user()->name, 0, 1) }}
                        </div>
                    @endif
                    <div class="flex-1 min-w-0">
                        <div class="font-bold text-xs text-slate-800 truncate">{{ Auth::user()->name }}</div>
                        <div class="text-[10px] text-slate-500 truncate">{{ Auth::user()->email }}</div>
                        <div class="mt-0.5">
                            <span
                                class="inline-flex items-center px-1.5 py-0.2 text-[9px] font-bold rounded bg-indigo-100 text-indigo-700">
                                {{ Auth::user()->role->name ?? 'Kullanıcı' }}
                            </span>
                        </div>
                    </div>
                </div>

                <div class="space-y-1">
                    <a href="{{ route('notifications.index') }}"
                        class="flex items-center justify-between px-3 py-2 rounded-xl text-xs font-semibold text-slate-700 hover:bg-slate-50 hover:text-indigo-600 transition-colors">
                        <span class="flex items-center gap-2.5">
                            <span
                                class="w-7 h-7 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9">
                                    </path>
                                </svg>
                            </span>
                            <span>{{ __('Bildirimler') }}</span>
                        </span>
                        @if(Auth::user()->unreadNotifications->count() > 0)
                            <span
                                class="px-2 py-0.5 text-[10px] font-black rounded-full bg-rose-600 text-white animate-pulse">
                                {{ Auth::user()->unreadNotifications->count() }}
                            </span>
                        @endif
                    </a>

                    <a href="{{ route('users.profile', Auth::id()) }}"
                        class="flex items-center gap-2.5 px-3 py-2 rounded-xl text-xs font-semibold text-slate-700 hover:bg-slate-50 hover:text-indigo-600 transition-colors">
                        <span class="w-7 h-7 rounded-lg bg-purple-50 text-purple-600 flex items-center justify-center">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                            </svg>
                        </span>
                        <span>{{ __('Kullanıcı Profilim') }}</span>
                    </a>

                    <a href="{{ route('profile.edit') }}"
                        class="flex items-center gap-2.5 px-3 py-2 rounded-xl text-xs font-semibold text-slate-700 hover:bg-slate-50 hover:text-indigo-600 transition-colors">
                        <span class="w-7 h-7 rounded-lg bg-slate-100 text-slate-600 flex items-center justify-center">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z">
                                </path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                            </svg>
                        </span>
                        <span>{{ __('Hesap Ayarları') }}</span>
                    </a>

                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit"
                            class="w-full flex items-center gap-2.5 px-3 py-2 rounded-xl text-xs font-bold text-rose-600 hover:bg-rose-50 transition-colors text-left">
                            <span class="w-7 h-7 rounded-lg bg-rose-50 text-rose-600 flex items-center justify-center">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1">
                                    </path>
                                </svg>
                            </span>
                            <span>{{ __('Çıkış Yap') }}</span>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </nav>
</div>