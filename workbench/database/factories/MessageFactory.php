<?php

namespace Namu\WireChat\Workbench\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Namu\WireChat\Models\Conversation;
use Namu\WireChat\Models\Message;
use Namu\WireChat\Workbench\App\Models\User;

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
        return [
        'sender_id' => User::factory(),
        'receiver_id' => User::factory(),
        'conversation_id' => Conversation::factory(),

        ];
    }
}
