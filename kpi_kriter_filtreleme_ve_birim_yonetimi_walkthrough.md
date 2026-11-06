# KPI KRİTER FİLTRELEME VE BİRİM YÖNETİMİ TAMAMLANMA ÖZETİ (WALKTHROUGH)

## Yapılan İyileştirmeler ve Teslim Edilen Özellikler

### 1. Master Data Bağımlı (Cascading) Kriter Filtreleri
* **Üst-Alt Kategori İlişkileri:**
  * Master Data sayfasında (`/master-data`) tanımladığınız tüm `parent_id` hiyerarşisi hedef oluşturma sihirbazına dinamik olarak bağlandı.
* **Ülke -> Şehir Bağımlılığı:**
  * "Ülke" seçilmediği sürece "Şehir" kutusu devre dışıdır ve `-- Önce Ülke Seçiniz --` uyarısı verir.
  * "Ülke" olarak **Amerika** seçildiğinde, "Şehir" kutusunda yalnızca Amerika'ya ait 20 şehir (*New York, Los Angeles, Chicago vb.*) listelenir.
  * Ülke değiştirildiğinde (örn. Türkiye yapıldığında) Şehir seçimi otomatik sıfırlanır ve Türkiye'nin şehirleri (*Gaziantep, Adana vb.*) listelenir.
* **İşyeri -> Renk, Gramaj, Kalınlık, IV, Versiyon, Tip, Çene Bağımlılığı:**
  * "İŞYERİ" seçilmeden önce Renk, Gramaj, Kalınlık gibi kutular `-- Önce İŞYERİ Seçiniz --` şeklinde kilitlidir.
  * "İŞYERİ" olarak **PREFORM** seçildiğinde:
    * Renk kutusunda Preform'un 14 rengi (*Şeffaf, Mavi, Yeşil, Silver, Sarı vb.*),
    * Gramaj kutusunda sadece Preform gramajları gelir.
  * "İŞYERİ" olarak **KAPAK** seçildiğinde:
    * Renk kutusundaki seçim sıfırlanır ve Kapak'a ait 19 renk (*Mavi, G.Mavi, P.Mavi, Beyaz vb.*) listelenir.

---

### 2. Master Data Tabanlı "BİRİM" (Ölçü Birimleri) Yönetimi
* **Dinamik Master Data Türü:**
  * Veritabanında `master_data_types` tablosuna **"BİRİM"** kategorisi eklendi.
  * Standart ölçü birimleri tanımlandı: `Ton`, `Adet`, `KG`, `TL`, `USD`, `EUR`, `Metre`, `Koli`, `Palet`, `Paket`, `Litre`.
* **Master Data Sayfası Entegrasyonu:**
  * Artık [`/master-data`](http://localhost:9002/master-data) sayfasına girdiğinizde **"BİRİM"** başlığı altında tüm birimleri görebilir, dilediğiniz zaman yeni ölçü birimleri ekleyebilir veya güncelleyebilirsiniz.
* **Hedef Oluşturma & Düzenleme Formu:**
  * "Hedef Miktarı & Yılı" bölümündeki `Birim` serbest metin alanı kaldırılarak Master Data'dan beslenen şık bir açılır listeye (`<select name="unit">`) dönüştürüldü.

---

### 3. Hedef Alanı (Sayısal Sütun) Temizliği
* **Metrik Ayrıştırması:**
  * Hedef Alanı kutusundan `Ölçü Birimi`, `Versiyon`, `Sipariş Veren (SAP)` gibi kümülatif toplamı alınamayacak alanlar temizlendi.
  * Bu kutuda yalnızca gerçek toplanabilir metrikler (**TONAJ**, **TOPLAM TESLİMAT MİKTARI**, **TOPLAM TUTAR**) listelenecek şekilde filtrelendi.

