<?php

namespace Wirechat\Wirechat\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \Wirechat\Wirechat\Models\Message
 */
class MessageResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'conversation_id' => $this->conversation_id,
            'sendable_id' => $this->sendable_id,
            'sendable_type' => $this->sendable_type,
            'body' => $this->body,
            'type' => $this->type,
            'conversation' => $this->whenLoaded('conversation', fn () => new ConversationResource($this->conversation)),
            'sendable' => $this->whenLoaded('sendable', fn () => new WirechatUserResource($this->sendable)),
            'has_attachment' => $this->hasAttachment(),
            'attachment' => $this->whenLoaded('attachment', fn () => new AttachmentResource($this->attachment)),
            'created_at' => $this->created_at,

        ];
    }
}
