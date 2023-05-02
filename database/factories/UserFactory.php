<?php

namespace Motor\Admin\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Motor\Admin\Models\User;

class UserFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = User::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'name'           => $this->faker->name,
            'email'          => $this->faker->unique()->safeEmail,
            'password'       => ! isset($password) ?: bcrypt('secret'),
            'api_token'      => Str::random(60),
            'remember_token' => Str::random(10),
        ];
    }
}
