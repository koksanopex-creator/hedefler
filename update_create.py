import os

filepath = r'c:\Users\celal.karaman\Desktop\HEDEFLER\resources\views\form_entries\create.blade.php'
with open(filepath, 'r', encoding='utf-8') as f:
    content = f.read()

# Replace required attribute for all types
content = content.replace(':required="field.is_required"', ':required="isFieldRequired(field, rowIndex)"')

# Add class binding for text, number, textarea, date
target_class = 'class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm"'
replacement_class = ':class="{\'border-l-4 border-l-red-500 border-gray-300\': isFieldRequired(field, rowIndex) && !field.is_required, \'border-gray-300\': !(isFieldRequired(field, rowIndex) && !field.is_required)}" class="w-full rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm"'
content = content.replace(target_class, replacement_class)

# Add class binding for textarea with resize-y
target_class2 = 'class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm resize-y"'
replacement_class2 = ':class="{\'border-l-4 border-l-red-500 border-gray-300\': isFieldRequired(field, rowIndex) && !field.is_required, \'border-gray-300\': !(isFieldRequired(field, rowIndex) && !field.is_required)}" class="w-full rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm resize-y"'
content = content.replace(target_class2, replacement_class2)

# Update select and master_data
target_select_class = ":class=\"row.values[field.id] ? 'bg-indigo-50 border-indigo-300 text-indigo-700 font-bold' : 'bg-white border-gray-300 text-gray-700'\""
replacement_select_class = ":class=\"{ 'bg-indigo-50 border-indigo-300 text-indigo-700 font-bold': row.values[field.id], 'bg-white border-gray-300 text-gray-700': !row.values[field.id], 'border-l-4 border-l-red-500': isFieldRequired(field, rowIndex) && !field.is_required }\""
content = content.replace(target_select_class, replacement_select_class)

# Add the JS function to Alpine
js_func = """
                isFieldRequired(field, rowIndex) {
                    if (field.is_required) return true;
                    if (field.condition_field_id && field.condition_value) {
                        let val = this.rows[rowIndex].values[field.condition_field_id];
                        if (val) {
                            let conditions = field.condition_value.split(',').map(s => s.trim().toLowerCase());
                            if (conditions.includes(val.toLowerCase())) {
                                return true;
                            }
                        }
                    }
                    return false;
                },
                
                init() {"""
content = content.replace('init() {', js_func)

with open(filepath, 'w', encoding='utf-8') as f:
    f.write(content)

print("Updated create.blade.php successfully.")
