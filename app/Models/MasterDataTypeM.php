<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class MasterDataTypeM extends Model
{
    use HasFactory;

    protected $table = 'master_data_types';

    protected $fillable = [
        'name',
        'slug',
        'parent_id',
        'sort_mode',
    ];

    public function parent()
    {
        return $this->belongsTo(MasterDataTypeM::class, 'parent_id');
    }

    public function children()
    {
        $driver = DB::connection($this->getConnectionName())->getDriverName();
        $nameOrder = $driver === 'mysql' ? 'name COLLATE utf8mb4_turkish_ci ASC' : 'name ASC';

        return $this->hasMany(MasterDataTypeM::class, 'parent_id')->orderByRaw($nameOrder);
    }

    public function items()
    {
        $driver = DB::connection($this->getConnectionName())->getDriverName();
        $nameOrder = $driver === 'mysql' ? 'name COLLATE utf8mb4_turkish_ci ASC' : 'name ASC';

        return $this->hasMany(MasterDataItemM::class, 'type_id')
            ->orderByRaw("CASE WHEN (SELECT sort_mode FROM master_data_types WHERE master_data_types.id = master_data_items.type_id) = 'custom' THEN master_data_items.sort_order ELSE 0 END ASC")
            ->orderByRaw("CASE WHEN (SELECT sort_mode FROM master_data_types WHERE master_data_types.id = master_data_items.type_id) = 'custom' THEN master_data_items.id ELSE 0 END ASC")
            ->orderByRaw($nameOrder);
    }
}
