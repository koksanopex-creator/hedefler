<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsActive
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->routeIs('sso.onay_bekliyor', 'sso.logout')) {
            return $next($request);
        }

        if (Auth::check()) {
            $user = Auth::user();
            if (!$user->is_active) {
                $centralUser = session('sso_user_data') ?? [
                    'first_name' => $user->name,
                    'last_name' => '',
                    'email' => $user->email,
                    'department_name' => $user->central_department,
                ];

                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                session([
                    'sso_user_data' => $centralUser,
                    'account_passive' => true,
                ]);
                $request->session()->save();

                return redirect()->route('sso.onay_bekliyor')->with('account_passive', true);
            }
        }

        return $next($request);
    }
}
