<?php

namespace Namu\WireChat\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Namu\WireChat\Events\MessageCreated;
use Namu\WireChat\Models\Conversation;
use Namu\WireChat\Models\Message;

class BroadcastMessage implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    protected  $auth;
    public function __construct(public Conversation $conversation, public Message $message)
    {
        //  
        $this->onQueue(config('wirechat.queue','default'));
        $this->auth= auth()->user();

    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {   
        $userModel = app(config('wirechat.user_model', \App\Models\User::class));

        //Get conversation users
        $participants = $this->conversation->users()->where($userModel->getTable().".id",'!=',$this->auth->id)->get();

        //loop through users 
        foreach ($participants as $key => $participant) {
            broadcast(new MessageCreated($this->message,$participant))->toOthers();
        }

    }


}
