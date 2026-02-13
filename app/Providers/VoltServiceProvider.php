<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Livewire\Volt\Volt;

class VoltServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Prevent errors when Volt isn't fully loaded yet
        if (! class_exists(\Livewire\Volt\Volt::class)) {
            return;
        }

        Volt::mount([
            config('livewire.view_path', resource_path('views/livewire')),
            resource_path('views/pages'),
        ]);

        // Layout is applied per-page; Volt does not support a global layout setter here.
    }
}
