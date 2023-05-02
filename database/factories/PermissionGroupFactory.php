<?php

namespace Motor\Admin\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Motor\Admin\Models\PermissionGroup;

class PermissionGroupFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = PermissionGroup::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->word,
        ];
    }
}
