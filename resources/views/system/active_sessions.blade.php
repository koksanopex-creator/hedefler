@section('title', 'Aktif Oturumlar & Giriş Takibi')
<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            <div>
                <h2 class="font-bold text-2xl text-transparent bg-clip-text bg-gradient-to-r from-indigo-600 via-purple-600 to-pink-600 leading-tight">
                    {{ __('Aktif Oturumlar & Giriş Takibi') }}
                </h2>
                <p class="text-xs text-gray-500 mt-1">Hedefler uygulamasına giriş yapmış kullanıcılar, anlık çevrimiçi oturumlar ve login denetim kayıtları.</p>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('system.active_sessions') }}" class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-white/90 hover:bg-white text-indigo-700 border border-indigo-200 rounded-xl text-xs font-bold transition shadow-xs">
                    <svg class="w-4 h-4 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 1121.21 7.89M21 3v5h-5"></path></svg>
                    Yenile
                </a>
            </div>
        </div>
    </x-slot>

    <!-- Glassmorphism Container -->
    <div class="relative py-8 min-h-[calc(100vh-65px)] bg-slate-50/50 overflow-hidden"
         x-data="{ activeTab: 'sessions', terminateId: null, terminateName: '', terminateIp: '', showTerminateModal: false }">
        
        <!-- Decorative Blobs -->
        <div class="absolute top-20 left-10 w-96 h-96 bg-indigo-200 rounded-full mix-blend-multiply filter blur-[90px] opacity-25"></div>
        <div class="absolute top-40 right-10 w-96 h-96 bg-purple-200 rounded-full mix-blend-multiply filter blur-[90px] opacity-25"></div>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10 space-y-6">

            @if(session('success'))
                <div class="backdrop-blur-md bg-emerald-50/90 border border-emerald-200 text-emerald-800 px-5 py-3.5 rounded-2xl shadow-sm flex items-center gap-3 text-xs font-semibold">
                    <svg class="w-5 h-5 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    <span>{{ session('success') }}</span>
                </div>
            @endif

            @if(session('error'))
                <div class="backdrop-blur-md bg-rose-50/90 border border-rose-200 text-rose-800 px-5 py-3.5 rounded-2xl shadow-sm flex items-center gap-3 text-xs font-semibold">
                    <svg class="w-5 h-5 text-rose-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    <span>{{ session('error') }}</span>
                </div>
            @endif

            <!-- 4 ÖZET İSTATİSTİK KARTI -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <!-- Kart 1: Çevrimiçi Kullanıcı -->
                <div class="backdrop-blur-xl bg-white/80 border border-white/60 p-5 rounded-3xl shadow-[0_8px_30px_rgb(0,0,0,0.03)] flex items-center gap-4">
                    <div class="w-12 h-12 rounded-2xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-600 flex items-center justify-center shrink-0">
                        <span class="relative flex h-3.5 w-3.5">
                            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                            <span class="relative inline-flex rounded-full h-3.5 w-3.5 bg-emerald-500"></span>
                        </span>
                    </div>
                    <div>
                        <div class="text-2xl font-black text-gray-900">{{ $onlineCount }}</div>
                        <div class="text-xs font-bold text-gray-500">Çevrimiçi Kullanıcı</div>
                        <div class="text-[10px] text-emerald-600 font-semibold mt-0.5">Son 5 dakika içinde aktif</div>
                    </div>
                </div>

                <!-- Kart 2: Toplam Aktif Oturum -->
                <div class="backdrop-blur-xl bg-white/80 border border-white/60 p-5 rounded-3xl shadow-[0_8px_30px_rgb(0,0,0,0.03)] flex items-center gap-4">
                    <div class="w-12 h-12 rounded-2xl bg-indigo-500/10 border border-indigo-500/20 text-indigo-600 flex items-center justify-center shrink-0">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
                    </div>
                    <div>
                        <div class="text-2xl font-black text-gray-900">{{ $totalSessions }}</div>
                        <div class="text-xs font-bold text-gray-500">Açık Oturum Sayısı</div>
                        <div class="text-[10px] text-indigo-600 font-semibold mt-0.5">Veritabanında kayıtlı</div>
                    </div>
                </div>

                <!-- Kart 3: Bugün Giriş Yapanlar -->
                <div class="backdrop-blur-xl bg-white/80 border border-white/60 p-5 rounded-3xl shadow-[0_8px_30px_rgb(0,0,0,0.03)] flex items-center gap-4">
                    <div class="w-12 h-12 rounded-2xl bg-purple-500/10 border border-purple-500/20 text-purple-600 flex items-center justify-center shrink-0">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                    </div>
                    <div>
                        <div class="text-2xl font-black text-gray-900">{{ $todayLoginCount }}</div>
                        <div class="text-xs font-bold text-gray-500">Bugün Giriş Yapan</div>
                        <div class="text-[10px] text-purple-600 font-semibold mt-0.5">Farklı personel sayısı</div>
                    </div>
                </div>

                <!-- Kart 4: Son Giriş -->
                <div class="backdrop-blur-xl bg-white/80 border border-white/60 p-5 rounded-3xl shadow-[0_8px_30px_rgb(0,0,0,0.03)] flex items-center gap-4">
                    <div class="w-12 h-12 rounded-2xl bg-amber-500/10 border border-amber-500/20 text-amber-600 flex items-center justify-center shrink-0">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    </div>
                    <div>
                        <div class="text-sm font-black text-gray-900 truncate max-w-[150px]">
                            {{ $latestLogin ? \Illuminate\Support\Carbon::parse($latestLogin->created_at)->diffForHumans() : 'Henüz yok' }}
                        </div>
                        <div class="text-xs font-bold text-gray-500">Son Login Hareketi</div>
                        <div class="text-[10px] text-amber-700 font-semibold mt-0.5 truncate max-w-[150px]">
                            {{ $latestLogin ? $latestLogin->description : 'Kayıt yok' }}
                        </div>
                    </div>
                </div>
            </div>

            <!-- TAB SEÇİCİ -->
            <div class="flex items-center justify-between bg-white/70 backdrop-blur-xl border border-white/60 p-2 rounded-2xl shadow-xs">
                <div class="inline-flex p-1 bg-slate-100/90 rounded-xl border border-slate-200">
                    <button type="button" 
                            @click="activeTab = 'sessions'" 
                            :class="activeTab === 'sessions' ? 'bg-white text-indigo-700 shadow-xs font-bold' : 'text-gray-500 hover:text-gray-900 font-medium'" 
                            class="px-4 py-2 rounded-lg text-xs transition flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
                        <span>Canlı Aktif Oturumlar</span>
                        <span class="px-2 py-0.5 rounded-full text-[10px] font-extrabold bg-indigo-50 text-indigo-700 border border-indigo-200">
                            {{ $activeSessions->count() }}
                        </span>
                    </button>
                    <button type="button" 
                            @click="activeTab = 'logs'" 
                            :class="activeTab === 'logs' ? 'bg-white text-indigo-700 shadow-xs font-bold' : 'text-gray-500 hover:text-gray-900 font-medium'" 
                            class="px-4 py-2 rounded-lg text-xs transition flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                        <span>Uygulama Giriş Geçmişi</span>
                        <span class="px-2 py-0.5 rounded-full text-[10px] font-extrabold bg-purple-50 text-purple-700 border border-purple-200">
                            {{ $loginLogs->total() }}
                        </span>
                    </button>
                </div>

                <div class="text-xs text-gray-400 font-medium hidden sm:block pr-2">
                    Tablo genişliği ekrana tam sığdırılmıştır.
                </div>
            </div>

            <!-- ============================================== -->
            <!-- 1) CANLI AKTİF OTURUMLAR TABLOSU              -->
            <!-- ============================================== -->
            <div x-show="activeTab === 'sessions'" class="backdrop-blur-xl bg-white/80 border border-white/60 shadow-[0_8px_30px_rgb(0,0,0,0.04)] rounded-3xl overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 bg-gradient-to-r from-indigo-50/50 via-purple-50/20 to-white flex justify-between items-center">
                    <div>
                        <h3 class="text-base font-extrabold text-gray-900 tracking-tight flex items-center gap-2">
                            <span>💻</span> Canlı Oturum Listesi
                        </h3>
                        <p class="text-xs text-gray-500 mt-0.5">Hedefler uygulaması üzerinde oturumu açık olan kullanıcılar ve bağlantı bilgileri.</p>
                    </div>
                </div>

                <div class="w-full overflow-hidden">
                    <table class="w-full divide-y divide-gray-100 table-fixed">
                        <thead class="bg-gray-50/70 text-gray-500 text-[11px] font-bold uppercase tracking-wider">
                            <tr>
                                <th class="w-[28%] px-3.5 py-3 sm:px-4 sm:py-3.5 text-left">Kullanıcı</th>
                                <th class="w-[18%] px-3.5 py-3 sm:px-4 sm:py-3.5 text-left">Rol & Grup</th>
                                <th class="w-[14%] px-3.5 py-3 sm:px-4 sm:py-3.5 text-left">IP Adresi</th>
                                <th class="w-[14%] px-3.5 py-3 sm:px-4 sm:py-3.5 text-left">Cihaz / Tarayıcı</th>
                                <th class="w-[12%] px-3.5 py-3 sm:px-4 sm:py-3.5 text-left">Son Hareket</th>
                                <th class="w-[7%] px-3.5 py-3 sm:px-4 sm:py-3.5 text-left">Durum</th>
                                <th class="w-[7%] px-3.5 py-3 sm:px-4 sm:py-3.5 text-right">İşlem</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100/70 text-sm">
                            @forelse($activeSessions as $s)
                                <tr class="hover:bg-slate-50/70 transition-colors {{ $s->is_current ? 'bg-indigo-50/30' : '' }}">
                                    <!-- Kullanıcı -->
                                    <td class="px-3.5 py-3 sm:px-4 sm:py-3.5 whitespace-nowrap">
                                        <div class="flex items-center gap-3">
                                            <div class="h-9 w-9 rounded-full bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center text-white font-bold text-xs shrink-0 shadow-xs">
                                                {{ substr($s->user?->name ?? 'U', 0, 1) }}
                                            </div>
                                            <div class="min-w-0">
                                                <div class="font-extrabold text-gray-900 flex items-center gap-1.5 truncate" title="{{ $s->user?->name ?? 'Bilinmeyen Kullanıcı' }}">
                                                    <span>{{ $s->user?->name ?? 'Bilinmeyen Kullanıcı' }}</span>
                                                    @if($s->is_current)
                                                        <span class="px-1.5 py-0.2 rounded text-[10px] font-black bg-indigo-100 text-indigo-800 border border-indigo-200 shrink-0">BU OTURUM</span>
                                                    @endif
                                                </div>
                                                <div class="text-xs text-gray-500 font-medium truncate" title="{{ $s->user?->email }}">{{ $s->user?->email ?? '-' }}</div>
                                            </div>
                                        </div>
                                    </td>

                                    <!-- Rol & Grup -->
                                    <td class="px-3.5 py-3 sm:px-4 sm:py-3.5 whitespace-nowrap">
                                        <div class="truncate" title="{{ $s->user?->role?->name ?? 'Rol Yok' }}">
                                            <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-slate-100 text-slate-700 border border-slate-200">
                                                {{ $s->user?->role?->name ?? 'Tanımsız' }}
                                            </span>
                                        </div>
                                        <div class="text-xs text-gray-500 font-medium mt-1 truncate" title="{{ $s->user?->group?->name ?? 'Grup Yok' }}">
                                            {{ $s->user?->group?->name ?? 'Bağımsız' }}
                                        </div>
                                    </td>

                                    <!-- IP Adresi -->
                                    <td class="px-3.5 py-3 sm:px-4 sm:py-3.5 whitespace-nowrap text-xs font-mono font-bold text-gray-700 truncate" title="{{ $s->ip_address }}">
                                        {{ $s->ip_address }}
                                    </td>

                                    <!-- Cihaz / Tarayıcı -->
                                    <td class="px-3.5 py-3 sm:px-4 sm:py-3.5 whitespace-nowrap text-xs text-gray-600">
                                        <div class="font-bold text-gray-800">{{ $s->browser }}</div>
                                        <div class="text-[11px] text-gray-400 font-medium">{{ $s->platform }}</div>
                                    </td>

                                    <!-- Son Hareket -->
                                    <td class="px-3.5 py-3 sm:px-4 sm:py-3.5 whitespace-nowrap text-xs">
                                        <div class="font-bold text-gray-800">{{ $s->last_active_at->diffForHumans() }}</div>
                                        <div class="text-[10px] text-gray-400">{{ $s->last_active_at->format('H:i:s') }}</div>
                                    </td>

                                    <!-- Durum -->
                                    <td class="px-3.5 py-3 sm:px-4 sm:py-3.5 whitespace-nowrap">
                                        <span class="px-2.5 py-1 rounded-full text-[11px] font-bold border {{ $s->status_badge }}">
                                            {{ $s->status_label }}
                                        </span>
                                    </td>

                                    <!-- İşlem (Sonlandır) -->
                                    <td class="px-3.5 py-3 sm:px-4 sm:py-3.5 whitespace-nowrap text-right text-xs">
                                        @if($s->is_current)
                                            <span class="text-xs text-gray-400 italic">Aktif Oturum</span>
                                        @elseif(!auth()->user()?->isYonetim())
                                            <button type="button" 
                                                    @click="terminateId = '{{ $s->id }}'; terminateName = '{{ addslashes($s->user?->name ?? 'Kullanıcı') }}'; terminateIp = '{{ $s->ip_address }}'; showTerminateModal = true"
                                                    class="px-2.5 py-1 bg-rose-50 hover:bg-rose-600 text-rose-700 hover:text-white rounded-lg border border-rose-200 transition font-bold shadow-2xs"
                                                    title="Oturumu Sonlandır">
                                                Sonlandır
                                            </button>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="px-6 py-12 text-center text-gray-400 text-sm">
                                        Aktif oturum kaydı bulunmamaktadır.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- ============================================== -->
            <!-- 2) GİRİŞ HAREKETLERİ GEÇMİŞİ TABLOSU          -->
            <!-- ============================================== -->
            <div x-show="activeTab === 'logs'" style="display: none;" class="backdrop-blur-xl bg-white/80 border border-white/60 shadow-[0_8px_30px_rgb(0,0,0,0.04)] rounded-3xl overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 bg-gradient-to-r from-purple-50/50 via-indigo-50/20 to-white flex justify-between items-center">
                    <div>
                        <h3 class="text-base font-extrabold text-gray-900 tracking-tight flex items-center gap-2">
                            <span>🛡️</span> Giriş ve Çıkış Denetim Günlüğü
                        </h3>
                        <p class="text-xs text-gray-500 mt-0.5">Kullanıcıların sisteme giriş, çıkış ve oturum sonlandırma hareketleri.</p>
                    </div>
                </div>

                <div class="w-full overflow-hidden">
                    <table class="w-full divide-y divide-gray-100 table-fixed">
                        <thead class="bg-gray-50/70 text-gray-500 text-[11px] font-bold uppercase tracking-wider">
                            <tr>
                                <th class="w-[28%] px-3.5 py-3 sm:px-4 sm:py-3.5 text-left">Kullanıcı</th>
                                <th class="w-[16%] px-3.5 py-3 sm:px-4 sm:py-3.5 text-left">İşlem Türü</th>
                                <th class="w-[14%] px-3.5 py-3 sm:px-4 sm:py-3.5 text-left">IP Adresi</th>
                                <th class="w-[14%] px-3.5 py-3 sm:px-4 sm:py-3.5 text-left">Tarih & Saat</th>
                                <th class="w-[28%] px-3.5 py-3 sm:px-4 sm:py-3.5 text-left">Açıklama</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100/70 text-sm">
                            @forelse($loginLogs as $log)
                                @php
                                    $meta = \App\Services\ActivityLogS::getActionMeta($log->action);
                                @endphp
                                <tr class="hover:bg-slate-50/70 transition-colors">
                                    <!-- Kullanıcı -->
                                    <td class="px-3.5 py-3 sm:px-4 sm:py-3.5 whitespace-nowrap">
                                        <div class="font-extrabold text-gray-900 truncate" title="{{ $log->user?->name ?? 'Sistem' }}">
                                            {{ $log->user?->name ?? 'Sistem / Anonim' }}
                                        </div>
                                        <div class="text-xs text-gray-500 truncate" title="{{ $log->user?->email }}">{{ $log->user?->email ?? '-' }}</div>
                                    </td>

                                    <!-- İşlem Türü -->
                                    <td class="px-3.5 py-3 sm:px-4 sm:py-3.5 whitespace-nowrap">
                                        <span class="px-2.5 py-1 rounded-full text-[11px] font-bold border {{ $meta['badge'] }}">
                                            {{ $meta['label'] }}
                                        </span>
                                    </td>

                                    <!-- IP Adresi -->
                                    <td class="px-3.5 py-3 sm:px-4 sm:py-3.5 whitespace-nowrap text-xs font-mono font-bold text-gray-700 truncate" title="{{ $log->ip_address }}">
                                        {{ $log->ip_address ?? '-' }}
                                    </td>

                                    <!-- Tarih & Saat -->
                                    <td class="px-3.5 py-3 sm:px-4 sm:py-3.5 whitespace-nowrap text-xs">
                                        <div class="font-bold text-gray-800">{{ $log->created_at->format('d.m.Y H:i') }}</div>
                                        <div class="text-[10px] text-gray-400">{{ $log->created_at->diffForHumans() }}</div>
                                    </td>

                                    <!-- Açıklama -->
                                    <td class="px-3.5 py-3 sm:px-4 sm:py-3.5 text-xs text-gray-600 truncate font-medium" title="{{ $log->description }}">
                                        {{ $log->description }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-6 py-12 text-center text-gray-400 text-sm">
                                        Giriş hareketi kaydı bulunmamaktadır.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if($loginLogs->hasPages())
                    <div class="p-4 bg-gray-50/70 border-t border-gray-100">
                        {{ $loginLogs->links() }}
                    </div>
                @endif
            </div>

        </div>

        <!-- OTURUM SONLANDIRMA ONAY MODALI -->
        <div x-cloak x-show="showTerminateModal" class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="terminate-modal-title" role="dialog" aria-modal="true">
            <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                <div x-show="showTerminateModal" 
                     x-transition:enter="ease-out duration-300" 
                     x-transition:enter-start="opacity-0" 
                     x-transition:enter-end="opacity-100" 
                     x-transition:leave="ease-in duration-200" 
                     x-transition:leave-start="opacity-100" 
                     x-transition:leave-end="opacity-0" 
                     class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs transition-opacity" 
                     @click="showTerminateModal = false" 
                     aria-hidden="true"></div>

                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

                <div x-show="showTerminateModal" 
                     x-transition:enter="ease-out duration-300" 
                     x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" 
                     x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100" 
                     x-transition:leave="ease-in duration-200" 
                     x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100" 
                     x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" 
                     class="inline-block align-bottom bg-white rounded-3xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-md sm:w-full border border-slate-100">
                    
                    <form :action="'{{ url('system/active-sessions') }}/' + terminateId" method="POST">
                        @csrf
                        @method('DELETE')
                        
                        <div class="bg-white p-6 sm:p-7">
                            <div class="flex items-start gap-4">
                                <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-2xl bg-rose-50 text-rose-600 sm:mx-0 border border-rose-100">
                                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                                </div>
                                <div class="mt-0 text-left flex-1">
                                    <h3 class="text-base font-extrabold text-slate-900" id="terminate-modal-title">
                                        Aktif Oturumu Sonlandır
                                    </h3>
                                    <p class="text-xs text-slate-500 mt-1">
                                        Bu kullanıcının aktif oturumunu kapatmak istediğinize emin misiniz?
                                    </p>
                                </div>
                            </div>

                            <div class="mt-4 p-3.5 bg-slate-50 rounded-2xl border border-slate-100">
                                <div class="text-[11px] text-slate-500 font-semibold uppercase tracking-wider mb-1">Hedef Oturum:</div>
                                <div class="text-sm font-extrabold text-slate-900" x-text="terminateName"></div>
                                <div class="text-xs text-slate-600 font-mono mt-0.5" x-text="'IP: ' + terminateIp"></div>
                            </div>

                            <div class="mt-4 p-3 bg-rose-50 rounded-xl border border-rose-100 text-[11px] text-rose-700">
                                <span>Kullanıcı sistemden anında düşecek ve bir sonraki istekte yeniden oturum açması istenecektir.</span>
                            </div>
                        </div>

                        <div class="bg-slate-50 px-6 py-4 flex flex-col sm:flex-row-reverse gap-2 sm:gap-3 rounded-b-3xl border-t border-slate-100">
                            <button type="submit" class="w-full sm:w-auto inline-flex justify-center items-center px-5 py-2.5 bg-rose-600 hover:bg-rose-700 text-white text-xs font-bold rounded-xl shadow-sm transition">
                                Evet, Oturumu Kapat
                            </button>
                            <button type="button" @click="showTerminateModal = false" class="w-full sm:w-auto inline-flex justify-center items-center px-4 py-2.5 bg-white hover:bg-slate-100 text-slate-700 text-xs font-bold rounded-xl border border-slate-200 transition">
                                Vazgeç
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

    </div>
</x-app-layout>

