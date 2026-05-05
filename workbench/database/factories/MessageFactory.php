<?php

namespace Wirechat\Wirechat\Workbench\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Wirechat\Wirechat\Enums\ParticipantRole;
use Wirechat\Wirechat\Models\Conversation;
use Wirechat\Wirechat\Models\Message;
use Wirechat\Wirechat\Models\Participant;
use Workbench\App\Models\User;

class MessageFactory extends Factory
{
    protected $model = Message::class;

    public function definition(): array
    {
        $conversation = Conversation::factory()->create();

        // Create user + participant
        $user = User::factory()->create();

        // Create user
        $participant = Participant::factory()->create([
            'conversation_id' => $conversation->id,
            'participantable_id' => $user->id,
            'participantable_type' => $user->getMorphClass(),
        ]);

        return [
            'conversation_id' => $conversation->id,
            'participant_id' => $participant->id,
            'body' => $this->faker->text,
            'reply_id' => null,
        ];
    }

    // MessageFactory.php (sender state)
    public function sender($sender): Factory
    {
        return $this->state(function (array $attributes) use ($sender) {
            // Resolve conversation id (handle factory / missing values)
            $conversationId = $attributes['conversation_id'] ?? null;

            if ($conversationId instanceof \Illuminate\Database\Eloquent\Factories\Factory) {
                $conversationId = $conversationId->create()->getKey();
            }

            // Ensure we have a Conversation model
            $conversation = $conversationId
                ? Conversation::find($conversationId) ?? Conversation::factory()->create()
                : Conversation::factory()->create();

            // Get or create participant for this conversation & sender
            $participant = $conversation->participants()->firstOrCreate(
                [
                    'participantable_id' => $sender->getKey(),
                    'participantable_type' => $sender->getMorphClass(),
                ],
                [
                    // provide any required default fields here
                    'role' => ParticipantRole::OWNER,
                ]
            );

            return [
                'conversation_id' => $conversation->id,
                'participant_id' => $participant->id,
            ];
        });
    }
}
