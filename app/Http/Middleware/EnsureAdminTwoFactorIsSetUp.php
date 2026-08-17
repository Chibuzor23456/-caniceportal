<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Section 7: 2FA is mandatory for the admin account. An admin who hasn't
 * confirmed it yet is redirected to the setup screen on every request until
 * they do; there is no path to the rest of the admin area without it.
 */
class EnsureAdminTwoFactorIsSetUp
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->isAdmin() && is_null($user->two_factor_confirmed_at) && ! $request->routeIs('admin.two-factor.setup')) {
            return redirect()->route('admin.two-factor.setup');
        }

        return $next($request);
    }
}
