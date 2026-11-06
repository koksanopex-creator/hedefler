# Formül Tanımlamada Nicel Alanların Listelenmesi Uygulama Planı

Bu geliştirme, `http://localhost:9002/form-templates/2/fields` sayfasında "Hesaplamalı Alan (Formül)" seçeneğinde tanımlanan formüllere dahil edilecek alanların listesinde, HTML tipi sadece Sayı (Number) olan alanlar değil, **Değer Tipi (Analiz için) "Nicel" olarak seçilmiş olan tüm alanların (Master Data, Select, Metin vb.)** listelenmesini ve form giriş ekranında bu alanların değerlerinin hesaplamalara doğru şekilde dahil edilmesini sağlar.

## Yapılacak Değişiklikler

1. **Şablon Alanları Ekranı (`resources/views/form_fields/edit.blade.php`):**
   - `getAvailableFormulaFields(currentField)` fonksiyonu güncellenerek sadece `f.type === 'number'` yerine `(f.value_type === 'nicel' || f.type === 'number')` şartı eklenecek.
   - "Hesaplamalı Alan (Formül)" seçeneği ve ayar kutusunun görünürlüğü `field.type === 'number' || field.value_type === 'nicel'` olarak genişletilecek.
   - Böylece Değer Tipi Nicel olan Master Data alanları (Örn: GRAMAJ, KALINLIK vb.) formül oluşturma açılır kutusunda (dropdown) anında listelenecek.

2. **Form Veri Giriş Ekranı (`resources/views/form_entries/create.blade.php`):**
   - `calculateTotals()` fonksiyonundaki formül hesaplama algoritması güncellenecek.
   - Master Data veya diğer metinsel alanlardan gelen değerlerdeki virgüllü veya noktalı sayısal formatlar (örn. `"12,5"`, `"12.5"`) güvenli biçimde `parseFloat(cleanVal)` ile çözülecek.
   - Zincirleme hesaplamaları (birbirine bağlı hesaplamalı alanları) eksiksiz desteklemek için döngü 2 tura çıkarılacak.

3. **Önbellek Temizleme ve Test:**
   - `php artisan view:clear` komutu çalıştırılacak.
   - Şablon alanları ve veri giriş hesaplama mantığı test edilecek.

