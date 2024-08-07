<?php

namespace Motor\Admin\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Motor\Admin\Models\Client;
use Motor\Admin\Models\EmailTemplate;
use Motor\Admin\Models\Language;
use Motor\Admin\Models\User;

class EmailTemplateFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = EmailTemplate::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'client_id'               => Client::factory()->make()->id,
            'language_id'             => Language::factory()->make()->id,
            'name'                    => $this->faker->sentence,
            'subject'                 => $this->faker->sentence,
            'body_text'               => $this->faker->paragraph(1),
            'body_html'               => $this->faker->paragraph(1),
            'default_sender_email'    => $this->faker->email,
            'default_sender_name'     => $this->faker->name,
            'default_recipient_email' => $this->faker->email,
            'default_recipient_name'  => $this->faker->name,
            'created_by'              => User::factory()->make()->id,
            'updated_by'              => User::factory()->make()->id,
        ];
    }
}
