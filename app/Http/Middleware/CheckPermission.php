<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckPermission
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, ...$permissions): Response
    {
        $user = $request->user();
        if (! $user) {
            abort(403, 'Bu işlem için yetkiniz bulunmamaktadır.');
        }

        // Verilen yetkilerden herhangi birine sahip mi kontrol et (OR mantığı)
        $hasAny = false;
        foreach ($permissions as $permission) {
            if ($user->can($permission)) {
                $hasAny = true;
                break;
            }
        }

        if (! $hasAny) {
            abort(403, 'Bu işlem için yetkiniz bulunmamaktadır.');
        }

        return $next($request);
    }
}
