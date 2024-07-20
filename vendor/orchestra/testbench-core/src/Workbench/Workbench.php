<?php

namespace Orchestra\Testbench\Workbench;

use Illuminate\Console\Application as Artisan;
use Illuminate\Console\Command;
use Illuminate\Contracts\Foundation\Application as ApplicationContract;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Routing\Router;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Orchestra\Testbench\Contracts\Config as ConfigContract;
use Orchestra\Testbench\Foundation\Config;
use Orchestra\Workbench\WorkbenchServiceProvider;
use ReflectionClass;
use Symfony\Component\Finder\Finder;

use function Orchestra\Testbench\after_resolving;
use function Orchestra\Testbench\package_path;
use function Orchestra\Testbench\workbench_path;

/**
 * @api
 *
 * @phpstan-import-type TWorkbenchDiscoversConfig from \Orchestra\Testbench\Foundation\Config
 */
class Workbench
{
    /**
     * The cached test case configuration.
     *
     * @var \Orchestra\Testbench\Contracts\Config|null
     */
    protected static $cachedConfiguration;

    /**
     * The cached core workbench bindings.
     *
     * @var array{kernel: array{console?: string|null, http?: string|null}, handler: array{exception?: string|null}}
     */
    public static $cachedCoreBindings = [
        'kernel' => [],
        'handler' => [],
    ];

    /**
     * Start Workbench.
     *
     * @param  \Illuminate\Contracts\Foundation\Application  $app
     * @param  \Orchestra\Testbench\Contracts\Config  $config
     * @return void
     */
    public static function start(ApplicationContract $app, ConfigContract $config): void
    {
        $app->singleton(ConfigContract::class, static fn () => $config);
    }

    /**
     * Start Workbench with providers.
     *
     * @param  \Illuminate\Contracts\Foundation\Application  $app
     * @param  \Orchestra\Testbench\Contracts\Config  $config
     * @return void
     */
    public static function startWithProviders(ApplicationContract $app, ConfigContract $config): void
    {
        static::start($app, $config);

        if (class_exists(WorkbenchServiceProvider::class)) {
            $app->register(WorkbenchServiceProvider::class);
        }
    }

    /**
     * Discover Workbench routes.
     *
     * @param  \Illuminate\Contracts\Foundation\Application  $app
     * @param  \Orchestra\Testbench\Contracts\Config  $config
     * @return void
     */
    public static function discoverRoutes(ApplicationContract $app, ConfigContract $config): void
    {
        /** @var TWorkbenchDiscoversConfig $discoversConfig */
        $discoversConfig = $config->getWorkbenchDiscoversAttributes();

        $app->booted(static function ($app) use ($discoversConfig) {
            tap($app->make('router'), static function (Router $router) use ($discoversConfig) {
                foreach (['web', 'api'] as $group) {
                    if (($discoversConfig[$group] ?? false) === true) {
                        if (file_exists($route = workbench_path(['routes', "{$group}.php"]))) {
                            $router->middleware($group)->group($route);
                        }
                    }
                }
            });

            if ($app->runningInConsole() && ($discoversConfig['commands'] ?? false) === true) {
                static::discoverCommandsRoutes($app);
            }
        });

        after_resolving($app, 'translator', static function ($translator) {
            /** @var \Illuminate\Contracts\Translation\Loader $translator */
            $path = Collection::make([
                workbench_path('lang'),
                workbench_path(['resources', 'lang']),
            ])->filter(static fn ($path) => is_dir($path))
                ->first();

            if (\is_null($path)) {
                return;
            }

            $translator->addNamespace('workbench', $path);
        });

        after_resolving($app, 'view', static function ($view, $app) use ($discoversConfig) {
            /** @var \Illuminate\Contracts\View\Factory|\Illuminate\View\Factory $view */
            if (! is_dir($path = workbench_path(['resources', 'views']))) {
                return;
            }

            if (($discoversConfig['views'] ?? false) === true && method_exists($view, 'addLocation')) {
                $view->addLocation($path);

                tap($app->make('config'), static fn ($config) => $config->set('view.paths', array_merge(
                    $config->get('view.paths', []),
                    [$path]
                )));
            }

            $view->addNamespace('workbench', $path);
        });

        after_resolving($app, 'blade.compiler', static function ($blade) use ($discoversConfig) {
            /** @var \Illuminate\View\Compilers\BladeCompiler $blade */
            if (($discoversConfig['components'] ?? false) === false && is_dir(workbench_path(['app', 'View', 'Components']))) {
                $blade->componentNamespace('Workbench\\App\\View\\Components', 'workbench');
            }
        });

        if (($discoversConfig['factories'] ?? false) === true) {
            Factory::guessFactoryNamesUsing(static function ($modelName) {
                /** @var class-string<\Illuminate\Database\Eloquent\Model> $modelName */
                $workbenchNamespace = 'Workbench\\App\\';

                $modelBasename = str_starts_with($modelName, $workbenchNamespace.'Models\\')
                    ? Str::after($modelName, $workbenchNamespace.'Models\\')
                    : Str::after($modelName, $workbenchNamespace);

                /** @var class-string<\Illuminate\Database\Eloquent\Factories\Factory> $factoryName */
                $factoryName = 'Workbench\\Database\\Factories\\'.$modelBasename.'Factory';

                return $factoryName;
            });

            Factory::guessModelNamesUsing(static function ($factory) {
                /** @var \Illuminate\Database\Eloquent\Factories\Factory $factory */
                $workbenchNamespace = 'Workbench\\App\\';

                $namespacedFactoryBasename = Str::replaceLast(
                    'Factory', '', Str::replaceFirst('Workbench\\Database\\Factories\\', '', \get_class($factory))
                );

                $factoryBasename = Str::replaceLast('Factory', '', class_basename($factory));

                /** @var class-string<\Illuminate\Database\Eloquent\Model> $modelName */
                $modelName = class_exists($workbenchNamespace.'Models\\'.$namespacedFactoryBasename)
                    ? $workbenchNamespace.'Models\\'.$namespacedFactoryBasename
                    : $workbenchNamespace.$factoryBasename;

                return $modelName;
            });
        }
    }

    /**
     * Discover Workbench command routes.
     *
     * @param  \Illuminate\Contracts\Foundation\Application  $app
     * @return void
     */
    public static function discoverCommandsRoutes(ApplicationContract $app): void
    {
        if (file_exists($console = workbench_path(['routes', 'console.php']))) {
            require $console;
        }

        if (! is_dir(workbench_path(['app', 'Console', 'Commands']))) {
            return;
        }

        $namespace = 'Workbench\App';

        foreach ((new Finder)->in([workbench_path(['app', 'Console', 'Commands'])])->files() as $command) {
            $command = $namespace.str_replace(
                ['/', '.php'],
                ['\\', ''],
                Str::after($command->getRealPath(), (string) realpath(workbench_path('app').DIRECTORY_SEPARATOR))
            );

            if (
                is_subclass_of($command, Command::class) &&
                ! (new ReflectionClass($command))->isAbstract()
            ) {
                Artisan::starting(function ($artisan) use ($command) {
                    $artisan->resolve($command);
                });
            }
        }
    }

    /**
     * Resolve the configuration.
     *
     * @return \Orchestra\Testbench\Contracts\Config
     *
     * @codeCoverageIgnore
     */
    public static function configuration(): ConfigContract
    {
        if (\is_null(static::$cachedConfiguration)) {
            static::$cachedConfiguration = Config::cacheFromYaml(package_path());
        }

        return static::$cachedConfiguration;
    }

    /**
     * Get application Console Kernel implementation.
     *
     * @return string|null
     */
    public static function applicationConsoleKernel(): ?string
    {
        if (! isset(static::$cachedCoreBindings['kernel']['console'])) {
            static::$cachedCoreBindings['kernel']['console'] = file_exists(workbench_path(['app', 'Console', 'Kernel.php']))
                ? 'Workbench\App\Console\Kernel'
                : null;
        }

        return static::$cachedCoreBindings['kernel']['console'];
    }

    /**
     * Get application HTTP Kernel implementation using Workbench.
     *
     * @return string|null
     */
    public static function applicationHttpKernel(): ?string
    {
        if (! isset(static::$cachedCoreBindings['kernel']['http'])) {
            static::$cachedCoreBindings['kernel']['http'] = file_exists(workbench_path(['app', 'Http', 'Kernel.php']))
                ? 'Workbench\App\Http\Kernel'
                : null;
        }

        return static::$cachedCoreBindings['kernel']['http'];
    }

    /**
     * Get application HTTP exception handler using Workbench.
     *
     * @return string|null
     */
    public static function applicationExceptionHandler(): ?string
    {
        if (! isset(static::$cachedCoreBindings['handler']['exception'])) {
            static::$cachedCoreBindings['handler']['exception'] = file_exists(workbench_path(['app', 'Exceptions', 'Handler.php']))
                ? 'Workbench\App\Exceptions\Handler'
                : null;
        }

        return static::$cachedCoreBindings['handler']['exception'];
    }

    /**
     * Flush the cached configuration.
     *
     * @return void
     *
     * @codeCoverageIgnore
     */
    public static function flush(): void
    {
        static::$cachedConfiguration = null;

        static::$cachedCoreBindings = [
            'kernel' => [],
            'handler' => [],
        ];
    }
}
