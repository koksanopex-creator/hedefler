<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RoleM extends Model
{
    protected $table = 'roles';
    protected $fillable = ['name', 'slug', 'description', 'is_system'];

    public function users()
    {
        return $this->hasMany(UserM::class, 'role_id');
    }

    public function permissions()
    {
        return $this->belongsToMany(PermissionM::class, 'role_permission', 'role_id', 'permission_id');
    }
}
