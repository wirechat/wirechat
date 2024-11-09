<?php

namespace Namu\WireChat\Tests;

use Carbon\Carbon;
use Facebook\WebDriver\Chrome\ChromeOptions;
use Facebook\WebDriver\Remote\DesiredCapabilities;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Dusk\Browser;
use Illuminate\Config\Repository;
use Illuminate\Foundation\Testing\Concerns\InteractsWithViews;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Livewire\LivewireServiceProvider;
use Namu\WireChat\WireChatServiceProvider;
use Orchestra\Testbench\Concerns\WithWorkbench;
use function Orchestra\Testbench\workbench_path;
use Orchestra\Testbench\Concerns\CreatesApplication;
use Orchestra\Testbench\Dusk\Options;
use Orchestra\Workbench\WorkbenchServiceProvider;
use Workbench\App\Providers\WorkbenchServiceProvider as ProvidersWorkbenchServiceProvider;

use Laravel\BrowserKitTesting\TestCase as BrowserKitTesting;
use Livewire\Features\SupportTesting\DuskBrowserMacros;

use Orchestra\Testbench\Attributes\WithMigration;
use Orchestra\Testbench\Dusk\TestCase;
use PDO;
use PHPUnit\Framework\Attributes\BeforeClass;


//#[WithMigration] 
abstract class DuskTestCase extends  \LivewireDuskTestbench\TestCase
// abstract class DuskTestCase extends  TestCase

{
    use WithWorkbench;

    //use DatabaseMigrations;
   // use DatabaseTruncation;
    use InteractsWithViews;
     use BrowserFunctions;
    public $baseUrl = 'http://127.0.0.1:8001';




    /**
     * Automatically enables package discoveries.
     *
     * @var bool
     */
    protected $enablesPackageDiscoveries = true;

    public array $packageProviders = [
        WireChatServiceProvider::class,
        LivewireServiceProvider::class,
        WorkbenchServiceProvider::class

    ];


    public static function tweakApplicationHook()
    {
        return function () {
         //   exec('php artisan serve > /dev/null 2>&1 &');
            sleep(1); // Small delay to allow application server to initialize
        };
    }

    public function viewsDirectory(): string
    {
        // Resolves to 'tests/Browser/views'
        return __DIR__ . 'views';
    }





    // protected function driver(): RemoteWebDriver
    // {
    //     return RemoteWebDriver::create(
    //         'http://localhost:9515', DesiredCapabilities::chrome()
    //     );
    // }


    /**
     * Create the RemoteWebDriver instance.
     */
    // protected function driver(): RemoteWebDriver
    // {
    //     return RemoteWebDriver::create(
    //         'http://localhost:9515', DesiredCapabilities::chrome()
    //     );
    // }
    // protected function getPackageProviders($app)
    // {
    //     return [
    //         WireChatServiceProvider::class,
    //         LivewireServiceProvider::class,
    //         WorkbenchServiceProvider::class
    //     ];
    // }

    public static function defineWebDriverOptions()
    {

        // To hide the UI during testing
         Options::withoutUI();
    }
    protected function defineEnvironment($app)
    {
        // Setup default database to use sqlite :memory:


        tap($app['config'], function (Repository $config) {

            $config->set('app.url', "http://127.0.0.1:8001");
            $config->set('app.debug', true);


            date_default_timezone_set('UTC'); // Set the timezone for this test

            // Set the timezone
            $config->set('app.timezone', 'UTC'); // Change 'UTC' to your desired timezone if necessary


            $config->set('app.env', 'testing');

            $config->set('view.paths', [__DIR__ . '/Browser/views', resource_path('views')]);

            $config->set('database.default', 'testbench');
            $config->set('database.connections.testbench', [
                'driver'   => 'sqlite',
                'database' => workbench_path('database/database.sqlite'),
                //  'database' => ':memory:' , 

                'prefix'   => '',
                'options'  => [
                    PDO::ATTR_PERSISTENT => true,
                    // Add more PDO options here if needed
                ],
            ]);

            //Livewire
            $config->set('livewire', require __DIR__ . '/../workbench/config/livewire.php');


            //Load wherechat config 
            //Use of require: Use require to load the PHP config file (wirechat.php).
            $config->set('wirechat', require __DIR__ . '/../config/wirechat.php');
        });
    }

    public  function setUp(): void
    {
        parent::setUp();
        //Config::set(\Namu\WireChat\Workbench\App\Models\User::class, \App\Models\User::class);


         // $this->artisan('config:cache')->run();

         $this->loadMigrationsFrom( __DIR__.'/../database/migrations',workbench_path('database/migrations')  );

        //$this->artisan('livewire:publish --assets')->run();

        // $this->withoutVite();

        //    Carbon::setTestNow(null);
        // $this->loadMigrationsFrom(
        //     workbench_path('database/migrations')
        // );


        //    $this->artisan('migrate:rollback')->run();
        //  $this->loadRoutesFrom(workbench_path('routes/web.php'));
        //here we add a new ile in the name of the mixture of the berir d 
        // $this->loadMigrationsFrom(__DIR__.'/migrations');
        // $this->loadMigrationsFrom(dirname(__DIR__).'/migrations');
    }


    protected function defineRoutes($router)
    {
        // Define routes.
        $router->get('/', fn() => 'Laravel');
    }
}
