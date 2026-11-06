# Master Data Bağlı (Cascading) Açılır Kutularda Tanımsız / Boş Veri Durumu Yönetimi Planı

## Amaç ve Kullanıcı Talebi
Kullanıcı `http://localhost:9002/form-entries/create/2` ekranında:
- `İŞYERİ` olarak `PREFORM` seçtiğinde alt kategori olan `VERSİYON` alanında tanımlı veriler (V1, V2 vb.) düzgünce listelenmektedir.
- Ancak `İŞYERİ` olarak `KULP` veya `VERSİYON` tanımlanmamış başka bir işyeri seçildiğinde, `VERSİYON` açılır kutusunda yalnızca `Seçiniz...` metni kalmakta, liste boş gelmekte ve alan aktif kalarak kırmızı zorunluluk/hata çerçevesi oluşturabilmektedir.
- Kullanıcı talebi:
  1. Alt kategori için seçilen üst değere ait bir veri bulunmuyorsa veya henüz üst seçim yapılmamışsa:
     - Üst kategori henüz seçilmemişse: `"-- Önce [Üst Kategori Adı] Seçiniz --"` şeklinde bilgilendirici pasif metin gösterilmesi,
     - Üst kategori seçilmiş ancak alt verisi yoksa (örn: KULP için VERSİYON yok): `"-- Tanımlı Veri Yok --"` şeklinde bilgilendirici pasif metin gösterilmesi,
  2. Bu durumlarda alanın devre dışı (`disabled`, tıklanamaz / seçilemez) ve soluk (`bg-gray-100 text-gray-400 cursor-not-allowed border-gray-200`) hale gelmesi,
  3. Veri olmayan veya devre dışı kalan alanların formu göndermeyi engellememesi (HTML `required` ve koşullu zorunluluk validasyonundan otomatik muaf tutulması).

---

## 1. Mimari Tasarım ve Değişiklikler

### `resources/views/form_entries/create.blade.php`
1. **`getMasterDataStatus(rowIndex, field)` Yardımcı Fonksiyonu:**
   - Alanın `master_data` tipi olup olmadığını ve üst tip (`parent_id`) bağımlılığını inceler.
   - Durumlar:
     - Üst alan var ve satırda henüz seçilmemiş: `isDisabled = true`, `placeholder = '-- Önce ' + parentLabel + ' Seçiniz --'`.
     - Üst alan seçilmiş fakat bu üst veriye bağlı tanımlı eleman yok (`optionsCount === 0`): `isDisabled = true`, `placeholder = '-- Tanımlı Veri Yok --'`.
     - Bağımsız alan fakat Master Data'da hiç kayıt yok: `isDisabled = true`, `placeholder = '-- Tanımlı Veri Yok --'`.
     - Seçilebilir veriler mevcut: `isDisabled = false`, `placeholder = 'Seçiniz...'`.
2. **Blade Şablonunda `<select>` Yönetimi:**
   - `:disabled="getMasterDataStatus(rowIndex, field).isDisabled"`
   - `option value=""` metni: `x-text="getMasterDataStatus(rowIndex, field).placeholder"`
   - Pasif CSS sınıfları: `bg-gray-100 text-gray-400 border-gray-200 cursor-not-allowed`
   - Form postunda alan bütünlüğünün korunması için devre dışı durumdayken boş değer gönderen gizli input (`<input type="hidden">`) entegrasyonu.
3. **Zorunluluk (`isFieldRequired`) Dinamik Kontrolü:**
   - Eğer bir `master_data` alanı pasifse (`status.isDisabled`), şablonda `is_required` işaretli olsa bile o satır için zorunlu olamaz (çünkü seçilecek veri yoktur).
   - Böylece formun gönderimi (`submit`) HTML5 validasyonuna takılmaz.
4. **Cascading Sıfırlama (`handleMasterDataChange`):**
   - Üst kategori değiştiğinde alt kategori değerinin temizlenmesi ve toplamların/formüllerin güncellenmesi teyit edilir.

---

## 2. Doğrulama ve Test Adımları
- Sayfa derlemesi ve Blade önbellek temizliği (`php artisan view:clear`).
- Simülasyon scripti ile PREFORM ve KULP senaryolarının test edilmesi:
  - PREFORM seçildiğinde VERSİYON'un aktif olması ve V1..V9 seçeneklerini sunması,
  - KULP seçildiğinde VERSİYON'un devre dışı (`disabled`) olması, `"-- Tanımlı Veri Yok --"` göstermesi ve required hatası vermemesi.
- Kullanıcıya walkthrough ile sunulması.

