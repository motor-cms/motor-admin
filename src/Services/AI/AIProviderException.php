<?php

namespace Motor\Admin\Services\AI;

use RuntimeException;

/**
 * Provider unreachable, rate limited after retries, or answered with an error.
 * The message never contains the API key or the prompt.
 */
class AIProviderException extends RuntimeException
{
    public function __construct(string $message, public readonly ?int $providerStatus = null)
    {
        parent::__construct($message);
    }
}
