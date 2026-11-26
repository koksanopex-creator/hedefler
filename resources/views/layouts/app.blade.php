<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>@hasSection('title') @yield('title') - @endif{{ config('app.name', 'Köksan Hedefler Portalı') }}</title>

        <!-- Favicon -->
        <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}?v=2">
        <link rel="alternate icon" type="image/png" href="{{ asset('favicon.png') }}?v=2">
        <link rel="shortcut icon" href="{{ asset('favicon.ico') }}?v=2">

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased">
        <div class="min-h-screen bg-gray-100">
            @include('layouts.navigation')

            <!-- Page Heading -->
            @isset($header)
                <header class="bg-white shadow">
                    <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                        {{ $header }}
                    </div>
                </header>
            @endisset

            <!-- Page Content -->
            <main>
                {{ $slot }}
            </main>
        </div>

        <!-- BFCache & Geri/İleri Tuşu Kilit Sıfırlayıcı -->
        <script>
            window.addEventListener('pageshow', function (event) {
                document.querySelectorAll('form').forEach(function(f) {
                    delete f.dataset.submitting;
                    f.querySelectorAll('button, input[type="submit"]').forEach(function(b) {
                        b.classList.remove('pointer-events-none', 'opacity-70', 'cursor-not-allowed', 'cursor-wait');
                    });
                });
                document.querySelectorAll('[data-click-locked], [data-clicked]').forEach(function(el) {
                    delete el.dataset.clickLocked;
                    delete el.dataset.clicked;
                    el.classList.remove('pointer-events-none', 'opacity-70', 'cursor-not-allowed', 'cursor-wait', 'btn-loading');
                    if (el.dataset.origHtml) {
                        el.innerHTML = el.dataset.origHtml;
                        delete el.dataset.origHtml;
                    }
                });
                if (document.body) {
                    document.body.classList.remove('cursor-wait');
                }
                window.dispatchEvent(new CustomEvent('page-restored', { detail: { persisted: event.persisted } }));
            });
        </script>
    </body>
</html>
