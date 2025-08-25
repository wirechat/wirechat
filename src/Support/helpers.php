<?php

use Namu\WireChat\Facades\WireChat;
use Namu\WireChat\Facades\WireChatColor;
use Namu\WireChat\Services\ColorService;

if (! function_exists('wirechat')) {
    /**
     * Get the WireChat service instance.
     */
    function wirechat(): WireChat
    {
        return WireChat::getFacadeRoot();
    }
}

if (! function_exists('wirechatColor')) {
    /**
     * Get the WireChat Color service instance.
     *
     * @return ColorService
     */
    function wirechatColor(): ColorService
    {
        return WireChatColor::getFacadeRoot();
    }
}
