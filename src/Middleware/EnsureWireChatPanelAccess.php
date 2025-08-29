<?php

namespace Namu\WireChat\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;
use Namu\WireChat\Exceptions\NoPanelProvidedException;
use Namu\WireChat\PanelRegistry;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

class EnsureWireChatPanelAccess
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

        if (! $user || ! method_exists($user, 'canAccessWireChatPanel') || ! $user->canAccessWireChatPanel($panel)) {
            abort(404);
        }

        return $next($request);
    }
}
