@section('title', 'Kullanıcı Girişi - HEDEFLER')
<x-guest-layout>
    <div class="min-h-screen flex flex-col lg:flex-row relative bg-slate-950 overflow-hidden font-sans">

        <!-- DEKORATİF ARKA PLAN IŞIKLARI (AMBIENT GLOW BLOBS) -->
        <div class="absolute -top-32 -left-32 w-[600px] h-[600px] bg-indigo-600/15 rounded-full blur-[140px] pointer-events-none"></div>
        <div class="absolute -bottom-32 -right-32 w-[600px] h-[600px] bg-purple-600/15 rounded-full blur-[160px] pointer-events-none"></div>

        <!-- ======================================================== -->
        <!-- 1. SOL BÖLÜM: KURUMSAL KİMLİK VE BİLGİLENDİRME (DESKTOP) -->
        <!-- ======================================================== -->
        <div class="hidden lg:flex lg:w-7/12 flex-col justify-between p-12 xl:p-20 relative z-10 border-r border-white/5 bg-gradient-to-br from-slate-950/90 via-slate-900/60 to-indigo-950/30 backdrop-blur-xl">
            
            <!-- Üst Logo & Kurum Adı -->
            <div class="flex items-center gap-4">
                @if(file_exists(public_path('storage/logo.png')))
                    <div class="bg-white/95 backdrop-blur-md px-4 py-2.5 rounded-2xl shadow-md border border-white/30 inline-flex items-center">
                        <img src="{{ asset('storage/logo.png') }}?v={{ time() }}" class="h-10 w-auto object-contain" alt="KÖKSAN Logo" />
                    </div>
                @else
                    <div class="flex items-center gap-3">
                        <div class="w-12 h-12 rounded-2xl bg-gradient-to-tr from-indigo-600 to-purple-600 flex items-center justify-center text-white font-black text-2xl shadow-lg">
                            H
                        </div>
                        <span class="text-2xl font-black tracking-tight text-white">HEDEFLER</span>
                    </div>
                @endif
                <div class="h-6 w-px bg-white/20"></div>
                <div>
                    <span class="text-xs uppercase font-extrabold tracking-widest text-indigo-300 block">KÖKSAN HOLDİNG</span>
                    <span class="text-[11px] text-slate-400 font-medium">Hedef Yönetim Sistemi</span>
                </div>
            </div>

            <!-- Orta Bölüm: Sade Kurumsal Başlık ve Açıklama -->
            <div class="my-auto py-10 space-y-8 max-w-xl">
                <div class="space-y-4">
                    <h1 class="text-4xl xl:text-5xl font-black text-white tracking-tight leading-[1.15]">
                        Hedef ve Performans <br />
                        <span class="text-transparent bg-clip-text bg-gradient-to-r from-indigo-300 via-purple-300 to-slate-200">
                            Yönetim Portalı
                        </span>
                    </h1>
                    <p class="text-sm xl:text-base text-slate-400 leading-relaxed font-normal">
                        Köksan bünyesindeki departman, fabrika ve satış gruplarının hedef planlama, veri girişi ve onay süreçlerinin yürütüldüğü şirket içi yönetim platformudur.
                    </p>
                </div>

                <!-- Kurumsal Erişim ve Güvenlik Bilgilendirme Kutusu -->
                <div class="p-6 rounded-2xl bg-white/[0.03] border border-white/10 backdrop-blur-md space-y-3">
                    <div class="flex items-center gap-2.5 text-indigo-300 font-bold text-xs uppercase tracking-wider">
                        <svg class="w-4 h-4 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>
                        <span>Kurumsal Erişim & Bilgi Güvenliği</span>
                    </div>
                    <p class="text-xs text-slate-400 leading-relaxed font-normal">
                        Bu sistem yalnızca yetkili şirket personelinin kullanımına tahsis edilmiştir. Lütfen şirketiniz tarafından tanımlanan kurumsal kullanıcı bilgilerinizle oturum açınız.
                    </p>
                    <div class="pt-2 border-t border-white/5 flex items-center gap-2 text-[11px] text-slate-500">
                        <svg class="w-3.5 h-3.5 text-slate-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        <span>Giriş veya yetki talepleri için Stratejik Planlama ekibi ile iletişime geçebilirsiniz.</span>
                    </div>
                </div>
            </div>

            <!-- Alt Telif & Departman Notu -->
            <div class="pt-6 border-t border-white/5 flex items-center justify-between text-xs text-slate-500 font-medium">
                <div class="flex items-center gap-2">
                    <svg class="w-4 h-4 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path></svg>
                    <span>Güvenli Kurumsal Ağ</span>
                </div>
                <span>Köksan Holding &bull; Stratejik Planlama</span>
            </div>

        </div>

        <!-- ======================================================== -->
        <!-- 2. SAĞ BÖLÜM: SADE VE ŞIK GİRİŞ FORMU                     -->
        <!-- ======================================================== -->
        <div class="w-full lg:w-5/12 flex-1 flex flex-col justify-center items-center p-6 sm:p-10 lg:p-16 relative z-10">

            <!-- Mobilde Görünen Logo -->
            <div class="lg:hidden mb-8 text-center">
                @if(file_exists(public_path('storage/logo.png')))
                    <div class="bg-white/95 backdrop-blur-md px-5 py-3 rounded-2xl shadow-xl inline-block border border-white/20">
                        <img src="{{ asset('storage/logo.png') }}?v={{ time() }}" class="h-10 w-auto object-contain mx-auto" alt="Logo" />
                    </div>
                @else
                    <div class="inline-flex items-center gap-3">
                        <div class="w-12 h-12 rounded-2xl bg-gradient-to-tr from-indigo-600 to-purple-600 flex items-center justify-center text-white font-black text-2xl shadow-lg">
                            H
                        </div>
                        <span class="text-2xl font-black text-white">HEDEFLER</span>
                    </div>
                @endif
                <p class="text-xs font-semibold text-indigo-300 uppercase tracking-widest mt-2">Hedef Yönetim Portalı</p>
            </div>

            <!-- Cam (Glassmorphism) Giriş Kartı -->
            <div class="w-full max-w-md bg-white/[0.04] backdrop-blur-2xl border border-white/10 rounded-3xl p-8 sm:p-10 shadow-[0_20px_60px_rgba(0,0,0,0.6)] relative overflow-hidden">
                
                <!-- Kart Üst Aksan Çizgisi -->
                <div class="absolute top-0 left-0 right-0 h-1 bg-gradient-to-r from-indigo-500 to-purple-500"></div>

                <!-- Kart Başlığı -->
                <div class="mb-8">
                    <h2 class="text-2xl font-extrabold text-white tracking-tight">Kullanıcı Girişi</h2>
                    <p class="text-xs text-slate-400 mt-1.5 leading-relaxed">
                        Sisteme erişmek için kurumsal e-posta ve şifrenizi giriniz.
                    </p>
                </div>

                <!-- Oturum Durumu Bildirimi -->
                @if (session('status'))
                    <div class="mb-6 p-4 rounded-xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-300 text-xs font-medium flex items-center gap-2">
                        <svg class="w-4 h-4 shrink-0 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                        <span>{{ session('status') }}</span>
                    </div>
                @endif

                <!-- Hata Bildirimi -->
                @if ($errors->any())
                    <div class="mb-6 p-4 rounded-xl bg-rose-500/10 border border-rose-500/20 text-rose-300 text-xs font-medium">
                        <div class="flex items-center gap-2 font-bold text-rose-200 mb-1">
                            <svg class="w-4 h-4 shrink-0 text-rose-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                            Giriş Yapılamadı
                        </div>
                        <ul class="list-disc list-inside space-y-0.5 text-[11px] text-rose-300/90">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <!-- Giriş Formu -->
                <form method="POST" action="{{ route('login') }}" class="space-y-5">
                    @csrf

                    <!-- E-Posta Alanı -->
                    <div>
                        <label for="email" class="block text-xs font-bold uppercase tracking-wider text-slate-300 mb-2">
                            E-Posta Adresi
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
                                   autocomplete="username" 
                                   placeholder="ad.soyad@koksan.com"
                                   class="w-full pl-10 pr-4 py-3 bg-white/5 border border-white/10 rounded-xl text-sm text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/50 focus:border-indigo-400 focus:bg-white/10 transition duration-200">
                        </div>
                    </div>

                    <!-- Şifre Alanı (Alpine.js ile Göster/Gizle) -->
                    <div x-data="{ show: false }">
                        <div class="flex items-center justify-between mb-2">
                            <label for="password" class="block text-xs font-bold uppercase tracking-wider text-slate-300">
                                Şifre
                            </label>
                            @if (Route::has('password.request'))
                                <a href="{{ route('password.request') }}" class="text-xs text-indigo-400 hover:text-indigo-300 transition hover:underline">
                                    Şifremi Unuttum?
                                </a>
                            @endif
                        </div>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>
                            </div>
                            <input id="password" 
                                   :type="show ? 'text' : 'password'" 
                                   name="password" 
                                   required 
                                   autocomplete="current-password" 
                                   placeholder="••••••••"
                                   class="w-full pl-10 pr-11 py-3 bg-white/5 border border-white/10 rounded-xl text-sm text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/50 focus:border-indigo-400 focus:bg-white/10 transition duration-200">
                            
                            <!-- Şifre Göster/Gizle Butonu -->
                            <button type="button" 
                                    @click="show = !show" 
                                    class="absolute inset-y-0 right-0 pr-3.5 flex items-center text-slate-400 hover:text-white transition"
                                    title="Şifreyi Göster / Gizle">
                                <template x-if="!show">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                                </template>
                                <template x-if="show">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18"></path></svg>
                                </template>
                            </button>
                        </div>
                    </div>

                    <!-- Beni Hatırla -->
                    <div class="flex items-center justify-between pt-1">
                        <label for="remember_me" class="inline-flex items-center gap-2 cursor-pointer group">
                            <input id="remember_me" 
                                   type="checkbox" 
                                   name="remember" 
                                   class="w-4 h-4 rounded bg-white/5 border border-white/20 text-indigo-600 focus:ring-indigo-500/50 focus:ring-offset-0 focus:ring-1 cursor-pointer">
                            <span class="text-xs text-slate-400 group-hover:text-slate-300 transition">Oturumu açık tut (Beni Hatırla)</span>
                        </label>
                    </div>

                    <!-- Giriş Yap Butonu -->
                    <div class="pt-2">
                        <button type="submit" 
                                class="w-full group relative inline-flex items-center justify-center px-6 py-3.5 overflow-hidden font-bold text-white transition-all duration-300 bg-gradient-to-r from-indigo-600 to-indigo-700 rounded-xl hover:from-indigo-500 hover:to-indigo-600 hover:shadow-[0_0_25px_rgba(99,102,241,0.4)] hover:scale-[1.01] active:scale-[0.99] shadow-lg shadow-indigo-600/30 text-sm">
                            <span class="relative flex items-center gap-2">
                                <span>Giriş Yap</span>
                                <svg class="w-4 h-4 transition-transform duration-300 group-hover:translate-x-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
                            </span>
                        </button>
                    </div>

                </form>

                <!-- Alt Güvenlik ve Telif Notu -->
                <div class="mt-8 pt-6 border-t border-white/5 text-center">
                    <p class="text-[11px] text-slate-500">
                        © {{ date('Y') }} Köksan Holding &bull; Tüm hakları saklıdır.
                    </p>
                </div>

            </div>

        </div>

    </div>
</x-guest-layout>
