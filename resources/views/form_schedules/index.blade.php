@section('title', 'Form Geçerlilik Takvimi')
<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ $formTemplate->name }} - Erişim Takvimi
            </h2>
            <a href="{{ route('form-templates.index') }}" class="text-gray-500 hover:text-gray-700 transition">
                Geri Dön
            </a>
        </div>
    </x-slot>

    <div class="py-12" x-data="{ targetType: 'all', isIndefinite: true }">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            
            @if(session('success'))
                <div class="p-4 mb-6 text-sm text-green-700 bg-green-100 rounded-lg" role="alert">
                    {{ session('success') }}
                </div>
            @endif

            @if($errors->any())
                <div class="bg-red-50 text-red-500 p-4 rounded-lg mb-6 border border-red-100">
                    <ul class="list-disc list-inside">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <!-- Yeni Takvim Kuralı Ekleme Formu -->
            <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg p-6 border border-gray-100 mb-8">
                <h3 class="text-lg font-bold text-gray-900 mb-4 border-b pb-2">Yeni Erişim Kuralı Ekle</h3>
                
                <form action="{{ route('form-schedules.store', $formTemplate) }}" method="POST">
                    @csrf
                    
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                        <!-- Hedef Kitle Seçimi -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Hedef Kitle</label>
                            <select name="target_type" x-model="targetType" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 bg-white">
                                <option value="all">Herkes (Tüm Kullanıcılar)</option>
                                <option value="group">Belirli Bir Grup</option>
                                <option value="user">Belirli Bir Kullanıcı</option>
                            </select>
                        </div>
                        
                        <!-- Hedef ID (Grup veya Kullanıcı) -->
                        <div x-show="targetType === 'group'" x-cloak>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Grup Seçin</label>
                            <select name="target_id" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 bg-white" :disabled="targetType !== 'group'">
                                <option value="">Seçiniz...</option>
                                @foreach($groups as $group)
                                    <option value="{{ $group->id }}">{{ $group->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div x-show="targetType === 'user'" x-cloak>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Kullanıcı Seçin</label>
                            <select name="target_id" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 bg-white" :disabled="targetType !== 'user'">
                                <option value="">Seçiniz...</option>
                                @foreach($users as $user)
                                    <option value="{{ $user->id }}">{{ $user->name }} ({{ $user->email }})</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6 p-4 bg-gray-50 border border-gray-200 rounded-lg">
                        <!-- Süre Tipi -->
                        <div class="flex items-center mt-6">
                            <input type="checkbox" name="is_indefinite" x-model="isIndefinite" value="1" class="h-5 w-5 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded">
                            <label class="ml-2 block text-sm font-bold text-gray-900">Süresiz Açık</label>
                        </div>
                        
                        <!-- Başlangıç / Bitiş -->
                        <div x-show="!isIndefinite">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Başlangıç Zamanı</label>
                            <input type="datetime-local" name="start_time" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" :disabled="isIndefinite">
                        </div>

                        <div x-show="!isIndefinite">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Bitiş Zamanı</label>
                            <input type="datetime-local" name="end_time" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" :disabled="isIndefinite">
                        </div>
                    </div>

                    <div class="flex items-center justify-between pt-4 border-t border-gray-200">
                        <div class="flex items-center">
                            <input type="checkbox" name="is_locked" value="1" class="h-5 w-5 text-red-600 focus:ring-red-500 border-gray-300 rounded">
                            <label class="ml-2 block text-sm font-bold text-red-700">Bu kuralı "KİLİTLİ" (Erişime Kapalı) olarak tanımla</label>
                        </div>
                        <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-3 px-8 rounded-lg shadow-md transition">
                            Kuralı Ekle
                        </button>
                    </div>
                </form>
            </div>

            <!-- Mevcut Kurallar Listesi -->
            <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg border border-gray-100">
                <div class="p-6 border-b border-gray-100">
                    <h3 class="text-lg font-bold text-gray-900">Mevcut Erişim Kuralları</h3>
                </div>
                
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Hedef Kitle</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Erişim Türü</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tarih Aralığı</th>
                                <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Durum</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">İşlem</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @forelse($schedules as $schedule)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-gray-900">
                                        {{ $schedule->target_name }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        @if($schedule->is_indefinite)
                                            <span class="text-green-600 font-semibold">Süresiz</span>
                                        @else
                                            <span class="text-blue-600 font-semibold">Süreli</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        @if($schedule->is_indefinite)
                                            -
                                        @else
                                            <div class="text-xs">
                                                Başlangıç: {{ $schedule->start_time ? $schedule->start_time->format('d.m.Y H:i') : '-' }}<br>
                                                Bitiş: <span class="font-bold text-red-500">{{ $schedule->end_time ? $schedule->end_time->format('d.m.Y H:i') : '-' }}</span>
                                            </div>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-center text-sm">
                                        @if($schedule->is_locked)
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">Kilitli (Giremez)</span>
                                        @else
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">Açık (Girebilir)</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <form action="{{ route('form-schedules.destroy', $schedule) }}" method="POST" onsubmit="return confirm('Bu kuralı silmek istediğinize emin misiniz?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-red-600 hover:text-red-900">Sil</button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-6 py-4 whitespace-nowrap text-center text-gray-500">
                                        Bu form için tanımlanmış herhangi bir erişim kuralı yok. Form varsayılan olarak kapalıdır.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            
        </div>
    </div>
</x-app-layout>

