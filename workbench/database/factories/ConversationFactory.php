<?php

namespace Namu\WireChat\Workbench\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Namu\WireChat\Enums\ConversationType;
use Namu\WireChat\Enums\ParticipantRole;
use Namu\WireChat\Models\Conversation;
use Namu\WireChat\Models\Participant;
use Namu\WireChat\Workbench\App\Models\User;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Attachment>
 */
class ConversationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */

    protected $model = Conversation::class;
    public function definition(): array
    {
        return [
        'type' =>ConversationType::PRIVATE
        ];
    }

    public function withParticipants(array $models,ParticipantRole $role=null): Factory
    {
        return $this->afterCreating(function (Conversation $conversation) use($models,$role) {

            $role=$role?$role:ParticipantRole::OWNER;


            foreach ($models as $key => $model) {

            Participant::factory()->create([
                'conversation_id'=>$conversation->id,
                'participantable_id'=>$model->id,
                'participantable_type'=>get_class($model),
                'role'=>$role
            ]);

            }
        });
    
    }
}
