<?php

namespace Namu\WireChat\Events;

use Carbon\Carbon;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Namu\WireChat\Facades\WireChat;
use Namu\WireChat\Helpers\MorphClassResolver;
use Namu\WireChat\Http\Resources\MessageResource;
use Namu\WireChat\Models\Message;
use Namu\WireChat\Models\Participant;

class NotifyParticipant implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $participantType;

    public $participantId;

    public function __construct(public Participant|Model $participant, public Message $message)
    {

        if ($participant instanceof Participant) {
            $this->participantType = $participant->participantable_type;
            $this->participantId = $participant->participantable_id;
        } else {

            $this->participantType = $participant->getMorphClass();
            $this->participantId = $participant->getKey();
        }

        //  $this->dontBroadcastToCurrentUser();

        $message->load('conversation.group', 'sendable', 'attachment');

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
        $isNotExpired = Carbon::parse($this->message->created_at)->gt(Carbon::now()->subMinute());

        return $isNotExpired;
    }

    public function broadcastOn(): array
    {
        $encodedType = MorphClassResolver::encode($this->participantType);

        return [
            new PrivateChannel('participant.'.$encodedType.'.'.$this->participantId),
        ];
    }

    public function broadcastWith(): array
    {
        return [
            'message' => new MessageResource($this->message),
            'redirect_url' => route(WireChat::viewRouteName(), [$this->message->conversation_id]),
        ];
    }
}
