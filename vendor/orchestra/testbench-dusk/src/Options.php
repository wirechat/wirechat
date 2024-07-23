<?php

namespace Orchestra\Testbench\Dusk;

use Facebook\WebDriver\Chrome\ChromeOptions;

/**
 * @api
 */
class Options
{
    /**
     * Store UI setting.
     *
     * @var bool
     */
    public static $ui = true;

    /**
     * Headless mode.
     *
     * @var string|null
     */
    public static $headlessMode = null;

    /**
     * Set W3C compliant.
     *
     * @var bool
     */
    public static $w3cCompliant = false;

    /**
     * Testbench should provide application server.
     *
     * @var bool
     */
    public static $providesApplicationServer = true;

    /**
     * A list of remote web driver arguments.
     *
     * @var array<int, string>
     */
    public static $arguments = [];

    /**
     * Apply `ChromeOptions` configuration using a callback.
     *
     * @var (\Closure(\Facebook\WebDriver\Chrome\ChromeOptions):(void))|null
     */
    public static $chromeOptionsCallback = null;

    /**
     * Reset arguments.
     *
     * @return void
     */
    public static function resetArguments(): void
    {
        static::$arguments = [];
    }

    /**
     * Set `ChromeOptions` callback.
     *
     * @param  (\Closure(\Facebook\WebDriver\Chrome\ChromeOptions):(void))|null  $callback
     * @return static
     */
    public static function using($callback)
    {
        static::$chromeOptionsCallback = $callback;

        return new static();
    }

    /**
     * Add a browser option.
     *
     * @return static
     */
    public static function addArgument(string $argument)
    {
        if (! static::hasArgument($argument)) {
            array_push(static::$arguments, $argument);
        }

        return new static();
    }

    /**
     * Remove a browser option.
     *
     * @return static
     */
    public static function removeArgument(string $argument)
    {
        if (static::hasArgument($argument)) {
            static::$arguments = array_values(array_filter(static::$arguments, static fn ($option) => $option !== $argument));
        }

        return new static();
    }

    /**
     * Check has a browser option.
     *
     * @return bool
     */
    public static function hasArgument(string $argument)
    {
        return \in_array($argument, static::$arguments);
    }

    /**
     * Set to hide UI.
     *
     * @return static
     */
    public static function withoutUI()
    {
        return static::disableGpu()->headless();
    }

    /**
     * Set to show UI.
     *
     * @return static
     */
    public static function withUI()
    {
        return static::removeArgument('--disable-gpu')
            ->removeArgument('--headless='.(static::$headlessMode ?? 'new'))
            ->removeArgument('--headless');
    }

    /**
     * Return current setting for showing UI.
     */
    public static function hasUI(): bool
    {
        return ! static::hasArgument('--headless='.(static::$headlessMode ?? 'new')) &&
            ! static::hasArgument('--headless');
    }

    /**
     * It should uses without UI.
     */
    public static function shouldUsesWithoutUI(): bool
    {
        return env('CI', false) == true;
    }

    /**
     * Run the browser in headless mode.
     *
     * @return static
     */
    public static function headless()
    {
        static::$headlessMode = env('DUSK_HEADLESS_MODE', 'new');

        if (\is_null(static::$headlessMode)) {
            return static::addArgument('--headless');
        }

        return static::addArgument('--headless='.static::$headlessMode);
    }

    /**
     * Disable the browser using gpu.
     *
     * @return static
     */
    public static function disableGpu()
    {
        return static::addArgument('--disable-gpu');
    }

    /**
     * Disable the sandbox.
     *
     * @return static
     */
    public static function noSandbox()
    {
        return static::addArgument('--no-sandbox');
    }

    /**
     * Disables the use of a zygote process for forking child processes.
     *
     * @return static
     */
    public static function noZygote()
    {
        return static::noSandbox()->addArgument('--no-zygote');
    }

    /**
     * Ignore SSL certificate error messages.
     *
     * @return static
     */
    public static function ignoreSslErrors()
    {
        return static::addArgument('--ignore-certificate-errors');
    }

    /**
     * Set the initial browser window size.
     *
     * @return static
     */
    public static function windowSize(int $width, int $height)
    {
        return static::addArgument('--window-size='.$width.','.$height);
    }

    /**
     * Set remote debugging port.
     *
     * @return static
     */
    public static function remoteDebuggingPort(int $port = 9222)
    {
        return static::addArgument('--remote-debugging-port='.$port);
    }

    /**
     * Set the user agent.
     *
     * @return static
     */
    public static function userAgent(string $useragent)
    {
        return static::addArgument('--user-agent='.$useragent);
    }

    /**
     * Return the ChromeOptions Object - used when configuring the
     * Facebook Webdriver.
     *
     * @return \Facebook\WebDriver\Chrome\ChromeOptions
     */
    public static function getChromeOptions()
    {
        return tap(new ChromeOptions(), static function ($option) {
            if (static::$w3cCompliant === false) {
                $option->setExperimentalOption('w3c', static::$w3cCompliant);
            }

            value(static::$chromeOptionsCallback);
        })->addArguments(static::$arguments);
    }
}
