<?php

namespace Motor\Admin\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Motor\Admin\Models\Category;

class CategoryFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Category::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'name'      => $this->faker->word,
            'scope'     => $this->faker->word,
            'parent_id' => null,
        ];
    }
}
