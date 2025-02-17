<?php

namespace Namu\WireChat\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Namu\WireChat\Models\Conversation;
use Namu\WireChat\Models\Message;

class BroadcastMessageEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public Message $message, public Conversation $conversation)
    {

        // Log::info($participant);
    }
}
