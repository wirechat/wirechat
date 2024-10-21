<?php

namespace Namu\WireChat\Jobs;

use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Namu\WireChat\Events\MessageCreated;
use Namu\WireChat\Events\NotifyParticipant;
use Namu\WireChat\Models\Conversation;
use Namu\WireChat\Models\Message;
use Namu\WireChat\Models\Participant;

class NotifySingleParticipantJob implements ShouldQueue
{
    use Dispatchable, Batchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    protected  $auth;

    protected $messagesTable;
    protected $participantsTable;



    public function __construct(public Participant $participant, public Message $message)
    {
        // Initialization

        $this->onQueue(config('wirechat.broadcasting.message_notification_queue', 'default'));
    }

    public function handle(): void
    {
        broadcast(new NotifyParticipant($this->participant->participantable, $this->message));
    }

    
}
