<?php

namespace Namu\WireChat\Workbench\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Namu\WireChat\Enums\ConversationType;
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

    public function withParticipants(array $models): Factory
    {
        return $this->afterCreating(function (Conversation $conversation) use($models) {

            foreach ($models as $key => $model) {

            Participant::factory()->create(['conversation_id'=>$conversation->id,'user_id'=>$model->id]);

            }
        });
    
    }
}
