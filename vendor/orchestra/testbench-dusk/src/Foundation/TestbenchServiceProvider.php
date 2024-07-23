<?php

namespace Orchestra\Testbench\Dusk\Foundation;

use Composer\InstalledVersions;
use Illuminate\Foundation\Console\AboutCommand;
use Illuminate\Support\ServiceProvider;

use function Orchestra\Testbench\package_path;

class TestbenchServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        AboutCommand::add('Testbench', fn () => [
            'Core Version' => InstalledVersions::getPrettyVersion('orchestra/testbench-core'),
            'Dusk Version' => InstalledVersions::getPrettyVersion('orchestra/testbench-dusk'),
            'Skeleton Path' => str_replace(package_path(), '', $this->app->basePath()),
            'Version' => InstalledVersions::getPrettyVersion('orchestra/testbench'),
        ]);
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        if (file_exists($this->app->basePath('migrations'))) {
            $this->loadMigrationsFrom($this->app->basePath('migrations'));
        }

        if ($this->app->runningInConsole()) {
            $this->commands([
                Console\CreateSqliteDbCommand::class,
                Console\DropSqliteDbCommand::class,
                Console\DuskCommand::class,
                Console\PurgeCommand::class,
                Console\PurgeSkeletonCommand::class,
                Console\ServeCommand::class,
            ]);
        }
    }
}
