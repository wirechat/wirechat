<?php

namespace Orchestra\Testbench\Dusk\Concerns;

use Laravel\Dusk\Browser;
use Laravel\Dusk\Chrome\SupportsChrome;
use Laravel\Dusk\Concerns\ProvidesBrowser as Concern;
use Orchestra\Testbench\Concerns\HandlesAttributes;
use Orchestra\Testbench\Dusk\Attributes\BeforeServing;
use Orchestra\Testbench\Dusk\Attributes\RestartServer;
use Orchestra\Testbench\Exceptions\ApplicationNotAvailableException;

use function Orchestra\Testbench\Dusk\prepare_debug_directories;

trait ProvidesBrowser
{
    use Concern;
    use SupportsChrome;

    /**
     * Setup the browser environment.
     *
     * @internal
     *
     * @return void
     *
     * @throws \Exception
     */
    protected function setUpTheBrowserEnvironment()
    {
        Browser::$baseUrl = static::applicationBaseUrl();

        $this->prepareDirectories();

        Browser::$userResolver = fn () => $this->user();

        if (\is_null($app = $this->app)) {
            throw ApplicationNotAvailableException::make(__METHOD__);
        }

        if (static::usesTestingConcern(HandlesAttributes::class)) {
            $this->parseTestMethodAttributes($this->app, RestartServer::class);
            $this->parseTestMethodAttributes($this->app, BeforeServing::class)
                ->take(1)
                ->each(function ($callback) {
                    $this->beforeServingApplication($callback);
                });
        }
    }

    /**
     * Ensure the directories we need for dusk exist, and set them for the Browser to use.
     *
     * @internal
     *
     * @return void
     *
     * @throws \Exception
     */
    protected function prepareDirectories()
    {
        prepare_debug_directories();
    }

    /**
     * Teardown the test environment.
     *
     * @return void
     *
     * @codeCoverageIgnore
     */
    protected static function tearDownAfterClassProvidesBrowser(): void
    {
        static::tearDownDuskClass();

        static::$afterClassCallbacks = [];
    }

    /**
     * Get Application's base URL.
     *
     *
     * @return string
     */
    abstract public static function applicationBaseUrl();

    /**
     * Get a callback that returns the default user to authenticate.
     *
     * @return callable
     *
     * @throws \Exception
     */
    abstract protected function user();
}
