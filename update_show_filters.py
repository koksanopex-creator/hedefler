import os
import json

filepath = r'c:\Users\celal.karaman\Desktop\HEDEFLER\resources\views\form_entries\show.blade.php'
with open(filepath, 'r', encoding='utf-8') as f:
    content = f.read()

# 1. Update the Excel Export Button (to trigger AlpineJS function)
old_excel_btn = """                @if(app(\\App\\Services\\PermissionS::class)->hasPermission(Auth::user(), 'exports.manage'))
                <a href="{{ route('form-entries.export', $formEntry) }}" class="inline-flex items-center px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-semibold uppercase tracking-widest rounded-lg shadow-sm transition">
                    <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                    Excel
                </a>
                @endif"""

new_excel_btn = """                @if(app(\\App\\Services\\PermissionS::class)->hasPermission(Auth::user(), 'exports.manage'))
                <button type="button" @click="if(typeof document.getElementById('exportExcelBtn') !== 'undefined' && document.getElementById('exportExcelBtn')) document.getElementById('exportExcelBtn').click()" class="inline-flex items-center px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-semibold uppercase tracking-widest rounded-lg shadow-sm transition">
                    <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                    Excel
                </button>
                @endif"""
content = content.replace(old_excel_btn, new_excel_btn)


# 2. Convert the table into AlpineJS format
old_table_section = """            <div class="bg-white shadow-xl sm:rounded-lg border border-gray-200">
                <div class="overflow-x-auto w-full pb-2">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-800">
                            <tr>
                                <th class="px-4 py-3 text-center text-xs font-medium text-white uppercase tracking-wider bg-gray-800">#</th>
                                @foreach($fieldsToRender as $field)
                                    <th class="px-2 sm:px-4 py-3 text-left text-[10px] sm:text-xs font-medium text-white uppercase tracking-wider bg-gray-800">
                                        {{ strtoupper($field->label) }}
                                    </th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($formEntry->rows as $index => $row)
                                <tr class="hover:bg-indigo-50/50 transition-colors group">
                                    <td class="px-4 py-3 whitespace-nowrap text-center text-sm font-medium text-gray-500">{{ $index + 1 }}</td>
                                    @foreach($fieldsToRender as $field)
                                        @php
                                            $val = $row->values->where('form_field_id', $field->id)->first();
                                        @endphp
                                        <td class="px-4 py-3 text-sm text-gray-900 whitespace-nowrap">
                                            @if($field->type === 'number' && isset($val->value) && is_numeric($val->value))
                                                @php
                                                    $floatVal = floatval($val->value);
                                                @endphp
                                                {{ strpos($val->value, '.') !== false ? number_format($floatVal, 2, ',', '.') : $val->value }}
                                            @else
                                                {{ $val->value ?? '-' }}
                                            @endif
                                        </td>
                                    @endforeach
                                </tr>
                            @endforeach
                        </tbody>
                        
                        @if($formEntry->template->show_row_totals)
                        <tfoot class="bg-slate-50 border-t-4 border-slate-200">
                            <tr>
                                <td class="px-2 sm:px-4 py-4 font-black text-right text-slate-700 tracking-wider text-xs" colspan="1">TOPLAM:</td>
                                @foreach($fieldsToRender as $field)
                                    <td class="px-2 sm:px-4 py-4 text-left">
                                        @if($field->is_summable && $field->type === 'number')
                                            @php
                                                $sum = 0;
                                                foreach($formEntry->rows as $row) {
                                                    $valObj = $row->values->where('form_field_id', $field->id)->first();
                                                    $sum += floatval($valObj->value ?? 0);
                                                }
                                            @endphp
                                            <span class="font-black text-indigo-700 text-sm bg-indigo-100/50 px-2.5 py-1 rounded-md border border-indigo-200/50">
                                                {{ number_format($sum, 2, ',', '.') }}
                                            </span>
                                        @endif
                                    </td>
                                @endforeach
                            </tr>
                        </tfoot>
                        @endif
                    </table>
                </div>
            </div>"""

new_table_section = """            <div class="bg-white shadow-xl sm:rounded-lg border border-gray-200" x-data="formShowApp({!! htmlspecialchars(json_encode($fieldsToRender), ENT_QUOTES, 'UTF-8') !!}, {{ $formEntry->template->show_row_totals ? 'true' : 'false' }})">
                <div class="overflow-x-auto w-full pb-2 max-h-[70vh]">
                    <!-- Gizli form (Excel Export için) -->
                    <form id="exportForm" action="{{ route('form-entries.export', $formEntry) }}" method="GET" style="display:none;">
                        <template x-for="(values, fieldId) in filters" :key="fieldId">
                            <template x-for="val in values" :key="val">
                                <input type="hidden" :name="'filters['+fieldId+'][]'" :value="val">
                            </template>
                        </template>
                        <button type="submit" id="exportExcelBtn">Export</button>
                    </form>

                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-800 sticky top-0 z-20 shadow-md">
                            <tr>
                                <th class="w-12 px-4 py-3 text-center text-xs font-medium text-white uppercase tracking-wider bg-gray-800">#</th>
                                
                                <template x-for="field in fields" :key="field.id">
                                    <th class="px-2 sm:px-4 py-3 text-left text-[10px] sm:text-xs font-medium text-white uppercase tracking-wider group relative bg-gray-800 min-w-[160px]">
                                        <div class="flex flex-col space-y-1">
                                            <div class="flex items-center justify-between">
                                                <span x-text="field.label.toUpperCase()"></span>
                                                <div class="flex items-center space-x-1">
                                                    <!-- Filter Dropdown Button -->
                                                    <div class="relative" x-data="{ open: false }">
                                                        <button type="button" @click.stop="open = !open; if(open) calcUniqueValues(field.id)" @click.away="open = false" 
                                                                class="flex items-center text-gray-400 hover:text-white transition-colors p-1"
                                                                :class="{ 'text-indigo-300': filters[field.id] && filters[field.id].length > 0 }">
                                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"></path></svg>
                                                            <span x-show="filters[field.id] && filters[field.id].length > 0" class="ml-1 text-[9px] font-bold bg-indigo-500 text-white rounded-full px-1.5 py-0.5" x-text="filters[field.id].length"></span>
                                                        </button>
                                                        
                                                        <!-- Dropdown Menu -->
                                                        <div x-show="open" x-transition.opacity
                                                             class="absolute z-50 mt-2 w-48 rounded-md shadow-lg bg-white ring-1 ring-black ring-opacity-5"
                                                             style="display: none; min-width: 200px;">
                                                            <div class="p-2 border-b border-gray-100 flex justify-between gap-2">
                                                                <button type="button" @click="selectAllFilters(field.id)" class="text-[10px] font-semibold text-indigo-600 hover:text-indigo-800">Tümünü Seç</button>
                                                                <button type="button" @click="clearFilter(field.id)" class="text-[10px] font-semibold text-red-600 hover:text-red-800">Temizle</button>
                                                            </div>
                                                            <div class="max-h-60 overflow-y-auto p-2">
                                                                <template x-for="val in uniqueValues[field.id] || []" :key="val">
                                                                    <label class="flex items-center space-x-2 p-1 hover:bg-gray-50 rounded cursor-pointer">
                                                                        <input type="checkbox" :value="val" x-model="filters[field.id]" @change="calculateTotals()" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                                                                        <span class="text-xs text-gray-700 truncate" x-text="val === '' ? '(Boş)' : val" :title="val === '' ? '(Boş)' : val"></span>
                                                                    </label>
                                                                </template>
                                                                <div x-show="!(uniqueValues[field.id] && uniqueValues[field.id].length > 0)" class="text-xs text-gray-400 text-center p-2">Veri yok</div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </th>
                                </template>
                            </tr>
                        </thead>
                        
                        <tbody class="bg-white divide-y divide-gray-200">
                            <template x-for="(row, originalRowIndex) in visibleRows()" :key="row.id">
                                <tr class="hover:bg-indigo-50 transition-colors group">
                                    <td class="px-4 py-3 whitespace-nowrap text-center text-sm font-medium text-gray-500" x-text="originalRowIndex + 1"></td>
                                    <template x-for="field in fields" :key="field.id">
                                        <td class="px-4 py-3 text-sm text-gray-900 whitespace-nowrap">
                                            <span x-text="formatValue(row.values[field.id], field.type)"></span>
                                        </td>
                                    </template>
                                </tr>
                            </template>
                        </tbody>
                        
                        <template x-if="showTotals">
                            <tfoot class="bg-gray-100">
                                <tr class="bg-indigo-50/50">
                                    <td class="px-4 py-3 font-bold text-right text-indigo-800 text-[11px] uppercase border-b border-indigo-100" colspan="1">Görünen Toplam:</td>
                                    <template x-for="field in fields" :key="'filtered-'+field.id">
                                        <td class="px-4 py-3 font-bold text-indigo-700 text-left whitespace-nowrap border-b border-indigo-100">
                                            <template x-if="field.is_summable && field.type === 'number'">
                                                <span x-text="filteredTotals[field.id] || 0"></span>
                                            </template>
                                        </td>
                                    </template>
                                </tr>
                                <tr>
                                    <td class="px-4 py-3 font-bold text-right text-gray-500 text-[10px] uppercase" colspan="1">Genel Toplam (Tümü):</td>
                                    <template x-for="field in fields" :key="'all-'+field.id">
                                        <td class="px-4 py-3 font-bold text-gray-500 text-left whitespace-nowrap">
                                            <template x-if="field.is_summable && field.type === 'number'">
                                                <span x-text="totals[field.id] || 0"></span>
                                            </template>
                                        </td>
                                    </template>
                                </tr>
                            </tfoot>
                        </template>
                    </table>
                </div>
            </div>"""
content = content.replace(old_table_section, new_table_section)

# 3. Inject AlpineJS Script for show.blade.php
script_block = """
    <!-- AlpineJS Grid Logic for Show -->
    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('formShowApp', (fields, showTotals) => ({
                fields: fields,
                showTotals: showTotals,
                rows: [],
                totals: {},
                filteredTotals: {},
                filters: {}, 
                uniqueValues: {}, 
                
                init() {
                    // Blade'den gelen verileri JSON objesine aktarma
                    let existingRows = {!! json_encode($formEntry->rows->map(function($row) {
                        $values = [];
                        foreach($row->values as $val) {
                            $values[$val->form_field_id] = $val->value;
                        }
                        return ['id' => $row->id, 'values' => (object)$values];
                    })) !!};
                    this.rows = existingRows;
                    
                    this.fields.forEach(f => {
                        this.filters[f.id] = [];
                        this.uniqueValues[f.id] = [];
                    });
                    
                    this.calculateTotals();
                },
                
                calcUniqueValues(fieldId) {
                    let values = new Set();
                    this.rows.forEach(r => {
                        let val = r.values[fieldId];
                        if (val === undefined || val === null) val = '';
                        values.add(String(val).trim());
                    });
                    this.uniqueValues[fieldId] = Array.from(values).sort();
                    
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
                                    return false; 
                                }
                            }
                        }
                        return true; 
                    });
                },
                
                calculateTotals() {
                    if (!this.showTotals) return;
                    
                    let newTotals = {};
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
                    this.filteredTotals = newFilteredTotals;
                },
                
                formatValue(val, type) {
                    if (val === undefined || val === null) return '-';
                    if (type === 'number') {
                        let fVal = parseFloat(val);
                        if (!isNaN(fVal) && String(val).includes('.')) {
                            return fVal.toFixed(2);
                        }
                    }
                    return val;
                }
            }));
        });
    </script>
</x-app-layout>"""

content = content.replace("</x-app-layout>", script_block)

with open(filepath, 'w', encoding='utf-8') as f:
    f.write(content)

print("Updated show.blade.php successfully!")
