# Dinamik Zil ve Mail Bildirim Şablonları & Zengin Metin Kutusu Uygulama Planı

Bu geliştirme, `http://localhost:9002/settings/notifications` sayfasında yer alan bildirim mekanizmasını hardcoded yapılardan tamamen arındırarak;
1. Hem **Mail** hem de **Zil (Sistem İçi / In-App)** bildirimleri için ayrı ayrı zengin metin düzenleme (WYSIWYG: kalın, italik, madde işaretleri, bağlantı vb.),
2. Tek tıkla eklenebilen geniş bir **Dinamik Değişken Havuzu** (`{kullanici_adi}`, `{form_adi}`, `{onaylayan_kullanici}`, `{red_nedeni}`, `{tarih_saat}`, `{islem_linki}` vb.),
3. Sistemdeki **her bir rol için (`n` adet rol)** dinamik olarak özelleştirilebilir şablon desteği (Genel şablon + Role özel şablonlar)
sunmayı amaçlamaktadır.

## Yapılacak İşlemler

### 1. Veritabanı Değişikliği (Migration)
- `notification_templates` tablosuna `in_app_subject`, `in_app_body` ve `role_templates` (JSON) sütunları eklenecektir.
- `role_templates` alanı sistemdeki n adet rol ve özel hedef için rol bazlı şablonları saklayacaktır.

### 2. Model ve Servis Katmanı
- `app/Models/NotificationTemplateM.php` modeline `$fillable` ve `casts` (`role_templates => array`) eklenecektir.
- `app/Services/NotificationS.php` servisi güncellenecektir:
  - Değişken havuzundaki Türkçe ve İngilizce tüm değişkenlerin otomatik çözümlenmesi (`{kullanici_adi}`, `{form_adi}`, `{islem_linki}`, `{tarih_saat}` vb.).
  - Kurallar gereği (Kural 17) dinamik alanların `<strong>...</strong>` ile kalınlaştırılması.
  - Alıcının rolüne göre (özel rol şablonu tanımlıysa rol şablonu, değilse genel şablon) doğru şablonun seçilmesi.
  - Mail için mail şablonunun, zil (veritabanı) için zil şablonunun kullanılması.
- `app/Notifications/DynamicNotification.php` sınıfına `inAppSubject` ve `inAppBody` parametreleri eklenecektir.

### 3. Controller Katmanı
- `app/Http/Controllers/NotificationSettingsC.php` sınıfındaki `updateTemplate` fonksiyonu:
  - Genel `subject`, `body`, `in_app_subject`, `in_app_body`, `additional_emails` alanlarını doğrulayıp kaydedecek.
  - Formdan gelen role özel şablonları (`role_templates`) JSON olarak kaydedecek.

### 4. Arayüz ve Zengin Metin Editörü (Blade & AlpineJS & Quill)
- `resources/views/settings/notifications/index.blade.php`:
  - Quill.js entegrasyonu (Kalın, İtalik, Altı Çizili, Madde İşaretleri, Numaralı Liste, Link).
  - Tıklanabilir **Dinamik Değişken Havuzu** çubuğu.
  - Genel Şablon ve Sistemdeki Roller (`n` adet rol) arasında geçiş sağlayan dinamik sekmeler (Tabs).
  - Hem Mail hem de Zil bildirimleri için ayrı başlık ve zengin metin kutuları.

### 5. Test ve Doğrulama
- Migration çalıştırılacak.
- Controller, view ve bildirim gönderim mekanizması test edilecek.
- Önbellekler temizlenecek.

