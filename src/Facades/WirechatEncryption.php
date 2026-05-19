<?php

namespace Wirechat\Wirechat\Facades;

use Illuminate\Support\Facades\Facade;

class WirechatEncryption extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \Wirechat\Wirechat\Services\WirechatEncryption::class;
    }
}
