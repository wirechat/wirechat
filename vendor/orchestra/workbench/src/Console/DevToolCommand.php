<?php

namespace Orchestra\Workbench\Console;

use Composer\InstalledVersions;
use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Composer;
use Orchestra\Testbench\Foundation\Console\Actions\EnsureDirectoryExists;
use Orchestra\Testbench\Foundation\Console\Actions\GeneratesFile;
use Orchestra\Workbench\Events\InstallEnded;
use Orchestra\Workbench\Events\InstallStarted;
use Orchestra\Workbench\Workbench;
use Symfony\Component\Console\Attribute\AsCommand;

use function Illuminate\Filesystem\join_paths;
use function Orchestra\Testbench\package_path;

#[AsCommand(name: 'workbench:devtool', description: 'Configure Workbench for package development')]
class DevToolCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'workbench:devtool
        {--force : Overwrite any existing files}
        {--skip-install : Skipped Workbench installation}';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(Filesystem $filesystem)
    {
        $workingPath = package_path();

        event(new InstallStarted($this->input, $this->output, $this->components));

        $this->prepareWorkbenchDirectories($filesystem, $workingPath);
        $this->prepareWorkbenchNamespaces($filesystem, $workingPath);

        if (! $this->option('skip-install')) {
            $this->call('workbench:install', [
                '--force' => $this->option('force'),
                '--skip-devtool' => true,
            ]);
        }

        return tap(Command::SUCCESS, function ($exitCode) use ($filesystem, $workingPath) {
            event(new InstallEnded($this->input, $this->output, $this->components, $exitCode));

            (new Composer($filesystem))
                ->setWorkingPath($workingPath)
                ->dumpAutoloads();
        });
    }

    /**
     * Prepare workbench directories.
     */
    protected function prepareWorkbenchDirectories(Filesystem $filesystem, string $workingPath): void
    {
        $workbenchWorkingPath = join_paths($workingPath, 'workbench');

        (new EnsureDirectoryExists(
            filesystem: $filesystem,
            components: $this->components,
        ))->handle(
            Collection::make([
                'app/Models',
                'routes',
                'resources/views',
                'database/factories',
                'database/migrations',
                'database/seeders',
            ])->map(static fn ($directory) => "{$workbenchWorkingPath}/{$directory}")
        );

        $this->callSilently('make:provider', [
            'name' => 'WorkbenchServiceProvider',
            '--preset' => 'workbench',
        ]);

        $this->callSilently('make:seeder', [
            'name' => 'DatabaseSeeder',
            '--preset' => 'workbench',
        ]);

        foreach (['api', 'console', 'web'] as $route) {
            (new GeneratesFile(
                filesystem: $filesystem,
                components: $this->components,
                force: (bool) $this->option('force'),
            ))->handle(
                (string) realpath(join_paths(__DIR__, 'stubs', 'routes', "{$route}.php")),
                join_paths($workbenchWorkingPath, 'routes', "{$route}.php")
            );
        }
    }

    /**
     * Prepare workbench namespace to `composer.json`.
     */
    protected function prepareWorkbenchNamespaces(Filesystem $filesystem, string $workingPath): void
    {
        $composer = (new Composer($filesystem))->setWorkingPath($workingPath);

        $composer->modify(function (array $content) use ($filesystem) {
            return $this->appendScriptsToComposer(
                $this->appendAutoloadDevToComposer($content, $filesystem), $filesystem
            );
        });
    }

    /**
     * Append `scripts` to `composer.json`.
     */
    protected function appendScriptsToComposer(array $content, Filesystem $filesystem): array
    {
        $hasScriptsSection = \array_key_exists('scripts', $content);
        $hasTestbenchDusk = InstalledVersions::isInstalled('orchestra/testbench-dusk');

        if (! $hasScriptsSection) {
            $content['scripts'] = [];
        }

        $postAutoloadDumpScripts = array_filter([
            '@clear',
            '@prepare',
            $hasTestbenchDusk ? '@dusk:install-chromedriver' : null,
        ]);

        if (! \array_key_exists('post-autoload-dump', $content['scripts'])) {
            $content['scripts']['post-autoload-dump'] = $postAutoloadDumpScripts;
        } else {
            $content['scripts']['post-autoload-dump'] = array_values(array_unique([
                ...$postAutoloadDumpScripts,
                ...Arr::wrap($content['scripts']['post-autoload-dump']),
            ]));
        }

        $content['scripts']['clear'] = '@php vendor/bin/testbench package:purge-skeleton --ansi';
        $content['scripts']['prepare'] = '@php vendor/bin/testbench package:discover --ansi';

        if ($hasTestbenchDusk) {
            $content['scripts']['dusk:install-chromedriver'] = '@php vendor/bin/dusk-updater detect --auto-update --ansi';
        }

        $content['scripts']['build'] = '@php vendor/bin/testbench workbench:build --ansi';
        $content['scripts']['serve'] = [
            'Composer\\Config::disableProcessTimeout',
            '@build',
            '@php vendor/bin/testbench serve',
        ];

        if (! \array_key_exists('lint', $content['scripts'])) {
            $lintScripts = [];

            if (InstalledVersions::isInstalled('laravel/pint')) {
                $lintScripts[] = '@php vendor/bin/pint';
            } elseif ($filesystem->exists(Workbench::packagePath('pint.json'))) {
                $lintScripts[] = 'pint';
            }

            if (InstalledVersions::isInstalled('phpstan/phpstan')) {
                $lintScripts[] = '@php vendor/bin/phpstan analyse';
            }

            if (\count($lintScripts) > 0) {
                $content['scripts']['lint'] = $lintScripts;
            }
        }

        if (
            $filesystem->exists(Workbench::packagePath('phpunit.xml'))
            || $filesystem->exists(Workbench::packagePath('phpunit.xml.dist'))
        ) {
            if (! \array_key_exists('test', $content['scripts'])) {
                $content['scripts']['test'][] = InstalledVersions::isInstalled('pestphp/pest')
                    ? '@php vendor/bin/pest'
                    : '@php vendor/bin/phpunit';
            }
        }

        return $content;
    }

    /**
     * Append `autoload-dev` to `composer.json`.
     */
    protected function appendAutoloadDevToComposer(array $content, Filesystem $filesystem): array
    {
        /** @var array{autoload-dev?: array{psr-4?: array<string, string>}} $content */
        if (! \array_key_exists('autoload-dev', $content)) {
            $content['autoload-dev'] = [];
        }

        /** @var array{autoload-dev: array{psr-4?: array<string, string>}} $content */
        if (! \array_key_exists('psr-4', $content['autoload-dev'])) {
            $content['autoload-dev']['psr-4'] = [];
        }

        $namespaces = [
            'Workbench\\App\\' => 'workbench/app/',
            'Workbench\\Database\\Factories\\' => 'workbench/database/factories/',
            'Workbench\\Database\\Seeders\\' => 'workbench/database/seeders/',
        ];

        foreach ($namespaces as $namespace => $path) {
            if (! \array_key_exists($namespace, $content['autoload-dev']['psr-4'])) {
                $content['autoload-dev']['psr-4'][$namespace] = $path;

                $this->components->task(sprintf(
                    'Added [%s] for [%s] to Composer', $namespace, $path
                ));
            } else {
                $this->components->twoColumnDetail(
                    sprintf('Composer already contain [%s] namespace', $namespace),
                    '<fg=yellow;options=bold>SKIPPED</>'
                );
            }
        }

        return $content;
    }
}
