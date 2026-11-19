@extends('errors.layout')

@section('code', '500')
@section('title', 'Sunucu Hatası')
@section('badge_title', 'SUNUCU HATASI (INTERNAL SERVER ERROR)')
@section('badge_style', 'bg-rose-50 text-rose-700 border border-rose-200')

@section('icon')
<div class="relative w-28 h-28 mx-auto flex items-center justify-center">
    <!-- Halka efekti -->
    <div class="absolute inset-0 bg-rose-100 rounded-full animate-ping opacity-25"></div>
    <div class="relative w-24 h-24 bg-gradient-to-tr from-rose-600 to-pink-600 rounded-3xl flex items-center justify-center text-white shadow-xl shadow-rose-200 -rotate-2 transition-transform hover:-rotate-6">
        <svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
        </svg>
    </div>
    <!-- Minik hata ikonu -->
    <div class="absolute -bottom-1 -right-1 bg-rose-500 text-white rounded-full p-1.5 shadow-md border-2 border-white">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
        </svg>
    </div>
</div>
@endsection

@section('message')
    İşleminiz gerçekleştirilirken sunucu tarafında beklenmeyen bir hata meydana geldi.
@endsection

@section('sub_message')
    Sistem yöneticilerimiz durumdan otomatik olarak bilgilendirilmiştir. Lütfen sayfayı yenilemeyi deneyiniz veya kısa bir süre sonra işleminizi tekrarlayınız.
@endsection
