<?php

namespace Wirechat\Wirechat\Panel\Concerns;

use Closure;
use Laravel\SerializableClosure\Serializers\Native;

/**
 * Provides route-related functionality for Wirechat panels, including route registration and URL generation.
 */
trait HasRoutes
{
    /**
     * Array of route closures for custom panel routes.
     *
     * @var array<Closure|Native>
     */
    protected array $routes = [];

    /**
     * The home URL for the panel, which can be a string, Closure, or null.
     */
    protected string|Closure|null $homeUrl = null;

    /**
     * The base path for the panel's routes.
     */
    protected string $path = '';

    /**
     * Route name for the chats index route.
     *
     * @const string
     */
    public const CHATS_ROUTE_NAME = 'chats';

    /**
     * Route name for the chat show route.
     *
     * @const string
     */
    public const CHAT_ROUTE_NAME = 'chat';

    /**
     * Sets the home URL for the panel.
     *
     * @param  string|Closure|null  $url  The home URL or a Closure that returns it.
     */
    public function homeUrl(string|Closure|null $url): static
    {
        $this->homeUrl = $url;

        return $this;
    }

    /**
     * Sets the base path for the panel's routes.
     *
     * @param  string  $path  The base path for the panel's routes.
     */
    public function path(string $path): static
    {
        $this->path = $path;

        return $this;
    }

    /**
     * Registers a custom route Closure for the panel.
     *
     * @param  Closure|null  $routes  A Closure defining custom routes, or null to reset.
     */
    public function routes(?Closure $routes): static
    {
        if ($routes) {
            $this->routes[] = $routes;
        }

        return $this;
    }

    /**
     * Gets the array of registered route Closures.
     *
     * @return array<Closure|Native> The registered route Closures.
     */
    public function getRoutes(): array
    {
        return $this->routes;
    }

    /**
     * Gets the evaluated home URL for the panel.
     *
     * @return string|null The home URL, or null if not set.
     */
    public function getHomeUrl(): ?string
    {
        return $this->evaluate($this->homeUrl);
    }

    /**
     * Gets the base path for the panel's routes.
     *
     * @return string The base path.
     */
    public function getPath(): string
    {
        return $this->path;
    }

    /**
     * Gets the route prefix for the panel, trimmed of leading/trailing slashes.
     *
     * @return string The route prefix.
     */
    public function getRoutePrefix(): string
    {
        return $this->path ? trim($this->path, '/') : '';
    }

    /**
     * Generates a fully qualified route name for the panel.
     *
     * @param  string  $name  The base route name (e.g., 'chats', 'chat').
     * @return string //The fully qualified route name (e.g., 'wirechat.panel1.chats').
     */
    public function generateRouteName(string $name): string
    {

        return "wirechat.{$this->getPath()}.{$name}";

    }

    /**
     * Gets the fully qualified route name for the chats index route.
     *
     * @return string The route name (e.g., 'wirechat.panel1.chats').
     */
    public function getChatsRouteName(): string
    {
        return $this->generateRouteName(self::CHATS_ROUTE_NAME);
    }

    /**
     * Gets the fully qualified route name for the chat show route.
     *
     * @return string The route name (e.g., 'wirechat.panel1.chat').
     */
    public function getChatRouteName(): string
    {
        return $this->generateRouteName(self::CHAT_ROUTE_NAME);
    }

    /**
     * Generates a URL for a named route within the panel.
     *
     * @param  string  $name  The base route name (e.g., 'chats', 'chat').
     * @param  array  $parameters  Route parameters (e.g., ['conversation' => $id]).
     * @param  bool  $absolute  Whether to generate an absolute URL.
     * @return string The generated URL.
     */
    public function route(string $name, array $parameters = [], bool $absolute = true): string
    {

        return route($this->generateRouteName($name), $parameters, $absolute);
    }

    /**
     * Generates a URL for the chats index route.
     *
     * @param  bool  $absolute  Whether to generate an absolute URL.
     * @return string The generated URL.
     */
    public function chatsRoute(bool $absolute = true): string
    {
        return $this->route(self::CHATS_ROUTE_NAME, [], $absolute);
    }

    /**
     * Generates a URL for the chat show route.
     *
     * @param  mixed  $conversation  The conversation ID or model for the route.
     * @param  bool  $absolute  Whether to generate an absolute URL.
     * @return string The generated URL.
     */
    public function chatRoute(mixed $conversation, bool $absolute = true): string
    {
        return $this->route(self::CHAT_ROUTE_NAME, ['conversation' => $conversation], $absolute);
    }
}
