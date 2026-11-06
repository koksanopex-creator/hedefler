<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class UserM extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected $table = 'users';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'avatar',
        'password',
        'role_id',
        'group_id',
        'is_active',
        'preferences',
        'central_user_id',
        'central_department',
        'approval_status',
        'rejection_type',
        'rejection_reason',
        'rejected_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'preferences' => 'array',
            'rejected_at' => 'datetime',
        ];
    }

    public function role()
    {
        return $this->belongsTo(RoleM::class, 'role_id');
    }

    public function getAvatarUrlAttribute(): ?string
    {
        if ($this->avatar) {
            return asset('storage/' . $this->avatar);
        }
        return null;
    }

    public function group()
    {
        return $this->belongsTo(GroupM::class, 'group_id');
    }

    public function managedGroups()
    {
        return $this->belongsToMany(GroupM::class, 'group_manager', 'user_id', 'group_id');
    }

    public function permissions()
    {
        return $this->belongsToMany(PermissionM::class, 'user_permission', 'user_id', 'permission_id')->withPivot('allowed');
    }

    public function formEntries()
    {
        return $this->hasMany(FormEntryM::class, 'user_id');
    }

    public function kpiTargets()
    {
        return $this->hasMany(KpiTargetM::class, 'assignee_id')->where('assignee_type', 'user');
    }
}
