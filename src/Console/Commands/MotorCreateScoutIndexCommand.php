<?php

namespace Motor\Admin\Console\Commands;

use Illuminate\Console\Command;

/**
 * Class MotorCreatePermissionsCommand
 */
class MotorCreateScoutIndexCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'motor:create:scout:index';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create scout index according to the configuration';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $this->call('scout:sync-index-settings');

        foreach (config('scout.meilisearch.index-settings', []) as $model => $config) {

            $this->call('scout:flush', ['model' => $model]);
            $this->call('scout:import', ['model' => $model]);
        }
    }
}
