<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('form_fields', function (Blueprint $table) {
            $table->id();
            $table->foreignId('form_template_id')->constrained('form_templates')->cascadeOnDelete();
            $table->string('label');
            $table->string('field_key'); // slug
            $table->enum('type', ['text','number','textarea','select','date','auto_user']);
            $table->enum('value_type', ['nicel','nitel']); // nicel=sayısal, nitel=metinsel/kategorik
            $table->json('options')->nullable(); // dropdown seçenekleri
            $table->boolean('is_required')->default(false);
            $table->boolean('is_summable')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['form_template_id', 'field_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('form_fields');
    }
};
