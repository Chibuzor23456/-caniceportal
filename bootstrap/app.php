<?php

use App\Http\Middleware\EnsureClientPasswordIsCurrent;
use App\Http\Middleware\EnsureUserHasRole;
use App\Http\Middleware\ForceInstallerRuntimeConfig;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'role' => EnsureUserHasRole::class,
            'client.password' => EnsureClientPasswordIsCurrent::class,
        ]);

        // Laravel sorts route middleware by an internal priority list,
        // ignoring the order they're written in on the route itself -
        // EncryptCookies has a fixed high priority, so without this,
        // ForceInstallerRuntimeConfig runs AFTER it despite being listed
        // first in routes/install.php, defeating the whole point of it
        // (EncryptCookies needs APP_KEY, which is exactly what that
        // middleware exists to generate on a freshly copied .env).
        $middleware->prependToPriorityList(
            before: EncryptCookies::class,
            prepend: ForceInstallerRuntimeConfig::class,
        );
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
