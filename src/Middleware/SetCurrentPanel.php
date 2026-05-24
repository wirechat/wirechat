<?php

namespace Wirechat\Wirechat\Middleware;

use Closure;
use Illuminate\Http\Request;
use Wirechat\Wirechat\PanelRegistry;

class SetCurrentPanel
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, string $panelId)
    {
        app(PanelRegistry::class)->setCurrent($panelId);

        return $next($request);
    }
}
