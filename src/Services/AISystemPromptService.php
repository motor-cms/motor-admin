<?php

namespace Motor\Admin\Services;

use Motor\Admin\Models\AISystemPrompt;

class AISystemPromptService extends BaseService
{
    protected array $loadColumns = ['client'];

    protected $model = AISystemPrompt::class;

    public function filters()
    {
        $this->filter->addClientFilter();
    }
}
