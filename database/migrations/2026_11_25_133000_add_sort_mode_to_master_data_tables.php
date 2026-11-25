<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Master Data kategorilerine sıralama modu (alphabetical / custom)
     * ve öğelerine özel sıra numarası (sort_order) ekler.
     */
    public function up(): void
    {
        Schema::table('master_data_types', function (Blueprint $table) {
            $table->string('sort_mode', 20)->default('alphabetical')->after('parent_id');
        });

        Schema::table('master_data_items', function (Blueprint $table) {
            $table->integer('sort_order')->default(0)->after('parent_id');
            $table->index(['type_id', 'sort_order'], 'master_data_items_type_sort_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('master_data_items', function (Blueprint $table) {
            $table->dropIndex('master_data_items_type_sort_idx');
            $table->dropColumn('sort_order');
        });

        Schema::table('master_data_types', function (Blueprint $table) {
            $table->dropColumn('sort_mode');
        });
    }
};
