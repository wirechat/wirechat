<?php

namespace Wirechat\Wirechat\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Wirechat\Wirechat\Events\MessageCreated;
use Wirechat\Wirechat\Facades\Wirechat;
use Wirechat\Wirechat\Models\Message;
use Wirechat\Wirechat\Traits\InteractsWithPanel;

class BroadcastMessage implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    use InteractsWithPanel;

    /**
     * Create a new job instance.
     */
    protected $participantable;

    protected $messagesTable;

    protected $participantsTable;

    public function __construct(public Message $message, ?string $panel = null)
    {
        $this->resolvePanel($panel);
        //
        $this->onQueue($this->getPanel()->getMessagesQueue());
        $this->participantable = Wirechat::getParticipantable();

        // Get table
        $this->messagesTable = Wirechat::messageModelTable();
        $this->participantsTable = Wirechat::participantModelTable();
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
