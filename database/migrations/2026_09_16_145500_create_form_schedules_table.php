<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('form_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('form_template_id')->constrained('form_templates')->cascadeOnDelete();
            
            // Hedef kitle: all, group, user
            $table->enum('target_type', ['all', 'group', 'user'])->default('all');
            $table->unsignedBigInteger('target_id')->nullable(); // group_id veya user_id
            
            // Süreli mi süresiz mi?
            $table->boolean('is_indefinite')->default(false);
            $table->dateTime('start_time')->nullable();
            $table->dateTime('end_time')->nullable();
            
            // Durum: Açık veya Kilitli
            $table->boolean('is_locked')->default(false);
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('form_schedules');
    }
};
