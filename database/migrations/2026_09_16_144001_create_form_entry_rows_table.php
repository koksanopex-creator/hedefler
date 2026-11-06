<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('form_entry_rows', function (Blueprint $table) {
            $table->id();
            $table->foreignId('form_entry_id')->constrained('form_entries')->cascadeOnDelete();
            $table->unsignedInteger('row_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('form_entry_rows');
    }
};
