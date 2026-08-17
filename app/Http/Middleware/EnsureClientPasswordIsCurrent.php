<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Section 9 onboarding: a client's first password is a generated temporary
 * one. They're forced through a change-password screen before reaching
 * anything else in the client area.
 */
class EnsureClientPasswordIsCurrent
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->isClient() && $user->must_change_password && ! $request->routeIs('client.password.change')) {
            return redirect()->route('client.password.change');
        }

        return $next($request);
    }
}
