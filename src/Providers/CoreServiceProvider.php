<?php

namespace Softpro\Core\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Route;

class CoreServiceProvider extends ServiceProvider
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
        $this->loadMigrationsFrom(__DIR__ . '/../Database/Migrations');
        $this->loadViewsFrom(__DIR__ . '/../../resources/views', 'softpro-core');
        
        $this->registerRoutes();
    }

    /**
     * Register the package routes.
     */
    protected function registerRoutes(): void
    {
        // If we are in a Tenancy environment, routes are handled by the main app's routes/tenant.php
        // If we are in Standalone mode, we register them here.
        if (!class_exists(\Stancl\Tenancy\Tenancy::class) || !app()->bound(\Stancl\Tenancy\Tenancy::class)) {
            Route::middleware('web')
                ->group(__DIR__ . '/../../routes/web.php');
        }
    }
}
