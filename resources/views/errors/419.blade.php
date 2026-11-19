@extends('errors.layout')

@section('code', '419')
@section('title', 'Oturum Süresi Doldu')
@section('badge_title', 'SAYFA SÜRESİ DOLDU (PAGE EXPIRED)')
@section('badge_style', 'bg-amber-50 text-amber-700 border border-amber-200')

@section('icon')
<div class="relative w-28 h-28 mx-auto flex items-center justify-center">
    <!-- Halka efekti -->
    <div class="absolute inset-0 bg-amber-100 rounded-full animate-ping opacity-25"></div>
    <div class="relative w-24 h-24 bg-gradient-to-tr from-amber-500 to-orange-500 rounded-3xl flex items-center justify-center text-white shadow-xl shadow-amber-200 rotate-1 transition-transform hover:rotate-3">
        <svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
        </svg>
    </div>
    <!-- Minik kalkan/anahtar ikonu -->
    <div class="absolute -bottom-1 -right-1 bg-white text-amber-600 rounded-full p-1.5 shadow-md border-2 border-amber-200">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
        </svg>
    </div>
</div>
@endsection

@section('message')
    Güvenlik oturumunuz veya form doğrulama belirteciniz (CSRF Token) uzun süre işlem yapılmadığı için zaman aşımına uğradı.
@endsection

@section('sub_message')
    Bilgilerinizin ve veri girişlerinizin güvenliği için oturum yenilenmelidir. Sayfayı yenileyerek veya anasayfadan giriş yaparak kaldığınız yerden devam edebilirsiniz.
@endsection
