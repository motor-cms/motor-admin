<?php

namespace Motor\Admin\Services\AI\Builder;

use RuntimeException;

/**
 * The request or the model output touches what the builder must never write:
 * statements about real third parties (competitors, brands, people) or
 * insulting / defamatory text. The admin shows a friendly message instead of a draft.
 */
class AIContentRefusedException extends RuntimeException
{
    /**
     * @param  string  $reason  short internal reason for the log (not shown to the editor)
     * @param  array<string, mixed>  $meta
     */
    public function __construct(public readonly string $reason, public readonly array $meta = [])
    {
        parent::__construct('Dazu schreibt die KI keine Texte: keine Aussagen über andere Unternehmen oder reale Personen und nichts Abwertendes. Bitte den Auftrag sachlich und auf Ihr eigenes Angebot bezogen formulieren.');
    }
}
