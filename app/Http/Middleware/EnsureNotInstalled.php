<?php

namespace App\Http\Middleware;

use App\Enums\UserRole;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Blocks every /install/* route once the app is installed - checked two
 * ways so a live site can never be re-configured or handed a rogue admin
 * account by anyone who finds the URL later.
 */
class EnsureNotInstalled
{
    public function handle(Request $request, Closure $next): Response|\Illuminate\Http\RedirectResponse
    {
        if ($this->alreadyInstalled()) {
            return redirect()->route('admin.login');
        }

        return $next($request);
    }

    private function alreadyInstalled(): bool
    {
        if (file_exists(storage_path('app/installed.lock'))) {
            return true;
        }

        try {
            return User::where('role', UserRole::Admin->value)->exists();
        } catch (\Throwable) {
            // No DB configured yet - the expected pre-install state.
            return false;
        }
    }
}
