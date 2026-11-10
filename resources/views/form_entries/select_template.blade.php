@section('title', 'Veri Girişi İçin Form Seçimi')
<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col md:flex-row md:items-center justify-between">
            <div>
                <h2 class="font-extrabold text-2xl text-gray-900 tracking-tight">
                    {{ __('Hangi forma veri girmek istiyorsunuz?') }}
                </h2>
                <p class="text-sm text-gray-500 mt-1">{{ __('Lütfen veri girişi yapmak istediğiniz şablonu seçin.') }}</p>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            
            @if($templates->isEmpty())
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-12 text-center flex flex-col items-center justify-center">
                    <div class="w-16 h-16 bg-gray-50 rounded-full flex items-center justify-center mb-4">
                        <svg class="h-8 w-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                    </div>
                    <h3 class="text-lg font-bold text-gray-900">Form Bulunamadı</h3>
                    <p class="mt-2 text-sm text-gray-500 max-w-md mx-auto">Size atanmış aktif veya oluşturulmuş bir form şablonu bulunmuyor. Yeni bir form oluşturulduğunda burada görünecektir.</p>
                </div>
            @else
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    @foreach($templates as $template)
                        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden hover:shadow-lg transition-all duration-300 flex flex-col group relative">
                            <!-- Dekoratif Üst Çizgi -->
                            <div class="h-1 w-full bg-gradient-to-r from-indigo-500 to-purple-500 transform origin-left scale-x-0 group-hover:scale-x-100 transition-transform duration-300 absolute top-0 left-0"></div>
                            
                            <div class="p-6 flex-grow flex flex-col">
                                <div class="flex items-start justify-between mb-4">
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0 h-12 w-12 flex items-center justify-center rounded-xl bg-indigo-50 text-indigo-600 font-bold text-2xl group-hover:bg-indigo-600 group-hover:text-white transition-colors duration-300">
                                            {{ mb_substr($template->name, 0, 1) }}
                                        </div>
                                        <div class="ml-4">
                                            <h3 class="text-lg font-bold text-gray-900 group-hover:text-indigo-600 transition-colors line-clamp-1" title="{{ $template->name }}">{{ $template->name }}</h3>
                                            <div class="text-[11px] text-gray-400 font-medium uppercase tracking-wider mt-1">
                                                Oluşturulma: {{ $template->created_at->format('d.m.Y') }}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <p class="text-sm text-gray-500 mb-6 flex-grow line-clamp-3" title="{{ $template->description }}">
                                    {{ $template->description ?? 'Bu form için bir açıklama girilmemiş.' }}
                                </p>
                                
                                <div class="space-y-4 mb-6">
                                    @if(isset($canViewTargets) && $canViewTargets)
                                    <div>
                                        <div class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2">Erişim Yetkisi Olanlar</div>
                                        @if($template->schedules->count() > 0)
                                            <div class="flex flex-wrap gap-1.5">
                                                @foreach($template->schedules as $schedule)
                                                    <span class="inline-flex items-center px-2 py-1 rounded-md text-xs font-medium bg-indigo-50 text-indigo-700 border border-indigo-100">
                                                        {{ $schedule->target_name }}
                                                    </span>
                                                @endforeach
                                            </div>
                                        @else
                                            <span class="text-xs text-gray-400 italic">Atama Yok</span>
                                        @endif
                                    </div>
                                    @endif
                                    
                                    <div>
                                        <div class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2">Geçerlilik Süresi</div>
                                        @if($template->schedules->count() > 0)
                                            <div class="space-y-1.5">
                                                @foreach($template->schedules as $schedule)
                                                    <div class="flex items-center text-xs">
                                                        @if($schedule->is_indefinite)
                                                            <span class="inline-flex items-center px-2 py-1 rounded-md font-medium bg-emerald-50 text-emerald-700 border border-emerald-100 w-full">
                                                                <span class="w-1.5 h-1.5 bg-emerald-500 rounded-full mr-2"></span>
                                                                Süresiz Erişim
                                                            </span>
                                                        @else
                                                            <span class="inline-flex items-center px-2 py-1 rounded-md font-medium bg-amber-50 text-amber-700 border border-amber-100 w-full">
                                                                <span class="w-1.5 h-1.5 bg-amber-500 rounded-full mr-2"></span>
                                                                {{ $schedule->start_time ? $schedule->start_time->format('d.m.Y') : '?' }} - {{ $schedule->end_time ? $schedule->end_time->format('d.m.Y') : '?' }}
                                                            </span>
                                                        @endif
                                                    </div>
                                                @endforeach
                                            </div>
                                        @else
                                            <span class="inline-flex items-center px-2 py-1 rounded-md text-xs font-medium bg-red-50 text-red-600 border border-red-100">
                                                Geçerlilik Kuralı Yok
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            
                            <div class="p-4 bg-gray-50 border-t border-gray-100 flex justify-end" x-data="{ clicking: false }">
                                <a href="{{ route('form-entries.create', $template) }}"
                                    @click="if(clicking) { $event.preventDefault(); return false; } clicking = true;"
                                    onclick="if(this.dataset.clicked === 'true'){ return false; } this.dataset.clicked = 'true'; this.classList.add('pointer-events-none', 'opacity-70', 'cursor-not-allowed');"
                                    :class="clicking ? 'pointer-events-none opacity-70 cursor-not-allowed' : ''"
                                    class="inline-flex items-center justify-center px-5 py-2.5 border border-transparent text-sm font-semibold rounded-xl text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 shadow-sm transition-all duration-200 w-full md:w-auto hover:shadow select-none">
                                    <svg x-show="clicking" style="display: none;" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    <span x-text="clicking ? 'Yönlendiriliyor...' : 'Forma Veri Gir'">Forma Veri Gir</span>
                                    <svg x-show="!clicking" class="ml-2 -mr-1 h-4 w-4 transform group-hover:translate-x-1 transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
                                </a>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
