<?php

namespace Namu\WireChat\Panel\Concerns;

/**
 * Trait HasMiddleware
 *
 * Provides the ability to define and retrieve middleware.
 */
trait HasMiddleware
{
    /**
     * @var array<string> Middleware to be applied.
     */
    protected array $middleware = [];

    /**
     * Set middleware.
     *
     * @param array<string> $middleware
     * @return static
     */
    public function middleware(array $middleware): static
    {
        $this->middleware = array_values(array_unique([
            ...$this->middleware,
            ...$middleware,
        ]));

        return $this;
    }

    /**
     * Get the middleware.
     *
     * @return array<string>
     */
    public function getMiddleware(): array
    {
        return $this->middleware;
    }
}
