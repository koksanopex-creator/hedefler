@section('title', 'Bildirim Matrisi')
<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <h2 class="font-extrabold text-2xl text-gray-900 tracking-tight">
                    {{ __('Bildirim Matrisi & Şablon Ayarları') }}
                </h2>
                <p class="text-sm text-gray-500 mt-1">Sistem olaylarında kimlere hangi kanaldan (Zil/E-Posta) bildirim gideceğini ve zengin bildirim şablonlarını yönetin.</p>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('settings.notification_logs.index') }}" class="inline-flex items-center px-3.5 py-1.5 rounded-xl text-xs font-bold text-indigo-700 bg-white border border-indigo-200 hover:bg-indigo-50 transition shadow-2xs">
                    <svg class="w-3.5 h-3.5 mr-1.5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                    Gönderim Loglarını İncele &rarr;
                </a>
                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-indigo-50 text-indigo-700 border border-indigo-200">
                    <svg class="w-3.5 h-3.5 mr-1 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path></svg>
                    Zil Bildirimi
                </span>
                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-teal-50 text-teal-700 border border-teal-200">
                    <svg class="w-3.5 h-3.5 mr-1 text-teal-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
                    E-Posta Bildirimi
                </span>
            </div>
        </div>
    </x-slot>

    <!-- Quill.js Zengin Metin Editörü Varlıkları -->
    <link href="https://cdn.jsdelivr.net/npm/quill@2.0.2/dist/quill.snow.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/quill@2.0.2/dist/quill.js"></script>

    <div class="py-10">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            
            @if(session('success'))
                <div class="p-4 mb-6 text-sm text-teal-800 bg-teal-50 border border-teal-200 rounded-xl flex items-center shadow-sm" role="alert">
                    <svg class="w-5 h-5 mr-2 text-teal-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg>
                    {{ session('success') }}
                </div>
            @endif

            <form action="{{ route('settings.notifications.matrix.update') }}" method="POST">
                @csrf

                <!-- UYGULAMA BAŞVURU BİLDİRİMLERİ (GENEL AÇ/KAPA KARTI) -->
                @if(isset($applicationEvents) && $applicationEvents->isNotEmpty())
                <div class="mb-8 bg-white/90 backdrop-blur-xl rounded-3xl border border-indigo-100 shadow-[0_8px_30px_rgb(0,0,0,0.04)] overflow-hidden">
                    <div class="px-6 py-5 bg-gradient-to-r from-indigo-50/80 via-purple-50/40 to-white border-b border-indigo-100/60 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                        <div class="flex items-center gap-3.5">
                            <div class="w-11 h-11 rounded-2xl bg-gradient-to-br from-indigo-600 to-purple-600 flex items-center justify-center text-white shadow-md shadow-indigo-500/20">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
                            </div>
                            <div>
                                <h3 class="text-base font-extrabold text-gray-900 tracking-tight flex items-center gap-2">
                                    Uygulama Başvuru Bildirimleri
                                    <span class="text-[11px] font-semibold text-indigo-700 bg-indigo-100/80 px-2.5 py-0.5 rounded-full">Kullanıcıya Özel</span>
                                </h3>
                                <p class="text-xs text-gray-500 mt-0.5">Merkezi Portaldan yapılan uygulama başvuruları onaylandığında veya reddedildiğinde başvuru sahibine giden bilgilendirme e-postalarını buradan açıp kapatabilir, e-posta şablonunu düzenleyebilirsiniz.</p>
                            </div>
                        </div>
                        <span class="text-xs font-bold text-indigo-700 bg-white/90 border border-indigo-200/60 px-3.5 py-1.5 rounded-xl shadow-2xs shrink-0">
                            Role / Gruba Bağlı Değildir (Genel Alıcı)
                        </span>
                    </div>

                    <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-5">
                        @foreach($applicationEvents as $appEvent)
                            @php
                                $isEmailActive = optional($appEvent->matrix->where('target_type', 'applicant')->first())->send_email;
                            @endphp
                            <div class="p-5 rounded-2xl border {{ $appEvent->slug === 'application.approved' ? 'bg-emerald-50/30 border-emerald-100' : 'bg-rose-50/30 border-rose-100' }} flex flex-col justify-between gap-4">
                                <div class="flex items-start justify-between gap-3">
                                    <div class="flex items-start gap-3">
                                        <div class="w-9 h-9 rounded-xl flex items-center justify-center shrink-0 {{ $appEvent->slug === 'application.approved' ? 'bg-emerald-100 text-emerald-700' : 'bg-rose-100 text-rose-700' }}">
                                            @if($appEvent->slug === 'application.approved')
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                            @else
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                                            @endif
                                        </div>
                                        <div>
                                            <h4 class="text-sm font-extrabold text-gray-900">{{ $appEvent->name }}</h4>
                                            <p class="text-xs text-gray-500 mt-0.5">
                                                @if($appEvent->slug === 'application.approved')
                                                    Başvuru onaylandığında ve rol tanımlandığında kullanıcıya e-posta gider.
                                                @else
                                                    Başvuru gerekçeli veya kesin reddedildiğinde kullanıcıya e-posta gider.
                                                @endif
                                            </p>
                                        </div>
                                    </div>

                                    <!-- Şablon Düzenleme Butonu -->
                                    <button type="button" 
                                            x-data 
                                            @click="$dispatch('open-template-modal', {{ json_encode([
                                                'id' => $appEvent->id, 
                                                'name' => $appEvent->name, 
                                                'slug' => $appEvent->slug,
                                                'subject' => $appEvent->template->subject ?? '', 
                                                'body' => $appEvent->template->body ?? '', 
                                                'in_app_subject' => $appEvent->template->in_app_subject ?? '',
                                                'in_app_body' => $appEvent->template->in_app_body ?? '',
                                                'additional_emails' => $appEvent->template->additional_emails ?? '',
                                                'role_templates' => $appEvent->template->role_templates ?? (object)[]
                                            ]) }})" 
                                            class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-bold text-slate-700 bg-white hover:bg-slate-50 rounded-xl border border-slate-200 transition shadow-2xs whitespace-nowrap shrink-0">
                                        <svg class="w-3.5 h-3.5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                                        <span>Şablonu Düzenle</span>
                                    </button>
                                </div>

                                <!-- Aç/Kapa Anahtarı -->
                                <div class="pt-3 border-t {{ $appEvent->slug === 'application.approved' ? 'border-emerald-100/70' : 'border-rose-100/70' }} flex items-center justify-between"
                                     x-data="{ active: {{ $isEmailActive ? 'true' : 'false' }} }">
                                    <span class="text-xs font-semibold text-gray-700 flex items-center gap-1.5">
                                        <svg class="w-4 h-4 text-teal-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
                                        E-Posta Bildirimi Durumu:
                                    </span>
                                    <label class="relative inline-flex items-center cursor-pointer">
                                        <input type="checkbox" name="matrix[{{ $appEvent->id }}][applicant][email]" value="1" class="sr-only peer" x-model="active">
                                        <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-emerald-600"></div>
                                        <span class="ml-2.5 text-xs font-bold" :class="active ? 'text-emerald-700' : 'text-gray-400'" x-text="active ? 'Açık (Aktif)' : 'Kapalı (Pasif)'"></span>
                                    </label>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
                @endif

                <div class="bg-white overflow-hidden shadow-sm sm:rounded-2xl border border-gray-100">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 table-fixed">
                            <thead>
                                <tr class="bg-gray-50">
                                    <th class="w-1/4 px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider sticky left-0 bg-gray-50 z-10 border-r border-gray-200">
                                        Olaylar (Tetikleyiciler) & Şablon
                                    </th>
                                    
                                    <!-- Dinamik Özel Hedefler -->
                                    @foreach($specialTargets as $key => $label)
                                        <th class="px-4 py-4 text-center text-xs font-bold text-indigo-600 uppercase tracking-wider bg-indigo-50/30 border-r border-gray-100">
                                            <div class="flex flex-col items-center justify-center">
                                                <svg class="w-5 h-5 mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"></path></svg>
                                                {{ $label }}
                                            </div>
                                        </th>
                                    @endforeach

                                    <!-- Sistemdeki N Adet Roller (Dinamik) -->
                                    @foreach($roles as $role)
                                        <th class="px-4 py-4 text-center text-xs font-bold text-gray-500 uppercase tracking-wider border-r border-gray-100">
                                            {{ $role->name }}
                                        </th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-100">
                                @foreach($events as $event)
                                    <tr class="hover:bg-gray-50 transition-colors">
                                        <td class="px-6 py-4 text-sm font-semibold text-gray-900 sticky left-0 bg-white group-hover:bg-gray-50 border-r border-gray-200 z-10">
                                            <div class="flex items-center justify-between gap-2">
                                                <div>
                                                    <span class="font-bold text-gray-900 block">{{ $event->name }}</span>
                                                    <span class="text-[10px] text-gray-400 font-mono block mt-0.5">{{ $event->slug }}</span>
                                                </div>
                                                
                                                <!-- Şablon Düzenleme Butonu (Modal Tetikleyici) -->
                                                <button type="button" 
                                                        x-data 
                                                        @click="$dispatch('open-template-modal', {{ json_encode([
                                                            'id' => $event->id, 
                                                            'name' => $event->name, 
                                                            'slug' => $event->slug,
                                                            'subject' => $event->template->subject ?? '', 
                                                            'body' => $event->template->body ?? '', 
                                                            'in_app_subject' => $event->template->in_app_subject ?? '',
                                                            'in_app_body' => $event->template->in_app_body ?? '',
                                                            'additional_emails' => $event->template->additional_emails ?? '',
                                                            'role_templates' => $event->template->role_templates ?? (object)[]
                                                        ]) }})" 
                                                        class="inline-flex items-center gap-1.5 px-2.5 py-1.5 text-xs font-semibold text-indigo-700 bg-indigo-50 hover:bg-indigo-100 rounded-lg border border-indigo-200/80 transition-all shadow-xs hover:shadow whitespace-nowrap" 
                                                        title="Zil ve Mail Şablonlarını Düzenle">
                                                    <svg class="w-3.5 h-3.5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                                                    <span>Şablonu Düzenle</span>
                                                </button>
                                            </div>
                                        </td>
                                        
                                        @php
                                            $matrixLookup = $event->matrix->keyBy('target_type');
                                        @endphp

                                        @foreach($specialTargets as $key => $label)
                                            <td class="px-4 py-4 text-center border-r border-gray-100 bg-indigo-50/10">
                                                <div class="flex flex-col items-center justify-center gap-2">
                                                    <label class="inline-flex items-center cursor-pointer tooltip" title="Zil Bildirimi (Sistem İçi)">
                                                        <input type="checkbox" name="matrix[{{ $event->id }}][{{ $key }}][in_app]" value="1" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50" {{ isset($matrixLookup[$key]) && $matrixLookup[$key]->send_in_app ? 'checked' : '' }}>
                                                        <svg class="w-4 h-4 ml-1 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path></svg>
                                                    </label>
                                                    <label class="inline-flex items-center cursor-pointer tooltip" title="E-Posta Bildirimi">
                                                        <input type="checkbox" name="matrix[{{ $event->id }}][{{ $key }}][email]" value="1" class="rounded border-gray-300 text-teal-600 shadow-sm focus:border-teal-300 focus:ring focus:ring-teal-200 focus:ring-opacity-50" {{ isset($matrixLookup[$key]) && $matrixLookup[$key]->send_email ? 'checked' : '' }}>
                                                        <svg class="w-4 h-4 ml-1 text-teal-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
                                                    </label>
                                                </div>
                                            </td>
                                        @endforeach

                                        @foreach($roles as $role)
                                            @php $targetKey = 'role:' . $role->slug; @endphp
                                            <td class="px-4 py-4 text-center border-r border-gray-100">
                                                <div class="flex flex-col items-center justify-center gap-2">
                                                    <label class="inline-flex items-center cursor-pointer tooltip" title="Zil Bildirimi (Sistem İçi)">
                                                        <input type="checkbox" name="matrix[{{ $event->id }}][{{ $targetKey }}][in_app]" value="1" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50" {{ isset($matrixLookup[$targetKey]) && $matrixLookup[$targetKey]->send_in_app ? 'checked' : '' }}>
                                                        <svg class="w-4 h-4 ml-1 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path></svg>
                                                    </label>
                                                    <label class="inline-flex items-center cursor-pointer tooltip" title="E-Posta Bildirimi">
                                                        <input type="checkbox" name="matrix[{{ $event->id }}][{{ $targetKey }}][email]" value="1" class="rounded border-gray-300 text-teal-600 shadow-sm focus:border-teal-300 focus:ring focus:ring-teal-200 focus:ring-opacity-50" {{ isset($matrixLookup[$targetKey]) && $matrixLookup[$targetKey]->send_email ? 'checked' : '' }}>
                                                        <svg class="w-4 h-4 ml-1 text-teal-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
                                                    </label>
                                                </div>
                                            </td>
                                        @endforeach
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="px-6 py-4 bg-gray-50 border-t border-gray-100 flex justify-end">
                        <button type="submit" class="inline-flex items-center px-6 py-3 border border-transparent text-sm font-medium rounded-xl shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-colors">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                            Matrisi Kaydet
                        </button>
                    </div>
                </div>
            </form>

        </div>
    </div>

    <!-- Gelişmiş Şablon Düzenleme Modalı (Quill Zengin Metin, Değişken Havuzu ve N Adet Rol Desteği) -->
    <div x-data="notificationTemplateModal()" 
         @open-template-modal.window="openModal($event.detail)"
         x-show="open" 
         style="display: none;" 
         class="fixed inset-0 z-50 overflow-y-auto" 
         aria-labelledby="modal-title" role="dialog" aria-modal="true">
        
        <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div x-show="open" @click="closeModal()" x-transition.opacity class="fixed inset-0 bg-gray-500/75 backdrop-blur-xs transition-opacity" aria-hidden="true"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
            
            <div x-show="open" x-transition class="inline-block align-bottom bg-white rounded-3xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-5xl sm:w-full border border-gray-100">
                
                <form :action="actionUrl" method="POST" @submit="prepareSubmit($event)">
                    @csrf

                    <!-- Modal Üst Başlık -->
                    <div class="bg-gradient-to-r from-indigo-900 via-indigo-800 to-slate-900 px-6 py-5 sm:px-8 border-b border-indigo-700/50 text-white">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-2xl bg-white/10 flex items-center justify-center text-white border border-white/20 shadow-inner">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path></svg>
                                </div>
                                <div>
                                    <h3 class="text-lg font-extrabold tracking-tight" id="modal-title">
                                        Bildirim Şablonu: <span x-text="event?.name" class="text-indigo-200"></span>
                                    </h3>
                                    <p class="text-xs text-indigo-200/80 mt-0.5 font-mono" x-text="'Tetikleyici: ' + (event?.slug || '')"></p>
                                </div>
                            </div>
                            <button type="button" @click="closeModal()" class="text-white/60 hover:text-white p-2 rounded-xl hover:bg-white/10 transition-colors">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                            </button>
                        </div>
                    </div>

                    <!-- Dinamik Değişken Havuzu (Variable Pool Toolbar) -->
                    <div class="bg-indigo-50/70 border-b border-indigo-100 px-6 py-3 sm:px-8">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-xs font-bold text-indigo-900 flex items-center gap-1.5">
                                <svg class="w-4 h-4 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                                <span>Dinamik Değişken Havuzu:</span>
                                <span class="text-[11px] font-normal text-indigo-600">(Metne eklemek için değişkene tıklayın)</span>
                            </span>
                            <span class="text-[10px] text-gray-500 font-medium">Hem Türkçe hem İngilizce etiketler desteklenir</span>
                        </div>
                        <div class="flex flex-wrap gap-1.5">
                            <template x-for="v in variablePool" :key="v.key">
                                <button type="button" 
                                        @click="insertVariable(v.key)" 
                                        :title="v.desc"
                                        class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-mono font-bold bg-white text-indigo-700 hover:bg-indigo-600 hover:text-white border border-indigo-200 hover:border-indigo-600 shadow-2xs transition-all transform hover:-translate-y-0.5">
                                    <span x-text="v.key"></span>
                                    <span class="text-[10px] opacity-70 font-sans font-normal" x-text="'(' + v.label + ')'"></span>
                                </button>
                            </template>
                        </div>
                    </div>

                    <!-- Rol / Hedef Sekmeleri (N Adet Rol İçin Dinamik) -->
                    <div class="px-6 sm:px-8 pt-4 pb-0 bg-white border-b border-gray-100">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-bold uppercase tracking-wider text-gray-400">Şablon Hedefi:</span>
                            <span class="text-xs text-gray-500">Her rol için ayrı zil ve mail metni tanımlayabilirsiniz</span>
                        </div>
                        <div class="flex gap-2 overflow-x-auto py-2.5 scrollbar-thin">
                            <!-- Genel Şablon Sekmesi -->
                            <button type="button" 
                                    @click="switchTab('general')"
                                    :class="activeTab === 'general' ? 'bg-indigo-600 text-white shadow-sm' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'"
                                    class="px-4 py-2 rounded-xl text-xs font-bold transition-all whitespace-nowrap flex items-center gap-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
                                <span>Genel Şablon (Varsayılan)</span>
                            </button>

                            <!-- Özel Hedefler Sekmeleri -->
                            @foreach($specialTargets as $sKey => $sLabel)
                                <button type="button" 
                                        @click="switchTab('{{ $sKey }}')"
                                        :class="activeTab === '{{ $sKey }}' ? 'bg-indigo-600 text-white shadow-sm' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'"
                                        class="px-3.5 py-2 rounded-xl text-xs font-bold transition-all whitespace-nowrap flex items-center gap-1.5">
                                    <span class="w-2 h-2 rounded-full" :class="tabsData['{{ $sKey }}']?.is_custom ? 'bg-amber-400 ring-2 ring-amber-300' : 'bg-gray-300'"></span>
                                    <span>{{ $sLabel }}</span>
                                </button>
                            @endforeach

                            <!-- Sistemdeki N Adet Roller (Dinamik) -->
                            @foreach($roles as $role)
                                @php $rKey = 'role:' . $role->slug; @endphp
                                <button type="button" 
                                        @click="switchTab('{{ $rKey }}')"
                                        :class="activeTab === '{{ $rKey }}' ? 'bg-indigo-600 text-white shadow-sm' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'"
                                        class="px-3.5 py-2 rounded-xl text-xs font-bold transition-all whitespace-nowrap flex items-center gap-1.5">
                                    <span class="w-2 h-2 rounded-full" :class="tabsData['{{ $rKey }}']?.is_custom ? 'bg-amber-400 ring-2 ring-amber-300' : 'bg-gray-300'"></span>
                                    <span>{{ $role->name }}</span>
                                </button>
                            @endforeach
                        </div>
                    </div>

                    <!-- Modal Gövdesi (Form Alanları) -->
                    <div class="p-6 sm:p-8 space-y-6 max-h-[65vh] overflow-y-auto">

                        <!-- Rol Özel Ayarı Kontrolü (Genel Şablon Haricindekiler İçin) -->
                        <div x-show="activeTab !== 'general'" class="bg-amber-50/80 border border-amber-200 p-4 rounded-2xl flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded-xl bg-amber-100 text-amber-700 flex items-center justify-center font-bold">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                                </div>
                                <div>
                                    <h4 class="text-sm font-bold text-amber-950">Bu Hedefe / Role Özel Şablon Tanımla</h4>
                                    <p class="text-xs text-amber-800">İşaretlenmezse, bu hedefe bildirim giderken otomatik olarak <b>Genel Şablon</b> kullanılır.</p>
                                </div>
                            </div>
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" x-model="currentTabData.is_custom" class="sr-only peer">
                                <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-indigo-600"></div>
                            </label>
                        </div>

                        <!-- Şablon Editörleri Alanı -->
                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6" :class="activeTab !== 'general' && !currentTabData.is_custom ? 'opacity-40 pointer-events-none' : ''">
                            
                            <!-- SOL KOLON: E-POSTA ŞABLONU -->
                            <div class="bg-gray-50/60 p-5 rounded-2xl border border-gray-200/80 flex flex-col gap-4">
                                <div class="flex items-center justify-between pb-3 border-b border-gray-200">
                                    <div class="flex items-center gap-2">
                                        <div class="w-7 h-7 rounded-lg bg-teal-100 text-teal-700 flex items-center justify-center">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
                                        </div>
                                        <h4 class="text-sm font-extrabold text-gray-900">E-Posta Şablonu</h4>
                                    </div>
                                    <span class="text-[11px] font-semibold text-teal-700 bg-teal-50 px-2 py-0.5 rounded border border-teal-200">HTML Mail</span>
                                </div>

                                <div>
                                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">
                                        E-Posta Konusu <span class="text-red-500">*</span>
                                    </label>
                                    <input type="text" 
                                           x-model="currentTabData.subject" 
                                           @focus="lastFocused = 'subject'"
                                           placeholder="Örn: Sayın {kullanici_adi}, {form_adi} İletildi"
                                           class="w-full rounded-xl border-gray-300 shadow-xs focus:border-indigo-500 focus:ring-indigo-500 text-sm font-medium">
                                </div>

                                <div class="flex-1 flex flex-col">
                                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">
                                        E-Posta İçeriği (Zengin Metin Kutusu) <span class="text-red-500">*</span>
                                    </label>
                                    <div class="bg-white rounded-xl border border-gray-300 shadow-xs overflow-hidden flex-1 flex flex-col">
                                        <div id="mail-quill-editor" style="min-height: 220px;" class="text-sm"></div>
                                    </div>
                                    <p class="text-[11px] text-gray-500 mt-1">Kalın, italik, liste ve linkleri üst araç çubuğundan doğrudan biçimlendirebilirsiniz.</p>
                                </div>
                            </div>

                            <!-- SAĞ KOLON: ZİL (SİSTEM İÇİ / IN-APP) BİLDİRİM ŞABLONU -->
                            <div class="bg-gray-50/60 p-5 rounded-2xl border border-gray-200/80 flex flex-col gap-4">
                                <div class="flex items-center justify-between pb-3 border-b border-gray-200">
                                    <div class="flex items-center gap-2">
                                        <div class="w-7 h-7 rounded-lg bg-indigo-100 text-indigo-700 flex items-center justify-center">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path></svg>
                                        </div>
                                        <h4 class="text-sm font-extrabold text-gray-900">Zil (Sistem İçi) Bildirim Şablonu</h4>
                                    </div>
                                    <span class="text-[11px] font-semibold text-indigo-700 bg-indigo-50 px-2 py-0.5 rounded border border-indigo-200">Zil / Bildirim Kutusu</span>
                                </div>

                                <div>
                                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">
                                        Zil Bildirim Başlığı <span class="text-gray-400 font-normal">(Boşsa E-Posta Konusu Alınır)</span>
                                    </label>
                                    <input type="text" 
                                           x-model="currentTabData.in_app_subject" 
                                           @focus="lastFocused = 'in_app_subject'"
                                           placeholder="Örn: Yeni Form Bildirimi"
                                           class="w-full rounded-xl border-gray-300 shadow-xs focus:border-indigo-500 focus:ring-indigo-500 text-sm font-medium">
                                </div>

                                <div class="flex-1 flex flex-col">
                                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">
                                        Zil Bildirim Mesajı (Zengin Metin Kutusu) <span class="text-gray-400 font-normal">(Boşsa E-Posta İçeriği Alınır)</span>
                                    </label>
                                    <div class="bg-white rounded-xl border border-gray-300 shadow-xs overflow-hidden flex-1 flex flex-col">
                                        <div id="zil-quill-editor" style="min-height: 220px;" class="text-sm"></div>
                                    </div>
                                    <p class="text-[11px] text-gray-500 mt-1">Zil açılır menüsünde ve bildirimler sayfasında görüntülenecek zengin metin.</p>
                                </div>
                            </div>

                        </div>

                        <!-- Ekstra Harici E-Postalar (Sadece Genel Şablonda Görünür) -->
                        <div x-show="activeTab === 'general'" class="bg-blue-50/50 p-4 rounded-2xl border border-blue-200">
                            <label class="block text-xs font-bold text-blue-900 uppercase tracking-wider mb-1">
                                Harici Ekstra E-Postalar (Virgülle Ayırın)
                            </label>
                            <textarea x-model="tabsData['general'].additional_emails" 
                                      rows="2" 
                                      placeholder="ornek1@koksan.com, ornek2@koksan.com" 
                                      class="w-full rounded-xl border-blue-200 shadow-xs focus:border-blue-500 focus:ring-blue-500 font-mono text-xs p-2.5 bg-white"></textarea>
                            <p class="text-[11px] text-blue-700 mt-1">Bu bildirim tetiklendiğinde, yukarıdaki adreslere de rol veya kullanıcıdan bağımsız olarak otomatik e-posta gönderilir.</p>
                        </div>

                    </div>

                    <!-- Gizli Alanlar (Backend POST Verileri İçin) -->
                    <input type="hidden" name="subject" :value="tabsData['general'].subject">
                    <input type="hidden" name="body" :value="tabsData['general'].body">
                    <input type="hidden" name="in_app_subject" :value="tabsData['general'].in_app_subject">
                    <input type="hidden" name="in_app_body" :value="tabsData['general'].in_app_body">
                    <input type="hidden" name="additional_emails" :value="tabsData['general'].additional_emails">

                    <!-- N Adet Rolün Gizli JSON Verileri -->
                    @foreach($specialTargets as $sKey => $sLabel)
                        <input type="hidden" :name="'role_templates[{{ $sKey }}][is_custom]'" :value="tabsData['{{ $sKey }}']?.is_custom ? '1' : '0'">
                        <input type="hidden" :name="'role_templates[{{ $sKey }}][subject]'" :value="tabsData['{{ $sKey }}']?.subject || ''">
                        <input type="hidden" :name="'role_templates[{{ $sKey }}][body]'" :value="tabsData['{{ $sKey }}']?.body || ''">
                        <input type="hidden" :name="'role_templates[{{ $sKey }}][in_app_subject]'" :value="tabsData['{{ $sKey }}']?.in_app_subject || ''">
                        <input type="hidden" :name="'role_templates[{{ $sKey }}][in_app_body]'" :value="tabsData['{{ $sKey }}']?.in_app_body || ''">
                    @endforeach

                    @foreach($roles as $role)
                        @php $rKey = 'role:' . $role->slug; @endphp
                        <input type="hidden" :name="'role_templates[{{ $rKey }}][is_custom]'" :value="tabsData['{{ $rKey }}']?.is_custom ? '1' : '0'">
                        <input type="hidden" :name="'role_templates[{{ $rKey }}][subject]'" :value="tabsData['{{ $rKey }}']?.subject || ''">
                        <input type="hidden" :name="'role_templates[{{ $rKey }}][body]'" :value="tabsData['{{ $rKey }}']?.body || ''">
                        <input type="hidden" :name="'role_templates[{{ $rKey }}][in_app_subject]'" :value="tabsData['{{ $rKey }}']?.in_app_subject || ''">
                        <input type="hidden" :name="'role_templates[{{ $rKey }}][in_app_body]'" :value="tabsData['{{ $rKey }}']?.in_app_body || ''">
                    @endforeach

                    <!-- Modal Alt Aksiyon Butonları -->
                    <div class="bg-gray-50 px-6 py-4 sm:px-8 sm:flex sm:flex-row-reverse items-center justify-between border-t border-gray-100 gap-3">
                        <div class="flex items-center gap-2">
                            <button type="submit" 
                                    class="w-full sm:w-auto inline-flex items-center justify-center px-6 py-2.5 rounded-xl border border-transparent shadow-md text-sm font-bold text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-all transform hover:-translate-y-0.5">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                <span>Tüm Şablonları Kaydet</span>
                            </button>
                            <button type="button" 
                                    @click="closeModal()" 
                                    class="w-full sm:w-auto inline-flex items-center justify-center px-5 py-2.5 rounded-xl border border-gray-300 shadow-2xs text-sm font-semibold text-gray-700 bg-white hover:bg-gray-50 focus:outline-none transition-colors">
                                İptal
                            </button>
                        </div>
                        <div class="text-xs text-gray-500 hidden sm:block">
                            💡 İpucu: Tanımladığınız değişkenler gönderim esnasında dinamik olarak doldurulacaktır.
                        </div>
                    </div>

                </form>
            </div>
        </div>
    </div>

    <!-- AlpineJS & Quill Mantığı -->
    <script>
        function notificationTemplateModal() {
            return {
                open: false,
                event: null,
                actionUrl: '',
                activeTab: 'general',
                lastFocused: 'mail_quill',
                mailQuill: null,
                zilQuill: null,
                
                // Dinamik Değişken Havuzu Listesi
                variablePool: [
                    { key: '{kullanici_adi}', label: 'Kullanıcı / Personel', desc: 'İşlemi yapan veya başvuru yapan personelin adı' },
                    { key: '{alici_adi}', label: 'Alıcı Adı', desc: 'Bildirimi alan kullanıcının adı' },
                    { key: '{rol_adi}', label: 'Tanımlanan Rol', desc: 'Kullanıcıya tanımlanan sistem rolü' },
                    { key: '{red_turu}', label: 'Red Türü', desc: 'Kesin Red veya Düzeltilebilir Red' },
                    { key: '{red_nedeni}', label: 'Red Sebebi', desc: 'Reddedilme veya ret gerekçesi' },
                    { key: '{form_adi}', label: 'Şablon Adı', desc: 'İlgili form şablonunun başlığı' },
                    { key: '{onaylayan_kullanici}', label: 'Yetkili', desc: 'İşlemi onaylayan veya inceleyen yetkili' },
                    { key: '{tarih_saat}', label: 'Tarih Saat', desc: 'İşlem tarihi ve saati (örn: 02.11.2026 17:30)' },
                    { key: '{tarih}', label: 'Tarih', desc: 'İşlem tarihi' },
                    { key: '{islem_linki}', label: 'Giriş / İşlem Linki', desc: 'Doğrudan sisteme veya kayda gitme bağlantısı' },
                    { key: '{email}', label: 'E-Posta', desc: 'Kullanıcının e-posta adresi' },
                    { key: '{grup_adi}', label: 'Bölüm / Grup', desc: 'Kullanıcının departman/grup adı' },
                    { key: '{sirket_adi}', label: 'Firma', desc: 'Kurum adı (KÖKSAN)' }
                ],

                // Tüm sekmelerin verisi
                tabsData: {},

                get currentTabData() {
                    if (!this.tabsData[this.activeTab]) {
                        this.tabsData[this.activeTab] = {
                            is_custom: false,
                            subject: '',
                            body: '',
                            in_app_subject: '',
                            in_app_body: '',
                            additional_emails: ''
                        };
                    }
                    return this.tabsData[this.activeTab];
                },

                init() {
                    // Sayfa yüklendiğinde varsayılan boş yapıyı hazırla
                    this.initDefaultTabsData();
                },

                initDefaultTabsData() {
                    this.tabsData = {
                        general: {
                            is_custom: true,
                            subject: '',
                            body: '',
                            in_app_subject: '',
                            in_app_body: '',
                            additional_emails: ''
                        }
                    };

                    @foreach($specialTargets as $sKey => $sLabel)
                        this.tabsData['{{ $sKey }}'] = {
                            is_custom: false,
                            subject: '',
                            body: '',
                            in_app_subject: '',
                            in_app_body: ''
                        };
                    @endforeach

                    @foreach($roles as $role)
                        @php $rKey = 'role:' . $role->slug; @endphp
                        this.tabsData['{{ $rKey }}'] = {
                            is_custom: false,
                            subject: '',
                            body: '',
                            in_app_subject: '',
                            in_app_body: ''
                        };
                    @endforeach
                },

                initQuill() {
                    if (this.mailQuill && this.zilQuill) return;

                    const toolbarOptions = [
                        ['bold', 'italic', 'underline', 'strike'],
                        [{ 'list': 'ordered'}, { 'list': 'bullet' }],
                        [{ 'color': [] }, { 'background': [] }],
                        ['link', 'clean']
                    ];

                    const mailContainer = document.getElementById('mail-quill-editor');
                    if (mailContainer && !this.mailQuill) {
                        this.mailQuill = new Quill(mailContainer, {
                            theme: 'snow',
                            placeholder: 'E-posta içeriğinizi buraya yazınız...',
                            modules: { toolbar: toolbarOptions }
                        });

                        this.mailQuill.root.addEventListener('focus', () => {
                            this.lastFocused = 'mail_quill';
                        });
                    }

                    const zilContainer = document.getElementById('zil-quill-editor');
                    if (zilContainer && !this.zilQuill) {
                        this.zilQuill = new Quill(zilContainer, {
                            theme: 'snow',
                            placeholder: 'Zil (sistem içi) bildirim mesajınızı buraya yazınız...',
                            modules: { toolbar: toolbarOptions }
                        });

                        this.zilQuill.root.addEventListener('focus', () => {
                            this.lastFocused = 'zil_quill';
                        });
                    }
                },

                openModal(detail) {
                    this.event = detail;
                    this.actionUrl = '{{ url('settings/notifications/templates') }}/' + detail.id;
                    this.activeTab = 'general';
                    this.initDefaultTabsData();

                    // Gelen verileri tabsData'ya doldur
                    this.tabsData.general.subject = detail.subject || '';
                    this.tabsData.general.body = detail.body || '';
                    this.tabsData.general.in_app_subject = detail.in_app_subject || '';
                    this.tabsData.general.in_app_body = detail.in_app_body || '';
                    this.tabsData.general.additional_emails = detail.additional_emails || '';

                    // Role templates doldur
                    if (detail.role_templates && typeof detail.role_templates === 'object') {
                        for (let k in detail.role_templates) {
                            if (this.tabsData[k]) {
                                let rt = detail.role_templates[k];
                                this.tabsData[k] = {
                                    is_custom: !!rt.is_custom,
                                    subject: rt.subject || '',
                                    body: rt.body || '',
                                    in_app_subject: rt.in_app_subject || '',
                                    in_app_body: rt.in_app_body || ''
                                };
                            }
                        }
                    }

                    this.open = true;

                    this.$nextTick(() => {
                        this.initQuill();
                        this.loadTabIntoEditors('general');
                    });
                },

                closeModal() {
                    this.open = false;
                },

                switchTab(newTabKey) {
                    if (this.activeTab === newTabKey) return;

                    // 1. Mevcut tab verisini kaydet
                    this.saveEditorsIntoCurrentTab();

                    // 2. Sekmeyi değiştir
                    this.activeTab = newTabKey;

                    // 3. Yeni sekme verisini editörlere yükle
                    this.$nextTick(() => {
                        this.loadTabIntoEditors(newTabKey);
                    });
                },

                saveEditorsIntoCurrentTab() {
                    if (this.mailQuill) {
                        let html = this.mailQuill.root.innerHTML;
                        this.currentTabData.body = html === '<p><br></p>' ? '' : html;
                    }
                    if (this.zilQuill) {
                        let html = this.zilQuill.root.innerHTML;
                        this.currentTabData.in_app_body = html === '<p><br></p>' ? '' : html;
                    }
                },

                loadTabIntoEditors(tabKey) {
                    let data = this.tabsData[tabKey] || {};
                    if (this.mailQuill) {
                        this.mailQuill.root.innerHTML = data.body || '';
                    }
                    if (this.zilQuill) {
                        this.zilQuill.root.innerHTML = data.in_app_body || '';
                    }
                },

                insertVariable(varKey) {
                    if (this.lastFocused === 'subject') {
                        this.currentTabData.subject = (this.currentTabData.subject || '') + ' ' + varKey + ' ';
                    } else if (this.lastFocused === 'in_app_subject') {
                        this.currentTabData.in_app_subject = (this.currentTabData.in_app_subject || '') + ' ' + varKey + ' ';
                    } else if (this.lastFocused === 'zil_quill' && this.zilQuill) {
                        const range = this.zilQuill.getSelection(true);
                        const idx = range ? range.index : this.zilQuill.getLength();
                        this.zilQuill.insertText(idx, varKey);
                        this.zilQuill.setSelection(idx + varKey.length);
                    } else if (this.mailQuill) {
                        const range = this.mailQuill.getSelection(true);
                        const idx = range ? range.index : this.mailQuill.getLength();
                        this.mailQuill.insertText(idx, varKey);
                        this.mailQuill.setSelection(idx + varKey.length);
                    }
                },

                prepareSubmit(e) {
                    // Son editör içeriğini mevcut sekmeye kaydet
                    this.saveEditorsIntoCurrentTab();

                    // Genel şablon zorunlu kontrolleri
                    if (!this.tabsData.general.subject || !this.tabsData.general.subject.trim()) {
                        alert('Lütfen Genel Şablon için E-Posta Konusunu giriniz.');
                        this.switchTab('general');
                        e.preventDefault();
                        return;
                    }

                    if (!this.tabsData.general.body || !this.tabsData.general.body.trim()) {
                        alert('Lütfen Genel Şablon için E-Posta İçeriğini giriniz.');
                        this.switchTab('general');
                        e.preventDefault();
                        return;
                    }
                }
            };
        }
    </script>
</x-app-layout>
