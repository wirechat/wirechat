<?php

namespace Namu\WireChat\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;

class InstallWireChat extends Command
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

    private function publishConfiguration($forcePublish = false)
    {
        $params = [
            '--provider' => "Namu\WireChat\WireChatServiceProvider",
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
            '--provider' => "Namu\WireChat\WireChatServiceProvider",
            '--tag' => 'wirechat-migrations',
        ]);
    }
}
