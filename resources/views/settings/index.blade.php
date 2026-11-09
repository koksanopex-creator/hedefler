@section('title', 'Sistem Ayarları')
<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div class="flex items-center gap-3.5">
                <div class="w-11 h-11 rounded-2xl bg-gradient-to-br from-indigo-600 to-purple-600 flex items-center justify-center text-white shadow-md shadow-indigo-500/20">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                    </svg>
                </div>
                <div>
                    <h2 class="font-extrabold text-2xl text-gray-900 tracking-tight">
                        {{ __('Sistem Ayarları & Yapılandırma') }}
                    </h2>
                    <p class="text-xs sm:text-sm text-gray-500 mt-0.5">Sistem kimliği, iş akışı kuralları, bildirim mekanizmaları ve denetim loglarını merkezi olarak yönetin.</p>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                    <span class="w-2 h-2 rounded-full bg-emerald-500 inline-block mr-1.5 animate-pulse"></span>
                    Sistem Aktif
                </span>
            </div>
        </div>
    </x-slot>

    <div class="py-8 sm:py-10">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-8">
            
            {{-- Bildirim Mesajları --}}
            @if(session('success'))
                <div class="p-4 rounded-2xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm font-medium flex items-center justify-between shadow-2xs">
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 rounded-xl bg-emerald-100 text-emerald-600 flex items-center justify-center shrink-0">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg>
                        </div>
                        <span>{{ session('success') }}</span>
                    </div>
                </div>
            @endif

            @if($errors->any())
                <div class="p-4 rounded-2xl bg-rose-50 border border-rose-200 text-rose-800 text-sm font-medium shadow-2xs space-y-1">
                    @foreach($errors->all() as $err)
                        <div class="flex items-center gap-2">
                            <svg class="w-4 h-4 text-rose-500 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path></svg>
                            <span>{{ $err }}</span>
                        </div>
                    @endforeach
                </div>
            @endif

            {{-- 1. KART: KURUMSAL KİMLİK & LOGO YÖNETİMİ --}}
            <div class="bg-white/95 backdrop-blur-xl rounded-3xl border border-gray-100 shadow-[0_8px_30px_rgb(0,0,0,0.04)] overflow-hidden">
                <div class="px-6 py-5 bg-gradient-to-r from-blue-50/80 via-indigo-50/40 to-white border-b border-gray-100 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                    <div class="flex items-center gap-3.5">
                        <div class="w-10 h-10 rounded-2xl bg-gradient-to-br from-blue-600 to-indigo-600 flex items-center justify-center text-white shadow-md shadow-blue-500/20">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-base font-extrabold text-gray-900 tracking-tight flex items-center gap-2">
                                Kurumsal Kimlik & Sistem Logosu
                                @if($customLogoExists)
                                    <span class="text-[11px] font-bold text-emerald-700 bg-emerald-100/80 px-2.5 py-0.5 rounded-full">Özel Logo Yüklü</span>
                                @else
                                    <span class="text-[11px] font-semibold text-gray-600 bg-gray-100 px-2.5 py-0.5 rounded-full">Varsayılan Logo</span>
                                @endif
                            </h3>
                            <p class="text-xs text-gray-500 mt-0.5">Uygulamanın sol üst gezinti çubuğunda ve e-postalarda görüntülenecek kurumsal logoyu yapılandırın.</p>
                        </div>
                    </div>
                </div>

                <div class="p-6">
                    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-center">
                        
                        {{-- Sol: Mevcut Logo Önizleme --}}
                        <div class="lg:col-span-5 flex flex-col items-center justify-center p-6 bg-gray-50/70 rounded-2xl border border-gray-100 text-center space-y-4">
                            <span class="text-xs font-bold text-gray-500 tracking-wider uppercase">Mevcut Aktif Logo</span>
                            
                            <div class="h-20 w-full max-w-[280px] bg-white rounded-xl border border-gray-200 p-3 shadow-inner flex items-center justify-center">
                                @if($customLogoExists)
                                    <img src="{{ asset('storage/logo.png') }}?v={{ time() }}" alt="Kurumsal Logo" class="max-h-14 max-w-full object-contain">
                                @else
                                    <span class="text-gray-400 font-extrabold text-lg tracking-wider">HEDEFLER</span>
                                @endif
                            </div>

                            <div class="flex items-center gap-2 text-xs text-gray-500">
                                <svg class="w-4 h-4 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                <span>Şeffaf PNG, SVG veya yüksek çözünürlüklü JPG</span>
                            </div>

                            @if($customLogoExists)
                                <form action="{{ route('settings.logo.remove') }}" method="POST" onsubmit="return confirm('Sistem logosunu varsayılan haline sıfırlamak istediğinize emin misiniz?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-xs font-semibold text-rose-600 hover:text-rose-800 hover:underline inline-flex items-center gap-1 transition">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                        Varsayılan Logoya Sıfırla
                                    </button>
                                </form>
                            @endif
                        </div>

                        {{-- Sağ: Yeni Logo Yükleme Alanı --}}
                        <div class="lg:col-span-7">
                            <form action="{{ route('settings.logo.upload') }}" method="POST" enctype="multipart/form-data" x-data="logoUpload()">
                                @csrf
                                <div class="space-y-4">
                                    <label class="block text-xs font-bold text-gray-700">
                                        Yeni Bir Logo Dosyası Yükleyin
                                    </label>

                                    <div class="relative border-2 border-dashed rounded-2xl p-6 text-center transition cursor-pointer"
                                         :class="previewUrl ? 'border-emerald-400 bg-emerald-50/50' : 'border-indigo-200 bg-indigo-50/30 hover:bg-indigo-50/60 hover:border-indigo-400'"
                                         @click="document.getElementById('file-upload').click()">
                                        
                                        <template x-if="!previewUrl">
                                            <div class="space-y-2">
                                                <div class="w-12 h-12 rounded-2xl bg-indigo-100 text-indigo-600 flex items-center justify-center mx-auto shadow-2xs">
                                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                                                    </svg>
                                                </div>
                                                <div class="text-xs text-gray-700 font-semibold">
                                                    Bilgisayarınızdan bir dosya seçin veya buraya sürükleyin
                                                </div>
                                                <p class="text-[11px] text-gray-400">
                                                    PNG, JPG, SVG veya WEBP (Maksimum 2MB)
                                                </p>
                                            </div>
                                        </template>

                                        <template x-if="previewUrl">
                                            <div class="space-y-3">
                                                <div class="h-16 flex items-center justify-center">
                                                    <img :src="previewUrl" class="max-h-16 max-w-[240px] object-contain rounded-lg p-1 bg-white border border-emerald-200 shadow-2xs">
                                                </div>
                                                <div class="text-xs font-bold text-emerald-800 flex items-center justify-center gap-1.5">
                                                    <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                                    <span x-text="fileName"></span>
                                                </div>
                                                <p class="text-[11px] text-gray-500">Değiştirmek için tekrar tıklayabilirsiniz</p>
                                            </div>
                                        </template>

                                        <input id="file-upload" name="logo" type="file" class="sr-only" accept="image/png, image/jpeg, image/jpg, image/svg+xml, image/webp" @change="fileChosen" required>
                                    </div>

                                    <div class="flex items-center justify-between pt-1">
                                        <span class="text-[11px] text-gray-400">Yüklenen logo tüm kullanıcılar için eş zamanlı güncellenir.</span>
                                        <button type="submit" 
                                            :disabled="!fileName"
                                            class="px-5 py-2.5 rounded-xl font-bold text-xs text-white shadow-md transition inline-flex items-center gap-2"
                                            :class="fileName ? 'bg-indigo-600 hover:bg-indigo-700 hover:shadow-lg cursor-pointer' : 'bg-gray-300 cursor-not-allowed opacity-60'">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path></svg>
                                            Logoyu Güncelle
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>

                    </div>
                </div>
            </div>

            {{-- 2. KART: İŞ AKIŞI & SÜREÇ AYARLARI --}}
            <div class="bg-white/95 backdrop-blur-xl rounded-3xl border border-gray-100 shadow-[0_8px_30px_rgb(0,0,0,0.04)] overflow-hidden">
                <div class="px-6 py-5 bg-gradient-to-r from-amber-50/80 via-orange-50/40 to-white border-b border-gray-100 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                    <div class="flex items-center gap-3.5">
                        <div class="w-10 h-10 rounded-2xl bg-gradient-to-br from-amber-500 to-orange-600 flex items-center justify-center text-white shadow-md shadow-amber-500/20">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path>
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-base font-extrabold text-gray-900 tracking-tight flex items-center gap-2">
                                Form & Revizyon İş Akışı Kuralları
                            </h3>
                            <p class="text-xs text-gray-500 mt-0.5">Onaylanmış veri girişleri ve form kayıtları için personel düzenleme/revizyon haklarını yapılandırın.</p>
                        </div>
                    </div>
                </div>

                <form action="{{ route('settings.general.update') }}" method="POST">
                    @csrf
                    <div class="p-6">
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between p-4 sm:p-5 rounded-2xl border border-gray-100 bg-gray-50/60 hover:bg-gray-50 transition gap-4">
                            <div class="flex items-start gap-4">
                                <div class="w-10 h-10 rounded-xl bg-indigo-100 text-indigo-700 flex items-center justify-center shrink-0 mt-0.5">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                    </svg>
                                </div>
                                <div class="space-y-1">
                                    <label for="form_revision_enabled" class="text-sm font-bold text-gray-900 cursor-pointer flex items-center gap-2">
                                        Onaylı Formlarda Düzenleme (Revizyon) Talebini Etkinleştir
                                        @if($formRevisionEnabled)
                                            <span class="text-[10px] font-bold text-emerald-700 bg-emerald-100 px-2 py-0.5 rounded-full">Açık</span>
                                        @else
                                            <span class="text-[10px] font-bold text-gray-600 bg-gray-200 px-2 py-0.5 rounded-full">Kapalı</span>
                                        @endif
                                    </label>
                                    <p class="text-xs text-gray-500 leading-relaxed max-w-3xl">
                                        İşaretli olduğunda; onaylanmış formların detayında personele gerekçe belirterek yöneticiden düzenleme izni isteyebileceği 
                                        <strong>"Düzenleme Talep Et (Revizyon)"</strong> butonu sunulur. İşaret kaldırılırsa onaylanan formlar kilitlenir ve revizyon talep butonu tüm sistemde gizlenir.
                                    </p>
                                </div>
                            </div>

                            <div class="sm:shrink-0 flex items-center justify-end">
                                <label class="relative inline-flex items-center cursor-pointer select-none">
                                    <input type="checkbox" id="form_revision_enabled" name="form_revision_enabled" value="1" {{ $formRevisionEnabled ? 'checked' : '' }} class="sr-only peer">
                                    <div class="w-12 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-indigo-600"></div>
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="px-6 py-3.5 bg-gray-50/80 border-t border-gray-100 flex items-center justify-between">
                        <span class="text-xs text-gray-400">Değişikliklerin yürürlüğe girmesi için ayarları kaydedin.</span>
                        <button type="submit" class="px-5 py-2 rounded-xl font-bold text-xs text-white bg-indigo-600 hover:bg-indigo-700 shadow-md transition hover:shadow-lg inline-flex items-center gap-1.5">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                            Ayarları Kaydet
                        </button>
                    </div>
                </form>
            </div>

            {{-- 3. KART GRUBU: YÖNETİM, BİLDİRİM & GÜVENLİK MODÜLLERİ --}}
            <div>
                <div class="mb-4">
                    <h3 class="text-base font-extrabold text-gray-900 tracking-tight">Yönetim & Denetim Modülleri</h3>
                    <p class="text-xs text-gray-500 mt-0.5">Sistem bildirimleri, gönderim geçmişi ve kullanıcı hareketlerinin denetim kayıtlarına doğrudan erişin.</p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    
                    {{-- Modül 1: Bildirim Matrisi --}}
                    <div class="bg-white rounded-3xl border border-gray-100 shadow-[0_8px_30px_rgb(0,0,0,0.04)] p-6 flex flex-col justify-between hover:shadow-md transition">
                        <div class="space-y-4">
                            <div class="flex items-center justify-between">
                                <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center text-white shadow-md shadow-indigo-500/20">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path></svg>
                                </div>
                                <span class="text-[11px] font-bold text-indigo-700 bg-indigo-50 px-2.5 py-1 rounded-full border border-indigo-100">
                                    {{ $notificationEventsCount }} Sistem Olayı
                                </span>
                            </div>

                            <div>
                                <h4 class="text-base font-extrabold text-gray-900">Dinamik Bildirim Matrisi</h4>
                                <p class="text-xs text-gray-500 mt-1.5 leading-relaxed">
                                    Sistemde gerçekleşen olaylar (yeni form, onay, revizyon vb.) sonrası kime Zil veya E-Posta bildirimi gideceğini ve zengin HTML şablonlarını yönetin.
                                </p>
                            </div>
                        </div>

                        <div class="pt-6 border-t border-gray-100 mt-6">
                            <a href="{{ route('settings.notifications.index') }}" class="w-full inline-flex items-center justify-center py-2.5 px-4 rounded-xl text-xs font-bold text-indigo-700 bg-indigo-50 hover:bg-indigo-100 border border-indigo-200 transition shadow-2xs group">
                                Matrisi Yönet
                                <svg class="w-3.5 h-3.5 ml-1.5 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
                            </a>
                        </div>
                    </div>

                    {{-- Modül 2: Bildirim ve E-Posta Gönderim Geçmişi --}}
                    <div class="bg-white rounded-3xl border border-gray-100 shadow-[0_8px_30px_rgb(0,0,0,0.04)] p-6 flex flex-col justify-between hover:shadow-md transition">
                        <div class="space-y-4">
                            <div class="flex items-center justify-between">
                                <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-sky-500 to-teal-600 flex items-center justify-center text-white shadow-md shadow-sky-500/20">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                                </div>
                                <span class="text-[11px] font-bold text-teal-700 bg-teal-50 px-2.5 py-1 rounded-full border border-teal-100">
                                    {{ $notificationLogsCount }} İletim Kaydı
                                </span>
                            </div>

                            <div>
                                <h4 class="text-base font-extrabold text-gray-900">Gönderim Geçmişi (Loglar)</h4>
                                <p class="text-xs text-gray-500 mt-1.5 leading-relaxed">
                                    Sistem tarafından iletilen tüm Zil (in-app) ve E-Posta bildirimlerinin rol, işlem, alıcı ve içerik detaylı teslim kayıtlarını filtreleyin ve inceleyin.
                                </p>
                            </div>
                        </div>

                        <div class="pt-6 border-t border-gray-100 mt-6">
                            <a href="{{ route('settings.notification_logs.index') }}" class="w-full inline-flex items-center justify-center py-2.5 px-4 rounded-xl text-xs font-bold text-teal-700 bg-teal-50 hover:bg-teal-100 border border-teal-200 transition shadow-2xs group">
                                Logları İncele
                                <svg class="w-3.5 h-3.5 ml-1.5 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
                            </a>
                        </div>
                    </div>

                    {{-- Modül 3: Sistem Günlüğü & Denetim İzi --}}
                    <div class="bg-white rounded-3xl border border-gray-100 shadow-[0_8px_30px_rgb(0,0,0,0.04)] p-6 flex flex-col justify-between hover:shadow-md transition">
                        <div class="space-y-4">
                            <div class="flex items-center justify-between">
                                <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-purple-600 to-pink-600 flex items-center justify-center text-white shadow-md shadow-purple-500/20">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path></svg>
                                </div>
                                <span class="text-[11px] font-bold text-purple-700 bg-purple-50 px-2.5 py-1 rounded-full border border-purple-100">
                                    {{ $activityLogsCount }} Olay Kaydı
                                </span>
                            </div>

                            <div>
                                <h4 class="text-base font-extrabold text-gray-900">Sistem Denetim İzi (Audit)</h4>
                                <p class="text-xs text-gray-500 mt-1.5 leading-relaxed">
                                    Kullanıcı hareketleri, veri girişleri, onay/red kararları, silinen kayıtlar ve idari işlemlerin güvenlik ve denetim standartlarına uygun kayıtları.
                                </p>
                            </div>
                        </div>

                        <div class="pt-6 border-t border-gray-100 mt-6">
                            <a href="{{ route('settings.activity_logs.index') }}" class="w-full inline-flex items-center justify-center py-2.5 px-4 rounded-xl text-xs font-bold text-purple-700 bg-purple-50 hover:bg-purple-100 border border-purple-200 transition shadow-2xs group">
                                Denetim İzini Aç
                                <svg class="w-3.5 h-3.5 ml-1.5 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
                            </a>
                        </div>
                    </div>

                </div>
            </div>

            {{-- 4. BİLGİLENDİRME ALT PANELİ --}}
            <div class="p-4 sm:p-5 rounded-2xl bg-gray-50 border border-gray-100 flex flex-col sm:flex-row items-center justify-between text-xs text-gray-500 gap-3">
                <div class="flex items-center gap-2">
                    <span class="w-2 h-2 rounded-full bg-indigo-500"></span>
                    <span>Ortam: <strong class="text-gray-800">{{ app()->environment() === 'production' ? 'Canlı Sunucu (Production)' : 'Geliştirme (Local)' }}</strong></span>
                </div>
                <div class="flex flex-wrap items-center gap-4">
                    <span>PHP: <strong class="text-gray-800">{{ phpversion() }}</strong></span>
                    <span>•</span>
                    <span>Laravel: <strong class="text-gray-800">{{ app()->version() }}</strong></span>
                    <span>•</span>
                    <span>Veritabanı: <strong class="text-gray-800">MySQL 8.0</strong></span>
                </div>
            </div>

        </div>
    </div>

    <script>
        function logoUpload() {
            return {
                previewUrl: null,
                fileName: null,
                fileChosen(event) {
                    const file = event.target.files[0];
                    if (!file) return;

                    this.fileName = file.name;
                    
                    const reader = new FileReader();
                    reader.onload = (e) => {
                        this.previewUrl = e.target.result;
                    };
                    reader.readAsDataURL(file);
                }
            }
        }
    </script>
</x-app-layout>
