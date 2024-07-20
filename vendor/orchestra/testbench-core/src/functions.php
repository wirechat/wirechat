<?php

namespace Orchestra\Testbench;

use Closure;
use Illuminate\Contracts\Console\Kernel as ConsoleKernel;
use Illuminate\Contracts\Foundation\Application as ApplicationContract;
use Illuminate\Foundation\Application;
use Illuminate\Routing\Router;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\ProcessUtils;
use Illuminate\Support\Str;
use Illuminate\Testing\PendingCommand;
use InvalidArgumentException;
use Orchestra\Testbench\Foundation\Config;
use Orchestra\Testbench\Foundation\Env;
use PHPUnit\Runner\Version;
use RuntimeException;
use Symfony\Component\Process\PhpExecutableFinder;
use Symfony\Component\Process\Process;

use function Illuminate\Filesystem\join_paths;

/**
 * Create Laravel application instance.
 *
 * @api
 *
 * @param  string|null  $basePath
 * @param  (callable(\Illuminate\Foundation\Application):(void))|null  $resolvingCallback
 * @param  array{extra?: array{providers?: array, dont-discover?: array, env?: array}, load_environment_variables?: bool, enabled_package_discoveries?: bool}  $options
 * @param  \Orchestra\Testbench\Foundation\Config|null  $config
 * @return \Orchestra\Testbench\Foundation\Application
 */
function container(
    ?string $basePath = null,
    ?callable $resolvingCallback = null,
    array $options = [],
    ?Config $config = null
): Foundation\Application {
    if ($config instanceof Config) {
        return Foundation\Application::makeFromConfig($config, $resolvingCallback, $options);
    }

    return Foundation\Application::make($basePath, $resolvingCallback, $options);
}

/**
 * Run artisan command.
 *
 * @api
 *
 * @param  \Orchestra\Testbench\Contracts\TestCase|\Illuminate\Contracts\Foundation\Application  $context
 * @param  string  $command
 * @param  array<string, mixed>  $parameters
 * @return int
 */
function artisan(Contracts\TestCase|ApplicationContract $context, string $command, array $parameters = []): int
{
    if ($context instanceof ApplicationContract) {
        return $context->make(ConsoleKernel::class)->call($command, $parameters);
    }

    $command = $context->artisan($command, $parameters);

    return $command instanceof PendingCommand ? $command->run() : $command;
}

/**
 * Run remote action using Testbench CLI.
 *
 * @api
 *
 * @param  array<int, string>|string  $command
 * @param  array<string, mixed>|string  $env
 * @return \Symfony\Component\Process\Process
 */
function remote(array|string $command, array|string $env = []): Process
{
    $phpBinary = transform(
        \defined('PHP_BINARY') ? PHP_BINARY : (new PhpExecutableFinder())->find(),
        static fn ($phpBinary) => ProcessUtils::escapeArgument((string) $phpBinary)
    );

    $binary = \defined('TESTBENCH_DUSK') ? 'testbench-dusk' : 'testbench';

    $commander = is_file($vendorBin = package_path(['vendor', 'bin', $binary]))
        ? ProcessUtils::escapeArgument((string) $vendorBin)
        : $binary;

    if (\is_string($env)) {
        $env = ['APP_ENV' => $env];
    }

    Arr::add($env, 'TESTBENCH_PACKAGE_REMOTE', '(true)');

    return Process::fromShellCommandline(
        command: Arr::join([$phpBinary, $commander, ...Arr::wrap($command)], ' '),
        cwd: package_path(),
        env: array_merge(defined_environment_variables(), $env)
    );
}

/**
 * Run callback only once.
 *
 * @api
 *
 * @param  mixed  $callback
 * @return \Closure():mixed
 */
function once($callback): Closure
{
    $response = new Foundation\UndefinedValue();

    return function () use ($callback, &$response) {
        if ($response instanceof Foundation\UndefinedValue) {
            $response = value($callback) ?? null;
        }

        return $response;
    };
}

/**
 * Register after resolving callback.
 *
 * @api
 *
 * @param  \Illuminate\Contracts\Foundation\Application  $app
 * @param  string  $name
 * @param  (\Closure(object, \Illuminate\Contracts\Foundation\Application):(mixed))|null  $callback
 * @return void
 */
function after_resolving(ApplicationContract $app, string $name, ?Closure $callback = null): void
{
    $app->afterResolving($name, $callback);

    if ($app->resolved($name)) {
        value($callback, $app->make($name), $app);
    }
}

/**
 * Load migration paths.
 *
 * @api
 *
 * @param  \Illuminate\Contracts\Foundation\Application  $app
 * @param  array<int, string>|string  $paths
 * @return void
 */
function load_migration_paths(ApplicationContract $app, array|string $paths): void
{
    after_resolving($app, 'migrator', static function ($migrator) use ($paths) {
        foreach (Arr::wrap($paths) as $path) {
            /** @var \Illuminate\Database\Migrations\Migrator $migrator */
            $migrator->path($path);
        }
    });
}

/**
 * Get default environment variables.
 *
 * @return array<int, string>
 *
 * @deprecated
 *
 * @codeCoverageIgnore
 */
function default_environment_variables(): array
{
    return [];
}

/**
 * Get defined environment variables.
 *
 * @api
 *
 * @return array<string, mixed>
 */
function defined_environment_variables(): array
{
    return Collection::make(array_merge($_SERVER, $_ENV))
        ->keys()
        ->mapWithKeys(static fn (string $key) => [$key => Env::forward($key)])
        ->unless(
            Env::has('TESTBENCH_WORKING_PATH'), static fn ($env) => $env->put('TESTBENCH_WORKING_PATH', package_path())
        )->all();
}

/**
 * Get default environment variables.
 *
 * @api
 *
 * @param  iterable<string, mixed>  $variables
 * @return array<int, string>
 */
function parse_environment_variables($variables): array
{
    return Collection::make($variables)
        ->transform(static function ($value, $key) {
            if (\is_bool($value) || \in_array($value, ['true', 'false'])) {
                $value = \in_array($value, [true, 'true']) ? '(true)' : '(false)';
            } elseif (\is_null($value) || \in_array($value, ['null'])) {
                $value = '(null)';
            } else {
                $value = $key === 'APP_DEBUG' ? sprintf('(%s)', Str::of($value)->ltrim('(')->rtrim(')')) : "'{$value}'";
            }

            return "{$key}={$value}";
        })->values()->all();
}

/**
 * Refresh router lookups.
 *
 * @api
 *
 * @param  \Illuminate\Routing\Router  $router
 * @return void
 */
function refresh_router_lookups(Router $router): void
{
    $router->getRoutes()->refreshNameLookups();
}

/**
 * Transform relative path.
 *
 * @api
 *
 * @param  string  $path
 * @param  string  $workingPath
 * @return string
 */
function transform_relative_path(string $path, string $workingPath): string
{
    return str_starts_with($path, './')
        ? rtrim($workingPath, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.mb_substr($path, 2)
        : $path;
}

/**
 * Get the default skeleton path.
 *
 * @api
 *
 * @param  array|string  $path
 * @return string
 */
function default_skeleton_path(array|string $path = ''): string
{
    return (string) realpath(join_paths(__DIR__, '..', 'laravel', ...Arr::wrap($path)));
}

/**
 * Get the path to the package folder.
 *
 * @api
 *
 * @param  array|string  $path
 * @return string
 */
function package_path(array|string $path = ''): string
{
    $workingPath = \defined('TESTBENCH_WORKING_PATH')
        ? TESTBENCH_WORKING_PATH
        : Env::get('TESTBENCH_WORKING_PATH', getcwd());

    $path = join_paths(...Arr::wrap($path));

    return str_starts_with($path, './')
        ? transform_relative_path($path, $workingPath)
        : join_paths(rtrim($workingPath, DIRECTORY_SEPARATOR), $path);
}

/**
 * Get the workbench configuration.
 *
 * @api
 *
 * @return array<string, mixed>
 */
function workbench(): array
{
    /** @var \Orchestra\Testbench\Contracts\Config $config */
    $config = app()->bound(Contracts\Config::class)
        ? app()->make(Contracts\Config::class)
        : new Foundation\Config();

    return $config->getWorkbenchAttributes();
}

/**
 * Get the path to the workbench folder.
 *
 * @api
 *
 * @param  array|string  $path
 * @return string
 */
function workbench_path(array|string $path = ''): string
{
    return package_path(join_paths('workbench', ...Arr::wrap($path)));
}

/**
 * Get the migration path by type.
 *
 * @api
 *
 * @param  string|null  $type
 * @return string
 *
 * @throws \InvalidArgumentException
 */
function laravel_migration_path(?string $type = null): string
{
    $path = realpath(
        \is_null($type) ? base_path('migrations') : base_path(join_paths('migrations', $type))
    );

    if ($path === false) {
        throw new InvalidArgumentException(sprintf('Unable to resolve migration path for type [%s]', $type ?? 'laravel'));
    }

    return $path;
}

/**
 * Laravel version compare.
 *
 * @api
 *
 * @param  string  $version
 * @param  string|null  $operator
 * @return int|bool
 */
function laravel_version_compare(string $version, ?string $operator = null)
{
    /** @phpstan-ignore identical.alwaysFalse */
    $laravel = Application::VERSION === '10.x-dev' ? '10.0.0' : Application::VERSION;

    if (\is_null($operator)) {
        return version_compare($laravel, $version);
    }

    return version_compare($laravel, $version, $operator);
}

/**
 * PHPUnit version compare.
 *
 * @api
 *
 * @param  string  $version
 * @param  string|null  $operator
 * @return int|bool
 *
 * @throws \RuntimeException
 */
function phpunit_version_compare(string $version, ?string $operator = null)
{
    if (! class_exists(Version::class)) {
        throw new RuntimeException('Unable to verify PHPUnit version');
    }

    if (\is_null($operator)) {
        return version_compare(Version::id(), $version);
    }

    return version_compare(Version::id(), $version, $operator);
}
