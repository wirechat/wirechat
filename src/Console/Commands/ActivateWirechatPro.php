<?php

namespace Wirechat\Wirechat\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use JsonException;
use RuntimeException;
use Symfony\Component\Process\Process;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\password;
use function Laravel\Prompts\text;

class ActivateWirechatPro extends Command
{
    private const REPOSITORY_URL = 'https://wirechat.composer.sh';

    private const REPOSITORY_HOST = 'wirechat.composer.sh';

    protected $signature = 'wirechat:activate
        {--email= : The email address assigned to the license, or "unlock" for an unassigned license}
        {--license= : The Wirechat Pro license key}
        {--fingerprint= : Optional license fingerprint}
        {--composer=composer : Composer executable}
        {--skip-install : Configure Composer without installing Wirechat Pro}';

    protected $description = 'Activate Wirechat Pro and install the private Composer package';

    public function handle(): int
    {
        try {
            $email = trim((string) ($this->option('email') ?: text(
                label: 'Enter the email address associated with your license',
                required: true,
                hint: 'Use "unlock" if the license is not assigned to a licensee yet.'
            )));

            $licenseKey = trim((string) ($this->option('license') ?: password(
                label: 'Enter your Wirechat Pro license key',
                required: true,
                hint: 'Purchase a license key: https://corepine.dev/marketplace/wirechat'
            )));

            $fingerprint = $this->option('fingerprint') === null
                ? ''
                : trim((string) $this->option('fingerprint'));

            $this->writeAuthJson($email, $this->licensePassword($licenseKey, $fingerprint));
            $this->ensureComposerRepository();
        } catch (RuntimeException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->info('[✓] Composer credentials configured.');

        if ($this->option('skip-install')) {
            $this->line('Skipped package installation.');

            return self::SUCCESS;
        }

        return $this->installWirechatPro();
    }

    private function licensePassword(string $licenseKey, string $fingerprint): string
    {
        return $fingerprint === '' ? $licenseKey : "{$licenseKey}:{$fingerprint}";
    }

    private function writeAuthJson(string $username, string $password): void
    {
        $path = base_path('auth.json');
        $auth = $this->readJsonFile($path, missingDefault: []);

        if (! isset($auth['http-basic']) || ! is_array($auth['http-basic'])) {
            $auth['http-basic'] = [];
        }

        $auth['http-basic'][self::REPOSITORY_HOST] = [
            'username' => $username,
            'password' => $password,
        ];

        $this->writeJsonFile($path, $auth);
    }

    private function ensureComposerRepository(): void
    {
        $path = base_path('composer.json');

        if (! File::exists($path)) {
            throw new RuntimeException('No composer.json file was found in the project root.');
        }

        $composer = $this->readJsonFile($path);
        $repositories = $composer['repositories'] ?? [];

        if (! is_array($repositories)) {
            throw new RuntimeException('The composer.json repositories value must be an array or object.');
        }

        if ($this->hasWirechatProRepository($repositories)) {
            return;
        }

        if (array_is_list($repositories)) {
            $repositories[] = [
                'type' => 'composer',
                'url' => self::REPOSITORY_URL,
            ];
        } else {
            $repositories['wirechat-pro'] = [
                'type' => 'composer',
                'url' => self::REPOSITORY_URL,
            ];
        }

        $composer['repositories'] = $repositories;
        $this->writeJsonFile($path, $composer);
    }

    private function hasWirechatProRepository(array $repositories): bool
    {
        foreach ($repositories as $repository) {
            if (is_array($repository) && ($repository['url'] ?? null) === self::REPOSITORY_URL) {
                return true;
            }
        }

        return false;
    }

    private function installWirechatPro(): int
    {
        $composer = (string) $this->option('composer');
        $rootComposer = $this->readJsonFile(base_path('composer.json'));
        $hasFreePackage = isset($rootComposer['require']['wirechat/wirechat']);
        $commands = array_values(array_filter([
            $hasFreePackage ? "{$composer} remove wirechat/wirechat --no-update" : null,
            "{$composer} require wirechat/wirechat-pro -W",
        ]));

        if (! confirm(
            label: $hasFreePackage
                ? 'Wirechat Pro replaces wirechat/wirechat because both packages use the same namespace. Continue with the Composer package replacement?'
                : 'Continue with the Wirechat Pro Composer installation?',
            default: true,
            hint: implode(PHP_EOL, $commands)
        )) {
            $this->line('Run these commands manually when you are ready:');

            foreach ($commands as $command) {
                $this->line($command);
            }

            return self::SUCCESS;
        }

        if ($hasFreePackage) {
            $this->line('Removing the free package from composer.json...');
            $removeExitCode = $this->runComposer([$composer, 'remove', 'wirechat/wirechat', '--no-update']);

            if ($removeExitCode !== self::SUCCESS) {
                return $removeExitCode;
            }
        }

        $this->line('Installing Wirechat Pro...');
        $requireExitCode = $this->runComposer([$composer, 'require', 'wirechat/wirechat-pro', '-W']);

        if ($requireExitCode !== self::SUCCESS) {
            return $requireExitCode;
        }

        $this->info('[✓] Wirechat Pro activated.');
        $this->line('If this is a new Wirechat installation, run: php artisan wirechat:install');

        return self::SUCCESS;
    }

    /**
     * @param  array<int, string>  $command
     */
    private function runComposer(array $command): int
    {
        $process = new Process($command, base_path());
        $process->setTimeout(null);
        $process->run(function (string $type, string $buffer): void {
            $this->output->write($buffer);
        });

        return $process->getExitCode() ?? self::FAILURE;
    }

    private function readJsonFile(string $path, ?array $missingDefault = null): array
    {
        if (! File::exists($path)) {
            if ($missingDefault !== null) {
                return $missingDefault;
            }

            throw new RuntimeException("The JSON file [{$path}] does not exist.");
        }

        try {
            $contents = json_decode(File::get($path), true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new RuntimeException("The JSON file [{$path}] contains invalid JSON: {$exception->getMessage()}");
        }

        if (! is_array($contents)) {
            throw new RuntimeException("The JSON file [{$path}] must contain a JSON object.");
        }

        return $contents;
    }

    private function writeJsonFile(string $path, array $contents): void
    {
        try {
            File::put($path, json_encode($contents, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR).PHP_EOL);
        } catch (JsonException $exception) {
            throw new RuntimeException("Unable to write [{$path}]: {$exception->getMessage()}");
        }
    }
}
