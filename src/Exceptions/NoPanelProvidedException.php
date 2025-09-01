<?php

namespace Wirechat\Wirechat\Exceptions;

use Exception;

class NoPanelProvidedException extends Exception
{
    public static function make(): self
    {
        return new self('No panel provided and no default panel set. Please create at least one panel in your Wirechat configuration.');
    }
}
