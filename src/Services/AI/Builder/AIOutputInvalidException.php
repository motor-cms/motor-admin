<?php

namespace Motor\Admin\Services\AI\Builder;

use RuntimeException;

/**
 * The model output is still invalid after all repair rounds.
 */
class AIOutputInvalidException extends RuntimeException
{
    /**
     * @param  string[]  $errors
     * @param  array<string, mixed>  $meta
     */
    public function __construct(public readonly array $errors, public readonly array $meta)
    {
        parent::__construct('Die KI-Antwort war auch nach '.($meta['repair_rounds'] ?? 0).' Korrekturrunden ungültig.');
    }
}
