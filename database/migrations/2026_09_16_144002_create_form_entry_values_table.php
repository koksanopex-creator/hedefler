<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('form_entry_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('form_entry_row_id')->constrained('form_entry_rows')->cascadeOnDelete();
            $table->foreignId('form_field_id')->constrained('form_fields')->restrictOnDelete();
            $table->text('value')->nullable(); // Tüm değerler text olarak tutulur, uygulama tarafında field type'ına göre dönüştürülür
            $table->timestamps();

            $table->unique(['form_entry_row_id', 'form_field_id'], 'row_field_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('form_entry_values');
    }
};
