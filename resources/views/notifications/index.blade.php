@section('title', 'Tüm Bildirimler')
<x-app-layout>
    <div class="min-h-screen bg-gray-50/50 py-4 sm:py-6">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
            
            <!-- Başlık ve Kontroller -->
            <div class="flex flex-col sm:flex-row justify-between items-center bg-white p-4 sm:px-6 rounded-2xl shadow-sm border border-gray-100 mb-6">
                <div class="flex items-center gap-4 mb-3 sm:mb-0">
                    <div class="w-10 h-10 bg-indigo-50 text-indigo-600 rounded-xl flex items-center justify-center transform -rotate-6">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path></svg>
                    </div>
                    <div>
                        <h2 class="text-xl font-extrabold text-gray-900 tracking-tight">Bildirim Merkezi</h2>
                        <p class="text-xs text-gray-500 font-medium mt-0.5">Sistemdeki tüm güncellemeler ve hareketler.</p>
                    </div>
                </div>
                
                @if(Auth::user()->unreadNotifications->count() > 0)
                    <form action="{{ route('notifications.mark-all-read') }}" method="POST">
                        @csrf
                        <button type="submit" class="inline-flex items-center px-4 py-2 text-sm font-bold rounded-lg text-indigo-700 bg-indigo-50 hover:bg-indigo-600 hover:text-white focus:outline-none transition-all duration-300 shadow-sm">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                            Tümünü Okundu İşaretle
                        </button>
                    </form>
                @endif
            </div>

            <!-- Bildirim Listesi -->
            <div class="space-y-3">
                @forelse($notifications as $notification)
                    <div class="group relative bg-white rounded-2xl p-4 sm:p-5 transition-all duration-300 hover:shadow-lg hover:shadow-indigo-100/40 hover:-translate-y-0.5 border {{ is_null($notification->read_at) ? 'border-indigo-100 shadow-sm shadow-indigo-50/50' : 'border-gray-100 shadow-sm' }}">
                        
                        <!-- Sol Mavi Vurgu (Okunmamış) -->
                        @if(is_null($notification->read_at))
                            <div class="absolute left-0 top-1/2 -translate-y-1/2 w-1 h-12 bg-gradient-to-b from-indigo-500 to-purple-500 rounded-r-full"></div>
                        @endif

                        <div class="flex flex-col sm:flex-row gap-4">
                            
                            <!-- İkon -->
                            <div class="flex-shrink-0 hidden sm:block">
                                @if(is_null($notification->read_at))
                                    <div class="w-12 h-12 rounded-full bg-indigo-50 text-indigo-600 flex items-center justify-center border-[3px] border-white shadow-sm transform transition-transform group-hover:scale-110 group-hover:rotate-6 duration-300">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path></svg>
                                    </div>
                                @else
                                    <div class="w-12 h-12 rounded-full bg-gray-50 text-gray-400 flex items-center justify-center border-[3px] border-white shadow-sm transition-colors group-hover:bg-gray-100 group-hover:text-gray-600 duration-300">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                    </div>
                                @endif
                            </div>
                            
                            <!-- İçerik -->
                            <div class="flex-1 min-w-0">
                                <!-- Üst Bilgi: Başlık & Tarih -->
                                <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-2 mb-2">
                                    <h4 class="text-base font-bold text-gray-900 group-hover:text-indigo-600 transition-colors">
                                        {{ $notification->data['subject'] ?? 'Sistem Bildirimi' }}
                                    </h4>
                                    <div class="flex items-center text-[11px] font-bold text-gray-400">
                                        <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                        {{ $notification->created_at->diffForHumans() }}
                                    </div>
                                </div>
                                
                                <!-- Mesaj Gövdesi -->
                                <div class="text-sm text-gray-600 leading-relaxed bg-gray-50/60 p-3 rounded-xl border border-gray-100/50 prose prose-sm max-w-none prose-p:m-0 prose-a:text-indigo-600 hover:prose-a:text-indigo-800 font-medium">
                                    {!! $notification->data['body'] ?? '' !!}
                                </div>
                                
                                <!-- Aksiyon Alanı -->
                                @if((isset($notification->data['actionUrl']) && !str_contains($notification->data['body'] ?? '', '<a href')) || is_null($notification->read_at))
                                <div class="mt-3 flex flex-wrap items-center gap-3">
                                    @if(isset($notification->data['actionUrl']) && !str_contains($notification->data['body'] ?? '', '<a href'))
                                        @php
                                            $rawUrl = $notification->data['actionUrl'];
                                            $parsedUrl = parse_url($rawUrl);
                                            if (isset($parsedUrl['path'])) {
                                                $pathWithQuery = $parsedUrl['path'] . (isset($parsedUrl['query']) ? '?' . $parsedUrl['query'] : '');
                                                $safeUrl = url($pathWithQuery);
                                            } else {
                                                $safeUrl = $rawUrl;
                                            }
                                        @endphp
                                        <a href="{{ $safeUrl }}" class="inline-flex items-center px-4 py-1.5 text-xs font-bold rounded-lg text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none transition-all duration-300 shadow-sm">
                                            İlgili Kayda Git
                                            <svg class="w-3.5 h-3.5 ml-1.5 transform transition-transform group-hover:translate-x-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
                                        </a>
                                    @endif
                                    
                                    @if(is_null($notification->read_at))
                                        <form action="{{ route('notifications.mark-read', $notification->id) }}" method="POST" class="inline">
                                            @csrf
                                            <button type="submit" class="inline-flex items-center px-4 py-1.5 text-xs font-bold rounded-lg text-gray-500 bg-white hover:bg-gray-50 hover:text-gray-900 border border-gray-200 transition-colors">
                                                Okundu İşaretle
                                            </button>
                                        </form>
                                    @endif
                                </div>
                                @endif
                            </div>
                        </div>
                    </div>
                @empty
                    <!-- Boş Durum -->
                    <div class="bg-white rounded-2xl p-12 text-center border border-gray-100 shadow-sm flex flex-col items-center justify-center">
                        <div class="w-20 h-20 bg-gray-50 rounded-full flex items-center justify-center mb-4">
                            <svg class="w-10 h-10 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path></svg>
                        </div>
                        <h3 class="text-xl font-extrabold text-gray-900 tracking-tight">Geçmiş Tertemiz!</h3>
                        <p class="mt-2 text-sm font-medium text-gray-500 max-w-sm">Şu anda gösterilecek herhangi bir bildiriminiz bulunmuyor.</p>
                    </div>
                @endforelse
            </div>
            
            <!-- Sayfalama -->
            @if($notifications->hasPages())
                <div class="mt-8 flex justify-center">
                    <div class="bg-white rounded-full px-6 py-2 shadow-sm border border-gray-100">
                        {{ $notifications->links() }}
                    </div>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
