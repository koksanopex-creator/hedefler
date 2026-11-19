@extends('errors.layout')

@section('code', '403')
@section('title', 'Erişim Yetkisi Yok')
@section('badge_title', 'ERİŞİM YETKİSİ BULUNMUYOR')
@section('badge_style', 'bg-rose-50 text-rose-700 border border-rose-200')

@section('icon')
<div class="relative w-28 h-28 mx-auto flex items-center justify-center">
    <!-- Halka efekti -->
    <div class="absolute inset-0 bg-rose-100 rounded-full animate-ping opacity-25"></div>
    <div class="relative w-24 h-24 bg-gradient-to-tr from-rose-600 to-red-500 rounded-3xl flex items-center justify-center text-white shadow-xl shadow-rose-200 -rotate-3 transition-transform hover:-rotate-6">
        <svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
        </svg>
    </div>
    <!-- Minik kilit rozeti -->
    <div class="absolute -bottom-1 -right-1 bg-slate-900 text-white rounded-full p-1.5 shadow-md border-2 border-white">
        <svg class="w-4 h-4 text-rose-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"></path>
        </svg>
    </div>
</div>
@endsection

@section('message')
    {{ isset($exception) && $exception->getMessage() ? $exception->getMessage() : 'Bu sayfaya, form kaydına veya modüle erişim yetkiniz bulunmamaktadır.' }}
@endsection

@section('sub_message')
    Bu modülü kullanmanız gerekiyorsa lütfen bağlı olduğunuz Bölüm Yöneticiniz ile görüşerek Yetki Matrisinden rol tanımlaması talep ediniz.
@endsection
