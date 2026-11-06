# Giriş Sayfası Modernizasyonu ve Kök Dizin Yönlendirme Planı

## 1. Amaç ve Kullanıcı Talebi
Kullanıcımızın talebi:
1. `http://localhost:9002/`:
   - Kök dizine (`/`) girildiğinde doğrudan dashboard'a yönlendirme yapılması (`redirect()->route('dashboard')`).
   - Kullanıcı oturum açmışsa doğrudan gösterge paneline (`/dashboard`) geçmeli, oturumu yoksa Laravel'in kimlik doğrulama mekanizması sayesinde `/login` sayfasına yönlenmelidir.
2. `http://localhost:9002/login`:
   - Giriş ekranının modern, kurumsal, şık ve responsive bir tasarıma kavuşturulması.
   - Şirket logosu (KÖKSAN / HEDEFLER), ambient gradient arka plan, cam (glassmorphism) efektleri, kullanıcı dostu şifre göster/gizle düğmesi, hata mesajları ve modern buton animasyonlarının eklenmesi.

---

## 2. Mimari ve Yapılacak Değişiklikler

### A. Rota Yönlendirmesi (`routes/web.php`)
- `Route::get('/', ...)` fonksiyonunda varsayılan statik `welcome` view'ı yerine `return redirect()->route('dashboard');` yönlendirmesi tanımlanır.

### B. Misafir Şablon Düzeni (`resources/views/layouts/guest.blade.php`)
- Standart eski Breeze sarmalayıcısı güncellenerek tam ekran düzenlerini destekleyecek esnek ve temiz bir yapıya kavuşturulur.
- `@vite` ve meta etiketleri korunur.

### C. Giriş Ekranı Tasarımı (`resources/views/auth/login.blade.php`)
- **İki Sütunlu / Modern Kurumsal Tasarım (Split Screen & Glassmorphism):**
  - **Sol / Tanıtım Bölümü (Masaüstü):**
    - KÖKSAN & HEDEFLER kurumsal kimliği ve logosu.
    - "Hedef ve Performans Yönetim Sistemi" başlığı.
    - Sistem özelliklerini özetleyen şık rozetler (Dinamik Form Şablonları, Gerçek Zamanlı Bildirimler, Rol & Grup Tabanlı Güvenlik).
    - Yumuşak mesh gradient ve ışık efektleri.
  - **Sağ / Giriş Formu Bölümü:**
    - Şirket logosu ve karşılama başlığı ("Hoş Geldiniz", "Sisteme giriş yapmak için bilgilerinizi giriniz").
    - Hata ve oturum durumu bildirimleri (kırmızı glassmorphism hata kutusu).
    - E-Posta Alanı: Şık ikonlu input, odaklanma efektleri.
    - Şifre Alanı: Kilit ikonu ve Alpine.js ile tek tıkla şifreyi göster/gizle (Show/Hide Password) göz butonu.
    - "Beni Hatırla" seçeneği ve (varsa) "Şifremi Unuttum" bağlantısı.
    - Gradient "Giriş Yap" butonu (hover ve tıklama animasyonları ile).
    - Alt bilgi (Telif hakkı ve güvenli bağlantı göstergesi).
- %100 responsive: Mobilde dikey akış ve taşma (overflow scroll) yapmayan ekran uyumu.

---

## 3. Doğrulama ve Test Adımları
1. `http://localhost:9002/` rotasının test edilmesi:
   - Giriş yapılmamışken `/` -> `/login` yönlenmesi,
   - Giriş yapılmışken `/` -> `/dashboard` yönlenmesi.
2. `http://localhost:9002/login` sayfasının görsel ve işlevsel testi:
   - Sayfa yükleme ve Blade önbellek temizliği (`php artisan view:clear`),
   - Şifre göster/gizle butonunun çalışması,
   - Başarısız giriş denemesinde hata kutusunun şık görünümü,
   - Başarılı giriş ile dashboard'a geçiş.
3. Kullanıcı için özet dokümanı (`login_redesign_walkthrough.md`) hazırlanması.

