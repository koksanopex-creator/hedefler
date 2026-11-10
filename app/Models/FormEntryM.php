<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FormEntryM extends Model
{
    protected $table = 'form_entries';
    protected $fillable = [
        'form_template_id', 'parent_id', 'partition_field_id', 'partition_value', 'title', 'is_consolidated',
        'user_id', 'entry_date', 'status', 'rejection_reason',
        'template_snapshot', 'submitted_at', 'approved_at', 'approved_by'
    ];

    protected function casts(): array
    {
        return [
            'entry_date' => 'date',
            'submitted_at' => 'datetime',
            'approved_at' => 'datetime',
            'template_snapshot' => 'array',
            'is_consolidated' => 'boolean',
            'parent_id' => 'integer',
            'partition_field_id' => 'integer',
        ];
    }

    public function parent()
    {
        return $this->belongsTo(FormEntryM::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(FormEntryM::class, 'parent_id')->orderBy('id', 'asc');
    }

    public function partitionField()
    {
        return $this->belongsTo(FormFieldM::class, 'partition_field_id');
    }

    public function isConsolidated(): bool
    {
        return (bool)$this->is_consolidated;
    }

    public function isPartition(): bool
    {
        return !empty($this->partition_value);
    }

    public function getFormattedTitleAttribute(): string
    {
        if (!empty($this->title)) {
            return $this->title;
        }

        $templateName = $this->template?->name ?? 'Form';
        if ($this->isConsolidated()) {
            return "Birleştirilmiş Konsolide Kayıt - {$templateName} (#{$this->id})";
        }

        if ($this->isPartition()) {
            return "{$this->partition_value} - {$templateName} (#{$this->id})";
        }

        return "{$templateName} (#{$this->id})";
    }

    public function template()
    {
        return $this->belongsTo(FormTemplateM::class, 'form_template_id');
    }

    public function user()
    {
        return $this->belongsTo(UserM::class, 'user_id');
    }

    public function approver()
    {
        return $this->belongsTo(UserM::class, 'approved_by');
    }

    public function rows()
    {
        return $this->hasMany(FormEntryRowM::class, 'form_entry_id')->orderBy('row_order');
    }

    public function revisionRequests()
    {
        return $this->hasMany(FormRevisionRequestM::class, 'form_entry_id')->orderBy('created_at', 'desc');
    }

    public function activeRevisionRequest()
    {
        return $this->hasOne(FormRevisionRequestM::class, 'form_entry_id')->latestOfMany();
    }

    public function pendingRevisionRequest()
    {
        return $this->hasOne(FormRevisionRequestM::class, 'form_entry_id')->where('status', 'pending');
    }

    public function hasPendingRevision(): bool
    {
        if ($this->relationLoaded('revisionRequests')) {
            return (bool)$this->revisionRequests->firstWhere('status', 'pending');
        }
        return $this->revisionRequests()->where('status', 'pending')->exists();
    }
}
