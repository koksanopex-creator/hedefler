@section('title', 'Merkezi Kullanıcı Senkronizasyonu')
<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            <div>
                <h2 class="font-bold text-2xl text-transparent bg-clip-text bg-gradient-to-r from-indigo-600 to-purple-600 leading-tight">
                    {{ __('Merkezi Kullanıcı Senkronizasyonu') }}
                </h2>
                <p class="text-xs text-gray-500 mt-1">Merkezi Yönetim Portalından (Port 8001) Hedefler uygulaması yetkisi verilen personellerin listesi ve anlık rol ataması.</p>
            </div>
            
            <div class="flex items-center gap-3">
                <form action="{{ route('users.sync.all') }}" method="POST" class="inline">
                    @csrf
                    <button type="submit" class="px-4 py-2 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 border border-indigo-200 rounded-xl text-xs font-bold transition-all shadow-xs flex items-center gap-1.5">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 1121.21 7.89M21 3v5h-5"></path></svg>
                        İsim & Bölüm Bilgilerini Eşitle
                    </button>
                </form>

                <a href="{{ $centralUrl }}/admin/users" target="_blank" class="px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white rounded-xl text-xs font-bold transition-all shadow-xs flex items-center gap-1.5">
                    ↗ Merkezi Kullanıcı Yönetimi
                </a>

                <a href="{{ route('users.index') }}" class="px-4 py-2 bg-white/80 hover:bg-white text-gray-700 border border-gray-200 rounded-xl text-xs font-semibold shadow-xs">
                    ← Kullanıcılar Listesi
                </a>
            </div>
        </div>
    </x-slot>

    <!-- Glassmorphism Background Wrapper -->
    <div class="relative py-8 min-h-[calc(100vh-65px)] bg-slate-50/50 overflow-hidden">
        <!-- Background Decorative Blobs -->
        <div class="absolute top-20 left-20 w-96 h-96 bg-purple-300 rounded-full mix-blend-multiply filter blur-[80px] opacity-30 animate-pulse"></div>
        <div class="absolute top-40 right-20 w-96 h-96 bg-indigo-300 rounded-full mix-blend-multiply filter blur-[80px] opacity-30 animate-pulse" style="animation-delay: 2s;"></div>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10 space-y-8">
            
            @if(session('success'))
                <div class="backdrop-blur-md bg-green-50/80 border border-green-200/50 text-green-700 px-6 py-4 rounded-2xl shadow-sm flex items-center gap-3">
                    <svg class="w-5 h-5 text-green-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    <span class="font-semibold text-sm">{{ session('success') }}</span>
                </div>
            @endif

            @if(session('error'))
                <div class="backdrop-blur-md bg-rose-50/80 border border-rose-200/50 text-rose-700 px-6 py-4 rounded-2xl shadow-sm flex items-center gap-3">
                    <svg class="w-5 h-5 text-rose-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                    <span class="font-semibold text-sm">{{ session('error') }}</span>
                </div>
            @endif

            <!-- Bilgi Kutusu -->
            <div class="p-4 bg-indigo-50/70 border border-indigo-100 rounded-2xl flex items-start gap-3">
                <div class="w-8 h-8 rounded-xl bg-indigo-600 text-white flex items-center justify-center shrink-0 shadow-sm text-sm">
                    💡
                </div>
                <div class="text-xs text-indigo-950 leading-relaxed">
                    <strong>Merkezi Atama Mekanizması:</strong> Merkezi Yönetim Sisteminde (<code class="bg-indigo-100/70 px-1.5 py-0.5 rounded text-indigo-800">/admin/users</code>) kullanıcıya <strong>"Hedefler Yönetim Sistemi"</strong> uygulaması tanımlandığında, kullanıcı otomatik olarak aşağıdaki listede belirir. Sistem yöneticisi bu ekrandan kullanıcıya <strong>Rol</strong> tanımlayana kadar, kullanıcı sisteme giriş yaptığında <strong class="text-amber-800">"Rolünüz verilmedi, onay bekleniyor"</strong> uyarısı görür.
                </div>
            </div>

            <!-- 1. BÖLÜM: ROL ATAMASI BEKLEYENLER -->
            <div class="backdrop-blur-xl bg-white/70 border border-amber-200/70 shadow-[0_8px_30px_rgb(0,0,0,0.04)] rounded-3xl overflow-hidden">
                <div class="p-6 border-b border-amber-100 bg-gradient-to-r from-amber-50/80 via-white to-white flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-2xl bg-gradient-to-br from-amber-500 to-orange-500 flex items-center justify-center text-white shadow-md shadow-amber-500/20 font-bold">
                            ⏳
                        </div>
                        <div>
                            <h3 class="text-base font-extrabold text-gray-900 tracking-tight flex items-center gap-2">
                                Rol Ataması Bekleyen Kullanıcılar
                                <span class="text-[11px] font-semibold text-amber-800 bg-amber-100 px-2.5 py-0.5 rounded-full">{{ count($pendingUsers) }} Kullanıcı</span>
                            </h3>
                            <p class="text-xs text-gray-500">Merkezi Portaldan uygulamaya atanmış ancak HEDEFLER'de henüz rolü seçilmemiş personeller.</p>
                        </div>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-100">
                        <thead class="bg-slate-50/50 text-gray-500 text-[11px] font-bold uppercase tracking-wider">
                            <tr>
                                <th class="px-6 py-3.5 text-left">Personel</th>
                                <th class="px-6 py-3.5 text-left">Merkezi Departman</th>
                                <th class="px-6 py-3.5 text-left">Tanımlanacak Rol *</th>
                                <th class="px-6 py-3.5 text-left">Grup (Opsiyonel)</th>
                                <th class="px-6 py-3.5 text-right">İşlem</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100/70 text-sm">
                            @forelse($pendingUsers as $pu)
                                <tr class="hover:bg-amber-50/30 transition-colors">
                                    <form action="{{ route('users.sync.assign_role', $pu->central_id) }}" method="POST">
                                        @csrf
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="font-bold text-gray-900">{{ $pu->name }}</div>
                                            <div class="text-xs text-gray-500">{{ $pu->email }}</div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-xs text-gray-600 font-medium">
                                            {{ $pu->department }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <select name="role_id" required class="bg-white border border-gray-300 rounded-xl px-3 py-1.5 text-xs text-gray-800 focus:ring-2 focus:ring-indigo-500">
                                                <option value="">Rol Seçiniz...</option>
                                                @foreach($roles as $role)
                                                    <option value="{{ $role->id }}">{{ $role->name }}</option>
                                                @endforeach
                                            </select>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <select name="group_id" class="bg-white border border-gray-300 rounded-xl px-3 py-1.5 text-xs text-gray-800 focus:ring-2 focus:ring-indigo-500">
                                                <option value="">Bağımsız (Grup Yok)</option>
                                                @foreach($groups as $group)
                                                    <option value="{{ $group->id }}">{{ $group->name }}</option>
                                                @endforeach
                                            </select>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-right">
                                            <button type="submit" class="px-4 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-bold shadow-sm transition-all flex items-center gap-1.5 ml-auto">
                                                <span>✓ Sisteme Dahil Et</span>
                                            </button>
                                        </td>
                                    </form>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-6 py-10 text-center text-gray-400 text-sm">
                                        Rol bekleyen yeni kullanıcı bulunmamaktadır. Tüm atanmış kullanıcılar aktif edilmiştir.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- 2. BÖLÜM: ROLÜ TANIMLANMIŞ VE AKTİF KULLANICILAR -->
            <div class="backdrop-blur-xl bg-white/70 border border-white/60 shadow-[0_8px_30px_rgb(0,0,0,0.04)] rounded-3xl overflow-hidden">
                <div class="p-6 border-b border-gray-100 bg-gradient-to-r from-emerald-50/50 to-white flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-2xl bg-gradient-to-br from-emerald-500 to-teal-600 flex items-center justify-center text-white shadow-md shadow-emerald-500/20 font-bold">
                            ✓
                        </div>
                        <div>
                            <h3 class="text-base font-extrabold text-gray-900 tracking-tight flex items-center gap-2">
                                Rolü Tanımlı ve Aktif Personeller
                                <span class="text-[11px] font-semibold text-emerald-800 bg-emerald-100 px-2.5 py-0.5 rounded-full">{{ count($syncedUsers) }} Aktif</span>
                            </h3>
                            <p class="text-xs text-gray-500">Merkezi Sistemle eşleşen ve Hedefler Portalı erişimi açık olan kullanıcılar.</p>
                        </div>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-100">
                        <thead class="bg-slate-50/50 text-gray-500 text-[11px] font-bold uppercase tracking-wider">
                            <tr>
                                <th class="px-6 py-3.5 text-left">Personel</th>
                                <th class="px-6 py-3.5 text-left">Departman</th>
                                <th class="px-6 py-3.5 text-left">Sistem Rolü</th>
                                <th class="px-6 py-3.5 text-left">Grubu</th>
                                <th class="px-6 py-3.5 text-right">İşlemler</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100/70 text-sm">
                            @forelse($syncedUsers as $su)
                                <tr class="hover:bg-slate-50/50 transition-colors">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="font-bold text-gray-900">{{ $su->name }}</div>
                                        <div class="text-xs text-gray-500">{{ $su->email }}</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-xs text-gray-600">
                                        {{ $su->department }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold bg-indigo-50 text-indigo-700 border border-indigo-100">
                                            {{ $su->local_user?->role?->name ?? 'Tanımsız' }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-xs text-gray-600">
                                        {{ $su->local_user?->group?->name ?? 'Grup Yok' }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right">
                                        @if($su->local_user)
                                            <a href="{{ route('users.edit', $su->local_user) }}" class="text-xs font-semibold text-indigo-600 hover:text-indigo-800">
                                                Rolü Düzenle →
                                            </a>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-6 py-8 text-center text-gray-400 text-sm">
                                        Henüz aktif kullanıcı bulunmamaktadır.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>
</x-app-layout>

