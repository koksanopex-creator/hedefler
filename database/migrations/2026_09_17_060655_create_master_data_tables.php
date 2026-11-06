<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('master_data_types', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->foreignId('parent_id')->nullable()->constrained('master_data_types')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('master_data_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('type_id')->constrained('master_data_types')->cascadeOnDelete();
            $table->string('name');
            $table->foreignId('parent_id')->nullable()->constrained('master_data_items')->nullOnDelete();
            $table->timestamps();
        });

        // form_fields tablosuna master_data_type_id ekle
        Schema::table('form_fields', function (Blueprint $table) {
            $table->foreignId('master_data_type_id')->nullable()->constrained('master_data_types')->nullOnDelete()->after('value_type');
        });

        // type enum değerini güncelle (DB raw işlemi ile, çünkü SQLite enum desteklemez ama biz MySQL kullanıyoruz)
        DB::statement("ALTER TABLE form_fields MODIFY COLUMN type ENUM('text', 'number', 'textarea', 'select', 'date', 'auto_user', 'master_data') NOT NULL");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('master_data_tables');
    }
};
