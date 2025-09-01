<?php

use Wirechat\Wirechat\Facades\Wirechat;
use Wirechat\Wirechat\Facades\WirechatColor;
use Wirechat\Wirechat\Services\ColorService;

if (! function_exists('wirechat')) {
    /**
     * Get the Wirechat service instance.
     */
    function wirechat(): Wirechat
    {
        return Wirechat::getFacadeRoot();
    }
}

if (! function_exists('wirechatColor')) {
    /**
     * Get the Wirechat Color service instance.
     */
    function wirechatColor(): ColorService
    {
        return WirechatColor::getFacadeRoot();
    }
}
