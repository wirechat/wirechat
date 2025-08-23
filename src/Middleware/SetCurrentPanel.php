<?php

namespace Namu\WireChat\Middleware;

use Closure;
use Illuminate\Http\Request;

class SetCurrentPanel
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, string $panelId)
    {
        app('wirechatPanelRegistry')->setCurrent($panelId);

        return $next($request);
    }
}
