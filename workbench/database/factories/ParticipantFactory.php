<?php

namespace Namu\WireChat\Workbench\Database\Factories;


use Illuminate\Database\Eloquent\Factories\Factory;
use Namu\WireChat\Models\Conversation;
use Namu\WireChat\Models\Participant;
use Namu\WireChat\Workbench\App\Models\User as ModelsUser;

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
            'conversation_id'=>Conversation::factory(),
            'user_id'=>ModelsUser::factory()
        ];
    }
}
