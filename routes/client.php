<?php

use App\Http\Controllers\Client\ActivityController;
use App\Http\Controllers\Client\ContractController;
use App\Http\Controllers\Client\DashboardController;
use App\Http\Controllers\Client\DocumentArchiveController;
use App\Http\Controllers\Client\FileController;
use App\Http\Controllers\Client\ForcePasswordChangeController;
use App\Http\Controllers\Client\InvoiceController;
use App\Http\Controllers\Client\MessageController;
use App\Http\Controllers\Client\ProjectController;
use App\Http\Controllers\Client\QuotationController;
use App\Http\Controllers\Client\TestimonialController;
use App\Http\Controllers\ComingSoonController;
use Illuminate\Support\Facades\Route;

// Client-facing app. Section 11 reserves `/app/*` for the authenticated,
// login-gated views (as opposed to the public token-based quotation link or
// the public verification page introduced in later phases).
Route::prefix('app')
    ->name('client.')
    ->middleware(['auth', 'role:client'])
    ->group(function () {
        Route::get('/password/change', [ForcePasswordChangeController::class, 'show'])->name('password.change');
        Route::post('/password/change', [ForcePasswordChangeController::class, 'update'])->name('password.update');

        Route::middleware('client.password')->group(function () {
            Route::get('/dashboard', DashboardController::class)->name('dashboard');

            Route::get('/quotations', [QuotationController::class, 'index'])->name('quotations.index');
            Route::get('/quotations/{quotation:reference}', [QuotationController::class, 'show'])->name('quotations.show');

            Route::get('/projects', [ProjectController::class, 'index'])->name('projects.index');
            Route::get('/projects/{project}', [ProjectController::class, 'show'])->name('projects.show');

            Route::get('/contracts', [ContractController::class, 'index'])->name('contracts.index');
            Route::get('/contracts/{contract:reference}', [ContractController::class, 'show'])->name('contracts.show');

            Route::get('/invoices', [InvoiceController::class, 'index'])->name('invoices.index');
            Route::get('/invoices/{invoice:reference}', [InvoiceController::class, 'show'])->name('invoices.show');
            Route::post('/invoices/{invoice:reference}/payment-proof', [InvoiceController::class, 'uploadProof'])->name('invoices.payment-proof');

            Route::get('/messages', [MessageController::class, 'index'])->name('messages.index');

            Route::get('/files', [FileController::class, 'index'])->name('files.index');
            Route::get('/files/{file}/download', [FileController::class, 'download'])->name('files.download');

            Route::get('/documents', [DocumentArchiveController::class, 'index'])->name('documents.index');

            Route::post('/testimonials/{testimonial}', [TestimonialController::class, 'store'])->name('testimonials.store');

            Route::get('/activity', [ActivityController::class, 'index'])->name('activity.index');
            Route::get('/{any}', ComingSoonController::class)->where('any', '.*')->name('coming-soon');
        });
    });
