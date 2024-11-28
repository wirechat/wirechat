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

use Namu\WireChat\Events\NotifyParticipant;
use Namu\WireChat\Facades\WireChat;
use Namu\WireChat\Models\Message;
use Namu\WireChat\Models\Participant;

class NotifyParticipants implements ShouldQueue
{
    use Batchable,Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Set a maximum time limit of 60 seconds for the job.
     * Because we don't want users getting old notifications
     */
    public int $timeout = 60;

    public int $retry_after = 65;
    public int $tries=1;

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
        $this->participantsTable = (new Participant)->getTable();

        //dd($this);

    }



      /**
     * Get the middleware the job should pass through.
     */
    // public function middleware(): array
    // {
   
    //     return [
    //         new SkipIfOlderThanSeconds(60), // You can pass a custom max age in seconds
    //     ];
    // }


    


    /**
     * Execute the job.
     */
    public function handle(): void
    {
       // Check if the message is too old
        $messageAgeInSeconds = now()->diffInSeconds($this->message->created_at);

        #delete the job if it is greater then 60 seconds 
        if ($messageAgeInSeconds > 60) {
            // Delete the job and stop further processing
            //$this->fail();
           $this->delete();
            return;
        }
    
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
