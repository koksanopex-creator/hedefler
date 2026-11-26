<?php

namespace Tests\Feature;

use App\Models\FormFieldM;
use App\Models\FormEntryM;
use App\Models\FormScheduleM;
use App\Models\FormTemplateM;
use App\Models\RoleM;
use App\Models\UserM;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class FormEntryPartitionExclusionTest extends TestCase
{
    private function createMinimalSqliteSchema(): void
    {
        if (Schema::getConnection()->getDriverName() !== 'sqlite') {
            return;
        }

        Schema::disableForeignKeyConstraints();
        Schema::dropAllTables();

        Schema::create('roles', function ($table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->timestamps();
        });

        Schema::create('users', function ($table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->foreignId('role_id')->nullable();
            $table->foreignId('group_id')->nullable();
            $table->boolean('is_active')->default(true);
            $table->string('approval_status')->default('approved');
            $table->json('preferences')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('form_fields', function ($table) {
            $table->id();
            $table->foreignId('form_template_id');
            $table->string('label');
            $table->string('name')->nullable();
            $table->string('type')->default('text');
            $table->unsignedInteger('order')->default(0);
            $table->boolean('is_required')->default(false);
            $table->boolean('is_calculated')->default(false);
            $table->boolean('is_disabled')->default(false);
            $table->json('options')->nullable();
            $table->unsignedBigInteger('master_data_type_id')->nullable();
            $table->timestamps();
        });

        Schema::create('form_templates', function ($table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->foreignId('created_by')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('allow_partition_entry')->default(true);
            $table->foreignId('partition_field_id')->nullable();
            $table->boolean('show_row_totals')->default(false);
            $table->timestamps();
        });

        Schema::create('form_entries', function ($table) {
            $table->id();
            $table->foreignId('form_template_id');
            $table->foreignId('user_id');
            $table->foreignId('parent_id')->nullable();
            $table->foreignId('partition_field_id')->nullable();
            $table->string('partition_value')->nullable();
            $table->string('title')->nullable();
            $table->boolean('is_consolidated')->default(false);
            $table->date('entry_date');
            $table->string('status')->default('draft');
            $table->string('rejection_reason')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('approved_by')->nullable();
            $table->json('template_snapshot')->nullable();
            $table->timestamps();
        });

        Schema::create('form_entry_rows', function ($table) {
            $table->id();
            $table->foreignId('form_entry_id');
            $table->unsignedInteger('row_order')->default(0);
            $table->timestamps();
        });

        Schema::create('form_entry_values', function ($table) {
            $table->id();
            $table->foreignId('form_entry_row_id');
            $table->foreignId('form_field_id');
            $table->text('value')->nullable();
            $table->timestamps();
        });

        Schema::create('form_entry_versions', function ($table) {
            $table->id();
            $table->foreignId('form_entry_id');
            $table->unsignedInteger('version')->default(1);
            $table->foreignId('created_by')->nullable();
            $table->json('snapshot_data')->nullable();
            $table->string('change_summary')->nullable();
            $table->timestamps();
        });

        Schema::create('form_schedules', function ($table) {
            $table->id();
            $table->foreignId('form_template_id');
            $table->string('target_type')->default('all');
            $table->unsignedBigInteger('target_id')->nullable();
            $table->boolean('is_indefinite')->default(true);
            $table->dateTime('start_time')->nullable();
            $table->dateTime('end_time')->nullable();
            $table->boolean('is_locked')->default(false);
            $table->timestamps();
        });

        Schema::create('master_data_types', function ($table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->foreignId('parent_id')->nullable();
            $table->timestamps();
        });

        Schema::create('master_data_items', function ($table) {
            $table->id();
            $table->foreignId('type_id');
            $table->string('name');
            $table->foreignId('parent_id')->nullable();
            $table->timestamps();
        });

        Schema::create('notifications', function ($table) {
            $table->uuid('id')->primary();
            $table->string('type');
            $table->morphs('notifiable');
            $table->text('data');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });

        Schema::create('permissions', function ($table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('group')->nullable();
            $table->timestamps();
        });

        Schema::create('role_permission', function ($table) {
            $table->id();
            $table->foreignId('role_id');
            $table->foreignId('permission_id');
            $table->timestamps();
        });

        Schema::create('user_permission', function ($table) {
            $table->id();
            $table->foreignId('user_id');
            $table->foreignId('permission_id');
            $table->boolean('allowed')->default(true);
            $table->timestamps();
        });

        Schema::create('activity_logs', function ($table) {
            $table->id();
            $table->foreignId('user_id')->nullable();
            $table->string('action');
            $table->text('description')->nullable();
            $table->string('model_type')->nullable();
            $table->unsignedBigInteger('model_id')->nullable();
            $table->string('ip_address')->nullable();
            $table->timestamps();
        });

        Schema::enableForeignKeyConstraints();
    }

    private function createTemplateAndUser(): array
    {
        $role = RoleM::create(['name' => 'Sistem Yöneticisi', 'slug' => 'sistem_yoneticisi']);
        $user = UserM::create([
            'name' => 'Test User',
            'email' => 'user@koksan.com',
            'password' => bcrypt('secret'),
            'role_id' => $role->id,
            'is_active' => true,
            'approval_status' => 'approved',
        ]);

        $template = FormTemplateM::create([
            'name' => 'HEDEFLER ŞABLONU',
            'slug' => 'hedefler-sablonu',
            'allow_partition_entry' => true,
            'is_active' => true,
        ]);

        $partitionField = FormFieldM::create([
            'form_template_id' => $template->id,
            'label' => 'İŞYERİ',
            'name' => 'isyeri',
            'type' => 'select',
            'options' => ['KAPAK', 'PREFORM', 'LEVHA'],
        ]);

        $template->update(['partition_field_id' => $partitionField->id]);

        FormScheduleM::create([
            'form_template_id' => $template->id,
            'target_type' => 'all',
            'is_indefinite' => true,
            'is_locked' => false,
        ]);

        return [$user, $template, $partitionField];
    }

    public function test_user_with_active_full_entry_cannot_start_partial_entry(): void
    {
        $this->createMinimalSqliteSchema();
        [$user, $template, $partitionField] = $this->createTemplateAndUser();

        // Tek parça taslak oluştur
        FormEntryM::create([
            'form_template_id' => $template->id,
            'user_id' => $user->id,
            'partition_value' => null,
            'is_consolidated' => false,
            'entry_date' => now()->toDateString(),
            'status' => 'draft',
        ]);

        // Parçalı giriş açmaya çalıştığında engellenmeli ve create sayfasına yönlendirilmeli
        $response = $this->actingAs($user)
            ->get(route('form-entries.create', ['formTemplate' => $template, 'partition_value' => 'KAPAK']));

        $response->assertRedirect(route('form-entries.create', $template));
        $response->assertSessionHas('warning');
    }

    public function test_user_with_active_full_entry_cannot_store_partial_entry(): void
    {
        $this->createMinimalSqliteSchema();
        [$user, $template, $partitionField] = $this->createTemplateAndUser();

        // Tek parça onay bekleyen form oluştur
        FormEntryM::create([
            'form_template_id' => $template->id,
            'user_id' => $user->id,
            'partition_value' => null,
            'is_consolidated' => false,
            'entry_date' => now()->toDateString(),
            'status' => 'submitted',
        ]);

        // Parçalı giriş kaydetmeye çalıştığında engellenmeli
        $response = $this->actingAs($user)
            ->post(route('form-entries.store', $template), [
                'entry_date' => now()->toDateString(),
                'partition_value' => 'KAPAK',
                'action' => 'draft',
                'rows' => [[$partitionField->id => 'KAPAK']],
            ]);

        $response->assertSessionHasErrors(['partition_value']);
    }

    public function test_user_with_active_partial_entry_cannot_start_full_entry(): void
    {
        $this->createMinimalSqliteSchema();
        [$user, $template, $partitionField] = $this->createTemplateAndUser();

        // Parçalı taslak oluştur
        FormEntryM::create([
            'form_template_id' => $template->id,
            'user_id' => $user->id,
            'partition_field_id' => $partitionField->id,
            'partition_value' => 'KAPAK',
            'is_consolidated' => false,
            'entry_date' => now()->toDateString(),
            'status' => 'draft',
        ]);

        // Tek parça form açmaya çalıştığında engellenmeli
        $response = $this->actingAs($user)
            ->get(route('form-entries.create', ['formTemplate' => $template, 'full_entry' => 1]));

        $response->assertRedirect(route('form-entries.create', $template));
        $response->assertSessionHas('warning');
    }

    public function test_user_with_active_partial_entry_cannot_store_full_entry(): void
    {
        $this->createMinimalSqliteSchema();
        [$user, $template, $partitionField] = $this->createTemplateAndUser();

        // Parçalı onay bekleyen kayıt oluştur
        FormEntryM::create([
            'form_template_id' => $template->id,
            'user_id' => $user->id,
            'partition_field_id' => $partitionField->id,
            'partition_value' => 'PREFORM',
            'is_consolidated' => false,
            'entry_date' => now()->toDateString(),
            'status' => 'submitted',
        ]);

        // Tek parça (partition_value olmadan) kaydetmeye çalıştığında engellenmeli
        $response = $this->actingAs($user)
            ->post(route('form-entries.store', $template), [
                'entry_date' => now()->toDateString(),
                'action' => 'draft',
                'rows' => [[$partitionField->id => 'PREFORM']],
            ]);

        $response->assertSessionHasErrors(['partition_value']);
    }

    public function test_user_without_entries_can_access_both_full_and_partial_creation(): void
    {
        $this->createMinimalSqliteSchema();
        [$user, $template, $partitionField] = $this->createTemplateAndUser();

        // 1. Henüz kayıt yokken tek parça giriş açılabilir
        $responseFull = $this->actingAs($user)
            ->get(route('form-entries.create', ['formTemplate' => $template, 'full_entry' => 1]));
        $responseFull->assertOk();
        $responseFull->assertViewIs('form_entries.create');

        // 2. Henüz kayıt yokken parçalı giriş açılabilir
        $responsePart = $this->actingAs($user)
            ->get(route('form-entries.create', ['formTemplate' => $template, 'partition_value' => 'KAPAK']));
        $responsePart->assertOk();
        $responsePart->assertViewIs('form_entries.create');
    }

    public function test_select_partition_view_shows_lock_badges_and_warnings(): void
    {
        $this->createMinimalSqliteSchema();
        [$user, $template, $partitionField] = $this->createTemplateAndUser();

        \Illuminate\Support\Facades\App::setLocale('tr');

        // 1. Hiçbir kayıt yokken her iki seçenek de açık
        $responseNone = $this->actingAs($user)
            ->withSession(['locale' => 'tr'])
            ->get(route('form-entries.create', $template));
        $responseNone->assertOk();
        $responseNone->assertSee('Tek Parça Giriş Başlat');

        // 2. Parçalı kayıt varken: Tek parça kilitli
        FormEntryM::create([
            'form_template_id' => $template->id,
            'user_id' => $user->id,
            'partition_field_id' => $partitionField->id,
            'partition_value' => 'KAPAK',
            'is_consolidated' => false,
            'entry_date' => now()->toDateString(),
            'status' => 'draft',
        ]);

        $responsePartial = $this->actingAs($user)
            ->withSession(['locale' => 'tr'])
            ->get(route('form-entries.create', $template));
        $responsePartial->assertOk();
        $responsePartial->assertSee('Parçalı Giriş Aktif - Kilitli');
        $responsePartial->assertSee('Tek Parça Giriş Kilitli');

        // 3. Tek parça kayıt varken: Parçalı kilitli
        FormEntryM::query()->delete();
        FormEntryM::create([
            'form_template_id' => $template->id,
            'user_id' => $user->id,
            'partition_value' => null,
            'is_consolidated' => false,
            'entry_date' => now()->toDateString(),
            'status' => 'draft',
        ]);

        $responseFull = $this->actingAs($user)
            ->withSession(['locale' => 'tr'])
            ->get(route('form-entries.create', $template));
        $responseFull->assertOk();
        $responseFull->assertSee('Tek Parça Giriş Tercih Edildi — Parçalı Giriş Kilitlendi');
        $responseFull->assertSee('Tek Parça Giriş Nedeniyle Kilitli');
        $responseFull->assertSee('Sil');
    }

    public function test_owner_can_delete_own_draft_entry(): void
    {
        $this->createMinimalSqliteSchema();
        [$adminUser, $template, $partitionField] = $this->createTemplateAndUser();

        // form_entries.delete yetkisi olmayan normal kullanıcı oluştur
        $regularRole = RoleM::create(['name' => 'Satış Temsilcisi', 'slug' => 'satis_temsilcisi']);
        $regularUser = UserM::create([
            'name' => 'Regular User',
            'email' => 'regular@koksan.com',
            'password' => bcrypt('secret'),
            'role_id' => $regularRole->id,
            'is_active' => true,
            'approval_status' => 'approved',
        ]);

        $draft = FormEntryM::create([
            'form_template_id' => $template->id,
            'user_id' => $regularUser->id,
            'partition_value' => 'KAPAK',
            'is_consolidated' => false,
            'entry_date' => now()->toDateString(),
            'status' => 'draft',
        ]);

        $submitted = FormEntryM::create([
            'form_template_id' => $template->id,
            'user_id' => $regularUser->id,
            'partition_value' => 'PREFORM',
            'is_consolidated' => false,
            'entry_date' => now()->toDateString(),
            'status' => 'submitted',
        ]);

        // Kendi taslağını silebilmeli
        $responseDraft = $this->actingAs($regularUser)
            ->delete(route('form-entries.destroy', $draft));
        $responseDraft->assertRedirect();
        $this->assertDatabaseMissing('form_entries', ['id' => $draft->id]);

        // Onaya gönderilmiş kendi kaydını yetkisi olmadan silememeli (403)
        $responseSubmitted = $this->actingAs($regularUser)
            ->delete(route('form-entries.destroy', $submitted));
        $responseSubmitted->assertForbidden();
        $this->assertDatabaseHas('form_entries', ['id' => $submitted->id]);
    }
}
