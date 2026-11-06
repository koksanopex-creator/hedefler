<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notification_logs', function (Blueprint $table) {
            $table->id();
            $table->string('channel', 20)->index(); // 'mail', 'in_app'
            $table->string('event_slug', 50)->index(); // 'form.submitted', 'form.approved', etc.
            $table->string('event_name', 100)->nullable(); // 'Yeni Form Gönderildi'
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('recipient_name')->nullable();
            $table->string('recipient_email')->nullable();
            $table->string('recipient_role')->nullable()->index();
            $table->string('subject');
            $table->longText('body');
            $table->string('action_url', 500)->nullable();
            $table->string('status', 20)->default('sent'); // 'sent', 'failed'
            $table->text('error_message')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('sent_at')->useCurrent()->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_logs');
    }
};

