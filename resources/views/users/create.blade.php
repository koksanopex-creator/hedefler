@section('title', 'Yeni Kullanıcı')
<x-app-layout>
    <x-slot name="header">
        <h2 class="font-bold text-2xl text-transparent bg-clip-text bg-gradient-to-r from-indigo-600 to-purple-600 leading-tight">
            {{ isset($user) ? 'Kullanıcı Düzenle: ' . $user->name : 'Yeni Kullanıcı Oluştur' }}
        </h2>
    </x-slot>

    <!-- Glassmorphism Background Wrapper -->
    <div class="relative py-12 min-h-[calc(100vh-65px)] bg-slate-50/50 overflow-hidden">
        <!-- Background Decorative Blobs -->
        <div class="absolute top-10 left-1/4 w-[500px] h-[500px] bg-purple-300 rounded-full mix-blend-multiply filter blur-[100px] opacity-30 animate-pulse"></div>
        <div class="absolute bottom-10 right-1/4 w-[500px] h-[500px] bg-indigo-300 rounded-full mix-blend-multiply filter blur-[100px] opacity-30 animate-pulse" style="animation-delay: 2s;"></div>

        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 relative z-10">
            
            <div class="backdrop-blur-xl bg-white/70 border border-white/50 shadow-[0_8px_30px_rgb(0,0,0,0.06)] sm:rounded-3xl p-8 lg:p-12 space-y-8">
                
                <!-- Merkezi API Bilgilendirme Banner'ı -->
                <div class="p-6 backdrop-blur-md bg-indigo-50/80 border border-indigo-200/70 rounded-2xl">
                    <div class="flex items-start gap-4">
                        <div class="w-12 h-12 rounded-2xl bg-indigo-600 text-white flex items-center justify-center font-bold text-xl shadow-md shrink-0">
                            💡
                        </div>
                        <div class="space-y-2">
                            <h4 class="text-base font-bold text-indigo-950">Kullanıcılar Merkezi Sistemden Eklenir</h4>
                            <p class="text-xs text-indigo-800 leading-relaxed">
                                Köksan Hedefler Portalı SSO altyapısına bağlıdır. Kullanıcıların kimlik, şifre ve departman tanımlamaları Merkezi Yönetim Portalı üzerinden yapılmaktadır.
                            </p>
                            <div class="pt-2 flex flex-wrap gap-3">
                                <a href="{{ route('users.sync') }}" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-bold transition-all shadow-sm flex items-center gap-1.5">
                                    🔄 Merkezi Senkronizasyon Ekranına Git
                                </a>
                                <a href="http://localhost:8001/admin/users" target="_blank" class="px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white rounded-xl text-xs font-bold transition-all shadow-sm flex items-center gap-1.5">
                                    ↗ Merkezi Kullanıcı Yönetimi (Port 8001)
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                
                @if($errors->any())
                    <div class="backdrop-blur-md bg-red-50/80 border border-red-200 text-red-600 px-6 py-4 rounded-2xl shadow-sm mb-8">
                        <div class="flex items-center gap-2 font-bold mb-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            Hatalar Bulundu
                        </div>
                        <ul class="list-disc list-inside text-sm">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @php
                    $action = isset($user) ? route('users.update', $user) : route('users.store');
                @endphp

                <form action="{{ $action }}" method="POST" class="space-y-8">
                    @csrf
                    @if(isset($user))
                        @method('PUT')
                    @endif
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                        <div class="space-y-2">
                            <label class="block text-sm font-semibold text-gray-700">Ad Soyad</label>
                            <input type="text" name="name" value="{{ old('name', $user->name ?? '') }}" required 
                                class="w-full bg-white/50 backdrop-blur-sm border border-gray-200 rounded-xl px-4 py-3 text-gray-700 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500/50 focus:border-indigo-500 focus:bg-white transition-all shadow-sm">
                        </div>
                        <div class="space-y-2">
                            <label class="block text-sm font-semibold text-gray-700">Email Adresi</label>
                            <input type="email" name="email" value="{{ old('email', $user->email ?? '') }}" required 
                                class="w-full bg-white/50 backdrop-blur-sm border border-gray-200 rounded-xl px-4 py-3 text-gray-700 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500/50 focus:border-indigo-500 focus:bg-white transition-all shadow-sm">
                        </div>
                    </div>

                    <div class="space-y-2">
                        <label class="block text-sm font-semibold text-gray-700">Şifre <span class="text-xs text-gray-400 font-normal ml-1">{{ isset($user) ? '(Değiştirmek istemiyorsanız boş bırakın)' : '' }}</span></label>
                        <input type="password" name="password" {{ isset($user) ? '' : 'required' }} 
                            class="w-full bg-white/50 backdrop-blur-sm border border-gray-200 rounded-xl px-4 py-3 text-gray-700 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500/50 focus:border-indigo-500 focus:bg-white transition-all shadow-sm">
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                        <div class="space-y-2">
                            <label class="block text-sm font-semibold text-gray-700">Rol (Sistem Yetkisi)</label>
                            <select name="role_id" required class="w-full bg-white/50 backdrop-blur-sm border border-gray-200 rounded-xl px-4 py-3 text-gray-700 focus:outline-none focus:ring-2 focus:ring-indigo-500/50 focus:border-indigo-500 focus:bg-white transition-all shadow-sm">
                                <option value="">Seçiniz...</option>
                                @foreach($roles as $role)
                                    <option value="{{ $role->id }}" {{ old('role_id', $user->role_id ?? '') == $role->id ? 'selected' : '' }}>
                                        {{ $role->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="space-y-2">
                            <label class="block text-sm font-semibold text-gray-700">Ait Olduğu Grup</label>
                            <select name="group_id" class="w-full bg-white/50 backdrop-blur-sm border border-gray-200 rounded-xl px-4 py-3 text-gray-700 focus:outline-none focus:ring-2 focus:ring-indigo-500/50 focus:border-indigo-500 focus:bg-white transition-all shadow-sm">
                                <option value="">Bağımsız (Grup Yok)</option>
                                @foreach($groups as $group)
                                    <option value="{{ $group->id }}" {{ old('group_id', $user->group_id ?? '') == $group->id ? 'selected' : '' }}>
                                        {{ $group->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <!-- Yönettiği Gruplar Glass Card -->
                    <div class="p-6 backdrop-blur-md bg-white/40 border border-white rounded-2xl shadow-sm relative overflow-hidden">
                        <div class="absolute top-0 left-0 w-2 h-full bg-gradient-to-b from-indigo-400 to-purple-500"></div>
                        <label class="block text-base font-bold text-gray-800 mb-1 pl-4">Yönettiği Gruplar</label>
                        <p class="text-sm text-gray-500 mb-5 pl-4">"Veri Giriş Yöneticisi" için, hangi grupların verilerini yönetebileceğini seçin.</p>
                        
                        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4 pl-4">
                            @php
                                $managedGroups = isset($user) ? $user->managedGroups->pluck('id')->toArray() : [];
                            @endphp
                            @foreach($groups as $group)
                                <label class="group flex items-center space-x-3 p-3 rounded-xl bg-white/60 border border-transparent hover:border-indigo-200 hover:bg-indigo-50/50 transition-all cursor-pointer shadow-sm">
                                    <input type="checkbox" name="managed_groups[]" value="{{ $group->id }}" class="w-5 h-5 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500 focus:ring-offset-0 bg-white" {{ in_array($group->id, old('managed_groups', $managedGroups)) ? 'checked' : '' }}>
                                    <span class="text-sm font-medium text-gray-700 group-hover:text-indigo-700">{{ $group->name }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>

                    <div class="flex items-center p-4 rounded-2xl bg-white/40 border border-white shadow-sm">
                        <input type="checkbox" name="is_active" id="is_active" value="1" {{ old('is_active', $user->is_active ?? true) ? 'checked' : '' }} class="h-6 w-6 text-green-500 focus:ring-green-500 border-gray-300 rounded shadow-sm">
                        <label for="is_active" class="ml-3 block text-sm font-bold text-gray-800 cursor-pointer">Kullanıcı Sisteme Giriş Yapabilir (Aktif)</label>
                    </div>

                    <div class="flex justify-end pt-8">
                        <a href="{{ route('users.index') }}" class="px-6 py-3 mr-4 text-gray-600 font-bold hover:text-gray-900 transition-colors">
                            İptal
                        </a>
                        <button type="submit" class="relative inline-flex items-center justify-center px-8 py-3 overflow-hidden font-bold text-white transition-all duration-300 bg-gradient-to-r from-indigo-600 to-purple-600 rounded-xl hover:from-indigo-500 hover:to-purple-500 hover:scale-[1.02] hover:shadow-[0_0_30px_rgba(79,70,229,0.4)]">
                            <span class="relative flex items-center gap-2">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                {{ isset($user) ? 'Değişiklikleri Kaydet' : 'Kullanıcı Oluştur' }}
                            </span>
                        </button>
                    </div>
                </form>

            </div>
        </div>
    </div>
</x-app-layout>

