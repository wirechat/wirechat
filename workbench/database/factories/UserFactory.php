<?php

namespace Wirechat\Wirechat\Workbench\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Wirechat\Wirechat\Models\Conversation;
use Wirechat\Wirechat\Models\Message;
use Workbench\App\Models\User;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    protected $model = User::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    public function withMessage(Conversation $conversation, string $body): Factory
    {
        return $this->afterCreating(function (User $user) use ($conversation, $body) {

            Message::factory()->create([
                'conversation_id' => $conversation->id,
                'sendable_id' => $user->id,
                'sendable_type' => get_class($user),
                'body' => $body,
            ]);

        });

    }
}
