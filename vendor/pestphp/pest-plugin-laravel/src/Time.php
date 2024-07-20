<?php

declare(strict_types=1);

namespace Pest\Laravel;

use DateTimeInterface;

/**
 * Freeze time.
 *
 * @param  callable|null  $callback
 * @return mixed
 */
function freezeTime($callback = null)
{
    return test()->freezeTime($callback);
}

/**
 * Freeze time at the beginning of the current second.
 *
 * @param  callable|null  $callback
 * @return mixed
 */
function freezeSecond($callback = null)
{
    return test()->freezeSecond($callback);
}

/**
 * Begin travelling to another time.
 *
 * @param  int  $value
 * @return \Illuminate\Foundation\Testing\Wormhole
 */
function travel($value)
{
    return test()->travel(...func_get_args());
}

/**
 * Travel to another time.
 *
 * @param  callable|null  $callback
 * @return mixed
 */
function travelTo(DateTimeInterface $date, $callback = null)
{
    return test()->travelTo(...func_get_args());
}

/**
 * Travel back to the current time.
 *
 * @return \DateTimeInterface
 */
function travelBack()
{
    return test()->travelBack(...func_get_args());
}
