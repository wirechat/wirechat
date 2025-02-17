<?php

namespace Namu\WireChat\Helpers;

class MorphClassResolver
{
    /**
     * Encodes the given raw type using hexadecimal encoding.
     */
    public static function encode(string $rawType): string
    {
        return bin2hex($rawType);
    }

    /**
     * Decodes the given hex-encoded type back to its raw string.
     *
     * @return string|false
     */
    public static function decode(string $encodedType)
    {
        return hex2bin($encodedType);
    }
}
