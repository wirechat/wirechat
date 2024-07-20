<?php

namespace Orchestra\Testbench\Bootstrap;

use Dotenv\Dotenv;
use Orchestra\Testbench\Foundation\Env;

use function Illuminate\Filesystem\join_paths;

/**
 * @internal
 */
final class LoadEnvironmentVariables extends \Illuminate\Foundation\Bootstrap\LoadEnvironmentVariables
{
    /**
     * Create a Dotenv instance.
     *
     * @param  \Illuminate\Contracts\Foundation\Application  $app
     * @return \Dotenv\Dotenv
     */
    #[\Override]
    protected function createDotenv($app)
    {
        /** @phpstan-ignore method.notFound, method.notFound */
        if (! file_exists(join_paths($app->environmentPath(), $app->environmentFile()))) {
            return Dotenv::create(
                Env::getRepository(), (string) realpath(join_paths(__DIR__, 'stubs')), '.env.testbench'
            );
        }

        return parent::createDotenv($app);
    }
}
