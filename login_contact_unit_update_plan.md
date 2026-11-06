# Giriş Ekranı İletişim Birimi Güncelleme Planı

## 1. Amaç ve Kullanıcı Talebi
Kullanıcımızın talebi:
> *"Giriş veya yetki talepleri için Bilgi Teknolojileri birimi ile iletişime geçebilirsiniz." Burası Opex ekibi olacak Bilgi Teknolojileir değil.*

`resources/views/auth/login.blade.php` dosyasındaki kurumsal bilgilendirme kutusu içerisinde yer alan iletişim birimi ifadesi "Bilgi Teknolojileri birimi" yerine doğrudan "Opex ekibi" olarak güncellenecektir.

---

## 2. Değişiklik Kapsamı
- **Hedef Dosya:** [`resources/views/auth/login.blade.php`](file:///c:/Users/celal.karaman/Desktop/HEDEFLER/resources/views/auth/login.blade.php)
- **Mevcut Metin:**
  ```html
  <span>Giriş veya yetki talepleri için Bilgi Teknolojileri birimi ile iletişime geçebilirsiniz.</span>
  ```
- **Yeni Metin:**
  ```html
  <span>Giriş veya yetki talepleri için Opex ekibi ile iletişime geçebilirsiniz.</span>
  ```

---

## 3. Doğrulama Adımları
1. `resources/views/auth/login.blade.php` dosyasında metin güncellenecektir.
2. `php artisan view:clear` çalıştırılarak derlenmiş şablon önbelleği temizlenecektir.
3. `http://localhost:9002/login` sayfasına HTTP isteği yapılarak "Opex ekibi" ifadesinin başarıyla yer aldığı ve "Bilgi Teknolojileri birimi" ifadesinin kaldırıldığı doğrulanacaktır.
4. Özet dokümanı (`login_contact_unit_update_walkthrough.md`) hazırlanacaktır.

