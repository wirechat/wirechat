<?php

namespace Wirechat\Wirechat\Panel\Concerns;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
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

    protected bool|Closure $hasRoutes = true;

    protected string|Closure|null $mountUrl = null;

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
     * Route name for the invite preview route.
     *
     * @const string
     */
    public const INVITE_SHOW_ROUTE_NAME = 'invite.show';

    /**
     * Route name for the invite join route.
     *
     * @const string
     */
    public const INVITE_JOIN_ROUTE_NAME = 'invite.join';

    /**
     * Check if panel has registered routes
     */
    public function hasRoutes(): bool
    {
        return (bool) $this->evaluate($this->hasRoutes);
    }

    public function registerRoutes(bool|Closure $condition = true): static
    {
        $this->hasRoutes = $condition;

        return $this;
    }

    public function mountUrl(string|Closure|null $url): static
    {
        $this->mountUrl = $url;

        return $this;
    }

    public function getMountUrl(?Request $request = null): ?string
    {
        return $this->evaluate(
            $this->mountUrl,
            ['request' => $request],
            $request ? [Request::class => $request] : []
        );
    }

    public function hasMountUrl(?Request $request = null): bool
    {
        if ($this->mountUrl instanceof Closure && $request === null) {
            return true;
        }

        $url = $this->getMountUrl($request);

        return is_string($url) && trim($url) !== '';
    }

    public function shouldRegisterInviteRoutes(): bool
    {
        return $this->hasRoutes() || $this->hasMountUrl();
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
     * Gets the fully qualified route name for the invite preview route.
     */
    public function getInviteRouteName(): string
    {
        return $this->generateRouteName(self::INVITE_SHOW_ROUTE_NAME);
    }

    /**
     * Gets the fully qualified route name for the invite join route.
     */
    public function getInviteJoinRouteName(): string
    {
        return $this->generateRouteName(self::INVITE_JOIN_ROUTE_NAME);
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

    public function hasRegisteredRoute(string $name): bool
    {
        if (! $this->shouldRegisterRouteName($name)) {
            return false;
        }

        return Route::has($this->generateRouteName($name));
    }

    protected function shouldRegisterRouteName(string $name): bool
    {
        return match ($name) {
            self::INVITE_SHOW_ROUTE_NAME, self::INVITE_JOIN_ROUTE_NAME => $this->shouldRegisterInviteRoutes(),
            default => $this->hasRoutes(),
        };
    }

    public function routeIfRegistered(string $name, array $parameters = [], bool $absolute = true): ?string
    {
        if (! $this->hasRegisteredRoute($name)) {
            return null;
        }

        return $this->route($name, $parameters, $absolute);
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

    public function chatsRouteIfRegistered(bool $absolute = true): ?string
    {
        return $this->routeIfRegistered(self::CHATS_ROUTE_NAME, [], $absolute);
    }

    public function chatsUrl(bool $absolute = true): ?string
    {
        $route = $this->chatsRouteIfRegistered($absolute);

        if ($route !== null) {
            return $route;
        }

        return $this->hasRoutes() ? null : $this->getMountUrl(request());
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

    public function chatRouteIfRegistered(mixed $conversation, bool $absolute = true): ?string
    {
        return $this->routeIfRegistered(self::CHAT_ROUTE_NAME, ['conversation' => $conversation], $absolute);
    }

    public function chatUrl(mixed $conversation, bool $absolute = true): ?string
    {
        $route = $this->chatRouteIfRegistered($conversation, $absolute);

        if ($route !== null) {
            return $route;
        }

        return $this->hasRoutes() ? null : $this->getMountUrl(request());
    }

    /**
     * Generates a URL for the invite preview route.
     *
     * @param  mixed  $token  The invite token or model route key.
     */
    public function inviteRoute(mixed $token, bool $absolute = true): string
    {
        return $this->route(self::INVITE_SHOW_ROUTE_NAME, ['token' => $token], $absolute);
    }

    public function inviteRouteIfRegistered(mixed $token, bool $absolute = true): ?string
    {
        return $this->routeIfRegistered(self::INVITE_SHOW_ROUTE_NAME, ['token' => $token], $absolute);
    }

    /**
     * Generates a URL for the invite join route.
     *
     * @param  mixed  $token  The invite token or model route key.
     */
    public function inviteJoinRoute(mixed $token, bool $absolute = true): string
    {
        return $this->route(self::INVITE_JOIN_ROUTE_NAME, ['token' => $token], $absolute);
    }

    public function inviteJoinRouteIfRegistered(mixed $token, bool $absolute = true): ?string
    {
        return $this->routeIfRegistered(self::INVITE_JOIN_ROUTE_NAME, ['token' => $token], $absolute);
    }
}
