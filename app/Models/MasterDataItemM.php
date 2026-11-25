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
        'sort_order',
        'user_id'
    ];

    protected $casts = [
        'sort_order' => 'integer',
    ];

    /**
     * Kategorinin sıralama moduna göre (custom / alphabetical) öğeleri sıralar.
     */
    public function scopeOrderedForType($query, ?MasterDataTypeM $type = null)
    {
        if ($type && $type->sort_mode === 'custom') {
            return $query->orderBy('sort_order', 'asc')->orderBy('id', 'asc');
        }

        $driver = $this->getConnection()->getDriverName();
        $nameOrder = $driver === 'mysql' ? 'name COLLATE utf8mb4_turkish_ci ASC' : 'name ASC';

        return $query->orderByRaw($nameOrder);
    }

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
