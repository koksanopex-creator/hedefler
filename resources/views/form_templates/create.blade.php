@section('title', 'Yeni Form Tasarımı')
<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center space-x-4">
            <a href="{{ route('form-templates.index') }}" class="text-gray-500 hover:text-gray-700 transition">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
            </a>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Yeni Form Şablonu Oluştur') }}
            </h2>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg p-8 border border-gray-100">
                
                @if($errors->any())
                    <div class="bg-red-50 text-red-500 p-4 rounded-lg mb-6 border border-red-100">
                        <ul class="list-disc list-inside">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form action="{{ route('form-templates.store') }}" method="POST">
                    @csrf
                    
                    <div class="mb-6">
                        <label for="name" class="block text-sm font-medium text-gray-700 mb-2">Şablon Adı</label>
                        <input type="text" name="name" id="name" value="{{ old('name') }}" required class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 p-3" placeholder="Örn: Günlük Üretim Raporu">
                    </div>

                    <div class="mb-6">
                        <label for="description" class="block text-sm font-medium text-gray-700 mb-2">Açıklama (Opsiyonel)</label>
                        <textarea name="description" id="description" rows="3" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 p-3" placeholder="Şablonun ne amaçla kullanılacağını belirtebilirsiniz.">{{ old('description') }}</textarea>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                        <div class="flex items-center bg-gray-50 p-4 rounded-lg border border-gray-100">
                            <input id="is_active" name="is_active" type="checkbox" value="1" {{ old('is_active', true) ? 'checked' : '' }} class="h-5 w-5 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded">
                            <label for="is_active" class="ml-3 block text-sm font-medium text-gray-700">
                                Şablon Aktif (Kullanıma Açık)
                            </label>
                        </div>
                        
                        <div class="flex items-center bg-gray-50 p-4 rounded-lg border border-gray-100">
                            <input id="show_row_totals" name="show_row_totals" type="checkbox" value="1" {{ old('show_row_totals') ? 'checked' : '' }} class="h-5 w-5 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded">
                            <label for="show_row_totals" class="ml-3 block text-sm font-medium text-gray-700">
                                Alt Toplam Göster (Satır toplama özellikli alanlar için)
                            </label>
                        </div>
                    </div>

                    <div class="flex justify-end">
                        <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-3 px-6 rounded-lg shadow-md transition ease-in-out duration-150 flex items-center">
                            Kaydet ve Alan Tasarımına Geç
                            <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>

