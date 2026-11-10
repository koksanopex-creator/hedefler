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
        Schema::table('form_templates', function (Blueprint $table) {
            $table->boolean('allow_partition_entry')->default(false)->after('show_row_totals');
            $table->unsignedBigInteger('partition_field_id')->nullable()->after('allow_partition_entry');

            $table->foreign('partition_field_id')
                ->references('id')
                ->on('form_fields')
                ->onDelete('set null');
        });

        Schema::table('form_entries', function (Blueprint $table) {
            $table->unsignedBigInteger('parent_id')->nullable()->after('form_template_id');
            $table->unsignedBigInteger('partition_field_id')->nullable()->after('parent_id');
            $table->string('partition_value')->nullable()->after('partition_field_id');
            $table->string('title')->nullable()->after('partition_value');
            $table->boolean('is_consolidated')->default(false)->after('title');

            $table->foreign('parent_id')
                ->references('id')
                ->on('form_entries')
                ->onDelete('set null');

            $table->foreign('partition_field_id')
                ->references('id')
                ->on('form_fields')
                ->onDelete('set null');
                
            $table->index(['user_id', 'form_template_id', 'partition_value'], 'idx_user_template_partition');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('form_entries', function (Blueprint $table) {
            $table->dropForeign(['parent_id']);
            $table->dropForeign(['partition_field_id']);
            $table->dropIndex('idx_user_template_partition');
            $table->dropColumn([
                'parent_id',
                'partition_field_id',
                'partition_value',
                'title',
                'is_consolidated'
            ]);
        });

        Schema::table('form_templates', function (Blueprint $table) {
            $table->dropForeign(['partition_field_id']);
            $table->dropColumn([
                'allow_partition_entry',
                'partition_field_id'
            ]);
        });
    }
};

