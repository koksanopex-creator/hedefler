# Stratejik Planlama Birimi Güncelleme Planı

## 1. Amaç ve Kullanıcı Talebi
Kullanıcımızın talebi:
> *"Opex değil pardon Stratejik Planlama olacak. Bilgi Teknolojileir yazan yerlere onu yazmanı istiyorum"*

Sistemdeki giriş ve kimlik doğrulama ekranlarında yer alan "Bilgi Teknolojileri" ve "Opex" ifadeleri, projenin kurumsal yürütücüsü olan **"Stratejik Planlama"** olarak güncellenecektir.

---

## 2. Değişiklik Yapılacak Dosyalar ve Alanlar

1. **[`resources/views/auth/login.blade.php`](file:///c:/Users/celal.karaman/Desktop/HEDEFLER/resources/views/auth/login.blade.php):**
   - Satır 60: `Opex ekibi ile iletişime geçebilirsiniz.` -> `Stratejik Planlama ekibi ile iletişime geçebilirsiniz.`
   - Satır 71: `Köksan Holding &bull; Bilgi Teknolojileri` -> `Köksan Holding &bull; Stratejik Planlama`

2. **[`resources/views/auth/forgot-password.blade.php`](file:///c:/Users/celal.karaman/Desktop/HEDEFLER/resources/views/auth/forgot-password.blade.php):**
   - Satır 111: `Köksan Holding &bull; Bilgi Teknolojileri` -> `Köksan Holding &bull; Stratejik Planlama`

3. **[`resources/views/auth/reset-password.blade.php`](file:///c:/Users/celal.karaman/Desktop/HEDEFLER/resources/views/auth/reset-password.blade.php):**
   - Satır 167: `Köksan Holding &bull; Bilgi Teknolojileri` -> `Köksan Holding &bull; Stratejik Planlama`

---

## 3. Doğrulama ve Test Adımları
1. Dosyalarda gerekli metin güncellemelerinin `edit_file` aracıyla yapılması.
2. `php artisan view:clear` çalıştırılarak Blade view önbelleğinin temizlenmesi.
3. Giriş (`/login`), Şifre Sıfırlama (`/forgot-password`) ve Yeni Şifre (`/reset-password/{token}`) sayfalarının HTTP istekleri ile çağrılarak "Stratejik Planlama" ifadesinin yer aldığı, eski "Bilgi Teknolojileri" ve "Opex" ifadelerinin kalmadığının doğrulanması.
4. Özet dokümanının (`strategic_planning_unit_update_walkthrough.md`) hazırlanması.

