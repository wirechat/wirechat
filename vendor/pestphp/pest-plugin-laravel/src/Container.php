<?php

declare(strict_types=1);

namespace Pest\Laravel;

use Closure;
use Illuminate\Foundation\Testing\TestCase;
use Mockery\MockInterface;

/**
 * Register an instance of an object in the container.
 */
function swap(string $abstract, object $instance): object
{
    return test()->swap(...func_get_args());
}

/**
 * Register an instance of an object in the container.
 */
function instance(string $abstract, object $instance): object
{
    return test()->instance(...func_get_args());
}

/**
 * Mock an instance of an object in the container.
 */
function mock(string $abstract, ?Closure $mock = null): MockInterface
{
    return test()->mock(...func_get_args());
}

/**
 * Mock a partial instance of an object in the container.
 */
function partialMock(string $abstract, ?Closure $mock = null): MockInterface
{
    return test()->partialMock(...func_get_args());
}

/**
 * Spy an instance of an object in the container.
 */
function spy(string $abstract, ?Closure $mock = null): MockInterface
{
    return test()->spy(...func_get_args());
}

/**
 * Instruct the container to forget a previously mocked / spied instance of an object.
 *
 * @return TestCase
 */
function forgetMock(string $abstract)
{
    return test()->forgetMock(...func_get_args());
}

/**
 * Register an empty handler for Laravel Mix in the container.
 *
 * @return TestCase
 */
function withoutMix()
{
    return test()->withoutMix(...func_get_args());
}

/**
 * Restore Laravel Mix in the container.
 *
 * @return TestCase
 */
function withMix()
{
    return test()->withMix(...func_get_args());
}

/**
 * Register an empty handler for Vite in the container.
 *
 * @return TestCase
 */
function withoutVite()
{
    return test()->withoutVite(...func_get_args());
}

/**
 * Restore Vite in the container.
 *
 * @return TestCase
 */
function withVite()
{
    return test()->withVite(...func_get_args());
}
