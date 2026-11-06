# Laravel Yetki Matrisi Sistemi — Kod Üretim Promptu
### (Claude Code / Cursor / Windsurf vb. IDE tabanlı AI ajanına verilecek)

Aşağıdaki metni olduğu gibi IDE'deki AI ajanına yapıştır. Uzun olduğu için ajan isterse fazları tek tek çalıştırabilir; sıralama zaten prompt içinde tanımlı.

---

```
SEN BİR LARAVEL BACKEND MİMARISIN. Aşağıdaki projeyi kurallara harfiyen uyarak inşa edeceksin.
Kod yazmadan önce her fazda önce PLAN yaz, sonra kodu üret. Onay bekleme, fazları sırayla uygula
ama her fazın sonunda ne yaptığını ve hangi dosyaları oluşturduğunu/değiştirdiğini özetle.

========================================
GENEL KURALLAR (TÜM FAZLARDA GEÇERLİ)
========================================
1. THINK FIRST: Her faz başında kısa bir analiz yap, sonra kodla.
2. CONTEXT AWARE: Mevcut proje yapısını (composer.json, mevcut migration/model varsa) önce incele,
   üzerine bozmadan ekle. Var olan bir dosyayı gereksiz yere yeniden yazma.
3. MINIMUM CHANGE: Sadece o fazda istenen dosyaları oluştur/değiştir. Kapsam dışına çıkma.
4. DRY: Tekrar eden mantığı Service/Trait/Scope içine al, controller'lara veya view'lara kopyalama.
5. CONTROLLER ŞİŞİRME YASAK: Controller metodu sadece şunları yapar: validate (FormRequest),
   authorize (Policy), bir Service metodunu çağır, response dön. İş mantığı controller'da YAZILAMAZ.
6. SECURITY:
   - Her migration'da foreign key ZORUNLU (ilgisine göre cascade/restrict/null-on-delete seç, rastgele seçme).
   - Her model'de $fillable açıkça tanımlanacak, $guarded kullanılmayacak.
   - Kullanıcıdan gelen hiçbir input, yetkisiz bir alanı (örn. role_id, user_id, approved_by) set edemeyecek;
     bu alanlar controller/service içinde auth()->user() üzerinden set edilecek.
   - Tüm view'larda Blade'in otomatik escape'i ({{ }}) kullanılacak, {!! !!} sadece zorunlu ve
     güvenilir içerik için, gerekçesiyle yorum satırı eklenerek kullanılacak.
   - Sorgularda ham SQL/string concatenation YASAK, her zaman Eloquent/Query Builder parametre binding.
   - Her linkte asset() / route() helper kullanılacak, hardcoded path YASAK.
7. PERFORMANCE: N+1 query'e izin verme, ilişkili veri çekilen her listelemede ->with() kullan.
   Gereksiz count()/exists() tekrarlarından kaçın.
8. DEBUG FRIENDLY: Kritik iş akışlarında (onay, düzenleme, silme, export) Log facade veya
   ActivityLogger servisi üzerinden loglama yap. try/catch bloklarında sessizce yutma, en azından logla.
9. OUTPUT FORMAT: Her dosya için şu formatı kullan:
   - Dosya yolu (proje köküne göre tam yol)
   - Ne işe yaradığı (1 satır)
   - Kod bloğu

========================================
PROJE ÖZETİ
========================================
Laravel tabanlı, login zorunlu, yetki matrisi ile yönetilen bir veri girişi/onay sistemi.
4 rol var (roller sabit kod değil, DB'den yönetilir): sistem_yoneticisi, yonetim (read-only),
veri_giris_yoneticisi (n grup yöneticisi olabilir), veri_girisi (operatör, n tane olabilir).

- Operatörler sadece kendi verisini görür/düzenler.
- Grup yöneticileri sadece kendi yönettiği grup(lar)daki operatörlerin verisini görür/düzenler
  (bu işlem loglanır), kendi girdiği veriyi ise sadece yönetici katmanı (diğer grup yöneticileri +
  sistem_yoneticisi + yonetim) görebilir.
- sistem_yoneticisi ve yonetim tüm veriyi görür; yonetim sadece görüntüler, düzenleyemez.
- Sistem yöneticisi n adet dinamik form alanı tasarlar (nicel/nitel, dropdown, otomatik-kullanıcı-adı,
  toplanabilir nicel alanlar), operatörler bu formu n satır olarak doldurur.
- Form export'u, sistem yöneticisinin yüklediği bir Excel şablonuna, alan-hücre eşlemesiyle yapılır.
- Form doldurma/düzenleme erişimi (açık/süreli/kilitli) sistem yöneticisi tarafından kişi bazlı veya
  grup bazlı planlanabilir.
- Tüm sayfa görünürlüğü, butonlar, aksiyonlar permission matrix'e (roles<->permissions pivot) bağlıdır.

========================================
FAZ 1 — TEMEL: ROL / YETKİ / GRUP / KULLANICI
========================================
Oluşturulacaklar:
- Migrations: roles, permissions, role_permission, user_permission, groups, group_manager,
  users tablosuna role_id + group_id + is_active ekleme (foreign key'lerle)
- Models: Role, Permission, Group, User (ilişkiler: Role hasMany User, Role belongsToMany Permission,
  User belongsToMany Permission (user_permission, pivot allowed), Group belongsToMany User (group_manager,
  "managedGroups" ilişkisi manager tarafında), User belongsTo Group (operatör için, "group" ilişkisi))
- app/Services/PermissionService.php: bir kullanıcının efektif yetkisini hesaplar
  (role_permission + user_permission override'ı birleştirir). User modeline can($slug) helper'ı
  (Laravel'in kendi Gate sistemine entegre, ör. Gate::before ile).
- Middleware: app/Http/Middleware/CheckPermission.php — route'a ->middleware('permission:slug') ile
  bağlanacak, yetkisi olmayan 403 dönecek.
- Seeders (aşağıdaki tam mantığa göre — bunları birebir kullan, sadece proje yapısına adapte et):

  RoleSeeder: sistem_yoneticisi, yonetim, veri_giris_yoneticisi, veri_girisi (4 sabit sistem rolü,
  is_system=true, updateOrCreate ile idempotent)

  PermissionSeeder: aşağıdaki module.action matrisini oluştur:
  users.manage, groups.manage, roles.manage, form_templates.manage, form_schedules.manage,
  form_entries.view_own, form_entries.view_group, form_entries.view_all, form_entries.create,
  form_entries.edit_own, form_entries.edit_group, form_entries.approve, exports.manage, logs.view

  RolePermissionSeeder: sistem_yoneticisi -> hepsi; yonetim -> form_entries.view_all, logs.view;
  veri_giris_yoneticisi -> view_own, view_group, create, edit_own, edit_group, approve, exports.manage;
  veri_girisi -> view_own, create, edit_own

  GroupSeeder: örnek 2 grup (Grup A, Grup B) — ama admin panelinden n tane eklenebilir olacak
  şekilde tasarla, hardcoded grup sayısı OLMAYACAK.

  UserSeeder: 1 sistem_yoneticisi, 1 yonetim, 2 grup yöneticisi (her biri kendi grubuna
  managedGroups()->sync ile atanmış), her gruba 2'şer operatör (group_id set edilmiş).
  DatabaseSeeder içinde sıra: Role -> Permission -> RolePermission -> Group -> User.

Bu fazın sonunda: migrate --seed çalıştığında sistemin login olup rollere göre farklı Gate::allows()
sonuçları verdiğini doğrulayacak kısa bir açıklama/test önerisi de yaz (otomatik test yazmasan da olur,
ama nasıl manuel doğrulanacağını belirt).

========================================
FAZ 2 — DİNAMİK FORM TASARIMI (ADMIN TARAFI)
========================================
Oluşturulacaklar:
- Migrations: form_templates, form_fields (type: text/number/textarea/select/date/auto_user,
  value_type: nicel/nitel, options json, is_required, is_summable, sort_order) — foreign key'lerle
- Models: FormTemplate (hasMany FormField, sıralı), FormField
- FormRequest: StoreFormFieldRequest / UpdateFormFieldRequest — options alanı sadece type=select
  iken zorunlu olacak şekilde conditional validation
- Controller: FormTemplateController, FormFieldController — sadece form_templates.manage yetkisi
  olanlar erişebilir (policy + middleware)
- Service: FormFieldService — alan ekleme/sıralama/silme mantığı (controller'da değil burada)
- View: Blade tabanlı form tasarımcı ekranı (alan ekle/sil/sırala, tip seçimi, nicel ise
  "toplanabilir" checkbox'ı, select ise dinamik dropdown seçenek ekleme)

========================================
FAZ 3 — FORM DOLDURMA / KAYIT (OPERATÖR TARAFI)
========================================
Oluşturulacaklar:
- Migrations: form_entries (status: draft/submitted/approved/rejected, user_id, group_id,
  approved_by, approved_at), form_entry_rows (n satır), form_entry_values (EAV: row_id + field_id + value)
- Models: FormEntry, FormEntryRow, FormEntryValue + FormEntry üzerinde visibleTo($user) local scope
  (Faz 1'deki permission mantığına göre: view_all -> hepsi, view_group -> managedGroups içindeki
  group_id'ler + yönetici katmanının kendi verisi, view_own -> sadece user_id = auth()->id())
- Policy: FormEntryPolicy (view, update, approve — visibleTo scope'u ile tutarlı kurallar)
- FormRequest: StoreFormEntryRequest/UpdateFormEntryRequest — auto_user tipindeki alanlar request'ten
  KABUL EDİLMEYECEK, service içinde otomatik dolduracaksın.
- Service: FormEntryService (satır ekleme/güncelleme, toplam hesaplama — DB'ye yazılmadan sadece
  view'a taşınan hesaplanmış değer olarak)
- Controller: FormEntryController (index/create/store/edit/update/approve — approve ayrı bir
  action + ayrı permission kontrolü)
- ActivityLogger entegrasyonu: bir grup yöneticisi bir operatörün verisini görüntülediğinde veya
  düzenlediğinde log kaydı at (user_id, action, subject, ip_address)
- View: dinamik form render (form_fields'e göre input tipini seçen bir Blade partial/component),
  n satır ekleme (JS ile satır çoğaltma önerisi), show_row_totals=true ise summable alanların
  toplamını altta göster.

========================================
FAZ 4 — FORM ERİŞİM TAKVİMİ (AÇMA/KAPAMA/KİLİTLEME)
========================================
Oluşturulacaklar:
- Migration: form_access_schedules (scope_type: global/group/user, scope_id, starts_at, ends_at,
  is_locked, created_by) — foreign key'lerle
- Model: FormAccessSchedule
- Service: app/Services/FormAccessService.php -> isOpenFor(FormTemplate $template, User $user): bool
  Öncelik sırası: is_locked=true olan herhangi bir kayıt varsa (user > group > global sırasıyla ilk
  eşleşeni al) kapalı; yoksa tarih aralığı kontrolü (starts_at/ends_at null ise sınırsız); user scope
  varsa onu kullan, yoksa group scope, yoksa global scope, hiçbiri yoksa varsayılan kapalı.
  BU MANTIK TEK YERDE YAŞAYACAK, controller'da veya view'da tekrar edilmeyecek.
- Controller: FormAccessScheduleController (sadece form_schedules.manage yetkisi, sistem yöneticisi)
- FormEntryController'daki store/update metodlarına bu servis ile guard ekle (kapalıysa 423 Locked dön).
- View: admin panelinde takvim/tarih seçici ile grup veya kişi bazlı açma-kapama-kilitleme ekranı.

========================================
FAZ 5 — EXCEL ŞABLON EXPORT
========================================
Oluşturulacaklar:
- Migration: form_export_templates (form_template_id, name, template_path, field_mapping json)
- Model: FormExportTemplate
- Service: app/Services/ExportService.php — PhpSpreadsheet ile:
  1) template_path'teki .xlsx dosyasını yükle
  2) field_mapping'deki her {form_field_id: hücre_referansı} için, seçilen form_entry'nin
     ilgili değerini o hücreye yaz
  3) Yeni bir dosya olarak stream/download et (orijinal şablonu bozma)
- Controller: ExportController (exports.manage yetkisi)
- Admin tarafında field_mapping'i ayarlayacak basit bir eşleme ekranı (form_field listesi + hücre
  referansı text input, örn. "C5")

========================================
FAZ 6 — GÖRÜNÜRLÜK / MENÜ / ASSET
========================================
- Ana layout'ta menü öğeleri @can('slug') direktifiyle sarmalanacak, permission yoksa link hiç
  render edilmeyecek (sadece disabled değil, DOM'da olmayacak).
- Tüm CSS/JS/görsel linkleri asset() helper'ı ile, route linkleri route() helper'ı ile.
- Login zorunlu: web.php'de tüm bu route'lar auth middleware grubunun altında olacak, guest'e
  kapalı. Login sonrası yönlendirme role göre farklılaşabilir (opsiyonel, dashboard tek olabilir
  ve içerik permission'a göre değişir).

========================================
ÇALIŞMA ŞEKLİ
========================================
- Fazları SIRAYLA uygula (1'den önce 2'yi yapma, önceki fazın migration'ları/modelleri sonrakinin
  foreign key'lerine referans veriyor).
- Her faz bitince: "Faz X tamamlandı, şu dosyalar oluşturuldu/değiştirildi: [liste]. Şimdi Faz X+1'e
  geçiyorum." de ve devam et.
- Emin olmadığın bir iş kuralı varsa (örn. mevcut projede zaten bir Role/Permission paketi kuruluysa,
  ör. spatie/laravel-permission), kod yazmadan önce durum tespiti yap ve bana sor; asla var olan bir
  paketle çakışan paralel bir yapı kurma.
```

---

**Not:** Bu prompt, önceki cevapta verdiğim mimari dokümanın (roller, tablolar, yetki mantığı, dinamik form, export, erişim takvimi) yürütülebilir talimat haline getirilmiş hali. IDE'deki ajan mevcut projeni (varsa `spatie/laravel-permission` gibi paketler, mevcut `users` tablosu vs.) tarayıp ona göre adapte edecek şekilde kurgulandı — CONTEXT AWARE kuralını IDE seviyesinde de zorluyor.
