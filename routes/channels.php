<?php

use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Log;
use Namu\WireChat\Events\NotifyParticipant;
use Namu\WireChat\Helpers\MorphTypeHelper;
use Namu\WireChat\Jobs\NotifyParticipantJob;
use Namu\WireChat\Models\Conversation;
use Namu\WireChat\Models\Message;

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

Broadcast::channel('conversation.{conversationId}', function ($user, int $conversationId) {

   $conversation= Conversation::find($conversationId);

   if ($user->belongsToConversation($conversation)) {
    // Broadcast an event to the user when they join the channel
         // broadcast(new NotifyParticipantJob($user));

        return true; // Allow access to the channel
    }

    return false; // Deny access to the channel




});
 

Broadcast::channel('participant.{id}', function ($user, $id) {
    //Check if the authenticated user matches the broadcast recipient (polymorphic check)
    //we don't use  tripple '===' because the type and id are polymophic hence can be strings 
    // so the validation will fail 

   // Log::info('here');
    return $user->id == $id ;
});
