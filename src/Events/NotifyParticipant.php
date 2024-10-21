<?php

namespace Namu\WireChat\Events;


use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Namu\WireChat\Helpers\MorphTypeHelper;
use Namu\WireChat\Models\Message;
use Namu\WireChat\Models\Participant;

class NotifyParticipant implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public Model  $participant)
    {

        //Log::info($participant);
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('participant.'.$this->participant->id)
        ];
    }

    public function broadcastWith(): array
    {
        return [
            'message'=> '',
        ];
    }
}
