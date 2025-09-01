<?php

namespace Wirechat\Wirechat\Jobs;

use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\Attributes\WithoutRelations;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Wirechat\Wirechat\Events\NotifyParticipant;
use Wirechat\Wirechat\Models\Conversation;
use Wirechat\Wirechat\Models\Message;
use Wirechat\Wirechat\Models\Participant;
use Wirechat\Wirechat\Traits\InteractsWithPanel;

class NotifyParticipants implements ShouldQueue
{
    use Batchable,Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    use InteractsWithPanel;

    /**
     * Set a maximum time limit of 60 seconds for the job.
     * Because we don't want users getting old notifications
     */
    public int $timeout = 60;

    public int $retry_after = 65;

    public int $tries = 1;

    protected $auth;

    protected $messagesTable;

    protected $participantsTable;

    public function __construct(

        public Conversation $conversation,
        #[WithoutRelations]
        public Message $message,
        ?string $panel = null
    ) {
        $this->resolvePanel($panel);
        //
        $this->onQueue($this->getPanel()->getEventsQueue());
        //  $this->delay(now()->addSeconds(3)); // Delay
        $this->auth = $message->sendable;

        // Get table
        $this->participantsTable = (new Participant)->getTable();

        // dd($this);

    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {

        // Check if the message is too old
        $messageAgeInSeconds = now()->diffInSeconds($this->message->created_at);

        // delete the job if it is greater then 60 seconds
        if ($messageAgeInSeconds > 60) {
            // Delete the job and stop further processing
            // $this->fail();
            $this->delete();
            Log::error('Participants not notified : Job older than '.$messageAgeInSeconds.'seconds');

            return;
        }

        /**
         * Fetch participants, ordered by `last_active_at` in descending order,
         * so that the most recently active participants are notified first. */
        Participant::where('conversation_id', $this->conversation->id)
            ->withoutParticipantable($this->auth)
            ->latest('last_active_at') // Prioritize active participants
            ->chunk(50, function ($participants) {
                foreach ($participants as $key => $participant) {
                    broadcast(new NotifyParticipant($participant, $this->message, $this->getPanel()->getId()));
                }
            });

    }
}
