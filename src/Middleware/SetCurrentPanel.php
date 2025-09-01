<?php

namespace Wirechat\Wirechat\Middleware;

use Closure;
use Illuminate\Http\Request;

class SetCurrentPanel
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, string $panelId)
    {
        app(\Wirechat\Wirechat\PanelRegistry::class)->setCurrent($panelId);

        return $next($request);
    }
}
