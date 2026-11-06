<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FormEntryValueM extends Model
{
    protected $table = 'form_entry_values';
    protected $fillable = ['form_entry_row_id', 'form_field_id', 'value'];

    public function row()
    {
        return $this->belongsTo(FormEntryRowM::class, 'form_entry_row_id');
    }

    public function field()
    {
        return $this->belongsTo(FormFieldM::class, 'form_field_id');
    }
}
