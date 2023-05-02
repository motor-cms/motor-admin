<?php

namespace Motor\Admin\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Motor\Admin\Models\ConfigVariable;

class ConfigVariableFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = ConfigVariable::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'package'      => Str::kebab($this->faker->word),
            'group'        => Str::kebab($this->faker->word),
            'name'         => Str::kebab($this->faker->word),
            'value'        => $this->faker->word,
            'is_invisible' => false,
        ];
    }
}
