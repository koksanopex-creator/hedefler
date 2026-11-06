@section('title', 'Şifre Sıfırlama Talebi - HEDEFLER')
<x-guest-layout>
    <div class="min-h-screen flex flex-col justify-center items-center p-6 sm:p-10 relative bg-slate-950 overflow-hidden font-sans">

        <!-- DEKORATİF ARKA PLAN IŞIKLARI (AMBIENT GLOW) -->
        <div class="absolute -top-32 -left-32 w-[550px] h-[550px] bg-indigo-600/15 rounded-full blur-[140px] pointer-events-none"></div>
        <div class="absolute -bottom-32 -right-32 w-[550px] h-[550px] bg-purple-600/15 rounded-full blur-[160px] pointer-events-none"></div>

        <!-- Üst Kurumsal Logo & Başlık -->
        <div class="mb-8 text-center relative z-10">
            @if(file_exists(public_path('storage/logo.png')))
                <div class="bg-white/95 backdrop-blur-md px-5 py-3 rounded-2xl shadow-xl inline-block border border-white/20">
                    <img src="{{ asset('storage/logo.png') }}?v={{ time() }}" class="h-10 w-auto object-contain mx-auto" alt="KÖKSAN Logo" />
                </div>
            @else
                <div class="inline-flex items-center gap-3">
                    <div class="w-12 h-12 rounded-2xl bg-gradient-to-tr from-indigo-600 to-purple-600 flex items-center justify-center text-white font-black text-2xl shadow-lg">
                        H
                    </div>
                    <span class="text-2xl font-black text-white">HEDEFLER</span>
                </div>
            @endif
            <p class="text-xs font-semibold text-indigo-300 uppercase tracking-widest mt-2.5">Hedef Yönetim Portalı</p>
        </div>

        <!-- Cam (Glassmorphism) Şifre Sıfırlama Kartı -->
        <div class="w-full max-w-md bg-white/[0.04] backdrop-blur-2xl border border-white/10 rounded-3xl p-8 sm:p-10 shadow-[0_20px_60px_rgba(0,0,0,0.6)] relative z-10 overflow-hidden">
            
            <!-- Kart Üst Aksan Çizgisi -->
            <div class="absolute top-0 left-0 right-0 h-1 bg-gradient-to-r from-indigo-500 to-purple-500"></div>

            <!-- Kart Başlığı -->
            <div class="mb-6">
                <h2 class="text-2xl font-extrabold text-white tracking-tight">Şifre Sıfırlama</h2>
                <p class="text-xs text-slate-400 mt-2 leading-relaxed">
                    Kayıtlı kurumsal e-posta adresinizi giriniz. Şifrenizi yenileyebilmeniz için size bir sıfırlama bağlantısı iletilecektir.
                </p>
            </div>

            <!-- Oturum Durumu Bildirimi (Başarı) -->
            @if (session('status'))
                <div class="mb-6 p-4 rounded-xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-300 text-xs font-medium flex items-start gap-2.5">
                    <svg class="w-4 h-4 shrink-0 text-emerald-400 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                    <span>{{ session('status') }}</span>
                </div>
            @endif

            <!-- Hata Bildirimi -->
            @if ($errors->any())
                <div class="mb-6 p-4 rounded-xl bg-rose-500/10 border border-rose-500/20 text-rose-300 text-xs font-medium">
                    <div class="flex items-center gap-2 font-bold text-rose-200 mb-1">
                        <svg class="w-4 h-4 shrink-0 text-rose-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                        İşlem Başarısız
                    </div>
                    <ul class="list-disc list-inside space-y-0.5 text-[11px] text-rose-300/90">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <!-- Form -->
            <form method="POST" action="{{ route('password.email') }}" class="space-y-5">
                @csrf

                <!-- E-Posta Alanı -->
                <div>
                    <label for="email" class="block text-xs font-bold uppercase tracking-wider text-slate-300 mb-2">
                        Kurumsal E-Posta Adresi
                    </label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.207"></path></svg>
                        </div>
                        <input id="email" 
                               type="email" 
                               name="email" 
                               value="{{ old('email') }}" 
                               required 
                               autofocus 
                               placeholder="ad.soyad@koksan.com"
                               class="w-full pl-10 pr-4 py-3 bg-white/5 border border-white/10 rounded-xl text-sm text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/50 focus:border-indigo-400 focus:bg-white/10 transition duration-200">
                    </div>
                </div>

                <!-- Gönder Butonu -->
                <div class="pt-2">
                    <button type="submit" 
                            class="w-full group relative inline-flex items-center justify-center px-6 py-3.5 overflow-hidden font-bold text-white transition-all duration-300 bg-gradient-to-r from-indigo-600 to-indigo-700 rounded-xl hover:from-indigo-500 hover:to-indigo-600 hover:shadow-[0_0_25px_rgba(99,102,241,0.4)] hover:scale-[1.01] active:scale-[0.99] shadow-lg shadow-indigo-600/30 text-sm">
                        <span class="relative flex items-center gap-2">
                            <span>Sıfırlama Bağlantısı Gönder</span>
                            <svg class="w-4 h-4 transition-transform duration-300 group-hover:translate-x-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
                        </span>
                    </button>
                </div>

                <!-- Giriş Ekranına Dön Bağlantısı -->
                <div class="pt-2 text-center">
                    <a href="{{ route('login') }}" class="inline-flex items-center gap-1.5 text-xs font-semibold text-indigo-400 hover:text-indigo-300 transition">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                        <span>Giriş Ekranına Dön</span>
                    </a>
                </div>

            </form>

            <!-- Alt Güvenlik ve Telif Notu -->
            <div class="mt-8 pt-6 border-t border-white/5 text-center">
                <p class="text-[11px] text-slate-500">
                    © {{ date('Y') }} Köksan Holding &bull; Stratejik Planlama
                </p>
            </div>

        </div>

    </div>
</x-guest-layout>
