@extends('errors.layout')

@section('code', '405')
@section('title', 'İzin Verilmeyen İstek Yöntemi')
@section('badge_title', 'METOD DESTEKLENMİYOR (METHOD NOT ALLOWED)')
@section('badge_style', 'bg-purple-50 text-purple-700 border border-purple-200')

@section('icon')
<div class="relative w-28 h-28 mx-auto flex items-center justify-center">
    <!-- Halka efekti -->
    <div class="absolute inset-0 bg-purple-100 rounded-full animate-ping opacity-25"></div>
    <div class="relative w-24 h-24 bg-gradient-to-tr from-purple-600 to-indigo-600 rounded-3xl flex items-center justify-center text-white shadow-xl shadow-purple-200 rotate-2 transition-transform hover:rotate-6">
        <svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"></path>
        </svg>
    </div>
    <!-- Minik transfer ikonu -->
    <div class="absolute -bottom-1 -right-1 bg-white text-purple-700 rounded-full p-1.5 shadow-md border-2 border-purple-200">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"></path>
        </svg>
    </div>
</div>
@endsection

@section('message')
    Bu bağlantıya gönderilen HTTP istek türü (GET, POST, PUT veya DELETE) sistem tarafından bu sayfa için desteklenmemektedir.
@endsection

@section('sub_message')
    Doğrudan URL üzerinden form göndermeye çalışmış veya tarayıcı önbelleğinizdeki eski bir işlemi tekrarlamış olabilirsiniz. Lütfen işlemi anasayfadan başlatınız.
@endsection
