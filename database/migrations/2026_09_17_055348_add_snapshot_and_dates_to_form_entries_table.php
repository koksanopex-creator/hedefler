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
            $table->json('template_snapshot')->nullable()->after('rejection_reason');
            $table->timestamp('submitted_at')->nullable()->after('template_snapshot');
            $table->timestamp('approved_at')->nullable()->after('submitted_at');
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete()->after('approved_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('form_entries', function (Blueprint $table) {
            $table->dropForeign(['approved_by']);
            $table->dropColumn(['template_snapshot', 'submitted_at', 'approved_at', 'approved_by']);
        });
    }
};
