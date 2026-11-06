# GÖREV LİSTESİ: KPI KRİTER FİLTRELEME VE BİRİM YÖNETİMİ

- [x] 1. Master Data "BİRİM" Türü ve Standart Birimlerin Eklenmesi
  - [x] Migration ile `master_data_types` tablosuna "BİRİM" ekleme
  - [x] `master_data_items` tablosuna standart birimleri (Ton, Adet, KG, TL, USD, EUR, Metre, Koli, Palet, Paket, Litre) tohumlama
  - [x] Master Data yönetim sayfasında (`/master-data`) görünürlüğü sağlama

- [x] 2. Backend Controller & AJAX API Güncellemeleri (`KpiTargetC.php`)
  - [x] `getFields()` metodunda `numeric_fields`'i yalnızca toplanabilir gerçek sayısal metriklerle sınırlama (Tonaj, Teslimat Miktarı, Tutar)
  - [x] `getFields()` yanıtına tüm Master Data hiyerarşisini içeren `master_data_tree` ekleme
  - [x] `create()` ve `edit()` metodlarında Master Data "BİRİM" öğelerini view'a aktarma

- [x] 3. Frontend Cascading Filtreleme Arayüzü (`create.blade.php`)
  - [x] Alpine.js içinde `criteria: {}`, `masterDataTree: {}` modellerini kurma
  - [x] `getFieldOptions(field)` fonksiyonuyla `parent_id` bağımlılıklarını filtreleme (Ülke -> Şehir, İşyeri -> Renk, Gramaj, Kalınlık, IV vb.)
  - [x] `handleCriteriaChange(fieldId)` fonksiyonuyla üst alan değiştiğinde alt alanları zincirleme sıfırlama
  - [x] "Birim" alanını Master Data dropdown'ına dönüştürme

- [x] 4. Hedef Düzenleme Ekranı Uyarlaması (`edit.blade.php`)
  - [x] Mevcut `criteria_json` değerlerini görünür ve açıklayıcı kılma
  - [x] "Birim" alanını Master Data dropdown'ı ile güncelleme

- [x] 5. Test ve Doğrulama
  - [x] Amerika seçilince Şehir kutusunda sadece Amerika şehirlerinin (20 şehir) geldiğini test etme
  - [x] PREFORM seçilince Renk (14 renk) ve Gramaj kutularında sadece Preform öğelerinin geldiğini test etme
  - [x] KAPAK seçilince Kapak renklerinin (19 renk) geldiğini test etme
  - [x] Master Data birimlerinin (Ton, Adet vb.) geldiğini ve kaydedildiğini test etme
