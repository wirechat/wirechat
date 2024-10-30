<?php

namespace Namu\WireChat\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class MessageNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public $message;

    public function __construct($message)
    {
        $this->message = $message;
        $this->onQueue(config('wirechat.broadcasting.message_notification_queue', 'default'));
        $this->delay(now()->addSeconds(2)); // Delay the job by 5 seconds
    }


    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['broadcast'];
    }

    // Broadcast data for real-time notifications
    public function toBroadcast($notifiable)
    {
        return new BroadcastMessage([
            'message_id' => $this->message,
            'conversation_id' => $this->message->conversation_id,
        ]);
    }

}
