<?php

namespace Wirechat\Wirechat\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;

class InstallWirechat extends Command
{
    protected $signature = 'wirechat:install';

    protected $description = 'Install the Wirechat package and publish necessary files';

    public function handle()
    {
        $this->comment('Installing Wirechat Package...');

        // Publish configuration
        $this->comment('Publishing configuration...');
        if (! $this->configExists('wirechat.php')) {
            $this->publishConfiguration();
            $this->info('[✓] Published configuration');
        } else {
            if ($this->shouldOverwriteConfig()) {
                $this->comment('Overwriting configuration file...');
                $this->publishConfiguration(true);
            } else {
                $this->info('Existing configuration was not overwritten');
            }
        }

        // create storage sym link
        $this->comment('Creating storage symlink...');

        Artisan::call('storage:link');
        $this->info('[✓] Storage linked.');
        // Publish migrations
        $this->comment('Publishing migrations...');
        $this->publishMigrations();
        $this->info('[✓] Published migrations');

        // Create deafult panel
        $this->createDefaultPanel();

        $this->comment('Configuring frontend stylesheet...');
        $this->configureStylesheet();

        $this->info('[✓] Wirechat Package installed successfully.');
    }

    private function configExists($fileName)
    {
        return File::exists(config_path($fileName));
    }

    private function shouldOverwriteConfig()
    {
        return $this->confirm(
            'Config file already exists. Do you want to overwrite it?',
            false
        );
    }

    private function createDefaultPanel(): void
    {

        $this->call('make:wirechat-panel', [
            'id' => 'chats',
        ]);

    }

    private function publishConfiguration($forcePublish = false)
    {
        $params = [
            '--provider' => "Wirechat\Wirechat\WirechatServiceProvider",
            '--tag' => 'wirechat-config',
        ];

        if ($forcePublish) {
            $params['--force'] = true;
        }
        $this->call('vendor:publish', $params);
    }

    private function publishMigrations()
    {
        $this->call('vendor:publish', [
            '--provider' => "Wirechat\Wirechat\WirechatServiceProvider",
            '--tag' => 'wirechat-migrations',
        ]);
    }

    private function configureStylesheet(): void
    {
        $cssPath = resource_path('css/app.css');
        $freeImport = "@import '../../vendor/wirechat/wirechat/resources/css/app.css';";
        $knownImports = [
            'vendor/wirechat/wirechat/resources/css/app.css',
            'vendor/wirechat/wirechat-pro/resources/css/app.css',
        ];

        if (! File::exists($cssPath)) {
            $this->warn('resources/css/app.css was not found. Add this import to your Tailwind CSS file:');
            $this->line($freeImport);

            return;
        }

        $contents = File::get($cssPath);

        foreach ($knownImports as $knownImport) {
            if (str_contains($contents, $knownImport)) {
                $this->info('[✓] Wirechat stylesheet already configured.');

                return;
            }
        }

        File::put($cssPath, $this->addStylesheetImport($contents, $freeImport));

        $this->info('[✓] Added Wirechat stylesheet import to resources/css/app.css');
    }

    private function addStylesheetImport(string $contents, string $import): string
    {
        $contents = rtrim($contents);
        $lines = preg_split('/\R/', $contents) ?: [];

        foreach ($lines as $index => $line) {
            if (preg_match("/^\\s*@import\\s+['\"]tailwindcss['\"]\\s*;/", $line) === 1) {
                array_splice($lines, $index + 1, 0, $import);

                return implode(PHP_EOL, $lines).PHP_EOL;
            }
        }

        return $import.PHP_EOL.PHP_EOL.$contents.PHP_EOL;
    }
}
