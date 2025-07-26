<?php

namespace Namu\WireChat\Exceptions;

use Exception;

class NoPanelProvidedException extends Exception
{
    public static function make(): static
    {
        return new static('No panel provided and no default panel set. Please create at least one panel in your WireChat configuration.');
    }
}
