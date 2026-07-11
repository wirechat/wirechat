<?php

namespace Wirechat\Wirechat\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Wirechat\Wirechat\Enums\MessageRequestStatus;
use Wirechat\Wirechat\Helpers\MorphClassResolver;
use Wirechat\Wirechat\Traits\InteractsWithPanel;

class MessageRequestUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;
    use InteractsWithPanel;

    public string $participantType;

    public int|string $participantId;

    public MessageRequestStatus $requestStatus;

    public function __construct(
        public Model $participantable,
        public int|string $conversationId,
        MessageRequestStatus|string $status,
        ?string $panel = null
    ) {
        $this->participantType = $participantable->getMorphClass();
        $this->participantId = $participantable->getKey();
        $this->requestStatus = $status instanceof MessageRequestStatus ? $status : MessageRequestStatus::from($status);

        $this->resolvePanel($panel);
    }

    public function broadcastQueue(): string
    {
        return $this->getPanel()->getEventsQueue();
    }

    public function broadcastOn(): array
    {
        $encodedType = MorphClassResolver::encode($this->participantType);
        $panelId = $this->getPanel()->getId();

        return [new PrivateChannel("{$panelId}.participant.{$encodedType}.{$this->participantId}")];
    }

    public function broadcastWith(): array
    {
        return [
            'conversation_id' => $this->conversationId,
            'status' => $this->requestStatus->value,
            'redirect_url' => $this->requestStatus === MessageRequestStatus::ACCEPTED
                ? $this->getPanel()->chatUrl($this->conversationId)
                : $this->getPanel()->chatsUrl(),
        ];
    }
}
