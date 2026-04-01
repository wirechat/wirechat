<?php

use Illuminate\Support\Facades\Broadcast;
use Wirechat\Wirechat\Facades\Wirechat;
use Wirechat\Wirechat\Helpers\MorphClassResolver;
use Wirechat\Wirechat\PanelRegistry;

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

$panels = app(PanelRegistry::class)->all();

if (empty($panels)) {
    \Illuminate\Support\Facades\Log::warning('No panels registered in wirechatPanelRegistry for channels');

    return;
}

foreach ($panels as $panel) {
    $panelId = $panel->getId();
    $guards = $panel->getGuards();
    $middleware = $panel->getMiddleware();

    // Conversation channel
    Broadcast::channel("{$panelId}.conversation.{conversationId}", function ($user, $conversationId) {
        $participantable = Wirechat::getParticipantable();

        if (! $participantable) {
            return false;
        }

        $conversation = Wirechat::conversationModelClass()::find($conversationId);

        return $conversation && $participantable->belongsToConversation($conversation);
    }, [
        'guards' => $guards,
        'middleware' => $middleware,
    ]);

    // Participant channel
    Broadcast::channel("{$panelId}.participant.{encodedType}.{id}", function ($user, $encodedType, $id) {
        $participantable = Wirechat::getParticipantable();

        if (! $participantable) {
            return false;
        }

        $morphType = MorphClassResolver::decode($encodedType);

        return $participantable->getKey() == $id && $participantable->getMorphClass() == $morphType;
    }, [
        'guards' => $guards,
        'middleware' => $middleware,
    ]);
}
