<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FormEntryRowM extends Model
{
    protected $table = 'form_entry_rows';
    protected $fillable = ['form_entry_id', 'row_order'];

    public function entry()
    {
        return $this->belongsTo(FormEntryM::class, 'form_entry_id');
    }

    public function values()
    {
        return $this->hasMany(FormEntryValueM::class, 'form_entry_row_id');
    }
}
