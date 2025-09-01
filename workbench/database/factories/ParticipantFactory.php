<?php

namespace Wirechat\Wirechat\Workbench\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Wirechat\Wirechat\Enums\ParticipantRole;
use Wirechat\Wirechat\Models\Conversation;
use Wirechat\Wirechat\Models\Participant;
use Workbench\App\Models\User;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Participant>
 */
class ParticipantFactory extends Factory
{
    protected $model = Participant::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'conversation_id' => Conversation::factory(),
            'role' => ParticipantRole::PARTICIPANT,
            'participantable_id' => User::factory(),
            'participantable_type' => function (array $attributes) {
                return User::find($attributes['participantable_id'])->getMorphClass();
            },
        ];
    }
}
