<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GroupM extends Model
{
    protected $table = 'groups';
    protected $fillable = ['name', 'description', 'is_active'];

    public function users()
    {
        return $this->hasMany(UserM::class, 'group_id');
    }

    public function managers()
    {
        return $this->belongsToMany(UserM::class, 'group_manager', 'group_id', 'user_id');
    }

    public function kpiTargets()
    {
        return $this->hasMany(KpiTargetM::class, 'assignee_id')->whereIn('assignee_type', ['group', 'manager']);
    }
}
