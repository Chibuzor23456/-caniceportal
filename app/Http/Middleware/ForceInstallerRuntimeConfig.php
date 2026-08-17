<?php

namespace App\Http\Middleware;

use App\Support\EnvFileWriter;
use Closure;
use Illuminate\Encryption\Encrypter;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Runs before the session/cache stack on install routes only, so the
 * wizard's own pages don't break before the database exists: the default
 * SESSION_DRIVER/CACHE_STORE are "database" (.env.example), and a freshly
 * copied .env may have no APP_KEY yet at all.
 */
class ForceInstallerRuntimeConfig
{
    public function handle(Request $request, Closure $next): Response
    {
        if (empty(config('app.key'))) {
            $key = 'base64:'.base64_encode(Encrypter::generateKey(config('app.cipher')));

            EnvFileWriter::set(['APP_KEY' => $key]);
            config(['app.key' => $key]);
        }

        config([
            'session.driver' => 'file',
            'cache.default' => 'file',
        ]);

        return $next($request);
    }
}
