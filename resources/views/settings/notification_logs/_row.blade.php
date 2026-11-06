<tr class="hover:bg-slate-50/80 transition-colors group">
    <!-- 1. Kanal Türü -->
    <td class="px-6 py-4 whitespace-nowrap">
        @if($log->channel === 'mail')
            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-bold bg-sky-100 text-sky-800 border border-sky-200 shadow-2xs">
                <svg class="w-3.5 h-3.5 text-sky-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
                E-Posta
            </span>
        @else
            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-bold bg-amber-100 text-amber-800 border border-amber-200 shadow-2xs">
                <svg class="w-3.5 h-3.5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path></svg>
                Zil (In-App)
            </span>
        @endif
    </td>

    <!-- 2. Kategori / İşlem -->
    @if(isset($showCategory) && $showCategory)
        <td class="px-6 py-4 whitespace-nowrap">
            <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-bold bg-purple-50 text-purple-700 border border-purple-200/60 shadow-2xs">
                {{ $log->event_name ?: $log->event_slug }}
            </span>
        </td>
    @endif

    <!-- 3. Alıcı (Kime Gitti) -->
    <td class="px-6 py-4 whitespace-nowrap">
        <div class="flex items-center gap-2.5">
            <div class="w-8 h-8 rounded-full bg-gradient-to-br from-indigo-100 to-purple-100 text-indigo-700 font-bold text-xs flex items-center justify-center border border-indigo-200/50 shadow-2xs">
                {{ mb_substr($log->recipient_name ?: 'A', 0, 1) }}
            </div>
            <div>
                <div class="text-xs font-bold text-gray-900">{{ $log->recipient_name ?: 'İsimsiz' }}</div>
                <div class="text-[11px] text-gray-400 font-medium">{{ $log->recipient_email ?: '-' }}</div>
            </div>
        </div>
    </td>

    <!-- 4. Rol -->
    <td class="px-6 py-4 whitespace-nowrap">
        <span class="inline-block px-2.5 py-1 rounded-md text-xs font-semibold bg-gray-100 text-gray-700 border border-gray-200/80">
            {{ $log->recipient_role ?: 'Kullanıcı' }}
        </span>
    </td>

    <!-- 5. Başlık / Konu -->
    <td class="px-6 py-4 text-xs font-medium text-gray-800 max-w-xs truncate" title="{{ $log->subject }}">
        <div class="truncate font-semibold text-gray-900">{{ $log->subject }}</div>
        @if($log->action_url)
            <span class="inline-flex items-center gap-1 text-[10px] text-indigo-600 font-normal mt-0.5">
                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path></svg>
                Aksiyon Linki Mevcut
            </span>
        @endif
    </td>

    <!-- 6. Gönderim Zamanı -->
    <td class="px-6 py-4 whitespace-nowrap text-xs text-gray-500 font-mono">
        <div>{{ $log->sent_at ? $log->sent_at->format('d.m.Y H:i:s') : '-' }}</div>
        <span class="text-[10px] text-gray-400 font-sans block">{{ $log->sent_at ? $log->sent_at->diffForHumans() : '' }}</span>
    </td>

    <!-- 7. İşlemler -->
    <td class="px-6 py-4 whitespace-nowrap text-right text-xs font-medium">
        @php
            $logPayload = [
                'id' => $log->id,
                'channel' => $log->channel,
                'event_name' => $log->event_name ?: $log->event_slug,
                'recipient_name' => $log->recipient_name,
                'recipient_email' => $log->recipient_email,
                'recipient_role' => $log->recipient_role,
                'subject' => $log->subject,
                'body' => $log->body,
                'action_url' => $log->action_url,
                'sent_at_formatted' => $log->sent_at ? $log->sent_at->format('d.m.Y H:i:s') : '',
            ];
        @endphp
        <button type="button" 
                @click="showDetails({{ json_encode($logPayload) }})"
                class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-indigo-50 text-indigo-700 hover:bg-indigo-600 hover:text-white transition shadow-2xs font-bold text-xs border border-indigo-100">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
            İçeriği Gör
        </button>
    </td>
</tr>

