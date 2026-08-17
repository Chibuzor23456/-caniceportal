<?php

use App\Http\Controllers\Install\InstallController;
use App\Http\Middleware\EnsureNotInstalled;
use App\Http\Middleware\ForceInstallerRuntimeConfig;
use Illuminate\Support\Facades\Route;

// First-run setup wizard: database, SMTP, admin account. Locked permanently
// after use (EnsureNotInstalled) - see app/Http/Middleware/EnsureNotInstalled.php
// for exactly what "installed" means.
//
// ForceInstallerRuntimeConfig runs before the 'web' group (not just before
// StartSession) since EncryptCookies also needs a valid APP_KEY, which may
// not exist yet on a freshly copied .env.
Route::middleware([ForceInstallerRuntimeConfig::class, 'web', EnsureNotInstalled::class])
    ->prefix('install')
    ->name('install.')
    ->group(function () {
        Route::get('/', [InstallController::class, 'showDatabase'])->name('database');
        Route::post('/database/test', [InstallController::class, 'testDatabase'])->name('database.test');
        Route::post('/database', [InstallController::class, 'saveDatabase'])->name('database.save');

        Route::get('/mail', [InstallController::class, 'showMail'])->name('mail');
        Route::post('/mail/test', [InstallController::class, 'testMail'])->name('mail.test');
        Route::post('/mail', [InstallController::class, 'saveMail'])->name('mail.save');

        Route::get('/admin', [InstallController::class, 'showAdmin'])->name('admin');
        Route::post('/admin', [InstallController::class, 'saveAdmin'])->name('admin.save');
    });
