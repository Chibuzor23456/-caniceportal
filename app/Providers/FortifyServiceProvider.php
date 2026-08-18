<?php

namespace App\Providers;

use App\Actions\Fortify\ResetUserPassword;
use App\Actions\Fortify\UpdateUserPassword;
use App\Actions\Fortify\UpdateUserProfileInformation;
use App\Http\Responses\RoleAwareLoginResponse;
use App\Http\Responses\RoleAwareLogoutResponse;
use App\Http\Responses\RoleAwarePasswordResetResponse;
use App\Models\User;
use App\Support\PostAuthRedirect;
use Illuminate\Auth\Middleware\RedirectIfAuthenticated;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use App\Enums\ClientStatus;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Contracts\LoginResponse;
use Laravel\Fortify\Contracts\LogoutResponse;
use Laravel\Fortify\Contracts\PasswordResetResponse;
use Laravel\Fortify\Fortify;

class FortifyServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Fortify::updateUserProfileInformationUsing(UpdateUserProfileInformation::class);
        Fortify::updateUserPasswordsUsing(UpdateUserPassword::class);
        Fortify::resetUserPasswordsUsing(ResetUserPassword::class);

        $this->bindRoleAwareResponses();
        $this->bindRoleAwareAuthentication();
        $this->bindViews();

        RedirectIfAuthenticated::redirectUsing(
            fn (Request $request) => PostAuthRedirect::path($request->user())
        );

        RateLimiter::for('login', function (Request $request) {
            $throttleKey = Str::transliterate(Str::lower($request->input(Fortify::username())).'|'.$request->ip());

            return Limit::perMinute(5)->by($throttleKey);
        });
    }

    private function bindRoleAwareResponses(): void
    {
        $this->app->singleton(LoginResponse::class, RoleAwareLoginResponse::class);
        $this->app->singleton(LogoutResponse::class, RoleAwareLogoutResponse::class);
        $this->app->bind(PasswordResetResponse::class, RoleAwarePasswordResetResponse::class);
    }

    /**
     * Both `/admin/login` and `/login` submit through the same Fortify
     * controller/pipeline (see routes/admin.php + routes/client.php); this
     * closure is what actually enforces "an admin login can only ever
     * authenticate an admin account, and vice versa" (PRD Section 7).
     */
    private function bindRoleAwareAuthentication(): void
    {
        Fortify::authenticateUsing(function (Request $request) {
            $user = User::where('email', $request->input(Fortify::username()))->first();

            if (! $user || ! Hash::check($request->input('password'), $user->password)) {
                return null;
            }

            $requiresAdmin = $request->routeIs('admin.*');

            if ($requiresAdmin !== $user->isAdmin()) {
                return null;
            }

            if ($user->isClient() && (! $user->client || $user->client->status === ClientStatus::Suspended)) {
                throw ValidationException::withMessages([
                    'email' => __('This account has been suspended. Contact us if you believe this is a mistake.'),
                ]);
            }

            return $user;
        });
    }

    private function bindViews(): void
    {
        Fortify::loginView(
            fn (Request $request) => view($request->routeIs('admin.*') ? 'auth.admin-login' : 'auth.client-login')
        );

        Fortify::requestPasswordResetLinkView(fn () => view('auth.forgot-password'));
        Fortify::resetPasswordView(fn (Request $request) => view('auth.reset-password', ['request' => $request]));
        Fortify::verifyEmailView(fn () => view('auth.verify-email'));
        Fortify::confirmPasswordView(fn () => view('auth.confirm-password'));
    }
}
