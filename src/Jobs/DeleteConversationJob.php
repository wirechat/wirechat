<?php

namespace Namu\WireChat\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\Attributes\WithoutRelations;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Namu\WireChat\Models\Conversation;
use Namu\WireChat\Traits\InteractsWithPanel;

class DeleteConversationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    use InteractsWithPanel;

    /**
     * Create a new job instance.
     */
    public function __construct(
        #[WithoutRelations]
        public Conversation $conversation, ?string $panel = null)
    {
        $this->resolvePanel($panel);
        //
        $this->onQueue($this->getPanel()->getEventsQueue());

        $this->delay(now()->addSeconds(5)); // Delay
    }

    public function handle()
    {

        $this->conversation->delete();

    }
}
