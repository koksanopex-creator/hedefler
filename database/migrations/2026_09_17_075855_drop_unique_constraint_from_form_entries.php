<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('form_entries', function (Blueprint $table) {
            // First add a regular index so the foreign key doesn't block dropping the unique index
            $table->index('form_template_id');
            $table->dropUnique('form_user_date_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('form_entries', function (Blueprint $table) {
            $table->unique(['form_template_id', 'user_id', 'entry_date'], 'form_user_date_unique');
        });
    }
};
