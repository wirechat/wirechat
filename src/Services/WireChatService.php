<?php

namespace Namu\WireChat\Services;

class WireChatService
{
    public function getColor()
    {
        return config('wirechat.color',"#3b82f6");
    }
}
