<?php

namespace Motor\Admin\Database\Seeders;

use Illuminate\Database\Seeder;
use Motor\Admin\Models\Client;
use Motor\Admin\Models\User;

/**
 * Class ClientsTableSeeder
 */
class ClientsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Client::factory()
            ->create([
                'name'       => 'Default',
                'created_by' => User::first(),
                'updated_by' => User::first(),
            ]);
    }
}
