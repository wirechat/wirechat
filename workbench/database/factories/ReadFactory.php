<?php

namespace Namu\WireChat\Workbench\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Namu\WireChat\Models\Read;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Read>
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
