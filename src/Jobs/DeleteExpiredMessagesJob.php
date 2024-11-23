<?php

namespace Namu\WireChat\Jobs;

use Carbon\Carbon;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Namu\WireChat\Events\MessageCreated;
use Namu\WireChat\Events\NotifyParticipant;
use Namu\WireChat\Facades\WireChat;
use Namu\WireChat\Models\Conversation;
use Namu\WireChat\Models\Message;
use Namu\WireChat\Models\Participant;
use Namu\WireChat\Notifications\MessageNotification;

class DeleteExpiredMessagesJob implements ShouldQueue
{
    use Dispatchable,Batchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    protected  $auth;

    protected $messagesTable;
    protected $participantsTable;



    public function __construct()
    {
        //  

    }
    public function handle()
    {
        // Get all conversations with disappearing messages enabled
        $conversations = Conversation::whereNotNull('disappearing_duration')->get();
      
        foreach ($conversations as $conversation) {

            $messages = $conversation->messages()
                ->withoutGlobalScopes()
                ->where(function ($query) {
                    $query->whereNull('kept_at') // Not kept
                        ->orWhereNotNull('deleted_at'); // Include trashed messages
                })
                ->where('created_at', '>', $conversation->disappearing_started_at) // After disappearing_started_at
                ->get();
        
            foreach ($messages as $message) {
                // Check if the message is older than the disappearing_duration in relation to now
        if ($message->created_at->diffInSeconds(now()) > $conversation->disappearing_duration) {
            $message->forceDelete(); // Permanently delete the message
        }
                
            }
        }
        
        
    }
    
    

}
