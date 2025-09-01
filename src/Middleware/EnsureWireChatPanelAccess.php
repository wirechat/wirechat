<?php

namespace Wirechat\Wirechat\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;
use Wirechat\Wirechat\Exceptions\NoPanelProvidedException;
use Wirechat\Wirechat\PanelRegistry;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

class EnsureWirechatPanelAccess
{
    /**
     * @throws ContainerExceptionInterface
     * @throws NoPanelProvidedException
     * @throws NotFoundExceptionInterface
     */
    public function handle($request, Closure $next, string $panelId)
    {
        $panel = app(PanelRegistry::class)->get($panelId);

        if (! $panel) {
            abort(404, 'Panel not found.');
        }

        $user = Auth::user();

        if (! $user || ! $user->canAccessWirechatPanel($panel)) {
            abort(404);
        }

        return $next($request);
    }
}
