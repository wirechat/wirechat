<?php

namespace Namu\WireChat\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Namu\WireChat\Events\MessageCreated;
use Namu\WireChat\Models\Message;
use Namu\WireChat\Models\Participant;
use Namu\WireChat\Traits\InteractsWithPanel;

class BroadcastMessage implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    use InteractsWithPanel;

    /**
     * Create a new job instance.
     */
    protected $auth;

    protected $messagesTable;

    protected $participantsTable;

    public function __construct(public Message $message, ?string $panel = null)
    {
        $this->resolvePanel($panel);
        //
        $this->onQueue($this->getPanel()->getMessagesQueue());
        $this->auth = auth()->user();

        // Get table
        $this->messagesTable = (new Message)->getTable();
        $this->participantsTable = (new Participant)->getTable();
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // Broadcast to the conversation channel for all participants
        event(new MessageCreated($this->message, $this->getPanel()->getId()));
    }
}
