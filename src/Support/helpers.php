<?php

use Namu\WireChat\Facades\WireChat;

if (! function_exists('wirechat')) {
    function wirechat(): WireChat
    {
        return WireChat::getFacadeRoot();
    }
}
