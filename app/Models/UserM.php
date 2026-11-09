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

    public function ssoApplications()
    {
        return $this->hasMany(SsoApplicationM::class, 'user_id');
    }

    /**
     * Aktif, onaylı ve rolü atanmış gerçek sistem kullanıcıları
     * (Henüz başvuru aşamasında veya onaysız olanlar hariç)
     */
    public function scopeActivePersonnel($query)
    {
        return $query->where('users.is_active', true)
            ->where('users.approval_status', 'approved')
            ->whereNotNull('users.role_id')
            ->whereDoesntHave('ssoApplications', function ($q) {
                $q->where('status', 'pending');
            })
            ->whereNotIn('users.email', function ($sq) {
                $sq->select('email')->from('sso_applications')->where('status', 'pending');
            });
    }

    /**
     * KPI ve Performans Hedefi Atanabilecek Personeller
     * (Sistem Yöneticisi ve Yönetim rolleri KPI hedefi almaz, listelerde görünmez)
     */
    public function scopeEligibleForKpi($query)
    {
        return $query->activePersonnel()
            ->where('users.id', '!=', 1)
            ->whereHas('role', function ($q) {
                $q->whereNotIn('slug', ['sistem_yoneticisi', 'yonetim']);
            });
    }

    public function hasRole(string|array $roles): bool
    {
        $roleSlug = $this->role?->slug ?? '';
        $roleName = $this->role?->name ?? '';

        if (is_array($roles)) {
            foreach ($roles as $r) {
                if (strcasecmp($roleSlug, $r) === 0 || strcasecmp($roleName, $r) === 0) {
                    return true;
                }
            }
            return false;
        }

        return strcasecmp($roleSlug, $roles) === 0 || strcasecmp($roleName, $roles) === 0;
    }

    public function isSuperAdmin(): bool
    {
        return $this->id === 1 || $this->role_id === 1 || $this->role?->slug === 'sistem_yoneticisi';
    }

    public function isYonetim(): bool
    {
        return $this->role_id === 19 || $this->role?->slug === 'yonetim' || strcasecmp($this->role?->name ?? '', 'Yonetim') === 0;
    }
}
