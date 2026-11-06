@section('title', 'Grup Düzenle')
<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ isset($group) ? 'Grup Düzenle: ' . $group->name : 'Yeni Grup Oluştur' }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg p-8 border border-gray-100">
                
                @if($errors->any())
                    <div class="bg-red-50 text-red-500 p-4 rounded-lg mb-6">
                        <ul class="list-disc list-inside">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @php
                    $action = isset($group) ? route('groups.update', $group) : route('groups.store');
                @endphp

                <form action="{{ $action }}" method="POST">
                    @csrf
                    @if(isset($group))
                        @method('PUT')
                    @endif
                    
                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Grup Adı</label>
                        <input type="text" name="name" value="{{ old('name', $group->name ?? '') }}" required class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    </div>

                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Açıklama (Opsiyonel)</label>
                        <textarea name="description" rows="3" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">{{ old('description', $group->description ?? '') }}</textarea>
                    </div>

                    <div class="mb-8 flex items-center">
                        <input type="checkbox" name="is_active" id="is_active" value="1" {{ old('is_active', $group->is_active ?? true) ? 'checked' : '' }} class="h-5 w-5 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded">
                        <label for="is_active" class="ml-2 block text-sm text-gray-900">Grup Aktif</label>
                    </div>

                    <div class="flex justify-end pt-4 border-t border-gray-200">
                        <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-3 px-8 rounded-lg shadow-md transition">
                            {{ isset($group) ? 'Güncellemeleri Kaydet' : 'Grubu Oluştur' }}
                        </button>
                    </div>
                </form>

            </div>
        </div>
    </div>
</x-app-layout>

