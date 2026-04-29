<?php

namespace Motor\Admin\Services;

use Motor\Admin\Models\AISystemPrompt;

class AISystemPromptService extends BaseService
{
    protected array $loadColumns = ['client'];

    protected string $model = AISystemPrompt::class;

    public function filters(): void
    {
        $this->filter->addClientFilter();
    }
}
