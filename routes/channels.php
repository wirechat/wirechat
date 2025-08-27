<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Broadcast;
use Namu\WireChat\Helpers\MorphClassResolver;
use Namu\WireChat\Models\Conversation;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Here you may register all of the event broadcasting channels that your
| application supports. The given channel authorization callbacks are
| used to check if an authenticated user can listen to the channel.
|
*/

$panels = app('wirechatPanelRegistry')->all();

if (empty($panels)) {
    \Illuminate\Support\Facades\Log::warning('No panels registered in wirechatPanelRegistry for channels');

    return;
}

foreach ($panels as $panel) {
    $panelId = $panel->getId();
    $guards = $panel->getGuards();
    $middleware = $panel->getMiddleware();

    // Conversation channel
    Broadcast::channel("{$panelId}.conversation.{conversationId}", function ($user, $conversationId) use ($guards) {
        // If $user is already authenticated by the application's broadcast auth, use it
        if (! $user) {
            // Fallback to checking each guard defined in the panel
            $authenticatedUser = null;
            foreach ($guards as $guard) {
                if (Auth::guard($guard)->check()) {
                    $authenticatedUser = Auth::guard($guard)->user();
                    break;
                }
            }
            $user = $authenticatedUser ?? null;

            if (! $user) {
                return false;
            }
        }

        $conversation = Conversation::find($conversationId);

        return $conversation && $user->belongsToConversation($conversation);
    }, [
        'guards' => $guards,
        'middleware' => $middleware,
    ]);

    // Participant channel
    Broadcast::channel("{$panelId}.participant.{encodedType}.{id}", function ($user, $encodedType, $id) use ($guards) {
        // If $user is already authenticated by the application's broadcast auth, use it
        if (! $user) {
            // Fallback to checking each guard defined in the panel
            $authenticatedUser = null;
            foreach ($guards as $guard) {
                if (Auth::guard($guard)->check()) {
                    $authenticatedUser = Auth::guard($guard)->user();
                    break;
                }
            }
            $user = $authenticatedUser ?? null;

            if (! $user) {
                return false;
            }
        }

        $morphType = MorphClassResolver::decode($encodedType);

        return $user->id == $id && $user->getMorphClass() == $morphType;
    }, [
        'guards' => $guards,
        'middleware' => $middleware,
    ]);
}
