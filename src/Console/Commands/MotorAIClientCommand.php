<?php

namespace Motor\Admin\Console\Commands;

use Illuminate\Console\Command;
use Motor\Admin\Models\Client;

/**
 * Switch AI features per tenant. Deliberately a console command instead of a
 * fillable API field: the shared test backend also serves energis, so turning
 * AI on stays an operator decision.
 */
class MotorAIClientCommand extends Command
{
    protected $signature = 'motor:ai:client
        {client : Client id or slug}
        {--enable : Switch AI on}
        {--disable : Switch AI off}
        {--tone-prompt= : Id of an ai_system_prompts row of this client used as tone}';

    protected $description = 'Show or switch the AI features of a client (default off)';

    public function handle(): int
    {
        $key = (string) $this->argument('client');
        $client = Client::withoutGlobalScopes()
            ->where(is_numeric($key) ? 'id' : 'slug', $key)
            ->first();

        if ($client === null) {
            $this->error('Client '.$key.' not found.');

            return self::FAILURE;
        }

        $config = $client->ai_config ?? [];
        if ($this->option('enable')) {
            $config['enabled'] = true;
        }
        if ($this->option('disable')) {
            $config['enabled'] = false;
        }
        if ($this->option('tone-prompt') !== null) {
            $config['tone_prompt_id'] = $this->option('tone-prompt') === '' ? null : (int) $this->option('tone-prompt');
        }

        if ($config !== ($client->ai_config ?? [])) {
            $client->ai_config = $config;
            $client->saveQuietly();
        }

        $this->info(sprintf(
            '%s (%d): AI %s, tone prompt %s',
            $client->slug ?: $client->name,
            $client->id,
            ($config['enabled'] ?? false) === true ? 'enabled' : 'disabled',
            $config['tone_prompt_id'] ?? '-'
        ));

        return self::SUCCESS;
    }
}
