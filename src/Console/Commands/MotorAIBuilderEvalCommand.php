<?php

namespace Motor\Admin\Console\Commands;

use Illuminate\Console\Command;
use Motor\Admin\Services\AI\AIProviderException;
use Motor\Admin\Services\AI\AIRequestLogger;
use Motor\Admin\Services\AI\Builder\AIOutputInvalidException;
use Motor\Admin\Services\AI\Builder\SectionGenerator;
use Throwable;

/**
 * Runs golden prompts against the live provider and writes raw results as JSON
 * (schema-valid rate, repair rounds, latency, tokens, cost). Calls the real API:
 * MISTRAL_API_KEY must be in the environment of the process.
 */
class MotorAIBuilderEvalCommand extends Command
{
    protected $signature = 'motor:ai:builder-eval
        {manifest : Path to a catalog manifest JSON}
        {prompts : Path to golden prompts JSON ([{id, prompt, max_sections?, expect?: string[]}])}
        {--model=* : Models to compare (default: ai.mistral.models.builder)}
        {--out= : Output JSON path}';

    protected $description = 'Evaluate section-from-prompt against golden prompts (live API)';

    public function handle(SectionGenerator $generator): int
    {
        $manifest = json_decode((string) file_get_contents($this->argument('manifest')), true);
        $prompts = json_decode((string) file_get_contents($this->argument('prompts')), true);
        $models = $this->option('model') ?: [config('ai.mistral.models.builder')];
        $results = [];

        foreach ($models as $model) {
            foreach ($prompts as $golden) {
                $row = ['id' => $golden['id'], 'model' => $model, 'prompt' => $golden['prompt'], 'expect' => $golden['expect'] ?? []];
                $started = hrtime(true);
                try {
                    $result = $generator->generate(
                        prompt: $golden['prompt'],
                        manifest: $manifest,
                        maxSections: (int) ($golden['max_sections'] ?? 3),
                        model: $model,
                    );
                    $row += ['status' => 'ok', 'meta' => $result['meta'], 'response' => $result['data']];
                } catch (AIOutputInvalidException $e) {
                    $row += ['status' => 'invalid', 'meta' => $e->meta, 'errors' => $e->errors, 'response' => null];
                } catch (AIProviderException $e) {
                    $row += ['status' => 'error', 'error' => $e->getMessage(), 'response' => null,
                        'meta'        => ['duration_ms' => (int) ((hrtime(true) - $started) / 1_000_000)]];
                } catch (Throwable $e) {
                    $row += ['status' => 'error', 'error' => get_class($e).': '.mb_substr($e->getMessage(), 0, 200), 'response' => null, 'meta' => []];
                }
                $usage = $row['meta']['usage'] ?? [];
                $row['cost_usd'] = AIRequestLogger::costUsd($model, (int) ($usage['prompt_tokens'] ?? 0), (int) ($usage['completion_tokens'] ?? 0));
                $results[] = $row;

                $this->line(sprintf(
                    '%-22s %-24s %-8s rounds=%s %6sms tokens=%s/%s blocks=%s',
                    $model, $golden['id'], $row['status'],
                    $row['meta']['repair_rounds'] ?? '-',
                    $row['meta']['duration_ms'] ?? '-',
                    $usage['prompt_tokens'] ?? '-', $usage['completion_tokens'] ?? '-',
                    implode(',', array_column($row['response']['sections'] ?? [], 'block'))
                ));
            }
        }

        $out = $this->option('out') ?: 'ai-builder-eval.json';
        file_put_contents($out, json_encode(['manifest_hash' => $manifest['hash'] ?? null, 'results' => $results], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        $this->info('Wrote '.$out);

        return self::SUCCESS;
    }
}
