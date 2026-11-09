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
     * @param  string|null  $modelReason  the model declined on its own (one sentence, shown to the editor);
     *                                    null when our policy refused (fixed message, nothing echoed)
     */
    public function __construct(
        public readonly string $reason,
        public readonly array $meta = [],
        public readonly ?string $modelReason = null,
    ) {
        parent::__construct($modelReason !== null && $modelReason !== ''
            ? $modelReason
            : 'Dazu schreibt die KI keine Texte: keine Aussagen über andere Unternehmen oder reale Personen und nichts Abwertendes. Bitte den Auftrag sachlich und auf Ihr eigenes Angebot bezogen formulieren.');
    }

    /** Error code for the API: our guardrail vs. the model declining for its own reasons */
    public function code(): string
    {
        return $this->modelReason !== null && $this->modelReason !== '' ? 'AI_REQUEST_DECLINED' : 'AI_CONTENT_REFUSED';
    }
}
