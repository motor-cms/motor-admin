<?php

namespace Motor\Admin\Services\AI\Images;

/**
 * One image produced by a generator, before it goes into the media library.
 */
final class GeneratedImage
{
    /**
     * @param  array<string, mixed>  $usage  token usage of the agent model plus `images` (number of tool calls)
     */
    public function __construct(
        public readonly string $bytes,
        public readonly string $mime,
        public readonly string $provider,
        public readonly string $model,
        public readonly string $tool,
        public readonly ?string $revisedPrompt,
        public readonly array $usage = [],
        public readonly ?string $reference = null,
    ) {}

    public function extension(): string
    {
        return match ($this->mime) {
            'image/png'  => 'png',
            'image/webp' => 'webp',
            default      => 'jpg',
        };
    }
}
