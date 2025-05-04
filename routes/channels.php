<?php

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

Broadcast::channel('conversation.{conversationId}', function ($user, $conversationId) {

    $conversation = Conversation::find($conversationId);

    if ($conversation) {
        // code...
        if ($user->belongsToConversation($conversation)) {
            return true; // Allow access to the channel
        }
    }

    return false; // Deny access to the channel

},
    [
        'guards' => config('wirechat.routes.guards', ['web']),
        'middleware' => config('wirechat.routes.middleware', ['web', 'auth']),
    ]
);

Broadcast::channel('participant.{encodedType}.{id}', function ($user, $encodedType, $id) {
    // Decode the encoded type to get the raw value.
    $morphType = MorphClassResolver::decode($encodedType);

    return $user->id == $id && $user->getMorphClass() == $morphType;
}, [
    'guards' => config('wirechat.routes.guards', ['web']),
    'middleware' => config('wirechat.routes.middleware', ['web', 'auth']),
]);
