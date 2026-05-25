<?php

namespace Wirechat\Wirechat\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Wirechat\Wirechat\Facades\Wirechat;

class BelongsToConversation
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {

        $user = $request->user();
        $conversationId = $request->route('conversation');

        $conversation = Wirechat::conversationModelClass()::findOrFail($conversationId);

        if (! $user || ! $user->belongsToConversation($conversation)
        ) {
            abort(403, 'Forbidden');
        }

        return $next($request);

    }
}
