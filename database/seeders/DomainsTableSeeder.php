<?php

namespace Motor\Admin\Database\Seeders;

use Illuminate\Database\Seeder;
use Motor\Admin\Models\Client;
use Motor\Admin\Models\Domain;

/**
 * Class ClientsTableSeeder
 */
class DomainsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create testdomains here
        Domain::factory()
              ->create([
                  'client_id'  => Client::first()->id,
                  'is_active'  => true,
                  'name'       => 'localhost',
                  'protocol'   => 'http',
                  'host'       => 'localhost',
                  'port'       => '80',
                  'path'       => '/',
                  'target'     => '',
                  'parameters' => '',
              ]);

        Domain::factory()
              ->count(4)
              ->make();
    }
}
