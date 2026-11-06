# Şifre Sıfırlama Sayfaları Kurumsal Modernizasyon Planı

## 1. Amaç ve Kullanıcı Talebi
Kullanıcımızın talebi:
> *"http://localhost:9002/forgot-password bu sayfayı da adam akıllı yapar mısın? Gerçi bunlara gerek kalmayacak merkezi api ile eşleştirince"*

Kullanıcımızın talebi doğrultusunda:
- Standart ham Laravel Breeze şablonundaki İngilizce ve biçimsiz şifre sıfırlama ekranı (`/forgot-password`), kurumsal şirket içi portal kimliğine (`/login` ile birebir uyumlu) kavuşturulacaktır.
- Köksan kurumsal logosu, cam (glassmorphism) efektleri, Türkçe kurumsal bilgilendirme metni, ikonlu e-posta alanı, durum/hata bildirimleri ve "Giriş Ekranına Dön" bağlantısı eklenecektir.
- Benzer şekilde bağlantılı olan şifre belirleme ekranı da (`/reset-password`) bütünlüğü korumak adına aynı kurumsal tasarıma uyarlanacaktır.

---

## 2. Mimari ve Bileşenler

### A. Şifremi Unuttum Ekranı (`resources/views/auth/forgot-password.blade.php`)
- **Kurumsal Kimlik & Logo:** Köksan logosu ve "Hedef Yönetim Portalı" başlığı.
- **Cam (Glassmorphism) Kart:**
  - Başlık: "Şifre Sıfırlama Talebi"
  - Açıklama: "Kayıtlı kurumsal e-posta adresinizi giriniz. Şifrenizi yenileyebilmeniz için size bir sıfırlama bağlantısı iletilecektir."
  - E-Posta Alanı: Şık posta ikonu, modern odaklanma efektleri.
  - "Sıfırlama Bağlantısı Gönder" butonu (gradyan ve animasyonlu).
  - "Giriş Ekranına Dön" bağlantısı.
  - Başarı (`session('status')`) ve hata (`$errors`) cam bildirim kutuları.

### B. Yeni Şifre Belirleme Ekranı (`resources/views/auth/reset-password.blade.php`)
- Token, e-posta, yeni şifre ve şifre onay alanlarını içeren tam uyumlu kurumsal form.
- Şifre göster/gizle butonları.

---

## 3. Doğrulama ve Test Adımları
1. Blade şablonlarının güncellenmesi.
2. View önbelleğinin temizlenmesi (`php artisan view:clear`).
3. `http://localhost:9002/forgot-password` sayfasının yüklenerek görsel ve işlevsel öğelerin test edilmesi.
4. Özet dokümanının (`forgot_password_redesign_walkthrough.md`) hazırlanması.

