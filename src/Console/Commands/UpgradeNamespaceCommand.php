<?php

namespace Wirechat\Wirechat\Console\Commands;

use Illuminate\Console\Command;

class UpgradeNamespaceCommand extends Command
{
    protected $signature = 'wirechat:upgrade-namespace-to-v0.3x {--dry-run : Show what would be done without making changes}';

    protected $description = 'Upgrade Wirechat namespace from Namu\WireChat to Wirechat\Wirechat';

    public function handle()
    {
        $this->info('Starting Wirechat namespace upgrade...');
        $this->info('Searching...');

        $basePath = base_path();
        $files = [];
        $isWindows = strtoupper(PHP_OS) === 'WINNT' || strtoupper(PHP_OS) === 'WIN32';
        $sedFlag = $isWindows ? '' : (strtoupper(PHP_OS) === 'DARWIN' ? "''" : '');
        $findCommand = "find . -type f -name '*.php' -not -path './vendor/*' -not -path './storage/*' -exec grep -l -E 'Namu\\\\WireChat|Namu\\\\Wirechat' {} \;";

        // Check if find is available
        exec('command -v find >/dev/null 2>&1', $output, $findExists);
        if ($findExists !== 0 && $isWindows) {
            $this->error('This command requires find/sed (available in Git Bash or WSL on Windows). Please install Git Bash or WSL, or manually update Namu\\WireChat to Wirechat\\Wirechat.');

            return 1;
        }

        exec($findCommand, $files);
        $files = array_map(fn ($file) => str_replace($basePath.DIRECTORY_SEPARATOR, '', $file), $files);

        if ($this->option('dry-run')) {
            if (empty($files)) {
                $this->info('Dry run: No files found with Namu\\WireChat or Namu\\Wirechat to update.');
            } else {
                $this->info('Dry run: Files that would be updated:');
                foreach ($files as $file) {
                    $this->info($file);
                }
            }

            return 0;
        }

        $sedCommand = "find . -type f -name '*.php' -not -path './vendor/*' -not -path './storage/*' -exec sed -i $sedFlag -e 's/Namu\\\\WireChat/Wirechat\\\\Wirechat/g' -e 's/Namu\\\\Wirechat/Wirechat\\\\Wirechat/g' {} \;";
        exec($sedCommand);

        if (empty($files)) {
            $this->info('No files found with Namu\\WireChat or Namu\\Wirechat to update.');
        } else {
            $this->info('Updated namespaces in the following files:');
            foreach ($files as $file) {
                $this->info($file);
            }
        }
    }
}
