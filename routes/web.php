<?php

use App\Http\Controllers\CanonicalQuotationController;
use App\Http\Controllers\Public\SecureQuotationController;
use App\Http\Controllers\Public\VerificationController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route('login'));

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

require __DIR__.'/admin.php';
require __DIR__.'/client.php';
