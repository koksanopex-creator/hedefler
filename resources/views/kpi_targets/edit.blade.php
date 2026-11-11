@section('title', 'KPI Hedefini Düzenle')
<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="font-extrabold text-2xl text-gray-900 tracking-tight flex items-center gap-2">
                    <a href="{{ route('kpi-targets.index') }}" class="text-gray-400 hover:text-gray-600 transition">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                    </a>
                    {{ __('KPI Hedefini Düzenle: ') }} <span class="text-indigo-600">{{ $kpiTarget->title }}</span>
                </h2>
                <p class="text-sm text-gray-500 mt-1">Hedef parametrelerini, kriterlerini ve durumunu güncelleyin</p>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
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

            <form action="{{ route('kpi-targets.update', $kpiTarget->id) }}" method="POST" class="space-y-6">
                @csrf
                @method('PUT')

                {{-- Kart 1: Temel Bilgiler --}}
                <div class="bg-white rounded-2xl shadow-xs border border-gray-100 p-6 space-y-4">
                    <h3 class="text-base font-extrabold text-gray-900 border-b border-gray-100 pb-3 flex items-center gap-2">
                        <span class="w-6 h-6 rounded-md bg-indigo-100 text-indigo-700 flex items-center justify-center text-xs font-black">1</span>
                        Hedef Bilgileri
                    </h3>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-gray-500 mb-1">Form Şablonu</label>
                            <input type="text" value="{{ $kpiTarget->template->name }}" disabled
                                class="w-full text-sm rounded-xl border-gray-200 bg-gray-50 text-gray-500">
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-gray-500 mb-1">Hedef Alanı</label>
                            <input type="text" value="{{ $kpiTarget->targetField->label }} ({{ $kpiTarget->targetField->field_key }})" disabled
                                class="w-full text-sm rounded-xl border-gray-200 bg-gray-50 text-gray-500">
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-700 mb-1">Hedef Başlığı <span class="text-rose-500">*</span></label>
                        <input type="text" name="title" value="{{ old('title', $kpiTarget->title) }}" required
                            class="w-full text-sm rounded-xl border-gray-200 focus:border-indigo-500 focus:ring-indigo-500">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-700 mb-1">Açıklama</label>
                        <textarea name="description" rows="2" class="w-full text-sm rounded-xl border-gray-200 focus:border-indigo-500 focus:ring-indigo-500">{{ old('description', $kpiTarget->description) }}</textarea>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-1">Durum <span class="text-rose-500">*</span></label>
                            <select name="status" class="w-full text-sm rounded-xl border-gray-200 focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="active" {{ $kpiTarget->status === 'active' ? 'selected' : '' }}>Aktif</option>
                                <option value="paused" {{ $kpiTarget->status === 'paused' ? 'selected' : '' }}>Duraklatıldı</option>
                                <option value="completed" {{ $kpiTarget->status === 'completed' ? 'selected' : '' }}>Tamamlandı</option>
                                <option value="cancelled" {{ $kpiTarget->status === 'cancelled' ? 'selected' : '' }}>İptal</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-1">Hedef Yılı <span class="text-rose-500">*</span></label>
                            <select name="target_year" required class="w-full text-sm rounded-xl border-gray-200 focus:border-indigo-500 focus:ring-indigo-500">
                                @foreach($years as $y)
                                    <option value="{{ $y }}" {{ $kpiTarget->target_year == $y ? 'selected' : '' }}>{{ $y }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>

                {{-- Kart 2: Kriter Kırılımları (Dinamik & Düzenlenebilir) --}}
                <div class="bg-white rounded-2xl shadow-xs border border-gray-100 p-6 space-y-5"
                    x-data="editKpiCriteriaHandler()">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between pb-3 border-b border-gray-100 gap-2">
                        <h3 class="text-base font-extrabold text-gray-900 flex items-center gap-2">
                            <span class="w-6 h-6 rounded-md bg-purple-100 text-purple-700 flex items-center justify-center text-xs font-black">2</span>
                            Hedef Kırılımı & Kriterler (Master Data)
                        </h3>
                        <span class="text-xs font-semibold text-purple-700 bg-purple-50 px-2.5 py-1 rounded-lg border border-purple-100">
                            İsteğe Bağlı Filtreleme
                        </span>
                    </div>

                    {{-- Aktif Kriter Rozetleri (Mevcut Durum Özeti) --}}
                    @if(!empty($criteriaBadges))
                        <div class="p-3.5 bg-slate-50/80 rounded-xl border border-slate-200/80">
                            <span class="text-[11px] font-bold text-slate-500 uppercase tracking-wider block mb-2">
                                Mevcut Kayıtlı Kriter Rozetleri:
                            </span>
                            <div class="flex flex-wrap items-center gap-2">
                                @foreach($criteriaBadges as $badge)
                                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-lg text-xs font-bold border {{ $badge['bg_class'] }} shadow-2xs">
                                        <span>{{ $badge['icon'] }}</span>
                                        <span class="opacity-75 font-semibold">{{ $badge['label'] }}:</span>
                                        <strong class="font-black">{{ $badge['value'] }}</strong>
                                    </span>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    <p class="text-xs text-gray-500">
                        Bu hedefi belirli bir <strong>İşyeri</strong> (örn: Preform), <strong>Ülke / Şehir</strong> veya <strong>Gramaj / Renk</strong> gibi kırılımlara göre güncellemek istiyorsanız aşağıdaki alanları düzenleyebilirsiniz. Boş bırakılan alanlar için tüm veriler toplanır.
                    </p>

                    {{-- Dinamik Kriter Alanları (Cascading Dropdowns) --}}
                    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4">
                        <template x-for="field in filterFields" :key="field.id">
                            <div class="bg-gray-50/70 p-3 rounded-xl border border-gray-100">
                                <label class="block text-xs font-bold text-gray-700 mb-1" x-text="field.label"></label>

                                {{-- Master Data veya Select Seçimi --}}
                                <template x-if="field.type === 'master_data' || field.type === 'select'">
                                    <select :name="'criteria[' + field.id + ']'"
                                        x-model="criteria[field.id]"
                                        @change="handleCriteriaChange(field.id)"
                                        class="w-full text-xs rounded-lg border-gray-200 bg-white focus:border-indigo-500 focus:ring-indigo-500">
                                        <option value="">-- Tüm Değerler (Filtresiz) --</option>
                                        <template x-for="opt in getFilteredOptions(field)" :key="opt.id || opt.name">
                                            <option :value="opt.name" x-text="opt.name" :selected="criteria[field.id] == opt.name"></option>
                                        </template>
                                    </select>
                                </template>

                                {{-- Serbest Metin / Diğer --}}
                                <template x-if="field.type !== 'master_data' && field.type !== 'select'">
                                    <input type="text" :name="'criteria[' + field.id + ']'"
                                        x-model="criteria[field.id]"
                                        placeholder="Filtre değeri..."
                                        class="w-full text-xs rounded-lg border-gray-200 bg-white focus:border-indigo-500 focus:ring-indigo-500">
                                </template>
                            </div>
                        </template>
                    </div>

                    <div class="flex justify-end pt-1">
                        <button type="button" @click="criteria = {}"
                            class="text-xs font-bold text-rose-600 hover:text-rose-800 bg-rose-50 hover:bg-rose-100 px-3 py-1.5 rounded-lg border border-rose-200 transition">
                            Tüm Filtreleri Temizle
                        </button>
                    </div>
                </div>

                {{-- Kart 3: Hedef Değeri & Birimi --}}
                <div class="bg-white rounded-2xl shadow-xs border border-gray-100 p-6 space-y-4">
                    <h3 class="text-base font-extrabold text-gray-900 border-b border-gray-100 pb-3 flex items-center gap-2">
                        <span class="w-6 h-6 rounded-md bg-emerald-100 text-emerald-700 flex items-center justify-center text-xs font-black">3</span>
                        Hedef Değeri & Dağıtım
                    </h3>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-1">Hedef Değer <span class="text-rose-500">*</span></label>
                            <div x-data="autoThousandsInput('{{ old('target_value', $kpiTarget->target_value) }}')">
                                <input type="hidden" name="target_value" :value="rawVal" required>
                                <input type="text"
                                    inputmode="decimal"
                                    :value="displayVal"
                                    @input="onInput($event)"
                                    @keydown="onKeydown($event)"
                                    @blur="onBlur($event)"
                                    required
                                    placeholder="Örn: 2.000.000"
                                    class="w-full text-sm rounded-xl border-gray-200 focus:border-indigo-500 focus:ring-indigo-500 font-bold">
                                <p class="text-[10px] text-gray-400 mt-1">
                                    💡 Binlik ayraçlar (nokta) otomatik formatlanır.
                                </p>
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-1">Ölçü Birimi <span class="text-rose-500">*</span></label>
                            <select name="unit" required
                                class="w-full text-sm rounded-xl border-gray-200 focus:border-indigo-500 focus:ring-indigo-500 font-bold bg-white">
                                <option value="">-- Birim Seçiniz --</option>
                                @foreach($units as $u)
                                    <option value="{{ $u }}" {{ (old('unit', $kpiTarget->unit) == $u) ? 'selected' : '' }}>{{ $u }}</option>
                                @endforeach
                            </select>
                            <p class="text-[10px] text-gray-400 mt-1">Master Data &gt; BİRİM listesinden beslenir.</p>
                        </div>
                    </div>

                    <div class="pt-2">
                        <label class="inline-flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" name="distribute_evenly" value="1"
                                class="w-4 h-4 text-indigo-600 rounded border-gray-300 focus:ring-indigo-500">
                            <span class="text-xs font-medium text-gray-700">Değer değiştiyse 12 aylık periyotları otomatik eşit olarak orantıla</span>
                        </label>
                    </div>
                </div>

                {{-- Kart 4: Hedef Sahibi --}}
                <div class="bg-white rounded-2xl shadow-xs border border-gray-100 p-6 space-y-4" x-data="{ assigneeType: '{{ $kpiTarget->assignee_type }}' }">
                    <h3 class="text-base font-extrabold text-gray-900 border-b border-gray-100 pb-3 flex items-center gap-2">
                        <span class="w-6 h-6 rounded-md bg-amber-100 text-amber-700 flex items-center justify-center text-xs font-black">4</span>
                        Hedef Sahibi (Atama)
                    </h3>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-1">Hedef Düzeyi <span class="text-rose-500">*</span></label>
                            <select name="assignee_type" x-model="assigneeType" class="w-full text-sm rounded-xl border-gray-200 focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="user">👤 Bireysel Personel</option>
                                <option value="group">👥 Grup / Departman</option>
                                <option value="manager">👔 Grup Yöneticisi</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-1">Atanan Kişi / Grup <span class="text-rose-500">*</span></label>
                            <div x-show="assigneeType === 'user'">
                                <select name="assignee_id" :disabled="assigneeType !== 'user'" class="w-full text-sm rounded-xl border-gray-200 focus:border-indigo-500 focus:ring-indigo-500">
                                    @foreach($users as $u)
                                        <option value="{{ $u->id }}" {{ $kpiTarget->assignee_type === 'user' && $kpiTarget->assignee_id == $u->id ? 'selected' : '' }}>
                                            {{ $u->name }} ({{ $u->group->name ?? 'Grup Yok' }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div x-show="assigneeType === 'group' || assigneeType === 'manager'">
                                <select name="assignee_id" :disabled="assigneeType === 'user'" class="w-full text-sm rounded-xl border-gray-200 focus:border-indigo-500 focus:ring-indigo-500">
                                    @foreach($groups as $g)
                                        <option value="{{ $g->id }}" {{ $kpiTarget->assignee_type !== 'user' && $kpiTarget->assignee_id == $g->id ? 'selected' : '' }}>
                                            {{ $g->name }} (Yönetici: {{ $g->managers->first()->name ?? 'Yok' }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Butonlar --}}
                <div class="flex items-center justify-between pt-2">
                    <a href="{{ route('kpi-targets.distribute', $kpiTarget->id) }}" class="inline-flex items-center gap-1.5 px-4 py-2 bg-teal-50 text-teal-700 hover:bg-teal-100 rounded-xl text-sm font-bold border border-teal-200 transition">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                        12 Aylık Dağıtımı Düzenle &rarr;
                    </a>

                    <div class="flex items-center gap-3">
                        <a href="{{ route('kpi-targets.index') }}" class="px-5 py-2.5 rounded-xl border border-gray-200 text-gray-600 hover:bg-gray-50 text-sm font-semibold transition">
                            İptal
                        </a>
                        <button type="submit" class="px-6 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-sm rounded-xl shadow-md transition hover:shadow-lg">
                            Değişiklikleri Kaydet
                        </button>
                    </div>
                </div>
            </form>

        </div>
    </div>

    <script>
        function editKpiCriteriaHandler() {
            return {
                criteria: {{ Js::from($kpiTarget->criteria_json ?? []) }},
                filterFields: {{ Js::from($filterFields) }},
                masterDataTree: {{ Js::from($masterDataTree) }},

                getFilteredOptions(field) {
                    if (!this.masterDataTree || !field.master_data_type_id) {
                        return field.options || [];
                    }

                    const currentType = this.masterDataTree[field.master_data_type_id];
                    if (!currentType || !currentType.items) {
                        return [];
                    }

                    // Eğer bu tipin bir üst tipi (parent_id) yoksa tüm öğeleri göster
                    if (!currentType.parent_id) {
                        return currentType.items;
                    }

                    // Üst tipe bağlı alanı bul
                    const parentField = this.filterFields.find(f => f.master_data_type_id === currentType.parent_id);
                    if (!parentField) {
                        return currentType.items;
                    }

                    const selectedParentName = this.criteria[parentField.id];
                    if (!selectedParentName) {
                        return []; // Üst alan seçilmediyse alt liste boş
                    }

                    const parentType = this.masterDataTree[currentType.parent_id];
                    if (!parentType || !parentType.items) {
                        return [];
                    }

                    const parentItem = parentType.items.find(item => item.name === selectedParentName);
                    if (!parentItem) {
                        return [];
                    }

                    return currentType.items.filter(item => item.parent_id === parentItem.id);
                },

                handleCriteriaChange(fieldId) {
                    const changedField = this.filterFields.find(f => f.id === fieldId);
                    if (!changedField || !changedField.master_data_type_id) return;

                    const childFields = this.filterFields.filter(f => {
                        const t = this.masterDataTree[f.master_data_type_id];
                        return t && t.parent_id === changedField.master_data_type_id;
                    });

                    childFields.forEach(cf => {
                        this.criteria[cf.id] = '';
                        this.handleCriteriaChange(cf.id);
                    });
                }
            };
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
