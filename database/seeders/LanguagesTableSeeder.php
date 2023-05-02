<?php

namespace Motor\Admin\Database\Seeders;

use Illuminate\Database\Seeder;
use Motor\Admin\Models\Language;

/**
 * Class ClientsTableSeeder
 */
class LanguagesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Language::factory()
            ->create([
                'iso_639_1'    => 'de',
                'english_name' => 'German',
                'native_name'  => 'Deutsch',
            ]);

        Language::factory()
            ->create([
                'iso_639_1'    => 'en',
                'english_name' => 'English',
                'native_name'  => 'English',
            ]);

        Language::factory()
            ->create([
                'iso_639_1'    => 'fr',
                'english_name' => 'French',
                'native_name'  => 'Français',
            ]);
    }
}
