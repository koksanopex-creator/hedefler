# Dinamik Zil ve Mail Bildirim Şablonları Görev Listesi

- [x] 1. Migration oluşturma ve çalıştırma: `notification_templates` tablosuna `in_app_subject`, `in_app_body`, `role_templates` kolonlarının eklenmesi
- [x] 2. `NotificationTemplateM.php` modelinin güncellenmesi
- [x] 3. `DynamicNotification.php` sınıfının zil ve e-posta içerik ayrımını destekleyecek şekilde güncellenmesi
- [x] 4. `NotificationS.php` servisinin genişletilmesi (Değişken havuzu, rol bazlı şablon çözümleme, dinamik kalın yazım kuralı)
- [x] 5. `NotificationSettingsC.php` controller'ının güncellenmesi (Gelişmiş şablon ve rol şablonlarının kaydedilmesi)
- [x] 6. `resources/views/settings/notifications/index.blade.php` arayüzünün güncellenmesi (Zengin metin editörü Quill, Değişken Havuzu, n adet rol sekmeleri, Mail ve Zil ayrımı)
- [x] 7. View cache temizliği ve uçtan uca testler
- [x] 8. Walkthrough belgesinin hazırlanması
