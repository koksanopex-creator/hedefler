<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement("ALTER TABLE form_fields MODIFY COLUMN type ENUM('text', 'number', 'textarea', 'select', 'date', 'auto_user', 'master_data', 'user_list') NOT NULL");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("ALTER TABLE form_fields MODIFY COLUMN type ENUM('text', 'number', 'textarea', 'select', 'date', 'auto_user', 'master_data') NOT NULL");
    }
};

