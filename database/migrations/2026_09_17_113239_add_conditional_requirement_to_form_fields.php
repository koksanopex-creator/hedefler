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
            $table->unsignedBigInteger('condition_field_id')->nullable()->after('is_summable');
            $table->string('condition_value')->nullable()->after('condition_field_id');
            
            $table->foreign('condition_field_id')->references('id')->on('form_fields')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('form_fields', function (Blueprint $table) {
            $table->dropForeign(['condition_field_id']);
            $table->dropColumn(['condition_field_id', 'condition_value']);
        });
    }
};
