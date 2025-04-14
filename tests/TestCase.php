<?php

namespace Namu\WireChat\Tests;

use Christophrumpel\MissingLivewireAssertions\MissingLivewireAssertionsServiceProvider;
use Illuminate\Config\Repository;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\Concerns\InteractsWithViews;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\View;
use Livewire\LivewireServiceProvider;
use Namu\WireChat\WireChatServiceProvider;
use Orchestra\Testbench\Concerns\WithWorkbench;

use function Orchestra\Testbench\package_path;
use function Orchestra\Testbench\workbench_path;

abstract class TestCase extends \Orchestra\Testbench\TestCase
{
    // use DatabaseMigrations;

    // use InteractsWithViews;
    // use RefreshDatabase;

    // use DatabaseTruncation;
    use WithWorkbench;

    protected function getPackageProviders($app): array
    {
        return [
            LivewireServiceProvider::class,
            WireChatServiceProvider::class,
            MissingLivewireAssertionsServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app)
    {
        // Setup default database to use sqlite :memory:

        View::addLocation('../resources/views');
        tap($app['config'], function (Repository $config) {

            $config->set('app.debug', true);
            $config->set('app.env', 'testing');
            $config->set('app.timezone', 'UTC');

            // $config->set('view.paths', [__DIR__.'/views', resource_path('views')]);

            $config->set('database.default', 'testbench');
            $config->set('database.connections.testbench', [
                'driver' => 'sqlite',
                'database' => ':memory:',
                'prefix' => '',
            ]);

            // set up user model
            $config->set('wirechat.user_model', \Workbench\App\Models\User::class);

            // Setup queue database connections.
            $config->set('queue.batching.database', 'testbench');
            $config->set('queue.failed.database', 'testbench');

            // file systems
            $config->set('filesystems.default', 'public');

            /** Set Livewire config */
            $config->set('livewire.temporary_file_upload.disk', 'public');

        });

        if (! app()->runningInConsole()) {
            Model::shouldBeStrict();
        }

    }

    protected function setUp(): void
    {
        parent::setUp();
        // Config::set(\Namu\WireChat\Workbench\App\Models\User::class, \App\Models\User::class);

        // dd(             __DIR__.'../../database/migrations');
        // $this->loadMigrationsFrom(__DIR__.'/../database/migrations', workbench_path('database/migrations'));

        //  $this->loadMigrationsFrom( );
        $this->withoutVite();
        // $this->loadMigrationsFrom([workbench_path('database/migrations'),__DIR__.'/../database/migrations']);

        // $this->artisan('migrate:fresh ')->run();
        //  $this->loadRoutesFrom(workbench_path('routes/web.php'));
        // here we add a new ile in the name of the mixture of the berir d
        // / $this->loadMigrationsFrom(__DIR__.'/migrations');
    }

    protected function defineDatabaseMigrations()
    {
        // /$this->loadLaravelMigrations();
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        $this->loadMigrationsFrom(workbench_path('database/migrations'));
        //     $this->artisan('orchid:install'); // installs migrations required for Orchid admin panel
    }

    // public static function applicationBasePath()
    // {
    //     // Adjust this path depending on where your override is located.
    //     return package_path('./tests/skeleton');
    // }

    // protected function defineWebRoutes($router)
    // {
    //     workbench_path('routes/web.php');
    // }

}
