<?php

namespace Tests\Feature;

use App\Models\FormEntryM;
use App\Models\FormTemplateM;
use App\Models\RoleM;
use App\Models\UserM;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class RevertReviewTest extends TestCase
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
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('form_templates', function ($table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->foreignId('created_by')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('allow_partition_entry')->default(false);
            $table->timestamps();
        });

        Schema::create('form_entries', function ($table) {
            $table->id();
            $table->foreignId('form_template_id');
            $table->foreignId('user_id');
            $table->date('entry_date');
            $table->string('status')->default('draft');
            $table->string('rejection_reason')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('approved_by')->nullable();
            $table->boolean('is_consolidated')->default(false);
            $table->foreignId('parent_id')->nullable();
            $table->string('partition_key')->nullable();
            $table->string('partition_value')->nullable();
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

        Schema::create('groups', function ($table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('group_user', function ($table) {
            $table->id();
            $table->foreignId('group_id');
            $table->foreignId('user_id');
            $table->timestamps();
        });

        Schema::create('user_managed_groups', function ($table) {
            $table->id();
            $table->foreignId('user_id');
            $table->foreignId('group_id');
            $table->timestamps();
        });

        Schema::create('permissions', function ($table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->timestamps();
        });

        Schema::create('role_permission', function ($table) {
            $table->id();
            $table->foreignId('permission_id');
            $table->foreignId('role_id');
            $table->timestamps();
        });

        Schema::create('user_permission', function ($table) {
            $table->id();
            $table->foreignId('permission_id');
            $table->foreignId('user_id');
            $table->boolean('allowed')->default(true);
            $table->timestamps();
        });

        Schema::enableForeignKeyConstraints();
    }

    public function test_superadmin_can_revert_approved_form()
    {
        $this->createMinimalSqliteSchema();

        $adminRole = RoleM::create([
            'name' => 'Sistem Yöneticisi',
            'slug' => 'sistem_yoneticisi',
        ]);

        $admin = UserM::create([
            'name' => 'CELAL KARAMAN',
            'email' => 'celal-test@koksan.com',
            'password' => bcrypt('password'),
            'role_id' => $adminRole->id,
            'is_active' => true,
            'approval_status' => 'approved',
        ]);

        $owner = UserM::create([
            'name' => 'SERHAT TÜMER',
            'email' => 'serhat-test@koksan.com',
            'password' => bcrypt('password'),
            'role_id' => $adminRole->id,
            'is_active' => true,
            'approval_status' => 'approved',
        ]);

        $approver = UserM::create([
            'name' => 'AHMET GÜLLÜCE',
            'email' => 'ahmet-test@koksan.com',
            'password' => bcrypt('password'),
            'role_id' => $adminRole->id,
            'is_active' => true,
            'approval_status' => 'approved',
        ]);

        $template = FormTemplateM::create([
            'name' => 'HEDEFLER ŞABLONU',
            'slug' => 'hedefler-test-revert',
            'created_by' => $owner->id,
            'is_active' => true,
        ]);

        $entry = FormEntryM::create([
            'form_template_id' => $template->id,
            'user_id' => $owner->id,
            'entry_date' => now()->toDateString(),
            'status' => 'approved',
            'submitted_at' => now()->subDay(),
            'approved_at' => now(),
            'approved_by' => $approver->id,
        ]);

        $response = $this->actingAs($admin)
            ->post(route('form-entries.revert-review', $entry));

        $response->assertRedirect(route('form-entries.show', $entry));
        $response->assertSessionHas('success');

        $entry->refresh();
        $this->assertEquals('submitted', $entry->status);
        $this->assertNull($entry->approved_at);
        $this->assertNull($entry->approved_by);
        $this->assertNotNull($entry->submitted_at);
    }

    public function test_unauthorized_user_cannot_revert_form()
    {
        $this->createMinimalSqliteSchema();

        $role = RoleM::create([
            'name' => 'Kullanıcı',
            'slug' => 'kullanici',
        ]);

        // Create ID 1 as placeholder
        UserM::create([
            'name' => 'SUPERADMIN #1',
            'email' => 'admin1@koksan.com',
            'password' => bcrypt('password'),
            'role_id' => $role->id,
            'is_active' => true,
            'approval_status' => 'approved',
        ]);

        $user1 = UserM::create([
            'name' => 'NORMAL USER 1',
            'email' => 'user1@koksan.com',
            'password' => bcrypt('password'),
            'role_id' => $role->id,
            'is_active' => true,
            'approval_status' => 'approved',
        ]);

        $user2 = UserM::create([
            'name' => 'NORMAL USER 2',
            'email' => 'user2@koksan.com',
            'password' => bcrypt('password'),
            'role_id' => $role->id,
            'is_active' => true,
            'approval_status' => 'approved',
        ]);

        $template = FormTemplateM::create([
            'name' => 'HEDEFLER ŞABLONU',
            'slug' => 'hedefler-test-revert-2',
            'created_by' => $user2->id,
            'is_active' => true,
        ]);

        $entry = FormEntryM::create([
            'form_template_id' => $template->id,
            'user_id' => $user2->id,
            'entry_date' => now()->toDateString(),
            'status' => 'approved',
            'submitted_at' => now()->subDay(),
            'approved_at' => now(),
            'approved_by' => $user2->id,
        ]);

        $response = $this->actingAs($user1)
            ->post(route('form-entries.revert-review', $entry));

        $response->assertStatus(403);
    }
}
