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
    protected $name = 'motor:admin:sync-scout-indexes';

    public int $timeout = 14400;

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Flush and rebuild Scout search indexes for all configured models';

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
