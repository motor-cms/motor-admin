<?php

namespace Motor\Admin\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Motor\Admin\Models\Role;
use Motor\Admin\Models\User;

/**
 * Class UsersTableSeeder
 */
class UsersTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $user = User::factory()->create([
            'name'       => 'Motor Admin',
            'email'      => 'admin@motor-cms.com',
            'password'   => bcrypt('admin'),
            'api_token'  => Str::random(60),
        ]);

        $role = Role::where('name', 'SuperAdmin')->first();

        $user->assignRole($role);
    }
}
