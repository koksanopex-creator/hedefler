import os

filepath = r'c:\Users\celal.karaman\Desktop\HEDEFLER\resources\views\form_entries\create.blade.php'
with open(filepath, 'r', encoding='utf-8') as f:
    content = f.read()

# 1. Update <input type="number"> for readonly and style
input_target = """                                                <template x-if="field.type === 'number'">
                                                    <input type="number" step="any" x-model.number="row.values[field.id]" @paste="handlePaste($event, rowIndex, colIndex)" :name="'rows['+rowIndex+']['+field.id+']'" :required="isFieldRequired(field, rowIndex)" :class="{'border-l-4 border-l-red-500 border-gray-300': isFieldRequired(field, rowIndex) && !field.is_required, 'border-gray-300': !(isFieldRequired(field, rowIndex) && !field.is_required)}" class="w-full rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm" @input="calculateTotals()">
                                                </template>"""

input_replace = """                                                <template x-if="field.type === 'number'">
                                                    <input type="number" step="any" x-model.number="row.values[field.id]" @paste="handlePaste($event, rowIndex, colIndex)" :name="'rows['+rowIndex+']['+field.id+']'" :required="isFieldRequired(field, rowIndex) && !field.is_calculated" :readonly="field.is_calculated" :class="{'bg-gray-100 cursor-not-allowed text-gray-700': field.is_calculated, 'border-l-4 border-l-red-500 border-gray-300': isFieldRequired(field, rowIndex) && !field.is_required && !field.is_calculated, 'border-gray-300': !(isFieldRequired(field, rowIndex) && !field.is_required && !field.is_calculated)}" class="w-full rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm" @input="calculateTotals()">
                                                </template>"""

content = content.replace(input_target, input_replace)


# 2. Update calculateTotals() function
calc_target = """                calculateTotals() {
                    if (!this.showTotals) return;
                    
                    let newTotals = {};
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
                                    newTotals[f.id] += val;
                                }
                            }
                        });
                    });

                    this.totals = newTotals;
                },"""

calc_replace = """                calculateTotals() {
                    // Önce formülleri hesapla
                    this.rows.forEach(r => {
                        this.fields.forEach(f => {
                            if (f.is_calculated && f.calculation_formula) {
                                let formula = f.calculation_formula;
                                // formüldeki #{id}# yapılarını satırdaki değerlerle değiştir
                                this.fields.forEach(otherField => {
                                    if (otherField.type === 'number') {
                                        let regex = new RegExp('#{' + otherField.id + '}#', 'g');
                                        let val = parseFloat(r.values[otherField.id]) || 0;
                                        formula = formula.replace(regex, val);
                                    }
                                });
                                // Güvenli şekilde hesapla
                                try {
                                    // Noktalamayı ve güvenliği sağlamak için Math işlemleri vs yapılabilir
                                    // Ancak eval yerine Function kullanmak daha güvenli (kapsam kısıtlı)
                                    let result = Function('return (' + formula + ')')();
                                    if (!isNaN(result) && result !== Infinity && result !== -Infinity) {
                                        // Ondalık sayıyı virgülden sonra makul uzunlukta tut (2 hane) ama tam sayılar için dokunma
                                        let rounded = Math.round(result * 10000) / 10000;
                                        r.values[f.id] = rounded;
                                    }
                                } catch (e) {
                                    // Formül hatalıysa (henüz eksik yazılmış olabilir) yoksay
                                }
                            }
                        });
                    });

                    if (!this.showTotals) return;
                    
                    let newTotals = {};
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
                                    newTotals[f.id] += val;
                                }
                            }
                        });
                    });

                    this.totals = newTotals;
                },"""

content = content.replace(calc_target, calc_replace)

with open(filepath, 'w', encoding='utf-8') as f:
    f.write(content)

print("Updated create.blade.php successfully.")
