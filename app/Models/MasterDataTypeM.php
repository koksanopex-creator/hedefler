<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MasterDataTypeM extends Model
{
    use HasFactory;

    protected $table = 'master_data_types';

    protected $fillable = [
        'name',
        'slug',
        'parent_id'
    ];

    public function parent()
    {
        return $this->belongsTo(MasterDataTypeM::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(MasterDataTypeM::class, 'parent_id');
    }

    public function items()
    {
        return $this->hasMany(MasterDataItemM::class, 'type_id');
    }
}
