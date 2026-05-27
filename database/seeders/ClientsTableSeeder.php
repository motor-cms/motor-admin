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
     */
    public function run(): void
    {
        $default = Client::factory()
            ->create([
                'name'       => 'Default',
                'slug'       => 'default',
                'created_by' => User::first(),
                'updated_by' => User::first(),
            ]);

        // Attach the seeded non-admin baseline users to the Default client so
        // V2 tests that exercise denial paths reach their model-bound policy
        // (returning 403) instead of hitting an empty-pivot 404 from the
        // global ClientScope. SuperAdmin is excluded -- its resolver is always
        // null, the pivot is not consulted.
        $emails = ['editor@motor-cms.com', 'writer@motor-cms.com', 'auth@motor-cms.com'];
        foreach (User::whereIn('email', $emails)->get() as $user) {
            $user->clients()->attach($default->id);
        }
    }
}
