<?php

namespace Namu\WireChat\Events;

use Carbon\Carbon;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Namu\WireChat\Facades\WireChat;
use Namu\WireChat\Models\Message;
use Namu\WireChat\Models\Participant;

class NotifyParticipant implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public Participant $participant, public Message $message)
    {

        //  $this->dontBroadcastToCurrentUser();

        // dd($message->conversation->isPrivate());
        //  Log::info($participant);

    }

    /**
     * The name of the queue on which to place the broadcasting job.
     */
    public function broadcastQueue(): string
    {
        return $this->message->conversation->isPrivate() ? WireChat::messagesQueue() : WireChat::notificationsQueue();
    }


    public function broadcastWhen(): bool
    {
        // Check if the message is not older than 60 seconds
        $isNotExpired=  Carbon::parse($this->message->created_at)->gt(Carbon::now()->subMinute(1));

        Log::info(['NotifyParticipant isNotExpired'=>$isNotExpired]);

        return $isNotExpired;
    }


    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('participant.'.$this->participant->participantable_id),
        ];
    }

    public function broadcastWith(): array
    {
        return [
            'message' => [
                'id' => $this->message->id,
                'conversation_id' => $this->message->conversation_id,
            ],
        ];
    }
}
