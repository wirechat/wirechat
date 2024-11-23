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
use Namu\WireChat\Enums\Actions;
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
 

    public function __construct()
    {
        //  
       // $this->onQueue(WireChat::notificationsQueue());
       
    //   Log::info($this->queue);

    }
    public function handle()
    {
        // Get all conversations with disappearing messages enabled
        $conversations = Conversation::whereNotNull('disappearing_duration')->get();
      
        foreach ($conversations as $conversation) {

            $messages = $conversation->messages()
            ->withoutGlobalScopes()
            ->where(function ($query) {
                // Messages that are not kept
                $query->whereNull('kept_at')
                    // Or messages that are kept but have delete actions or are trashed
                    ->orWhere(function ($query) {
                        $query->whereNotNull('kept_at') // Kept messages
                            ->where(function ($query) {
                                $query->whereNotNull('deleted_at') // Trashed
                                    ->orWhereHas('actions', function ($query) {
                                        $query->where('type', Actions::DELETE);
                                    });
                            });
                    });
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
