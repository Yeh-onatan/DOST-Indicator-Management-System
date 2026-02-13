<?php

namespace App\Providers;

use App\Actions\Fortify\CreateNewUser;
use App\Actions\Fortify\ResetUserPassword;
use App\Models\User;
use App\Services\AuditService;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Laravel\Fortify\Fortify;

class FortifyServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Fortify::username('username');

    // Force username-only authentication with audit logging
    Fortify::authenticateUsing(function (Request $request) {
        $user = User::where('username', $request->input('username'))->first();

        if ($user && Hash::check($request->input('password'), $user->password)) {
            // Update last login time
            $user->update(['last_login_at' => now()]);

            // Log successful login
            AuditService::logLogin($request);

            // Check for expired password
            if ($user->isPasswordExpired()) {
                session()->flash('password_expired', true);
            }

            return $user;
        }

        // Log failed login attempt
        AuditService::logFailedLogin($request);

        return null;
    });

        $this->configureActions();
        $this->configureViews();
        $this->configureRateLimiting();
    }

    private function configureActions(): void
    {
        Fortify::resetUserPasswordsUsing(ResetUserPassword::class);
        Fortify::createUsersUsing(CreateNewUser::class);
    }

    private function configureViews(): void
    {
        Fortify::loginView(fn () => view('livewire.auth.login'));

        // If you don't want these screens, disable the features in config/fortify.php / jetstream.php
        // Fortify::registerView(fn () => view('livewire.auth.register'));
        // Fortify::requestPasswordResetLinkView(fn () => view('livewire.auth.forgot-password'));
        // Fortify::resetPasswordView(fn () => view('livewire.auth.reset-password'));

        Fortify::verifyEmailView(fn () => view('livewire.auth.verify-email'));
        // Two-Factor disabled
        // Fortify::twoFactorChallengeView(fn () => view('livewire.auth.two-factor-challenge'));
        Fortify::confirmPasswordView(fn () => view('livewire.auth.confirm-password'));
    }

    private function configureRateLimiting(): void
    {
        // Two-Factor disabled
        // RateLimiter::for('two-factor', function (Request $request) {
        //     return Limit::perMinute(5)->by($request->session()->get('login.id'));
        // });

        RateLimiter::for('login', function (Request $request) {
            $throttleKey = Str::transliterate(
                Str::lower($request->input(Fortify::username())).'|'.$request->ip()
            );

            return Limit::perMinute(5)->by($throttleKey);
        });

        // Rate limit for password reset attempts
        // OWASP/PCI DSS: Limit password reset to prevent account enumeration
        RateLimiter::for('password-reset', function (Request $request) {
            return Limit::perHour(3)->by($request->ip());
        });

        // Rate limit for data export operations
        // Prevents data scraping and excessive server load
        RateLimiter::for('export', function (Request $request) {
            return Limit::perHour(10)->by($request->user()?->id ?: $request->ip());
        });

        // Rate limit for bulk operations
        // Prevents abuse of bulk create/update/delete
        RateLimiter::for('bulk', function (Request $request) {
            return Limit::perHour(20)->by($request->user()?->id ?: $request->ip());
        });

        // Rate limit for API-like actions
        // General purpose limiter for AJAX/Livewire actions
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });

        // Rate limit for approval/rejection actions
        // Prevents rapid automated approvals
        RateLimiter::for('approval', function (Request $request) {
            return Limit::perMinute(10)->by($request->user()?->id ?: $request->ip());
        });
    }
}
