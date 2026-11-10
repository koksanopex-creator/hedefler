@section('title', 'Form Şablonu Önizleme')
<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <div class="flex items-center space-x-4">
                <a href="{{ route('form-templates.index') }}" class="text-gray-500 hover:text-gray-700 transition">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                </a>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    {{ $formTemplate->name }}
                </h2>
            </div>
            <a href="{{ route('form_fields.edit', $formTemplate) }}" class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-4 rounded-lg shadow transition ease-in-out duration-150 flex items-center">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                Form Alanlarını Düzenle
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            
            @if(session('success'))
                <div class="p-4 mb-6 text-sm text-green-700 bg-green-100 rounded-lg" role="alert">
                    {{ session('success') }}
                </div>
            @endif

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                
                <!-- Sol Taraf: Şablon Detayları -->
                <div class="col-span-1">
                    <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg p-6 border border-gray-100 mb-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-4 border-b pb-2">Şablon Bilgileri</h3>
                        <div class="space-y-4">
                            <div>
                                <span class="block text-sm font-medium text-gray-500">Durum</span>
                                @if($formTemplate->is_active)
                                    <span class="mt-1 inline-flex px-2 text-xs font-semibold rounded-full bg-green-100 text-green-800">Aktif</span>
                                @else
                                    <span class="mt-1 inline-flex px-2 text-xs font-semibold rounded-full bg-red-100 text-red-800">Pasif</span>
                                @endif
                            </div>
                            <div>
                                <span class="block text-sm font-medium text-gray-500">Açıklama</span>
                                <p class="mt-1 text-sm text-gray-900">{{ $formTemplate->description ?: 'Açıklama girilmemiş.' }}</p>
                            </div>
                            <div>
                                <span class="block text-sm font-medium text-gray-500">Alt Toplam Gösterimi</span>
                                <p class="mt-1 text-sm text-gray-900">{{ $formTemplate->show_row_totals ? 'Açık' : 'Kapalı' }}</p>
                            </div>
                            <div>
                                <span class="block text-sm font-medium text-gray-500">Parçalı / Kısıtlı Giriş Modu</span>
                                @if($formTemplate->isPartitioned())
                                    <div class="mt-1 flex items-center gap-2">
                                        <span class="inline-flex items-center px-2.5 py-0.5 text-xs font-bold rounded-full bg-indigo-100 text-indigo-800 border border-indigo-200">
                                            Aktif (Kriter: {{ $formTemplate->partitionField?->label ?? 'Tanımsız' }})
                                        </span>
                                    </div>
                                @else
                                    <span class="mt-1 inline-flex px-2 text-xs font-semibold rounded-full bg-slate-100 text-slate-600">Kapalı (Klasik Tek Parça)</span>
                                @endif
                            </div>
                            <div>
                                <span class="block text-sm font-medium text-gray-500">Oluşturan</span>
                                <p class="mt-1 text-sm text-gray-900">{{ $formTemplate->creator->name ?? 'Bilinmiyor' }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Sağ Taraf: Form Alanları (Preview) -->
                <div class="col-span-1 md:col-span-2">
                    <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg p-6 border border-gray-100">
                        <h3 class="text-lg font-medium text-gray-900 mb-4 border-b pb-2">Form Alanları (Önizleme)</h3>
                        
                        @if($formTemplate->fields->isEmpty())
                            <div class="text-center py-8">
                                <svg class="mx-auto h-12 w-12 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 13h6m-3-3v6m5 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                                <p class="mt-4 text-sm text-gray-500">Bu şablona henüz hiçbir alan eklenmemiş.</p>
                                <a href="{{ route('form_fields.edit', $formTemplate) }}" class="mt-4 inline-block text-indigo-600 hover:text-indigo-900 font-medium text-sm">
                                    Şimdi Alan Ekle
                                </a>
                            </div>
                        @else
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200 border rounded-lg">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Sıra</th>
                                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Etiket (Label)</th>
                                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tip</th>
                                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Değer Tipi</th>
                                            <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Zorunlu</th>
                                            <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Toplanabilir</th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-200">
                                        @foreach($formTemplate->fields as $index => $field)
                                            <tr class="hover:bg-gray-50">
                                                <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500">{{ $index + 1 }}</td>
                                                <td class="px-4 py-3 whitespace-nowrap text-sm font-medium text-gray-900">{{ $field->label }}</td>
                                                <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500">
                                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">
                                                        {{ $field->type }}
                                                    </span>
                                                </td>
                                                <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500">{{ $field->value_type == 'nicel' ? 'Nicel (Sayısal)' : 'Nitel (Metin/Kategori)' }}</td>
                                                <td class="px-4 py-3 whitespace-nowrap text-center text-sm text-gray-500">
                                                    {!! $field->is_required ? '<span class="text-green-600">âœ”</span>' : '<span class="text-gray-300">-</span>' !!}
                                                </td>
                                                <td class="px-4 py-3 whitespace-nowrap text-center text-sm text-gray-500">
                                                    {!! $field->is_summable ? '<span class="text-green-600">âœ”</span>' : '<span class="text-gray-300">-</span>' !!}
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>

