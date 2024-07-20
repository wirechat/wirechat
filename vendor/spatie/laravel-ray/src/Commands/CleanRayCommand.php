<?php

namespace Spatie\LaravelRay\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Process;

class CleanRayCommand extends Command
{
    protected $signature = 'ray:clean';

    protected $description = 'Remove all Ray calls from your codebase.';

    public function handle()
    {
        $directories = [
            'app',
            'config',
            'database',
            'public',
            'resources',
            'routes',
            'tests',
        ];

        $this->withProgressBar($directories, function ($directory) {
            $result = Process::run('./vendor/bin/remove-ray.sh ' . $directory);

            if (! $result->successful()) {
                $this->error($result->errorOutput());

                return;
            }
        });

        $this->newLine(2);
        $this->info('All Ray calls have been removed from your codebase.');
    }
}
