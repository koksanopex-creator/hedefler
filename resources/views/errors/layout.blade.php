<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('code', 'Hata') - @yield('title', 'Bir Sorun Oluştu') | Köksan Hedefler</title>

    <!-- Favicon -->
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}?v=2">
    <link rel="alternate icon" type="image/png" href="{{ asset('favicon.png') }}?v=2">
    <link rel="shortcut icon" href="{{ asset('favicon.ico') }}?v=2">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800,900&display=swap" rel="stylesheet" />

    <!-- Tailwind CSS (Vite & Standalone Fallback) -->
    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @endif
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Figtree', 'ui-sans-serif', 'system-ui', 'sans-serif'],
                    },
                    animation: {
                        'pulse-slow': 'pulse 3s cubic-bezier(0.4, 0, 0.6, 1) infinite',
                        'ping-slow': 'ping 2s cubic-bezier(0, 0, 0.2, 1) infinite',
                        'float': 'floating 3s ease-in-out infinite',
                    },
                    keyframes: {
                        floating: {
                            '0%, 100%': { transform: 'translateY(0px)' },
                            '50%': { transform: 'translateY(-8px)' },
                        }
                    }
                }
            }
        }
    </script>

    <style>
        body {
            font-family: 'Figtree', sans-serif;
            background-color: #f8fafc;
            color: #1e293b;
        }
        .mesh-gradient {
            background-color: #f8fafc;
            background-image: 
                radial-gradient(at 15% 15%, rgba(99, 102, 241, 0.08) 0px, transparent 50%),
                radial-gradient(at 85% 20%, rgba(244, 63, 94, 0.07) 0px, transparent 50%),
                radial-gradient(at 50% 80%, rgba(56, 189, 248, 0.08) 0px, transparent 50%);
        }
        .progress-bar-fill {
            transition: width 0.1s linear;
        }
    </style>
</head>
<body class="mesh-gradient min-h-screen flex flex-col justify-between selection:bg-indigo-500 selection:text-white antialiased overflow-x-hidden">
    
    <!-- Üst Bar: Logo ve Güvenli Başlık -->
    <header class="w-full px-4 sm:px-8 py-5 flex items-center justify-between border-b border-slate-200/50 bg-white/60 backdrop-blur-md">
        <a href="{{ url('/') }}" class="flex items-center gap-3 group transition-transform hover:scale-[1.01]" title="Köksan Hedefler Portalı Anasayfası">
            @if(file_exists(public_path('storage/logo.png')))
                <img src="{{ asset('storage/logo.png') }}" class="h-9 sm:h-11 w-auto object-contain" alt="Köksan Logo">
            @else
                <div class="flex items-center justify-center bg-indigo-600 text-white rounded-xl h-10 w-10 font-black text-xl shadow-md">K</div>
                <span class="font-extrabold text-xl tracking-wider text-slate-800">KÖKSAN</span>
            @endif
            <div class="hidden sm:flex flex-col border-s border-slate-200 ps-3">
                <span class="text-xs font-black tracking-widest text-indigo-700 uppercase">Hedefler Portalı</span>
                <span class="text-[10px] text-slate-400 font-medium">Kurumsal Yönetim Sistemi</span>
            </div>
        </a>

        <div class="flex items-center gap-2">
            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-rose-50 text-rose-700 border border-rose-200/70 shadow-xs">
                <span class="w-2 h-2 rounded-full bg-rose-500 animate-pulse"></span>
                <span>Hata Durumu</span>
            </span>
        </div>
    </header>

    <!-- Ana İçerik Kartı -->
    <main class="flex-1 flex items-center justify-center px-4 py-8 sm:py-12">
        <div class="w-full max-w-2xl bg-white/90 backdrop-blur-xl border border-slate-200/80 shadow-2xl shadow-indigo-100/40 rounded-3xl p-6 sm:p-10 text-center relative overflow-hidden">
            
            <!-- Arka Plan Hafif Efekti -->
            <div class="absolute -top-24 -right-24 w-48 h-48 bg-indigo-200/30 rounded-full blur-3xl pointer-events-none"></div>
            <div class="absolute -bottom-24 -left-24 w-48 h-48 bg-rose-200/30 rounded-full blur-3xl pointer-events-none"></div>

            <!-- Tematik İkon Bölümü -->
            <div class="mb-6 relative flex justify-center">
                <div class="relative animate-float">
                    @yield('icon')
                </div>
            </div>

            <!-- Hata Kodu & Başlık -->
            <div class="mb-5">
                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-lg text-xs font-extrabold tracking-widest uppercase mb-3 @yield('badge_style', 'bg-indigo-50 text-indigo-700 border border-indigo-200')">
                    @yield('badge_title', 'SİSTEM UYARISI')
                </div>
                <h1 class="text-6xl sm:text-7xl font-black text-slate-900 tracking-tight leading-none mb-3">
                    @yield('code', '500')
                </h1>
                <h2 class="text-xl sm:text-2xl font-bold text-slate-800">
                    @yield('title', 'Bir Hata Oluştu')
                </h2>
            </div>

            <!-- Detaylı Açıklama -->
            <div class="bg-slate-50/80 border border-slate-200/70 rounded-2xl p-4 sm:p-5 mb-6 text-slate-600 text-sm sm:text-base leading-relaxed text-center">
                <p class="font-medium text-slate-700 mb-1.5">
                    @yield('message')
                </p>
                <p class="text-xs sm:text-sm text-slate-500">
                    @yield('sub_message', 'Bu durumun geçici olduğunu düşünüyor veya işleminizi tekrarlamak istiyorsanız aşağıdaki seçenekleri kullanabilirsiniz.')
                </p>
            </div>

            <!-- 5 Saniyelik Otomatik Yönlendirme Paneli -->
            <div class="bg-indigo-50/70 border border-indigo-100 rounded-2xl p-4 mb-7 shadow-xs">
                <div class="flex flex-col sm:flex-row items-center justify-between gap-3 text-xs sm:text-sm font-semibold text-indigo-900 mb-2.5">
                    <div class="flex items-center gap-2">
                        <svg class="w-4 h-4 text-indigo-600 animate-spin" style="animation-duration: 3s;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <span id="countdown-banner-text">
                            <strong id="countdown-seconds" class="text-indigo-700 font-extrabold text-base">5</strong> saniye içinde anasayfaya yönlendiriliyorsunuz...
                        </span>
                    </div>

                    <!-- Geri Sayımı Duraklat / Başlat Butonu -->
                    <button type="button" id="btn-toggle-timer" onclick="toggleTimer()" 
                        class="text-xs font-bold text-indigo-700 hover:text-indigo-900 bg-white/90 hover:bg-white border border-indigo-200 px-3 py-1.5 rounded-xl transition-all shadow-2xs flex items-center gap-1.5">
                        <span id="btn-timer-icon">⏸️</span>
                        <span id="btn-timer-label">Durdur</span>
                    </button>
                </div>

                <!-- Geri Sayım İlerleme Çubuğu (Progress Bar) -->
                <div class="w-full bg-indigo-100/80 rounded-full h-2 overflow-hidden">
                    <div id="countdown-progress-bar" class="progress-bar-fill bg-indigo-600 h-2 rounded-full" style="width: 100%;"></div>
                </div>
            </div>

            <!-- Eylem Butonları -->
            <div class="flex flex-col sm:flex-row items-center justify-center gap-3 mb-8">
                <!-- Geri Dön Butonu -->
                <button type="button" onclick="historyBack()"
                    class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-6 py-3 border border-slate-200 shadow-sm text-sm font-bold rounded-2xl text-slate-700 bg-white hover:bg-slate-50 transition-all duration-200 active:scale-95">
                    <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                    </svg>
                    <span>Önceki Sayfaya Dön</span>
                </button>

                <!-- Hemen Anasayfaya Git Butonu -->
                <a href="{{ url('/') }}" id="btn-go-home"
                    class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-7 py-3 border border-transparent text-sm font-bold rounded-2xl text-white bg-indigo-600 hover:bg-indigo-700 shadow-lg shadow-indigo-200 transition-all duration-200 hover:-translate-y-0.5 active:scale-95">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
                    </svg>
                    <span>Hemen Anasayfaya Git</span>
                </a>
            </div>

            <!-- İletişim & Destek Bilgi Kartları -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-start pt-6 border-t border-slate-100">
                <!-- Departman / Bölüm Yöneticisi -->
                <div class="p-3.5 bg-slate-50/70 border border-slate-200/60 rounded-xl flex items-center gap-3">
                    <div class="w-9 h-9 rounded-lg bg-indigo-100 text-indigo-600 flex items-center justify-center shrink-0">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                        </svg>
                    </div>
                    <div class="min-w-0">
                        <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">İç Süreç & Onay</p>
                        <p class="text-xs font-semibold text-slate-700 truncate">Bölüm Yöneticisi</p>
                        <p class="text-[11px] text-slate-500 truncate">Yetki talebi için yöneticinize danışın</p>
                    </div>
                </div>

                <!-- Sistem & IT Destek -->
                <div class="p-3.5 bg-slate-50/70 border border-slate-200/60 rounded-xl flex items-center gap-3">
                    <div class="w-9 h-9 rounded-lg bg-emerald-100 text-emerald-600 flex items-center justify-center shrink-0">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192l-3.536 3.536M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-5 0a4 4 0 11-8 0 4 4 0 018 0z"></path>
                        </svg>
                    </div>
                    <div class="min-w-0">
                        <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Teknik Destek</p>
                        <p class="text-xs font-semibold text-slate-700 truncate">Sistem ve IT Yönetimi</p>
                        <p class="text-[11px] text-slate-500 truncate">Dahili Destek / Sistem Masası</p>
                    </div>
                </div>
            </div>

            <!-- Alt Güvenlik Bilgisi -->
            <div class="mt-6 pt-4 text-[10px] font-mono text-slate-400 flex flex-wrap items-center justify-between gap-2 border-t border-slate-100/60">
                <span>REF: @yield('code', 'ERR')-{{ strtoupper(substr(md5(request()->fullUrl() . time()), 0, 8)) }}</span>
                <span>{{ date('d.m.Y H:i:s') }}</span>
            </div>

        </div>
    </main>

    <!-- Sayfa Altı Footer -->
    <footer class="w-full py-4 text-center text-xs text-slate-400 border-t border-slate-200/50 bg-white/40">
        <p>&copy; {{ date('Y') }} Köksan Pet ve Plastik Ambalaj San. Tic. A.Ş. Tüm hakları saklıdır.</p>
    </footer>

    <!-- Otomatik Yönlendirme ve Sayaç Mantığı -->
    <script>
        const REDIRECT_URL = @json(url('/'));
        const TOTAL_DURATION_MS = 5000;
        const TICK_INTERVAL_MS = 100;
        
        let remainingMs = TOTAL_DURATION_MS;
        let isPaused = false;
        let timerInterval = null;

        const secondsEl = document.getElementById('countdown-seconds');
        const progressBarEl = document.getElementById('countdown-progress-bar');
        const bannerTextEl = document.getElementById('countdown-banner-text');
        const btnTimerIcon = document.getElementById('btn-timer-icon');
        const btnTimerLabel = document.getElementById('btn-timer-label');

        function startCountdown() {
            clearInterval(timerInterval);
            timerInterval = setInterval(() => {
                if (isPaused) return;

                remainingMs -= TICK_INTERVAL_MS;

                if (remainingMs <= 0) {
                    remainingMs = 0;
                    clearInterval(timerInterval);
                    updateUI();
                    // Süre dolduğunda yönlendir
                    window.location.href = REDIRECT_URL;
                    return;
                }

                updateUI();
            }, TICK_INTERVAL_MS);
        }

        function updateUI() {
            const currentSeconds = Math.ceil(remainingMs / 1000);
            if (secondsEl) {
                secondsEl.textContent = currentSeconds;
            }

            const percentage = (remainingMs / TOTAL_DURATION_MS) * 100;
            if (progressBarEl) {
                progressBarEl.style.width = percentage + '%';
            }
        }

        function toggleTimer() {
            isPaused = !isPaused;
            if (isPaused) {
                btnTimerIcon.textContent = '▶️';
                btnTimerLabel.textContent = 'Devam Et';
                if (bannerTextEl) {
                    bannerTextEl.innerHTML = '<span class="text-amber-700 font-bold">Geri sayım duraklatıldı.</span> İncelemenizi tamamladıktan sonra devam ettirebilirsiniz.';
                }
                if (progressBarEl) {
                    progressBarEl.classList.remove('bg-indigo-600');
                    progressBarEl.classList.add('bg-amber-500');
                }
            } else {
                btnTimerIcon.textContent = '⏸️';
                btnTimerLabel.textContent = 'Durdur';
                if (bannerTextEl) {
                    bannerTextEl.innerHTML = '<strong id="countdown-seconds" class="text-indigo-700 font-extrabold text-base">' + Math.ceil(remainingMs / 1000) + '</strong> saniye içinde anasayfaya yönlendiriliyorsunuz...';
                }
                if (progressBarEl) {
                    progressBarEl.classList.remove('bg-amber-500');
                    progressBarEl.classList.add('bg-indigo-600');
                }
            }
        }

        function historyBack() {
            if (window.history.length > 1) {
                window.history.back();
            } else {
                window.location.href = REDIRECT_URL;
            }
        }

        // Sayfa açıldığında sayacı başlat
        document.addEventListener('DOMContentLoaded', () => {
            updateUI();
            startCountdown();
        });
    </script>
</body>
</html>
