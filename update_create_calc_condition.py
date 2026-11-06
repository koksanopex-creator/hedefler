import os

filepath = r'c:\Users\celal.karaman\Desktop\HEDEFLER\resources\views\form_entries\create.blade.php'
with open(filepath, 'r', encoding='utf-8') as f:
    content = f.read()

# 1. Add isFieldCalculated function
js_target = """                isFieldRequired(field, rowIndex) {"""

js_replace = """                isFieldCalculated(field, rowIndex) {
                    if (!field.is_calculated) return false;
                    
                    if (field.calc_condition_field_id && field.calc_condition_value) {
                        let val = this.rows[rowIndex].values[field.calc_condition_field_id];
                        if (val) {
                            let conditions = field.calc_condition_value.split(',').map(s => s.trim().toLowerCase());
                            if (conditions.includes(String(val).toLowerCase())) {
                                return true;
                            }
                        }
                        return false; // Şart var ama sağlanmamış
                    }
                    
                    return true; // Şart yok, her zaman hesaplanıyor
                },
                
                isFieldRequired(field, rowIndex) {"""

content = content.replace(js_target, js_replace)


# 2. Update input fields to use isFieldCalculated instead of is_calculated
input_target = """                                                <template x-if="field.type === 'number'">
                                                    <input type="number" step="any" x-model.number="row.values[field.id]" @paste="handlePaste($event, rowIndex, colIndex)" :name="'rows['+rowIndex+']['+field.id+']'" :required="isFieldRequired(field, rowIndex) && !field.is_calculated" :readonly="field.is_calculated" :class="{'bg-gray-100 cursor-not-allowed text-gray-700': field.is_calculated, 'border-l-4 border-l-red-500 border-gray-300': isFieldRequired(field, rowIndex) && !field.is_required && !field.is_calculated, 'border-gray-300': !(isFieldRequired(field, rowIndex) && !field.is_required && !field.is_calculated)}" class="w-full rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm" @input="calculateTotals()">
                                                </template>"""

input_replace = """                                                <template x-if="field.type === 'number'">
                                                    <input type="number" step="any" x-model.number="row.values[field.id]" @paste="handlePaste($event, rowIndex, colIndex)" :name="'rows['+rowIndex+']['+field.id+']'" :required="isFieldRequired(field, rowIndex) && !isFieldCalculated(field, rowIndex)" :readonly="isFieldCalculated(field, rowIndex)" :class="{'bg-gray-100 cursor-not-allowed text-gray-700': isFieldCalculated(field, rowIndex), 'border-l-4 border-l-red-500 border-gray-300': isFieldRequired(field, rowIndex) && !field.is_required && !isFieldCalculated(field, rowIndex), 'border-gray-300': !(isFieldRequired(field, rowIndex) && !field.is_required && !isFieldCalculated(field, rowIndex))}" class="w-full rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm" @input="calculateTotals()">
                                                </template>"""

content = content.replace(input_target, input_replace)


# 3. Update calculateTotals() to check isFieldCalculated
calc_target = """                    // Önce formülleri hesapla
                    this.rows.forEach(r => {
                        this.fields.forEach(f => {
                            if (f.is_calculated && f.calculation_formula) {"""

calc_replace = """                    // Önce formülleri hesapla
                    this.rows.forEach((r, rIndex) => {
                        this.fields.forEach(f => {
                            if (this.isFieldCalculated(f, rIndex) && f.calculation_formula) {"""

content = content.replace(calc_target, calc_replace)


# 4. Clear the value if the condition is not met (optional but recommended in plan)
calc_target_2 = """                                        r.values[f.id] = rounded;
                                    }
                                } catch (e) {
                                    // Formül hatalıysa (henüz eksik yazılmış olabilir) yoksay
                                }
                            }
                        });"""

calc_replace_2 = """                                        r.values[f.id] = rounded;
                                    }
                                } catch (e) {
                                    // Formül hatalıysa (henüz eksik yazılmış olabilir) yoksay
                                }
                            } else if (f.is_calculated && !this.isFieldCalculated(f, rIndex)) {
                                // Şart sağlanmıyorsa (artık elle girilecek demektir)
                                // İstediğiniz gibi burayı elle girişe açık bırakıyoruz.
                                // Kullanıcı isterse değiştirebilir. (İçini tamamen silmiyorum, son değer kalabilir).
                            }
                        });"""

content = content.replace(calc_target_2, calc_replace_2)

with open(filepath, 'w', encoding='utf-8') as f:
    f.write(content)

print("Updated create.blade.php successfully.")
