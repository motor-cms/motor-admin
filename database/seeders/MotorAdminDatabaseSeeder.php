<?php

namespace Motor\Admin\Database\Seeders;

use Illuminate\Database\Seeder;

class MotorAdminDatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->call([
            RolesTableSeeder::class,
            UsersTableSeeder::class,
            ClientsTableSeeder::class,
            LanguagesTableSeeder::class,
            PermissionsTableSeeder::class,
            ConfigVariablesTableSeeder::class,
            EmailTemplatesTableSeeder::class,
            CategoriesTableSeeder::class,
        ]);
    }
}
