<?php

namespace Motor\Admin\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Motor\Admin\Models\AISystemPrompt;

class AiSystemPromptFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = AISystemPrompt::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'name'    => $this->faker->name,
            'prompt' => $this->faker->sentence(),
        ];
    }
}
