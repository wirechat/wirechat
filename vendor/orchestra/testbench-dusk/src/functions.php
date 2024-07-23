<?php

namespace Orchestra\Testbench\Dusk;

use Illuminate\Support\LazyCollection;
use Laravel\Dusk\Browser;

use function Illuminate\Filesystem\join_paths;
use function Orchestra\Testbench\package_path;

/**
 * Get the default skeleton path
 */
function default_skeleton_path(string $path = ''): string
{
    return (string) realpath(join_paths(__DIR__, '..', 'laravel', $path));
}

/**
 * Find test directory.
 */
function find_test_directory(): string
{
    return package_path(join_paths('tests', 'Browser'));
}

/**
 * Prepare debug direcotories.
 */
function prepare_debug_directories(): void
{
    if (\defined('TESTBENCH_DIRECTORY_STUBBED')) {
        return;
    }

    $path = find_test_directory();

    LazyCollection::make(['screenshots', 'console', 'source'])
        ->map(static fn ($directory) => join_paths($path, $directory))
        ->each(static function ($directory) {
            if (! is_dir($directory)) {
                mkdir($directory, 0777, true);
            }
        })->each(static function ($directory) {
            if (! is_file(join_paths($directory, '.gitignore'))) {
                copy((string) realpath(join_paths(__DIR__, '..', 'stubs', 'gitignore.stub')), join_paths($directory, '.gitignore'));
            }
        });

    Browser::$storeScreenshotsAt = join_paths($path, 'screenshots');
    Browser::$storeConsoleLogAt = join_paths($path, 'console');
    Browser::$storeSourceAt = join_paths($path, 'source');

    \define('TESTBENCH_DIRECTORY_STUBBED', true);
}
