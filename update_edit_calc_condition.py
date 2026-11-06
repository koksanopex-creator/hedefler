import os

filepath = r'c:\Users\celal.karaman\Desktop\HEDEFLER\resources\views\form_fields\edit.blade.php'
with open(filepath, 'r', encoding='utf-8') as f:
    content = f.read()

# 1. Add AlpineJS properties
js_target = """                            is_calculated: f.is_calculated == 1,
                            calculation_formula: f.calculation_formula || '',
                            options_string: f.options ? JSON.stringify(f.options) : '',"""
js_replace = """                            is_calculated: f.is_calculated == 1,
                            calculation_formula: f.calculation_formula || '',
                            calc_condition_field_id: f.calc_condition_field_id || '',
                            calc_condition_value: f.calc_condition_value || '',
                            calc_condition_value_array: f.calc_condition_value ? f.calc_condition_value.split(',') : [],
                            options_string: f.options ? JSON.stringify(f.options) : '',"""
content = content.replace(js_target, js_replace)

js_add_target = """                        calculation_formula: '',
                        condition_field_id: '',
                        condition_value: '',"""
js_add_replace = """                        calculation_formula: '',
                        calc_condition_field_id: '',
                        calc_condition_value: '',
                        calc_condition_value_array: [],
                        condition_field_id: '',
                        condition_value: '',"""
content = content.replace(js_add_target, js_add_replace)


# 2. Add UI for calc_condition
html_target = """                                            <textarea x-model="field.calculation_formula" :name="'fields['+index+'][calculation_formula]'" class="w-full rounded-md border-blue-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm p-2 font-mono" rows="2" placeholder="Örn: #{12}# * #{15}#"></textarea>
                                        </div>
                                    </div>"""

html_replace = """                                            <textarea x-model="field.calculation_formula" :name="'fields['+index+'][calculation_formula]'" class="w-full rounded-md border-blue-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm p-2 font-mono" rows="2" placeholder="Örn: #{12}# * #{15}#"></textarea>
                                        </div>
                                        
                                        <!-- Koşullu Hesaplama Bölümü -->
                                        <div class="mt-4 pt-3 border-t border-blue-200 flex flex-col md:flex-row gap-4">
                                            <div class="w-full md:w-1/2">
                                                <label class="block text-xs font-bold text-blue-800 uppercase tracking-wider mb-1">Koşullu Hesaplama (Şart Var Mı?)</label>
                                                <select x-model="field.calc_condition_field_id" :name="'fields['+index+'][calc_condition_field_id]'" class="w-full rounded-md border-blue-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm p-2 bg-white">
                                                    <option value="">-- Her Zaman Hesaplansın --</option>
                                                    <template x-for="otherField in fields" :key="otherField.id || Math.random()">
                                                        <option x-show="otherField.id && otherField.id != field.id" :value="otherField.id" x-text="otherField.label"></option>
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
                                                        <div x-show="open" @click.away="open = false" style="display: none;" class="absolute z-50 mt-1 w-full bg-white border border-gray-300 rounded-md shadow-lg max-h-48 overflow-y-auto">
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
                                                        <div x-show="open" @click.away="open = false" style="display: none;" class="absolute z-50 mt-1 w-full bg-white border border-gray-300 rounded-md shadow-lg max-h-48 overflow-y-auto">
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
                                    </div>"""

content = content.replace(html_target, html_replace)

with open(filepath, 'w', encoding='utf-8') as f:
    f.write(content)

print("Updated edit.blade.php successfully.")
