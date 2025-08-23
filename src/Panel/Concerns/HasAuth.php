<?php

namespace Namu\WireChat\Panel\Concerns;

use Closure;

/**
 * Trait HasAuth
 *
 * Supports configuring and resolving authentication across multiple guards.
 *
 * @method mixed evaluate(mixed $value)
 */
trait HasAuth
{
    /**
     * Guards used for authenticating users.
     *
     * @var array<string>|Closure
     */
    protected array|Closure $guards = ['web'];

    /**
     * Set the guards to be used.
     *
     * @param  array<string>|Closure  $guards
     */
    public function guards(array|Closure $guards): static
    {
        $this->guards = $guards;

        return $this;
    }

    /**
     * Get the configured guards.
     */
    public function getGuards(): array
    {
        return (array) $this->evaluate($this->guards);
    }

    /**
     * Get the current authenticated user from one of the defined guards.
     */
    public function auth(): ?\Illuminate\Contracts\Auth\Authenticatable
    {
        foreach ($this->getGuards() as $guard) {
            if ($user = auth($guard)->user()) {
                return $user;
            }
        }

        return null;
    }

    /**
     * Check if at least one guard has an authenticated user.
     */
    public function authCheck(): bool
    {
        return $this->auth() !== null;
    }
}
