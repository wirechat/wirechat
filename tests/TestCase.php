<?php

namespace Namu\WireChat\Tests;

use Illuminate\Config\Repository;
use Illuminate\Support\Facades\Config;
use Livewire\LivewireServiceProvider;
use Namu\WireChat\WireChatServiceProvider;


use function Orchestra\Testbench\workbench_path;

 class TestCase extends \Orchestra\Testbench\TestCase
{
    protected function getPackageProviders($app): array
    {
        return [
            LivewireServiceProvider::class,
            WireChatServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app) 
    {
        // Setup default database to use sqlite :memory:
        tap($app['config'], function (Repository $config) { 
            $config->set('database.default', 'testbench'); 
            $config->set('database.connections.testbench', [ 
                'driver'   => 'sqlite', 
                'database' => ':memory:', 
                'prefix'   => '', 
            ]); 

        //set up user model 
          $config->set('wirechat.user_model', \Namu\WireChat\Workbench\App\Models\User::class); 
            
            // Setup queue database connections.
            $config->set('queue.batching.database', 'testbench'); 
            $config->set('queue.failed.database', 'testbench'); 
        });
    }

    protected function setUp(): void
    {
        parent::setUp();
        //Config::set(\Namu\WireChat\Workbench\App\Models\User::class, \App\Models\User::class);

        $this->loadMigrationsFrom(
            workbench_path('database/migrations')
        );
        // $this->loadMigrationsFrom(__DIR__.'/migrations');
        // $this->loadMigrationsFrom(dirname(__DIR__).'/migrations');
    }

}
