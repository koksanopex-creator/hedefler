@section('title', 'Form Tasarımları')
<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            <div>
                <h2 class="font-extrabold text-2xl text-gray-900 tracking-tight">
                    {{ __('Form Şablonları') }}
                </h2>
                <p class="text-sm text-gray-500 mt-1">Sistemdeki tüm form yapılarını yönetin ve yapılandırın</p>
            </div>
            <a href="{{ route('form-templates.create') }}" class="group relative flex items-center justify-center py-2.5 px-5 border border-transparent text-sm font-semibold rounded-xl text-white bg-gradient-to-r from-indigo-600 to-indigo-700 hover:from-indigo-700 hover:to-indigo-800 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 shadow-md hover:shadow-lg transition-all overflow-hidden">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                Yeni Şablon
            </a>
        </div>
    </x-slot>

    <div class="py-10">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            
            @if(session('success'))
                <div class="p-4 mb-6 text-sm text-teal-800 bg-teal-50 border border-teal-200 rounded-xl flex items-center shadow-sm" role="alert">
                    <svg class="w-5 h-5 mr-2 text-teal-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg>
                    {!! session('success') !!}
                </div>
            @endif

            @if(session('error'))
                <div class="p-4 mb-6 text-sm text-red-800 bg-red-50 border border-red-200 rounded-xl flex items-center shadow-sm" role="alert">
                    <svg class="w-5 h-5 mr-2 text-red-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path></svg>
                    <span>{!! session('error') !!}</span>
                </div>
            @endif

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                @forelse($templates as $template)
                    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm hover:shadow-xl transition-all duration-300 flex flex-col overflow-hidden group">
                        
                        <!-- Card Header -->
                        <div class="p-6 pb-4 border-b border-gray-50 flex justify-between items-start">
                            <div class="flex-1 min-w-0 pr-4">
                                <h3 class="text-lg font-bold text-gray-900 truncate group-hover:text-indigo-600 transition-colors" title="{{ $template->name }}">
                                    {{ $template->name }}
                                </h3>
                                <p class="text-xs text-gray-400 mt-1 flex items-center">
                                    <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                                    {{ $template->creator->name ?? 'Bilinmiyor' }}
                                </p>
                            </div>
                            <div>
                                @if($template->is_active)
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-600 border border-emerald-100 shadow-sm">
                                        <span class="w-1.5 h-1.5 bg-emerald-500 rounded-full mr-1.5"></span> Aktif
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[10px] font-bold bg-gray-50 text-gray-600 border border-gray-200 shadow-sm">
                                        <span class="w-1.5 h-1.5 bg-gray-400 rounded-full mr-1.5"></span> Pasif
                                    </span>
                                @endif
                            </div>
                        </div>

                        <!-- Card Body -->
                        <div class="p-6 flex-grow">
                            <p class="text-sm text-gray-600 line-clamp-2 min-h-[2.5rem]">
                                {{ $template->description ?: 'Bu şablon için henüz bir açıklama girilmemiş.' }}
                            </p>
                            
                            <div class="mt-4 pt-4 border-t border-dashed border-gray-100 flex justify-between items-center text-xs">
                                <div class="flex items-center text-gray-500">
                                    <svg class="w-4 h-4 mr-1.5 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path></svg>
                                    Alt Toplam: <span class="ml-1 font-semibold text-gray-700">{{ $template->show_row_totals ? 'Açık' : 'Kapalı' }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Card Footer / Actions -->
                        <div class="px-4 py-3 bg-gray-50/50 border-t border-gray-100 flex justify-between items-center flex-wrap gap-2">
                            <div class="flex items-center gap-1">
                                <a href="{{ route('form_fields.edit', $template) }}" class="p-2 text-indigo-600 hover:text-white hover:bg-indigo-600 rounded-lg transition-colors tooltip" title="Alanları Düzenle">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                                </a>
                                <a href="{{ route('form-schedules.index', $template) }}" class="p-2 text-amber-600 hover:text-white hover:bg-amber-500 rounded-lg transition-colors tooltip" title="Erişim Takvimi">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                                </a>
                                <a href="{{ route('form-templates.show', $template) }}" class="p-2 text-teal-600 hover:text-white hover:bg-teal-500 rounded-lg transition-colors tooltip" title="Önizle / Görüntüle">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                                </a>
                            </div>

                            @if(app(\App\Services\PermissionS::class)->hasPermission(Auth::user(), 'form_templates.delete'))
                            <div x-data="{ open: false }">
                                <button @click="open = true" class="p-2 text-red-500 hover:text-white hover:bg-red-500 rounded-lg transition-colors tooltip" title="Şablonu Sil">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                </button>

                                <!-- Modal -->
                                <div x-show="open" style="display: none;" class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
                                    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                                        <div x-show="open" @click="open = false" x-transition.opacity class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true"></div>
                                        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
                                        <div x-show="open" x-transition class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                                            <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                                                <div class="sm:flex sm:items-start">
                                                    <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-red-100 sm:mx-0 sm:h-10 sm:w-10">
                                                        <svg class="h-6 w-6 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                                        </svg>
                                                    </div>
                                                    <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                                                        <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title">Şablon Silinecek</h3>
                                                        <div class="mt-2">
                                                            <p class="text-sm text-gray-500"><strong>{{ $template->name }}</strong> isimli şablonu silmek istediğinize emin misiniz? Bu işlem geri alınamaz.</p>
                                                            <p class="text-xs text-red-500 mt-2 font-semibold">Not: Eğer bu şablona ait form verileri varsa silme işlemi gerçekleşmeyecektir.</p>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                                                <form action="{{ route('form-templates.destroy', $template) }}" method="POST">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-red-600 text-base font-medium text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 sm:ml-3 sm:w-auto sm:text-sm">
                                                        Evet, Sil
                                                    </button>
                                                </form>
                                                <button type="button" @click="open = false" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                                                    İptal
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="col-span-1 md:col-span-2 lg:col-span-3">
                        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-12 text-center">
                            <div class="mx-auto h-24 w-24 bg-gray-50 rounded-full flex items-center justify-center mb-4">
                                <svg class="w-12 h-12 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                            </div>
                            <h3 class="text-lg font-bold text-gray-900 mb-2">Henüz Şablon Yok</h3>
                            <p class="text-gray-500 mb-6">Sistemde oluşturulmuş herhangi bir form şablonu bulunmuyor.</p>
                            <a href="{{ route('form-templates.create') }}" class="inline-flex items-center px-6 py-3 border border-transparent text-sm font-medium rounded-xl shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-colors">
                                İlk Şablonu Oluştur
                            </a>
                        </div>
                    </div>
                @endforelse
            </div>
        </div>
    </div>
</x-app-layout>

