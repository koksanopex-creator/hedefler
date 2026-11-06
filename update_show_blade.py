import os

filepath = r'c:\Users\celal.karaman\Desktop\HEDEFLER\resources\views\form_entries\show.blade.php'
with open(filepath, 'r', encoding='utf-8') as f:
    content = f.read()

# 1. Update table wrapper
old_wrapper = """            <!-- Veri Tablosu -->
            <div class="bg-white shadow-xl sm:rounded-lg border border-gray-200 mb-8">
                <div class="p-3 sm:p-4 border-b border-gray-100 bg-gray-50 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-2">
                    <span class="text-gray-700 font-bold text-sm sm:text-base">Girilen Veriler</span>"""

new_wrapper = """            <!-- Veri Tablosu -->
            <div class="bg-white shadow-xl sm:rounded-xl overflow-hidden ring-1 ring-black/5 mb-8">
                <div class="p-4 sm:p-5 border-b border-gray-200 bg-gradient-to-r from-gray-50 to-white flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                    <div class="flex items-center space-x-3">
                        <div class="p-2 bg-indigo-100 text-indigo-600 rounded-lg shadow-inner">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"></path></svg>
                        </div>
                        <h3 class="text-gray-900 font-extrabold text-lg tracking-tight">Girilen Veriler</h3>
                    </div>"""
content = content.replace(old_wrapper, new_wrapper)

# 2. Update table div and thead
old_thead = """                <div>
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-800 sticky top-16 z-10 shadow-md">
                            <tr>
                                <th class="w-12 px-2 sm:px-4 py-3 text-center text-xs font-medium text-white uppercase tracking-wider bg-gray-800">#</th>
                                @foreach($fieldsToRender as $field)
                                    <th class="px-2 sm:px-4 py-3 text-left text-[10px] sm:text-xs font-medium text-white uppercase tracking-wider bg-gray-800">
                                        {{ $field->label }}
                                    </th>
                                @endforeach
                            </tr>
                        </thead>"""

new_thead = """                <div class="overflow-x-auto min-h-[200px]">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-slate-900 sticky top-0 z-10 shadow-md shadow-slate-900/10">
                            <tr>
                                <th class="w-12 px-2 sm:px-4 py-3.5 text-center text-[10px] sm:text-xs font-bold text-slate-300 uppercase tracking-widest bg-slate-900 border-b border-slate-700">#</th>
                                @foreach($fieldsToRender as $field)
                                    <th class="px-2 sm:px-4 py-3.5 text-left text-[10px] sm:text-xs font-bold text-slate-300 uppercase tracking-widest bg-slate-900 border-b border-slate-700 group cursor-default">
                                        <div class="flex items-center space-x-1">
                                            <span class="{{ $field->is_required ? 'text-white' : '' }}">{{ strtoupper($field->label) }}</span>
                                            @if($field->is_required)
                                                <span class="text-red-400 font-bold text-sm" title="Zorunlu Alan">*</span>
                                            @endif
                                        </div>
                                    </th>
                                @endforeach
                            </tr>
                        </thead>"""
content = content.replace(old_thead, new_thead)

# 3. Update tbody tr hover
old_tr = """                                <tr class="hover:bg-gray-50">"""
new_tr = """                                <tr class="hover:bg-indigo-50/50 transition-colors group">"""
content = content.replace(old_tr, new_tr)

# 4. Update the values rendering inside tbody to include formatting for numbers
old_val = """                                        <td class="px-4 py-3 text-sm text-gray-900">
                                            {{ $val->value ?? '-' }}
                                        </td>"""

new_val = """                                        <td class="px-4 py-3 text-sm text-gray-900 whitespace-nowrap">
                                            @if($field->type === 'number' && isset($val->value) && is_numeric($val->value))
                                                @php
                                                    $floatVal = floatval($val->value);
                                                @endphp
                                                {{ strpos($val->value, '.') !== false ? number_format($floatVal, 2, ',', '.') : $val->value }}
                                            @else
                                                {{ $val->value ?? '-' }}
                                            @endif
                                        </td>"""
content = content.replace(old_val, new_val)

# 5. Update tfoot design
old_tfoot = """                        <tfoot class="bg-gray-100 border-t-2 border-gray-300">
                            <tr>
                                <td class="px-4 py-3 font-bold text-right text-gray-700" colspan="1">TOPLAM:</td>
                                @foreach($fieldsToRender as $field)
                                    <td class="px-4 py-3 font-bold text-gray-900 text-left">
                                        @if($field->is_summable && $field->type === 'number')
                                            @php
                                                $sum = 0;
                                                foreach($formEntry->rows as $row) {
                                                    $valObj = $row->values->where('form_field_id', $field->id)->first();
                                                    $sum += floatval($valObj->value ?? 0);
                                                }
                                            @endphp
                                            {{ $sum }}
                                        @endif
                                    </td>
                                @endforeach
                            </tr>
                        </tfoot>"""

new_tfoot = """                        <tfoot class="bg-slate-50 border-t-4 border-slate-200">
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
                        </tfoot>"""
content = content.replace(old_tfoot, new_tfoot)

with open(filepath, 'w', encoding='utf-8') as f:
    f.write(content)

print("Updated show.blade.php successfully!")
