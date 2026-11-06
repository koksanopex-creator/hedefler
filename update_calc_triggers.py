import os

filepath = r'c:\Users\celal.karaman\Desktop\HEDEFLER\resources\views\form_entries\create.blade.php'
with open(filepath, 'r', encoding='utf-8') as f:
    content = f.read()

# Make calculateTotals run on master_data change
target1 = """@change="handleMasterDataChange(rowIndex, field.id)""""
replace1 = """@change="handleMasterDataChange(rowIndex, field.id); calculateTotals()""""
content = content.replace(target1, replace1)

# Make calculateTotals run on normal select change
target2 = """@keydown.ctrl.v.prevent="handleSelectPaste($event, rowIndex, colIndex)" :name"""
replace2 = """@keydown.ctrl.v.prevent="handleSelectPaste($event, rowIndex, colIndex)" @change="calculateTotals()" :name"""
content = content.replace(target2, replace2)

# Make calculateTotals run on text input
target3 = """<input type="text" x-model="row.values[field.id]" @paste="handlePaste($event, rowIndex, colIndex)" :name"""
replace3 = """<input type="text" x-model="row.values[field.id]" @paste="handlePaste($event, rowIndex, colIndex)" @input="calculateTotals()" :name"""
content = content.replace(target3, replace3)


with open(filepath, 'w', encoding='utf-8') as f:
    f.write(content)

print("Updated create.blade.php successfully.")
