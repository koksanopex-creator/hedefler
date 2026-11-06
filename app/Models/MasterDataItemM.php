<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MasterDataItemM extends Model
{
    use HasFactory;

    protected $table = 'master_data_items';

    protected $fillable = [
        'type_id',
        'name',
        'parent_id',
        'user_id'
    ];

    public function user()
    {
        return $this->belongsTo(UserM::class, 'user_id');
    }

    public function type()
    {
        return $this->belongsTo(MasterDataTypeM::class, 'type_id');
    }

    public function parent()
    {
        return $this->belongsTo(MasterDataItemM::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(MasterDataItemM::class, 'parent_id');
    }
}
