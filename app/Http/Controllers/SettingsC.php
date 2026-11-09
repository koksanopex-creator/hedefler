<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Services\ActivityLogS;

class SettingsC extends Controller
{
    public function index()
    {
        $formRevisionEnabled = \App\Services\SettingS::get('form_revision_enabled', true);
        $customLogoExists = Storage::disk('public')->exists('logo.png');
        $activityLogsCount = \App\Models\ActivityLogM::count();
        $notificationLogsCount = \App\Models\NotificationLogM::count();
        $notificationEventsCount = \App\Models\NotificationEventM::count();

        return view('settings.index', compact(
            'formRevisionEnabled',
            'customLogoExists',
            'activityLogsCount',
            'notificationLogsCount',
            'notificationEventsCount'
        ));
    }

    public function updateGeneral(Request $request, ActivityLogS $activityLogService)
    {
        $enabled = $request->has('form_revision_enabled');
        \App\Services\SettingS::set('form_revision_enabled', $enabled);

        $activityLogService->log('settings.general', 'Sistem genel ayarları güncellendi: Form revizyon talepleri ' . ($enabled ? 'Açık' : 'Kapalı'));

        return redirect()->back()->with('success', 'Genel sistem ayarları başarıyla güncellendi.');
    }

    public function uploadLogo(Request $request, ActivityLogS $activityLogService)
    {
        $request->validate([
            'logo' => 'required|image|mimes:jpeg,png,jpg,svg,webp|max:2048',
        ]);

        if ($request->file('logo')) {
            // Logoyu 'public' diskine logo.png olarak kaydet
            $request->file('logo')->storeAs('/', 'logo.png', 'public');
            
            $activityLogService->log('settings.logo', 'Sistem logosu güncellendi.');
        }

        return redirect()->back()->with('success', 'Sistem logosu başarıyla güncellendi.');
    }

    public function removeLogo(ActivityLogS $activityLogService)
    {
        if (Storage::disk('public')->exists('logo.png')) {
            Storage::disk('public')->delete('logo.png');
            $activityLogService->log('settings.logo', 'Sistem logosu varsayılana sıfırlandı.');
        }

        return redirect()->back()->with('success', 'Sistem logosu başarıyla varsayılan haline sıfırlandı.');
    }
}
