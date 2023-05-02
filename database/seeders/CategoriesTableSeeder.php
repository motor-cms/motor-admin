<?php

namespace Motor\Admin\Database\Seeders;

use Illuminate\Database\Eloquent\Factories\Sequence;
use Illuminate\Database\Seeder;
use Motor\Admin\Models\Category;

/**
 * Class CategoriesTableSeeder
 */
class CategoriesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $mainCategory = Category::factory()
            ->create([
                'name'  => 'Default',
                'scope' => 'default',
            ]);

        Category::factory()
            ->count(3)
            ->state(new Sequence(['parent_id' => $mainCategory->id, 'name' => 'Test #1', 'scope' => 'default'], [
                'parent_id' => $mainCategory->id,
                'name'      => 'Test #2',
                'scope'     => 'default',
            ], [
                'parent_id' => $mainCategory->id,
                'name'      => 'Test #3',
                'scope'     => 'default',
            ]))
            ->create();
    }
}
