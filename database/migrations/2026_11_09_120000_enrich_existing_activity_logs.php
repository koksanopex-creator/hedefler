<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use App\Models\ActivityLogM;
use App\Models\FormEntryM;

return new class extends Migration
{
    public function up(): void
    {
        $logs = ActivityLogM::where('model_type', 'App\\Models\\FormEntryM')->get();
        foreach ($logs as $log) {
            $entry = FormEntryM::with(['template', 'user'])->find($log->model_id);
            if ($entry) {
                $tName = $entry->template?->name ?? 'Dinamik Form';
                $eDate = $entry->entry_date ? $entry->entry_date->format('d.m.Y') : '-';
                $oName = $entry->user?->name ?? 'Kullanıcı';
                $tag = "Form: [{$tName}] (Giriş Tarihi: {$eDate}, Sahibi: {$oName}, Form ID: #{$entry->id})";

                if (!str_contains($log->description, 'Form: [')) {
                    $log->description = trim($log->description . " " . $tag);
                    $log->save();
                }
            }
        }
    }

    public function down(): void
    {
        // No-op
    }
};
