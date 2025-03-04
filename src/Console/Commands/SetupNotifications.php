<?php

namespace Namu\WireChat\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class SetupNotifications extends Command
{
    protected $signature = 'wirechat:setup-notifications';

    protected $description = 'Setup Wirechat service worker for notifications';

    public function handle()
    {
        $publicSW = public_path('sw.js');
        $wirechatSW = public_path('js/wirechat/sw.js');

        $wirechatServiceWorkerStub = 'ServiceWorkerJsScript.stub';
        $mainServiceWorkerStub = 'MainServiceWorkerJsScript.stub';

        $this->comment('Setting up Notifications...');

        // Ensure js/wirechat directory exists
        if (! File::exists(public_path('js/wirechat'))) {
            File::makeDirectory(public_path('js/wirechat'), 0755, true);
        }

        // Copy Wirechat SW script if it doesn't exist
        $this->info('Creating Wirechat service worker script...');

        if (File::exists($wirechatSW)) {
            if ($this->confirm('Wirechat service worker script already exists at `js/wirechat/sw.js`. Do you want to overwrite it?', false)) {
                File::put($wirechatSW, $this->getStub($wirechatServiceWorkerStub));
                $this->info('✅ Wirechat service worker script successfully overwritten at `js/wirechat/sw.js`.');
            } else {
                $this->info('Existing Wirechat service worker was not overwritten.');
            }
        } else {
            File::put($wirechatSW, $this->getStub($wirechatServiceWorkerStub));
            $this->info('✅ Wirechat service worker script successfully created at `js/wirechat/sw.js`.');
        }

        $this->newLine();

        $this->comment('Creating main service worker script...');

        if (File::exists($publicSW)) {
            $this->error('⚠️ A service worker (sw.js) already exists in the public directory.');
            $this->warn('To use Wirechat notifications, add the following at the top of your service worker file:');
            $this->line("`importScripts('/js/wirechat/sw.js');`\n");
        } else {
            File::put($publicSW, $this->getStub($mainServiceWorkerStub));
            $this->info('✅ Created `sw.js` in the public directory.');
        }

        $this->info('✅ Wirechat notifications setup complete!');
        $this->line("Note: If you're already using a custom service worker in your application, you need to manually add `importScripts('/js/wirechat/sw.js');` to your existing service worker file and update the notifications.main_sw_script value in config/wirechat.php to point to your service worker file.");
        $this->newLine();

        $this->comment('Finally, ensure that `notifications.enabled` is set to true in your Wirechat config.');
    }

    protected function getStub(string $stub)
    {
        return file_get_contents(__DIR__."/../../../stubs/{$stub}");
    }
}
