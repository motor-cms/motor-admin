<?php

namespace Motor\Admin\Database\Seeders;

use Illuminate\Database\Seeder;

class MotorAdminDatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $this->call([
            RolesTableSeeder::class,
            UsersTableSeeder::class,
            ClientsTableSeeder::class,
            LanguagesTableSeeder::class,
            PermissionsTableSeeder::class,
        ]);
    }
}
