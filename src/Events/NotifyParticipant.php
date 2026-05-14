<?php

namespace Wirechat\Wirechat\Events;

use Carbon\Carbon;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Wirechat\Wirechat\Facades\Wirechat;
use Wirechat\Wirechat\Helpers\MorphClassResolver;
use Wirechat\Wirechat\Http\Resources\MessageResource;
use Wirechat\Wirechat\Models\Message;
use Wirechat\Wirechat\Models\Participant;
use Wirechat\Wirechat\Traits\InteractsWithPanel;

class NotifyParticipant implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;
    use InteractsWithPanel;

    public $participantType;

    public $participantId;

    /**
     * Note:
     * - Messages no longer have a direct `conversation_id`. Conversation is
     *   obtained via the participant (convenience accessor on Message).
     * - `sendable` is kept only for backward-compatibility as an attribute
     *   (not an Eloquent relation). We eagerly load the participant and its
     *   participantable (the actual user) instead.
     *
     * @param  Participant|Model  $participant  Participant instance OR a model representing the target participantable
     */
    public function __construct(public Participant|Model $participant, public Message $message, ?string $panel = null)
    {
        if ($participant instanceof Participant) {
            $participant->loadMissing('participantable');

            $this->participantType = $participant->participantable_type;
            $this->participantId = $participant->participantable_id;
        } else {
            $this->participantType = $participant->getMorphClass();
            $this->participantId = $participant->getKey();
        }

        $this->resolvePanel($panel);

        // Eager-load the participant and its participantable (the sender),
        // and the participant's conversation with group, plus any attachment.
        // We use loadMissing so we don't override already-loaded relationships.
        $this->message->loadMissing([
            'participant.participantable',
            'participant.conversation.group',
            'attachment',
        ]);

        // For backwards-compatibility, you can still access $message->sendable()
        // (which returns the user attribute). Note: 'sendable' is not a relation
        // and therefore is not passed to loadMissing.
    }

    /**
     * The name of the queue on which to place the broadcasting job.
     */
    public function broadcastQueue(): string
    {
        // Prefer conversation via accessor; fallback to participant->conversation.
        $conversation = $this->message->conversation ?? $this->message->participant?->conversation;

        $isPrivate = $conversation?->isPrivate() ?? false;

        return $isPrivate ? $this->getPanel()->getMessagesQueue() : $this->getPanel()->getEventsQueue();
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
        $channels = [];

        $panelId = $this->getPanel()->getId();
        $channels[] = "$panelId.participant.$encodedType.$this->participantId";

        return array_map(fn ($channelName) => new PrivateChannel($channelName), $channels);
    }

    public function broadcastWith(): array
    {
        // Use conversation accessor (via participant) to build the redirect URL.
        $conversation = $this->message->conversation ?? $this->message->participant?->conversation;
        $conversationId = $conversation?->id;
        $isMessageRequest = (bool) ($conversation?->isPrivate() && $conversation->hasActiveMessageRequest());

        return [
            'message' => new MessageResource($this->message),
            'redirect_url' => $this->getPanel()->chatRoute($conversationId),
            'is_request' => $isMessageRequest,
            'notification' => $this->notificationPreferences($conversation),
        ];
    }

    /**
     * @return array{enabled: bool, show_preview: bool}
     */
    protected function notificationPreferences($conversation): array
    {
        $recipient = $this->participant instanceof Participant
            ? $this->participant->participantable
            : $this->participant;

        $settings = Wirechat::settings($recipient);
        $conversationNotificationsEnabled = $conversation?->isGroup()
            ? $settings->group_message_notifications_enabled
            : $settings->direct_message_notifications_enabled;

        return [
            'enabled' => $settings->notifications_enabled && $conversationNotificationsEnabled,
            'show_preview' => $settings->notification_previews_enabled,
        ];
    }
}
