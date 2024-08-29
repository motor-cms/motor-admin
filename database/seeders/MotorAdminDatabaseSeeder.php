<?php

namespace Motor\Admin\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;

class MotorAdminDatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Artisan::call('scout:delete-all-indexes');
        Artisan::call('scout:sync-index-settings');

        $this->call([
            RolesTableSeeder::class,
            UsersTableSeeder::class,
            ClientsTableSeeder::class,
            DomainsTableSeeder::class,
            LanguagesTableSeeder::class,
            PermissionsTableSeeder::class,
            ConfigVariablesTableSeeder::class,
            EmailTemplatesTableSeeder::class,
            CategoriesTableSeeder::class,
            RolesPermissionsDatabaseSeeder::class,
        ]);
    }
}
