# Giriş Ekranı Kurumsal İyileştirme Planı

## 1. Amaç ve Kullanıcı Talebi
Kullanıcımızın geri bildirimi:
> *"Bunu bir ürün pazarlaması gibi sunma, kurumsal firmada kullanılan bir program bu. Zil bildirimi ıvır zıvır koyma.*
> *Daha amacına uygun bir alt metin yaz buraya da: 'Fabrika, işyeri ve satış gruplarının hedef verilerini tek bir merkezde toplayan, dinamik formlar ve akıllı onay mekanizmalarıyla güçlendirilmiş kurumsal yönetim platformu.' buna gerek yok böyle. Bu bir şirketin hazır ürünü."*

Kullanıcımızın haklı uyarısı doğrultusunda:
- Dışarıya satılan bir SaaS/pazarlama ürünü izlenimi veren tüm tanıtım kartları ("Dinamik Şablonlar", "Zil & Mail Bildirimi", "Rol Yetkilendirme", "v2.4 Enterprise") tamamen kaldırılacaktır.
- Pazarlama dili yerine, Köksan bünyesinde kullanılan **ciddi, sade ve kurumsal şirket içi portal** dili kullanılacaktır.
- Amacına uygun sade alt metin ve kurum içi bilgi güvenliği / yetkili personel bilgilendirmesi yerleştirilecektir.
- Kurumsal logo, temiz tipografi ve profesyonel kurumsal giriş formu korunacaktır.

---

## 2. Tasarım Değişiklikleri (`resources/views/auth/login.blade.php`)

### A. Kaldırılacak Öğeler (Pazarlama Unsurları):
- ❌ 3'lü özellik kutuları ("Dinamik Şablonlar", "Zil & Mail Bildirimi", "Rol Yetkilendirme")
- ❌ Pazarlama manşeti ve SaaS satış jargonu
- ❌ "v2.4 Enterprise" ticari sürüm rozeti

### B. Eklenecek Kurumsal ve Amacına Uygun Öğeler:
- ✅ **Kurumsal Başlık:**
  "Hedef ve Performans Yönetim Sistemi"
- ✅ **Amacına Uygun Kurumsal Alt Metin:**
  "Köksan bünyesindeki birimlerin hedef planlama, veri girişi ve onay süreçlerinin yürütüldüğü kurum içi portaldır."
- ✅ **Yetkili Erişim ve Destek Notu:**
  "Bu portal yalnızca yetkili şirket personelinin kullanımına tahsis edilmiştir. Oturum açma veya yetki talepleriniz için Bilgi Teknolojileri birimi ile iletişime geçebilirsiniz."
- ✅ **Giriş Formu Alanı:**
  - Köksan kurumsal logosu
  - Kurumsal Kullanıcı Girişi
  - E-Posta, Şifre (Göster/Gizle fonksiyonu ile), Beni Hatırla ve Giriş Yap butonu
  - 100% mobil ve masaüstü uyumu, taşma yapmayan ergonomik yapı

---

## 3. Doğrulama ve Test Adımları
1. Blade şablonunun güncellenmesi.
2. View önbelleğinin temizlenmesi (`php artisan view:clear`).
3. Sayfa render testinin çalıştırılarak pazarlama öğelerinin temizlendiğinin ve yeni kurumsal yapının doğrulandığının teyidi.
4. Özet dokümanı (`corporate_login_refinement_walkthrough.md`) hazırlanması.

