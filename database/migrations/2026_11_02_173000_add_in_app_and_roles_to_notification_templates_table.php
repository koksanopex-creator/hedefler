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
        Schema::table('notification_templates', function (Blueprint $table) {
            $table->string('in_app_subject')->nullable()->after('subject');
            $table->text('in_app_body')->nullable()->after('body');
            $table->json('role_templates')->nullable()->after('additional_emails');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('notification_templates', function (Blueprint $table) {
            $table->dropColumn(['in_app_subject', 'in_app_body', 'role_templates']);
        });
    }
};

