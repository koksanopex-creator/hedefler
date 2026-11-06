@section('title', 'Master Veri Yönetimi')
<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3">
            <div>
                <h2 class="font-semibold text-lg text-gray-800 leading-tight">
                    {{ __('Master Data Yönetimi (Dinamik Listeler)') }}
                </h2>
                <p class="text-xs text-gray-500">Form alanlarında kullanılacak tekli ve birbirine bağlı dinamik seçim listeleri.</p>
            </div>
            
            <!-- Son İşlemleri Geri Al Menüsü (Bağımsız Alpine Bileşeni) -->
            @if(isset($recentBatches) && $recentBatches->count() > 0)
                <div x-data="{ open: false }" class="relative">
                    <button type="button" @click="open = !open" class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-amber-50 hover:bg-amber-100 active:bg-amber-200 text-amber-800 border border-amber-300 rounded-lg text-xs font-bold shadow-xs transition cursor-pointer">
                        <svg class="w-4 h-4 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        <span>🕒 Son İşlemleri Geri Al ({{ $recentBatches->count() }})</span>
                        <svg class="w-3.5 h-3.5 text-amber-600 transition-transform duration-200" :class="{'rotate-180': open}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                    </button>

                    <!-- Açılır Menü (Dropdown Panel) -->
                    <div x-show="open" @click.away="open = false" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 scale-95 -translate-y-2" x-transition:enter-end="opacity-100 scale-100 translate-y-0" x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100 scale-100 translate-y-0" x-transition:leave-end="opacity-0 scale-95 -translate-y-2" style="display: none;" class="absolute right-0 mt-2 w-96 sm:w-[460px] bg-white rounded-xl shadow-2xl border border-gray-200 z-50 overflow-hidden">
                        
                        <!-- Menü Başlığı -->
                        <div class="bg-gradient-to-r from-amber-500 to-amber-600 px-4 py-3 flex justify-between items-center text-white">
                            <div class="flex items-center gap-2">
                                <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                <span class="font-bold text-xs uppercase tracking-wider">Son Yapılan {{ $recentBatches->count() }} Veri Ekleme İşlemi</span>
                            </div>
                            <button type="button" @click="open = false" class="text-amber-100 hover:text-white transition">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                            </button>
                        </div>

                        <!-- İşlem Listesi (Sıralı 1, 2, 3...) -->
                        <div class="p-3 max-h-[70vh] overflow-y-auto divide-y divide-gray-100 space-y-2.5">
                            @foreach($recentBatches as $index => $b)
                                <div class="pt-2.5 first:pt-0">
                                    <div class="p-3 bg-gray-50 hover:bg-amber-50/40 rounded-lg border border-gray-200/80 transition flex items-center justify-between gap-3">
                                        <div class="flex-grow min-w-0">
                                            <div class="flex items-center gap-2">
                                                <span class="w-5 h-5 rounded-full {{ $index === 0 ? 'bg-amber-500 text-white font-extrabold' : 'bg-gray-200 text-gray-700 font-bold' }} text-[10px] flex items-center justify-center flex-shrink-0">
                                                    {{ $index + 1 }}
                                                </span>
                                                <span class="text-xs font-bold text-gray-900 truncate">
                                                    {{ $b->type?->name ?? 'Kategori' }}
                                                </span>
                                                <span class="px-2 py-0.5 text-[10px] font-bold bg-blue-100 text-blue-800 rounded-full flex-shrink-0">
                                                    {{ $b->count }} Veri
                                                </span>
                                                @if($index === 0)
                                                    <span class="text-[9px] font-bold bg-emerald-100 text-emerald-800 px-1.5 py-0.5 rounded-full">En Son</span>
                                                @endif
                                            </div>
                                            @if($b->preview)
                                                <div class="text-[11px] text-gray-600 mt-1 pl-7 font-mono truncate" title="{{ $b->preview }}">
                                                    {{ $b->preview }}
                                                </div>
                                            @endif
                                            <div class="text-[10px] text-gray-400 mt-0.5 pl-7">
                                                {{ $b->created_at->diffForHumans() }} ({{ $b->created_at->format('H:i') }})
                                            </div>
                                        </div>

                                        <form action="{{ route('master_data.batches.undo', $b->id) }}" method="POST" onsubmit="return confirm('\'{{ $b->type?->name }}\' kategorisine eklenen {{ $b->count }} adet veriyi geri alıp silmek istediğinize emin misiniz?');" class="flex-shrink-0">
                                            @csrf
                                            <button type="submit" class="inline-flex items-center gap-1 px-3 py-1.5 bg-red-50 hover:bg-red-600 text-red-700 hover:text-white border border-red-200 hover:border-red-600 rounded-md text-xs font-bold transition shadow-xs">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"></path></svg>
                                                Geri Al
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <!-- Alt Bilgi -->
                        <div class="bg-gray-50 px-4 py-2 border-t border-gray-200 text-[11px] text-gray-500">
                            💡 İlgili işlemin yanındaki <strong>"Geri Al"</strong> butonuna basarak o gruptaki verileri tek tek geri alabilirsiniz.
                        </div>

                    </div>
                </div>
            @endif
        </div>
    </x-slot>

    <div class="py-6" x-data="masterDataApp()">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            
            @if(session('success'))
                <div class="p-3 mb-4 text-sm text-green-700 bg-green-50 border-l-4 border-green-500 rounded-r-md shadow-sm flex flex-col sm:flex-row sm:items-center justify-between gap-3" role="alert">
                    <div class="flex items-center">
                        <svg class="w-5 h-5 mr-2 text-green-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                        <span class="font-medium">{{ session('success') }}</span>
                    </div>

                    @if(session('last_batch_id'))
                        <div class="flex flex-wrap items-center gap-2">
                            <!-- Doğrudan Son İşlemi Geri Al Butonu -->
                            <form action="{{ route('master_data.batches.undo', session('last_batch_id')) }}" method="POST" onsubmit="return confirm('Son eklenen {{ session('last_batch_count') }} adet veriyi geri alıp silmek istediğinize emin misiniz?');">
                                @csrf
                                <button type="submit" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-white hover:bg-red-50 text-red-700 hover:text-red-800 border border-red-300 rounded-md text-xs font-bold transition shadow-xs">
                                    <svg class="w-4 h-4 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"></path></svg>
                                    ↩️ Son İşlemi Geri Al ({{ session('last_batch_count') }} Veriyi Sil)
                                </button>
                            </form>
                        </div>
                    @endif
                </div>
            @endif
            @if(session('error'))
                <div class="p-3 mb-4 text-sm text-red-700 bg-red-50 border-l-4 border-red-500 rounded-r-md shadow-sm flex items-center" role="alert">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                    {{ session('error') }}
                </div>
            @endif

            <div class="grid grid-cols-1 md:grid-cols-12 gap-0 border border-gray-300 rounded-sm shadow-sm bg-white overflow-hidden">
                <!-- Sol Panel: Master Data Tipleri -->
                <div class="md:col-span-4 border-r border-gray-300 bg-gray-50 flex flex-col h-[75vh]">
                    <div class="px-4 py-3 border-b border-gray-300 bg-gray-100 flex justify-between items-center">
                        <h3 class="text-sm font-bold text-gray-700 uppercase tracking-wide">Kategoriler (Veri Tipleri)</h3>
                    </div>
                    
                    <!-- Tip Ekleme Formu -->
                    <div class="p-3 bg-white border-b border-gray-300 shadow-inner">
                        <form action="{{ route('master_data.types.store') }}" method="POST" class="space-y-2">
                            @csrf
                            <div>
                                <input type="text" name="name" placeholder="Yeni Tip Adı (Örn: Şehirler)" required class="w-full rounded-sm border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-xs p-2">
                            </div>
                            <div>
                                <select name="parent_id" class="w-full rounded-sm border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-xs p-2 text-gray-600 bg-gray-50">
                                    <option value="">-- Bağımsız Kategori (Üst Tip Yok) --</option>
                                    @foreach($types as $t)
                                        <option value="{{ $t->id }}">{{ $t->name }}'e Bağlı Alt Kategori</option>
                                    @endforeach
                                </select>
                            </div>
                            <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-1.5 px-3 rounded-sm text-xs transition shadow-sm">
                                + Yeni Tip Ekle
                            </button>
                        </form>
                    </div>

                    <!-- Tip Listesi -->
                    <ul class="divide-y divide-gray-200 overflow-y-auto flex-grow bg-white">
                        @forelse($types as $t)
                            <li class="group cursor-pointer transition flex justify-between items-stretch hover:bg-blue-50" 
                                :class="{'bg-blue-100 border-l-4 border-blue-600': selectedTypeId == {{ $t->id }}, 'border-l-4 border-transparent': selectedTypeId != {{ $t->id }}}"
                                @click="selectType({{ $t->id }}, '{{ $t->name }}', {{ $t->parent_id ?? 'null' }})">
                                
                                <div class="p-3 flex-grow flex items-center">
                                    @if($t->depth > 0)
                                        <div class="flex items-center text-gray-400 mr-2" style="margin-left: {{ ($t->depth - 1) * 1.5 }}rem;">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5v8a1 1 0 001 1h5"></path>
                                            </svg>
                                        </div>
                                    @endif
                                    <div>
                                        <div class="font-bold text-gray-800 text-sm" :class="{'text-blue-800': selectedTypeId == {{ $t->id }}}">{{ $t->name }}</div>
                                        @if($t->parent)
                                            <div class="text-[10px] text-gray-500 uppercase tracking-wider mt-0.5">Alt Kategori</div>
                                        @else
                                            <div class="text-[10px] text-green-600 uppercase tracking-wider mt-0.5 font-semibold">Bağımsız Kategori</div>
                                        @endif
                                    </div>
                                </div>
                                
                                <div class="flex items-center px-2">
                                    <span class="inline-flex items-center justify-center px-2 py-1 text-xs font-bold leading-none text-gray-500 bg-gray-100 rounded-full group-hover:bg-blue-200 group-hover:text-blue-800 transition mr-2" :class="{'bg-blue-200 text-blue-800': selectedTypeId == {{ $t->id }}}">
                                        {{ $t->items->count() }}
                                    </span>
                                    <form action="{{ route('master_data.types.destroy', $t) }}" method="POST" onsubmit="return confirm('Bu tipi ve altındaki TÜM verileri silmek istediğinize emin misiniz? Bu işlem geri alınamaz.');" class="opacity-0 group-hover:opacity-100 transition">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-red-400 hover:text-red-600 p-1 rounded hover:bg-red-50" title="Tipi Sil" @click.stop>
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                        </button>
                                    </form>
                                </div>
                            </li>
                        @empty
                            <li class="p-4 text-xs text-gray-400 text-center italic">Henüz kategori bulunmuyor.</li>
                        @endforelse
                    </ul>
                </div>

                <!-- Sağ Panel: Master Data Öğeleri -->
                <div class="md:col-span-8 bg-white flex flex-col h-[75vh]">
                    
                    <div x-show="!selectedTypeId" class="flex flex-col items-center justify-center h-full text-gray-400 bg-gray-50/50">
                        <svg class="w-12 h-12 mb-3 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                        <p class="text-sm font-medium">İşlem yapmak için soldan bir Kategori seçiniz.</p>
                    </div>

                    <div x-show="selectedTypeId" style="display: none;" class="flex flex-col h-full">
                        <div class="px-4 py-3 border-b border-gray-300 bg-gray-100 flex justify-between items-center">
                            <h3 class="text-sm font-bold text-blue-900 uppercase tracking-wide">
                                <span x-text="selectedTypeName"></span> <span class="text-gray-500 font-normal normal-case">Veri Listesi</span>
                            </h3>
                            <div class="flex items-center gap-2">
                                <div class="relative hidden sm:block">
                                    <input type="text" x-model="searchQuery" placeholder="İçinde ara..." class="rounded-sm border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-xs p-1.5 pl-7 w-48 bg-white">
                                    <svg class="w-4 h-4 text-gray-400 absolute left-2 top-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                                </div>
                                <button type="button" @click="isEditingType = !isEditingType" class="text-xs text-blue-600 hover:text-blue-800 font-semibold px-2 py-1.5 rounded bg-blue-50 hover:bg-blue-100 transition whitespace-nowrap border border-blue-200 shadow-sm">
                                    Bağ Ayarları
                                </button>
                            </div>
                        </div>
                        
                        <!-- Kategori Bağ Ayarları Düzenleme (Gizli form) -->
                        <div x-show="isEditingType" x-transition class="p-3 bg-blue-50/50 border-b border-blue-200">
                            <form :action="'{{ url('master-data/types') }}/' + selectedTypeId" method="POST" class="flex flex-col sm:flex-row items-center gap-3">
                                @csrf
                                @method('PUT')
                                <div class="text-xs text-gray-700 w-full sm:w-auto font-semibold">Bu kategoriyi bağla:</div>
                                <select name="parent_id" x-model="selectedTypeParentId" class="w-full sm:w-auto rounded-sm border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-xs p-2 flex-grow">
                                    <option value="">-- Bağımsız Kategori Yap (Bağı Kopart) --</option>
                                    <template x-for="t in Object.values(typesList)" :key="t.id">
                                        <option :value="t.id" x-show="t.id != selectedTypeId" x-text="t.name + ' kategorisine bağla'"></option>
                                    </template>
                                </select>
                                <div class="flex gap-2 w-full sm:w-auto justify-end">
                                    <button type="button" @click="isEditingType = false" class="bg-white border border-gray-300 hover:bg-gray-50 text-gray-700 font-semibold py-1.5 px-3 rounded-sm text-xs transition shadow-sm">İptal</button>
                                    <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-semibold py-1.5 px-4 rounded-sm text-xs transition shadow-sm whitespace-nowrap">Bağı Güncelle</button>
                                </div>
                            </form>
                        </div>
                        
                        <!-- Öğe Ekleme Formu -->
                        <div class="p-3 bg-gray-50 border-b border-gray-300 shadow-inner">
                            <form action="{{ route('master_data.items.store') }}" method="POST" class="flex flex-col sm:flex-row sm:items-center gap-3">
                                @csrf
                                <input type="hidden" name="type_id" :value="selectedTypeId">
                                
                                <div class="w-full sm:flex-grow">
                                    <textarea name="name" rows="1" placeholder="Çoklu eklemek için noktalı virgül (;) veya yeni satır (Enter) kullanın. (Örn: Fransa; İspanya; İtalya)" required class="w-full rounded-sm border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-xs p-2 bg-white resize-y" oninput="this.style.height = ''; this.style.height = this.scrollHeight + 'px'"></textarea>
                                </div>
                                
                                <div class="w-full sm:flex-grow" x-show="selectedTypeParentId">
                                    <select name="parent_id" class="w-full rounded-sm border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-xs p-2 bg-white" :required="selectedTypeParentId !== null">
                                        <option value="">-- Üst Veri Seçin --</option>
                                        <template x-for="item in parentItems" :key="item.id">
                                            <option :value="item.id" x-text="item.name"></option>
                                        </template>
                                    </select>
                                </div>

                                <button type="submit" class="w-full sm:w-auto bg-gray-800 hover:bg-black text-white font-semibold py-2 px-5 rounded-sm text-xs transition whitespace-nowrap shadow-sm">
                                    Ekle
                                </button>
                            </form>
                        </div>

                        <!-- Toplu Silme Barı -->
                        <div x-show="selectedItemIds.length > 0" x-transition class="bg-red-50 p-2 border-b border-red-200 flex justify-between items-center" style="display: none;">
                            <span class="text-xs font-semibold text-red-700" x-text="selectedItemIds.length + ' veri seçildi.'"></span>
                            <form action="{{ route('master_data.items.bulk_destroy') }}" method="POST" onsubmit="return confirm('Seçili verileri silmek istediğinize emin misiniz?');">
                                @csrf
                                @method('DELETE')
                                <input type="hidden" name="type_id" :value="selectedTypeId">
                                <template x-for="id in selectedItemIds" :key="id">
                                    <input type="hidden" name="item_ids[]" :value="id">
                                </template>
                                <button type="submit" class="bg-red-600 hover:bg-red-700 text-white text-xs font-bold py-1 px-3 rounded-sm shadow-sm transition">
                                    Seçilenleri Sil
                                </button>
                            </form>
                        </div>

                        <!-- Öğeler Tablosu -->
                        <div class="flex-grow overflow-y-auto bg-white">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-white sticky top-0 border-b border-gray-300 z-10 shadow-sm">
                                    <tr>
                                        <th class="px-4 py-2 w-10 text-center">
                                            <input type="checkbox" x-model="selectAll" @change="toggleSelectAll()" class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                                        </th>
                                        <th class="px-4 py-2 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Veri Adı</th>
                                        <th class="px-4 py-2 text-left text-xs font-bold text-gray-500 uppercase tracking-wider" x-show="selectedTypeParentId">Bağlı Olduğu Üst Veri</th>
                                        <th class="px-4 py-2 text-right text-xs font-bold text-gray-500 uppercase tracking-wider w-32">İşlem</th>
                                    </tr>
                                </thead>
                                <template x-for="(group, gIndex) in groupedFilteredItems()" :key="group.parent_name || 'all'">
                                    <tbody class="bg-white divide-y divide-gray-100 border-b-2 border-gray-200">
                                        <!-- Group Header (Only shown if there is a parent) -->
                                        <template x-if="group.parent_name">
                                            <tr class="bg-gray-50 border-y border-gray-200">
                                                <td colspan="4" class="px-4 py-2">
                                                    <div class="flex items-center text-xs font-bold text-gray-500 uppercase tracking-wider">
                                                        <svg class="w-4 h-4 mr-1 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
                                                        BAĞLI OLDUĞU ÜST VERİ: <span class="ml-1 text-blue-700 bg-blue-50 px-2 py-0.5 rounded border border-blue-200" x-text="group.parent_name"></span>
                                                    </div>
                                                </td>
                                            </tr>
                                        </template>

                                        <template x-for="(item, iIndex) in group.items" :key="item.id">
                                            <tr class="hover:bg-blue-50/50 transition">
                                                
                                                <!-- Checkbox Column -->
                                                <td class="px-4 py-2 text-center whitespace-nowrap border-r border-gray-100">
                                                    <input type="checkbox" x-model="selectedItemIds" :value="item.id" class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                                                </td>

                                                <!-- Normal Mode -->
                                                <template x-if="editingItemId !== item.id">
                                                    <td class="px-4 py-3 whitespace-nowrap text-sm font-medium text-gray-800" x-text="item.name"></td>
                                                </template>
                                                <template x-if="editingItemId !== item.id">
                                                    <td class="px-4 py-3 whitespace-nowrap text-xs text-gray-500" x-show="selectedTypeParentId" x-text="getParentName(item.parent_id)"></td>
                                                </template>
                                                <template x-if="editingItemId !== item.id">
                                                    <td class="px-4 py-2 whitespace-nowrap text-right flex justify-end items-center gap-2">
                                                        <button type="button" @click="startEditing(item.id, item.name, item.parent_id)" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold text-blue-700 bg-blue-50 border border-blue-200 rounded-md hover:bg-blue-600 hover:text-white hover:border-blue-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition shadow-sm">
                                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path></svg>
                                                            Düzenle
                                                        </button>
                                                        <form :action="'{{ url('master-data/items') }}/' + item.id" method="POST" onsubmit="return confirm('Bu veriyi silmek istediğinize emin misiniz?');" class="inline">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold text-red-700 bg-red-50 border border-red-200 rounded-md hover:bg-red-600 hover:text-white hover:border-red-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 transition shadow-sm">
                                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                                                Sil
                                                            </button>
                                                        </form>
                                                    </td>
                                                </template>

                                                <!-- Editing Mode -->
                                                <template x-if="editingItemId === item.id">
                                                    <td :colspan="selectedTypeParentId ? 3 : 2" class="px-4 py-2 bg-yellow-50/50 border-y border-yellow-200">
                                                        <form :action="'{{ url('master-data/items') }}/' + item.id" method="POST" class="flex items-center gap-2">
                                                            @csrf
                                                            @method('PUT')
                                                            <input type="text" name="name" x-model="editFormData.name" required class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm p-1.5">
                                                            
                                                            <div class="w-full" x-show="selectedTypeParentId">
                                                                <select name="parent_id" x-model="editFormData.parent_id" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm p-1.5" :required="selectedTypeParentId !== null">
                                                                    <option value="">-- Üst Veri Seçin --</option>
                                                                    <template x-for="pItem in parentItems" :key="pItem.id">
                                                                        <option :value="pItem.id" x-text="pItem.name"></option>
                                                                    </template>
                                                                </select>
                                                            </div>
                                                            <button type="button" @click="cancelEditing()" class="bg-white border border-gray-300 hover:bg-gray-50 text-gray-700 font-semibold py-1.5 px-3 rounded-md text-xs transition shadow-sm">İptal</button>
                                                            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-semibold py-1.5 px-4 rounded-md text-xs transition shadow-sm">Kaydet</button>
                                                        </form>
                                                    </td>
                                                </template>
                                                
                                            </tr>
                                        </template>
                                    </tbody>
                                </template>
                                
                                <!-- Boş Durum -->
                                <tbody x-show="filteredItems().length === 0" style="display: none;">
                                    <tr>
                                        <td colspan="4" class="px-4 py-8 text-center text-gray-400 text-xs italic">
                                            Bu kategoride henüz veri bulunmuyor veya aramaya uygun sonuç yok.
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    
                </div>
            </div>
        </div>

    </div>

    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('masterDataApp', () => ({
                selectedTypeId: null,
                selectedTypeName: '',
                selectedTypeParentId: null,
                parentItems: [],
                isEditingType: false,
                searchQuery: '',
                
                // Bulk delete & inline edit state
                selectedItemIds: [],
                selectAll: false,
                editingItemId: null,
                editFormData: { name: '', parent_id: '' },
                
                // Data mapping for fast lookup (passed from PHP directly)
                typesList: @json($typesList),
                allTypesData: @json($allTypesData),

                init() {
                    let flashedId = {{ session('selectedTypeId') ? session('selectedTypeId') : 'null' }};
                    
                    if (flashedId && this.typesList[flashedId]) {
                        let t = this.typesList[flashedId];
                        this.selectType(t.id, t.name, t.parent_id);
                    }
                    
                    this.checkEmptyState();
                },

                selectType(id, name, parentId) {
                    this.selectedTypeId = id;
                    this.selectedTypeName = name;
                    this.selectedTypeParentId = parentId;
                    this.isEditingType = false;
                    this.selectedItemIds = [];
                    this.selectAll = false;
                    this.editingItemId = null;
                    
                    if (parentId) {
                        let parentTypeData = this.allTypesData[parentId];
                        this.parentItems = parentTypeData ? parentTypeData.items : [];
                    } else {
                        this.parentItems = [];
                    }
                    
                    this.checkEmptyState();
                },
                
                toggleSelectAll() {
                    if (this.selectAll) {
                        let currentItems = this.filteredItems();
                        this.selectedItemIds = currentItems.map(i => i.id);
                    } else {
                        this.selectedItemIds = [];
                    }
                },
                
                filteredItems() {
                    if (!this.selectedTypeId || !this.allTypesData[this.selectedTypeId]) return [];
                    let items = this.allTypesData[this.selectedTypeId].items;
                    
                    if (this.searchQuery.trim() !== '') {
                        let query = this.searchQuery.toLowerCase().trim();
                        items = items.filter(item => item.name.toLowerCase().includes(query));
                    }
                    return items;
                },
                
                groupedFilteredItems() {
                    let items = this.filteredItems();
                    if (items.length === 0) return [];
                    
                    if (!this.selectedTypeParentId) {
                        return [{ parent_name: null, items: items.sort((a,b) => a.name.localeCompare(b.name)) }];
                    }

                    let groups = {};
                    items.forEach(item => {
                        let pName = this.getParentName(item.parent_id);
                        if (!groups[pName]) groups[pName] = [];
                        groups[pName].push(item);
                    });

                    let sortedGroupKeys = Object.keys(groups).sort((a, b) => a.localeCompare(b));
                    
                    return sortedGroupKeys.map(key => {
                        return {
                            parent_name: key,
                            items: groups[key].sort((a,b) => a.name.localeCompare(b.name))
                        };
                    });
                },
                
                getParentName(parentId) {
                    if (!parentId) return '-';
                    // We need to look up the parent name in allTypesData -> items
                    if (this.selectedTypeParentId && this.allTypesData[this.selectedTypeParentId]) {
                        let pItem = this.allTypesData[this.selectedTypeParentId].items.find(i => i.id == parentId);
                        return pItem ? pItem.name : '-';
                    }
                    return '-';
                },
                
                startEditing(id, name, parentId) {
                    this.editingItemId = id;
                    this.editFormData.name = name;
                    this.editFormData.parent_id = parentId || '';
                },
                
                cancelEditing() {
                    this.editingItemId = null;
                },
                
                checkEmptyState() {
                    setTimeout(() => {
                        let emptyState = document.getElementById('empty-state');
                        if (!emptyState) return;
                        
                        if (this.selectedTypeId && this.allTypesData[this.selectedTypeId]) {
                            if (this.allTypesData[this.selectedTypeId].items.length === 0) {
                                emptyState.style.display = 'table-row';
                            } else {
                                emptyState.style.display = 'none';
                            }
                        }
                    }, 50);
                }
            }));
        });
    </script>
</x-app-layout>
