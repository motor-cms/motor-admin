<?php

namespace Motor\Admin\Database\Seeders;

use Illuminate\Database\Seeder;
use Motor\Admin\Models\Client;
use Motor\Admin\Models\EmailTemplate;
use Motor\Admin\Models\Language;

/**
 * Class EmailTemplatesTableSeeder
 */
class EmailTemplatesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        EmailTemplate::factory()
            ->create([
                'client_id'               => Client::first()->id,
                'language_id'             => Language::first()->id,
                'name'                    => 'Error-Template',
                'slug'                    => 'motor-backend-error-template',
                'subject'                 => 'An error has occurred',
                'body_text'               => 'Error',
                'body_html'               => 'Error',
                'default_sender_name'     => 'Motor CMS Admin',
                'default_sender_email'    => 'admin@motor-cms.com',
                'default_recipient_name'  => 'Motor User',
                'default_recipient_email' => 'user@motor-cms.com',
            ]);
    }
}
