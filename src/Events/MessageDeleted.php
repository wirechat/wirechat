<?php

namespace Namu\WireChat\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Queue\SerializesModels;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Support\Facades\Log;
use Namu\WireChat\Helpers\MorphTypeHelper;
use Namu\WireChat\Models\Conversation;
use Namu\WireChat\Models\Message;

class MessageDeleted implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $message;
    // public $receiver;

    public function __construct(Message $message,public Conversation $conversation)
    {
       // dd($message);
        $this->message = $message;
        //$this->onQueue(config('wirechat.broadcasting.messages_queue', 'default'));
        // $this->receiver = $receiver;
       // Log::info(["$receiver->id"=>$receiver->id]);

        //Exclude the current user from receiving the broadcast.
        //$this->broadcastToEveryone();
    }

       /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\PrivateChannel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('conversation.'.$this->message->conversation_id)
            // new PrivateChannel('wirechat')
        ];
    }
    
    // public function broadcastOn(): Channel
    // {

    //      return   new Channel('test');
    // }


    /**
 * The name of the queue on which to place the broadcasting job.
 */
public function broadcastQueue(): string
{
    return 'default';
}

/**
     * Get the data to broadcast.
     *
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
     //   dd($this->message);
        return [
            'message'=> $this->message
            // 'receiver_id'=>$this->receiver?->id
        ];
    }
    }