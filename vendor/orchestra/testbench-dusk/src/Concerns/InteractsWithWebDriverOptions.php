<?php

namespace Orchestra\Testbench\Dusk\Concerns;

trait InteractsWithWebDriverOptions
{
    /**
     * Prepare the testing environment web driver options.
     *
     * @return void
     *
     * @codeCoverageIgnore
     */
    public static function setUpBeforeClassForInteractsWithWebDriverOptions()
    {
        static::defineWebDriverOptions();
    }

    /**
     * Prepare the testing environment web driver options.
     *
     * @return void
     */
    public static function defineWebDriverOptions()
    {
        //
    }
}
