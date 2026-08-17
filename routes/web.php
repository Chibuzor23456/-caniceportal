<?php

use App\Http\Controllers\CanonicalContractController;
use App\Http\Controllers\CanonicalQuotationController;
use App\Http\Controllers\DeployWebhookController;
use App\Http\Controllers\Public\SecureContractController;
use App\Http\Controllers\Public\SecureQuotationController;
use App\Http\Controllers\Public\VerificationController;
use Illuminate\Support\Facades\Route;

require __DIR__.'/install.php';

Route::get('/', fn () => redirect()->route('login'));

// GitHub push webhook (see README "Auto-Deploy"). Public by necessity -
// signature verification inside the controller is the real gate, not
// anything at the route layer. Throttled as defense in depth.
Route::post('/deploy/webhook', DeployWebhookController::class)
    ->middleware('throttle:20,1')
    ->name('deploy.webhook');

// Public, token-based, no login (Section 11).
Route::get('/q/{token}', [SecureQuotationController::class, 'show'])->name('quotation.secure');
Route::post('/q/{token}/accept', [SecureQuotationController::class, 'accept'])->name('quotation.secure.accept');
Route::post('/q/{token}/reject', [SecureQuotationController::class, 'reject'])->name('quotation.secure.reject');
Route::post('/q/{token}/request-revision', [SecureQuotationController::class, 'requestRevision'])->name('quotation.secure.request-revision');

// Public, non-sensitive confirmation data only (Section 11).
Route::get('/verify/{reference}', VerificationController::class)->name('quotation.verify');

// Authenticated via session (not a token), permanent canonical signed copy
// (Section 11). Reachable by the owning client or any admin - not nested
// under /admin or /app since neither role prefix fits its access rule.
Route::get('/quotation/{slug}', CanonicalQuotationController::class)
    ->middleware('auth')
    ->name('quotation.canonical');

// Contracts (Section 14) - same public token/canonical pattern as quotations.
Route::get('/c/{token}', [SecureContractController::class, 'show'])->name('contract.secure');
Route::post('/c/{token}/accept', [SecureContractController::class, 'accept'])->name('contract.secure.accept');
Route::post('/c/{token}/reject', [SecureContractController::class, 'reject'])->name('contract.secure.reject');

Route::get('/contract/{slug}', CanonicalContractController::class)
    ->middleware('auth')
    ->name('contract.canonical');

require __DIR__.'/admin.php';
require __DIR__.'/client.php';
