<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notification_matrix', function (Blueprint $table) {
            $table->id();
            $table->foreignId('notification_event_id')->constrained('notification_events')->cascadeOnDelete();
            $table->string('target_type'); // e.g., 'role:sistem_yoneticisi', 'special:group_manager', 'special:creator'
            $table->boolean('send_email')->default(false);
            $table->boolean('send_in_app')->default(true);
            $table->timestamps();
            
            $table->unique(['notification_event_id', 'target_type'], 'matrix_event_target_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_matrix');
    }
};
