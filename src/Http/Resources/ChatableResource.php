<?php

namespace Namu\WireChat\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \Illuminate\Database\Eloquent\Model
 */
class ChatableResource extends JsonResource
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
            'type' => $this->getMorphClass(),
            'display_name' => $this->display_name,
            'cover_url' => $this->cover_url,
        ];
    }
}
