<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('form_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('form_template_id')->constrained('form_templates')->restrictOnDelete();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->date('entry_date');
            $table->enum('status', ['draft', 'submitted', 'approved', 'rejected'])->default('draft');
            $table->text('rejection_reason')->nullable();
            $table->timestamps();

            // Aynı kullanıcı aynı şablona aynı gün için sadece 1 kayıt atabilsin (içinde n tane satır olacak)
            $table->unique(['form_template_id', 'user_id', 'entry_date'], 'form_user_date_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('form_entries');
    }
};
