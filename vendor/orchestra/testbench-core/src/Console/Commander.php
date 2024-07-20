<?php

namespace Orchestra\Testbench\Console;

use Illuminate\Console\Command;
use Illuminate\Console\Concerns\InteractsWithSignals;
use Illuminate\Console\Signals;
use Illuminate\Contracts\Console\Kernel as ConsoleKernel;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Foundation\Application as LaravelApplication;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Orchestra\Testbench\Foundation\Application;
use Orchestra\Testbench\Foundation\Bootstrap\LoadMigrationsFromArray;
use Orchestra\Testbench\Foundation\Config;
use Orchestra\Testbench\Foundation\Console\Concerns\CopyTestbenchFiles;
use Orchestra\Testbench\Foundation\TestbenchServiceProvider;
use Orchestra\Testbench\Workbench\Workbench;
use Symfony\Component\Console\Application as ConsoleApplication;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\SignalRegistry\SignalRegistry;
use Throwable;

use function Illuminate\Filesystem\join_paths;
use function Orchestra\Testbench\transform_relative_path;

/**
 * @phpstan-import-type TConfig from \Orchestra\Testbench\Foundation\Config
 */
class Commander
{
    use CopyTestbenchFiles;
    use InteractsWithSignals;

    /**
     * Application instance.
     *
     * @var \Illuminate\Foundation\Application|null
     */
    protected $app;

    /**
     * List of configurations.
     *
     * @var \Orchestra\Testbench\Foundation\Config
     */
    protected $config;

    /**
     * Working path.
     *
     * @var string
     */
    protected $workingPath;

    /**
     * The environment file name.
     *
     * @var string
     */
    protected $environmentFile = '.env';

    /**
     * Construct a new Commander.
     *
     * @param  \Orchestra\Testbench\Foundation\Config|array  $config
     * @param  string  $workingPath
     *
     * @phpstan-param \Orchestra\Testbench\Foundation\Config|TConfig  $config
     */
    public function __construct($config, string $workingPath)
    {
        $this->config = $config instanceof Config ? $config : new Config($config);
        $this->workingPath = $workingPath;
    }

    /**
     * Handle the command.
     *
     * @return void
     */
    public function handle()
    {
        $input = new ArgvInput();
        $output = new ConsoleOutput();

        try {
            $laravel = $this->laravel();
            $kernel = $laravel->make(ConsoleKernel::class);

            $this->prepareCommandSignals();

            $status = $kernel->handle($input, $output);

            $kernel->terminate($input, $status);
        } catch (Throwable $error) {
            $status = $this->handleException($output, $error);
        } finally {
            $this->handleTerminatingConsole();
            Workbench::flush();
            Application::flushState();

            $this->untrap();
        }

        exit($status);
    }

    /**
     * Create Laravel application.
     *
     * @return \Illuminate\Foundation\Application
     */
    public function laravel()
    {
        if (! $this->app instanceof LaravelApplication) {
            $APP_BASE_PATH = $this->getBasePath();

            $hasEnvironmentFile = fn () => file_exists(join_paths($APP_BASE_PATH, '.env'));

            tap(
                Application::createVendorSymlink($APP_BASE_PATH, join_paths($this->workingPath, 'vendor')),
                function ($app) use ($hasEnvironmentFile) {
                    $filesystem = new Filesystem();

                    $this->copyTestbenchConfigurationFile($app, $filesystem, $this->workingPath);

                    if (! $hasEnvironmentFile()) {
                        $this->copyTestbenchDotEnvFile($app, $filesystem, $this->workingPath);
                    }
                }
            );

            $options = array_filter([
                'load_environment_variables' => $hasEnvironmentFile(),
                'extra' => $this->config->getExtraAttributes(),
            ]);

            $this->app = Application::create(
                basePath: $APP_BASE_PATH,
                resolvingCallback: function ($app) {
                    Workbench::startWithProviders($app, $this->config);
                    Workbench::discoverRoutes($app, $this->config);

                    (new LoadMigrationsFromArray(
                        $this->config['migrations'] ?? [],
                        $this->config['seeders'] ?? false,
                    ))->bootstrap($app);

                    \call_user_func($this->resolveApplicationCallback(), $app);
                },
                options: $options,
            );
        }

        return $this->app;
    }

    /**
     * Resolve application implementation.
     *
     * @return \Closure(\Illuminate\Foundation\Application): void
     */
    protected function resolveApplicationCallback()
    {
        return static function ($app) {
            $app->register(TestbenchServiceProvider::class);
        };
    }

    /**
     * Get base path.
     *
     * @return string
     */
    protected function getBasePath()
    {
        $path = $this->config['laravel'] ?? null;

        if (! \is_null($path)) {
            return tap(transform_relative_path($path, $this->workingPath), static function ($path) {
                $_ENV['APP_BASE_PATH'] = $path;
            });
        }

        return static::applicationBasePath();
    }

    /**
     * Get Application base path.
     *
     * @return string
     */
    public static function applicationBasePath()
    {
        return Application::applicationBasePath();
    }

    /**
     * Render an exception to the console.
     *
     * @param  \Symfony\Component\Console\Output\OutputInterface  $output
     * @param  \Throwable  $error
     * @return int
     */
    protected function handleException(OutputInterface $output, Throwable $error)
    {
        if ($this->app instanceof LaravelApplication) {
            tap($this->app->make(ExceptionHandler::class), static function ($handler) use ($error, $output) {
                $handler->report($error);
                $handler->renderForConsole($output, $error);
            });
        } else {
            (new ConsoleApplication)->renderThrowable($error, $output);
        }

        return 1;
    }

    /**
     * Prepare command signals.
     *
     * @return void
     */
    protected function prepareCommandSignals(): void
    {
        Signals::resolveAvailabilityUsing(static fn () => \extension_loaded('pcntl'));

        Signals::whenAvailable(function () {
            $this->signals ??= new Signals(new SignalRegistry());

            Collection::make(Arr::wrap([SIGINT, SIGTERM, SIGQUIT]))
                ->each(
                    fn ($signal) => $this->signals->register($signal, function () use ($signal) {
                        $this->handleTerminatingConsole();
                        Workbench::flush();

                        $status = match ($signal) {
                            SIGINT => 130,
                            SIGTERM => 143,
                            default => 128 + $signal,
                        };

                        $this->untrap();

                        exit($status);
                    })
                );
        });
    }
}
