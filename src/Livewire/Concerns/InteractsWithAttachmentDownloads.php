<?php

namespace Wirechat\Wirechat\Livewire\Concerns;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Wirechat\Wirechat\Facades\Wirechat;
use Wirechat\Wirechat\Models\Attachment;
use Wirechat\Wirechat\Models\Conversation;
use Wirechat\Wirechat\Models\Group;
use Wirechat\Wirechat\Models\Message;

trait InteractsWithAttachmentDownloads
{
    /**
     * Download an attachment by encrypted ID.
     *
     * This is intended to be called from a Livewire action:
     *   wire:click="download('{{ encrypt($attachment->id) }}')"
     */
    public function download(string $encryptedId): StreamedResponse
    {
        return $this->completeDownload((int) decrypt($encryptedId));
    }

    protected function completeDownload(int $attachmentId): StreamedResponse
    {
        $attachment = Wirechat::attachmentModelClass()::query()->findOrFail($attachmentId);
        $this->authorizeAttachmentDownload($attachment);

        $path = $attachment->file_path ?? $attachment->file_name;
        $downloadName = $attachment->original_name
            ?? $attachment->file_name
            ?? basename($path);

        return Storage::disk($this->storageDisk())->download($path, $downloadName);
    }

    protected function authorizeAttachmentDownload(Attachment $attachment): void
    {
        $auth = auth()->user();

        abort_unless($auth !== null, 401);

        $conversation = $this->resolveAttachmentConversation($attachment);

        abort_unless($conversation !== null && $auth->belongsToConversation($conversation), 403);
    }

    protected function resolveAttachmentConversation(Attachment $attachment): ?Conversation
    {
        $attachable = $attachment->attachable()->first();

        if (! $attachable instanceof Model) {
            return null;
        }

        if ($attachable instanceof Message) {
            return $attachable->conversation;
        }

        if ($attachable instanceof Group) {
            return $attachable->conversation;
        }

        if ($attachable instanceof Conversation) {
            return $attachable;
        }

        return null;
    }

    protected function storageDisk(): string
    {
        return Wirechat::storage()->disk();
    }
}
