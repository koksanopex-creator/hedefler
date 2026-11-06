import os

filepath = r'c:\Users\celal.karaman\Desktop\HEDEFLER\app\Services\FormExportS.php'
with open(filepath, 'r', encoding='utf-8') as f:
    content = f.read()

# 1. Update function signature
old_sig = "public function exportToExcel(FormEntryM $formEntry)"
new_sig = "public function exportToExcel(FormEntryM $formEntry, array $filters = [])"
content = content.replace(old_sig, new_sig)


# 2. Add filtering logic before writing rows
old_rows_loop = """        // Verileri Yaz
        $startRow = file_exists($templatePath) ? 6 : 2; // Dinamikse 2. satırdan başlar
        
        foreach ($formEntry->rows as $rowIndex => $row) {"""

new_rows_loop = """        // Verileri Yaz
        $startRow = file_exists($templatePath) ? 6 : 2; // Dinamikse 2. satırdan başlar
        
        // Gelen filtrelere göre satırları süz
        $rowsToExport = collect($formEntry->rows)->filter(function($row) use ($filters) {
            foreach ($filters as $fieldId => $allowedValues) {
                if (empty($allowedValues)) continue;
                
                $valObj = $row->values->where('form_field_id', $fieldId)->first();
                $val = trim((string)($valObj->value ?? ''));
                
                if (!in_array($val, $allowedValues)) {
                    return false;
                }
            }
            return true;
        })->values(); // Reset array keys
        
        foreach ($rowsToExport as $rowIndex => $row) {"""

content = content.replace(old_rows_loop, new_rows_loop)


# 3. Fix lastDataRow calculation
old_last_data = "$lastDataRow = $startRow + $formEntry->rows->count();"
new_last_data = "$lastDataRow = $startRow + $rowsToExport->count();"
content = content.replace(old_last_data, new_last_data)


with open(filepath, 'w', encoding='utf-8') as f:
    f.write(content)

print("Updated FormExportS.php successfully!")
