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
        $this->mergeConfigFrom(
            __DIR__ . '/../../config/softpro-core.php', 'softpro-core'
        );
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Config
        $this->publishes([
            __DIR__ . '/../../config/softpro-core.php' => config_path('softpro-core.php'),
        ], 'softpro-core-config');

        // Migrations
        $this->loadMigrationsFrom(__DIR__ . '/../Database/Migrations');
        $this->publishes([
            __DIR__ . '/../Database/Migrations' => database_path('migrations'),
        ], 'softpro-core-migrations');

        // Views
        $this->loadViewsFrom(__DIR__ . '/../../resources/views', 'softpro-core');
        $this->publishes([
            __DIR__ . '/../../resources/views' => resource_path('views/vendor/softpro-core'),
        ], 'softpro-core-views');

        // Assets
        $this->publishes([
            __DIR__ . '/../../resources/js' => resource_path('js/vendor/softpro-core'),
        ], 'softpro-core-assets');
        
        $this->registerRoutes();
        $this->registerCommands();
    }

    /**
     * Register the package routes.
     */
    protected function registerRoutes(): void
    {
        if (config('softpro-core.standalone')) {
            Route::middleware(['web', \Softpro\Core\Http\Middleware\SetStandaloneRootView::class])
                ->prefix(config('softpro-core.route_prefix'))
                ->group(__DIR__ . '/../../routes/web.php');
        }
    }

    /**
     * Register console commands.
     */
    protected function registerCommands(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                \Softpro\Core\Console\Commands\InstallCommand::class,
            ]);
        }
    }
}

