<?php

namespace App\Http\Controllers;

use App\Models\RoleM;
use App\Models\PermissionM;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class RoleC extends Controller
{
    public function matrix()
    {
        $roles = RoleM::all();
        $permissions = PermissionM::all()->groupBy('module')->map(function ($items, $module) {
            if ($module === 'dashboard') {
                return $items->sortBy(function ($p) {
                    if ($p->slug === 'dashboard.date_cockpit') return 1;
                    if ($p->slug === 'dashboard.kpi_cockpit') return 2;
                    return $p->id + 10;
                });
            }
            if ($module === 'users') {
                return $items->sortBy(function ($p) {
                    if ($p->slug === 'users.view_all') return 1;
                    if ($p->slug === 'users.view_group') return 2;
                    if ($p->slug === 'users.manage') return 3;
                    return $p->id + 10;
                });
            }
            return $items;
        });
        
        // Matrix'i doldurmak için mevcut ilişkileri çekelim
        $rolePermissions = [];
        foreach ($roles as $role) {
            $rolePermissions[$role->id] = $role->permissions->pluck('id')->toArray();
        }

        return view('roles.matrix', compact('roles', 'permissions', 'rolePermissions'));
    }

    public function updateMatrix(Request $request)
    {
        $matrix = $request->input('matrix', []);
        
        $roles = RoleM::all();
        foreach ($roles as $role) {
            $permissionsToSync = $matrix[$role->id] ?? [];
            $role->permissions()->sync($permissionsToSync);
        }

        Cache::flush();

        return redirect()->route('roles.matrix')->with('success', 'Yetki matrisi başarıyla güncellendi.');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:roles,name'
        ]);

        $slug = \Illuminate\Support\Str::slug($request->name, '_');

        // Aynı slug var mı kontrol et
        if (RoleM::where('slug', $slug)->exists()) {
            return back()->withErrors(['name' => 'Bu isimde veya benzer bir rol zaten mevcut.'])->withInput();
        }

        RoleM::create([
            'name' => $request->name,
            'slug' => $slug
        ]);

        Cache::flush();

        return redirect()->route('roles.matrix')->with('success', 'Yeni rol başarıyla eklendi.');
    }

    public function destroy(RoleM $role)
    {
        // Çekirdek rolleri koru
        $coreRoles = ['sistem_yoneticisi', 'yonetici', 'standart_kullanici'];
        
        if (in_array($role->slug, $coreRoles)) {
            return back()->with('error', 'Çekirdek roller silinemez.');
        }

        // Eğer bu role sahip kullanıcılar varsa silmeyi engelleyelim
        if ($role->users()->count() > 0) {
            return back()->with('error', 'Bu role atanmış kullanıcılar olduğu için rol silinemez. Önce kullanıcıların rolünü değiştirin.');
        }

        $role->permissions()->detach();
        $role->delete();

        Cache::flush();

        return redirect()->route('roles.matrix')->with('success', 'Rol başarıyla silindi.');
    }
}
