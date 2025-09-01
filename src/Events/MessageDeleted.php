<?php

namespace Wirechat\Wirechat\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Wirechat\Wirechat\Models\Message;
use Wirechat\Wirechat\Traits\InteractsWithPanel;

class MessageDeleted implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;
    use InteractsWithPanel;

    public $message;
    // public $receiver;

    public function __construct(Message $message, ?string $panel = null)
    {
        $this->message = $message->load([]);
        $this->resolvePanel($panel);

    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\PrivateChannel>
     */
    public function broadcastOn(): array
    {
        $channels = [];

        $panelId = $this->getPanel()->getId();
        $channels[] = "$panelId.conversation.{$this->message->conversation_id}";

        return array_map(function ($channelName) {
            return new PrivateChannel($channelName);
        }, $channels);
    }

    /**
     * The name of the queue on which to place the broadcasting job.
     */
    public function broadcastQueue(): string
    {
        return $this->getPanel()->getMessagesQueue();
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
