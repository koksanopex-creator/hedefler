<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PermissionM extends Model
{
    protected $table = 'permissions';
    protected $fillable = ['module', 'action', 'slug', 'name'];

    public function roles()
    {
        return $this->belongsToMany(RoleM::class, 'role_permission', 'permission_id', 'role_id');
    }

    public function users()
    {
        return $this->belongsToMany(UserM::class, 'user_permission', 'permission_id', 'user_id')->withPivot('allowed');
    }
}
