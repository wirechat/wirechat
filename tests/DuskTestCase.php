<?php

namespace Namu\WireChat\Tests;

use Facebook\WebDriver\Chrome\ChromeOptions;
use Facebook\WebDriver\Remote\DesiredCapabilities;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Illuminate\Support\Collection;
use Laravel\Dusk\TestCase as BaseTestCase;
use Livewire\LivewireServiceProvider;
use Namu\WireChat\WireChatServiceProvider;
use Orchestra\Testbench\Concerns\CreatesApplication;
use Orchestra\Testbench\Concerns\WithWorkbench;
use PHPUnit\Framework\Attributes\BeforeClass;
use Illuminate\Support\Facades\View;
use function Orchestra\Testbench\workbench_path;

 abstract class DuskTestCase extends  \Orchestra\Testbench\Dusk\TestCase
//abstract class DuskTestCase extends  BaseTestCase

{
    use WithWorkbench; 
  //  use CreatesApplication;

  protected static $baseServeHost = '127.0.0.1';
  protected static $baseServePort = 8001;




  
    // /**
    //  * Prepare for Dusk test execution.
    //  */
    // #[BeforeClass]
    // public static function prepare(): void
    // {
    //     // if (! static::runningInSail()) {
    //        // static::startChromeDriver();
    //     // }
    // }

    // /**
    //  * Create the RemoteWebDriver instance.
    //  */
    protected function driver(): RemoteWebDriver
    {
        $options = (new ChromeOptions)->addArguments(collect([ '--start-maximized',
        ])->unless($this->hasHeadlessDisabled(), function (Collection $items) {
            return $items->merge([
                '--disable-gpu',
                //'--headless=new',
            ]);
        })->all());

        return RemoteWebDriver::create(
            $_ENV['DUSK_DRIVER_URL'] ?? 'http://localhost:9515',
            DesiredCapabilities::chrome()->setCapability(
                ChromeOptions::CAPABILITY, $options
            )
        );
    }
    // protected function getPackageProviders($app)
    // {
    //     return [
    //         LivewireServiceProvider::class,
    //         WireChatServiceProvider::class
    //     ];
    // }

    // protected function setUp(): void
    // {
    //     parent::setUp();
    //     //Config::set(\Namu\WireChat\Workbench\App\Models\User::class, \App\Models\User::class);

    //     $this->loadMigrationsFrom(
    //         workbench_path('database/migrations')
    //     );
    //     $this->withoutVite();
    //   //  $this->loadRoutesFrom(workbench_path('routes/web.php'));
    //     //here we add a new ile in the name of the mixture of the berir d 
    //     // $this->loadMigrationsFrom(__DIR__.'/migrations');
    //     // $this->loadMigrationsFrom(dirname(__DIR__).'/migrations');
    // }
    // protected function getEnvironmentSetUp($app)
    // {
    //     View ::addLocation('../resources/views');
    //     tap($app['session'], function ($session) {
    //         $session->put('_token', str()->random(40));
    //     });

    //     tap($app['config'], function ($config) {
    //         $config->set('app.env', 'testing');

    //         $config->set('app.debug', true);

    //         $config->set('view.paths', [__DIR__.'/views', resource_path('views')]);

    //         $config->set('app.key', 'base64:Hupx3yAySikrM2/edkZQNQHslgDWYfiBfCuSThJ5SK8=');

    //         $config->set('database.default', 'testbench');

    //         $config->set('database.connections.testbench', [
    //             'driver' => 'sqlite',
    //             'database' => ':memory:',
    //             'prefix' => '',
    //         ]);
    //     });
    // }

/**
* Make sure all integration tests use the same Laravel "skeleton" files.
* This avoids duplicate classes during migrations.
*
* Overrides \Orchestra\Testbench\Dusk\TestCase::getBasePath
*       and \Orchestra\Testbench\Concerns\CreatesApplication::getBasePath
*
* @return string
*/
// protected function getBasePath()
// {
//     // Adjust this path depending on where your override is located.
//     return __DIR__.'/../vendor/orchestra/testbench-dusk/laravel'; 
// }

 }
