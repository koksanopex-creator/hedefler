@section('title', 'Form Alanlarını Düzenle')
<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center space-x-4">
            <a href="{{ route('form-templates.show', $formTemplate) }}" class="text-gray-500 hover:text-gray-700 transition">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
            </a>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ $formTemplate->name }} - Form Alanları Tasarımı
            </h2>
        </div>
    </x-slot>

    <div class="py-12 pb-32" x-data="formBuilder({{ json_encode($formTemplate->fields) }}, {{ $formTemplate->allow_partition_entry ? 'true' : 'false' }}, {{ json_encode($formTemplate->partition_field_id) }})">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg p-6 border border-gray-100">
                
                <div class="flex justify-between items-center mb-6 border-b pb-4">
                    <p class="text-sm text-gray-600">Bu ekrandan forma yeni alanlar (sütunlar) ekleyebilir ve tiplerini belirleyebilirsiniz. (Örn: Sayısal, Açılır Menü vs.)</p>
                    <button type="button" @click="addField()" class="bg-indigo-50 text-indigo-700 hover:bg-indigo-100 font-bold py-2 px-4 rounded-lg shadow-sm transition border border-indigo-200">
                        + Yeni Alan Ekle
                    </button>
                </div>

                @if($errors->any())
                    <div class="bg-red-50 text-red-500 p-4 rounded-lg mb-6 border border-red-100">
                        Tüm alanların eksiksiz doldurulduğundan emin olun.
                    </div>
                @endif

                <form action="{{ route('form_fields.update', $formTemplate) }}" method="POST">
                    @csrf

                    <!-- 🌟 KISITLI / BÖLÜMLENMİŞ FORM GİRİŞİ AYARI (PARTITION MODE) -->
                    <input type="hidden" name="allow_partition_entry" :value="allowPartition ? '1' : '0'">
                    <input type="hidden" name="partition_field_id" :value="allowPartition ? partitionFieldId : ''">

                    <div class="mb-6 p-5 bg-gradient-to-r from-amber-50/70 via-indigo-50/40 to-slate-50 border-2 border-indigo-200/80 rounded-2xl shadow-xs transition-all">
                        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                            <div class="flex items-start gap-3">
                                <span class="p-2.5 bg-indigo-600 text-white rounded-xl shadow-xs shrink-0 mt-0.5">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16m-7 6h7"></path></svg>
                                </span>
                                <div>
                                    <div class="flex items-center gap-2">
                                        <h3 class="text-sm font-black text-slate-900 uppercase tracking-wide">Kısıtlı / Parçalı Veri Girişi ve Konsolidasyon Modu</h3>
                                        <span class="text-[10px] font-black uppercase px-2 py-0.5 rounded-full" 
                                            :class="allowPartition ? 'bg-emerald-100 text-emerald-800 border border-emerald-300' : 'bg-slate-200 text-slate-600'">
                                            <span x-text="allowPartition ? 'AKTİF' : 'PASİF'"></span>
                                        </span>
                                    </div>
                                    <p class="text-xs text-slate-600 mt-1 max-w-2xl">
                                        Bu ayar açıldığında personel form açılışında tek bir devasa tablo yerine, seçeceği kritere (örn: <strong class="text-indigo-700">İŞYERİ</strong> - Kapak, Preform, Levha vb.) göre kısıtlanmış izole formla karşılaşır. Formlar parça parça kaydedilir ve istendiğinde tek tıkla birleştirilebilir (konsolide edilir).
                                    </p>
                                </div>
                            </div>
                            
                            <!-- Toggle ve Alan Seçimi -->
                            <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3 shrink-0">
                                <label class="inline-flex items-center cursor-pointer select-none bg-white px-3.5 py-2 rounded-xl border border-slate-300 hover:border-indigo-400 shadow-2xs">
                                    <input type="checkbox" x-model="allowPartition" class="sr-only peer">
                                    <div class="w-9 h-5 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-indigo-600"></div>
                                    <span class="ml-2.5 text-xs font-bold text-slate-700">Parçalı Girişi Aç</span>
                                </label>

                                <div x-show="allowPartition" class="flex items-center gap-2" x-transition>
                                    <label class="text-xs font-bold text-slate-700 whitespace-nowrap">Bölümleme Kriteri:</label>
                                    <select x-model="partitionFieldId" class="rounded-xl border-slate-300 text-xs font-bold text-indigo-900 bg-white py-1.5 px-3 focus:ring-indigo-500 focus:border-indigo-500 shadow-2xs min-w-[220px]">
                                        <option value="">-- Alan Seçiniz --</option>
                                        <template x-for="f in selectablePartitionFields" :key="f._key">
                                            <option :value="f._key" :selected="String(partitionFieldId) === String(f._key)" x-text="(f._idx + 1) + '. ' + f.display_label + ' [' + f.type_label + ']'"></option>
                                        </template>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Hızlı Erişim Menüsü (Üst Bar) -->
                    <div class="mb-6 p-4 bg-gradient-to-r from-slate-50 via-indigo-50/40 to-purple-50/30 border border-indigo-100 rounded-2xl shadow-xs" x-show="fields.length > 0">
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 mb-3 pb-2.5 border-b border-indigo-100/80">
                            <div class="flex items-center gap-2">
                                <span class="p-1.5 bg-indigo-600 text-white rounded-lg shadow-xs">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                                </span>
                                <span class="text-xs font-bold uppercase tracking-wider text-slate-800">Hızlı Alan Erişimi (Tıklayınca Alana Kayar)</span>
                                <span class="text-[11px] font-bold text-indigo-700 bg-indigo-100 px-2 py-0.5 rounded-full border border-indigo-200" x-text="fields.length + ' Alan'"></span>
                            </div>
                            <span class="text-[11px] text-slate-500 italic">Etikete tıklayarak doğrudan o alana gidebilirsiniz.</span>
                        </div>
                        
                        <!-- Alan Rozetleri / Linkleri -->
                        <div class="flex flex-wrap gap-2 max-h-48 overflow-y-auto pr-1">
                            <template x-for="(f, i) in fields" :key="i">
                                <button type="button" 
                                        @click="scrollToField(i)" 
                                        class="group inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold transition-all duration-150 border bg-white text-slate-700 border-slate-200 hover:border-indigo-400 hover:bg-indigo-50 hover:text-indigo-700 hover:shadow-xs active:scale-95 cursor-pointer">
                                    <span class="text-[10px] font-bold text-slate-400 group-hover:text-indigo-600" x-text="(i + 1) + '.'"></span>
                                    <span x-text="f.label && f.label.trim() !== '' ? f.label : '(İsimsiz ' + (i + 1) + ')'" class="truncate max-w-[170px]"></span>
                                    <span class="w-1.5 h-1.5 rounded-full" 
                                          :class="{
                                              'bg-indigo-500': f.type === 'master_data',
                                              'bg-purple-500': f.type === 'user_list',
                                              'bg-blue-500': f.type === 'number',
                                              'bg-emerald-500': f.type === 'select',
                                              'bg-amber-500': f.type === 'date' || f.type === 'year',
                                              'bg-slate-400': f.type === 'text' || f.type === 'textarea' || f.type === 'auto_user'
                                          }"></span>
                                </button>
                            </template>
                        </div>
                    </div>

                    <!-- Dinamik Alanlar Listesi -->
                    <div class="space-y-4 mb-8">
                        <template x-for="(field, index) in fields" :key="index">
                            <div :id="'field-card-' + index" class="p-4 bg-gray-50 rounded-lg border border-gray-200 transition-all duration-300 hover:shadow-md">
                                
                                <div class="flex justify-between items-center mb-4 pb-3 border-b border-gray-200">
                                    <!-- Sol Üst: Sıralama -->
                                    <div class="flex items-center space-x-2">
                                        <span class="text-xs font-bold text-indigo-700 bg-indigo-50 px-3 py-1.5 rounded-md border border-indigo-200 shadow-sm">Sıra: <span x-text="index + 1"></span></span>
                                        
                                        <button type="button" @click="moveUp(index)" :disabled="index === 0" :class="{'opacity-30 cursor-not-allowed': index === 0, 'hover:bg-gray-200 hover:text-gray-800': index !== 0}" class="text-gray-600 bg-gray-100 p-1.5 rounded border border-gray-300 transition shadow-sm" title="Yukarı Taşı">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"></path></svg>
                                        </button>
                                        
                                        <button type="button" @click="moveDown(index)" :disabled="index === fields.length - 1" :class="{'opacity-30 cursor-not-allowed': index === fields.length - 1, 'hover:bg-gray-200 hover:text-gray-800': index !== fields.length - 1}" class="text-gray-600 bg-gray-100 p-1.5 rounded border border-gray-300 transition shadow-sm" title="Aşağı Taşı">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                                        </button>
                                    </div>

                                    <!-- Sağ Üst: Menüye Dön ve Silme -->
                                    <div class="flex items-center gap-2">
                                        <button type="button" @click="window.scrollTo({ top: 0, behavior: 'smooth' })" class="text-slate-500 hover:text-indigo-600 bg-white hover:bg-slate-100 py-1.5 px-2.5 rounded border border-gray-200 transition shadow-xs text-xs font-semibold flex items-center gap-1 cursor-pointer" title="Sayfa Başına / Hızlı Menüye Dön">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18"></path></svg>
                                            <span>Menü</span>
                                        </button>
                                        
                                        <button type="button" @click="removeField(index)" class="text-red-600 hover:text-red-800 bg-red-50 hover:bg-red-100 py-1.5 px-3 rounded border border-red-200 transition shadow-sm flex items-center gap-1.5 text-xs font-bold cursor-pointer" title="Alanı Sil">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                            <span>ALANI SİL</span>
                                        </button>
                                    </div>
                                </div>
                                
                                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                                    <!-- Label -->
                                    <div class="md:col-span-1">
                                        <label class="block text-xs font-medium text-gray-500 uppercase tracking-wider mb-1">Etiket (Başlık)</label>
                                        <input type="hidden" :name="'fields['+index+'][id]'" x-model="field.id">
                                        <input type="hidden" :name="'fields['+index+'][_uid]'" :value="field._uid">
                                        <input type="text" x-model="field.label" :name="'fields['+index+'][label]'" required class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm p-2" placeholder="Örn: Miktar">
                                    </div>
                                    
                                    <!-- Tip -->
                                    <div class="md:col-span-1">
                                        <label class="block text-xs font-medium text-gray-500 uppercase tracking-wider mb-1">HTML Tipi</label>
                                        <select x-model="field.type" :name="'fields['+index+'][type]'" required class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm p-2 bg-white">
                                            <option value="text">Kısa Metin (Text)</option>
                                            <option value="number">Sayı (Number)</option>
                                            <option value="textarea">Uzun Metin (Textarea)</option>
                                            <option value="select">Açılır Menü (Select)</option>
                                            <option value="date">Tarih (Date)</option>
                                            <option value="year">Yıl (Seçim Kutusu)</option>
                                            <option value="auto_user">Otomatik Kullanıcı (Görünmez)</option>
                                            <option value="user_list">Kullanıcı Listesi (Seçim Kutusu)</option>
                                            <option value="master_data">Master Data (Dinamik Liste)</option>
                                        </select>
                                    </div>

                                    <!-- Değer Tipi -->
                                    <div class="md:col-span-1">
                                        <label class="block text-xs font-medium text-gray-500 uppercase tracking-wider mb-1">Değer Tipi (Analiz için)</label>
                                        <select x-model="field.value_type" :name="'fields['+index+'][value_type]'" required class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm p-2 bg-white">
                                            <option value="nicel">Nicel (Sayısal Analiz)</option>
                                            <option value="nitel">Nitel (Metinsel/Kategorik)</option>
                                        </select>
                                    </div>

                                    <!-- Seçenekler (Select için) -->
                                    <div class="md:col-span-1" x-show="field.type === 'select'">
                                        <label class="block text-xs font-medium text-gray-500 uppercase tracking-wider mb-1">Seçenekler (JSON)</label>
                                        <input type="text" x-model="field.options_string" :name="field.type === 'select' ? 'fields['+index+'][options]' : ''" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm p-2" placeholder='["Evet","Hayır"] veya {"1":"A"}'>
                                    </div>

                                    <!-- Master Data Tipi (Master Data için) -->
                                    <div class="md:col-span-1" x-show="field.type === 'master_data'">
                                        <label class="block text-xs font-medium text-gray-500 uppercase tracking-wider mb-1">Bağlanacak Master Data</label>
                                        <select x-model="field.master_data_type_id" :name="'fields['+index+'][master_data_type_id]'" :required="field.type === 'master_data'" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm p-2 bg-white">
                                            <option value="">-- Veri Tipi Seçin --</option>
                                            @foreach($masterDataTypes as $mdType)
                                                <option value="{{ $mdType->id }}">{{ $mdType->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <!-- Ekstra Seçenekler Checkbox -->
                                    <div class="md:col-span-4 flex flex-wrap items-center gap-6 mt-2 pt-2 border-t border-gray-200">
                                        <label class="flex items-center text-sm text-gray-700">
                                            <input type="hidden" :name="'fields['+index+'][is_required]'" value="0">
                                            <input type="checkbox" x-model="field.is_required" :name="'fields['+index+'][is_required]'" value="1" class="h-4 w-4 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500 mr-2">
                                            Bu Alan Zorunlu
                                        </label>

                                        <label class="flex items-center text-sm text-gray-700" x-show="field.value_type === 'nicel' && field.type === 'number'">
                                            <input type="hidden" :name="'fields['+index+'][is_summable]'" value="0">
                                            <input type="checkbox" x-model="field.is_summable" :name="'fields['+index+'][is_summable]'" value="1" class="h-4 w-4 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500 mr-2">
                                            Alt Toplamı Alınabilir
                                        </label>
                                        
                                        <label class="flex items-center text-sm text-gray-700" x-show="field.type === 'number' || field.value_type === 'nicel'">
                                            <input type="hidden" :name="'fields['+index+'][is_calculated]'" value="0">
                                            <input type="checkbox" x-model="field.is_calculated" :name="'fields['+index+'][is_calculated]'" value="1" class="h-4 w-4 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500 mr-2">
                                            Hesaplamalı Alan (Formül)
                                        </label>
                                    </div>

                                    <!-- Kullanıcı Listesi (user_list) Özel Ayarları (Yalnızca HTML Tipi Kullanıcı Listesi ise Görünür) -->
                                    <div class="md:col-span-4 mt-3 bg-purple-50/70 p-4 rounded-xl border border-purple-200 shadow-xs flex flex-col gap-3" x-show="field.type === 'user_list'">
                                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 pb-2.5 border-b border-purple-200">
                                            <div class="flex items-center gap-2">
                                                <span class="p-1.5 bg-purple-600 text-white rounded-lg shadow-xs">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                                                </span>
                                                <span class="text-xs font-bold uppercase tracking-wider text-purple-950">Kullanıcı Listesi Özel Ayarları</span>
                                            </div>
                                            <span class="text-[11px] text-purple-700 bg-purple-100 px-2 py-0.5 rounded-full font-medium">Kaynak: Sistem Kullanıcıları</span>
                                        </div>

                                        <!-- Yetki Filtresi Checkbox -->
                                        <label class="flex items-start gap-2.5 p-3 rounded-lg bg-white border border-purple-200 cursor-pointer shadow-xs hover:bg-purple-50 transition">
                                            <input type="hidden" :name="'fields['+index+'][filter_by_user_group]'" value="0">
                                            <input type="checkbox" x-model="field.filter_by_user_group" :name="'fields['+index+'][filter_by_user_group]'" value="1" class="mt-0.5 h-4 w-4 text-purple-600 border-purple-300 rounded focus:ring-purple-500">
                                            <div>
                                                <span class="text-xs font-bold text-purple-950 block">👤 Kullanıcı / Grup Yetkisine Göre Filtrele</span>
                                                <span class="text-[11px] text-purple-600 font-normal block mt-0.5">İşaretlenirse: Grup yöneticisi kendi grubunu ve kendisini, normal kullanıcı yalnızca kendisini seçebilir. İşaretlenmezse: Aşağıda muaf tutulanlar hariç tüm sistem kullanıcıları listede görünür.</span>
                                            </div>
                                        </label>

                                        <!-- Listeden Muaf (Hariç) Tutulacak Kullanıcılar -->
                                        <div class="mt-1 pt-2 border-t border-purple-200/80">
                                            <div class="flex items-center justify-between mb-1.5">
                                                <label class="block text-xs font-bold text-purple-900 uppercase tracking-wider">
                                                    Listeden Muaf Tutulacak (Hariç) Kullanıcılar:
                                                </label>
                                                <span class="text-[11px] font-semibold text-purple-700" x-text="(field.excluded_users && field.excluded_users.length > 0) ? (field.excluded_users.length + ' kullanıcı hariç tutuldu') : 'Tüm kullanıcılar dahil'"></span>
                                            </div>
                                            <p class="text-[11px] text-purple-600 mb-2">Form girişindeki açılır kutuda görünmesini <u>istemediğiniz</u> kullanıcıları işaretleyin (Örn: Sistem Yöneticisi, Yönetim):</p>
                                            
                                            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-2 max-h-44 overflow-y-auto p-2.5 bg-white rounded-lg border border-purple-200">
                                                @if(isset($allUsers))
                                                    @foreach($allUsers as $u)
                                                        <label class="flex items-center gap-2 p-1.5 rounded hover:bg-purple-50 cursor-pointer text-xs transition border border-transparent"
                                                               :class="{'bg-red-50 text-red-900 font-semibold border-red-200': field.excluded_users && field.excluded_users.includes({{ $u->id }})}">
                                                            <input type="checkbox" :value="{{ $u->id }}" x-model="field.excluded_users" class="rounded border-purple-300 text-red-600 focus:ring-red-500">
                                                            <span class="truncate">{{ $u->name }}</span>
                                                            <span class="text-[10px] text-gray-400 font-normal ml-auto">({{ $u->role->name ?? 'Rol Yok' }})</span>
                                                        </label>
                                                    @endforeach
                                                @endif
                                            </div>
                                            
                                            <!-- Gizli input: excluded_user_ids bilgisini options JSON olarak gönder -->
                                            <input type="hidden" :name="field.type === 'user_list' ? 'fields['+index+'][options]' : ''" :value="JSON.stringify({ excluded_user_ids: field.excluded_users || [] })">
                                        </div>
                                    </div>

                                    <!-- Yıl Alanı (year) Özel Ayarları (Yalnızca HTML Tipi Yıl ise Görünür) -->
                                    <div class="md:col-span-4 mt-3 bg-amber-50/70 p-4 rounded-xl border border-amber-200 shadow-xs flex flex-col gap-3" x-show="field.type === 'year'">
                                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 pb-2.5 border-b border-amber-200">
                                            <div class="flex items-center gap-2">
                                                <span class="p-1.5 bg-amber-600 text-white rounded-lg shadow-xs">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                                                </span>
                                                <span class="text-xs font-bold uppercase tracking-wider text-amber-950">Yıl (Year) Seçim Kutusu Ayarları</span>
                                            </div>
                                            <span class="text-[11px] text-amber-800 bg-amber-100 px-2.5 py-0.5 rounded-full font-semibold">Mevcut Sistem Yılı: {{ now()->year }}</span>
                                        </div>

                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                            <!-- Varsayılan Seçili Yıl -->
                                            <div class="bg-white p-3 rounded-lg border border-amber-200">
                                                <label class="block text-xs font-bold text-amber-900 uppercase tracking-wider mb-1">
                                                    Formda / Yeni Satırda Varsayılan Yıl:
                                                </label>
                                                <select x-model="field.year_default" class="w-full rounded-md border-amber-300 shadow-sm focus:border-amber-500 focus:ring-amber-500 text-sm p-2 bg-white">
                                                    <option value="future_1">🎯 Hedef Yılı (Mevcut Yıl + 1: {{ now()->year + 1 }}) [Tavsiye Edilen]</option>
                                                    <option value="current">📅 Bu Yıl (Mevcut Yıl: {{ now()->year }})</option>
                                                    <option value="empty">-- Boş Bırak (Kullanıcı Kendisi Seçsin) --</option>
                                                </select>
                                                <p class="text-[10px] text-amber-700 mt-1.5 leading-relaxed">
                                                    "Hedef Yılı" seçildiğinde kullanıcı her satır eklediğinde <strong>{{ now()->year + 1 }}</strong> otomatik seçili gelir. Gelecek sene sistem yılı {{ now()->year + 1 }} olduğunda form otomatik olarak {{ now()->year + 2 }} yılını varsayılan yapacaktır.
                                                </p>
                                            </div>

                                            <!-- Yıl Aralığı Modu -->
                                            <div class="bg-white p-3 rounded-lg border border-amber-200">
                                                <label class="block text-xs font-bold text-amber-900 uppercase tracking-wider mb-1">
                                                    Yıl Aralığı (Hangi Yıllar Listelensin?):
                                                </label>
                                                <select x-model="field.year_mode" class="w-full rounded-md border-amber-300 shadow-sm focus:border-amber-500 focus:ring-amber-500 text-sm p-2 bg-white mb-2">
                                                    <option value="dynamic">✨ Dinamik Akıllı Aralık (Mevcut Yıla Göre Otomatik Kayar)</option>
                                                    <option value="custom">✏️ Sabit Yıl Listesi (Elle Belirle)</option>
                                                </select>

                                                <!-- Dinamik Aralık Ayarları -->
                                                <div x-show="field.year_mode === 'dynamic'" class="flex items-center gap-2 text-xs text-amber-900 bg-amber-50/70 p-2 rounded border border-amber-200">
                                                    <span>Geçmiş:</span>
                                                    <input type="number" min="0" max="10" x-model="field.year_past" class="w-16 rounded border-amber-300 p-1 text-center text-xs">
                                                    <span>yıl</span>
                                                    <span class="mx-1 font-bold text-amber-400">|</span>
                                                    <span>Gelecek:</span>
                                                    <input type="number" min="0" max="10" x-model="field.year_future" class="w-16 rounded border-amber-300 p-1 text-center text-xs">
                                                    <span>yıl</span>
                                                </div>

                                                <!-- Sabit Yıl Listesi -->
                                                <div x-show="field.year_mode === 'custom'" class="mt-1">
                                                    <input type="text" x-model="field.year_custom_list" placeholder="Örn: 2024, 2025, 2026, 2027, 2028, 2029" class="w-full rounded border-amber-300 p-1.5 text-xs">
                                                    <p class="text-[10px] text-amber-600 mt-1">Virgülle ayırarak listelenecek yılları yazınız.</p>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Canlı Önizleme -->
                                        <div class="bg-amber-100/70 p-2.5 rounded-lg border border-amber-300/80 flex flex-col sm:flex-row sm:items-center justify-between gap-1 text-xs text-amber-950">
                                            <div>
                                                <span class="font-bold">Açılır Kutuda Çıkacak Yıllar: </span>
                                                <span class="font-mono bg-white/80 px-2 py-0.5 rounded border border-amber-200 font-semibold text-amber-900" x-text="getYearPreviewList(field).join(', ')"></span>
                                            </div>
                                            <span class="text-[11px] text-amber-800 italic">(Geciken hedefler için geriye dönük seçim imkanı sağlanır)</span>
                                        </div>

                                        <!-- Gizli input: year konfigürasyonunu options JSON olarak gönder -->
                                        <input type="hidden" :name="field.type === 'year' ? 'fields['+index+'][options]' : ''" :value="JSON.stringify({
                                            year_mode: field.year_mode || 'dynamic',
                                            year_default: field.year_default || 'future_1',
                                            year_past: field.year_past !== undefined ? field.year_past : 1,
                                            year_future: field.year_future !== undefined ? field.year_future : 2,
                                            year_custom_list: field.year_custom_list || ''
                                        })">
                                    </div>
                                    
                                    <!-- Hesaplamalı Alan (Formül) Bölümü -->
                                    <div class="md:col-span-4 mt-3 bg-blue-50 p-4 rounded-lg border border-blue-200 flex flex-col gap-2 shadow-sm" x-show="field.is_calculated && (field.type === 'number' || field.value_type === 'nicel')">
                                        <label class="block text-xs font-bold text-blue-800 uppercase tracking-wider mb-1">Formül Tanımlama</label>
                                        <div class="flex flex-col gap-2">
                                            <div class="flex flex-col md:flex-row items-center gap-2 w-full">
                                                <select x-model="field._temp_formula_field" class="w-full md:w-1/3 rounded-md border-blue-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm p-2 bg-white">
                                                    <option value="">-- Alan Seçip Ekle --</option>
                                                    <template x-for="otherField in getAvailableFormulaFields(field)" :key="otherField.id">
                                                        <option :value="'#{' + otherField.id + '}#'" x-text="otherField.label"></option>
                                                    </template>
                                                </select>
                                                <button type="button" @click="if(field._temp_formula_field) { field.calculation_formula = (field.calculation_formula || '') + ' ' + field._temp_formula_field + ' '; field._temp_formula_field = ''; }" class="bg-blue-600 hover:bg-blue-700 text-white px-3 py-2 rounded-md text-sm font-semibold transition shadow-sm whitespace-nowrap">
                                                    Formüle Ekle
                                                </button>
                                            </div>
                                            <div class="text-[10px] text-blue-600 mt-1">Matematiksel işlemleri (+, -, *, /) ve sabit sayıları doğrudan aşağıya yazabilirsiniz. (Örn: #{12}# * 1.18 + 50)</div>
                                            <textarea x-model="field.calculation_formula" :name="'fields['+index+'][calculation_formula]'" class="w-full rounded-md border-blue-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm p-2 font-mono" rows="2" placeholder="Örn: #{12}# * #{15}#"></textarea>
                                        </div>
                                        
                                        <!-- Koşullu Hesaplama Bölümü -->
                                        <div class="mt-4 pt-3 border-t border-blue-200 flex flex-col md:flex-row gap-4">
                                            <div class="w-full md:w-1/2">
                                                <label class="block text-xs font-bold text-blue-800 uppercase tracking-wider mb-1">Koşullu Hesaplama (Şart Var Mı?)</label>
                                                <select x-model="field.calc_condition_field_id" :name="'fields['+index+'][calc_condition_field_id]'" class="w-full rounded-md border-blue-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm p-2 bg-white">
                                                    <option value="">-- Her Zaman Hesaplansın --</option>
                                                    <template x-for="otherField in getAvailableConditionFields(field)" :key="otherField.id">
                                                        <option :value="otherField.id" x-text="otherField.label"></option>
                                                    </template>
                                                </select>
                                                <p class="text-[10px] text-blue-600 mt-1">Sadece belirli bir şart sağlandığında bu formül çalışır. Sağlanmadığında alan elle doldurulur.</p>
                                            </div>
                                            
                                            <div class="w-full md:w-1/2" x-show="field.calc_condition_field_id">
                                                <label class="block text-xs font-bold text-blue-800 uppercase tracking-wider mb-1">Hangi Değer Seçilirse Formül Çalışsın?</label>
                                                
                                                <!-- Master Data (Çoklu Seçim Dropdown) -->
                                                <template x-if="getConditionFieldType(field.calc_condition_field_id) === 'master_data'">
                                                    <div x-data="{ open: false }" class="relative w-full">
                                                        <div @click="open = !open" class="w-full rounded-md border-blue-300 border shadow-sm text-sm p-2 bg-white cursor-pointer flex justify-between items-center focus-within:ring-1 focus-within:ring-blue-500">
                                                            <div class="truncate pr-4" x-text="field.calc_condition_value_array.length ? field.calc_condition_value_array.join(', ') : 'Seçim yapın...'"></div>
                                                            <svg class="w-4 h-4 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                                                        </div>
                                                        <div x-show="open" @click.away="open = false" style="display: none;" class="absolute z-50 mt-1 w-full bg-white border border-gray-300 rounded-md shadow-lg max-h-56 overflow-y-auto">
                                                            <!-- Toplu Seçim Barı -->
                                                            <div class="px-3 py-2 border-b border-blue-200 bg-blue-100 flex items-center justify-between sticky top-0 z-10 shadow-xs" x-show="getConditionFieldMasterDataItems(field.calc_condition_field_id).length > 0">
                                                                <label class="flex items-center text-xs font-bold text-blue-950 cursor-pointer select-none">
                                                                    <input type="checkbox" :checked="isAllMasterDataSelected(field, field.calc_condition_field_id, true)" @change="toggleAllMasterData(field, field.calc_condition_field_id, $event.target.checked, true)" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500 mr-2">
                                                                    <span>Tümünü Seç</span>
                                                                </label>
                                                                <button type="button" @click="field.calc_condition_value_array = []" class="text-[11px] font-bold text-blue-700 hover:text-red-700 transition" x-show="field.calc_condition_value_array && field.calc_condition_value_array.length > 0">
                                                                    Seçimi Temizle (<span x-text="field.calc_condition_value_array.length"></span>)
                                                                </button>
                                                            </div>
                                                            <template x-for="item in getConditionFieldMasterDataItems(field.calc_condition_field_id)" :key="item.id">
                                                                <label class="flex items-center px-3 py-2 hover:bg-blue-50 cursor-pointer border-b border-gray-100 last:border-0">
                                                                    <input type="checkbox" x-model="field.calc_condition_value_array" :value="item.name" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500 mr-2">
                                                                    <span x-text="item.name" class="text-sm text-gray-700"></span>
                                                                </label>
                                                            </template>
                                                            <div x-show="getConditionFieldMasterDataItems(field.calc_condition_field_id).length === 0" class="px-3 py-2 text-sm text-gray-500 italic">Veri bulunamadı</div>
                                                        </div>
                                                        <template x-for="val in field.calc_condition_value_array">
                                                            <input type="hidden" :name="'fields['+index+'][calc_condition_value][]'" :value="val">
                                                        </template>
                                                    </div>
                                                </template>
                                                
                                                <!-- Select (Çoklu Seçim Dropdown) -->
                                                <template x-if="getConditionFieldType(field.calc_condition_field_id) === 'select'">
                                                    <div x-data="{ open: false }" class="relative w-full">
                                                        <div @click="open = !open" class="w-full rounded-md border-blue-300 border shadow-sm text-sm p-2 bg-white cursor-pointer flex justify-between items-center focus-within:ring-1 focus-within:ring-blue-500">
                                                            <div class="truncate pr-4" x-text="field.calc_condition_value_array.length ? field.calc_condition_value_array.join(', ') : 'Seçim yapın...'"></div>
                                                            <svg class="w-4 h-4 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                                                        </div>
                                                        <div x-show="open" @click.away="open = false" style="display: none;" class="absolute z-50 mt-1 w-full bg-white border border-gray-300 rounded-md shadow-lg max-h-56 overflow-y-auto">
                                                            <!-- Toplu Seçim Barı -->
                                                            <div class="px-3 py-2 border-b border-blue-200 bg-blue-100 flex items-center justify-between sticky top-0 z-10 shadow-xs" x-show="getConditionFieldSelectOptions(field.calc_condition_field_id).length > 0">
                                                                <label class="flex items-center text-xs font-bold text-blue-950 cursor-pointer select-none">
                                                                    <input type="checkbox" :checked="isAllSelectOptionsSelected(field, field.calc_condition_field_id, true)" @change="toggleAllSelectOptions(field, field.calc_condition_field_id, $event.target.checked, true)" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500 mr-2">
                                                                    <span>Tümünü Seç</span>
                                                                </label>
                                                                <button type="button" @click="field.calc_condition_value_array = []" class="text-[11px] font-bold text-blue-700 hover:text-red-700 transition" x-show="field.calc_condition_value_array && field.calc_condition_value_array.length > 0">
                                                                    Seçimi Temizle (<span x-text="field.calc_condition_value_array.length"></span>)
                                                                </button>
                                                            </div>
                                                            <template x-for="opt in getConditionFieldSelectOptions(field.calc_condition_field_id)" :key="opt">
                                                                <label class="flex items-center px-3 py-2 hover:bg-blue-50 cursor-pointer border-b border-gray-100 last:border-0">
                                                                    <input type="checkbox" x-model="field.calc_condition_value_array" :value="opt" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500 mr-2">
                                                                    <span x-text="opt" class="text-sm text-gray-700"></span>
                                                                </label>
                                                            </template>
                                                            <div x-show="getConditionFieldSelectOptions(field.calc_condition_field_id).length === 0" class="px-3 py-2 text-sm text-gray-500 italic">Veri bulunamadı</div>
                                                        </div>
                                                        <template x-for="val in field.calc_condition_value_array">
                                                            <input type="hidden" :name="'fields['+index+'][calc_condition_value][]'" :value="val">
                                                        </template>
                                                    </div>
                                                </template>
                                                
                                                <!-- Normal Metin Kutusu (Diğerleri) -->
                                                <template x-if="getConditionFieldType(field.calc_condition_field_id) !== 'master_data' && getConditionFieldType(field.calc_condition_field_id) !== 'select'">
                                                    <input type="text" x-model="field.calc_condition_value" :name="'fields['+index+'][calc_condition_value]'" class="w-full rounded-md border-blue-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm p-2" placeholder="Örn: Değer (Çoklu değer için virgül kullanın)">
                                                </template>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Koşullu Zorunluluk Bölümü -->
                                    <div class="md:col-span-4 mt-3 bg-orange-50 p-4 rounded-lg border border-orange-200 flex flex-col md:flex-row gap-4 shadow-sm" x-show="!field.is_required">
                                        <div class="w-full md:w-1/2">
                                            <label class="block text-xs font-bold text-orange-800 uppercase tracking-wider mb-1">Koşullu Zorunluluk (Hangi Alana Bağlı?)</label>
                                            <select x-model="field.condition_field_id" :name="'fields['+index+'][condition_field_id]'" class="w-full rounded-md border-orange-300 shadow-sm focus:border-orange-500 focus:ring-orange-500 text-sm p-2 bg-white">
                                                <option value="">-- Şart Yok (Zorunlu Değil) --</option>
                                                <template x-for="otherField in getAvailableConditionFields(field)" :key="otherField.id">
                                                    <option :value="otherField.id" x-text="otherField.label"></option>
                                                </template>
                                            </select>
                                        </div>
                                        <div class="w-full md:w-1/2" x-show="field.condition_field_id">
                                            <label class="block text-xs font-bold text-orange-800 uppercase tracking-wider mb-1">Hangi Değer Seçilirse Zorunlu Olsun?</label>
                                            
                                            <!-- Master Data (Çoklu Seçim Dropdown) -->
                                            <template x-if="getConditionFieldType(field.condition_field_id) === 'master_data'">
                                                <div x-data="{ open: false }" class="relative w-full">
                                                    <div @click="open = !open" class="w-full rounded-md border-orange-300 border shadow-sm text-sm p-2 bg-white cursor-pointer flex justify-between items-center focus-within:ring-1 focus-within:ring-orange-500">
                                                        <div class="truncate pr-4" x-text="field.condition_value_array.length ? field.condition_value_array.join(', ') : 'Seçim yapın...'"></div>
                                                        <svg class="w-4 h-4 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                                                    </div>
                                                    <div x-show="open" @click.away="open = false" style="display: none;" class="absolute z-50 mt-1 w-full bg-white border border-gray-300 rounded-md shadow-lg max-h-56 overflow-y-auto">
                                                        <!-- Toplu Seçim Barı -->
                                                        <div class="px-3 py-2 border-b border-orange-200 bg-orange-100 flex items-center justify-between sticky top-0 z-10 shadow-xs" x-show="getConditionFieldMasterDataItems(field.condition_field_id).length > 0">
                                                            <label class="flex items-center text-xs font-bold text-orange-950 cursor-pointer select-none">
                                                                <input type="checkbox" :checked="isAllMasterDataSelected(field, field.condition_field_id, false)" @change="toggleAllMasterData(field, field.condition_field_id, $event.target.checked, false)" class="rounded border-gray-300 text-orange-600 focus:ring-orange-500 mr-2">
                                                                <span>Tümünü Seç</span>
                                                            </label>
                                                            <button type="button" @click="field.condition_value_array = []" class="text-[11px] font-bold text-orange-700 hover:text-red-700 transition" x-show="field.condition_value_array && field.condition_value_array.length > 0">
                                                                Seçimi Temizle (<span x-text="field.condition_value_array.length"></span>)
                                                            </button>
                                                        </div>
                                                        <template x-for="item in getConditionFieldMasterDataItems(field.condition_field_id)" :key="item.id">
                                                            <label class="flex items-center px-3 py-2 hover:bg-orange-50 cursor-pointer border-b border-gray-100 last:border-0">
                                                                <input type="checkbox" x-model="field.condition_value_array" :value="item.name" class="rounded border-gray-300 text-orange-600 focus:ring-orange-500 mr-2">
                                                                <span x-text="item.name" class="text-sm text-gray-700"></span>
                                                            </label>
                                                        </template>
                                                        <div x-show="getConditionFieldMasterDataItems(field.condition_field_id).length === 0" class="px-3 py-2 text-sm text-gray-500 italic">Veri bulunamadı</div>
                                                    </div>
                                                    <template x-for="val in field.condition_value_array">
                                                        <input type="hidden" :name="'fields['+index+'][condition_value][]'" :value="val">
                                                    </template>
                                                </div>
                                            </template>
                                            
                                            <!-- Select (Çoklu Seçim Dropdown) -->
                                            <template x-if="getConditionFieldType(field.condition_field_id) === 'select'">
                                                <div x-data="{ open: false }" class="relative w-full">
                                                    <div @click="open = !open" class="w-full rounded-md border-orange-300 border shadow-sm text-sm p-2 bg-white cursor-pointer flex justify-between items-center focus-within:ring-1 focus-within:ring-orange-500">
                                                        <div class="truncate pr-4" x-text="field.condition_value_array.length ? field.condition_value_array.join(', ') : 'Seçim yapın...'"></div>
                                                        <svg class="w-4 h-4 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                                                    </div>
                                                    <div x-show="open" @click.away="open = false" style="display: none;" class="absolute z-50 mt-1 w-full bg-white border border-gray-300 rounded-md shadow-lg max-h-56 overflow-y-auto">
                                                        <!-- Toplu Seçim Barı -->
                                                        <div class="px-3 py-2 border-b border-orange-200 bg-orange-100 flex items-center justify-between sticky top-0 z-10 shadow-xs" x-show="getConditionFieldSelectOptions(field.condition_field_id).length > 0">
                                                            <label class="flex items-center text-xs font-bold text-orange-950 cursor-pointer select-none">
                                                                <input type="checkbox" :checked="isAllSelectOptionsSelected(field, field.condition_field_id, false)" @change="toggleAllSelectOptions(field, field.condition_field_id, $event.target.checked, false)" class="rounded border-gray-300 text-orange-600 focus:ring-orange-500 mr-2">
                                                                <span>Tümünü Seç</span>
                                                            </label>
                                                            <button type="button" @click="field.condition_value_array = []" class="text-[11px] font-bold text-orange-700 hover:text-red-700 transition" x-show="field.condition_value_array && field.condition_value_array.length > 0">
                                                                Seçimi Temizle (<span x-text="field.condition_value_array.length"></span>)
                                                            </button>
                                                        </div>
                                                        <template x-for="opt in getConditionFieldSelectOptions(field.condition_field_id)" :key="opt">
                                                            <label class="flex items-center px-3 py-2 hover:bg-orange-50 cursor-pointer border-b border-gray-100 last:border-0">
                                                                <input type="checkbox" x-model="field.condition_value_array" :value="opt" class="rounded border-gray-300 text-orange-600 focus:ring-orange-500 mr-2">
                                                                <span x-text="opt" class="text-sm text-gray-700"></span>
                                                            </label>
                                                        </template>
                                                        <div x-show="getConditionFieldSelectOptions(field.condition_field_id).length === 0" class="px-3 py-2 text-sm text-gray-500 italic">Veri bulunamadı</div>
                                                    </div>
                                                    <template x-for="val in field.condition_value_array">
                                                        <input type="hidden" :name="'fields['+index+'][condition_value][]'" :value="val">
                                                    </template>
                                                </div>
                                            </template>
                                            
                                            <!-- Normal Metin Kutusu (Diğerleri) -->
                                            <template x-if="getConditionFieldType(field.condition_field_id) !== 'master_data' && getConditionFieldType(field.condition_field_id) !== 'select'">
                                                <input type="text" x-model="field.condition_value" :name="'fields['+index+'][condition_value]'" class="w-full rounded-md border-orange-300 shadow-sm focus:border-orange-500 focus:ring-orange-500 text-sm p-2" placeholder="Örn: Şirket İçi (Çoklu değer için virgül kullanın)">
                                            </template>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </template>

                        <div x-show="fields.length === 0" class="text-center py-12 bg-gray-50 rounded-lg border-2 border-dashed border-gray-300">
                            <p class="text-gray-500">Henüz hiçbir alan eklemediniz.</p>
                            <button type="button" @click="addField()" class="mt-4 text-indigo-600 hover:text-indigo-800 font-medium">İlk alanı eklemek için tıklayın</button>
                        </div>
                    </div>

                    <!-- Sabit Alt Bar (Toolbar) -->
                    <div class="fixed bottom-0 left-0 right-0 z-40 bg-white/95 backdrop-blur-md border-t border-gray-200 shadow-[0_-4px_25px_rgba(0,0,0,0.1)] py-3.5 px-4 sm:px-8">
                        <div class="max-w-7xl mx-auto flex justify-between items-center">
                            <button type="button" @click="addField()" class="bg-indigo-50 text-indigo-700 hover:bg-indigo-100 font-bold py-2.5 px-6 rounded-lg shadow-sm transition border border-indigo-200 flex items-center gap-2">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg>
                                <span>+ Yeni Alan Ekle</span>
                            </button>
                            
                            <!-- Orta: Alana Hızlı Atla -->
                            <div class="hidden md:flex items-center gap-2" x-show="fields.length > 0">
                                <span class="text-xs font-semibold text-slate-500">Alana Atla:</span>
                                <select @change="if($event.target.value !== '') { scrollToField($event.target.value); $event.target.value = ''; }" class="text-xs rounded-lg border-slate-300 bg-white text-slate-700 py-1.5 pl-2.5 pr-8 shadow-xs focus:ring-indigo-500 focus:border-indigo-500 max-w-[220px] truncate cursor-pointer">
                                    <option value="">-- Alana Git --</option>
                                    <template x-for="(f, i) in fields" :key="i">
                                        <option :value="i" x-text="(i + 1) + '. ' + (f.label || 'İsimsiz')"></option>
                                    </template>
                                </select>
                            </div>
                            
                            <div class="flex items-center gap-4">
                                <span class="text-xs text-gray-500 font-medium hidden sm:inline" x-text="fields.length + ' adet alan tanımlı'"></span>
                                <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2.5 px-8 rounded-lg shadow-lg hover:shadow-indigo-500/30 transition ease-in-out duration-150 transform hover:-translate-y-0.5 flex items-center gap-2">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                    <span>Tüm Alanları Kaydet</span>
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- AlpineJS Logic -->
    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('formBuilder', (initialFields, initialAllowPartition, initialPartitionFieldId) => ({
                fields: [],
                allowPartition: initialAllowPartition || false,
                partitionFieldId: (initialPartitionFieldId !== null && initialPartitionFieldId !== undefined) ? String(initialPartitionFieldId) : '',
                masterDataTypes: @json($masterDataTypes),

                getFieldTypeLabel(type) {
                    const types = {
                        'text': 'Kısa Metin',
                        'number': 'Sayı',
                        'textarea': 'Uzun Metin',
                        'select': 'Seçim Listesi',
                        'date': 'Tarih',
                        'year': 'Yıl',
                        'auto_user': 'Otomatik Personel',
                        'user_list': 'Personel Listesi',
                        'master_data': 'Master Data'
                    };
                    return types[type] || type || 'Metin';
                },

                get selectablePartitionFields() {
                    return this.fields.map((f, idx) => {
                        let key = (f.id !== undefined && f.id !== null && f.id !== '') ? String(f.id) : (f._uid || ('index:' + idx));
                        let name = (f.label && f.label.trim() !== '') ? f.label.trim() : ('(İsimsiz Alan ' + (idx + 1) + ')');
                        let typeName = this.getFieldTypeLabel(f.type);
                        return {
                            ...f,
                            _key: key,
                            _idx: idx,
                            display_label: name,
                            type_label: typeName
                        };
                    });
                },
                
                getConditionFieldType(id) {
                    let f = this.fields.find(f => f.id == id);
                    return f ? f.type : null;
                },
                getConditionFieldMasterDataItems(id) {
                    let f = this.fields.find(f => f.id == id);
                    if (!f || !f.master_data_type_id) return [];
                    let mdType = this.masterDataTypes.find(m => m.id == f.master_data_type_id);
                    return mdType ? mdType.items : [];
                },
                getConditionFieldSelectOptions(id) {
                    let f = this.fields.find(f => f.id == id);
                    if (!f || !f.options_string) return [];
                    try {
                        let opts = JSON.parse(f.options_string);
                        if (Array.isArray(opts)) return opts;
                        return Object.values(opts);
                    } catch(e) {
                        return [];
                    }
                },
                getAvailableConditionFields(currentField) {
                    return this.fields.filter(f => f.id && f.id != currentField.id && f.label && f.label.trim() !== '');
                },
                getAvailableFormulaFields(currentField) {
                    return this.fields.filter(f => f.id && f.id != currentField.id && (f.value_type === 'nicel' || f.type === 'number') && f.label && f.label.trim() !== '');
                },

                // Toplu Seçim Fonksiyonları (Master Data)
                isAllMasterDataSelected(field, fieldId, isCalc = false) {
                    let items = this.getConditionFieldMasterDataItems(fieldId).map(i => i.name);
                    let current = (isCalc ? field.calc_condition_value_array : field.condition_value_array) || [];
                    return items.length > 0 && items.every(name => current.includes(name));
                },
                toggleAllMasterData(field, fieldId, checkAll, isCalc = false) {
                    let items = this.getConditionFieldMasterDataItems(fieldId).map(i => i.name);
                    let key = isCalc ? 'calc_condition_value_array' : 'condition_value_array';
                    if (!Array.isArray(field[key])) {
                        field[key] = [];
                    }
                    if (checkAll) {
                        field[key] = [...new Set([...field[key], ...items])];
                    } else {
                        field[key] = field[key].filter(name => !items.includes(name));
                    }
                },

                // Toplu Seçim Fonksiyonları (Select Seçenekleri)
                isAllSelectOptionsSelected(field, fieldId, isCalc = false) {
                    let opts = this.getConditionFieldSelectOptions(fieldId);
                    let current = (isCalc ? field.calc_condition_value_array : field.condition_value_array) || [];
                    return opts.length > 0 && opts.every(opt => current.includes(opt));
                },
                toggleAllSelectOptions(field, fieldId, checkAll, isCalc = false) {
                    let opts = this.getConditionFieldSelectOptions(fieldId);
                    let key = isCalc ? 'calc_condition_value_array' : 'condition_value_array';
                    if (!Array.isArray(field[key])) {
                        field[key] = [];
                    }
                    if (checkAll) {
                        field[key] = [...new Set([...field[key], ...opts])];
                    } else {
                        field[key] = field[key].filter(opt => !opts.includes(opt));
                    }
                },

                getYearPreviewList(field) {
                    const currentYear = new Date().getFullYear();
                    if (field.year_mode === 'custom' && field.year_custom_list) {
                        let custom = field.year_custom_list.split(',').map(y => y.trim()).filter(Boolean);
                        return custom.length > 0 ? custom : [currentYear];
                    }
                    const past = parseInt(field.year_past !== undefined ? field.year_past : 1);
                    const future = parseInt(field.year_future !== undefined ? field.year_future : 2);
                    let years = [];
                    for (let y = currentYear - past; y <= currentYear + future; y++) {
                        years.push(y);
                    }
                    return years;
                },

                init() {
                    if (initialFields && initialFields.length > 0) {
                        this.fields = initialFields.map((f, idx) => {
                            let uid = (f.id !== undefined && f.id !== null && f.id !== '') ? String(f.id) : ('uid_' + Date.now() + '_' + idx);
                            let excludedUsers = [];
                            let yearMode = 'dynamic';
                            let yearDefault = 'future_1';
                            let yearPast = 1;
                            let yearFuture = 2;
                            let yearCustomList = '';

                            if (f.options) {
                                let opts = typeof f.options === 'object' ? f.options : null;
                                if (!opts) {
                                    try { opts = JSON.parse(f.options); } catch(e) {}
                                }
                                if (opts) {
                                    if (opts.excluded_user_ids && Array.isArray(opts.excluded_user_ids)) {
                                        excludedUsers = opts.excluded_user_ids.map(id => parseInt(id));
                                    }
                                    if (opts.year_mode) yearMode = opts.year_mode;
                                    if (opts.year_default) yearDefault = opts.year_default;
                                    if (opts.year_past !== undefined) yearPast = opts.year_past;
                                    if (opts.year_future !== undefined) yearFuture = opts.year_future;
                                    if (opts.year_custom_list) yearCustomList = opts.year_custom_list;
                                }
                            }

                            return {
                                ...f,
                                _uid: uid,
                                is_required: f.is_required == 1,
                                is_summable: f.is_summable == 1,
                                condition_field_id: f.condition_field_id || '',
                                condition_value: f.condition_value || '',
                                condition_value_array: f.condition_value ? f.condition_value.split(',') : [],
                                is_calculated: f.is_calculated == 1,
                                calculation_formula: f.calculation_formula || '',
                                calc_condition_field_id: f.calc_condition_field_id || '',
                                calc_condition_value: f.calc_condition_value || '',
                                calc_condition_value_array: f.calc_condition_value ? f.calc_condition_value.split(',') : [],
                                options_string: f.options ? (typeof f.options === 'string' ? f.options : JSON.stringify(f.options)) : '',
                                master_data_type_id: f.master_data_type_id || '',
                                filter_by_user_group: f.filter_by_user_group == 1,
                                excluded_users: excludedUsers,
                                year_mode: yearMode,
                                year_default: yearDefault,
                                year_past: yearPast,
                                year_future: yearFuture,
                                year_custom_list: yearCustomList
                            };
                        });
                    }
                },
                addField() {
                    this.fields.push({
                        _uid: 'uid_' + Date.now() + '_' + Math.random().toString(36).substr(2, 7),
                        label: '',
                        type: 'text',
                        value_type: 'nitel',
                        options_string: '',
                        master_data_type_id: '',
                        filter_by_user_group: false,
                        excluded_users: [],
                        year_mode: 'dynamic',
                        year_default: 'future_1',
                        year_past: 1,
                        year_future: 2,
                        year_custom_list: '',
                        is_required: false,
                        is_summable: false,
                        is_calculated: false,
                        calculation_formula: '',
                        calc_condition_field_id: '',
                        calc_condition_value: '',
                        calc_condition_value_array: [],
                        condition_field_id: '',
                        condition_value: '',
                        condition_value_array: []
                    });

                    this.$nextTick(() => {
                        window.scrollTo({
                            top: document.body.scrollHeight,
                            behavior: 'smooth'
                        });
                        setTimeout(() => {
                            const inputs = document.querySelectorAll('input[placeholder="Örn: Miktar"]');
                            if (inputs.length > 0) {
                                inputs[inputs.length - 1].focus();
                            }
                        }, 300);
                    });
                },
                removeField(index) {
                    const removed = this.fields[index];
                    if (removed) {
                        const removedKey = (removed.id !== undefined && removed.id !== null && removed.id !== '') ? String(removed.id) : (removed._uid || ('index:' + index));
                        if (String(this.partitionFieldId) === String(removedKey)) {
                            this.partitionFieldId = '';
                        }
                    }
                    this.fields.splice(index, 1);
                },
                moveUp(index) {
                    if (index > 0) {
                        const field = this.fields.splice(index, 1)[0];
                        this.fields.splice(index - 1, 0, field);
                    }
                },
                moveDown(index) {
                    if (index < this.fields.length - 1) {
                        const field = this.fields.splice(index, 1)[0];
                        this.fields.splice(index + 1, 0, field);
                    }
                },
                scrollToField(index) {
                    const el = document.getElementById('field-card-' + index);
                    if (el) {
                        el.scrollIntoView({ behavior: 'smooth', block: 'center' });
                        
                        // Görsel Odaklama Efekti (Indigo Ring & Glow)
                        el.classList.add('ring-4', 'ring-indigo-500', 'bg-indigo-50/70');
                        setTimeout(() => {
                            el.classList.remove('ring-4', 'ring-indigo-500', 'bg-indigo-50/70');
                        }, 1800);

                        // Başlık inputuna odaklan
                        setTimeout(() => {
                            const input = el.querySelector('input[placeholder="Örn: Miktar"]');
                            if (input) {
                                input.focus();
                            }
                        }, 300);
                    }
                }
            }));
        });
    </script>
</x-app-layout>
