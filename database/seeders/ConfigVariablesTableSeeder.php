<?php

namespace Motor\Admin\Database\Seeders;

use Illuminate\Database\Seeder;
use Motor\Admin\Models\ConfigVariable;

/**
 * Class ConfigVariablesTableSeeder
 */
class ConfigVariablesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        ConfigVariable::factory()
            ->create([
                'package' => 'motor-admin',
                'group'   => 'motor-admin-project',
                'name'    => 'name',
                'value'   => 'Motor CMS',
            ]);
    }
}
