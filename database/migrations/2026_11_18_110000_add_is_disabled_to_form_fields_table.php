<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('form_fields', function (Blueprint $table) {
            if (!Schema::hasColumn('form_fields', 'is_disabled')) {
                $table->boolean('is_disabled')->default(false)->after('is_required');
            }
        });
    }

    public function down(): void
    {
        Schema::table('form_fields', function (Blueprint $table) {
            if (Schema::hasColumn('form_fields', 'is_disabled')) {
                $table->dropColumn('is_disabled');
            }
        });
    }
};
