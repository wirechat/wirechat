<?php

namespace Wirechat\Wirechat\Panel\Concerns;

/**
 * Trait HasChatMiddleware
 *
 * Provides the ability to define and retrieve middleware for chat-specific routes.
 * Ensures the default "belongsToConversation" middleware is always first.
 */
trait HasChatMiddleware
{
    /**
     * @var array<string> Middleware for chat-specific routes.
     */
    protected array $chatMiddleware = ['belongsToConversation'];

    /**
     * Append middleware for chat routes, keeping the default first.
     *
     * @param  array<string>  $middleware
     */
    public function chatMiddleware(array $middleware): static
    {
        // Always ensure 'belongsToConversation' is the first item
        $this->chatMiddleware = array_values(array_unique([
            'belongsToConversation',
            ...$middleware,
        ]));

        return $this;
    }

    /**
     * Get the chat route middleware.
     *
     * @return array<string>
     */
    public function getChatMiddleware(): array
    {
        return $this->chatMiddleware;
    }
}
