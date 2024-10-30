<?php

namespace Namu\WireChat\Listeners;

use App\Events\MessageCreated;
use App\Jobs\NotifyParticipantJob;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Bus;
use Namu\WireChat\Events\BroadcastMessageEvent;

use Namu\WireChat\Jobs\BroadcastMessage;
use Namu\WireChat\Jobs\NotifyParticipantsJob;

class NotifyParticipantsListener implements ShouldQueue
{
    use  Queueable;
    public function __construct()
    {

        $this->onQueue(config('wirechat.broadcasting.message_notification_queue', 'default'));
    }

    public function handle(BroadcastMessageEvent $event)
    {





        Bus::batch([
          new  BroadcastMessage($event->message,$event->message->conversation),
           new NotifyParticipantsJob($event->message->conversation,$event->message)
        ])->dispatch();

        // Dispatch the job to notify participants
     //   JobsNotifyParticipantJob::dispatch($event->message->conversation, $event->message);
    }
}
