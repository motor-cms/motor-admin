<?php

namespace Motor\Admin\Services\AI\Images;

use RuntimeException;

/**
 * The provider answered, but without a usable image (no tool call, refused, broken file).
 */
class ImageGenerationFailed extends RuntimeException
{
    /**
     * @param  array<string, mixed>  $usage
     */
    public function __construct(string $message, public readonly array $usage = [])
    {
        parent::__construct($message);
    }
}
