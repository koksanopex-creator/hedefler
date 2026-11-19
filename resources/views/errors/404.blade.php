@extends('errors.layout')

@section('code', '404')
@section('title', 'Sayfa Bulunamadı')
@section('badge_title', 'SAYFA BULUNAMADI')
@section('badge_style', 'bg-indigo-50 text-indigo-700 border border-indigo-200')

@section('icon')
<div class="relative w-28 h-28 mx-auto flex items-center justify-center">
    <!-- Halka efekti -->
    <div class="absolute inset-0 bg-indigo-100 rounded-full animate-ping opacity-25"></div>
    <div class="relative w-24 h-24 bg-gradient-to-tr from-indigo-600 to-violet-500 rounded-3xl flex items-center justify-center text-white shadow-xl shadow-indigo-200 rotate-3 transition-transform hover:rotate-6">
        <svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM10 7v3m0 4h.01"></path>
        </svg>
    </div>
    <!-- Minik uydu rozeti -->
    <div class="absolute -bottom-1 -right-1 bg-amber-400 text-slate-900 rounded-full p-1.5 shadow-md border-2 border-white">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
        </svg>
    </div>
</div>
@endsection

@section('message')
    Ulaşmaya çalıştığınız sayfa kaldırılmış, bağlantı adı değiştirilmiş veya geçici olarak taşınmış olabilir.
@endsection

@section('sub_message')
    Girdiğiniz URL adresini kontrol edebilir veya sistem anasayfasına dönerek ilgili form listesinden aradığınız kayda ulaşabilirsiniz.
@endsection
