<?php

namespace Softpro\Core\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class InstallCommand extends Command
{
    protected $signature = 'softpro:install';
    protected $description = 'Install and configure the Softpro Core package';

    public function handle()
    {
        $this->info('Installing Softpro Core...');

        // 1. Publish Config
        $this->publishConfig();

        // 2. Publish Migrations
        $this->publishMigrations();

        // 3. Publish Views
        $this->publishViews();

        // 4. Publish Assets (Vue Components)
        $this->publishAssets();

        // 4. Update Vite Config
        $this->updateViteConfig();

        // 5. Update app.js
        $this->updateAppJs();

        // 6. Check dependencies
        $this->checkDependencies();

        $this->info('Softpro Core installed successfully!');
        $this->info('Please run "npm install" and "npm run dev" to compile the assets.');
    }

    protected function publishConfig()
    {
        $this->call('vendor:publish', [
            '--tag' => 'softpro-core-config',
            '--force' => true,
        ]);
    }

    protected function publishMigrations()
    {
        $this->call('vendor:publish', [
            '--tag' => 'softpro-core-migrations',
        ]);
    }

    protected function publishViews()
    {
        $this->call('vendor:publish', [
            '--tag' => 'softpro-core-views',
        ]);
    }

    protected function publishAssets()
    {
        $this->info('Publishing Vue components, layouts, and components...');
        $source = __DIR__ . '/../../../resources/js';
        $destination = resource_path('js/vendor/softpro-core');

        if (!File::exists($destination)) {
            File::makeDirectory($destination, 0755, true);
        }

        File::copyDirectory($source, $destination);
        $this->line('Assets published to resources/js/vendor/softpro-core');
    }

    protected function updateViteConfig()
    {
        $path = base_path('vite.config.js');
        if (!File::exists($path)) return;

        $content = File::get($path);
        
        if (str_contains($content, '@softpro-core')) {
            $this->line('Vite alias already exists.');
            return;
        }

        $aliasMatch = '/alias:\s*{/';
        $replacement = "alias: {\n            '@softpro-core': '/resources/js/vendor/softpro-core',";
        
        if (preg_match($aliasMatch, $content)) {
            $content = preg_replace($aliasMatch, $replacement, $content);
            File::put($path, $content);
            $this->line('Vite config updated with @softpro-core alias.');
        }
    }

    protected function updateAppJs()
    {
        $path = resource_path('js/app.js');
        if (!File::exists($path)) return;

        $content = File::get($path);

        if (str_contains($content, 'vendor/softpro-core')) {
            $this->line('app.js already configured for Softpro Core.');
            return;
        }

        // Add package components to import.meta.glob
        $globPattern = "/import\.meta\.glob\(\[\s*/";
        $replacement = "import.meta.glob([\n    '/resources/js/vendor/softpro-core/Pages/**/*.vue', ";

        if (preg_match($globPattern, $content)) {
            $content = preg_replace($globPattern, $replacement, $content);
            File::put($path, $content);
            $this->line('app.js updated to include package components.');
        }
    }

    protected function checkDependencies()
    {
        $path = base_path('package.json');
        if (!File::exists($path)) return;

        $content = json_decode(File::get($path), true);
        $dependencies = array_merge($content['dependencies'] ?? [], $content['devDependencies'] ?? []);

        $required = [
            'admin-lte' => '^4.0.0-rc7',
            'bootstrap' => '^5.3.0',
            'bootstrap-icons' => '^1.11.0',
            '@inertiajs/vue3' => '^1.0.0',
            'vue' => '^3.3.0',
            'axios' => '^1.0.0',
            'ziggy-js' => '^1.8.0',
        ];

        $missing = [];
        foreach ($required as $pkg => $ver) {
            if (!isset($dependencies[$pkg])) {
                $missing[] = $pkg;
            }
        }

        if (!empty($missing)) {
            $this->warn('The following NPM dependencies are missing: ' . implode(', ', $missing));
            $this->line('You can install them by running:');
            $this->info('npm install ' . implode(' ', $missing));
        }
    }
}

