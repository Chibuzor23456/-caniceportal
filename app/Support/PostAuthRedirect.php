<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Http\RedirectResponse;

class PostAuthRedirect
{
    /**
     * Where a just-authenticated (or already-authenticated) user should land,
     * accounting for the admin's mandatory 2FA setup and a client's forced
     * first-login password change.
     */
    public static function path(User $user): string
    {
        if ($user->isAdmin()) {
            return is_null($user->two_factor_confirmed_at)
                ? route('admin.two-factor.setup')
                : route('admin.dashboard');
        }

        return $user->must_change_password
            ? route('client.password.change')
            : route('client.dashboard');
    }

    public static function redirect(User $user): RedirectResponse
    {
        return redirect(static::path($user));
    }
}
