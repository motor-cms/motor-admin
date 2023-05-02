<?php

namespace Motor\Admin\Database\Seeders;

use Illuminate\Database\Seeder;
use Motor\Admin\Models\Role;

/**
 * Class UsersTableSeeder
 */
class RolesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Role::factory()
            ->create([
                'name'       => 'SuperAdmin',
                'guard_name' => 'web',
            ]);
    }
}
