<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmailLogM extends Model
{
    protected $table = 'email_logs';
    protected $fillable = ['user_id', 'to_email', 'subject', 'body', 'sent_at'];
    protected $casts = ['sent_at' => 'datetime'];

    public function user()
    {
        return $this->belongsTo(UserM::class);
    }
}
