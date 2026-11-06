<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MasterDataBatchM extends Model
{
    use HasFactory;

    protected $table = 'master_data_batches';

    protected $fillable = [
        'user_id',
        'type_id',
        'count',
        'item_ids',
        'preview',
        'is_undone',
    ];

    protected $casts = [
        'item_ids' => 'array',
        'is_undone' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(UserM::class, 'user_id');
    }

    public function type()
    {
        return $this->belongsTo(MasterDataTypeM::class, 'type_id');
    }
}

