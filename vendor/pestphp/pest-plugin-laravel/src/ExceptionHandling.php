<?php

declare(strict_types=1);

namespace Pest\Laravel;

use Illuminate\Foundation\Testing\TestCase;

/**
 * Restore exception handling.
 *
 * @return TestCase
 */
function withExceptionHandling()
{
    return test()->withExceptionHandling(...func_get_args());
}

/**
 * Only handle the given exceptions via the exception handler.
 *
 * @return TestCase
 */
function handleExceptions(array $exceptions)
{
    return test()->handleExceptions(...func_get_args());
}

/**
 * Only handle validation exceptions via the exception handler.
 *
 * @return TestCase
 */
function handleValidationExceptions()
{
    return test()->handleValidationExceptions(...func_get_args());
}

/**
 * Disable exception handling for the test.
 *
 * @return TestCase
 */
function withoutExceptionHandling(array $except = [])
{
    return test()->withoutExceptionHandling(...func_get_args());
}
