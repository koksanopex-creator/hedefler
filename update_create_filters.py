import os
import re

filepath = r'c:\Users\celal.karaman\Desktop\HEDEFLER\resources\views\form_entries\create.blade.php'
with open(filepath, 'r', encoding='utf-8') as f:
    content = f.read()

# 1. Update Thead to include Filter Dropdowns
old_thead = """        @foreach($fieldsToRender as $field)
            <th class="px-2 sm:px-4 py-3.5 text-left text-[10px] sm:text-xs font-bold text-slate-300 uppercase tracking-widest bg-slate-900 border-b border-slate-700 group cursor-default">
                <div class="flex items-center space-x-1">
                    <span class="{{ $field->is_required ? 'text-white' : '' }}">{{ strtoupper($field->label) }}</span>
                    @if($field->is_required)
                        <span class="text-red-400 font-bold text-sm" title="Zorunlu Alan">*</span>
                    @endif
                </div>
            </th>
        @endforeach"""

new_thead = """        @foreach($fieldsToRender as $field)
            <th class="px-2 sm:px-4 py-3.5 text-left text-[10px] sm:text-xs font-bold text-slate-300 uppercase tracking-widest bg-slate-900 border-b border-slate-700 group relative">
                <div class="flex flex-col space-y-1">
                    <div class="flex items-center space-x-1">
                        <span class="{{ $field->is_required ? 'text-white' : '' }}">{{ strtoupper($field->label) }}</span>
                        @if($field->is_required)
                            <span class="text-red-400 font-bold text-sm" title="Zorunlu Alan">*</span>
                        @endif
                    </div>
                    
                    <!-- Filter Dropdown Button -->
                    <div class="relative" x-data="{ open: false }">
                        <button type="button" @click.stop="open = !open; if(open) calcUniqueValues({{ $field->id }})" @click.away="open = false" 
                                class="flex items-center text-slate-400 hover:text-white transition-colors"
                                :class="{ 'text-indigo-400': filters[{{ $field->id }}] && filters[{{ $field->id }}].length > 0 }">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"></path></svg>
                            <span x-show="filters[{{ $field->id }}] && filters[{{ $field->id }}].length > 0" class="ml-1 text-[9px] font-bold bg-indigo-500 text-white rounded-full px-1.5 py-0.5" x-text="filters[{{ $field->id }}].length"></span>
                        </button>
                        
                        <!-- Dropdown Menu -->
                        <div x-show="open" x-transition.opacity
                             class="absolute z-50 mt-2 w-48 rounded-md shadow-lg bg-white ring-1 ring-black ring-opacity-5"
                             style="display: none; min-width: 200px;">
                            <div class="p-2 border-b border-gray-100 flex justify-between gap-2">
                                <button type="button" @click="selectAllFilters({{ $field->id }})" class="text-[10px] font-semibold text-indigo-600 hover:text-indigo-800">Tümünü Seç</button>
                                <button type="button" @click="clearFilter({{ $field->id }})" class="text-[10px] font-semibold text-red-600 hover:text-red-800">Temizle</button>
                            </div>
                            <div class="max-h-60 overflow-y-auto p-2">
                                <template x-for="val in uniqueValues[{{ $field->id }}] || []" :key="val">
                                    <label class="flex items-center space-x-2 p-1 hover:bg-gray-50 rounded cursor-pointer">
                                        <input type="checkbox" :value="val" x-model="filters[{{ $field->id }}]" @change="calculateTotals()" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                                        <span class="text-xs text-gray-700 truncate" x-text="val === '' ? '(Boş)' : val" :title="val === '' ? '(Boş)' : val"></span>
                                    </label>
                                </template>
                                <div x-show="!(uniqueValues[{{ $field->id }}] && uniqueValues[{{ $field->id }}].length > 0)" class="text-xs text-gray-400 text-center p-2">Veri yok</div>
                            </div>
                        </div>
                    </div>
                </div>
            </th>
        @endforeach"""
content = content.replace(old_thead, new_thead)


# 2. Update x-for in Tbody to use visibleRows()
old_tbody = """                                <template x-for="(row, rowIndex) in rows" :key="row.id">"""
new_tbody = """                                <template x-for="(row, originalRowIndex) in visibleRows()" :key="row.id">
                                    @php /* We map originalRowIndex to real index inside visibleRows logic, but we need rowIndex for inputs. Actually we can use a method to get real index. */ @endphp
                                    <tr class="hover:bg-indigo-50 transition-colors group" x-data="{ get rowIndex() { return rows.findIndex(r => r.id === row.id); } }">"""
content = content.replace(old_tbody, new_tbody)


# 3. Update AlpineJS Component State & Methods
js_old = """                    history: [],
                    totals: {},"""

js_new = """                    history: [],
                    totals: {},
                    filteredTotals: {},
                    filters: {}, // { fieldId: ['val1', 'val2'] }
                    uniqueValues: {}, // { fieldId: ['val1', 'val2'] }
                    
                    initFilters() {
                        this.fields.forEach(f => {
                            this.filters[f.id] = [];
                            this.uniqueValues[f.id] = [];
                        });
                    },
                    
                    calcUniqueValues(fieldId) {
                        let values = new Set();
                        this.rows.forEach(r => {
                            let val = r.values[fieldId];
                            // Check if val is undefined or null
                            if (val === undefined || val === null) val = '';
                            values.add(String(val).trim());
                        });
                        this.uniqueValues[fieldId] = Array.from(values).sort();
                        
                        // Ensure filters don't have orphan values
                        if (this.filters[fieldId]) {
                            this.filters[fieldId] = this.filters[fieldId].filter(v => this.uniqueValues[fieldId].includes(v));
                        }
                    },
                    
                    selectAllFilters(fieldId) {
                        this.calcUniqueValues(fieldId);
                        this.filters[fieldId] = [...this.uniqueValues[fieldId]];
                        this.calculateTotals();
                    },
                    
                    clearFilter(fieldId) {
                        this.filters[fieldId] = [];
                        this.calculateTotals();
                    },
                    
                    visibleRows() {
                        return this.rows.filter(row => {
                            for (const fieldId in this.filters) {
                                if (this.filters[fieldId] && this.filters[fieldId].length > 0) {
                                    let val = String(row.values[fieldId] || '').trim();
                                    if (!this.filters[fieldId].includes(val)) {
                                        return false; // Satır bu filtreden geçemedi
                                    }
                                }
                            }
                            return true; // Tüm filtrelerden geçti
                        });
                    },"""
content = content.replace(js_old, js_new)

js_init_old = """                        this.rows = existingRows;
                        this.calculateTotals();
                    @else"""

js_init_new = """                        this.rows = existingRows;
                        this.initFilters();
                        this.calculateTotals();
                    @else
                        this.initFilters();"""
content = content.replace(js_init_old, js_init_new)


# 4. Update calculateTotals for TWO sets of totals (filtered and unfiltered)
calc_totals_old = """                    let newTotals = {};
                    this.fields.forEach(f => {
                        if (f.is_summable && f.type === 'number') {
                            newTotals[f.id] = 0;
                        }
                    });

                    this.rows.forEach(r => {
                        this.fields.forEach(f => {
                            if (f.is_summable && f.type === 'number') {
                                let val = parseFloat(r.values[f.id]);
                                if (!isNaN(val)) {
                                    newTotals[f.id] = (parseFloat(newTotals[f.id] || 0) + val).toFixed(2);
                                }
                            }
                        });
                    });

                    // Tüm toplamlarda toFixed kalıcı olsun diye string'den float'a döndürüp tekrar toFixed(2) yapalım
                    Object.keys(newTotals).forEach(k => {
                        newTotals[k] = parseFloat(newTotals[k]).toFixed(2);
                    });

                    this.totals = newTotals;"""

calc_totals_new = """                    let newTotals = {};
                    let newFilteredTotals = {};
                    
                    this.fields.forEach(f => {
                        if (f.is_summable && f.type === 'number') {
                            newTotals[f.id] = 0;
                            newFilteredTotals[f.id] = 0;
                        }
                    });

                    // Genel Toplam Hesapla (Tümü)
                    this.rows.forEach(r => {
                        this.fields.forEach(f => {
                            if (f.is_summable && f.type === 'number') {
                                let val = parseFloat(r.values[f.id]);
                                if (!isNaN(val)) {
                                    newTotals[f.id] = (parseFloat(newTotals[f.id] || 0) + val).toFixed(2);
                                }
                            }
                        });
                    });
                    
                    // Filtrelenmiş Toplam Hesapla
                    let vRows = this.visibleRows();
                    vRows.forEach(r => {
                        this.fields.forEach(f => {
                            if (f.is_summable && f.type === 'number') {
                                let val = parseFloat(r.values[f.id]);
                                if (!isNaN(val)) {
                                    newFilteredTotals[f.id] = (parseFloat(newFilteredTotals[f.id] || 0) + val).toFixed(2);
                                }
                            }
                        });
                    });

                    Object.keys(newTotals).forEach(k => {
                        newTotals[k] = parseFloat(newTotals[k]).toFixed(2);
                        newFilteredTotals[k] = parseFloat(newFilteredTotals[k]).toFixed(2);
                    });

                    this.totals = newTotals;
                    this.filteredTotals = newFilteredTotals;"""
content = content.replace(calc_totals_old, calc_totals_new)


# 5. Update tfoot html for both totals
tfoot_old = """                                <tfoot class="bg-slate-50 border-t border-slate-300 shadow-[inset_0_1px_0_rgba(0,0,0,0.1)]">
                                    <tr>
                                        <td class="px-2 sm:px-4 py-4 whitespace-nowrap align-middle">
                                            <span class="font-black text-slate-700 tracking-wider text-xs">TOPLAM:</span>
                                        </td>
                                        <template x-for="field in fields" :key="'footer-'+field.id">
                                            <td class="px-2 sm:px-4 py-4 whitespace-nowrap min-w-[160px] align-middle">
                                                <template x-if="field.is_summable && field.type === 'number'">
                                                    <span class="font-black text-indigo-700 text-sm" x-text="totals[field.id] || 0"></span>
                                                </template>
                                            </td>
                                        </template>
                                        <td></td>
                                    </tr>
                                </tfoot>"""

tfoot_new = """                                <tfoot class="bg-slate-50 border-t border-slate-300 shadow-[inset_0_1px_0_rgba(0,0,0,0.1)]">
                                    <tr class="bg-indigo-50/50">
                                        <td class="px-2 sm:px-4 py-3 whitespace-nowrap align-middle border-b border-indigo-100">
                                            <span class="font-black text-indigo-800 tracking-wider text-[11px] uppercase">Görünen Toplam:</span>
                                        </td>
                                        <template x-for="field in fields" :key="'footer-filtered-'+field.id">
                                            <td class="px-2 sm:px-4 py-3 whitespace-nowrap min-w-[160px] align-middle border-b border-indigo-100">
                                                <template x-if="field.is_summable && field.type === 'number'">
                                                    <span class="font-black text-indigo-700 text-sm" x-text="filteredTotals[field.id] || 0"></span>
                                                </template>
                                            </td>
                                        </template>
                                        <td class="border-b border-indigo-100"></td>
                                    </tr>
                                    <tr>
                                        <td class="px-2 sm:px-4 py-3 whitespace-nowrap align-middle">
                                            <span class="font-bold text-slate-500 tracking-wider text-[10px] uppercase">Genel Toplam (Tümü):</span>
                                        </td>
                                        <template x-for="field in fields" :key="'footer-all-'+field.id">
                                            <td class="px-2 sm:px-4 py-3 whitespace-nowrap min-w-[160px] align-middle">
                                                <template x-if="field.is_summable && field.type === 'number'">
                                                    <span class="font-bold text-slate-500 text-xs" x-text="totals[field.id] || 0"></span>
                                                </template>
                                            </td>
                                        </template>
                                        <td></td>
                                    </tr>
                                </tfoot>"""
content = content.replace(tfoot_old, tfoot_new)

with open(filepath, 'w', encoding='utf-8') as f:
    f.write(content)

print("Updated create.blade.php successfully for filtering!")
