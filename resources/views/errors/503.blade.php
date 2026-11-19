@extends('errors.layout')

@section('code', '503')
@section('title', 'Sistem Bakımda')
@section('badge_title', 'PLANLI BAKIM MODU (SERVICE UNAVAILABLE)')
@section('badge_style', 'bg-sky-50 text-sky-700 border border-sky-200')

@section('icon')
<div class="relative w-28 h-28 mx-auto flex items-center justify-center">
    <!-- Halka efekti -->
    <div class="absolute inset-0 bg-sky-100 rounded-full animate-ping opacity-25"></div>
    <div class="relative w-24 h-24 bg-gradient-to-tr from-sky-500 to-indigo-600 rounded-3xl flex items-center justify-center text-white shadow-xl shadow-sky-200 rotate-2 transition-transform hover:rotate-6">
        <svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
        </svg>
    </div>
    <!-- Minik bakım ikonu -->
    <div class="absolute -bottom-1 -right-1 bg-amber-400 text-slate-900 rounded-full p-1.5 shadow-md border-2 border-white">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
        </svg>
    </div>
</div>
@endsection

@section('message')
    Hedefler Portalı performans iyileştirmeleri ve planlı sunucu bakımı nedeniyle geçici olarak hizmet verememektedir.
@endsection

@section('sub_message')
    Bakım çalışması en kısa sürede tamamlanarak sistem tekrar erişime açılacaktır. Gösterdiğiniz sabır ve anlayış için teşekkür ederiz.
@endsection
