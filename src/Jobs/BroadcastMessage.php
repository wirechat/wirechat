<?php

namespace Namu\WireChat\Jobs;

use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Namu\WireChat\Events\MessageCreated;
use Namu\WireChat\Events\NotifyParticipant;
use Namu\WireChat\Facades\WireChat;
use Namu\WireChat\Models\Conversation;
use Namu\WireChat\Models\Message;
use Namu\WireChat\Models\Participant;
use Namu\WireChat\Notifications\MessageNotification;

class BroadcastMessage implements ShouldQueue
{
    use Batchable,Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    protected $auth;

    protected $messagesTable;

    protected $participantsTable;

    public function __construct(public Message $message)
    {
        //
        $this->onQueue(WireChat::messagesQueue());
        $this->auth = auth()->user();

        //Get table
        $this->messagesTable = (new Message)->getTable();
        $this->participantsTable = (new Participant)->getTable();
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        //Broadcast to the conversation channel for all participants
        event(new MessageCreated($this->message));

        // $participants = $this->conversation->participants()
        // ->with('participantable')
        // ->where('participantable_id', '!=', $this->auth->id)
        // ->chunk(100, function ($chunkedParticipants) {
        //     // Prepare the jobs for the chunk
        //    // $jobs = [];

        //    $users = collect();

        //    foreach ($chunkedParticipants as $participant) {
        //        $users->push($participant->participantable);
        //    }

        //    // Send the notification to the collection of users (or other participantable models)
        //    Notification::send($users, new MessageNotification($this->message));

        // });

        // $participants = $this->conversation->participants()
        // ->with('participantable')
        // ->where('participantable_id', '!=', $this->auth->id)
        // ->chunk(100, function ($chunkedParticipants) {
        //     // Prepare the jobs for the chunk
        //     $jobs = [];

        //     foreach ($chunkedParticipants as $participant) {
        //         $jobs[] = new NotifySingleParticipantJob($participant, $this->message);
        //     }

        //     // Dispatch the batch of jobs
        //     Bus ::batch($jobs)->dispatch();
        // });

        // Get all participants, including those who haven't sent any messages
        // $participants = $this->conversation->participants()
        // ->with('participantable')
        // ->where("$this->participantsTable.participantable_id", '!=', $this->auth->id)
        // ->where("$this->participantsTable.participantable_type", get_class($this->auth))
        // ->leftJoin("$this->messagesTable", function ($join) {
        //     $join->on("$this->participantsTable.participantable_id", '=', "$this->messagesTable.sendable_id")
        //         ->on("$this->participantsTable.participantable_type", '=', "$this->messagesTable.sendable_type")
        //         ->where("$this->messagesTable.conversation_id", $this->conversation->id);
        // })
        // ->select("$this->participantsTable.*", DB::raw("MAX($this->messagesTable.created_at) as last_message_time")) // Get last message time
        // ->groupBy("$this->participantsTable.id") // Group by participants
        // ->orderByDesc('last_message_time') // Order by the most recent message
        // ->get();

        // // Loop through users
        // foreach ($participants as $participant) {
        //     broadcast(new NotifyParticipant($participant->participantable,$this->message));
        // }
    }
}
