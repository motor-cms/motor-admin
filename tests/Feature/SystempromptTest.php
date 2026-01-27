<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Motor\Admin\Models\AiSystemPrompt;

pest()->group('AiSystemPrompt')->use(RefreshDatabase::class);

describe('AiSystemPrompt', function () {
    it('can create a AiSystemPrompt', fn () => assertCrudCreate(
        '/api/ai_system_prompts',
        ['name' => 'testprompt', 'prompt' => 'Das ist eine Testprompt'],
        AiSystemPrompt::class
    ));

    it('cannot create a Systemprompt without necessary fields', fn () => assertCrudValidation(
        '/api/ai_system_prompts',
        ['name' => 'test']
    ));

    it('can get all AiSystemPrompts', fn () => assertCrudIndex(
        '/api/ai_system_prompts',
        3,
        ['id', 'name', 'prompt', 'client_id', 'client']
    ));

    it('can get a specific AiSystemPrompt', fn () => assertCrudShow(
        '/api/ai_system_prompts/'.AiSystemPrompt::whereName('Basic')->first()->id,
        ['id', 'name', 'prompt', 'client_id', 'client']
    ));

    it('can update ai_system_prompts', fn () => assertCrudUpdate(
        '/api/ai_system_prompts/'.AiSystemPrompt::whereName('Basic')->first()->id,
        ['name' => 'changed', 'prompt' => 'changed'],
        'name',
        'changed'
    ));

    it('can delete ai_system_prompts', fn () => assertCrudDelete(
        '/api/ai_system_prompts/'.AiSystemPrompt::first()->id,
        AiSystemPrompt::class
    ));

    it("can't do anything without permissions", fn () => assertPermissionsDenied(
        '/api/ai_system_prompts',
        AiSystemPrompt::first()->id
    ));
});
