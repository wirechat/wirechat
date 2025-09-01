<?php

namespace Wirechat\Wirechat\Events;

use Carbon\Carbon;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Wirechat\Wirechat\Models\Message;
use Wirechat\Wirechat\Traits\InteractsWithPanel;

class MessageCreated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithQueue,InteractsWithSockets, Queueable ,SerializesModels;
    use InteractsWithPanel;

    public $message;
    // public $receiver;

    public function __construct(Message $message, ?string $panel = null)
    {
        $this->message = $message->load([]);

        $this->resolvePanel($panel);

        $this->onQueue($this->getPanel()->getMessagesQueue());
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

    public function broadcastWhen(): bool
    {
        // Check if the message is not older than 1 minutes
        $isNotExpired = Carbon::parse($this->message->created_at)->gt(Carbon::now()->subMinute());

        return $isNotExpired;
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
        return [
            'message' => [
                'id' => $this->message->id,
                'conversation_id' => $this->message->conversation_id,
            ],

        ];
    }
}
