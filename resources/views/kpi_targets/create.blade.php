@section('title', 'Yeni KPI Hedefi Tanımla')
<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="font-extrabold text-2xl text-gray-900 tracking-tight flex items-center gap-2">
                    <a href="{{ route('kpi-targets.index') }}" class="text-gray-400 hover:text-gray-600 transition">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                    </a>
                    {{ __('Yeni Dinamik KPI Hedefi Tanımla') }}
                </h2>
                <p class="text-sm text-gray-500 mt-1">Dinamik form alanlarını seçerek kurumsal veya bireysel hedef belirleyin</p>
            </div>
        </div>
    </x-slot>

    <div class="py-8" x-data="kpiTargetWizard()">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">

            @if(isset($errors) && $errors->any())
                <div class="p-4 mb-6 text-sm text-rose-800 bg-rose-50 border border-rose-200 rounded-2xl" role="alert">
                    <ul class="list-disc pl-5 space-y-1">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form action="{{ route('kpi-targets.store') }}" method="POST" @submit="isSubmitting = true" class="space-y-6">
                @csrf

                {{-- Kart 1: Temel Bilgiler ve Form Alanı Seçimi --}}
                <div class="bg-white rounded-2xl shadow-xs border border-gray-100 p-6 space-y-5">
                    <h3 class="text-base font-extrabold text-gray-900 border-b border-gray-100 pb-3 flex items-center gap-2">
                        <span class="w-6 h-6 rounded-md bg-indigo-100 text-indigo-700 flex items-center justify-center text-xs font-black">1</span>
                        Hedef Kaynağı & Alanı
                    </h3>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        {{-- Form Şablonu --}}
                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-1">
                                Form Şablonu <span class="text-rose-500">*</span>
                            </label>
                            <select name="form_template_id" x-model="selectedTemplate" @change="fetchFields()" required
                                class="w-full text-sm rounded-xl border-gray-200 focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="">-- Şablon Seçiniz --</option>
                                @foreach($templates as $t)
                                    <option value="{{ $t->id }}">{{ $t->name }}</option>
                                @endforeach
                            </select>
                            <p class="text-[11px] text-gray-400 mt-1">Hedef verisinin toplanacağı form yapısı</p>
                        </div>

                        {{-- Hedef Alanı (Sayısal Alanlar) --}}
                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-1">
                                Hedef Alanı (Sayısal Sütun) <span class="text-rose-500">*</span>
                            </label>
                            <select name="target_field_id" x-model="selectedTargetField" required :disabled="numericFields.length === 0"
                                class="w-full text-sm rounded-xl border-gray-200 focus:border-indigo-500 focus:ring-indigo-500 disabled:bg-gray-100 disabled:text-gray-400">
                                <option value="">-- Önce Şablon Seçiniz --</option>
                                <template x-for="f in numericFields" :key="f.id">
                                    <option :value="f.id" x-text="f.label + ' (' + (f.field_key) + ')'"></option>
                                </template>
                            </select>
                            <p class="text-[11px] text-gray-400 mt-1">Örn: TONAJ, TESLİMAT MİKTARI</p>
                        </div>
                    </div>

                    {{-- Hedef Başlığı & Açıklama --}}
                    <div>
                        <label class="block text-xs font-bold text-gray-700 mb-1">
                            Hedef Başlığı <span class="text-rose-500">*</span>
                        </label>
                        <input type="text" name="title" required placeholder="Örn: 2027 Preform Satış Tonajı Hedefi"
                            class="w-full text-sm rounded-xl border-gray-200 focus:border-indigo-500 focus:ring-indigo-500">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-700 mb-1">Açıklama / Notlar</label>
                        <textarea name="description" rows="2" placeholder="Hedefin amacı ve detayları..."
                            class="w-full text-sm rounded-xl border-gray-200 focus:border-indigo-500 focus:ring-indigo-500"></textarea>
                    </div>
                </div>

                {{-- Kart 2: Kriter / Kırılım Filtreleri (Master Data Bağımlı Cascading) --}}
                <div class="bg-white rounded-2xl shadow-xs border border-gray-100 p-6 space-y-4" x-show="filterFields.length > 0">
                    <div class="flex items-center justify-between border-b border-gray-100 pb-3">
                        <h3 class="text-base font-extrabold text-gray-900 flex items-center gap-2">
                            <span class="w-6 h-6 rounded-md bg-purple-100 text-purple-700 flex items-center justify-center text-xs font-black">2</span>
                            Hedef Kırılımı & Kriterler (İsteğe Bağlı)
                        </h3>
                        <span class="text-xs text-gray-400">Genel hedef için boş bırakabilirsiniz</span>
                    </div>

                    <p class="text-xs text-gray-500">
                        Bu hedefi belirli bir <strong>İşyeri (örn: Preform)</strong>, <strong>Renk (örn: Sarı)</strong> veya <strong>Gramaj (örn: 25g)</strong> gibi spesifik kırılımlara kısıtlamak istiyorsanız aşağıdaki filtreleri doldurabilirsiniz. Alanlar Master Data'da tanımlanan kurallara göre birbirine bağlı (cascading) olarak filtrelenir.
                    </p>

                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 pt-2">
                        <template x-for="field in filterFields" :key="field.id">
                            <div class="p-3 rounded-xl bg-slate-50 border border-slate-200/70 space-y-1.5 transition"
                                :class="isParentRequired(field) && !isParentSelected(field) ? 'opacity-70 bg-slate-100/60' : ''">
                                
                                <div class="flex items-center justify-between">
                                    <label class="block text-xs font-bold text-gray-800" x-text="field.label"></label>
                                    <template x-if="isParentRequired(field)">
                                        <span class="text-[9px] font-bold text-amber-700 bg-amber-50 px-1.5 py-0.5 rounded border border-amber-200/80">
                                            Bağımlı Alan
                                        </span>
                                    </template>
                                </div>

                                {{-- Master data veya Select ise bağımlı dinamik seçenekler --}}
                                <template x-if="field.type === 'master_data' || field.type === 'select'">
                                    <div>
                                        <select :name="'criteria[' + field.id + ']'"
                                            x-model="criteria[field.id]"
                                            @change="handleCriteriaChange(field.id)"
                                            :disabled="isParentRequired(field) && !isParentSelected(field)"
                                            class="w-full text-xs rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 bg-white disabled:bg-gray-100 disabled:text-gray-400">
                                            <option value="" x-text="getSelectPlaceholder(field)"></option>
                                            <template x-for="opt in getFieldOptions(field)" :key="opt.id">
                                                <option :value="opt.name" x-text="opt.name"></option>
                                            </template>
                                        </select>
                                        
                                        <template x-if="isParentRequired(field) && !isParentSelected(field)">
                                            <p class="text-[10px] text-amber-600 mt-1 font-medium flex items-center gap-1">
                                                <svg class="w-3 h-3 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                                <span x-text="'Önce ' + getParentFieldLabel(field) + ' seçiniz'"></span>
                                            </p>
                                        </template>
                                    </div>
                                </template>

                                {{-- Serbest metin veya diğer alanlar için input --}}
                                <template x-if="field.type !== 'master_data' && field.type !== 'select'">
                                    <input type="text" :name="'criteria[' + field.id + ']'" x-model="criteria[field.id]" placeholder="Filtre değeri..."
                                        class="w-full text-xs rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 bg-white">
                                </template>
                            </div>
                        </template>
                    </div>
                </div>

                {{-- Kart 3: Hedef Değeri ve Dönem --}}
                <div class="bg-white rounded-2xl shadow-xs border border-gray-100 p-6 space-y-4">
                    <h3 class="text-base font-extrabold text-gray-900 border-b border-gray-100 pb-3 flex items-center gap-2">
                        <span class="w-6 h-6 rounded-md bg-emerald-100 text-emerald-700 flex items-center justify-center text-xs font-black">3</span>
                        Hedef Miktarı & Yılı
                    </h3>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        {{-- Hedef Yılı --}}
                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-1">
                                Hedef Yılı <span class="text-rose-500">*</span>
                            </label>
                            <select name="target_year" required
                                class="w-full text-sm rounded-xl border-gray-200 focus:border-indigo-500 focus:ring-indigo-500">
                                @foreach($years as $y)
                                    <option value="{{ $y }}" {{ $y == date('Y') ? 'selected' : '' }}>{{ $y }}</option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Hedef Değeri --}}
                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-1">
                                Hedeflenen Sayısal Değer <span class="text-rose-500">*</span>
                            </label>
                            <div x-data="autoThousandsInput('{{ old('target_value', '') }}')">
                                <input type="hidden" name="target_value" :value="rawVal" required>
                                <div class="relative">
                                    <input type="text"
                                        inputmode="decimal"
                                        :value="displayVal"
                                        @input="onInput($event)"
                                        @keydown="onKeydown($event)"
                                        @blur="onBlur($event)"
                                        required
                                        placeholder="Örn: 2.000.000"
                                        class="w-full text-sm rounded-xl border-gray-200 focus:border-indigo-500 focus:ring-indigo-500 font-bold">
                                </div>
                                <p class="text-[10px] text-gray-400 mt-1">
                                    💡 Binlik ayraçlar (nokta) siz rakamları yazdıkça otomatik formatlanır.
                                </p>
                            </div>
                        </div>

                        {{-- Birim (Master Data'dan) --}}
                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-1">
                                Ölçü Birimi <span class="text-rose-500">*</span>
                            </label>
                            <select name="unit" required
                                class="w-full text-sm rounded-xl border-gray-200 focus:border-indigo-500 focus:ring-indigo-500 font-bold bg-white">
                                <option value="">-- Birim Seçiniz --</option>
                                @foreach($units as $u)
                                    <option value="{{ $u }}" {{ (old('unit', 'Ton') == $u) ? 'selected' : '' }}>{{ $u }}</option>
                                @endforeach
                            </select>
                            <p class="text-[10px] text-gray-400 mt-1">Master Data &gt; BİRİM listesinden beslenir.</p>
                        </div>
                    </div>

                    <div class="pt-2">
                        <label class="inline-flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" name="distribute_evenly" value="1" checked
                                class="w-4 h-4 text-indigo-600 rounded border-gray-300 focus:ring-indigo-500">
                            <span class="text-xs font-medium text-gray-700">Yıllık hedefi 12 aya eşit olarak paylaştır (Daha sonra ay ay özel dağıtım yapabilirsiniz)</span>
                        </label>
                    </div>
                </div>

                {{-- Kart 4: Hedef Sahibi ve Hiyerarşik Yayılım (Hoshin Kanri) --}}
                <div class="bg-white rounded-2xl shadow-xs border border-gray-100 p-6 space-y-4">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between border-b border-gray-100 pb-3 gap-2">
                        <h3 class="text-base font-extrabold text-gray-900 flex items-center gap-2">
                            <span class="w-6 h-6 rounded-md bg-amber-100 text-amber-700 flex items-center justify-center text-xs font-black">4</span>
                            Hedef Sahibi (Atama) & Hiyerarşi
                        </h3>
                        <div class="text-xs text-gray-500 font-medium">
                            <span x-show="assigneeType === 'user'">Birden fazla personele aynı hedefi tek seferde tanımlayabilirsiniz.</span>
                            <span x-show="assigneeType !== 'user'">Grup veya Yönetici düzeyinde hedef ataması.</span>
                        </div>
                    </div>

                    {{-- Hedef Düzeyi Seçici --}}
                    <div>
                        <label class="block text-xs font-bold text-gray-700 mb-2">
                            Hedef Tipi / Düzeyi <span class="text-rose-500">*</span>
                        </label>
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                            <label class="flex items-center gap-3 p-3 rounded-xl border cursor-pointer transition text-xs font-semibold"
                                :class="assigneeType === 'user' ? 'bg-indigo-50 border-indigo-500 text-indigo-700 shadow-2xs ring-1 ring-indigo-200' : 'border-gray-200 text-gray-600 hover:bg-gray-50'">
                                <input type="radio" name="assignee_type" value="user" x-model="assigneeType" class="text-indigo-600 focus:ring-indigo-500">
                                <div>
                                    <div class="font-bold flex items-center gap-1.5">
                                        <svg class="w-4 h-4 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                                        Bireysel Personel(ler)
                                    </div>
                                    <p class="text-[10px] text-gray-500 mt-0.5">Seçilen her personele ayrı hedef karnesi açılır</p>
                                </div>
                            </label>

                            <label class="flex items-center gap-3 p-3 rounded-xl border cursor-pointer transition text-xs font-semibold"
                                :class="assigneeType === 'group' ? 'bg-indigo-50 border-indigo-500 text-indigo-700 shadow-2xs ring-1 ring-indigo-200' : 'border-gray-200 text-gray-600 hover:bg-gray-50'">
                                <input type="radio" name="assignee_type" value="group" x-model="assigneeType" class="text-indigo-600 focus:ring-indigo-500">
                                <div>
                                    <div class="font-bold flex items-center gap-1.5">
                                        <svg class="w-4 h-4 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                                        Grup / Departman
                                    </div>
                                    <p class="text-[10px] text-gray-500 mt-0.5">Departman genel toplamı üzerinden izlenir</p>
                                </div>
                            </label>

                            <label class="flex items-center gap-3 p-3 rounded-xl border cursor-pointer transition text-xs font-semibold"
                                :class="assigneeType === 'manager' ? 'bg-indigo-50 border-indigo-500 text-indigo-700 shadow-2xs ring-1 ring-indigo-200' : 'border-gray-200 text-gray-600 hover:bg-gray-50'">
                                <input type="radio" name="assignee_type" value="manager" x-model="assigneeType" class="text-indigo-600 focus:ring-indigo-500">
                                <div>
                                    <div class="font-bold flex items-center gap-1.5">
                                        <svg class="w-4 h-4 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
                                        Grup Yöneticisi
                                    </div>
                                    <p class="text-[10px] text-gray-500 mt-0.5">Yönetici ve ekibinin kümülatif hedefi</p>
                                </div>
                            </label>
                        </div>
                    </div>

                    {{-- 1. Bireysel Personel Çoklu Seçim Arayüzü --}}
                    <div x-show="assigneeType === 'user'" class="space-y-3 pt-2">
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                            <label class="block text-xs font-bold text-gray-700">
                                Hedefin Atanacağı Personeller <span class="text-rose-500">*</span>
                                <span class="text-gray-400 font-normal ml-1">(İstediğiniz kadar personel seçebilirsiniz)</span>
                            </label>

                            {{-- Hızlı Seçim Butonları --}}
                            <div class="flex items-center gap-2">
                                <button type="button" @click="selectAllFiltered()"
                                    class="text-[11px] font-bold text-indigo-600 hover:text-indigo-800 bg-indigo-50 hover:bg-indigo-100 px-2.5 py-1 rounded-lg transition">
                                    Listelenenleri Seç
                                </button>
                                <button type="button" @click="clearSelectedUsers()"
                                    class="text-[11px] font-bold text-gray-500 hover:text-rose-700 bg-gray-100 hover:bg-rose-50 px-2.5 py-1 rounded-lg transition"
                                    x-show="selectedUsers.length > 0">
                                    Seçimi Temizle
                                </button>
                            </div>
                        </div>

                        {{-- Arama ve Grup Filtresi --}}
                        <div class="grid grid-cols-1 sm:grid-cols-12 gap-2">
                            <div class="sm:col-span-7 relative">
                                <span class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none text-gray-400">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                                </span>
                                <input type="text" x-model="userSearch" placeholder="İsim veya e-posta ile personel ara..."
                                    class="w-full text-xs pl-9 pr-3 py-2 rounded-xl border-gray-200 focus:border-indigo-500 focus:ring-indigo-500">
                            </div>

                            <div class="sm:col-span-5">
                                <select x-model="userGroupFilter"
                                    class="w-full text-xs py-2 rounded-xl border-gray-200 focus:border-indigo-500 focus:ring-indigo-500">
                                    <option value="">Tüm Departmanlar / Gruplar</option>
                                    @foreach($groups as $g)
                                        <option value="{{ $g->id }}">{{ $g->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        {{-- Seçilen Personellerin Rozetleri --}}
                        <div x-show="selectedUsers.length > 0" class="p-3 bg-indigo-50/60 rounded-xl border border-indigo-100 space-y-2">
                            <div class="flex items-center justify-between text-xs">
                                <span class="font-extrabold text-indigo-900 flex items-center gap-1.5">
                                    <span class="w-2 h-2 rounded-full bg-emerald-500 inline-block animate-pulse"></span>
                                    <span x-text="selectedUsers.length + ' Personel Seçildi'"></span>
                                </span>
                                <span class="text-[11px] text-indigo-600 font-medium">Bu personellerin her biri için ayrı hedef kaydı açılacaktır.</span>
                            </div>

                            <div class="flex flex-wrap gap-1.5 max-h-24 overflow-y-auto pt-1">
                                <template x-for="uid in selectedUsers" :key="uid">
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-bold bg-white text-indigo-800 border border-indigo-200 shadow-2xs">
                                        <span x-text="getUserName(uid)"></span>
                                        <button type="button" @click="toggleUser(uid)" class="text-indigo-400 hover:text-rose-600 transition ml-0.5">
                                            &times;
                                        </button>
                                    </span>
                                </template>
                            </div>
                        </div>

                        {{-- Personel Listesi (Scrollable) --}}
                        <div class="border border-gray-200 rounded-xl overflow-hidden shadow-2xs">
                            <div class="max-h-56 overflow-y-auto divide-y divide-gray-100 bg-white">
                                <template x-for="u in filteredUsers" :key="u.id">
                                    <div @click="toggleUser(u.id)"
                                        class="px-3 py-2.5 flex items-center justify-between cursor-pointer transition select-none hover:bg-indigo-50/50"
                                        :class="selectedUsers.includes(u.id) ? 'bg-indigo-50/70 font-semibold' : ''">
                                        <div class="flex items-center gap-3 min-w-0">
                                            <input type="checkbox" :checked="selectedUsers.includes(u.id)"
                                                @click.stop="toggleUser(u.id)"
                                                class="w-4 h-4 text-indigo-600 rounded border-gray-300 focus:ring-indigo-500 cursor-pointer">
                                            
                                            <div class="w-7 h-7 rounded-full bg-indigo-100 text-indigo-700 flex items-center justify-center font-bold text-xs shrink-0"
                                                x-text="u.name.substring(0, 1).toUpperCase()">
                                            </div>

                                            <div class="min-w-0">
                                                <div class="text-xs text-gray-900 truncate" x-text="u.name"></div>
                                                <div class="text-[10px] text-gray-400 truncate" x-text="u.email"></div>
                                            </div>
                                        </div>

                                        <span class="text-[10px] px-2 py-0.5 rounded-full font-medium shrink-0 ml-2"
                                            :class="selectedUsers.includes(u.id) ? 'bg-indigo-200/80 text-indigo-800' : 'bg-gray-100 text-gray-600'"
                                            x-text="u.group_name">
                                        </span>
                                    </div>
                                </template>

                                <div x-show="filteredUsers.length === 0" class="p-6 text-center text-xs text-gray-400">
                                    Aramanıza uygun personel bulunamadı.
                                </div>
                            </div>
                        </div>

                        {{-- Hidden inputs for selected users --}}
                        <template x-if="assigneeType === 'user'">
                            <template x-for="uid in selectedUsers" :key="'usr-' + uid">
                                <input type="hidden" name="assignee_ids[]" :value="uid">
                            </template>
                        </template>

                        {{-- Validation uyarısı --}}
                        <div x-show="selectedUsers.length === 0" class="text-[11px] text-amber-600 font-medium flex items-center gap-1">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                            <span>Lütfen listelenen personeller arasından en az bir kişi seçiniz.</span>
                        </div>
                    </div>

                    {{-- 2. Grup / Departman Çoklu Seçim Arayüzü --}}
                    <div x-show="assigneeType === 'group'" class="space-y-3 pt-2">
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                            <label class="block text-xs font-bold text-gray-700">
                                Hedefin Atanacağı Grup(lar) / Departman(lar) <span class="text-rose-500">*</span>
                                <span class="text-gray-400 font-normal ml-1">(1 veya birden fazla grup seçebilirsiniz)</span>
                            </label>

                            {{-- Hızlı Seçim Butonları --}}
                            <div class="flex items-center gap-2">
                                <button type="button" @click="selectAllGroups()"
                                    class="text-[11px] font-bold text-indigo-600 hover:text-indigo-800 bg-indigo-50 hover:bg-indigo-100 px-2.5 py-1 rounded-lg transition">
                                    Tüm Grupları Seç
                                </button>
                                <button type="button" @click="clearSelectedGroups()"
                                    class="text-[11px] font-bold text-gray-500 hover:text-rose-700 bg-gray-100 hover:bg-rose-50 px-2.5 py-1 rounded-lg transition"
                                    x-show="selectedGroups.length > 0">
                                    Seçimi Temizle
                                </button>
                            </div>
                        </div>

                        {{-- Arama Kutusu --}}
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none text-gray-400">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                            </span>
                            <input type="text" x-model="groupSearch" placeholder="Grup veya departman adı ile ara..."
                                class="w-full text-xs pl-9 pr-3 py-2 rounded-xl border-gray-200 focus:border-indigo-500 focus:ring-indigo-500">
                        </div>

                        {{-- Seçilen Grupların Rozetleri --}}
                        <div x-show="selectedGroups.length > 0" class="p-3 bg-indigo-50/60 rounded-xl border border-indigo-100 space-y-2">
                            <div class="flex items-center justify-between text-xs">
                                <span class="font-extrabold text-indigo-900 flex items-center gap-1.5">
                                    <span class="w-2 h-2 rounded-full bg-emerald-500 inline-block animate-pulse"></span>
                                    <span x-text="selectedGroups.length + ' Grup / Departman Seçildi'"></span>
                                </span>
                                <span class="text-[11px] text-indigo-600 font-medium">Seçilen her departman için ayrı hedef karnesi açılacaktır.</span>
                            </div>

                            <div class="flex flex-wrap gap-1.5 max-h-24 overflow-y-auto pt-1">
                                <template x-for="gid in selectedGroups" :key="'grp-badge-' + gid">
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-bold bg-white text-indigo-800 border border-indigo-200 shadow-2xs">
                                        <span x-text="getGroupName(gid)"></span>
                                        <button type="button" @click="toggleGroup(gid)" class="text-indigo-400 hover:text-rose-600 transition ml-0.5">
                                            &times;
                                        </button>
                                    </span>
                                </template>
                            </div>
                        </div>

                        {{-- Grup Listesi (Scrollable) --}}
                        <div class="border border-gray-200 rounded-xl overflow-hidden shadow-2xs">
                            <div class="max-h-56 overflow-y-auto divide-y divide-gray-100 bg-white">
                                <template x-for="g in filteredGroups" :key="'grp-' + g.id">
                                    <div @click="toggleGroup(g.id)"
                                        class="px-3.5 py-2.5 flex items-center justify-between cursor-pointer transition select-none hover:bg-indigo-50/50"
                                        :class="selectedGroups.includes(g.id) ? 'bg-indigo-50/70 font-semibold' : ''">
                                        <div class="flex items-center gap-3 min-w-0">
                                            <input type="checkbox" :checked="selectedGroups.includes(g.id)"
                                                @click.stop="toggleGroup(g.id)"
                                                class="w-4 h-4 text-indigo-600 rounded border-gray-300 focus:ring-indigo-500 cursor-pointer">
                                            
                                            <div class="w-8 h-8 rounded-xl bg-gradient-to-br from-indigo-500 to-purple-600 text-white flex items-center justify-center font-bold text-xs shrink-0 shadow-2xs">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                                            </div>

                                            <div class="min-w-0">
                                                <div class="text-xs text-gray-900 font-bold truncate" x-text="g.name"></div>
                                                <div class="text-[11px] text-gray-500 truncate" x-text="'Yönetici: ' + g.manager_name"></div>
                                            </div>
                                        </div>

                                        <span class="text-[10px] px-2.5 py-1 rounded-full font-semibold shrink-0 ml-2"
                                            :class="selectedGroups.includes(g.id) ? 'bg-indigo-200/80 text-indigo-900' : 'bg-gray-100 text-gray-600'"
                                            x-text="g.members_count + ' Personel'">
                                        </span>
                                    </div>
                                </template>

                                <div x-show="filteredGroups.length === 0" class="p-6 text-center text-xs text-gray-400">
                                    Aramanıza uygun grup / departman bulunamadı.
                                </div>
                            </div>
                        </div>

                        {{-- Hidden inputs for selected groups --}}
                        <template x-if="assigneeType === 'group'">
                            <template x-for="gid in selectedGroups" :key="'h-grp-' + gid">
                                <input type="hidden" name="assignee_ids[]" :value="gid">
                            </template>
                        </template>

                        {{-- Validation uyarısı --}}
                        <div x-show="selectedGroups.length === 0" class="text-[11px] text-amber-600 font-medium flex items-center gap-1">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                            <span>Lütfen hedefin atanacağı en az bir grup / departman seçiniz.</span>
                        </div>
                    </div>

                    {{-- 3. Grup Yöneticisi Çoklu Seçim Arayüzü --}}
                    <div x-show="assigneeType === 'manager'" class="space-y-3 pt-2">
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                            <label class="block text-xs font-bold text-gray-700">
                                Hedefin Atanacağı Grup Yöneticileri <span class="text-rose-500">*</span>
                                <span class="text-gray-400 font-normal ml-1">(1 veya birden fazla yönetici seçebilirsiniz)</span>
                            </label>

                            {{-- Hızlı Seçim Butonları --}}
                            <div class="flex items-center gap-2">
                                <button type="button" @click="selectAllManagers()"
                                    class="text-[11px] font-bold text-indigo-600 hover:text-indigo-800 bg-indigo-50 hover:bg-indigo-100 px-2.5 py-1 rounded-lg transition">
                                    Tüm Yöneticileri Seç
                                </button>
                                <button type="button" @click="clearSelectedManagers()"
                                    class="text-[11px] font-bold text-gray-500 hover:text-rose-700 bg-gray-100 hover:bg-rose-50 px-2.5 py-1 rounded-lg transition"
                                    x-show="selectedManagers.length > 0">
                                    Seçimi Temizle
                                </button>
                            </div>
                        </div>

                        {{-- Arama Kutusu --}}
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none text-gray-400">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                            </span>
                            <input type="text" x-model="managerSearch" placeholder="Yönetici adı veya grup adı ile ara..."
                                class="w-full text-xs pl-9 pr-3 py-2 rounded-xl border-gray-200 focus:border-indigo-500 focus:ring-indigo-500">
                        </div>

                        {{-- Seçilen Yöneticilerin Rozetleri --}}
                        <div x-show="selectedManagers.length > 0" class="p-3 bg-indigo-50/60 rounded-xl border border-indigo-100 space-y-2">
                            <div class="flex items-center justify-between text-xs">
                                <span class="font-extrabold text-indigo-900 flex items-center gap-1.5">
                                    <span class="w-2 h-2 rounded-full bg-emerald-500 inline-block animate-pulse"></span>
                                    <span x-text="selectedManagers.length + ' Grup Yöneticisi Seçildi'"></span>
                                </span>
                                <span class="text-[11px] text-indigo-600 font-medium">Seçilen her yönetici için ekibiyle kümülatif hedef kaydı açılacaktır.</span>
                            </div>

                            <div class="flex flex-wrap gap-1.5 max-h-24 overflow-y-auto pt-1">
                                <template x-for="gid in selectedManagers" :key="'mgr-badge-' + gid">
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-bold bg-white text-indigo-800 border border-indigo-200 shadow-2xs">
                                        <span x-text="getManagerDisplayName(gid)"></span>
                                        <button type="button" @click="toggleManager(gid)" class="text-indigo-400 hover:text-rose-600 transition ml-0.5">
                                            &times;
                                        </button>
                                    </span>
                                </template>
                            </div>
                        </div>

                        {{-- Yönetici Listesi (Scrollable) --}}
                        <div class="border border-gray-200 rounded-xl overflow-hidden shadow-2xs">
                            <div class="max-h-56 overflow-y-auto divide-y divide-gray-100 bg-white">
                                <template x-for="g in filteredManagers" :key="'mgr-' + g.id">
                                    <div @click="g.has_manager && toggleManager(g.id)"
                                        class="px-3.5 py-2.5 flex items-center justify-between transition select-none"
                                        :class="!g.has_manager ? 'opacity-50 cursor-not-allowed bg-gray-50' : (selectedManagers.includes(g.id) ? 'bg-indigo-50/70 font-semibold cursor-pointer' : 'cursor-pointer hover:bg-indigo-50/50')">
                                        <div class="flex items-center gap-3 min-w-0">
                                            <input type="checkbox" :checked="selectedManagers.includes(g.id)"
                                                :disabled="!g.has_manager"
                                                @click.stop="g.has_manager && toggleManager(g.id)"
                                                class="w-4 h-4 text-indigo-600 rounded border-gray-300 focus:ring-indigo-500 cursor-pointer disabled:cursor-not-allowed">
                                            
                                            <div class="w-8 h-8 rounded-full flex items-center justify-center font-bold text-xs shrink-0 shadow-2xs"
                                                :class="g.has_manager ? 'bg-indigo-100 text-indigo-700' : 'bg-gray-200 text-gray-500'"
                                                x-text="g.has_manager ? g.manager_name.substring(0, 1).toUpperCase() : '?'">
                                            </div>

                                            <div class="min-w-0">
                                                <div class="text-xs text-gray-900 font-bold truncate" x-text="g.manager_name"></div>
                                                <div class="text-[11px] text-gray-500 truncate" x-text="g.name + ' Grubu Yöneticisi'"></div>
                                            </div>
                                        </div>

                                        <span class="text-[10px] px-2.5 py-1 rounded-full font-semibold shrink-0 ml-2"
                                            :class="selectedManagers.includes(g.id) ? 'bg-indigo-200/80 text-indigo-900' : 'bg-gray-100 text-gray-600'"
                                            x-text="g.members_count + ' Kişilik Ekip'">
                                        </span>
                                    </div>
                                </template>

                                <div x-show="filteredManagers.length === 0" class="p-6 text-center text-xs text-gray-400">
                                    Aramanıza uygun yönetici bulunamadı.
                                </div>
                            </div>
                        </div>

                        {{-- Hidden inputs for selected managers --}}
                        <template x-if="assigneeType === 'manager'">
                            <template x-for="gid in selectedManagers" :key="'h-mgr-' + gid">
                                <input type="hidden" name="assignee_ids[]" :value="gid">
                            </template>
                        </template>

                        {{-- Validation uyarısı --}}
                        <div x-show="selectedManagers.length === 0" class="text-[11px] text-amber-600 font-medium flex items-center gap-1">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                            <span>Lütfen hedefin atanacağı en az bir grup yöneticisi seçiniz.</span>
                        </div>
                    </div>

                    {{-- İsteğe Bağlı Üst Hedef --}}
                    @if($parentTargets->count() > 0)
                        <div class="pt-3 border-t border-gray-100">
                            <label class="block text-xs font-bold text-gray-700 mb-1">
                                Bağlı Olduğu Üst Hedef (Hoshin Kanri Kümülasyonu)
                            </label>
                            <select name="parent_target_id"
                                class="w-full text-sm rounded-xl border-gray-200 focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="">-- Yok (Bağımsız / Tepe Hedef) --</option>
                                @foreach($parentTargets as $pt)
                                    <option value="{{ $pt->id }}">
                                        {{ $pt->title }} ({{ $pt->target_value }} {{ $pt->unit }} - {{ $pt->assignee_name }})
                                    </option>
                                @endforeach
                            </select>
                            <p class="text-[11px] text-gray-400 mt-1">Bu hedefteki gerçekleşmeler seçilen üst hedefin havuzunu otomatik besler.</p>
                        </div>
                    @endif
                </div>

                {{-- Butonlar --}}
                <div class="flex items-center justify-end gap-3 pt-2">
                    <a href="{{ route('kpi-targets.index') }}" class="px-5 py-2.5 rounded-xl border border-gray-200 text-gray-600 hover:bg-gray-50 text-sm font-semibold transition">
                        İptal
                    </a>
                    <button type="submit"
                        :disabled="isSubmitting || 
                            (assigneeType === 'user' && selectedUsers.length === 0) || 
                            (assigneeType === 'group' && selectedGroups.length === 0) || 
                            (assigneeType === 'manager' && selectedManagers.length === 0)"
                        class="px-6 py-2.5 bg-indigo-600 hover:bg-indigo-700 disabled:opacity-50 disabled:cursor-not-allowed text-white font-bold text-sm rounded-xl shadow-md transition hover:shadow-lg inline-flex items-center gap-2">
                        <span x-show="!isSubmitting">Hedefi Kaydet ve Devam Et →</span>
                        <span x-show="isSubmitting" class="inline-flex items-center gap-1.5">
                            <svg class="animate-spin w-4 h-4 text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                            Kaydediliyor...
                        </span>
                    </button>
                </div>

            </form>
        </div>
    </div>

    <script>
    function kpiTargetWizard() {
        return {
            selectedTemplate: '',
            selectedTargetField: '',
            assigneeType: 'user',
            numericFields: [],
            filterFields: [],
            isSubmitting: false,

            // Kullanıcı Çoklu Seçim State
            userSearch: '',
            userGroupFilter: '',
            selectedUsers: [],
            allUsers: [
                @foreach($users as $u)
                {
                    id: {{ $u->id }},
                    name: @json($u->name),
                    email: @json($u->email),
                    group_id: '{{ $u->group_id ?? "" }}',
                    group_name: @json($u->group->name ?? 'Grubu Yok')
                },
                @endforeach
            ],

            get filteredUsers() {
                const s = this.userSearch.trim().toLowerCase();
                const g = this.userGroupFilter;
                return this.allUsers.filter(u => {
                    const matchSearch = !s || u.name.toLowerCase().includes(s) || u.email.toLowerCase().includes(s);
                    const matchGroup = !g || String(u.group_id) === String(g);
                    return matchSearch && matchGroup;
                });
            },

            toggleUser(id) {
                const idx = this.selectedUsers.indexOf(id);
                if (idx > -1) {
                    this.selectedUsers.splice(idx, 1);
                } else {
                    this.selectedUsers.push(id);
                }
            },

            selectAllFiltered() {
                this.filteredUsers.forEach(u => {
                    if (!this.selectedUsers.includes(u.id)) {
                        this.selectedUsers.push(u.id);
                    }
                });
            },

            clearSelectedUsers() {
                this.selectedUsers = [];
            },

            getUserName(id) {
                const u = this.allUsers.find(x => x.id === id);
                return u ? u.name : id;
            },

            // Grup Çoklu Seçim State
            groupSearch: '',
            selectedGroups: [],
            allGroups: [
                @foreach($groups as $g)
                {
                    id: {{ $g->id }},
                    name: @json($g->name),
                    manager_name: @json($g->managers->first()->name ?? 'Yönetici Tanımlanmamış'),
                    has_manager: {{ $g->managers->isNotEmpty() ? 'true' : 'false' }},
                    members_count: {{ $g->users->count() }}
                },
                @endforeach
            ],

            get filteredGroups() {
                const s = this.groupSearch.trim().toLowerCase();
                return this.allGroups.filter(g => !s || g.name.toLowerCase().includes(s) || g.manager_name.toLowerCase().includes(s));
            },

            toggleGroup(id) {
                const idx = this.selectedGroups.indexOf(id);
                if (idx > -1) {
                    this.selectedGroups.splice(idx, 1);
                } else {
                    this.selectedGroups.push(id);
                }
            },

            selectAllGroups() {
                this.filteredGroups.forEach(g => {
                    if (!this.selectedGroups.includes(g.id)) {
                        this.selectedGroups.push(g.id);
                    }
                });
            },

            clearSelectedGroups() {
                this.selectedGroups = [];
            },

            getGroupName(id) {
                const g = this.allGroups.find(x => x.id === id);
                return g ? g.name : id;
            },

            // Grup Yöneticisi Çoklu Seçim State
            managerSearch: '',
            selectedManagers: [],

            get filteredManagers() {
                const s = this.managerSearch.trim().toLowerCase();
                return this.allGroups.filter(g => {
                    return !s || g.name.toLowerCase().includes(s) || g.manager_name.toLowerCase().includes(s);
                });
            },

            toggleManager(id) {
                const idx = this.selectedManagers.indexOf(id);
                if (idx > -1) {
                    this.selectedManagers.splice(idx, 1);
                } else {
                    this.selectedManagers.push(id);
                }
            },

            selectAllManagers() {
                this.filteredManagers.forEach(g => {
                    if (g.has_manager && !this.selectedManagers.includes(g.id)) {
                        this.selectedManagers.push(g.id);
                    }
                });
            },

            clearSelectedManagers() {
                this.selectedManagers = [];
            },

            getManagerDisplayName(id) {
                const g = this.allGroups.find(x => x.id === id);
                return g ? (g.manager_name + ' (' + g.name + ')') : id;
            },

            // Kriter / Kırılım ve Master Data Cascading State
            criteria: {},
            masterDataTree: {},

            isParentRequired(field) {
                if (field.type !== 'master_data' || !field.master_data_type_id) return false;
                const typeObj = this.masterDataTree[field.master_data_type_id];
                return !!(typeObj && typeObj.parent_id);
            },

            getParentField(field) {
                if (!this.isParentRequired(field)) return null;
                const typeObj = this.masterDataTree[field.master_data_type_id];
                return this.filterFields.find(f => f.type === 'master_data' && f.master_data_type_id == typeObj.parent_id);
            },

            isParentSelected(field) {
                const parentField = this.getParentField(field);
                if (!parentField) return true;
                const val = this.criteria[parentField.id];
                return val && String(val).trim() !== '';
            },

            getParentFieldLabel(field) {
                const parentField = this.getParentField(field);
                return parentField ? parentField.label : 'Üst Kategori';
            },

            getFieldOptions(field) {
                if (field.type === 'select') {
                    let opts = field.options;
                    if (typeof opts === 'string') {
                        try { opts = JSON.parse(opts); } catch (e) { opts = []; }
                    }
                    return (opts || []).map(o => ({ id: o, name: o }));
                }

                if (field.type !== 'master_data' || !field.master_data_type_id) {
                    return [];
                }

                const typeObj = this.masterDataTree[field.master_data_type_id];
                if (!typeObj) return [];

                let items = typeObj.items || [];

                // Cascading kontrolü: Üst tip var mı? (Örn: Şehir -> Ülke, Renk -> İşyeri, Gramaj -> İşyeri)
                if (typeObj.parent_id) {
                    const parentField = this.getParentField(field);
                    if (parentField) {
                        const selectedParentName = this.criteria[parentField.id];
                        if (!selectedParentName) {
                            return []; // Üst alan seçilmediyse alt alan seçenekleri boş
                        }

                        const parentTypeObj = this.masterDataTree[typeObj.parent_id];
                        const selectedParentItem = parentTypeObj && parentTypeObj.items 
                            ? parentTypeObj.items.find(i => String(i.name).trim().toLowerCase() === String(selectedParentName).trim().toLowerCase()) 
                            : null;

                        if (selectedParentItem) {
                            items = items.filter(i => i.parent_id == selectedParentItem.id);
                        } else {
                            return [];
                        }
                    }
                }

                return items;
            },

            getSelectPlaceholder(field) {
                if (field.type === 'master_data' && this.isParentRequired(field) && !this.isParentSelected(field)) {
                    return '-- Önce ' + this.getParentFieldLabel(field) + ' Seçiniz --';
                }
                return '-- Tüm Değerler (Filtresiz) --';
            },

            handleCriteriaChange(fieldId) {
                const field = this.filterFields.find(f => f.id == fieldId);
                if (!field || field.type !== 'master_data') return;

                const typeId = field.master_data_type_id;
                // Bu alana bağımlı olan tüm alt alanları bul
                const childFields = this.filterFields.filter(f => 
                    f.type === 'master_data' && 
                    this.masterDataTree[f.master_data_type_id] && 
                    this.masterDataTree[f.master_data_type_id].parent_id == typeId
                );

                // Üst alan değiştiğinde veya temizlendiğinde alt alanların seçimini sıfırla
                childFields.forEach(cf => {
                    this.criteria[cf.id] = '';
                    this.handleCriteriaChange(cf.id); // zincirleme sıfırlama (örn: Ülke -> Şehir -> ...)
                });
            },

            async fetchFields() {
                if (!this.selectedTemplate) {
                    this.numericFields = [];
                    this.filterFields = [];
                    this.criteria = {};
                    this.masterDataTree = {};
                    return;
                }

                try {
                    const response = await fetch(`{{ url('/kpi-targets/api/template-fields') }}/${this.selectedTemplate}`);
                    if (response.ok) {
                        const data = await response.json();
                        this.numericFields = data.numeric_fields || [];
                        this.filterFields = data.filter_fields || [];
                        this.masterDataTree = data.master_data_tree || {};
                        this.criteria = {};
                    }
                } catch (e) {
                    console.error('Alanlar yüklenirken hata oluştu:', e);
                }
            }
        }
    }

    function autoThousandsInput(initialVal = '') {
        return {
            rawVal: initialVal ? String(initialVal) : '',
            displayVal: '',
            init() {
                if (this.rawVal) {
                    this.displayVal = this.formatNumber(this.rawVal);
                }
            },
            formatNumber(val) {
                if (val === null || val === undefined || val === '') return '';
                let str = String(val).trim();
                if (str.includes('.') && !str.includes(',')) {
                    let parts = str.split('.');
                    let intPart = parts[0].replace(/\D/g, '');
                    let formattedInt = intPart.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
                    let decPart = parts[1] || '';
                    if (decPart && decPart !== '00' && decPart !== '0') {
                        return formattedInt + ',' + decPart.slice(0, 2);
                    }
                    return formattedInt;
                }
                let parts = str.split(',');
                let intPart = parts[0].replace(/\D/g, '');
                let formattedInt = intPart.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
                if (parts.length > 1) {
                    let decPart = parts[1].replace(/\D/g, '').slice(0, 2);
                    return formattedInt + ',' + decPart;
                }
                return formattedInt;
            },
            onKeydown(e) {
                // Numpad noktasını virgüle dönüştür (Türkçe ondalık formatı için)
                if (e.key === '.' && !e.target.value.includes(',')) {
                    e.preventDefault();
                    let start = e.target.selectionStart;
                    let end = e.target.selectionEnd;
                    let currentVal = e.target.value;
                    e.target.value = currentVal.substring(0, start) + ',' + currentVal.substring(end);
                    e.target.setSelectionRange(start + 1, start + 1);
                    this.onInput(e);
                }
            },
            onInput(e) {
                let input = e.target;
                let val = input.value;
                let cursor = input.selectionStart;

                let digitsBeforeCursor = val.slice(0, cursor).replace(/\D/g, '').length;
                let hadCommaBefore = val.slice(0, cursor).includes(',');

                // Rakam ve virgül dışındakileri temizle
                let cleaned = val.replace(/[^0-9,]/g, '');
                let parts = cleaned.split(',');
                if (parts.length > 2) {
                    parts = [parts[0], parts.slice(1).join('')];
                }

                let intPart = parts[0].replace(/\D/g, '');
                let formattedInt = intPart.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
                let hasComma = parts.length > 1;
                let decPart = hasComma ? ',' + parts[1].replace(/\D/g, '').slice(0, 2) : '';

                this.displayVal = formattedInt + decPart;
                input.value = this.displayVal;

                if (intPart) {
                    this.rawVal = hasComma && parts[1] !== '' ? intPart + '.' + parts[1].replace(/\D/g, '').slice(0, 2) : intPart;
                } else {
                    this.rawVal = hasComma && parts[1] !== '' ? '0.' + parts[1].replace(/\D/g, '').slice(0, 2) : '';
                }

                this.$nextTick(() => {
                    let newPos = 0;
                    let digitsSeen = 0;
                    for (let i = 0; i < this.displayVal.length; i++) {
                        if (this.displayVal[i] === ',') {
                            if (hadCommaBefore) {
                                newPos = i + 1;
                                break;
                            }
                        }
                        if (/\d/.test(this.displayVal[i])) {
                            digitsSeen++;
                        }
                        if (digitsSeen === digitsBeforeCursor) {
                            newPos = i + 1;
                            break;
                        }
                    }
                    if (newPos === 0 && this.displayVal.length > 0) {
                        newPos = this.displayVal.length;
                    }
                    input.setSelectionRange(newPos, newPos);
                });
            },
            onBlur(e) {
                if (this.displayVal.endsWith(',')) {
                    this.displayVal = this.displayVal.slice(0, -1);
                    e.target.value = this.displayVal;
                }
            }
        };
    }
    </script>
</x-app-layout>
