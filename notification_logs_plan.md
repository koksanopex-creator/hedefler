# Bildirim ve E-Posta Gönderim Geçmişi (Notification Logs) Uygulama Planı

## 1. Amaç ve Kapsam
Kullanıcımızın talebi:
> *"Bildirim Şablonu: için rollere göre veriyoruz şablonu ve yetkiyide bu zil ve mail bildirmlerini yapılan işleme göre gruplayıp log tutmamız lazım. Kime gitti, ne zaman gitti, içeriği neydi, hangi kategori için gitti? Zil ve maili ayrı ayrı tutmalı. Bunları da üstten filtreleyebilmem lazım."*

Bu doğrultuda sistemde hem **Zil (In-App)** hem de **E-Posta (Mail)** kanallarından gönderilen tüm bildirimleri işlem (kategori), rol, alıcı, içerik ve zaman bazında ayrı ayrı loglayan ve gelişmiş üst filtreleme barı ile yöneticiye sunan kapsamlı bir loglama altyapısı geliştirilecektir.

---

## 2. Mimari ve Bileşenler

### A. Veritabanı (`notification_logs` tablosu)
- `id`: Birincil anahtar
- `channel`: `mail` veya `in_app` (Zil ve mail loglarının ayrı ayrı tutulması)
- `event_slug`: Olay kodu (Örn: `form.submitted`, `form.approved`, `form.rejected`, `form.deleted`, `template.deleted`, `user.created`)
- `event_name`: Olay başlığı (Örn: `Yeni Form Gönderildi`, `Form Onaylandı`, `Yeni Kullanıcı Eklendi`)
- `user_id`: Alıcı kullanıcı id'si (nullable - harici e-postalar için null)
- `recipient_name`: Alıcı ad soyadı
- `recipient_email`: Alıcı e-posta adresi
- `recipient_role`: Alıcının rolü (Örn: `Sistem Yöneticisi`, `Veri Giriş Yöneticisi`, `Veri Girişi`, `Yönetim`)
- `subject`: Bildirim / E-Posta konusu veya zil başlığı
- `body`: Bildirim içeriği (Zengin HTML veya metin formatı)
- `action_url`: Varsa butona bağlı hedef bağlantı URL'i
- `status`: Gönderim durumu (`sent`, `failed`)
- `metadata`: Ek parametreler (gönderilen değişkenler snapshot'ı, hedef kitle türü vb. JSON)
- `sent_at`: Bildirimin gönderildiği tarih/saat
- Zaman damgaları (`created_at`, `updated_at`)

### B. Model (`App\Models\NotificationLogM`)
- `user()` ilişkisi (`belongsTo(UserM::class)`)
- Kanal, olay, rol, arama ve tarih aralığı filtreleme `scope` metotları
- `sent_at` cast'i

### C. Servis Entegrasyonu (`App\Services\NotificationS`)
- Bildirim tetiklendiğinde (`NotificationS::dispatch`):
  - Zil bildirimi (`send_in_app = true`) aktifse: `channel = 'in_app'` olarak bir kayıt oluşturulur.
  - E-posta bildirimi (`send_email = true`) aktifse: `channel = 'mail'` olarak bir kayıt oluşturulur.
  - Harici ekstra e-postalar için de alıcı rolü "Harici Alıcı" olarak işaretlenip `channel = 'mail'` kaydı oluşturulur.
  - Geriye dönük uyumluluk için mevcut `EmailLogM` kaydı korunur.

### D. Kontrolcü (`App\Http\Controllers\NotificationLogC`)
- `index(Request $request)` metodu:
  - Üst filtreleri uygular:
    - Kanal (`channel`): Tümü / Zil / E-Posta
    - Kategori / İşlem (`event_slug`): Tüm olaylar veya seçili olay
    - Rol (`role`): Tüm roller veya seçili rol
    - Arama (`search`): Alıcı adı, e-posta, konu veya içerik içinde
    - Tarih Aralığı (`date_from`, `date_to`): Başlangıç ve bitiş tarihi
  - Gruplama veya liste modunu destekler.
  - Sayfalama (Pagination: 30 kayıt) uygular.
  - Özet istatistik sayaçlarını (Toplam, Zil, E-Posta, İşlem sayısı) hesaplar.

### E. Arayüz (`resources/views/settings/notification_logs/index.blade.php`)
- Üst Yönetim / Bilgi Paneli:
  - Toplam Gönderim, Zil Sayısı, E-Posta Sayısı, İşlem Türü rozetleri.
  - Hızlı filtreleme formu (Kanal, Kategori, Rol, Tarih Aralığı, Metin Arama, Temizle butonu).
- Gruplu ve Liste Görünüm Tablosu:
  - Kanal ikonu (🔔 Zil / ✉️ E-Posta),
  - Yapılan işlem kategorisi rozeti,
  - Alıcı bilgisi (Avatar, Ad Soyad, E-posta, Rol etiketi),
  - Konu ve tarih bilgisi,
  - "İçeriği Gör" butonu.
- Zengin İçerik Önizleme Modalı:
  - Bildirimin alıcıya gittiği orijinal zengin metin / HTML tasarımını, varsa "Sisteme Git" butonunu ve gönderim detaylarını gösterir.

### F. Rota ve Menü Entegrasyonu
- `settings/notification-logs` rotası tanımlanır (`settings.notification_logs.index`).
- `settings/email-logs` rotası yeni log ekranına kanal filtresiyle yönlendirilir veya desteklenir.
- `settings/index.blade.php` ve `settings/notifications/index.blade.php` üzerindeki bağlantılar güncellenir.

---

## 3. Doğrulama ve Test Adımları
1. Migration çalıştırılması ve tablo şemasının doğrulanması.
2. `NotificationS::dispatch` üzerinden örnek bir test bildirimi tetiklenmesi ve hem `in_app` hem `mail` loglarının `notification_logs` tablosuna düştüğünün kontrol edilmesi.
3. Üst filtreleme işlevlerinin (kanal, kategori/işlem, rol, arama, tarih) test edilmesi.
4. Önbellek temizliği (`php artisan view:clear`) ve sunucu kontrolü.

