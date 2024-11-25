<?php

namespace Namu\WireChat\Jobs;

use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\Attributes\WithoutRelations;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Namu\WireChat\Events\NotifyParticipant;
use Namu\WireChat\Facades\WireChat;
use Namu\WireChat\Models\Conversation;
use Namu\WireChat\Models\Message;
use Namu\WireChat\Models\Participant;
use Namu\WireChat\Notifications\MessageNotification;

class NotifyParticipants implements ShouldQueue
{
    use Batchable,Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Set a maximum time limit of 60 seconds for the job.
     * Because we don't want users getting old notifications
     */
    public int $timeout = 60;

    public int $retry_after = 65;

    protected $auth;

    protected $messagesTable;

    protected $participantsTable;

    public function __construct(

        public Model $conversation,
        #[WithoutRelations]
        public Message $message)
    {
        //
        $this->onQueue(WireChat::notificationsQueue());
        $this->delay(now()->addSeconds(3)); // Delay
        $this->auth = $message->sendable;

        //Get table
        $this->messagesTable = (new Message)->getTable();
        $this->participantsTable = (new Participant)->getTable();

    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {

        /**
         * Fetch participants, ordered by `last_active_at` in descending order,
         * so that the most recently active participants are notified first. */

        //     $queueToUse = $this->conversation->isPrivate()?WireChat::messagesQueue():WireChat::notificationsQueue();
        $this->conversation->participants()
        //exclude current user
        // ->with('participantable')
            ->where("$this->participantsTable.participantable_id", '!=', $this->auth->id)
            ->where("$this->participantsTable.participantable_type", get_class($this->auth))
        // ->select("$this->participantsTable.*")
            ->latest('last_active_at') // Prioritize active participants
            ->chunk(50, function ($participants) {
                foreach ($participants as $key => $participant) {
                    broadcast(new NotifyParticipant($participant, $this->message));
                }
            });

        // // // Chunk the participants to avoid memory overload
        // $this->conversation->participants()
        //     ->with('participantable')
        //     ->where("$this->participantsTable.participantable_id", '!=', $this->auth->id)
        //     ->where("$this->participantsTable.participantable_type", get_class($this->auth))
        //     ->leftJoin("$this->messagesTable", function ($join) {
        //         $join->on("$this->participantsTable.participantable_id", '=', "$this->messagesTable.sendable_id")
        //             ->on("$this->participantsTable.participantable_type", '=', "$this->messagesTable.sendable_type")
        //             ->where("$this->messagesTable.conversation_id", $this->conversation->id);
        //     })
        //     ->select("$this->participantsTable.*", DB::raw("MAX($this->messagesTable.created_at) as last_message_time")) // Get last message time
        //     ->groupBy("$this->participantsTable.id") // Group by participants
        //     ->orderByDesc('last_message_time') // Order by the most recent message
        //     ->chunk(40, function ($participants) {
        //         // Loop through participants in batches of 100

        //         foreach ($participants as $participant) {
        //             event(new NotifyParticipant($participant,$this->message));
        //             // broadcast(new NotifyParticipant($participant));
        //         }
        //     });

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
        // ->chunk(100, function ($chunkedParticipants)   {
        //     // Collect the participantable instances
        //     $users = collect();

        //     foreach ($chunkedParticipants as $participant) {
        //         $users->push($participant->participantable);
        //     }

        //     // Send the notification to the collection of users (or other participantable models)
        //     Notification::send($users, new MessageNotification($this->message));
        // });

        //     Get conversation participants except auth
        //  $participants = $this->conversation->participants()
        //  ->where('participantable_id','!=',$this->auth->id)
        //  ->where('participantable_type',get_class($this->auth))
        //   ->chunk(500, function ($participants) {
        //             // Loop through participants in batches of 100
        //             foreach ($participants as $participant) {
        //                 broadcast(new NotifyParticipant($participant));
        //             }
        //         });;

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

        // // Loop through users
        // foreach ($participants as $participant) {
        //     broadcast(new NotifyParticipant($participant->participantable,$this->message));
        // }
    }
}
