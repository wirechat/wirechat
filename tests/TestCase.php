<?php

namespace Wirechat\Wirechat\Tests;

use Christophrumpel\MissingLivewireAssertions\MissingLivewireAssertionsServiceProvider;
use Illuminate\Config\Repository;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider;
use Illuminate\Foundation\Testing\Concerns\InteractsWithViews;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\View;
use Livewire\LivewireServiceProvider;
use Orchestra\Testbench\Concerns\WithLaravelMigrations;
use Orchestra\Testbench\Concerns\WithWorkbench;
use Wirechat\Wirechat\WirechatServiceProvider;
use Workbench\App\Providers\Wirechat\TestPanelProvider;

use function Orchestra\Testbench\workbench_path;

abstract class TestCase extends \Orchestra\Testbench\TestCase
{
    use WithWorkbench;

    //  use DatabaseTruncation; // Ensures migrations are run and database is refreshed for each test
    //  use WithLaravelMigrations;
    // use InteractsWithViews;
    // use RefreshDatabase;
    protected function getPackageProviders($app): array
    {
        return [
            LivewireServiceProvider::class,
            WirechatServiceProvider::class,
            RouteServiceProvider::class,
            MissingLivewireAssertionsServiceProvider::class,
            TestPanelProvider::class,
        ];
    }

    protected function defineEnvironment($app)
    {
        View::addLocation('../resources/views');
        tap($app['config'], function (Repository $config) {
            $config->set('app.debug', true);
            $config->set('app.env', 'testing');
            $config->set('app.timezone', 'UTC');

            $config->set('database.default', 'testbench');
            $config->set('database.connections.testbench', [
                'driver' => 'sqlite',
                'database' => ':memory:',
                'prefix' => '',
            ]);

            $config->set('wirechat.user_model', \Workbench\App\Models\User::class);

            $config->set('queue.batching.database', 'testbench');
            $config->set('queue.failed.database', 'testbench');
            $config->set(['queue.default', 'sync']);

            $config->set('filesystems.default', 'public');
            $config->set('livewire.temporary_file_upload.disk', 'public');
        });

        if (! app()->runningInConsole()) {
            Model::shouldBeStrict();
        }

    }

    protected function defineDatabaseMigrations()
    {
        $this->loadMigrationsFrom(__DIR__.'/../workbench/database/migrations');
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        //  $this->loadMigrationsFrom(workbench_path('database/migrations'));

    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }
}
