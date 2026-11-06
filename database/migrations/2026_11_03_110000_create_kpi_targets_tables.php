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
        // 1. Hedef Tanımları Tablosu
        Schema::create('kpi_targets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('form_template_id')->constrained('form_templates')->cascadeOnDelete();
            $table->foreignId('target_field_id')->constrained('form_fields')->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->smallInteger('target_year');
            $table->enum('assignee_type', ['user', 'group', 'manager'])->default('user');
            $table->unsignedBigInteger('assignee_id');
            $table->decimal('target_value', 15, 2);
            $table->string('unit', 50)->default('Ton');
            $table->json('criteria_json')->nullable(); // Filtre kriterleri: {"field_5": "PREFORM", "field_8": "SARI"}
            $table->foreignId('parent_target_id')->nullable()->constrained('kpi_targets')->nullOnDelete();
            $table->enum('status', ['active', 'paused', 'completed', 'cancelled'])->default('active');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['target_year', 'status']);
            $table->index(['assignee_type', 'assignee_id']);
        });

        // 2. Hedef Aylık Dağılım Tablosu (12 Aylık Dönem Hedefleri)
        Schema::create('kpi_target_periods', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kpi_target_id')->constrained('kpi_targets')->cascadeOnDelete();
            $table->smallInteger('period_year');
            $table->unsignedTinyInteger('period_month'); // 1 - 12
            $table->decimal('period_target_value', 15, 2)->default(0);
            $table->timestamps();

            $table->unique(['kpi_target_id', 'period_year', 'period_month'], 'kpi_period_unique');
        });

        // 3. Hedef Gerçekleşme ve Performans Kayıtları Tablosu (Cache)
        Schema::create('kpi_target_realizations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kpi_target_id')->constrained('kpi_targets')->cascadeOnDelete();
            $table->smallInteger('realization_year');
            $table->unsignedTinyInteger('realization_month'); // 1 - 12 (0 ise yıllık toplam)
            $table->unsignedTinyInteger('realization_week')->nullable(); // 1 - 53 (haftalık izleme için)
            $table->decimal('actual_value', 15, 2)->default(0);
            $table->decimal('achievement_rate', 7, 2)->default(0); // Başarı oranı %
            $table->timestamp('calculated_at')->useCurrent();
            $table->timestamps();

            $table->index(['kpi_target_id', 'realization_year', 'realization_month'], 'kpi_realization_ym_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kpi_target_realizations');
        Schema::dropIfExists('kpi_target_periods');
        Schema::dropIfExists('kpi_targets');
    }
};

