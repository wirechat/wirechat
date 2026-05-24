<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Wirechat\Wirechat\Models\Attachment;

/**
 * @extends Factory<Attachment>
 */
class AttachmentFactory extends Factory
{
    protected $model = Attachment::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            //
        ];
    }
}
