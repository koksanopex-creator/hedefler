# KPI HEDEF KRİTERLERİNDE MASTER DATA BAĞIMLI (CASCADING) FİLTRELEME VE BİRİM YÖNETİMİ PLANI

## 1. Genel Bakış ve Amaç
Bu geliştirme ile KPI hedefi oluşturma ve düzenleme ekranlarında ([`/kpi-targets/create`](http://localhost:9002/kpi-targets/create) ve `/kpi-targets/{id}/edit`):
1. **Master Data Bağımlı (Cascading) Kriter Filtreleri:** Form alanlarındaki master data kırılımlarının (örn. Ülke -> Şehir, İşyeri -> Renk, Gramaj, Kalınlık, IV, Versiyon vb.) Master Data sayfasında tanımlanan hiyerarşik kurallara (`parent_id`) göre birbirine dinamik olarak bağlanması sağlanacaktır. (Örn: Amerika seçilince Şehir kutusunda sadece Amerika'nın şehirleri gelecek; İşyeri seçilince Renk kutusunda sadece o işyerine ait renkler listelenecektir).
2. **Master Data Tabanlı Birim (Unit) Yönetimi:** "Hedef Miktarı & Yılı" bölümündeki `Birim` serbest metin kutusu yerine, Master Data altında oluşturulacak **"BİRİM"** kategorisinden (`Ton`, `Adet`, `KG`, `TL`, `USD`, `EUR`, `Metre`, `Koli`, `Palet` vb.) dinamik olarak beslenen bir seçim kutusu oluşturulacak; yöneticiler dilediğinde Master Data sayfasından yeni birimler ekleyebilecektir.
3. **Hedef Alanı Ayrıştırması:** "Hedef Alanı (Sayısal Sütun)" açılır kutusu sadece gerçek toplanabilir metriklerle (`TONAJ`, `TOPLAM TESLİMAT MİKTARI`, `TOPLAM TUTAR` vb.) sınırlandırılarak, master data veya dropdown alanlarının hedef metriği kutusunu kirletmesi engellenecektir.

---

## 2. Kapsam ve Yapılacak İşlemler

### 2.1. Master Data "BİRİM" Türü ve Veritabanı Tohumlama
* `master_data_types` tablosuna `name: "BİRİM"` (slug: `birim_...`) kaydı oluşturulacaktır.
* `master_data_items` tablosuna bu türe bağlı standart birimler eklenecektir:
  * Ton, Adet, KG, TL, USD, EUR, Metre, Koli, Palet.
* Bu sayede kullanıcılar [`/master-data`](http://localhost:9002/master-data) sayfasına gittiklerinde istedikleri yeni ölçü birimlerini ekleyip çıkarabilecektir.

### 2.2. Backend Controller & AJAX API Güncellemesi (`KpiTargetC.php`)
* `getFields(FormTemplateM $template)` metodu güncellenecek:
  * `numeric_fields`: Sadece toplanabilir gerçek sayısal alanlar dönecektir (`type: number, decimal` ve `select, master_data, text` hariç).
  * `filter_fields`: Kriter olarak kullanılabilecek tüm boyutlar (`master_data`, `select`, `text` vb.).
  * `master_data_tree`: Master Data ağacı (`MasterDataTypeM::with('items')->get()`) frontend'e aktarılarak hiyerarşik `parent_id` ilişkileri dinamik olarak sunulacaktır.
* `create()` ve `edit()` metodlarında:
  * `units`: Master Data "BİRİM" öğeleri çekilerek view'a gönderilecektir.

### 2.3. Frontend Cascading (Bağımlı) Filtre Arayüzü (`create.blade.php` & `edit.blade.php`)
* Alpine.js `getFieldOptions(field)` fonksiyonu eklenecek:
  * Eğer alanın `master_data_type_id`'si bir `parent_id`'ye sahipse (örn: Şehir -> Ülke, Renk -> İşyeri), formdaki üst alanın seçili değeri aranacak.
  * Üst alan henüz seçilmemişse: Alt alan devre dışı kalacak ve *"Önce [Üst Alan] seçiniz"* uyarısı verilecek.
  * Üst alan seçildiğinde: Yalnızca seçilen öğeye ait (`parent_id == selectedParentItemId`) alt öğeler listelenecektir.
* `handleCriteriaChange(fieldId)` fonksiyonu:
  * Üst alan değiştiğinde veya temizlendiğinde, ona bağlı tüm alt alanların seçili kriterleri zincirleme olarak temizlenecektir.
* "Birim" alanı serbest inputtan Master Data tabanlı şık bir `<select>` kutusuna dönüştürülecektir.

---

## 3. Test ve Doğrulama
1. Master Data tohumlama test edilerek `/master-data` üzerinde "BİRİM" kategorisi ve öğeleri doğrulanacak.
2. `/kpi-targets/create` sayfasında:
   * Şablon seçildiğinde "Hedef Alanı" kutusunda yalnızca gerçek metriklerin (Tonaj, Teslimat Miktarı vb.) listelendiği doğrulanacak.
   * "Ülke" olarak "Amerika" seçildiğinde "Şehir" kutusunda yalnızca Amerika şehirlerinin geldiği doğrulanacak.
   * "İŞYERİ" olarak "PREFORM" seçildiğinde "RENK", "GRAMAJ" kutularında yalnızca Preform'a ait değerlerin geldiği doğrulanacak.
   * "İŞYERİ" değiştirildiğinde alt kriterlerin otomatik sıfırlandığı doğrulanacak.
   * "Birim" alanında Master Data birimlerinin (Ton, Adet vb.) geldiği ve kaydedildiği doğrulanacak.
3. `/kpi-targets/{id}/edit` ekranında mevcut kriterlerin doğru cascading değerleriyle yüklendiği doğrulanacak.

