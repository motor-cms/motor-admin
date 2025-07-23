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
        AiSystemPrompt::factory()
            ->create([
                'name'    => 'Basic',
                'prompt' => 'Verhalte dich wie ein Ottonormalbürger',
            ]);

        AiSystemPrompt::factory()
            ->create([
                'name'    => 'Programmierer',
                'prompt' => 'Verhalte dich wie ein Programmierer',
            ]);

        AiSystemPrompt::factory()
            ->create([
                'name'    => 'CEO',
                'prompt' => 'Verhalte dich wie ein CEO',
            ]);
    }
}
