<?php

declare(strict_types=1);

namespace Pest\Laravel;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Foundation\Testing\TestCase;

/**
 * Set the currently logged in user for the application.
 *
 * @return TestCase
 */
function actingAs(Authenticatable $user, ?string $driver = null)
{
    return test()->actingAs(...func_get_args());
}

/**
 * Set the currently logged in user for the application.
 *
 * @return TestCase
 */
function be(Authenticatable $user, ?string $driver = null)
{
    return test()->be(...func_get_args());
}

/**
 * Assert that the user is authenticated.
 *
 * @return TestCase
 */
function assertAuthenticated(?string $guard = null)
{
    return test()->assertAuthenticated(...func_get_args());
}

/**
 * Assert that the user is not authenticated.
 *
 * @return TestCase
 */
function assertGuest(?string $guard = null)
{
    return test()->assertGuest(...func_get_args());
}

/**
 * Return true if the user is authenticated, false otherwise.
 *
 * @return bool
 */
function isAuthenticated(?string $guard = null)
{
    return test()->isAuthenticated(...func_get_args());
}

/**
 * Assert that the user is authenticated as the given user.
 *
 * @return TestCase
 */
function assertAuthenticatedAs(Authenticatable $user, ?string $guard = null)
{
    return test()->assertAuthenticatedAs(...func_get_args());
}

/**
 * Assert that the given credentials are valid.
 *
 * @return TestCase
 */
function assertCredentials(array $credentials, ?string $guard = null)
{
    return test()->assertCredentials(...func_get_args());
}

/**
 * Assert that the given credentials are invalid.
 *
 * @return TestCase
 */
function assertInvalidCredentials(array $credentials, ?string $guard = null)
{
    return test()->assertInvalidCredentials(...func_get_args());
}

/**
 * Return true if the credentials are valid, false otherwise.
 */
function hasCredentials(array $credentials, ?string $guard = null): bool
{
    return test()->hasCredentials(...func_get_args());
}
