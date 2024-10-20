<?php

use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Log;
use Namu\WireChat\Helpers\MorphTypeHelper;
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


   return $user->belongsToConversation($conversation);
});
 

