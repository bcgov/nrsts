<?php

namespace Modules\Institution\Http\Middleware;

use App\Models\Role;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class IsAdmin
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, ...$roles)
    {
        $roles = empty($roles) ? [null] : $roles;

        if (! Auth::check()) {
            return redirect()->away(env('PDEX_LOGIN_URL', '/login'));
        }

        $user = Auth::user();
        if (! $user->hasRole(Role::SUPER_ADMIN) && ! $user->hasRole(Role::Institution_ADMIN)) {
            return redirect()->away(env('PDEX_LOGIN_URL', '/login'));
        }

        return $next($request);
    }
}
