<?php

namespace App\Http\Controllers;

use App\Models\UserM;
use App\Models\RoleM;
use App\Models\GroupM;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserC extends Controller
{
    public function index()
    {
        $currentUser = auth()->user();
        $permissionS = app(\App\Services\PermissionS::class);

        $isSuperAdmin = ($currentUser->role && $currentUser->role->slug === 'sistem_yoneticisi') || $permissionS->hasPermission($currentUser, 'roles.manage') || $currentUser->id === 1;
        $canManage = $permissionS->hasPermission($currentUser, 'users.manage');
        $canViewAll = $permissionS->hasPermission($currentUser, 'users.view_all') || $canManage || $isSuperAdmin;
        $canViewGroup = $permissionS->hasPermission($currentUser, 'users.view_group') || $currentUser->managedGroups->isNotEmpty();
        $canView = $permissionS->hasPermission($currentUser, 'users.view') || $canViewAll || $canViewGroup;

        if (!$canView) {
            abort(403, 'Kullanıcı listesini görüntüleme yetkiniz bulunmamaktadır.');
        }

        if ($canViewAll) {
            $users = UserM::with(['role', 'group', 'managedGroups'])->get();

            // 1) Sistem ve Genel Yönetim Rolleri (Sistem Yöneticisi, Yönetim)
            $systemUsers = $users->filter(function ($u) {
                return in_array($u->role?->slug, ['sistem_yoneticisi', 'yonetim']);
            });

            // 2) Gruplar (Yöneticileri ve Üyeleri ile)
            $groups = GroupM::with(['managers.role', 'users.role'])->get();

            // 3) Herhangi bir gruba bağlı veya yönetici olmayan bağımsız kullanıcılar
            $systemUserIds = $systemUsers->pluck('id')->toArray();
            $unassignedUsers = $users->filter(function ($u) use ($systemUserIds) {
                if (in_array($u->id, $systemUserIds)) {
                    return false;
                }
                $hasGroup = !empty($u->group_id);
                $managesGroup = $u->managedGroups->isNotEmpty();
                return !$hasGroup && !$managesGroup;
            });
        } elseif ($canViewGroup) {
            // Sadece yöneticisi olduğu veya bağlı olduğu grup(lar)
            $managedGroupIds = $currentUser->managedGroups->pluck('id')->toArray();
            $accessibleGroupIds = array_unique(array_filter(array_merge([$currentUser->group_id], $managedGroupIds)));

            $groups = GroupM::with(['managers.role', 'users.role'])
                ->whereIn('id', $accessibleGroupIds)
                ->get();

            $users = UserM::with(['role', 'group', 'managedGroups'])
                ->where(function ($q) use ($accessibleGroupIds, $currentUser) {
                    $q->whereIn('group_id', $accessibleGroupIds)
                        ->orWhereHas('managedGroups', function ($sq) use ($accessibleGroupIds) {
                            $sq->whereIn('groups.id', $accessibleGroupIds);
                        })
                        ->orWhere('id', $currentUser->id);
                })
                ->get();

            $systemUsers = collect();
            $unassignedUsers = collect();
        } else {
            // Sadece kendisi
            $users = collect([$currentUser->load(['role', 'group', 'managedGroups'])]);
            $groups = $currentUser->group ? collect([$currentUser->group->load(['managers.role', 'users.role'])]) : collect();
            $systemUsers = collect();
            $unassignedUsers = collect();
        }

        return view('users.index', compact('users', 'systemUsers', 'groups', 'unassignedUsers', 'canManage', 'isSuperAdmin', 'canViewAll'));
    }

    public function create()
    {
        $roles = RoleM::all();
        $groups = GroupM::where('is_active', true)->get();
        return view('users.create', compact('roles', 'groups'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:6',
            'role_id' => 'required|exists:roles,id',
            'group_id' => 'nullable|exists:groups,id',
            'managed_groups' => 'nullable|array',
            'managed_groups.*' => 'exists:groups,id'
        ]);

        $validated['password'] = Hash::make($validated['password']);
        $validated['is_active'] = $request->has('is_active');

        $user = UserM::create($validated);

        if ($request->has('managed_groups')) {
            $user->managedGroups()->sync($request->managed_groups);
        }

        \App\Services\NotificationS::dispatch('user.created', [
            'user_name' => $user->name,
        ]);

        return redirect()->route('users.index')->with('success', 'Kullanıcı eklendi.');
    }

    public function edit(UserM $user)
    {
        $roles = RoleM::all();
        $groups = GroupM::where('is_active', true)->get();
        $user->load('managedGroups');
        $centralUrl = rtrim(config('services.central_sso.url', env('CENTRAL_SSO_URL', 'http://localhost:8001')), '/');
        return view('users.edit', compact('user', 'roles', 'groups', 'centralUrl'));
    }

    public function update(Request $request, UserM $user)
    {
        // Ad, Soyad, E-posta ve Şifre Merkezi API üzerinden yönetilir.
        // HEDEFLER uygulamasında yalnızca Rol, Grup ve Aktiflik güncellenir.
        $validated = $request->validate([
            'role_id' => 'required|exists:roles,id',
            'group_id' => 'nullable|exists:groups,id',
            'managed_groups' => 'nullable|array',
            'managed_groups.*' => 'exists:groups,id'
        ]);

        $isActive = $request->has('is_active');
        $validated['is_active'] = $isActive;
        $validated['approval_status'] = $isActive ? 'approved' : 'rejected';
        $user->update($validated);

        if (!$isActive) {
            // 1. Kullanıcının tüm açık oturumlarını derhal sonlandır
            try {
                \Illuminate\Support\Facades\DB::table('sessions')->where('user_id', $user->id)->delete();
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::warning('Oturum silme uyarısı: ' . $e->getMessage());
            }

            // 2. Merkezi API üzerinde hedefler erişimini pasife al
            if (!empty($user->central_user_id)) {
                try {
                    $hedeflerAppId = \Illuminate\Support\Facades\DB::table('merkezi_app.applications')->where('code', 'hedefler')->value('id') ?? 14;
                    \Illuminate\Support\Facades\DB::table('merkezi_app.app_users')
                        ->where('user_id', $user->central_user_id)
                        ->where('application_id', $hedeflerAppId)
                        ->update([
                            'is_active' => 0,
                            'status' => 'rejected',
                            'approval_notes' => 'Hedefler sistem yöneticisi tarafından pasife alındı.',
                            'updated_at' => now(),
                        ]);
                } catch (\Exception $e) {
                    \Illuminate\Support\Facades\Log::warning('Merkezi API app_users pasif güncelleme hatası: ' . $e->getMessage());
                }
            }
        } else {
            // Aktife alındıysa Merkezi API'de de onayla/aktif et
            if (!empty($user->central_user_id)) {
                try {
                    $hedeflerAppId = \Illuminate\Support\Facades\DB::table('merkezi_app.applications')->where('code', 'hedefler')->value('id') ?? 14;
                    \Illuminate\Support\Facades\DB::table('merkezi_app.app_users')
                        ->where('user_id', $user->central_user_id)
                        ->where('application_id', $hedeflerAppId)
                        ->update([
                            'is_active' => 1,
                            'status' => 'approved',
                            'approval_notes' => 'Hedefler sistem yöneticisi tarafından aktifleştirildi.',
                            'updated_at' => now(),
                        ]);
                } catch (\Exception $e) {
                    \Illuminate\Support\Facades\Log::warning('Merkezi API app_users aktif güncelleme hatası: ' . $e->getMessage());
                }
            }
        }

        if ($request->has('managed_groups')) {
            $user->managedGroups()->sync($request->managed_groups);
        } else {
            $user->managedGroups()->sync([]);
        }

        return redirect()->route('users.index')->with('success', "'{$user->name}' kullanıcısının rol ve durum yetkileri başarıyla güncellendi.");
    }
}
