<?php

namespace Orchestra\Testbench\Foundation;

/**
 * @api
 */
class Env extends \Illuminate\Support\Env
{
    /**
     * Determine if environmemt variable is available.
     *
     * @param  string  $key
     * @return bool
     */
    public static function has(string $key): bool
    {
        return static::get($key, new UndefinedValue) instanceof UndefinedValue === false;
    }

    /**
     * Set an environment value.
     *
     * @param  string  $key
     * @param  string  $value
     * @return void
     */
    public static function set(string $key, string $value): void
    {
        static::getRepository()->set($key, $value);
    }

    /**
     * Forget an environment variable.
     *
     * @param  string  $key
     * @return bool
     *
     * @throws \InvalidArgumentException
     */
    public static function forget(string $key): bool
    {
        return static::getRepository()->clear($key);
    }

    /**
     * Forward environment value.
     *
     * @param  string  $key
     * @param  \Orchestra\Testbench\Foundation\UndefinedValue|mixed|null  $default
     * @return mixed
     */
    public static function forward(string $key, $default = new UndefinedValue())
    {
        $value = static::get($key, $default);

        if ($value instanceof UndefinedValue) {
            return false;
        }

        return static::encode($value);
    }

    /**
     * Encode environment variable value.
     *
     * @param  mixed  $value
     * @return mixed
     */
    public static function encode($value)
    {
        if (\is_null($value)) {
            return '(null)';
        }

        if (\is_bool($value)) {
            return $value === true ? '(true)' : '(false)';
        }

        if (empty($value)) {
            return '(empty)';
        }

        return $value;
    }
}
