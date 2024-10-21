<?php

namespace Namu\WireChat\Listeners;

use App\Events\MessageCreated;
use App\Jobs\NotifyParticipantJob;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Namu\WireChat\Events\MessageCreated as EventsMessageCreated;
use Namu\WireChat\Jobs\NotifyParticipantJob as JobsNotifyParticipantJob;

class NotifyParticipants implements ShouldQueue
{
    use  Queueable;
    public function __construct()
    {
        $this->onQueue(config('wirechat.broadcasting.message_notification_queue', 'default'));

        //
    }

    public function handle(EventsMessageCreated $event)
    {

        // Dispatch the job to notify participants
        JobsNotifyParticipantJob::dispatch($event->message->conversation, $event->message);
    }
}
