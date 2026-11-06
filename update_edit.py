import os

filepath = r'c:\Users\celal.karaman\Desktop\HEDEFLER\resources\views\form_fields\edit.blade.php'
with open(filepath, 'r', encoding='utf-8') as f:
    content = f.read()

html_target = """                                        <div class="w-full md:w-1/2" x-show="field.condition_field_id">
                                            <label class="block text-xs font-bold text-orange-800 uppercase tracking-wider mb-1">Hangi Değer Seçilirse Zorunlu Olsun?</label>
                                            <input type="text" x-model="field.condition_value" :name="'fields['+index+'][condition_value]'" class="w-full rounded-md border-orange-300 shadow-sm focus:border-orange-500 focus:ring-orange-500 text-sm p-2" placeholder="Örn: Şirket İçi (Çoklu değer için virgül kullanın)">
                                        </div>"""

html_replace = """                                        <div class="w-full md:w-1/2" x-show="field.condition_field_id">
                                            <label class="block text-xs font-bold text-orange-800 uppercase tracking-wider mb-1">Hangi Değer Seçilirse Zorunlu Olsun?</label>
                                            
                                            <!-- Master Data (Çoklu Seçim) -->
                                            <template x-if="getConditionFieldType(field.condition_field_id) === 'master_data'">
                                                <select x-model="field.condition_value_array" :name="'fields['+index+'][condition_value][]'" multiple class="w-full rounded-md border-orange-300 shadow-sm focus:border-orange-500 focus:ring-orange-500 text-sm p-2 bg-white" style="min-height: 80px;">
                                                    <template x-for="item in getConditionFieldMasterDataItems(field.condition_field_id)" :key="item.id">
                                                        <option :value="item.name" x-text="item.name"></option>
                                                    </template>
                                                </select>
                                            </template>
                                            
                                            <!-- Select (Çoklu Seçim) -->
                                            <template x-if="getConditionFieldType(field.condition_field_id) === 'select'">
                                                <select x-model="field.condition_value_array" :name="'fields['+index+'][condition_value][]'" multiple class="w-full rounded-md border-orange-300 shadow-sm focus:border-orange-500 focus:ring-orange-500 text-sm p-2 bg-white" style="min-height: 80px;">
                                                    <template x-for="opt in getConditionFieldSelectOptions(field.condition_field_id)" :key="opt">
                                                        <option :value="opt" x-text="opt"></option>
                                                    </template>
                                                </select>
                                            </template>
                                            
                                            <!-- Normal Metin Kutusu (Diğerleri) -->
                                            <template x-if="getConditionFieldType(field.condition_field_id) !== 'master_data' && getConditionFieldType(field.condition_field_id) !== 'select'">
                                                <input type="text" x-model="field.condition_value" :name="'fields['+index+'][condition_value]'" class="w-full rounded-md border-orange-300 shadow-sm focus:border-orange-500 focus:ring-orange-500 text-sm p-2" placeholder="Örn: Şirket İçi (Çoklu değer için virgül kullanın)">
                                            </template>
                                            
                                            <p class="text-[10px] text-orange-600 mt-1" x-show="getConditionFieldType(field.condition_field_id) === 'master_data' || getConditionFieldType(field.condition_field_id) === 'select'">Çoklu seçim yapmak için CTRL (veya CMD) tuşuna basılı tutarak tıklayın.</p>
                                        </div>"""

content = content.replace(html_target, html_replace)

js_target = """                        init() {
                    if (initialFields && initialFields.length > 0) {
                        this.fields = initialFields.map(f => ({
                            ...f,
                            is_required: f.is_required == 1,
                            is_summable: f.is_summable == 1,
                            condition_field_id: f.condition_field_id || '',
                            condition_value: f.condition_value || '',
                            options_string: f.options ? JSON.stringify(f.options) : '',
                            master_data_type_id: f.master_data_type_id || ''
                        }));
                    }
                },"""

js_replace = """                masterDataTypes: @json($masterDataTypes),
                
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
                
                init() {
                    if (initialFields && initialFields.length > 0) {
                        this.fields = initialFields.map(f => ({
                            ...f,
                            is_required: f.is_required == 1,
                            is_summable: f.is_summable == 1,
                            condition_field_id: f.condition_field_id || '',
                            condition_value: f.condition_value || '',
                            condition_value_array: f.condition_value ? f.condition_value.split(',') : [],
                            options_string: f.options ? JSON.stringify(f.options) : '',
                            master_data_type_id: f.master_data_type_id || ''
                        }));
                    }
                },"""

content = content.replace(js_target, js_replace)

js_add_field_target = """                        condition_field_id: '',
                        condition_value: ''"""

js_add_field_replace = """                        condition_field_id: '',
                        condition_value: '',
                        condition_value_array: []"""

content = content.replace(js_add_field_target, js_add_field_replace)

with open(filepath, 'w', encoding='utf-8') as f:
    f.write(content)

print("Updated edit.blade.php successfully.")
