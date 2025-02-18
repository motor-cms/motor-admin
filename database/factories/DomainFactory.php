<?php

namespace Motor\Admin\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Motor\Admin\Models\Client;
use Motor\Admin\Models\Domain;
use Motor\Admin\Models\User;

class DomainFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Domain::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'client_id'  => Client::factory()
                                  ->make()->id,
            'is_active'  => true,
            'name'       => $this->faker->domainName,
            'protocol'   => array_rand(['http', 'https']),
            'host'       => $this->faker->domainName,
            'port'       => array_rand([80, 443]),
            'path'       => '/',
            'created_by' => User::factory()
                                ->make()->id,
            'updated_by' => User::factory()
                                ->make()->id,
        ];
    }
}
