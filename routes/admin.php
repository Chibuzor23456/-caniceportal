<?php

use App\Http\Controllers\Admin\ActivityController;
use App\Http\Controllers\Admin\ClientController;
use App\Http\Controllers\Admin\CompanySettingsController;
use App\Http\Controllers\Admin\ContractController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\FileController;
use App\Http\Controllers\Admin\InvoiceController;
use App\Http\Controllers\Admin\MessageController;
use App\Http\Controllers\Admin\ProjectController;
use App\Http\Controllers\Admin\QuotationController;
use App\Http\Controllers\Admin\QuotationTemplateController;
use App\Http\Controllers\Admin\TwoFactorSetupController;
use App\Http\Controllers\ComingSoonController;
use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Http\Controllers\AuthenticatedSessionController;

// Admin auth. Reuses Fortify's own controller/pipeline; the role check that
// makes this an admin-only login (as opposed to the client-facing /login) is
// enforced in FortifyServiceProvider::bindRoleAwareAuthentication(), keyed
// off the route name below ("admin.*").
Route::middleware('guest')->group(function () {
    Route::get('/admin/login', [AuthenticatedSessionController::class, 'create'])->name('admin.login');

    Route::post('/admin/login', [AuthenticatedSessionController::class, 'store'])
        ->middleware(config('fortify.limiters.login') ? 'throttle:'.config('fortify.limiters.login') : [])
        ->name('admin.login.store');
});

Route::post('/admin/logout', [AuthenticatedSessionController::class, 'destroy'])
    ->middleware('auth')
    ->name('admin.logout');

Route::prefix('admin')
    ->name('admin.')
    ->middleware(['auth', 'role:admin', 'admin.2fa'])
    ->group(function () {
        Route::get('/dashboard', DashboardController::class)->name('dashboard');

        Route::get('/security/two-factor-setup', [TwoFactorSetupController::class, 'show'])->name('two-factor.setup');

        Route::get('/clients', [ClientController::class, 'index'])->name('clients.index');
        Route::get('/clients/create', [ClientController::class, 'create'])->name('clients.create');
        Route::get('/clients/export', [ClientController::class, 'export'])->name('clients.export');
        Route::get('/clients/{client}', [ClientController::class, 'show'])->name('clients.show');
        Route::get('/clients/{client}/edit', [ClientController::class, 'edit'])->name('clients.edit');

        Route::get('/quotations', [QuotationController::class, 'index'])->name('quotations.index');
        Route::get('/quotations/create', [QuotationController::class, 'create'])->name('quotations.create');
        Route::get('/quotations/{quotation}', [QuotationController::class, 'show'])->name('quotations.show');
        Route::get('/quotations/{quotation}/edit', [QuotationController::class, 'edit'])->name('quotations.edit');
        Route::post('/quotations/{quotation}/send', [QuotationController::class, 'send'])->name('quotations.send');
        Route::get('/quotations/{quotation}/versions/{version}', [QuotationController::class, 'showVersion'])->name('quotations.versions.show');

        Route::get('/quotation-templates', [QuotationTemplateController::class, 'index'])->name('quotation-templates.index');

        Route::get('/contracts', [ContractController::class, 'index'])->name('contracts.index');
        Route::get('/contracts/create', [ContractController::class, 'create'])->name('contracts.create');
        Route::post('/contracts', [ContractController::class, 'store'])->name('contracts.store');
        Route::get('/contracts/{contract}', [ContractController::class, 'show'])->name('contracts.show');
        Route::post('/contracts/{contract}/send', [ContractController::class, 'send'])->name('contracts.send');

        Route::get('/projects', [ProjectController::class, 'index'])->name('projects.index');
        Route::get('/projects/{project}', [ProjectController::class, 'show'])->name('projects.show');
        Route::post('/projects/{project}/invoices', [InvoiceController::class, 'store'])->name('projects.invoices.store');

        Route::get('/invoices', [InvoiceController::class, 'index'])->name('invoices.index');
        Route::get('/invoices/export', [InvoiceController::class, 'export'])->name('invoices.export');
        Route::get('/invoices/{invoice}', [InvoiceController::class, 'show'])->name('invoices.show');
        Route::patch('/invoices/{invoice}', [InvoiceController::class, 'update'])->name('invoices.update');
        Route::post('/invoices/{invoice}/send', [InvoiceController::class, 'send'])->name('invoices.send');
        Route::post('/invoices/{invoice}/mark-paid', [InvoiceController::class, 'markPaid'])->name('invoices.mark-paid');
        Route::post('/invoices/{invoice}/cancel', [InvoiceController::class, 'cancel'])->name('invoices.cancel');

        Route::get('/files', [FileController::class, 'index'])->name('files.index');
        Route::get('/files/{file}/download', [FileController::class, 'download'])->name('files.download');

        Route::get('/messages', [MessageController::class, 'index'])->name('messages.index');
        Route::get('/messages/{client}', [MessageController::class, 'show'])->name('messages.show');

        Route::get('/activity', [ActivityController::class, 'index'])->name('activity.index');

        Route::get('/settings/company', [CompanySettingsController::class, 'show'])->name('settings.company');
        Route::post('/settings/company', [CompanySettingsController::class, 'update'])->name('settings.company.update');

        // Every other nav item from Section 6.8 that isn't built until a later
        // phase (full Settings beyond company/currency), kept navigable now,
        // filled in later.
        Route::get('/{any}', ComingSoonController::class)->where('any', '.*')->name('coming-soon');
    });
