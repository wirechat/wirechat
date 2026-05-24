<?php

namespace Wirechat\Wirechat\Workbench\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Wirechat\Wirechat\Models\Read;

/**
 * @extends Factory<Read>
 */
class ReadFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    protected $model = Read::class;

    public function definition(): array
    {
        return [
            //
        ];
    }
}
