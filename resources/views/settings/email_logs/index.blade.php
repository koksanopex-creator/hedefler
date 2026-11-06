@section('title', 'E-Posta Gönderim Geçmişi')
<x-app-layout>
    <x-slot name="header">
        <h2 class="font-extrabold text-2xl text-gray-900 tracking-tight">
            {{ __('E-Posta Gönderim Geçmişi') }}
        </h2>
        <p class="text-sm text-gray-500 mt-1">Sistem tarafından kullanıcılara gönderilen e-postaların kayıtları.</p>
    </x-slot>

    <div class="py-10">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow-sm sm:rounded-2xl border border-gray-100 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Tarih</th>
                                <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Alıcı (Kime)</th>
                                <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Konu</th>
                                <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">İçerik Görüntüle</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-100">
                            @forelse($logs as $log)
                                <tr class="hover:bg-gray-50 transition-colors">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 font-mono">
                                        {{ $log->sent_at->format('d.m.Y H:i:s') }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex flex-col">
                                            @if($log->user)
                                                <span class="text-sm font-semibold text-gray-900">{{ $log->user->name }}</span>
                                            @endif
                                            <span class="text-xs text-gray-500">{{ $log->to_email }}</span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-900 font-medium">
                                        {{ $log->subject }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <button type="button" x-data @click="$dispatch('open-body-modal', {{ json_encode(['subject' => $log->subject, 'body' => $log->body, 'to' => $log->to_email, 'date' => $log->sent_at->format('d.m.Y H:i:s')]) }})" class="text-indigo-600 hover:text-indigo-900 bg-indigo-50 px-3 py-1.5 rounded-lg flex items-center">
                                            <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                                            Göster
                                        </button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-6 py-10 text-center text-gray-500">
                                        Henüz kaydedilmiş bir e-posta gönderimi bulunmuyor.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                
                @if($logs->hasPages())
                    <div class="bg-white px-4 py-3 border-t border-gray-200 sm:px-6">
                        {{ $logs->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- E-Posta İçeriği Modalı -->
    <div x-data="{ 
            open: false, 
            log: null
         }" 
         @open-body-modal.window="
            log = $event.detail; 
            open = true;
         "
         x-show="open" 
         style="display: none;" 
         class="fixed inset-0 z-50 overflow-y-auto" 
         aria-labelledby="modal-title" role="dialog" aria-modal="true">
        
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div x-show="open" @click="open = false" x-transition.opacity class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
            <div x-show="open" x-transition class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-3xl sm:w-full">
                
                <div class="bg-gray-50 px-6 py-4 border-b border-gray-100 flex justify-between items-start">
                    <div>
                        <h3 class="text-lg leading-6 font-bold text-gray-900" id="modal-title" x-text="log?.subject"></h3>
                        <p class="text-xs text-gray-500 mt-1">
                            <span class="font-bold">Kime:</span> <span x-text="log?.to"></span> &bull; 
                            <span class="font-bold">Tarih:</span> <span x-text="log?.date"></span>
                        </p>
                    </div>
                    <button type="button" @click="open = false" class="text-gray-400 hover:text-gray-500 bg-white rounded-full p-1 border border-gray-200">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
                <div class="px-6 py-6 bg-white min-h-[200px]">
                    <div class="prose prose-sm max-w-none text-gray-800" x-html="log?.body"></div>
                </div>
                <div class="bg-gray-50 px-4 py-3 sm:px-6 flex justify-end border-t border-gray-100">
                    <button type="button" @click="open = false" class="w-full inline-flex justify-center rounded-xl border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none sm:w-auto sm:text-sm">
                        Kapat
                    </button>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
