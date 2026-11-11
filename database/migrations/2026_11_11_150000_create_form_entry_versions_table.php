<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('form_entry_versions')) {
            Schema::create('form_entry_versions', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('form_entry_id')->index();
                $table->unsignedInteger('version_number');
                $table->string('status', 50)->default('draft');
                $table->string('action_type', 50)->default('draft_auto');
                $table->unsignedBigInteger('user_id')->index();
                $table->unsignedInteger('row_count')->default(0);
                $table->longText('rows_data'); // JSON: [{ "row_order": 0, "values": { "4": "val", ... } }]
                $table->text('summary')->nullable();
                $table->timestamps();

                $table->foreign('form_entry_id')->references('id')->on('form_entries')->onDelete('cascade');
                $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
                $table->unique(['form_entry_id', 'version_number'], 'entry_version_unique');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('form_entry_versions');
    }
};

