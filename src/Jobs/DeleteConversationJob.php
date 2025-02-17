<?php

namespace Namu\WireChat\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Namu\WireChat\Facades\WireChat;
use Namu\WireChat\Models\Conversation;

class DeleteConversationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(public Conversation $conversation)
    {
        //
        $this->onQueue(WireChat::notificationsQueue());
        $this->delay(now()->addSeconds(5)); // Delay
    }

    public function handle()
    {

        $this->conversation->delete();

    }
}
