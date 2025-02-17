<?php

namespace Namu\WireChat\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Namu\WireChat\Facades\WireChat;
use Namu\WireChat\Models\Message;

class MessageDeleted implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $message;
    // public $receiver;

    public function __construct(Message $message)
    {
        $this->message = $message->load([]);
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\PrivateChannel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('conversation.'.$this->message->conversation_id),
        ];
    }

    /**
     * The name of the queue on which to place the broadcasting job.
     */
    public function broadcastQueue(): string
    {
        return WireChat::notificationsQueue();
    }

    /**
     * Get the data to broadcast.
     *
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        //   dd($this->message);
        return [
            'message' => [
                'id' => $this->message->id,
                'conversation_id' => $this->message->conversation_id,
                'sendable_id' => $this->message->sendable_id,
                'sendable_type' => $this->message->sendable_type,
            ],
        ];
    }
}
