<?php

namespace App\Http\Controllers;

use App\Models\UserM;
use App\Models\RoleM;
use App\Models\GroupM;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class UserSyncC extends Controller
{
    /**
     * Merkezi Senkronizasyon Ekranı
     */
    public function index()
    {
        $centralUrl = rtrim(config('services.central_sso.url', env('CENTRAL_SSO_URL', 'http://localhost:8001')), '/');
        
        // Merkezi veritabanından Hedefler uygulamasına atanmış kullanıcıları çek
        $appId = DB::table('merkezi_app.applications')->where('code', 'hedefler')->value('id') ?? 14;

        $centralAppUsers = DB::table('merkezi_app.app_users')
            ->join('merkezi_app.users', 'app_users.user_id', '=', 'users.id')
            ->leftJoin('merkezi_app.departments', 'users.department_id', '=', 'departments.id')
            ->where('app_users.application_id', $appId)
            ->whereNull('app_users.deleted_at')
            ->select(
                'users.id as central_id',
                'users.first_name',
                'users.last_name',
                'users.email',
                'departments.name as department_name',
                'app_users.status as app_status',
                'app_users.created_at as assigned_at'
            )
            ->get();

        // Yerel kullanıcıları email ile indeksle
        $localUsers = UserM::with(['role', 'group'])->get()->keyBy(fn($u) => strtolower(trim($u->email)));

        $roles = RoleM::all();
        $groups = GroupM::where('is_active', true)->get();

        // Kullanıcıları sınıflandır:
        // 1) Rolü Atanmamış (Bekleyenler)
        // 2) Rolü Tanımlı (Aktifler)
        $pendingUsers = [];
        $syncedUsers = [];

        foreach ($centralAppUsers as $cu) {
            $emailKey = strtolower(trim($cu->email));
            $local = $localUsers->get($emailKey);

            $item = (object)[
                'central_id' => $cu->central_id,
                'name' => trim($cu->first_name . ' ' . $cu->last_name),
                'email' => $cu->email,
                'department' => $cu->department_name ?? 'Tanımsız',
                'assigned_at' => $cu->assigned_at,
                'local_user' => $local,
                'has_role' => ($local && !empty($local->role_id) && $local->approval_status === 'approved'),
            ];

            if ($item->has_role) {
                $syncedUsers[] = $item;
            } else {
                $pendingUsers[] = $item;
            }
        }

        return view('users.sync', compact('pendingUsers', 'syncedUsers', 'roles', 'groups', 'centralUrl'));
    }

    /**
     * Merkezi Kullanıcıya HEDEFLER'de Rol Ata ve Sisteme Dahil Et
     */
    public function assignRole(Request $request, $centralId)
    {
        $request->validate([
            'role_id' => 'required|exists:roles,id',
            'group_id' => 'nullable|exists:groups,id',
        ]);

        // Merkezi kullanıcıyı bul
        $cu = DB::table('merkezi_app.users')
            ->leftJoin('merkezi_app.departments', 'users.department_id', '=', 'departments.id')
            ->where('users.id', $centralId)
            ->select('users.*', 'departments.name as department_name')
            ->first();

        if (!$cu) {
            return back()->with('error', 'Merkezi API kullanıcısı bulunamadı.');
        }

        $fullName = trim($cu->first_name . ' ' . $cu->last_name);

        $user = UserM::firstOrNew(['email' => $cu->email]);
        $user->name = $fullName;
        $user->central_user_id = $cu->id;
        $user->central_department = $cu->department_name;
        $user->role_id = $request->role_id;
        $user->group_id = $request->group_id;
        $user->approval_status = 'approved';
        $user->rejection_type = null;
        $user->rejection_reason = null;
        $user->rejected_at = null;
        $user->is_active = true;

        if (!$user->exists) {
            $user->password = bcrypt(\Illuminate\Support\Str::random(24));
        }
        $user->save();

        // Merkezi API'deki app_users durumunu da 'approved' yap
        $centralAdminId = auth()->user()?->central_user_id ?? 172;
        $appId = DB::table('merkezi_app.applications')->where('code', 'hedefler')->value('id') ?? 14;
        DB::table('merkezi_app.app_users')->updateOrInsert(
            ['user_id' => $cu->id, 'application_id' => $appId],
            [
                'status' => 'approved',
                'is_active' => 1,
                'approved_at' => now(),
                'approved_by' => $centralAdminId,
                'updated_at' => now(),
            ]
        );

        $roleName = RoleM::find($request->role_id)->name;
        return back()->with('success', "{$fullName} kullanıcısına '{$roleName}' rolü başarıyla tanımlandı ve sisteme dahil edildi.");
    }

    /**
     * Tüm Kullanıcıların Bilgilerini Merkezi API ile Eşitle
     */
    public function syncAll()
    {
        $centralUsers = DB::table('merkezi_app.users')
            ->leftJoin('merkezi_app.departments', 'users.department_id', '=', 'departments.id')
            ->select('users.id', 'users.first_name', 'users.last_name', 'users.email', 'departments.name as department_name')
            ->get();

        $updatedCount = 0;
        foreach ($centralUsers as $cu) {
            $user = UserM::where('email', $cu->email)->first();
            if ($user) {
                $user->update([
                    'name' => trim($cu->first_name . ' ' . $cu->last_name),
                    'central_user_id' => $cu->id,
                    'central_department' => $cu->department_name,
                ]);
                $updatedCount++;
            }
        }

        return back()->with('success', "{$updatedCount} kullanıcının ad, soyad ve bölüm bilgileri Merkezi Sistem ile eşitlendi.");
    }
}
