<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Eloquent\Model;
use App\Services\NotificationService;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(NotificationService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        /**
         * PERFORMANCE GUARD: Prevents N+1 query problems.
         * If you forget to use ->with() in your dashboard, the app will throw an error
         * in development, forcing you to write efficient code for your 4GB RAM.
         */
        Model::preventLazyLoading(! app()->isProduction());

        /**
         * 🚫 DO NOT touch the database while:
         * - Composer is running
         * - Migrations are running
         * - Docker image is building
         * - Package discovery is running
         */
        if (app()->runningInConsole()) {
            return;
        }

        try {
            if (Schema::hasTable('admin_settings')) {

                $settings = \App\Models\AdminSetting::first();

                if ($settings?->timezone) {
                    config(['app.timezone' => $settings->timezone]);
                    @date_default_timezone_set($settings->timezone);
                }

                if ($settings?->locale) {
                    app()->setLocale($settings->locale);
                }
            }
        } catch (\Throwable $e) {
            // intentionally silent to avoid production crash
        }
    }
}