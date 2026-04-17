<?php

namespace Motor\Admin\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Motor\Admin\Models\Client;
use Motor\Admin\Models\User;

class ClientFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Client::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'name'               => $this->faker->sentence(),
            'slug'               => $this->faker->slug(),
            'country_iso_3166_1' => Str::random(2),
            'created_by'         => User::factory()->make()->id,
            'updated_by'         => User::factory()->make()->id,
        ];
    }

    /**
     * State with a populated frontend_config.
     */
    public function withFrontendConfig(array $config = []): static
    {
        return $this->state(fn () => [
            'frontend_config' => array_merge([
                'brand' => [
                    'name' => $this->faker->company(),
                    'logoAlt' => 'Logo '.$this->faker->company(),
                ],
                'features' => [
                    'orderLine' => true,
                    'appointments' => true,
                    'clickpath' => true,
                    'footerMenu' => true,
                ],
                'social' => [
                    'instagram' => 'https://www.instagram.com/'.$this->faker->slug(),
                    'facebook' => 'https://www.facebook.com/'.$this->faker->slug(),
                ],
                'seo' => [
                    'siteName' => $this->faker->company(),
                ],
            ], $config),
        ]);
    }
}
