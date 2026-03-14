<?php

namespace Wirechat\Wirechat\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \Wirechat\Wirechat\Models\Participant
 */
class ParticipantResource extends JsonResource
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
            'participantable_id' => $this->participantable_id,
            'participantable_type' => $this->participantable_type,
            'role' => $this->role,
            'exited_at' => $this->exited_at,
            'conversation_deleted_at' => $this->conversation_deleted_at,
            'conversation_cleared_at' => $this->conversation_cleared_at,
            'conversation_read_at' => $this->conversation_read_at,
            'last_active_at' => $this->last_active_at,
            'conversation' => $this->whenLoaded('conversation', fn () => new ConversationResource($this->conversation)),
            'participantable' => $this->whenLoaded('participantable', fn () => new WirechatUserResource($this->participantable)),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
