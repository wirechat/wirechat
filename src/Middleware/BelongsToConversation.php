<?php

namespace Wirechat\Wirechat\Middleware;

use Closure;
use Illuminate\Http\Request;
use Wirechat\Wirechat\Models\Conversation;
use Symfony\Component\HttpFoundation\Response;

class BelongsToConversation
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {

        $user = $request->user();
        $conversationId = $request->route('conversation');

        $conversation = Conversation::findOrFail($conversationId);

        if (! $user || ! $user->belongsToConversation($conversation)
        ) {
            abort(403, 'Forbidden');
        }

        return $next($request);

    }
}
