<?php

namespace Namu\WireChat\Exceptions;

use Exception;

class NoPanelProvidedException extends Exception
{
    public static function make(): self
    {
        return new self('No panel provided and no default panel set. Please create at least one panel in your WireChat configuration.');
    }
}
