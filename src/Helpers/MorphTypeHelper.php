<?php

namespace Namu\WireChat\Helpers;

class MorphTypeHelper
{
    // Convert backslashes to dashes for broadcast
    public static function deslash($type)
    {
        return str_replace('\\', '-', $type);
    }

    // Convert dashes back to backslashes for comparison
    public static function reslash($type)
    {
        return str_replace('-', '\\', $type);
    }
}
