@section('title', 'Yeni Şifre Belirleme - HEDEFLER')
<x-guest-layout>
    <div
        class="min-h-screen flex flex-col justify-center items-center p-6 sm:p-10 relative bg-slate-950 overflow-hidden font-sans">

        <!-- DEKORATİF ARKA PLAN IŞIKLARI (AMBIENT GLOW) -->
        <div
            class="absolute -top-32 -left-32 w-[550px] h-[550px] bg-indigo-600/15 rounded-full blur-[140px] pointer-events-none">
        </div>
        <div
            class="absolute -bottom-32 -right-32 w-[550px] h-[550px] bg-purple-600/15 rounded-full blur-[160px] pointer-events-none">
        </div>

        <!-- Üst Kurumsal Logo & Başlık -->
        <div class="mb-8 text-center relative z-10">
            @if(file_exists(public_path('storage/logo.png')))
                <div
                    class="bg-white/95 backdrop-blur-md px-5 py-3 rounded-2xl shadow-xl inline-block border border-white/20">
                    <img src="{{ asset('storage/logo.png') }}?v={{ time() }}" class="h-10 w-auto object-contain mx-auto"
                        alt="KÖKSAN Logo" />
                </div>
            @else
                <div class="inline-flex items-center gap-3">
                    <div
                        class="w-12 h-12 rounded-2xl bg-gradient-to-tr from-indigo-600 to-purple-600 flex items-center justify-center text-white font-black text-2xl shadow-lg">
                        H
                    </div>
                    <span class="text-2xl font-black text-white">HEDEFLER</span>
                </div>
            @endif
            <p class="text-xs font-semibold text-indigo-300 uppercase tracking-widest mt-2.5">Hedef Yönetim Portalı</p>
        </div>

        <!-- Cam (Glassmorphism) Yeni Şifre Kartı -->
        <div
            class="w-full max-w-md bg-white/[0.04] backdrop-blur-2xl border border-white/10 rounded-3xl p-8 sm:p-10 shadow-[0_20px_60px_rgba(0,0,0,0.6)] relative z-10 overflow-hidden">

            <!-- Kart Üst Aksan Çizgisi -->
            <div class="absolute top-0 left-0 right-0 h-1 bg-gradient-to-r from-indigo-500 to-purple-500"></div>

            <!-- Kart Başlığı -->
            <div class="mb-6">
                <h2 class="text-2xl font-extrabold text-white tracking-tight">Yeni Şifre Belirleme</h2>
                <p class="text-xs text-slate-400 mt-2 leading-relaxed">
                    Lütfen hesabınız için yeni ve güvenli bir şifre belirleyiniz.
                </p>
            </div>

            <!-- Hata Bildirimi -->
            @if ($errors->any())
                <div class="mb-6 p-4 rounded-xl bg-rose-500/10 border border-rose-500/20 text-rose-300 text-xs font-medium">
                    <div class="flex items-center gap-2 font-bold text-rose-200 mb-1">
                        <svg class="w-4 h-4 shrink-0 text-rose-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z">
                            </path>
                        </svg>
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
            <form method="POST" action="{{ route('password.store') }}" class="space-y-5">
                @csrf

                <!-- Şifre Sıfırlama Token -->
                <input type="hidden" name="token" value="{{ $request->route('token') }}">

                <!-- E-Posta Alanı -->
                <div>
                    <label for="email" class="block text-xs font-bold uppercase tracking-wider text-slate-300 mb-2">
                        Kurumsal E-Posta Adresi
                    </label>
                    <div class="relative">
                        <div
                            class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.207">
                                </path>
                            </svg>
                        </div>
                        <input id="email" type="email" name="email" value="{{ old('email', $request->email) }}" required
                            autofocus
                            class="w-full pl-10 pr-4 py-3 bg-white/5 border border-white/10 rounded-xl text-sm text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/50 focus:border-indigo-400 focus:bg-white/10 transition duration-200">
                    </div>
                </div>

                <!-- Yeni Şifre -->
                <div x-data="{ show: false }">
                    <label for="password" class="block text-xs font-bold uppercase tracking-wider text-slate-300 mb-2">
                        Yeni Şifre
                    </label>
                    <div class="relative">
                        <div
                            class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z">
                                </path>
                            </svg>
                        </div>
                        <input id="password" :type="show ? 'text' : 'password'" name="password" required
                            autocomplete="new-password" placeholder="••••••••"
                            class="w-full pl-10 pr-11 py-3 bg-white/5 border border-white/10 rounded-xl text-sm text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/50 focus:border-indigo-400 focus:bg-white/10 transition duration-200">

                        <button type="button" @click="show = !show"
                            class="absolute inset-y-0 right-0 pr-3.5 flex items-center text-slate-400 hover:text-white transition"
                            title="Şifreyi Göster / Gizle">
                            <template x-if="!show">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z">
                                    </path>
                                </svg>
                            </template>
                            <template x-if="show">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18">
                                    </path>
                                </svg>
                            </template>
                        </button>
                    </div>
                </div>

                <!-- Şifre Tekrarı -->
                <div x-data="{ show: false }">
                    <label for="password_confirmation"
                        class="block text-xs font-bold uppercase tracking-wider text-slate-300 mb-2">
                        Yeni Şifre (Tekrar)
                    </label>
                    <div class="relative">
                        <div
                            class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z">
                                </path>
                            </svg>
                        </div>
                        <input id="password_confirmation" :type="show ? 'text' : 'password'"
                            name="password_confirmation" required autocomplete="new-password" placeholder="••••••••"
                            class="w-full pl-10 pr-11 py-3 bg-white/5 border border-white/10 rounded-xl text-sm text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/50 focus:border-indigo-400 focus:bg-white/10 transition duration-200">

                        <button type="button" @click="show = !show"
                            class="absolute inset-y-0 right-0 pr-3.5 flex items-center text-slate-400 hover:text-white transition"
                            title="Şifreyi Göster / Gizle">
                            <template x-if="!show">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z">
                                    </path>
                                </svg>
                            </template>
                            <template x-if="show">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18">
                                    </path>
                                </svg>
                            </template>
                        </button>
                    </div>
                </div>

                <!-- Şifreyi Güncelle Butonu -->
                <div class="pt-2">
                    <button type="submit"
                        class="w-full group relative inline-flex items-center justify-center px-6 py-3.5 overflow-hidden font-bold text-white transition-all duration-300 bg-gradient-to-r from-indigo-600 to-indigo-700 rounded-xl hover:from-indigo-500 hover:to-indigo-600 hover:shadow-[0_0_25px_rgba(99,102,241,0.4)] hover:scale-[1.01] active:scale-[0.99] shadow-lg shadow-indigo-600/30 text-sm">
                        <span class="relative flex items-center gap-2">
                            <span>Şifreyi Güncelle ve Giriş Yap</span>
                            <svg class="w-4 h-4 transition-transform duration-300 group-hover:translate-x-1" fill="none"
                                stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M14 5l7 7m0 0l-7 7m7-7H3"></path>
                            </svg>
                        </span>
                    </button>
                </div>

                <!-- Giriş Ekranına Dön Bağlantısı -->
                <div class="pt-2 text-center">
                    <a href="{{ route('login') }}"
                        class="inline-flex items-center gap-1.5 text-xs font-semibold text-indigo-400 hover:text-indigo-300 transition">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                        </svg>
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