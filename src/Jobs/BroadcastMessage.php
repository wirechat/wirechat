<?php

namespace Namu\WireChat\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Namu\WireChat\Events\MessageCreated;
use Namu\WireChat\Events\NotifyParticipant;
use Namu\WireChat\Models\Conversation;
use Namu\WireChat\Models\Message;
use Namu\WireChat\Models\Participant;

class BroadcastMessage implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    protected  $auth;

    protected $messagesTable;
    protected $participantsTable;



    public function __construct(public Conversation $conversation, public Message $message)
    {
        //  
        $this->onQueue(config('wirechat.queue', 'default'));
        $this->auth = auth()->user();


        #Get table
        $this->messagesTable = (new Message())->getTable();
        $this->participantsTable = (new Participant())->getTable();
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        //Broadcast to the conversation channel for all participants
        broadcast(new MessageCreated($this->message))->toOthers();

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
        //     broadcast(new NotifyParticipant($this->message, $participant->participantable))->toOthers();
        // }
    }
}
