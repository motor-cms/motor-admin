<?php

namespace Motor\Admin\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

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

        foreach (config('scout.meilisearch.index-settings', []) as $class => $config) {

            // For some reason (bug perhaps) we can't use $this->call or Artisan::call here, because scout throws an error
            $command = "scout:import \"{$class}\"";

            $this->info(shell_exec('php artisan '.$command));
        }
    }
}
