<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationC extends Controller
{
    public function index()
    {
        $notifications = Auth::user()->notifications()->paginate(20);
        return view('notifications.index', compact('notifications'));
    }

    public function markAsRead(Request $request, $id)
    {
        $notification = Auth::user()->notifications()->findOrFail($id);
        $notification->markAsRead();

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'unread_count' => Auth::user()->unreadNotifications()->count(),
                'message' => 'Bildirim okundu olarak işaretlendi.'
            ]);
        }

        return back()->with('success', 'Bildirim okundu olarak işaretlendi.');
    }

    public function markAllAsRead(Request $request)
    {
        Auth::user()->unreadNotifications->markAsRead();

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'unread_count' => 0,
                'message' => 'Tüm bildirimler okundu olarak işaretlendi.'
            ]);
        }

        return back()->with('success', 'Tüm bildirimler okundu olarak işaretlendi.');
    }
}
