<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class ProfileC extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): View
    {
        $centralUrl = rtrim(config('services.central_sso.url', env('CENTRAL_SSO_URL', 'https://kys.koksan.com/merkezi_yonetim_sistemi')), '/');

        return view('profile.edit', [
            'user' => $request->user(),
            'centralUrl' => $centralUrl,
        ]);
    }

    /**
     * Update the user's profile information (Avatar upload & preferences).
     */
    public function update(Request $request): RedirectResponse
    {
        $request->validate([
            'avatar' => 'nullable|image|mimes:jpeg,png,jpg,webp,svg|max:2048',
        ]);

        $user = $request->user();

        // Profil Resmi Kaldırma
        if ($request->boolean('remove_avatar')) {
            if ($user->avatar && Storage::disk('public')->exists($user->avatar)) {
                Storage::disk('public')->delete($user->avatar);
            }
            $user->avatar = null;
            $user->save();
            return Redirect::route('profile.edit')->with('status', 'avatar-removed');
        }

        // Yeni Profil Resmi Yükleme
        if ($request->hasFile('avatar')) {
            // Eski avatarı sil
            if ($user->avatar && Storage::disk('public')->exists($user->avatar)) {
                Storage::disk('public')->delete($user->avatar);
            }

            $file = $request->file('avatar');
            $extension = $file->getClientOriginalExtension();
            $filename = 'avatar_' . $user->id . '_' . time() . '.' . $extension;
            $path = $file->storeAs('avatars', $filename, 'public');

            $user->avatar = $path;
            $user->save();
        }

        return Redirect::route('profile.edit')->with('status', 'profile-updated');
    }

    /**
     * Delete the user's account (Disabled in SSO environment).
     */
    public function destroy(Request $request): RedirectResponse
    {
        return Redirect::route('profile.edit')->with('error', 'Kurumsal hesaplar Merkezi Yönetim Sistemi üzerinden yönetilir ve buradan silinemez.');
    }
}
