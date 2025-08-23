<?php

namespace Namu\WireChat\Panel\Concerns;

use Closure;

/**
 * Trait HasWebPushNotifications
 *
 * Adds support for configuring web push notifications, including
 * service worker path management with a sensible default.
 *
 * Usage:
 *   $panel->webPushNotifications(); // enables + sets default asset('sw.js')
 *   $panel->serviceWorkerPath(asset('sw.js')); // optional override
 *
 * @method mixed evaluate(mixed $value) Evaluates closures or returns the given value.
 */
trait HasWebPushNotifications
{
    /**
     * Whether web push notifications are enabled.
     */
    protected bool|Closure $hasWebPushNotifications = false;

    /**
     * Service worker path (absolute URL or asset helper), or a Closure returning it.
     * Set automatically to asset('sw.js') on first call to webPushNotifications()
     * unless explicitly overridden via serviceWorkerPath().
     */
    protected string|Closure|null $serviceWorkerPath = null;

    /**
     * Enable/disable web push notifications.
     * Also sets a default service worker path to asset('sw.js') if none was set.
     */
    public function webPushNotifications(bool|Closure $condition = true): static
    {
        $this->hasWebPushNotifications = $condition;

        // Set default SW path only once, unless previously overridden.
        if ($this->serviceWorkerPath === null) {
            $this->serviceWorkerPath(asset('sw.js'));
        }

        return $this;
    }

    /**
     * Check if web push notifications are enabled.
     */
    public function hasWebPushNotifications(): bool
    {
        return (bool) $this->evaluate($this->hasWebPushNotifications);
    }

    /**
     * Explicitly set/override the service worker path.
     */
    public function serviceWorkerPath(string|Closure $path): static
    {
        $this->serviceWorkerPath = $path;

        return $this;
    }

    /**
     * Get the effective (evaluated) service worker path.
     * Requires that webPushNotifications() has been called at least once,
     * or that serviceWorkerPath() was explicitly set.
     *
     * @throws \RuntimeException|\Illuminate\Contracts\Container\BindingResolutionException if no path is available.
     */
    public function getServiceWorkerPath(): string
    {
        if ($this->serviceWorkerPath === null) {
            throw new \RuntimeException(
                'Service worker path not set. Call webPushNotifications() or serviceWorkerPath() first.'
            );
        }

        return (string) $this->evaluate($this->serviceWorkerPath);
    }
}
