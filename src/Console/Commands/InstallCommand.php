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
        $this->info('🚀 Installing Softpro Core...');

        // 1. Install Composer Dependencies
        $this->installComposerDependencies();

        // 2. Publish Everything
        $this->publishConfig();
        $this->publishMigrations();
        $this->publishViews();
        $this->publishAssets();
        $this->publishCss();

        // 3. Update Configurations
        $this->updateViteConfig();
        $this->updateAppJs();
        $this->updateAppCss();

        // 4. Install NPM Dependencies
        $this->installNpmDependencies();

        $this->info('✅ Softpro Core installed successfully!');
        $this->info('Please run "npm run dev" to compile the assets.');
    }

    protected function installComposerDependencies()
    {
        $this->info('Checking Composer dependencies...');
        $dependencies = ['inertiajs/inertia-laravel', 'tightenco/ziggy'];
        
        foreach ($dependencies as $dependency) {
            if (!str_contains(file_get_contents(base_path('composer.json')), $dependency)) {
                $this->line("Installing {$dependency}...");
                shell_exec("composer require {$dependency}");
            }
        }
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
        $this->info('Publishing Vue components...');
        $source = __DIR__ . '/../../../resources/js';
        $destination = resource_path('js/vendor/softpro-core');

        if (!File::exists($destination)) {
            File::makeDirectory($destination, 0755, true);
        }

        File::copyDirectory($source, $destination);
    }

    protected function publishCss()
    {
        $this->info('Publishing package CSS...');
        $source = __DIR__ . '/../../../resources/css/softpro-core.css';
        $destination = resource_path('css/softpro-core.css');

        if (!File::exists(resource_path('css'))) {
            File::makeDirectory(resource_path('css'), 0755, true);
        }

        File::copy($source, $destination);
    }

    protected function updateViteConfig()
    {
        $path = base_path('vite.config.js');
        if (!File::exists($path)) {
            $this->createDefaultViteConfig($path);
            return;
        }

        $content = File::get($path);
        
        if (str_contains($content, '@softpro-core')) {
            return;
        }

        // If 'resolve' block exists
        if (preg_match('/resolve:\s*{/', $content)) {
            if (preg_match('/alias:\s*{/', $content)) {
                $content = preg_replace('/alias:\s*{/', "alias: {\n            '@softpro-core': '/resources/js/vendor/softpro-core',", $content);
            } else {
                $content = preg_replace('/resolve:\s*{/', "resolve: {\n        alias: {\n            '@softpro-core': '/resources/js/vendor/softpro-core',\n        },", $content);
            }
        } else {
            // Add resolve block after plugins
            $content = preg_replace('/plugins:\s*\[/', "plugins: [\n    ],\n    resolve: {\n        alias: {\n            '@softpro-core': '/resources/js/vendor/softpro-core',\n        },\n    ", $content);
        }

        File::put($path, $content);
        $this->line('Updated vite.config.js with @softpro-core alias.');
    }

    protected function createDefaultViteConfig($path)
    {
        $template = <<<EOT
import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
        }),
        vue({
            template: {
                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false,
                },
            },
        }),
    ],
    resolve: {
        alias: {
            '@softpro-core': '/resources/js/vendor/softpro-core',
        },
    },
});
EOT;
        File::put($path, $template);
        $this->line('Created default vite.config.js.');
    }

    protected function updateAppJs()
    {
        $path = resource_path('js/app.js');
        $content = File::exists($path) ? File::get($path) : '';

        if (str_contains($content, 'vendor/softpro-core')) {
            return;
        }

        if (empty(trim($content)) || !str_contains($content, 'createInertiaApp')) {
            $this->createDefaultAppJs($path);
            return;
        }

        // Inject package components into existing glob
        if (preg_match('/import\.meta\.glob\(\[\s*/', $content)) {
            $content = preg_replace('/import\.meta\.glob\(\[\s*/', "import.meta.glob([\n    '/resources/js/vendor/softpro-core/Pages/**/*.vue', ", $content);
        }

        // Add CSS import if missing
        if (!str_contains($content, 'softpro-core.css')) {
            $content = "import '../css/softpro-core.css';\n" . $content;
        }

        File::put($path, $content);
    }

    protected function createDefaultAppJs($path)
    {
        $template = <<<EOT
import './bootstrap';
import '../css/softpro-core.css';

import { createApp, h } from 'vue';
import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { ZiggyVue } from '../../vendor/tightenco/ziggy';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

createInertiaApp({
    title: (title) => `\${title} - \${appName}`,
    resolve: (name) => {
        const pages = import.meta.glob([
            './Pages/**/*.vue',
            '/resources/js/vendor/softpro-core/Pages/**/*.vue'
        ]);
        
        // Try to find in app first, then package
        let page = pages[`./Pages/\${name}.vue`];
        if (!page) {
            page = pages[`/resources/js/vendor/softpro-core/Pages/\${name}.vue`];
        }
        
        return page();
    },
    setup({ el, App, props, plugin }) {
        return createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(ZiggyVue)
            .mount(el);
    },
    progress: {
        color: '#4B5563',
    },
});
EOT;
        if (!File::exists(resource_path('js'))) {
            File::makeDirectory(resource_path('js'), 0755, true);
        }
        File::put($path, $template);
        $this->line('Configured app.js for Inertia and Softpro Core.');
    }

    protected function updateAppCss()
    {
        $path = resource_path('css/app.css');
        if (!File::exists($path)) {
            File::put($path, "@import './softpro-core.css';\n");
            return;
        }

        $content = File::get($path);
        if (!str_contains($content, 'softpro-core.css')) {
            File::put($path, "@import './softpro-core.css';\n" . $content);
        }
    }

    protected function installNpmDependencies()
    {
        $this->info('Installing NPM dependencies...');
        
        $dependencies = [
            'admin-lte@4.0.0-rc7',
            'bootstrap@5.3.3',
            'bootstrap-icons@1.11.3',
            '@inertiajs/vue3@^1.0.0',
            '@vitejs/plugin-vue@^5.0.0',
            'vue@^3.4.0',
            'axios@^1.6.0',
            'ziggy-js@^2.0.0',
            'cropperjs@1.6.1'
        ];

        $command = 'npm install ' . implode(' ', $dependencies) . ' --save';
        $this->line("Running: {$command}");
        shell_exec($command);
    }
}

