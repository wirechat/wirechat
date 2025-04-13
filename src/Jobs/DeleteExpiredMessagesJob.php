<?php

namespace Namu\WireChat\Jobs;

use Carbon\Carbon;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Namu\WireChat\Enums\Actions;
use Namu\WireChat\Facades\WireChat;
use Namu\WireChat\Models\Conversation;
use Namu\WireChat\Models\Message;

class DeleteExpiredMessagesJob implements ShouldQueue
{
    use Batchable,Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Safely access the created_at property of the message.
     */
    private function getCreatedAt(Message|Model $message): ?Carbon
    {
        return $message->created_at ?? null;
    }

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //
        // $this->onQueue(WireChat::notificationsQueue());

        //   Log::info($this->queue);

    }

    public function handle()
    {
        // Get all conversations with disappearing messages enabled
        $conversations = Conversation::whereNotNull('disappearing_duration')->get();

        foreach ($conversations as $conversation) {

            $messages = $conversation->messages()
                ->withoutGlobalScopes()
                ->where(function ($query) {
                    // Messages that are not kept
                    $query->whereNull('kept_at')
                        // Or messages that are kept but have delete actions or are trashed
                        ->orWhere(function ($query) {
                            $query->whereNotNull('kept_at') // Kept messages
                                ->where(function ($query) {
                                    $query->whereNotNull('deleted_at') // Trashed
                                        ->orWhereHas('actions', function ($query) {
                                            $query->where('type', Actions::DELETE);
                                        });
                                });
                        });
                })
                ->where('created_at', '>', $conversation->disappearing_started_at) // After disappearing_started_at
                ->get();

            foreach ($messages as $message) {

                $createdAt = $this->getCreatedAt($message);

                if ($createdAt && $createdAt->isFuture()) {
                    continue; // Skip processing this message
                }
                if ($createdAt && $createdAt->diffInSeconds(now()) > $conversation->disappearing_duration) {
                    $message->forceDelete(); // Permanently delete the message
                }
            }
        }

    }
}
