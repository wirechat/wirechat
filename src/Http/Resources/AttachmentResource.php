<?php

namespace Namu\WireChat\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \Namu\WireChat\Models\Attachment
 */
class AttachmentResource extends JsonResource
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
            'attachable_id' => $this->attachable_type,
            'attachable_type' => $this->attachable_id,
            'file_path' => $this->file_path,
            'url' => $this->url,
            'mime_type' => $this->mime_type,
        ];
    }
}
