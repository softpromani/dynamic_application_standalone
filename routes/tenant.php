<?php

use Illuminate\Support\Facades\Route;
use Softpro\Core\Http\Controllers\Auth\AdminAuthController;
use Softpro\Core\Http\Controllers\NewsController;
use Softpro\Core\Http\Controllers\ProgramController;
use Softpro\Core\Http\Controllers\OpeningController;
use Softpro\Core\Http\Controllers\FormTemplateController;
use Softpro\Core\Http\Controllers\Auth\ApplicantAuthController;
use Softpro\Core\Http\Controllers\ApplicantProfileController;
use Softpro\Core\Http\Controllers\ApplicantApplicationController;
use Softpro\Core\Http\Controllers\AdminApplicationController;
use Softpro\Core\Http\Controllers\ReportController;
use Softpro\Core\Http\Controllers\AdminVirtualColumnController;
use Softpro\Core\Http\Controllers\AdminDashboardController;
use Softpro\Core\Http\Controllers\AdminSettingsController;
use Softpro\Core\Http\Controllers\CustomEntityController;

/*
|--------------------------------------------------------------------------
| Softpro Core - Tenant Routes
|--------------------------------------------------------------------------
*/

if (config('softpro-core.enable_root_route', true)) {
    Route::get('/', function () {
        // This could be moved to a LandingController in the package
        $tenant = function_exists('tenant') ? tenant() : null;
        if ($tenant && !empty($tenant->landing_page_html)) {
            return Inertia\Inertia::render('CustomLanding', [
                'htmlContent' => $tenant->landing_page_html
            ]);
        }

        $programs = \Softpro\Core\Models\Program::with(['openings.subject'])
            ->where('is_active', true)
            ->where('application_end_date', '>=', now())
            ->latest()
            ->get();

        $news = \Softpro\Core\Models\News::where('is_active', true)
            ->orderBy('sort_order')
            ->latest()
            ->get();

        return Inertia\Inertia::render('Welcome', [
            'programs' => $programs,
            'news' => $news,
        ]);
    })->name('softpro.welcome');

    Route::get('/email/verify/{id}/{hash}', function (\Illuminate\Foundation\Auth\EmailVerificationRequest $request) {
        $request->fulfill();
        // Log the applicant in after successful verification using the applicant guard
        \Illuminate\Support\Facades\Auth::guard('applicant')->login($request->user());
        // Ensure subsequent requests use the applicant guard
        \Illuminate\Support\Facades\Auth::shouldUse('applicant');
        return redirect()
            ->intended('/dashboard')
            ->with('status', 'Your email has been verified.');
    })
    ->middleware(['signed'])
    ->name('verification.verify');
}

// Admin Auth
Route::get('/login', [AdminAuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AdminAuthController::class, 'login']);
Route::post('/logout', [AdminAuthController::class, 'logout'])->name('logout');

// Applicant Auth
Route::prefix('applicant')->name('applicant.')->group(function () {
    Route::get('/register', [ApplicantAuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [ApplicantAuthController::class, 'register']);
    Route::get('/login', [ApplicantAuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [ApplicantAuthController::class, 'login']);
    Route::post('/logout', [ApplicantAuthController::class, 'logout'])->name('logout');

    Route::middleware([
        'web',
        \Softpro\Core\Http\Middleware\SetStandaloneRootView::class,
        'auth:applicant',
        'verified',
        \App\Http\Middleware\EnsureProfileIsComplete::class
    ])->group(function () {
        Route::get('/dashboard', [ApplicantProfileController::class, 'dashboard'])->name('dashboard');
        Route::get('/profile-setup', [ApplicantProfileController::class, 'showProfileSetup'])->name('profile-setup');
        Route::post('/profile-update', [ApplicantProfileController::class, 'updateProfile'])->name('profile-update');

        // Applications
        Route::get('/browse-programs', [ApplicantApplicationController::class, 'index'])->name('browse-programs');
        Route::post('/apply/upload-temp', [ApplicantApplicationController::class, 'uploadTemp'])->name('apply.upload-temp');
        Route::get('/apply/{opening}', [ApplicantApplicationController::class, 'showForm'])->name('apply.form');
        Route::get('/apply/{opening}/preview', [ApplicantApplicationController::class, 'preview'])->name('apply.preview');
        Route::post('/apply/{opening}/step', [ApplicantApplicationController::class, 'saveStep'])->name('apply.saveStep');
        Route::post('/apply/{opening}', [ApplicantApplicationController::class, 'submitForm'])->name('apply.submit');
        Route::get('/applications/{application}/print', [ApplicantApplicationController::class, 'print'])->name('application.print');
    });
});

Route::middleware(['auth'])->group(function() {
    Route::resource('programs', ProgramController::class);
    Route::resource('openings', OpeningController::class);
    Route::resource('templates', FormTemplateController::class);
    Route::get('templates/{template}/preview', [FormTemplateController::class, 'preview'])->name('templates.preview');
    Route::post('templates/{template}/toggle', [FormTemplateController::class, 'toggleStatus'])->name('templates.toggle');
    Route::resource('news', NewsController::class);
    
    // Applications (admin view)
    Route::get('applications', [AdminApplicationController::class, 'index'])->name('applications.index');
    Route::get('applications/{application}', [AdminApplicationController::class, 'show'])->name('applications.show');
    Route::get('applications/{application}/print', [AdminApplicationController::class, 'print'])->name('applications.print');
    Route::patch('applications/{application}/status', [AdminApplicationController::class, 'updateStatus'])->name('applications.update-status');
    Route::post('applications/{application}/unlock', [AdminApplicationController::class, 'unlockForm'])->name('applications.unlock');
    Route::post('applications/{application}/refresh-payment', [AdminApplicationController::class, 'refreshPaymentStatus'])->name('applications.refresh-payment');
    Route::post('applications/bulk-sync-payment-status', [AdminApplicationController::class, 'bulkSyncPayments'])->name('applications.bulk-sync');

    // Dashboard
    Route::get('dashboard', [AdminDashboardController::class, 'index'])->name('admin.dashboard');
    Route::get('dashboard/export', [AdminDashboardController::class, 'exportApplications'])->name('admin.dashboard.export');

    // Settings
    Route::get('settings', [AdminSettingsController::class, 'index'])->name('admin.settings.index');
    Route::post('settings', [AdminSettingsController::class, 'update'])->name('admin.settings.update');

    // Custom Entities (Master Entities)
    Route::prefix('custom-entities')->name('admin.custom-entities.')->group(function() {
        Route::get('/', [CustomEntityController::class, 'index'])->name('index');
        Route::post('/', [CustomEntityController::class, 'store'])->name('store');
        Route::get('/{entity}', [CustomEntityController::class, 'show'])->name('show');
        Route::put('/{entity}', [CustomEntityController::class, 'update'])->name('update');
        Route::delete('/{entity}', [CustomEntityController::class, 'destroy'])->name('destroy');
        
        Route::post('/{entity}/values', [CustomEntityController::class, 'storeValue'])->name('values.store');
        Route::put('/values/{value}', [CustomEntityController::class, 'updateValue'])->name('values.update');
        Route::delete('/values/{value}', [CustomEntityController::class, 'destroyValue'])->name('values.destroy');
    });

    // Reports
    Route::get('reports', [ReportController::class, 'index'])->name('admin.reports.index');
    Route::get('reports/master-excel', [ReportController::class, 'masterExcel'])->name('admin.reports.master');
    Route::get('reports/dossier-excel', [ReportController::class, 'dossierExcel'])->name('admin.reports.dossier');

    // Virtual Columns
    Route::get('virtual-columns', [AdminVirtualColumnController::class, 'index'])->name('admin.virtual-columns.index');
    Route::post('virtual-columns', [AdminVirtualColumnController::class, 'store'])->name('admin.virtual-columns.store');
    Route::delete('virtual-columns/{column}', [AdminVirtualColumnController::class, 'destroy'])->name('admin.virtual-columns.destroy');
});
