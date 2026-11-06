# Dinamik Zil ve Mail Bildirim Şablonları & Zengin Metin Editörü - İnceleme ve Özet

`http://localhost:9002/settings/notifications` sayfasındaki bildirim yönetim sistemi hardcoded yapılardan tamamen arındırılarak zengin metin kutusu, dinamik değişken havuzu ve rol bazlı şablonlama mimarisi ile baştan sona modernize edilmiştir.

## 1. Hayata Geçirilen Temel Özellikler

### A. Zil (Sistem İçi) ve E-Posta Bildirim Şablonlarının Ayrılması
- Önceden sadece e-posta konusu ve içeriği bulunuyor, zil bildirimi de bu içeriği aynen alıyordu.
- Yapılan geliştirme ile hem **E-Posta Şablonu** (Konu + Zengin Metin) hem de **Zil Bildirimi Şablonu** (Başlık + Zengin Metin) ayrı ayrı yapılandırılabilir hale getirildi.
- Zil şablonu boş bırakıldığında sistem geriye dönük uyumlu olarak e-posta konusunu ve içeriğini kullanmaktadır.

### B. Zengin Metin Kutusu (WYSIWYG - Quill.js Entegrasyonu)
- Hem e-posta hem de zil bildirimleri için modern ve hafif Quill.js editörü entegre edildi:
  - **Kalın (Bold)**, *İtalik (Italic)*, <u>Altı Çizili (Underline)</u>, ~~Üstü Çizili~~
  - • Madde İşaretli Liste, 1. Numaralı Liste
  - 🎨 Renk ve Vurgu Seçimi
  - 🔗 Bağlantı / Link Ekleme
  - 🧹 Biçimlendirmeyi Temizleme

### C. Kapsamlı Dinamik Değişken Havuzu (Variable Pool)
Bildirim şablon modalının üst kısmına tıklanabilir chip/butonlar şeklinde değişken havuzu eklendi:
- `{kullanici_adi}` : Form Sahibi / İşlemi Yapan Personel (İngilizce alias: `{user_name}`)
- `{alici_adi}` : Bildirimi Alan Kullanıcı (`{recipient_name}`)
- `{form_adi}` : Form Şablonunun Başlığı (`{form_name}`)
- `{onaylayan_kullanici}` : Formu Onaylayan / Reddeden Yetkili (`{approver_name}`)
- `{red_nedeni}` : Red Açıklaması / Gerekçesi (`{rejection_reason}`)
- `{tarih_saat}` : İşlem Zamanı (`{date_time}`, örn: 02.11.2026 17:30)
- `{tarih}` : Tarih (`{date}`)
- `{islem_linki}` : Kayda Doğrudan Gitme Butonu (`{action_url}`)
- `{kayit_id}` : Form Kayıt No (`{entry_id}`)
- `{grup_adi}` : Kullanıcının Departmanı / Grubu (`{group_name}`)
- `{sirket_adi}` : Kurum Adı ("KÖKSAN")

> **Kural 17 Uyumu:** Sistem bildirimleri gönderilirken metin içindeki dinamik değişkenler otomatik olarak `<strong>...</strong>` ile kalınlaştırılarak görsel bütünlük ve kurallara tam uyum sağlanır.

### D. Sistemdeki N Adet Rol İçin Dinamik Şablon Mimarisi
- Modal içerisinde **Sekmeler (Tabs)** mimarisi kuruldu:
  - **Genel Şablon (Varsayılan):** Tüm alıcılar için geçerli standart şablon.
  - **Dinamik Rol Sekmeleri:** Sistemde tanımlı tüm roller (`$roles`) ve özel hedefler (Kullanıcının Yöneticisi, İşlemi Yapan Kişi) otomatik olarak birer sekme olarak listelenir. Sisteme yeni bir rol eklendiğinde (`n adet rol`), hiçbir kod yazmaya gerek kalmadan doğrudan bu sekmelere dahil olur.
  - İstenen rol sekmesine geçilip `"Bu Hedefe / Role Özel Şablon Tanımla"` anahtarı açılarak o role özel farklı bir zil ve mail şablonu yazılabilir.
  - Özel şablon tanımlanmamış roller için otomatik olarak Genel Şablon kullanılır.

## 2. Değiştirilen ve Eklenen Dosyalar
- `database/migrations/2026_11_02_173000_add_in_app_and_roles_to_notification_templates_table.php` (Yeni Migration)
- `app/Models/NotificationTemplateM.php` (Model ve `getResolvedTemplate` mantığı)
- `app/Notifications/DynamicNotification.php` (Zil ve E-Posta ayrımı)
- `app/Services/NotificationS.php` (Değişken havuzu eşleme, Kural 17 kalınlaştırma, rol şablonu çözümleme)
- `app/Http/Controllers/NotificationSettingsC.php` (Zil ve rol bazlı şablonların kaydedilmesi)
- `resources/views/settings/notifications/index.blade.php` (Gelişmiş arayüz, Quill editörleri, Değişken Havuzu, N rol sekmesi)

## 3. Doğrulama ve Test Sonuçları
- `GET /settings/notifications` sayfası HTTP 200 ile başarıyla render edildi.
- `updateTemplate` controller eylemi üzerinden genel şablon ve role özel şablonların veritabanına JSON formatında kusursuz kaydedildiği test edildi.
- `NotificationS::dispatch` fonksiyonunun dinamik değişkenleri doğru çözdüğü, Türkçe/İngilizce takma adları eşleştirdiği ve HTML içeriklerinde değişkenleri `<strong>` ile kalınlaştırdığı doğrulandı.

