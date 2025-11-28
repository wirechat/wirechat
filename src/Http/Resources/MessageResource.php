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
            'body' => $this->body,
            'type' => $this->type,
            'conversation' => $this->when($this->conversation !== null, fn () => new ConversationResource($this->conversation)),
            'user' => $this->when($this->user, fn () => new WirechatUserResource($this->user)),
            'sendable' => $this->when($this->user, fn () => new WirechatUserResource($this->user)), // bacwards compatibility
            'participant' => $this->whenLoaded('participant', fn () => new ParticipantResource($this->participant)),
            'has_attachment' => $this->hasAttachment(),
            'attachment' => $this->whenLoaded('attachment', fn () => new AttachmentResource($this->attachment)),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
