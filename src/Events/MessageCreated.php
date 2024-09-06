<?php

namespace Namu\WireChat\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Queue\SerializesModels;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Support\Facades\Log;
use Namu\WireChat\Helpers\MorphTypeHelper;

use Namu\WireChat\Models\Message;

class MessageCreated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $message;
    public $receiver;

    public function __construct(Message $message,Model $receiver)
    {
        $this->message = $message;
        $this->receiver = $receiver;
        Log::info(["$receiver->id"=>$receiver->id]);

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
            new PrivateChannel('conversation.' . $this->message->conversation_id),
    
            // In the participant, make sure the type is encoded using deslashType
            new PrivateChannel('participant.'.MorphTypeHelper::deslash(get_class($this->receiver)).'.'.$this->receiver->id)
        ];
    }
    

    // public function broadcastOn(): Channel
    // {

    //      return   new Channel('test');
    // }
    /**
     * Get the data to broadcast.
     *
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'message'=> $this->message,
            'receiver_id'=>$this->receiver?->id
        ];
    }
    }