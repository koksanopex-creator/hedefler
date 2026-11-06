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
            $table->boolean('filter_by_user_group')->default(false)->after('calc_condition_value');
        });

        Schema::table('master_data_items', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->after('parent_id')->constrained('users')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('master_data_items', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropColumn('user_id');
        });

        Schema::table('form_fields', function (Blueprint $table) {
            $table->dropColumn('filter_by_user_group');
        });
    }
};
