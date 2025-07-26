<?php

namespace Namu\WireChat\Panel\Concerns;

use Closure;

/**
 * Trait HasNotifications
 *
 * Adds support for configuring web push notifications,
 * including service worker path management.
 *
 * @method mixed evaluate(mixed $value)  Evaluates closures or returns the given value.
 */
trait HasNotifications
{
    /**
     * Determines whether web push notifications are enabled.
     *
     * @var bool|Closure
     */
    protected bool|Closure $hasWebPushNotifications = false;

    /**
     * Path to the service worker file (relative to the public directory).
     *
     * @var string|Closure
     */
    protected string|Closure $serviceWorkerPath = 'sw.js';

    /**
     * Configure whether web push notifications are enabled.
     *
     * @param  bool|Closure  $condition
     * @return static
     */
    public function webPushNotifications(bool|Closure $condition = true): static
    {
        $this->hasWebPushNotifications = $condition;
        return $this;
    }

    /**
     * Check if web push notifications are enabled.
     *
     * @return bool
     */
    public function hasWebPushNotifications(): bool
    {
        return (bool) $this->evaluate($this->hasWebPushNotifications);
    }

    /**
     * Get the absolute public path to the service worker file.
     *
     * @return string
     */
    public function serviceWorkerPath(string|Closure $path): string
    {
        return (string) public_path($this->evaluate($this->serviceWorkerPath));
    }

    /**
     * Get the raw configured service worker path.
     *
     * @return string
     */
    public function getServiceWorkerPath(): string
    {
        return $this->serviceWorkerPath;
    }
}
