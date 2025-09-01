<?php

namespace Wirechat\Wirechat\Workbench\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Wirechat\Wirechat\Models\Group;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\RoomSetting>
 */
class GroupFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    protected $model = Group::class;

    public function definition(): array
    {
        return [
            //
        ];
    }
}
