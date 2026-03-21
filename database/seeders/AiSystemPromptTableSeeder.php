<?php

namespace Motor\Admin\Database\Seeders;

use Illuminate\Database\Seeder;
use Motor\Admin\Models\AISystemPrompt;

/**
 * Class ClientsTableSeeder
 */
class AiSystemPromptTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        AISystemPrompt::factory()
            ->create([
                'name'    => 'Basic',
                'prompt' => 'Verhalte dich wie ein Ottonormalbürger',
            ]);

        AISystemPrompt::factory()
            ->create([
                'name'    => 'Programmierer',
                'prompt' => 'Verhalte dich wie ein Programmierer',
            ]);

        AISystemPrompt::factory()
            ->create([
                'name'    => 'CEO',
                'prompt' => 'Verhalte dich wie ein CEO',
            ]);
    }
}
