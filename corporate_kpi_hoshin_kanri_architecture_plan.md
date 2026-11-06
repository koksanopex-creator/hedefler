# Kurumsal KPI & Hoshin Kanri Dinamik Hedef Yönetim Sistemi Mimari Planı ve Senaryosu

## 1. Yönetici Özeti ve Vizyon
Bu sistem, Köksan Holding bünyesindeki fabrika, işyeri ve satış gruplarının hedef belirleme, veri toplama ve performans takibi süreçlerini **Hoshin Kanri (Stratejik Hedef Yayılımı)** metodolojisi ile dijitalleştiren kurumsal bir performans yönetim mimarisidir.

Hedefler tablolarda sabit metinler olarak değil; **dinamik form alanları** (`form_fields`, örn: Tonaj, Toplam Teslimat Miktarı, İşyeri, Renk, Gramaj vb.) ile doğrudan ilişkilendirilir.

---

## 2. Hoshin Kanri Hiyerarşik Hedef Yayılım Modeli

```
               ┌────────────────────────────────────────────────────────┐
               │              ŞİRKET / DİREKTÖRLÜK HEDEFİ               │
               │        (Toplam Hedef: 150.000 Ton / 2027 Yılı)         │
               └───────────────────────────┬────────────────────────────┘
                                           │
                    ┌──────────────────────┴──────────────────────┐
                    ▼                                             ▼
     ┌─────────────────────────────┐               ┌─────────────────────────────┐
     │      GRUP HEDEFİ 1          │               │      GRUP HEDEFİ 2          │
     │      (Yurt İçi Satış)       │               │     (Yurt Dışı Satış)       │
     │     Hedef: 60.000 Ton       │               │     Hedef: 90.000 Ton       │
     └──────────────┬──────────────┘               └──────────────┬──────────────┘
                    │                                             │
      ┌─────────────┴─────────────┐                 ┌─────────────┴─────────────┐
      ▼                           ▼                 ▼                           ▼
┌─────────────┐             ┌─────────────┐   ┌─────────────┐             ┌─────────────┐
│ Bireysel 1  │             │ Bireysel 2  │   │ Bireysel 3  │             │ Bireysel 4  │
│İbrahim Çelik│             │ Gülnur Nurt │   │ Caner Demir │             │ Seda Aydın  │
│ 12.000 Ton  │             │ 15.000 Ton  │   │ 25.000 Ton  │             │ 30.000 Ton  │
└─────────────┘             └─────────────┘   └─────────────┘             └─────────────┘
```

### Kümülasyon İlkesi (Bottom-Up Aggregation):
1. **Bireysel Veri Girişi:** İbrahim Çelik form üzerinden satış yaptıkça (örn: 200 Ton Preform), gerçekleşen veri anında İbrahim'in bireysel hedefine işlenir.
2. **Grup Kümülasyonu:** İbrahim'in ve gruptaki diğer personellerin gerçekleştirdiği toplam tonaj, **Yurt İçi Satış Grubu**'nun hedef havuzunu kümülatif olarak besler.
3. **Yönetici Performansı:** Grup yöneticisinin hedefi, hem ekibinin toplam gerçekleşme oranından hem de varsa kendi bireysel portföyünden otomatik hesaplanır.

---

## 3. Gerçek Hayat Operasyonel Senaryosu (Köksan Örneği)

### Senaryo Tanımı:
- **Şablon:** Satış ve Sevkiyat Formu (`form_templates/2`)
- **Dönem:** 2027 Hedef Yılı
- **Personel:** İbrahim ÇELİK (Yurt İçi Satış Temsilcisi)
- **Grup:** Yurt İçi Satış Grubu (Grup Yöneticisi: Ahmet GÜLLÜCE)

### A. İbrahim Çelik'e Atanan Hedef Matrisi:
| Hedef Tipi | Hedef Kapsamı / Alanı | Kırılım Koşulu (Filtre) | Hedef Değer | Birim |
| :--- | :--- | :--- | :--- | :--- |
| **Genel Nicel** | TONAJ | Tüm Satışlar | **12.000** | Ton |
| **Genel Nicel** | TOPLAM TESLİMAT MİKTARI | Tüm Satışlar | **1.500.000** | Adet |
| **Kategorik Kırılım** | İŞYERİ Bazlı Tonaj | İŞYERİ = PREFORM | **6.000** | Ton |
| **Kategorik Kırılım** | İŞYERİ Bazlı Tonaj | İŞYERİ = KAPAK | **4.000** | Ton |
| **Kategorik Kırılım** | İŞYERİ Bazlı Tonaj | İŞYERİ = RESIN | **2.000** | Ton |
| **Spesifik Mikro Hedef**| Ürün / Renk / Gramaj | İŞYERİ = PREFORM <br> RENK = Sarı <br> GRAMAJ = 25g | **20** | Ton |

### B. Gerçekleşme ve Kıyaslama Mantığı:
İbrahim Çelik `form-entries/create/2` ekranından satır satır veri girdiğinde:
1. Satırdaki **İŞYERİ** "PREFORM", **RENK** "Sarı", **GRAMAJ** "25", **TONAJ** "5" girildiğinde;
2. Sistem bu 5 tonu:
   - İbrahim'in "Preform Sarı 25g" mikro hedefine (`5 / 20 = %25`),
   - İbrahim'in "Preform Genel Tonaj" hedefine (`5 / 6.000 = %0.08`),
   - İbrahim'in "Toplam Tonaj" hedefine (`5 / 12.000 = %0.04`),
   - Yurt İçi Satış Grubu'nun genel tonaj havuzuna (`5 / 60.000 = %0.008`) **aynı anda** yansıtır!

---

## 4. Veritabanı ve Mimari Tasarım

### 1. `kpi_targets` (Hedef Tanımları Tablosu)
- `id` (PK)
- `form_template_id` (FK -> form_templates.id)
- `target_field_id` (FK -> form_fields.id - Örn: "TONAJ" alanı)
- `title` (Hedef Başlığı: "2027 Preform Satış Tonajı")
- `target_year` (Yıl: 2027)
- `target_period` (Yıllık, Q1, Q2, Aylık vb.)
- `assignee_type` (Enum: `user`, `group`, `role`, `department`)
- `assignee_id` (User ID veya Group ID)
- `target_value` (Hedeflenen Sayısal Değer: 12000.00)
- `unit` (Birim: Ton, Adet, TL vb.)
- `criteria_json` (Filtre Kriterleri: `{"isyeri": "PREFORM", "renk": "SARI", "gramaj": "25"}`)
- `created_by`, `status`, `timestamps`

### 2. `kpi_target_realizations` (Gerçekleşme ve Hesaplama Görünümü / Cache)
- Form onaylandığında (`status = 'approved'`) veya canlı taslak modunda tetiklenen motor.
- `target_id`, `actual_value`, `achievement_rate` (`actual / target * 100`), `variance`, `last_calculated_at`.

---

## 5. Yeni Arayüzler ve Kullanıcı Deneyimi

1. **Hedef Belirleme & Yönetim Merkezi (`/kpi-targets`):**
   - Şablon seçimi (`form-templates/2`),
   - Hedef alanı seçimi (Sayısal/Nicel alanlar: Tonaj, Teslimat vb.),
   - Hedef sahibi (Kullanıcı, Grup veya Yönetici),
   - Kriter belirleme (Dinamik dropdownlar: Master Data İşyeri, Renk, Gramaj filtreleri),
   - Hedef değeri girişi.

2. **Kullanıcı Profil Sayfası (`/kullanici-profil/{id}`):**
   - Kişisel bilgiler, departman ve grup bilgisi,
   - **Hedeflerim & Performans Karnesi:**
     - Halka ve ilerleme çubukları ile KPI hedefleri,
     - Hedeflenen vs Gerçekleşen kıyaslaması,
     - Renkli başarı göstergesi (Kırmızı < %70, Sarı %70-%99, Yeşil >= %100).
   - Son doldurulan formlar ve işlem geçmişi.

3. **Grup & Yönetici Dashboard'u:**
   - Grup Müdürü için ekibinin toplam karnesi ve ekip üyeleri başarı sıralaması.

