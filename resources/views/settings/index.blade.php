@section('title', 'Sistem Ayarları')
<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Sistem Ayarları') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg p-6">
                
                @if(session('success'))
                    <div class="p-4 mb-4 text-sm text-green-700 bg-green-100 rounded-lg" role="alert">
                        {{ session('success') }}
                    </div>
                @endif
                @if($errors->any())
                    <div class="bg-red-50 text-red-500 p-4 rounded-lg mb-6 border border-red-100">
                        @foreach($errors->all() as $err)
                            <p>{{ $err }}</p>
                        @endforeach
                    </div>
                @endif

                <div class="md:grid md:grid-cols-3 md:gap-6">
                    <div class="md:col-span-1">
                        <div class="px-4 sm:px-0">
                            <h3 class="text-lg font-medium leading-6 text-gray-900">Sistem Logosu</h3>
                            <p class="mt-1 text-sm text-gray-600">
                                Uygulamanın sol üst köşesinde görünecek olan şirket veya sistem logosunu buradan değiştirebilirsiniz. (PNG, JPG veya SVG tavsiye edilir).
                            </p>
                        </div>
                    </div>
                    <div class="mt-5 md:mt-0 md:col-span-2">
                        <form action="{{ route('settings.logo.upload') }}" method="POST" enctype="multipart/form-data">
                            @csrf
                            <div class="shadow sm:rounded-md sm:overflow-hidden">
                                <div class="px-4 py-5 bg-white space-y-6 sm:p-6">
                                    
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">
                                            Mevcut Logo
                                        </label>
                                        <div class="mt-2 flex items-center">
                                            <span class="inline-block h-16 w-auto overflow-hidden bg-gray-50 p-2 rounded-lg border border-gray-200 shadow-inner">
                                                @if(file_exists(public_path('storage/logo.png')))
                                                    <img src="{{ asset('storage/logo.png') }}?v={{ time() }}" class="h-12 w-auto object-contain">
                                                @else
                                                    <span class="text-gray-400 font-bold px-4 py-3 inline-block">HEDEFLER (Varsayılan)</span>
                                                @endif
                                            </span>
                                        </div>
                                    </div>

                                    <div x-data="logoUpload()">
                                        <label class="block text-sm font-medium text-gray-700">
                                            Yeni Logo Yükle
                                        </label>
                                        <div class="mt-1 flex justify-center px-6 pt-5 pb-6 border-2 border-dashed rounded-md transition cursor-pointer" 
                                             :class="previewUrl ? 'border-emerald-300 bg-emerald-50' : 'border-indigo-300 bg-indigo-50 hover:bg-indigo-100'"
                                             onclick="document.getElementById('file-upload').click()">
                                            <div class="space-y-1 text-center">
                                                
                                                <template x-if="!previewUrl">
                                                    <svg class="mx-auto h-12 w-12 text-indigo-400" stroke="currentColor" fill="none" viewBox="0 0 48 48" aria-hidden="true">
                                                        <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                                    </svg>
                                                </template>
                                                <template x-if="previewUrl">
                                                    <img :src="previewUrl" class="mx-auto h-24 w-auto object-contain mb-2 rounded shadow-sm">
                                                </template>

                                                <div class="flex text-sm text-gray-600 justify-center mt-2">
                                                    <label for="file-upload" class="relative cursor-pointer bg-transparent rounded-md font-medium text-indigo-600 hover:text-indigo-800 focus-within:outline-none focus-within:ring-2 focus-within:ring-offset-2 focus-within:ring-indigo-500">
                                                        <span x-text="fileName ? fileName : 'Bilgisayardan Dosya Seç'"></span>
                                                        <input id="file-upload" name="logo" type="file" class="sr-only" accept="image/png, image/jpeg, image/jpg, image/svg+xml, image/webp" @change="fileChosen" required>
                                                    </label>
                                                </div>
                                                <p class="text-xs text-gray-500 mt-2" x-show="!fileName">
                                                    PNG, JPG, SVG - Maksimum 2MB
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                    
                                </div>
                                <div class="px-4 py-3 bg-gray-50 text-right sm:px-6">
                                    <button type="submit" class="inline-flex justify-center py-2 px-6 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                        Logoyu Güncelle
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="hidden sm:block" aria-hidden="true">
                    <div class="py-5">
                        <div class="border-t border-gray-200"></div>
                    </div>
                </div>

                {{-- Form & Onay Süreci Ayarları --}}
                <div class="md:grid md:grid-cols-3 md:gap-6 mt-10">
                    <div class="md:col-span-1">
                        <div class="px-4 sm:px-0">
                            <h3 class="text-lg font-medium leading-6 text-gray-900">Form & Revizyon Ayarları</h3>
                            <p class="mt-1 text-sm text-gray-600">
                                Onaylanmış form kayıtları için personelin "Düzenleme Talep Et (Revizyon)" butonu ile talepte bulunabilme özelliğini sistem genelinde açıp kapatabilirsiniz.
                            </p>
                        </div>
                    </div>
                    <div class="mt-5 md:mt-0 md:col-span-2">
                        <form action="{{ route('settings.general.update') }}" method="POST">
                            @csrf
                            <div class="shadow sm:rounded-md sm:overflow-hidden bg-white border border-gray-100">
                                <div class="px-4 py-5 sm:p-6 space-y-4">
                                    <div class="flex items-start">
                                        <div class="flex items-center h-5">
                                            <input id="form_revision_enabled" name="form_revision_enabled" type="checkbox" value="1" {{ $formRevisionEnabled ? 'checked' : '' }}
                                                class="focus:ring-indigo-500 h-5 w-5 text-indigo-600 border-gray-300 rounded cursor-pointer">
                                        </div>
                                        <div class="ml-3 text-sm">
                                            <label for="form_revision_enabled" class="font-bold text-gray-800 cursor-pointer">
                                                Onaylı Formlarda Düzenleme (Revizyon) Talebi Özelliğini Etkinleştir
                                            </label>
                                            <p class="text-gray-500 text-xs mt-1">
                                                İşaretli olduğunda; onaylanmış formların detayında personele gerekçe belirterek düzenleme talep edebileceği bir buton sunulur. İşaret kaldırılırsa bu buton tüm sistemde gizlenir.
                                            </p>
                                        </div>
                                    </div>
                                </div>
                                <div class="px-4 py-3 bg-gray-50 text-right sm:px-6">
                                    <button type="submit" class="inline-flex justify-center py-2 px-6 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                        Ayarları Kaydet
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="md:grid md:grid-cols-3 md:gap-6 mt-10">
                    <div class="md:col-span-1">
                        <div class="px-4 sm:px-0">
                            <h3 class="text-lg font-medium leading-6 text-gray-900">Bildirim Matrisi</h3>
                            <p class="mt-1 text-sm text-gray-600">
                                Sistemde gerçekleşen olaylar sonrası (yeni form vb.) kime Zil veya E-Posta bildirimi gideceğini ve E-Posta şablonlarını buradan yönetebilirsiniz.
                            </p>
                        </div>
                    </div>
                    <div class="mt-5 md:mt-0 md:col-span-2">
                        <div class="shadow sm:rounded-md sm:overflow-hidden bg-white px-4 py-5 sm:p-6 flex flex-col sm:flex-row items-center justify-between border border-gray-100">
                            <div class="flex items-center mb-4 sm:mb-0">
                                <div class="w-12 h-12 bg-indigo-50 text-indigo-600 rounded-full flex items-center justify-center mr-4">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path></svg>
                                </div>
                                <div>
                                    <h4 class="text-base font-bold text-gray-900">Dinamik Bildirim Sistemi</h4>
                                    <p class="text-sm text-gray-500">Bildirim kanallarını ve metinlerini düzenleyin.</p>
                                </div>
                            </div>
                            <a href="{{ route('settings.notifications.index') }}" class="inline-flex justify-center py-2 px-6 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                Matrisi Yönet &rarr;
                            </a>
                        </div>
                    </div>
                </div>

                <div class="hidden sm:block" aria-hidden="true">
                    <div class="py-5">
                        <div class="border-t border-gray-200"></div>
                    </div>
                </div>

                <!-- Bildirim ve E-Posta Gönderim Geçmişi (Loglar) -->
                <div class="md:grid md:grid-cols-3 md:gap-6 mt-10">
                    <div class="md:col-span-1">
                        <div class="px-4 sm:px-0">
                            <h3 class="text-lg font-medium leading-6 text-gray-900">Bildirim ve E-Posta Geçmişi</h3>
                            <p class="mt-1 text-sm text-gray-600">
                                Sistem tarafından gönderilen tüm Zil (in-app) ve E-Posta (mail) bildirimlerinin alıcı, rol, işlem ve içerik detaylı log kayıtlarını filtreleyip görüntüleyebilirsiniz.
                            </p>
                        </div>
                    </div>
                    <div class="mt-5 md:mt-0 md:col-span-2">
                        <div class="shadow sm:rounded-md sm:overflow-hidden bg-white px-4 py-5 sm:p-6 flex flex-col sm:flex-row items-center justify-between border border-gray-100">
                            <div class="flex items-center mb-4 sm:mb-0">
                                <div class="w-12 h-12 bg-indigo-50 text-indigo-600 rounded-full flex items-center justify-center mr-4">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                                </div>
                                <div>
                                    <h4 class="text-base font-bold text-gray-900">Gönderim Geçmişi (Zil & Mail Logları)</h4>
                                    <p class="text-sm text-gray-500">Zil ve mail loglarını işlem bazlı filtreleyin ve inceleyin.</p>
                                </div>
                            </div>
                            <a href="{{ route('settings.notification_logs.index') }}" class="inline-flex justify-center py-2 px-6 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                Logları İncele &rarr;
                            </a>
                        </div>
                    </div>
                </div>

                <div class="hidden sm:block" aria-hidden="true">
                    <div class="py-5">
                        <div class="border-t border-gray-200"></div>
                    </div>
                </div>

                <!-- Sistem Günlüğü & Denetim İzi (Audit Logs) -->
                <div class="md:grid md:grid-cols-3 md:gap-6">
                    <div class="md:col-span-1">
                        <div class="px-4 sm:px-0">
                            <h3 class="text-lg font-medium leading-6 text-gray-900">Sistem Günlüğü & Denetim İzi</h3>
                            <p class="mt-1 text-sm text-gray-600">
                                Tüm kullanıcı işlemleri, veri girişleri, onay/red kararları, revizyonlar ve idari hareketlerin sayfalama ve filtrelemeli geçmiş kayıtları.
                            </p>
                        </div>
                    </div>
                    <div class="mt-5 md:mt-0 md:col-span-2">
                        <div class="shadow sm:rounded-md sm:overflow-hidden bg-white px-4 py-5 sm:p-6 flex flex-col sm:flex-row items-center justify-between border border-gray-100">
                            <div class="flex items-center mb-4 sm:mb-0">
                                <div class="w-12 h-12 bg-purple-50 text-purple-600 rounded-full flex items-center justify-center mr-4">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path></svg>
                                </div>
                                <div>
                                    <h4 class="text-base font-bold text-gray-900">Sistem Denetim İzi (Audit Logs)</h4>
                                    <p class="text-sm text-gray-500">Tüm kullanıcı hareketlerini, silinen verileri ve onay geçmişini sayfa başına 20 kayıtla inceleyin.</p>
                                </div>
                            </div>
                            <a href="{{ route('settings.activity_logs.index') }}" class="inline-flex justify-center py-2 px-6 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-purple-600 hover:bg-purple-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-purple-500">
                                Günlüğü İncele &rarr;
                            </a>
                        </div>
                    </div>
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

