@section('title', 'Hesap Ayarları')
<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            <div>
                <h2 class="font-bold text-2xl text-transparent bg-clip-text bg-gradient-to-r from-indigo-600 to-purple-600 leading-tight">
                    {{ __('Hesap ve Profil Ayarları') }}
                </h2>
                <p class="text-xs text-gray-500 mt-1">Profil resminizi güncelleyin ve merkezi oturum bilgilerinizi inceleyin.</p>
            </div>

            <div class="flex items-center gap-2">
                <a href="{{ route('users.profile', auth()->id()) }}" class="px-4 py-2 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 border border-indigo-200 rounded-xl text-xs font-bold transition-all shadow-2xs flex items-center gap-1.5">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                    Kullanıcı Profilimi Görüntüle
                </a>
            </div>
        </div>
    </x-slot>

    <!-- Glassmorphism Background Wrapper -->
    <div class="relative py-8 min-h-[calc(100vh-65px)] bg-slate-50/50 overflow-hidden">
        <!-- Background Decorative Blobs -->
        <div class="absolute top-20 left-20 w-96 h-96 bg-purple-300 rounded-full mix-blend-multiply filter blur-[80px] opacity-30 animate-pulse"></div>
        <div class="absolute top-40 right-20 w-96 h-96 bg-indigo-300 rounded-full mix-blend-multiply filter blur-[80px] opacity-30 animate-pulse" style="animation-delay: 2s;"></div>

        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10 space-y-6">
            
            @if(session('error'))
                <div class="backdrop-blur-md bg-rose-50/80 border border-rose-200/50 text-rose-700 px-6 py-4 rounded-2xl shadow-sm flex items-center gap-3">
                    <svg class="w-5 h-5 text-rose-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                    <span class="font-semibold text-sm">{{ session('error') }}</span>
                </div>
            @endif

            <!-- 1. PROFİL BİLGİLERİ VE RESİM -->
            <div class="backdrop-blur-xl bg-white/70 border border-white/60 shadow-[0_8px_30px_rgb(0,0,0,0.04)] sm:rounded-3xl p-6 sm:p-8">
                @include('profile.partials.update-profile-information-form')
            </div>

            <!-- 2. ŞİFRE VE MERKEZİ GÜVENLİK YÖNLENDİRMESİ -->
            <div class="backdrop-blur-xl bg-white/70 border border-white/60 shadow-[0_8px_30px_rgb(0,0,0,0.04)] sm:rounded-3xl p-6 sm:p-8">
                @include('profile.partials.update-password-form')
            </div>

            <!-- 3. HESAP DURUMU BİLGİLENDİRMESİ -->
            <div class="backdrop-blur-xl bg-white/70 border border-white/60 shadow-[0_8px_30px_rgb(0,0,0,0.04)] sm:rounded-3xl p-6 sm:p-8">
                @include('profile.partials.delete-user-form')
            </div>

        </div>
    </div>
</x-app-layout>
