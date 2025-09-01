<?php

namespace Wirechat\Wirechat\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \Illuminate\Database\Eloquent\Model
 */
class WirechatUserResource extends JsonResource
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
            'wirechat_name' => $this->wirechat_name,
            'wirechat_avatar_url' => $this->wirechat_avatar_url,
        ];
    }
}
