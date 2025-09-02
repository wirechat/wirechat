<?php

namespace Wirechat\Wirechat\Facades;

use Illuminate\Support\Facades\Facade;

class Wirechat extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'wirechat'; // This will refer to the binding in the service container.
    }
}
