<?php

namespace Wirechat\Wirechat\Workbench\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Wirechat\Wirechat\Enums\MessageRequestStatus;
use Wirechat\Wirechat\Models\Conversation;
use Wirechat\Wirechat\Models\MessageRequest;
use Workbench\App\Models\User;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\Wirechat\Wirechat\Models\MessageRequest>
 */
class MessageRequestFactory extends Factory
{
    protected $model = MessageRequest::class;

    public function definition(): array
    {
        $sender = User::factory()->create();
        $recipient = User::factory()->create();

        return [
            'conversation_id' => Conversation::factory()->create()->getKey(),
            'sender_id' => $sender->getKey(),
            'sender_type' => $sender->getMorphClass(),
            'recipient_id' => $recipient->getKey(),
            'recipient_type' => $recipient->getMorphClass(),
            'status' => MessageRequestStatus::PENDING,
        ];
    }
}
