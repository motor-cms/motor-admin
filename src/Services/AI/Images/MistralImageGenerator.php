<?php

namespace Motor\Admin\Services\AI\Images;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Motor\Admin\Services\AI\AIProviderException;
use Motor\Admin\Services\AI\MistralClient;

/**
 * Image from Mistral's built-in tool `image_generation` (Conversations API with model + tools, no stored agent).
 *
 * The docs describe a `tool_file` chunk in the message, fetched over the Files API. Live (11/2026) the tool instead
 * answers with a signed, one-hour download URL in the `tool.execution` entry (`info.result`), as JPEG with a signed
 * C2PA manifest (digitalSourceType trainedAlgorithmicMedia, generator "Pruna AI"). Both ways are handled: a
 * `tool_file` chunk wins, otherwise the URL is fetched, but only from the hosts in ai.images.download_hosts.
 */
class MistralImageGenerator
{
    public const INSTRUCTIONS = <<<'TXT'
        Du erzeugst genau ein Bild für eine Unternehmenswebsite mit dem Werkzeug image_generation. Rufe das Werkzeug genau einmal auf und antworte danach nur mit "OK".
        Regeln für das Bild: keine Logos, keine Marken, kein Text oder Schriftzug im Bild, keine realen oder erkennbaren Personen des öffentlichen Lebens, keine Wasserzeichen. Personen nur, wenn ausdrücklich verlangt, dann anonym und nicht im Vordergrund. Querformat.
        TXT;

    public function __construct(protected MistralClient $client) {}

    public function generate(string $prompt): GeneratedImage
    {
        $model = (string) config('ai.images.model', 'mistral-medium-2604');
        $result = $this->client->conversation([
            'model'           => $model,
            'inputs'          => $prompt,
            'instructions'    => self::INSTRUCTIONS,
            'tools'           => [['type' => 'image_generation']],
            'completion_args' => ['temperature' => 0.3],
            'store'           => false,
        ], (int) config('ai.images.timeout', 120));

        $usage = $result['usage'];
        $usage['images'] = (int) data_get($usage, 'connectors.image_generation', 0);

        $execution = collect($result['outputs'])->first(fn ($o) => ($o['type'] ?? null) === 'tool.execution'
            && ($o['name'] ?? null) === 'image_generation');
        $revised = is_string($execution['arguments'] ?? null) ? data_get(json_decode($execution['arguments'], true), 'prompt') : null;

        $bytes = null;
        $reference = null;
        foreach ($result['outputs'] as $output) {
            foreach ((array) (is_array($output['content'] ?? null) ? $output['content'] : []) as $chunk) {
                if (($chunk['type'] ?? null) === 'tool_file' && filled($chunk['file_id'] ?? null)) {
                    $bytes = $this->client->fileContent($chunk['file_id']);
                    $reference = 'file:'.$chunk['file_id'];
                    break 2;
                }
            }
        }
        if ($bytes === null && $execution !== null) {
            $url = data_get(json_decode((string) data_get($execution, 'info.result', ''), true), 'url');
            if (is_string($url)) {
                $bytes = $this->download($url, $usage);
                $reference = 'tool:'.($execution['id'] ?? '');
            }
        }
        if ($bytes === null) {
            throw new ImageGenerationFailed('Mistral did not call image_generation.', $usage);
        }

        $mime = self::sniff($bytes);
        if ($mime === null) {
            throw new ImageGenerationFailed('Mistral returned no readable image.', $usage);
        }

        return new GeneratedImage(
            bytes: $bytes,
            mime: $mime,
            provider: 'mistral',
            model: (string) ($execution['model'] ?? $model),
            tool: 'image_generation',
            revisedPrompt: is_string($revised) ? mb_substr($revised, 0, 2000) : null,
            usage: $usage,
            reference: $reference,
        );
    }

    /**
     * @param  array<string, mixed>  $usage
     */
    protected function download(string $url, array $usage): string
    {
        if (! self::allowedHost($url)) {
            throw new ImageGenerationFailed('Image URL host not allowed.', $usage);
        }
        try {
            $response = Http::timeout(60)->get($url);
        } catch (ConnectionException) {
            throw new AIProviderException('Image download failed.');
        }
        if (! $response->successful()) {
            throw new AIProviderException('Image download returned HTTP '.$response->status().'.');
        }
        if (strlen($response->body()) > (int) config('ai.images.max_bytes', 15 * 1024 * 1024)) {
            throw new ImageGenerationFailed('Image too large.', $usage);
        }

        return $response->body();
    }

    public static function allowedHost(string $url): bool
    {
        $parts = parse_url($url);
        if (($parts['scheme'] ?? '') !== 'https' || empty($parts['host'])) {
            return false;
        }
        $host = mb_strtolower($parts['host']);
        foreach ((array) config('ai.images.download_hosts', []) as $suffix) {
            if (str_ends_with($host, mb_strtolower($suffix))) {
                return true;
            }
        }

        return false;
    }

    /** Mime type from the magic bytes (the blob is served as application/octet-stream). */
    public static function sniff(string $bytes): ?string
    {
        return match (true) {
            str_starts_with($bytes, "\xFF\xD8\xFF")                                     => 'image/jpeg',
            str_starts_with($bytes, "\x89PNG\r\n\x1A\n")                               => 'image/png',
            str_starts_with($bytes, 'RIFF') && substr($bytes, 8, 4) === 'WEBP'         => 'image/webp',
            default                                                                     => null,
        };
    }
}
