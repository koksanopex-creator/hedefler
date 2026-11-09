@section('title', 'Başvuru Yönetimi')
<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            <div>
                <h2 class="font-bold text-2xl text-transparent bg-clip-text bg-gradient-to-r from-indigo-600 to-purple-600 leading-tight">
                    {{ __('Uygulama Başvuru Yönetimi') }}
                </h2>
                <p class="text-xs text-gray-500 mt-1">Merkezi Sistemden Hedefler Portalı için yapılan kullanıcı erişim başvuruları ve yetkilendirme.</p>
            </div>
            
            <div class="flex items-center gap-3">
                <!-- Başvuruları Aç / Kapat Formu -->
                <form action="{{ route('users.applications.toggle') }}" method="POST" class="inline">
                    @csrf
                    <button type="submit" class="px-4 py-2 rounded-xl text-xs font-bold transition-all flex items-center gap-2 shadow-sm {{ $basvurularAcik ? 'bg-emerald-600 hover:bg-emerald-700 text-white' : 'bg-rose-600 hover:bg-rose-700 text-white' }}">
                        <span class="w-2.5 h-2.5 rounded-full {{ $basvurularAcik ? 'bg-emerald-200 animate-pulse' : 'bg-rose-200' }}"></span>
                        {{ $basvurularAcik ? 'Başvurular AÇIK (Kapatmak için tıkla)' : 'Başvurular KAPALI (Açmak için tıkla)' }}
                    </button>
                </form>

                <a href="{{ route('users.index') }}" class="px-4 py-2 bg-white/80 hover:bg-white text-gray-700 border border-gray-200 rounded-xl text-xs font-semibold shadow-xs">
                    ← Kullanıcılar
                </a>
            </div>
        </div>
    </x-slot>

    <!-- Glassmorphism Background Wrapper -->
    <div class="relative py-8 min-h-[calc(100vh-65px)] bg-slate-50/50 overflow-hidden" x-data="{ activeTab: 'pending', approveModal: false, rejectModal: false, selectedApp: null, rejectType: 'correctable' }">
        <!-- Background Blobs -->
        <div class="absolute top-20 left-20 w-96 h-96 bg-purple-300 rounded-full mix-blend-multiply filter blur-[80px] opacity-30 animate-pulse"></div>
        <div class="absolute top-40 right-20 w-96 h-96 bg-indigo-300 rounded-full mix-blend-multiply filter blur-[80px] opacity-30 animate-pulse" style="animation-delay: 2s;"></div>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10 space-y-6">
            
            @if(session('success'))
                <div class="backdrop-blur-md bg-green-50/80 border border-green-200/50 text-green-700 px-6 py-4 rounded-2xl shadow-sm flex items-center gap-3">
                    <svg class="w-5 h-5 text-green-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    <span class="font-semibold text-sm">{{ session('success') }}</span>
                </div>
            @endif

            <!-- Mini İstatistik ve Sekmeler -->
            <div class="flex flex-col sm:flex-row justify-between items-center gap-4 bg-white/70 backdrop-blur-xl border border-white/60 p-4 rounded-2xl shadow-sm">
                <div class="flex items-center gap-2">
                    <button type="button" @click="activeTab = 'pending'" :class="activeTab === 'pending' ? 'bg-indigo-600 text-white shadow-md' : 'bg-slate-100 text-gray-600 hover:bg-slate-200'" class="px-4 py-2 rounded-xl text-xs font-bold transition-all flex items-center gap-2">
                        <span>Bekleyen Başvurular</span>
                        <span class="px-2 py-0.5 rounded-full text-[10px] {{ $pendingApplications->count() > 0 ? 'bg-amber-400 text-amber-950 font-extrabold' : 'bg-white/20 text-white' }}">{{ $pendingApplications->count() }}</span>
                    </button>
                    <button type="button" @click="activeTab = 'rejected'" :class="activeTab === 'rejected' ? 'bg-rose-600 text-white shadow-md' : 'bg-slate-100 text-gray-600 hover:bg-slate-200'" class="px-4 py-2 rounded-xl text-xs font-bold transition-all flex items-center gap-2">
                        <span>Reddedilenler</span>
                        <span class="px-2 py-0.5 rounded-full text-[10px] bg-slate-200 text-slate-800">{{ $rejectedApplications->count() }}</span>
                    </button>
                    <button type="button" @click="activeTab = 'approved'" :class="activeTab === 'approved' ? 'bg-emerald-600 text-white shadow-md' : 'bg-slate-100 text-gray-600 hover:bg-slate-200'" class="px-4 py-2 rounded-xl text-xs font-bold transition-all flex items-center gap-2">
                        <span>Son Onaylananlar</span>
                        <span class="px-2 py-0.5 rounded-full text-[10px] bg-slate-200 text-slate-800">{{ $approvedApplications->count() }}</span>
                    </button>
                </div>

                <div class="text-xs text-gray-500 font-medium">
                    Durum: <strong class="{{ $basvurularAcik ? 'text-emerald-600' : 'text-rose-600' }}">{{ $basvurularAcik ? 'Yeni Başvuru Alımı Açık' : 'Yeni Başvuru Alımı Kapalı' }}</strong>
                </div>
            </div>

            <!-- 1. BEKLEYEN BAŞVURULAR -->
            <div x-show="activeTab === 'pending'" class="backdrop-blur-xl bg-white/70 border border-white/60 shadow-sm rounded-3xl overflow-hidden">
                <div class="p-6 border-b border-gray-100/70 bg-gradient-to-r from-amber-50/50 to-transparent">
                    <h3 class="text-base font-bold text-gray-800 flex items-center gap-2">
                        <svg class="w-5 h-5 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        Onay Bekleyen Başvurular ({{ $pendingApplications->count() }})
                    </h3>
                    <p class="text-xs text-gray-500 mt-0.5">Merkezi Portaldan başvuru yapan kullanıcıları inceleyip rol tanımlayarak onaylayabilir veya reddedebilirsiniz.</p>
                </div>

                <div class="overflow-x-auto w-full">
                    <table class="min-w-full divide-y divide-gray-100 text-left text-xs">
                        <thead class="bg-slate-50/50 text-gray-500 text-[11px] font-bold uppercase tracking-wider">
                            <tr>
                                <th class="px-4 py-3.5 text-left whitespace-nowrap min-w-[200px]">Başvuran</th>
                                <th class="px-4 py-3.5 text-left whitespace-nowrap min-w-[150px]">Merkezi Bölüm</th>
                                <th class="px-4 py-3.5 text-left min-w-[220px]">Talep Notu</th>
                                <th class="px-4 py-3.5 text-left whitespace-nowrap min-w-[120px]">Tarih</th>
                                <th class="px-4 py-3.5 text-right whitespace-nowrap min-w-[220px]">İşlemler</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100/70 text-sm">
                            @forelse($pendingApplications as $app)
                                <tr class="hover:bg-amber-50/30 transition-colors">
                                    <td class="px-4 py-3.5 whitespace-nowrap">
                                        <div class="font-bold text-gray-900">{{ $app->name }}</div>
                                        <div class="text-xs text-gray-500 truncate max-w-[200px]">{{ $app->email }}</div>
                                    </td>
                                    <td class="px-4 py-3.5 whitespace-nowrap">
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-medium bg-slate-100 text-slate-700">
                                            {{ $app->department ?? 'Tanımsız' }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3.5">
                                        <div class="text-xs text-gray-600 max-w-sm truncate" title="{{ $app->application_note }}">
                                            {{ $app->application_note ?? '-' }}
                                        </div>
                                    </td>
                                    <td class="px-4 py-3.5 whitespace-nowrap text-xs text-gray-500">
                                        {{ $app->created_at->format('d.m.Y H:i') }}
                                    </td>
                                    <td class="px-4 py-3.5 whitespace-nowrap text-right space-x-2">
                                        <button type="button" @click="selectedApp = {{ json_encode($app) }}; approveModal = true" class="px-3 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-xs font-semibold shadow-sm transition-all cursor-pointer">
                                            ✓ Onayla & Rol Ver
                                        </button>
                                        <button type="button" @click="selectedApp = {{ json_encode($app) }}; rejectModal = true" class="px-3 py-1.5 bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200 rounded-lg text-xs font-semibold transition-all cursor-pointer">
                                            ✕ Reddet
                                        </button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-6 py-12 text-center text-gray-400 text-sm">
                                        Onay bekleyen başvuru bulunmamaktadır.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- 2. REDDEDİLEN BAŞVURULAR -->
            <div x-show="activeTab === 'rejected'" class="backdrop-blur-xl bg-white/70 border border-white/60 shadow-sm rounded-3xl overflow-hidden">
                <div class="p-6 border-b border-gray-100/70 bg-gradient-to-r from-rose-50/50 to-transparent">
                    <h3 class="text-base font-bold text-gray-800 flex items-center gap-2">
                        <svg class="w-5 h-5 text-rose-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"></path></svg>
                        Reddedilen Başvurular ({{ $rejectedApplications->count() }})
                    </h3>
                    <p class="text-xs text-gray-500 mt-0.5">Kesin veya düzeltilebilir ret kararı verilen kullanıcılar. Kesin ret kararını buradan silerseniz kullanıcı tekrar başvurabilir.</p>
                </div>

                <div class="overflow-x-auto w-full">
                    <table class="min-w-full divide-y divide-gray-100 text-left text-xs">
                        <thead class="bg-slate-50/50 text-gray-500 text-[11px] font-bold uppercase tracking-wider">
                            <tr>
                                <th class="px-4 py-3.5 text-left whitespace-nowrap min-w-[200px]">Kullanıcı</th>
                                <th class="px-4 py-3.5 text-left whitespace-nowrap min-w-[140px]">Red Türü</th>
                                <th class="px-4 py-3.5 text-left min-w-[240px]">Gerekçeli Karar</th>
                                <th class="px-4 py-3.5 text-left whitespace-nowrap min-w-[140px]">Reddeden / Tarih</th>
                                <th class="px-4 py-3.5 text-right whitespace-nowrap min-w-[150px]">İşlemler</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100/70 text-sm">
                            @forelse($rejectedApplications as $app)
                                <tr class="hover:bg-rose-50/20 transition-colors">
                                    <td class="px-4 py-3.5 whitespace-nowrap">
                                        <div class="font-bold text-gray-900">{{ $app->name }}</div>
                                        <div class="text-xs text-gray-500 truncate max-w-[200px]" title="{{ $app->email }}">{{ $app->email }}</div>
                                    </td>
                                    <td class="px-4 py-3.5 whitespace-nowrap">
                                        @if($app->rejection_type === 'permanent')
                                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-bold bg-rose-100 text-rose-800 border border-rose-200">
                                                🚫 Kesin Red
                                            </span>
                                        @else
                                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-bold bg-amber-100 text-amber-800 border border-amber-200">
                                                ⚠️ Düzeltilebilir Red
                                            </span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3.5">
                                        <div class="text-xs text-gray-700 font-medium truncate max-w-sm" title="{{ $app->rejection_reason }}">
                                            {{ $app->rejection_reason ?? '-' }}
                                        </div>
                                    </td>
                                    <td class="px-4 py-3.5 whitespace-nowrap text-xs text-gray-500">
                                        <div>{{ $app->reviewer?->name ?? 'Yönetim' }}</div>
                                        <div class="text-[10px] text-gray-400">{{ $app->reviewed_at?->format('d.m.Y H:i') }}</div>
                                    </td>
                                    <td class="px-4 py-3.5 whitespace-nowrap text-right">
                                        <form action="{{ route('users.applications.destroy_rejection', $app) }}" method="POST" onsubmit="return confirm('Bu kullanıcının ret kararını silmek istediğinize emin misiniz? Kullanıcı tekrar başvuru yapabilecek.');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg text-xs font-semibold transition-all cursor-pointer">
                                                🗑️ Ret Kararını Sil
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-6 py-12 text-center text-gray-400 text-sm">
                                        Reddedilen başvuru kaydı bulunmamaktadır.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- 3. SON ONAYLANANLAR -->
            <div x-show="activeTab === 'approved'" class="backdrop-blur-xl bg-white/70 border border-white/60 shadow-sm rounded-3xl overflow-hidden">
                <div class="p-6 border-b border-gray-100/70 bg-gradient-to-r from-emerald-50/50 to-transparent">
                    <h3 class="text-base font-bold text-gray-800 flex items-center gap-2">
                        <svg class="w-5 h-5 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                        Son Onaylanan Başvurular
                    </h3>
                </div>

                <div class="overflow-x-auto w-full">
                    <table class="min-w-full divide-y divide-gray-100 text-left text-xs">
                        <thead class="bg-slate-50/50 text-gray-500 text-[11px] font-bold uppercase tracking-wider">
                            <tr>
                                <th class="px-4 py-3.5 text-left whitespace-nowrap min-w-[220px]">Kullanıcı</th>
                                <th class="px-4 py-3.5 text-left whitespace-nowrap min-w-[160px]">Bölüm</th>
                                <th class="px-4 py-3.5 text-left whitespace-nowrap min-w-[160px]">Tanımlanan Rol</th>
                                <th class="px-4 py-3.5 text-left whitespace-nowrap min-w-[160px]">Onaylayan / Tarih</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100/70 text-sm">
                            @forelse($approvedApplications as $app)
                                <tr class="hover:bg-emerald-50/20 transition-colors">
                                    <td class="px-4 py-3.5 whitespace-nowrap">
                                        <div class="font-bold text-gray-900">{{ $app->name }}</div>
                                        <div class="text-xs text-gray-500 truncate max-w-[200px]" title="{{ $app->email }}">{{ $app->email }}</div>
                                    </td>
                                    <td class="px-4 py-3.5 whitespace-nowrap text-xs text-gray-600 truncate max-w-[160px]" title="{{ $app->department }}">
                                        {{ $app->department ?? '-' }}
                                    </td>
                                    <td class="px-4 py-3.5 whitespace-nowrap">
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800 border border-emerald-200">
                                            {{ $app->user?->role?->name ?? 'Rol Atandı' }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3.5 whitespace-nowrap text-xs text-gray-500">
                                        <div>{{ $app->reviewer?->name ?? 'Yönetim' }}</div>
                                        <div class="text-[10px] text-gray-400">{{ $app->reviewed_at?->format('d.m.Y H:i') }}</div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-6 py-12 text-center text-gray-400 text-sm">
                                        Kayıt bulunamadı.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

        </div>

        <!-- ONAY MODALI -->
        <div x-show="approveModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm">
            <div @click.away="approveModal = false" class="bg-white rounded-3xl max-w-md w-full p-6 shadow-2xl border border-white/60 space-y-5">
                <div class="flex justify-between items-center pb-3 border-b border-gray-100">
                    <h3 class="text-lg font-bold text-gray-900">Başvuruyu Onayla & Rol Ata</h3>
                    <button type="button" @click="approveModal = false" class="text-gray-400 hover:text-gray-600">✕</button>
                </div>

                <template x-if="selectedApp">
                    <form :action="'{{ url('users/applications') }}/' + selectedApp.id + '/approve'" method="POST" class="space-y-4">
                        @csrf
                        <div class="p-3 bg-slate-50 rounded-xl text-xs space-y-1">
                            <div><strong>Ad Soyad:</strong> <span x-text="selectedApp.name"></span></div>
                            <div><strong>E-posta:</strong> <span x-text="selectedApp.email"></span></div>
                            <div><strong>Departman:</strong> <span x-text="selectedApp.department || '-'"></span></div>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-1">Kullanıcı Rolü (Zorunlu) *</label>
                            <select name="role_id" required class="w-full bg-slate-50 border border-gray-300 rounded-xl px-3 py-2 text-sm text-gray-800 focus:ring-2 focus:ring-indigo-500">
                                <option value="">Rol Seçiniz...</option>
                                @foreach($roles as $role)
                                    <option value="{{ $role->id }}">{{ $role->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-1">Ait Olduğu Grup (İsteğe Bağlı)</label>
                            <select name="group_id" class="w-full bg-slate-50 border border-gray-300 rounded-xl px-3 py-2 text-sm text-gray-800 focus:ring-2 focus:ring-indigo-500">
                                <option value="">Bağımsız (Grup Yok)</option>
                                @foreach($groups as $group)
                                    <option value="{{ $group->id }}">{{ $group->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="pt-3 flex items-center justify-end gap-3">
                            <button type="button" @click="approveModal = false" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-gray-700 rounded-xl text-xs font-semibold">Vazgeç</button>
                            <button type="submit" class="px-5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-bold shadow-md">✓ Onayla ve Yetkilendir</button>
                        </div>
                    </form>
                </template>
            </div>
        </div>

        <!-- RED MODALI -->
        <div x-show="rejectModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm">
            <div @click.away="rejectModal = false" class="bg-white rounded-3xl max-w-md w-full p-6 shadow-2xl border border-white/60 space-y-5">
                <div class="flex justify-between items-center pb-3 border-b border-gray-100">
                    <h3 class="text-lg font-bold text-gray-900">Başvuruyu Reddet</h3>
                    <button type="button" @click="rejectModal = false" class="text-gray-400 hover:text-gray-600">✕</button>
                </div>

                <template x-if="selectedApp">
                    <form :action="'{{ url('users/applications') }}/' + selectedApp.id + '/reject'" method="POST" class="space-y-4">
                        @csrf
                        <div class="p-3 bg-slate-50 rounded-xl text-xs space-y-1">
                            <div><strong>Kullanıcı:</strong> <span x-text="selectedApp.name"></span> (<span x-text="selectedApp.email"></span>)</div>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-2">Red Türü Seçin *</label>
                            <div class="space-y-2">
                                <label class="flex items-start gap-2.5 p-3 rounded-xl border border-amber-200 bg-amber-50/50 cursor-pointer">
                                    <input type="radio" name="rejection_type" value="correctable" x-model="rejectType" class="mt-0.5 text-amber-600">
                                    <div>
                                        <div class="text-xs font-bold text-amber-900">Düzeltilebilir Red (Gerekçeli)</div>
                                        <div class="text-[11px] text-amber-700">Kullanıcı Merkezi Portaldan bilgilerini (ad soyad, mail vb.) düzeltip tekrar başvuru yapabilir.</div>
                                    </div>
                                </label>
                                <label class="flex items-start gap-2.5 p-3 rounded-xl border border-rose-200 bg-rose-50/50 cursor-pointer">
                                    <input type="radio" name="rejection_type" value="permanent" x-model="rejectType" class="mt-0.5 text-rose-600">
                                    <div>
                                        <div class="text-xs font-bold text-rose-900">Kesin Red (Kalıcı Engel)</div>
                                        <div class="text-[11px] text-rose-700">Kullanıcı bir daha başvuru yapamaz. Yönetici ret kararını silene kadar engelli kalır.</div>
                                    </div>
                                </label>
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-1">Gerekçeli Karar Metni *</label>
                            <textarea name="rejection_reason" required rows="3" class="w-full bg-slate-50 border border-gray-300 rounded-xl p-3 text-xs text-gray-800 focus:ring-2 focus:ring-rose-500" placeholder="Örn: Mail adresinizi şirket uzantılı olarak güncelleyiniz..."></textarea>
                        </div>

                        <div class="pt-3 flex items-center justify-end gap-3">
                            <button type="button" @click="rejectModal = false" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-gray-700 rounded-xl text-xs font-semibold">Vazgeç</button>
                            <button type="submit" class="px-5 py-2 bg-rose-600 hover:bg-rose-700 text-white rounded-xl text-xs font-bold shadow-md">✕ Reddet</button>
                        </div>
                    </form>
                </template>
            </div>
        </div>

    </div>
</x-app-layout>

