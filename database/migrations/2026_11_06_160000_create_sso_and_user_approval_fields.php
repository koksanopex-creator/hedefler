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
        // 1. users tablosuna SSO ve Onay/Red alanları
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedBigInteger('central_user_id')->nullable()->after('id')->index();
            $table->string('central_department')->nullable()->after('email');
            $table->string('approval_status')->default('approved')->after('is_active'); // approved, pending_role, pending_application, rejected
            $table->string('rejection_type')->nullable()->after('approval_status'); // correctable, permanent
            $table->text('rejection_reason')->nullable()->after('rejection_type');
            $table->timestamp('rejected_at')->nullable()->after('rejection_reason');
        });

        // 2. sso_applications tablosu
        Schema::create('sso_applications', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->unsignedBigInteger('central_user_id')->nullable()->index();
            $table->string('name');
            $table->string('email')->index();
            $table->string('department')->nullable();
            $table->text('application_note')->nullable();
            $table->string('status')->default('pending'); // pending, approved, rejected
            $table->string('rejection_type')->nullable(); // correctable, permanent
            $table->text('rejection_reason')->nullable();
            $table->unsignedBigInteger('reviewed_by')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sso_applications');

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'central_user_id',
                'central_department',
                'approval_status',
                'rejection_type',
                'rejection_reason',
                'rejected_at'
            ]);
        });
    }
};

