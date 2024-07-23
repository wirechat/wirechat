<?php

namespace Orchestra\Testbench\Dusk\Exceptions;

use Exception;
use Throwable;

class UnableToStartServer extends Exception
{
    /**
     * Create new "Unable to start server" exception.
     *
     * @param  string  $message
     * @param  int  $code
     * @param  \Throwable|null  $previous
     */
    public function __construct($message = '', $code = 0, ?Throwable $previous = null)
    {
        $fullMessage = "A server is already running on {$message}. Please stop it before proceeding. \n".
            "It is likely to be a php server, so you can search your existing processes for 'php -L'. \n".
            'Alternatively you can change the setup for your tests to use a different host and port.';

        parent::__construct($fullMessage, $code, $previous);
    }
}
