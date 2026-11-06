import os

filepath = r'c:\Users\celal.karaman\Desktop\HEDEFLER\resources\views\form_fields\edit.blade.php'
with open(filepath, 'r', encoding='utf-8') as f:
    content = f.read()

# 1. Add Checkbox
checkbox_target = """                                        <label class="flex items-center text-sm text-gray-700" x-show="field.value_type === 'nicel' && field.type === 'number'">
                                            <input type="hidden" :name="'fields['+index+'][is_summable]'" value="0">
                                            <input type="checkbox" x-model="field.is_summable" :name="'fields['+index+'][is_summable]'" value="1" class="h-4 w-4 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500 mr-2">
                                            Alt Toplamı Alınabilir
                                        </label>
                                    </div>"""

checkbox_replace = """                                        <label class="flex items-center text-sm text-gray-700" x-show="field.value_type === 'nicel' && field.type === 'number'">
                                            <input type="hidden" :name="'fields['+index+'][is_summable]'" value="0">
                                            <input type="checkbox" x-model="field.is_summable" :name="'fields['+index+'][is_summable]'" value="1" class="h-4 w-4 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500 mr-2">
                                            Alt Toplamı Alınabilir
                                        </label>
                                        
                                        <label class="flex items-center text-sm text-gray-700" x-show="field.type === 'number'">
                                            <input type="hidden" :name="'fields['+index+'][is_calculated]'" value="0">
                                            <input type="checkbox" x-model="field.is_calculated" :name="'fields['+index+'][is_calculated]'" value="1" class="h-4 w-4 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500 mr-2">
                                            Hesaplamalı Alan (Formül)
                                        </label>
                                    </div>
                                    
                                    <!-- Hesaplamalı Alan (Formül) Bölümü -->
                                    <div class="md:col-span-4 mt-3 bg-blue-50 p-4 rounded-lg border border-blue-200 flex flex-col gap-2 shadow-sm" x-show="field.is_calculated && field.type === 'number'">
                                        <label class="block text-xs font-bold text-blue-800 uppercase tracking-wider mb-1">Formül Tanımlama</label>
                                        <div class="flex flex-col gap-2">
                                            <div class="flex flex-col md:flex-row items-center gap-2 w-full">
                                                <select x-ref="'fieldSelect' + index" class="w-full md:w-1/3 rounded-md border-blue-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm p-2 bg-white">
                                                    <option value="">-- Alan Seçip Ekle --</option>
                                                    <template x-for="otherField in fields" :key="otherField.id || Math.random()">
                                                        <option x-show="otherField.id && otherField.id != field.id && otherField.type === 'number'" :value="'#{' + otherField.id + '}#'" x-text="otherField.label"></option>
                                                    </template>
                                                </select>
                                                <button type="button" @click="field.calculation_formula = (field.calculation_formula || '') + ' ' + $refs['fieldSelect' + index].value + ' '; $refs['fieldSelect' + index].value = '';" class="bg-blue-600 hover:bg-blue-700 text-white px-3 py-2 rounded-md text-sm font-semibold transition shadow-sm whitespace-nowrap">
                                                    Formüle Ekle
                                                </button>
                                            </div>
                                            <div class="text-[10px] text-blue-600 mt-1">Matematiksel işlemleri (+, -, *, /) ve sabit sayıları doğrudan aşağıya yazabilirsiniz. (Örn: #{12}# * 1.18 + 50)</div>
                                            <textarea x-model="field.calculation_formula" :name="'fields['+index+'][calculation_formula]'" class="w-full rounded-md border-blue-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm p-2 font-mono" rows="2" placeholder="Örn: #{12}# * #{15}#"></textarea>
                                        </div>
                                    </div>"""

content = content.replace(checkbox_target, checkbox_replace)


# 2. Add properties to AlpineJS init()
js_target = """                            condition_field_id: f.condition_field_id || '',
                            condition_value: f.condition_value || '',
                            condition_value_array: f.condition_value ? f.condition_value.split(',') : [],
                            options_string: f.options ? JSON.stringify(f.options) : '',"""

js_replace = """                            condition_field_id: f.condition_field_id || '',
                            condition_value: f.condition_value || '',
                            condition_value_array: f.condition_value ? f.condition_value.split(',') : [],
                            is_calculated: f.is_calculated == 1,
                            calculation_formula: f.calculation_formula || '',
                            options_string: f.options ? JSON.stringify(f.options) : '',"""

content = content.replace(js_target, js_replace)

# 3. Add properties to AlpineJS addField()
js_add_target = """                        is_required: false,
                        is_summable: false,
                        condition_field_id: '',
                        condition_value: '',
                        condition_value_array: []"""

js_add_replace = """                        is_required: false,
                        is_summable: false,
                        is_calculated: false,
                        calculation_formula: '',
                        condition_field_id: '',
                        condition_value: '',
                        condition_value_array: []"""

content = content.replace(js_add_target, js_add_replace)

with open(filepath, 'w', encoding='utf-8') as f:
    f.write(content)

print("Updated edit.blade.php successfully.")
