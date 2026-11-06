# Formül Tanımlamada Nicel Alanlar Görev Listesi

- [x] 1. `resources/views/form_fields/edit.blade.php` dosyasında `getAvailableFormulaFields` fonksiyonunun güncellenmesi (`f.value_type === 'nicel' || f.type === 'number'`)
- [x] 2. `resources/views/form_fields/edit.blade.php` dosyasında "Hesaplamalı Alan (Formül)" seçeneğinin görünürlük şartının güncellenmesi (`field.type === 'number' || field.value_type === 'nicel'`)
- [x] 3. `resources/views/form_entries/create.blade.php` dosyasında `calculateTotals()` fonksiyonunun Master Data ve virgüllü nicel değerleri kusursuz ayrıştıracak şekilde güncellenmesi
- [x] 4. Laravel view cache temizleme (`php artisan view:clear`)
- [x] 5. Test ve doğrulama
