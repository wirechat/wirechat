<?php

namespace Namu\WireChat\Workbench\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Namu\WireChat\Models\Conversation;
use Namu\WireChat\Models\Message;
use Workbench\App\Models\User;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Attachment>
 */
class MessageFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    protected $model = Message::class;

    public function definition(): array
    {
        // Create a User instance for the sendable entity
        $user = User::factory()->create();

        return [
            'conversation_id' => Conversation::factory(),
            'sendable_id' => $user->id,
            'sendable_type' => $user->getMorphClass(), // Get the morph class of the user
            'body' => $this->faker->text, // Add a body for completeness
            'reply_id' => null,
        ];
    }

    public function sender($sender): Factory
    {
        return $this->state(function (array $attributes) use ($sender) {
            return [
                'sendable_id' => $sender->id,
                'sendable_type' => $sender->getMorphClass(),
            ];
        });

    }
}
