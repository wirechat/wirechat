<?php

namespace Namu\WireChat\Panel\Concerns;

trait HasMiddleware
{
    /**
     * @var array<string>
     */
    protected array $middleware = ['auth'];

    /**
     * @var array<string>
     */
    protected array $authMiddleware = ['auth'];

    /**
     * Sets middleware for the panel.
     *
     * @param array<string> $middleware
     * @return static
     */
    public function middleware(array $middleware): static
    {
        $this->middleware = [
            ...$this->middleware,
            ...$middleware,
        ];

        return $this;
    }

    /**
     * Sets authentication middleware for the panel.
     *
     * @param array<string> $middleware
     * @return static
     */
    public function authMiddleware(array $middleware): static
    {
        $this->authMiddleware = [
            ...$this->authMiddleware,
            ...$middleware,
        ];

        return $this;
    }

    /**
     * Gets the middleware for the panel.
     *
     * @return array<string>
     */
    public function getMiddleware(): array
    {
        return $this->middleware;
    }

    /**
     * Gets the authentication middleware for the panel.
     *
     * @return array<string>
     */
    public function getAuthMiddleware(): array
    {
        return $this->authMiddleware;
    }
}
