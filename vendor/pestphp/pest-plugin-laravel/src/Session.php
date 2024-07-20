<?php

declare(strict_types=1);

namespace Pest\Laravel;

use Illuminate\Foundation\Testing\TestCase;

/**
 * Set the session to the given array.
 *
 * @return TestCase
 */
function withSession(array $data)
{
    return test()->withSession(...func_get_args());
}

/**
 * Set the session to the given array.
 *
 * @return TestCase
 */
function session(array $data)
{
    return test()->session(...func_get_args());
}

/**
 * Start the session for the application.
 *
 * @return TestCase
 */
function startSession()
{
    return test()->startSession(...func_get_args());
}

/**
 * Flush all of the current session data.
 *
 * @return TestCase
 */
function flushSession()
{
    return test()->flushSession(...func_get_args());
}
