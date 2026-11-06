# GÖREV LİSTESİ: KULLANICI KPI VE HEDEFLERİM SİSTEMİ

- [x] 1. Veritabanı ve Yetki Matrisi Güncellemesi
  - [x] `kpi_targets.view_profile_kpi` iznini oluşturma ve permissions tablosuna ekleme
  - [x] `veri_girisi` rolüne `kpi_targets.view_own` iznini bağlama
  - [x] `sistem_yoneticisi`, `yonetim`, `veri_giris_yoneticisi` rollerine `kpi_targets.view_profile_kpi` iznini bağlama
  - [x] Yetki matrisinde etiket ve modül eşleştirmesini doğrulama

- [x] 2. "Hedeflerim" Controller ve Rotaları
  - [x] `app/Http/Controllers/MyTargetsC.php` oluşturma
  - [x] Mevcut yıl hedefleri, başarı oranları ve 12 aylık gerçekleşme verisi çekme
  - [x] Geçmiş yıllar arşiv seçicisi ve geçmiş yıl performans özeti üretme
  - [x] Boş durum (hedef atanmadıysa) kontrolü
  - [x] `routes/web.php` içine `/hedeflerim` rotasını tanımlama

- [x] 3. "Hedeflerim" Arayüzü (Blade)
  - [x] `resources/views/kpi_targets/my_targets.blade.php` oluşturma
  - [x] Yıl seçici (2026, 2025, 2024...) ve sekme yapısı
  - [x] Genel Performans Karnesi (Ortalama Başarı %, Hedef Sayısı, Durum Rozeti)
  - [x] Hedef atanmamış durum için şık boş durum kartı
  - [x] Hedef kartları (Hedef Değeri, Gerçekleşen, Yüzde, İlerleme Çubuğu, 12 Aylık Dağılım Çizelgesi)

- [x] 4. Kullanıcı Profilinde Performans Karnesi
  - [x] `app/Http/Controllers/UserProfileC.php` yetki kontrolü ve geçmiş yıl arşiv verisini hazırlama
  - [x] `resources/views/users/profile.blade.php` içindeki KPI bölümünü dinamik yıl filtreli ve yetki kontrollü hale getirme
  - [x] Yönetici ve Yönetim için "Kullanıcının Performansı Nedir?" özet karne kartı

- [x] 5. Navigasyon Menüsü Entegrasyonu
  - [x] `resources/views/layouts/navigation.blade.php` içine "Hedeflerim" linkini ekleme
  - [x] `kpi_targets.view_own` yetkisi olan herkese (İbrahim Çelik dahil) görünmesini sağlama

- [x] 6. Test ve Doğrulama
  - [x] İbrahim Çelik kullanıcısı ile giriş simülasyonu ve "Hedeflerim" sayfasını test etme
  - [x] Boş durum ve hedef atanmış durumları doğrulama
  - [x] Yetki matrisinden yetki açma/kapatma testleri

