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
        Schema::table('form_fields', function (Blueprint $table) {
            $table->foreignId('calc_condition_field_id')->nullable()->after('calculation_formula')->constrained('form_fields')->nullOnDelete();
            $table->text('calc_condition_value')->nullable()->after('calc_condition_field_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('form_fields', function (Blueprint $table) {
            $table->dropForeign(['calc_condition_field_id']);
            $table->dropColumn(['calc_condition_field_id', 'calc_condition_value']);
        });
    }
};
