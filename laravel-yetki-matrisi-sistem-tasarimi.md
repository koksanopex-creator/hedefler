# Laravel Yetki Matrisi Tabanlı Veri Girişi Sistemi
## Mimari Tasarım Dokümanı

---

## 0. Varsayımlar (Onayına Sunuluyor)

Talepte 3 nokta muğlaktı, aşağıdaki gibi yorumlayıp ilerledim:

1. **"Yöneticilerin verilerini sadece yöneticiler görebilir"** → Bir grup yöneticisinin kendi girdiği veri; tüm grup yöneticileri + Sistem Yöneticisi + Yönetim rolü görebilir, ama **Veri Girişi** rolündeki operatörler göremez. (Yani yönetici verisi, yönetici katmanının ortak havuzu.)
2. **"n grup, n veri giriş yöneticisi"** → Bir grubun birden fazla yöneticisi olabilir, bir yönetici birden fazla grubu yönetebilir (many-to-many). Bir operatör ise **tek bir gruba** bağlıdır (1-N).
3. **Form onay süreci** ("veri girenlerin verilerini inceleyip onaylayan... yönetim rolü read only") → Yönetim rolü onay/red aksiyonu **yapmaz**, sadece görüntüler. Onay işlemini **Grup Yöneticisi** ve **Sistem Yöneticisi** yapar (yetki matrisinde ayrı bir permission olarak tanımlandı, dilersen Yönetim'e de onay yetkisi permission matrisinden tek satırla açılabilir — sistemin gücü de zaten burada).

Bunlar dışında hiçbir şey sabit/hardcoded değil; **rol sayısı, grup sayısı, yetki sayısı** tamamen veritabanı üzerinden (`roles`, `permissions`, `groups` tabloları) yönetiliyor. Kod hiçbir yerde `if ($role == 'admin')` şeklinde yazılmayacak, her zaman `$user->can('permission.slug')`.

---

## 1. Rol ve Grup Hiyerarşisi

```
Sistem Yöneticisi (sistem_yoneticisi)
   └─ Her şeyi görür/yönetir. Yetki matrisini, formları, grupları, açma-kapama takvimini o yönetir.

Yönetim (yonetim)  — READ ONLY
   └─ Tüm grupların, tüm operatörlerin, tüm yöneticilerin verisini görür. Düzenleyemez.

Veri Giriş Yöneticisi (veri_giris_yoneticisi)  — N tane olabilir
   └─ Kendi grubu/gruplarındaki operatörlerin verisini görür + düzenler (LOGLANIR)
   └─ Kendi girdiği veri sadece yönetici katmanınca görülür
   └─ N gruba atanabilir (pivot tablo)

Veri Girişi (veri_girisi) — operatör — N tane olabilir
   └─ SADECE kendi verisini görür/düzenir
   └─ Bir gruba bağlıdır (users.group_id)
```

Roller ve gruplar **sabit kod değil, tablo verisi**. Yeni bir rol ya da yeni bir grup eklemek migration gerektirmez, sadece kayıt eklemek yeterlidir.

---

## 2. Yetki Matrisi Mantığı (RBAC + Permission Matrix)

Klasik "role içine gömülü yetki" yerine **role ↔ permission** ayrık pivot yapısı kullanılıyor. Böylece "Sistem Yöneticisi bir gruba özel yetki verir" gibi ince ayarlar da mümkün olur.

- `permissions` tablosu **modül.aksiyon** formatında slug tutar: `form_entries.view_own`, `form_entries.view_group`, `form_entries.view_all`, `form_entries.approve`, `form_templates.manage`, `form_schedules.manage`, `exports.manage`, `logs.view`, `users.manage` vb.
- `role_permission` pivot bu yetkileri role bağlar.
- Blade, Controller, Middleware, hatta menü/link görünürlüğü **her zaman** `$user->can('slug')` ile kontrol edilir. Yeni bir sayfa/buton eklendiğinde tek yapılacak iş: yeni bir `permission` satırı eklemek ve admin panelinden ilgili role/kullanıcıya atamak.
- İsteğe bağlı kişi bazlı override için `user_permission` tablosu (aşağıda) — "bu operatöre özel olarak export yetkisi de aç" gibi istisnai durumlar için.

---

## 3. Veritabanı Şeması

### 3.1 Kimlik / Yetki Katmanı

**`database/migrations/xxxx_create_roles_table.php`**
```php
Schema::create('roles', function (Blueprint $table) {
    $table->id();
    $table->string('name');
    $table->string('slug')->unique();
    $table->string('description')->nullable();
    $table->boolean('is_system')->default(false); // sistem rolleri silinemez
    $table->timestamps();
});
```

**`database/migrations/xxxx_create_permissions_table.php`**
```php
Schema::create('permissions', function (Blueprint $table) {
    $table->id();
    $table->string('module');      // form_entries, form_templates, users, exports...
    $table->string('action');      // view_own, view_group, view_all, create, edit, approve...
    $table->string('slug')->unique(); // module.action
    $table->string('name');        // ekranda gösterilecek isim
    $table->timestamps();
});
```

**`database/migrations/xxxx_create_role_permission_table.php`**
```php
Schema::create('role_permission', function (Blueprint $table) {
    $table->foreignId('role_id')->constrained()->cascadeOnDelete();
    $table->foreignId('permission_id')->constrained()->cascadeOnDelete();
    $table->primary(['role_id', 'permission_id']);
    $table->timestamps();
});
```

**`database/migrations/xxxx_create_user_permission_table.php`** (kişi bazlı istisna)
```php
Schema::create('user_permission', function (Blueprint $table) {
    $table->foreignId('user_id')->constrained()->cascadeOnDelete();
    $table->foreignId('permission_id')->constrained()->cascadeOnDelete();
    $table->boolean('allowed')->default(true); // false = role'den gelen yetkiyi bu kullanıcıda iptal et
    $table->primary(['user_id', 'permission_id']);
    $table->timestamps();
});
```

### 3.2 Grup / Kullanıcı Katmanı

**`database/migrations/xxxx_create_groups_table.php`**
```php
Schema::create('groups', function (Blueprint $table) {
    $table->id();
    $table->string('name');
    $table->string('description')->nullable();
    $table->boolean('is_active')->default(true);
    $table->timestamps();
});
```

**`database/migrations/xxxx_create_users_table.php`** (mevcut users tablosuna eklenecek alanlar)
```php
Schema::table('users', function (Blueprint $table) {
    $table->foreignId('role_id')->constrained()->restrictOnDelete();
    $table->foreignId('group_id')->nullable()->constrained('groups')->nullOnDelete(); // sadece operatör için dolu
    $table->boolean('is_active')->default(true);
});
```

**`database/migrations/xxxx_create_group_manager_table.php`** (n yönetici ↔ n grup)
```php
Schema::create('group_manager', function (Blueprint $table) {
    $table->foreignId('group_id')->constrained()->cascadeOnDelete();
    $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
    $table->primary(['group_id', 'user_id']);
    $table->timestamps();
});
```

### 3.3 Dinamik Form Katmanı

**`database/migrations/xxxx_create_form_templates_table.php`**
```php
Schema::create('form_templates', function (Blueprint $table) {
    $table->id();
    $table->string('name');
    $table->string('slug')->unique();
    $table->text('description')->nullable();
    $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
    $table->boolean('is_active')->default(true);
    $table->boolean('show_row_totals')->default(false); // alt toplam gösterilsin mi
    $table->timestamps();
});
```

**`database/migrations/xxxx_create_form_fields_table.php`**
```php
Schema::create('form_fields', function (Blueprint $table) {
    $table->id();
    $table->foreignId('form_template_id')->constrained()->cascadeOnDelete();
    $table->string('label');
    $table->string('field_key');              // slug, DB'de referans için
    $table->enum('type', ['text','number','textarea','select','date','auto_user']);
    $table->enum('value_type', ['nicel','nitel']); // nicel=sayısal, nitel=metinsel/kategorik
    $table->json('options')->nullable();       // select tipinde dropdown seçenekleri
    $table->boolean('is_required')->default(false);
    $table->boolean('is_summable')->default(false); // nicel alan toplama dahil edilsin mi
    $table->unsignedInteger('sort_order')->default(0);
    $table->timestamps();

    $table->unique(['form_template_id', 'field_key']);
});
```

**`database/migrations/xxxx_create_form_entries_table.php`** (bir kullanıcının bir forma ait "kaydı" — n satır içerir)
```php
Schema::create('form_entries', function (Blueprint $table) {
    $table->id();
    $table->foreignId('form_template_id')->constrained()->restrictOnDelete();
    $table->foreignId('user_id')->constrained('users')->restrictOnDelete(); // veriyi giren
    $table->foreignId('group_id')->nullable()->constrained('groups')->nullOnDelete();
    $table->enum('status', ['draft','submitted','approved','rejected'])->default('draft');
    $table->timestamp('submitted_at')->nullable();
    $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
    $table->timestamp('approved_at')->nullable();
    $table->timestamps();
});
```

**`database/migrations/xxxx_create_form_entry_rows_table.php`** (n satır desteği)
```php
Schema::create('form_entry_rows', function (Blueprint $table) {
    $table->id();
    $table->foreignId('form_entry_id')->constrained()->cascadeOnDelete();
    $table->unsignedInteger('sort_order')->default(0);
    $table->timestamps();
});
```

**`database/migrations/xxxx_create_form_entry_values_table.php`** (EAV — her satırın her alan için değeri)
```php
Schema::create('form_entry_values', function (Blueprint $table) {
    $table->id();
    $table->foreignId('form_entry_row_id')->constrained()->cascadeOnDelete();
    $table->foreignId('form_field_id')->constrained()->cascadeOnDelete();
    $table->text('value')->nullable();
    $table->timestamps();

    $table->unique(['form_entry_row_id', 'form_field_id']);
});
```

### 3.4 Form Açma / Kapama / Kilitleme Takvimi

**`database/migrations/xxxx_create_form_access_schedules_table.php`**
```php
Schema::create('form_access_schedules', function (Blueprint $table) {
    $table->id();
    $table->foreignId('form_template_id')->constrained()->cascadeOnDelete();
    $table->enum('scope_type', ['global','group','user']); // öncelik: user > group > global
    $table->unsignedBigInteger('scope_id')->nullable();     // group_id ya da user_id (scope_type'a göre)
    $table->timestamp('starts_at')->nullable(); // null = şimdi açık
    $table->timestamp('ends_at')->nullable();   // null = süresiz
    $table->boolean('is_locked')->default(false); // her şeyin üstünde: true ise kesin kapalı
    $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
    $table->timestamps();

    $table->index(['form_template_id', 'scope_type', 'scope_id']);
});
```

> **Erişim önceliği:** `user` scope > `group` scope > `global` scope. `is_locked = true` her koşulda diğer her şeyi ezer. Tarih aralığı dışındaysa da kapalı sayılır. Bu mantık `FormAccessService::isOpenFor($template, $user)` içinde tek yerde toplanır (DRY).

### 3.5 Excel Şablon Eşleme

**`database/migrations/xxxx_create_form_export_templates_table.php`**
```php
Schema::create('form_export_templates', function (Blueprint $table) {
    $table->id();
    $table->foreignId('form_template_id')->constrained()->cascadeOnDelete();
    $table->string('name');
    $table->string('template_path'); // storage'daki .xlsx şablon dosyası
    $table->json('field_mapping');   // {"form_field_id": "hücre_referansı (örn: C5)"}
    $table->timestamps();
});
```
PhpSpreadsheet ile şablon dosyası açılır, `field_mapping`'deki hücrelere değerler yazılır (Maatwebsite/Laravel-Excel'in `FromTemplate` yaklaşımı yerine ham PhpSpreadsheet kullanmak, "belirlediğim excel şablonuna göre" isteği için daha uygun — kolon eşlemesi admin panelinden sürükle bırak/dropdown ile ayarlanır).

### 3.6 Log / Denetim

**`database/migrations/xxxx_create_activity_logs_table.php`**
```php
Schema::create('activity_logs', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
    $table->string('action');           // form_entry.updated, form_entry.approved, user.viewed_data ...
    $table->string('subject_type');
    $table->unsignedBigInteger('subject_id');
    $table->text('description')->nullable();
    $table->json('properties')->nullable(); // önce/sonra değerleri
    $table->string('ip_address', 45)->nullable();
    $table->timestamps();

    $table->index(['subject_type', 'subject_id']);
});
```
Grup yöneticisinin operatör verisi üzerindeki her görüntüleme/düzenleme işlemi buraya zorunlu yazılır (talepteki "log tutmak şartı ile" gereği).

---

## 4. Veri Görünürlük Kuralları (Scope Query)

Fat controller yasağı gereği bu mantık **tek bir yerde**, bir Eloquent local scope + policy olarak yaşar:

**`app/Models/FormEntry.php`** (özet)
```php
public function scopeVisibleTo(Builder $query, User $user): Builder
{
    if ($user->can('form_entries.view_all')) {
        return $query; // Sistem Yöneticisi, Yönetim
    }

    if ($user->can('form_entries.view_group')) {
        // Grup Yöneticisi: kendi yönettiği gruplardaki operatörlerin verisi
        // + yönetici katmanının kendi girdiği veriler
        $groupIds = $user->managedGroups()->pluck('groups.id');
        return $query->where(function ($q) use ($groupIds, $user) {
            $q->whereIn('group_id', $groupIds)
              ->orWhereHas('user', fn ($u) => $u->whereHas('role', fn ($r) => $r->where('slug', 'veri_giris_yoneticisi')));
        });
    }

    // Operatör: sadece kendi verisi
    return $query->where('user_id', $user->id);
}
```
Her controller `FormEntry::visibleTo($user)->...` çağırır; hiçbir yerde manuel `if ($user->role == ...)` yazılmaz.

---

## 5. Dinamik Form / Nicel-Nitel Alan Yapısı

- Sistem Yöneticisi `form_templates` + `form_fields` üzerinden formu tasarlar (n alan).
- Her alan `value_type`: **nicel** (sayısal, toplanabilir — `is_summable`) ya da **nitel** (metin/kategorik).
- `type = select` → `options` json içinde dropdown seçenekleri.
- `type = auto_user` → alan formda **readonly**, backend `auth()->user()->name` ile otomatik doldurulur, input olarak hiç açılmaz (mass assignment riskine kapalı).
- Operatör form doldururken n satır (`form_entry_rows`) ekleyebilir; her satırda tanımlı tüm alanlar için `form_entry_values` kaydı oluşur.
- `form_templates.show_row_totals = true` ise, `is_summable = true` olan alanlar için satırlar toplanıp altta gösterilir (bu **sadece görünüm katmanında** hesaplanır, DB'de ayrı toplam satırı tutulmaz — veri tekrarı olmasın diye).

---

## 6. Controller / Servis Katmanı (Şişirme Yasak)

```
app/Http/Controllers/FormEntryController.php   → sadece orkestrasyon (request al, service çağır, response dön)
app/Http/Requests/StoreFormEntryRequest.php    → validasyon (mass assignment koruması burada)
app/Policies/FormEntryPolicy.php               → view/update/approve yetkisi
app/Services/FormAccessService.php             → açık/kapalı/kilitli mantığı (tek kaynak)
app/Services/FormEntryService.php              → satır/değer kaydetme iş mantığı
app/Services/ExportService.php                 → excel şablon doldurma
app/Services/ActivityLogger.php                → tüm modüllerden çağrılan tek log servisi
```

Controller metodu örnek iskelet (kod şişirmeden):
```php
public function update(UpdateFormEntryRequest $request, FormEntry $formEntry)
{
    $this->authorize('update', $formEntry);
    abort_unless($this->formAccessService->isOpenFor($formEntry->formTemplate, auth()->user()), 423);

    $this->formEntryService->updateRows($formEntry, $request->validated());

    return redirect()->route('form-entries.show', $formEntry);
}
```

Mass Assignment koruması: her model `$fillable` açık şekilde tanımlı, `auto_user` gibi alanlar **hiçbir zaman** request'ten değil, `auth()->id()` ile set edilir.

---

## 7. Seeder'lar (Hazır Kod)

**`database/seeders/RoleSeeder.php`**
```php
<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            ['name' => 'Sistem Yöneticisi',     'slug' => 'sistem_yoneticisi',     'is_system' => true],
            ['name' => 'Yönetim (Read Only)',   'slug' => 'yonetim',               'is_system' => true],
            ['name' => 'Veri Giriş Yöneticisi', 'slug' => 'veri_giris_yoneticisi', 'is_system' => true],
            ['name' => 'Veri Girişi',           'slug' => 'veri_girisi',           'is_system' => true],
        ];

        foreach ($roles as $role) {
            Role::updateOrCreate(['slug' => $role['slug']], $role);
        }
    }
}
```

**`database/seeders/PermissionSeeder.php`**
```php
<?php

namespace Database\Seeders;

use App\Models\Permission;
use Illuminate\Database\Seeder;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        $matrix = [
            // module           action          name
            ['users',           'manage',        'Kullanıcı Yönetimi'],
            ['groups',          'manage',        'Grup Yönetimi'],
            ['roles',           'manage',        'Rol / Yetki Matrisi Yönetimi'],
            ['form_templates',  'manage',        'Form Tasarımı Yönetimi'],
            ['form_schedules',  'manage',        'Form Açma/Kapama Takvimi Yönetimi'],
            ['form_entries',    'view_own',      'Sadece Kendi Verisini Görüntüleme'],
            ['form_entries',    'view_group',    'Grup Verisini Görüntüleme'],
            ['form_entries',    'view_all',      'Tüm Verileri Görüntüleme'],
            ['form_entries',    'create',        'Veri Girişi Yapma'],
            ['form_entries',    'edit_own',      'Kendi Verisini Düzenleme'],
            ['form_entries',    'edit_group',    'Grup Verisini Düzenleme'],
            ['form_entries',    'approve',       'Veri Onaylama/Reddetme'],
            ['exports',         'manage',        'Excel Export Alma'],
            ['logs',            'view',          'Sistem Loglarını Görüntüleme'],
        ];

        foreach ($matrix as [$module, $action, $name]) {
            Permission::updateOrCreate(
                ['slug' => "{$module}.{$action}"],
                ['module' => $module, 'action' => $action, 'name' => $name]
            );
        }
    }
}
```

**`database/seeders/RolePermissionSeeder.php`**
```php
<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\Permission;
use Illuminate\Database\Seeder;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        $map = [
            'sistem_yoneticisi' => Permission::query()->pluck('slug')->all(), // hepsi

            'yonetim' => [
                'form_entries.view_all',
                'logs.view',
            ],

            'veri_giris_yoneticisi' => [
                'form_entries.view_own',
                'form_entries.view_group',
                'form_entries.create',
                'form_entries.edit_own',
                'form_entries.edit_group',
                'form_entries.approve',
                'exports.manage',
            ],

            'veri_girisi' => [
                'form_entries.view_own',
                'form_entries.create',
                'form_entries.edit_own',
            ],
        ];

        foreach ($map as $roleSlug => $permissionSlugs) {
            $role = Role::where('slug', $roleSlug)->firstOrFail();
            $permissionIds = Permission::whereIn('slug', $permissionSlugs)->pluck('id');
            $role->permissions()->sync($permissionIds);
        }
    }
}
```

**`database/seeders/GroupSeeder.php`**
```php
<?php

namespace Database\Seeders;

use App\Models\Group;
use Illuminate\Database\Seeder;

class GroupSeeder extends Seeder
{
    public function run(): void
    {
        // Örnek: 2 grup. n grup istenirse buraya satır eklemek yeterli, kod değişmez.
        Group::updateOrCreate(['name' => 'Grup A'], ['description' => 'Örnek veri giriş grubu A', 'is_active' => true]);
        Group::updateOrCreate(['name' => 'Grup B'], ['description' => 'Örnek veri giriş grubu B', 'is_active' => true]);
    }
}
```

**`database/seeders/UserSeeder.php`**
```php
<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Role;
use App\Models\Group;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $roleAdmin   = Role::where('slug', 'sistem_yoneticisi')->firstOrFail();
        $roleYonetim = Role::where('slug', 'yonetim')->firstOrFail();
        $roleManager = Role::where('slug', 'veri_giris_yoneticisi')->firstOrFail();
        $roleOperator= Role::where('slug', 'veri_girisi')->firstOrFail();

        $groupA = Group::where('name', 'Grup A')->firstOrFail();
        $groupB = Group::where('name', 'Grup B')->firstOrFail();

        // 1) Sistem Yöneticisi
        $admin = User::updateOrCreate(
            ['email' => 'admin@example.com'],
            ['name' => 'Sistem Yöneticisi', 'password' => Hash::make('password'), 'role_id' => $roleAdmin->id, 'is_active' => true]
        );

        // 2) Yönetim (read only)
        User::updateOrCreate(
            ['email' => 'yonetim@example.com'],
            ['name' => 'Yönetim', 'password' => Hash::make('password'), 'role_id' => $roleYonetim->id, 'is_active' => true]
        );

        // 3) Grup Yöneticileri (n olabilir; örnek: 2 tane, her biri kendi grubunu yönetiyor)
        $managerA = User::updateOrCreate(
            ['email' => 'yonetici.a@example.com'],
            ['name' => 'Grup A Yöneticisi', 'password' => Hash::make('password'), 'role_id' => $roleManager->id, 'is_active' => true]
        );
        $managerB = User::updateOrCreate(
            ['email' => 'yonetici.b@example.com'],
            ['name' => 'Grup B Yöneticisi', 'password' => Hash::make('password'), 'role_id' => $roleManager->id, 'is_active' => true]
        );

        $managerA->managedGroups()->sync([$groupA->id]);
        $managerB->managedGroups()->sync([$groupB->id]);

        // 4) Operatörler (n olabilir; örnek: her gruba 2 operatör)
        foreach (range(1, 2) as $i) {
            User::updateOrCreate(
                ['email' => "operator.a{$i}@example.com"],
                ['name' => "Grup A Operatör {$i}", 'password' => Hash::make('password'),
                 'role_id' => $roleOperator->id, 'group_id' => $groupA->id, 'is_active' => true]
            );

            User::updateOrCreate(
                ['email' => "operator.b{$i}@example.com"],
                ['name' => "Grup B Operatör {$i}", 'password' => Hash::make('password'),
                 'role_id' => $roleOperator->id, 'group_id' => $groupB->id, 'is_active' => true]
            );
        }
    }
}
```

**`database/seeders/DatabaseSeeder.php`**
```php
<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Sıra önemli: rol/yetki önce, sonra grup, sonra kullanıcı
        $this->call([
            RoleSeeder::class,
            PermissionSeeder::class,
            RolePermissionSeeder::class,
            GroupSeeder::class,
            UserSeeder::class,
        ]);
    }
}
```

> `n grup / n yönetici / n operatör` senaryosunda kod hiç değişmez — sadece `GroupSeeder` ve `UserSeeder` içindeki dizilere satır eklenir ya da gerçek ortamda admin panelinden CRUD ile eklenir.

---

## 8. Uygulama Sırası (İstediğin STEP BY STEP)

1. `roles`, `permissions`, `role_permission`, `user_permission` migration + model
2. `groups`, `group_manager`, `users` (role_id/group_id ekleme) migration + model
3. Yukarıdaki 4 seeder ile temel veri
4. Middleware: `CheckPermission` (route'larda `->middleware('permission:form_entries.view_all')`)
5. `form_templates`, `form_fields` migration + model + admin CRUD (form tasarımcı ekranı)
6. `form_entries`, `form_entry_rows`, `form_entry_values` migration + model + `FormEntryService`
7. `form_access_schedules` migration + `FormAccessService` (açma/kapama/kilitleme mantığı)
8. `activity_logs` migration + `ActivityLogger` servisi (grup yöneticisi işlemlerine entegre)
9. `form_export_templates` migration + `ExportService` (PhpSpreadsheet şablon doldurma)
10. View katmanı: tüm linkler `asset()` ile, tüm buton/menü görünürlüğü `@can()` ile

---

## 9. Sırada Ne Var?

Onayını istediğim noktalar:
1. Bölüm 0'daki 3 varsayımı onaylıyor musun, yoksa değiştirelim mi?
2. İlk adım olarak **Rol/Yetki/Grup/Kullanıcı katmanını** (madde 1-4) migration+model+seeder olarak tam kod halinde üretmemi ister misin, yoksa direkt tüm sistemi mi istiyorsun?

Kod üretimine, onayladığın kapsamdan başlayacağım (şişirmeden, adım adım).
