<?php

namespace Namu\WireChat\Facades;

use Illuminate\Support\Facades\Facade;

class WireChat extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'wirechat'; // This will refer to the binding in the service container.
    }
}
