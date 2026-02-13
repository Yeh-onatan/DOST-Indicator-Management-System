<?php

use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;
use Laravel\Fortify\Features;
use Illuminate\Support\Facades\Auth;

// Controllers
use App\Http\Controllers\ReportController;
use App\Http\Controllers\PasswordSecurityController;
use App\Http\Controllers\SuperAdminController;

use App\Http\Middleware\EnsureOUSEC;

// Livewire components
use App\Livewire\Proponent\ObjectiveList;
use App\Http\Middleware\EnsureAdministrator;
use App\Http\Middleware\EnsureSuperAdmin;

/*
|--------------------------------------------------------------------------
| AUTH ROUTES (FOR FORTIFY + TESTS)
|--------------------------------------------------------------------------
*/

// Fortify provides auth routes; no local stub controllers needed.

// Logout (simple closure to avoid missing controller)
Route::post('/logout', function () {
    Auth::guard('web')->logout();
    request()->session()->invalidate();
    request()->session()->regenerateToken();
    return redirect('/');
})->middleware('auth')->name('logout');

/*
|--------------------------------------------------------------------------
| Public
|--------------------------------------------------------------------------
*/
Route::get('/', function () {
    if (! auth()->check()) {
        return redirect()->route('login');
    }

    $pref = auth()->user()?->settings?->default_landing_page ?? null;

    if ($pref && Route::has($pref)) {
        return redirect()->route($pref);
    }

    return redirect()->route('dashboard');
})->name('home');

/*
|--------------------------------------------------------------------------
| Authenticated
|--------------------------------------------------------------------------
*/
Route::middleware(['auth'])->group(function () {
    // Password security routes (for expired password flow)
    Route::get('/password/change', [PasswordSecurityController::class, 'showChangeForm'])
        ->name('password.change');
    Route::post('/password/change', [PasswordSecurityController::class, 'change'])
        ->name('password.update');

    // SuperAdmin: User impersonation routes
    Route::prefix('superadmin')->middleware(['auth', 'superadmin'])->group(function () {
        Route::get('/impersonate/{id}', [SuperAdminController::class, 'impersonate'])
            ->name('superadmin.impersonate');
    });

    // Exit impersonation route (must be accessible while impersonating, so no superadmin middleware)
    // Supports both GET and POST for better UX (e.g., direct URL access, bookmarks)
    Route::match(['get', 'post'], '/superadmin/exit-impersonation', [SuperAdminController::class, 'exitImpersonation'])
        ->name('superadmin.exit-impersonation');

    // Dashboard - Unified dashboard for all roles
    Route::get('/home', \App\Livewire\Dashboard\UnifiedDashboard::class)->name('dashboard');

    // Backwards-compatible redirect from old /dashboard URL
    Route::redirect('/dashboard', '/home');

    // Notifications
    Route::prefix('notifications')->group(function () {
        Route::get('/', [App\Http\Controllers\NotificationsController::class, 'index'])
            ->name('notifications.index');
        Route::post('/{id}/mark-read', [App\Http\Controllers\NotificationsController::class, 'markAsRead'])
            ->name('notifications.mark-read');
        Route::post('/mark-all-read', [App\Http\Controllers\NotificationsController::class, 'markAllAsRead'])
            ->name('notifications.mark-all-read');
        Route::delete('/{id}', [App\Http\Controllers\NotificationsController::class, 'destroy'])
            ->name('notifications.destroy');
        Route::post('/clear-read', [App\Http\Controllers\NotificationsController::class, 'clearRead'])
            ->name('notifications.clear-read');
    });

    // Settings
    Route::redirect('/settings', '/settings/profile');
    Volt::route('/settings/profile', 'settings.profile')->name('profile.edit');
    Volt::route('/settings/password', 'settings.password')->name('user-password.edit');

    /*
    |--------------------------------------------------------------------------
    | Admin Settings
    |--------------------------------------------------------------------------
    */

    Route::get('/settings/admin/approvals', \App\Livewire\Admin\Approvals::class)
        ->middleware(EnsureAdministrator::class)
        ->name('settings.admin.approvals');

    Route::get('/settings/audit', \App\Livewire\Super\AuditLogs::class)
        ->middleware(EnsureSuperAdmin::class)
        ->name('settings.audit');

    /*
    |--------------------------------------------------------------------------
    | Proponent
    |--------------------------------------------------------------------------
    */
    Route::prefix('proponent')->group(function () {

        Route::get('/objectives', ObjectiveList::class)
            ->name('proponent.objectives.index');
    });

    /*
    |--------------------------------------------------------------------------
    | Admin (Top Level)
    |--------------------------------------------------------------------------
    */
    Route::prefix('admin')->group(function () {

        // New Admin Dashboard with tabbed management
        Route::get('/manage', \App\Livewire\Admin\AdminDashboard::class)
            ->middleware(EnsureAdministrator::class)
            ->name('admin.manage');

        Route::get('/stratplan-manager', \App\Livewire\Admin\StrategicPlanManager::class)
            ->middleware(EnsureAdministrator::class)
            ->name('admin.stratplan-manager');

        Route::get('/users', \App\Livewire\Super\CreateAccount::class)
            ->middleware(EnsureSuperAdmin::class)
            ->name('superadmin.users');

        Route::get('/objectives/{id}', \App\Livewire\Admin\ObjectiveView::class)
            ->middleware(EnsureAdministrator::class)
            ->name('admin.objectives.show');

        Route::get('/approvals', \App\Livewire\Admin\Approvals::class)
            ->middleware(EnsureAdministrator::class)
            ->name('admin.approvals');

        Route::get('/ousec', \App\Livewire\Admin\OUSECDashboard::class)
            ->middleware(EnsureOUSEC::class)
            ->name('admin.ousec');

        Route::get('/audit', \App\Livewire\Super\AuditLogs::class)
            ->middleware(EnsureSuperAdmin::class)
            ->name('admin.audit');

        // Audit Export Routes
        Route::prefix('audit-export')->middleware(EnsureSuperAdmin::class)->group(function () {
            Route::get('/download', [\App\Http\Controllers\AuditExportController::class, 'download'])
                ->name('audit-export.download');
        });
    });

    /*
    |--------------------------------------------------------------------------
    | Reports
    |--------------------------------------------------------------------------
    */
    Route::prefix('reports')->group(function () {

        Route::get('/indicators', [ReportController::class, 'indicators'])
            ->name('reports.indicators');

        Route::get('/indicators/export/csv', [ReportController::class, 'exportIndicatorsCsv'])
            ->name('reports.indicators.csv');

        Route::get('/indicators/export/pdf', [ReportController::class, 'exportIndicatorsPdf'])
            ->name('reports.indicators.pdf');
    });

    /*
    |--------------------------------------------------------------------------
    | Indicators
    |--------------------------------------------------------------------------
    */
    Route::get('/indicators/intake', \App\Livewire\Indicators\CategoryIntake::class)
        ->name('indicators.intake');

    Route::get('/indicators/library', \App\Livewire\Indicators\Library::class)
        ->name('indicators.library');

    /*
    |--------------------------------------------------------------------------
    | Extras
    |--------------------------------------------------------------------------
    */
    Route::get('/chapters/{category}', \App\Livewire\Indicators\ChaptersIndex::class)
        ->name('chapters.index');

    Route::get('/chapters/{category}/{chapter}/indicators', \App\Livewire\Indicators\Library::class)
        ->name('objectives.index');
});
