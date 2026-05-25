<?php

namespace Wirechat\Wirechat\Workbench\Database\Factories;

use App\Models\RoomSetting;
use Illuminate\Database\Eloquent\Factories\Factory;
use Wirechat\Wirechat\Models\Group;

/**
 * @extends Factory<RoomSetting>
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
