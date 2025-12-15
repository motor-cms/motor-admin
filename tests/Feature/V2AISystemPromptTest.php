<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Motor\Admin\Models\AiSystemPrompt;

pest()->group('V2AISystemPrompt')->use(RefreshDatabase::class);

describe('V2 AISystemPrompt API', function () {

    it('includes api_version v2 in response meta', function () {
        $response = $this->asAdmin()->getJson('/api/v2/ai-system-prompts');

        $response->assertStatus(200)
            ->assertJsonPath('meta.api_version', 'v2');
    });

    it('can get all AI system prompts', function () {
        assertV2CrudIndex('/api/v2/ai-system-prompts', 3, ['id', 'name', 'prompt', 'client_id', 'client']);
    });

    it('can get a specific AI system prompt', function () {
        assertV2CrudShow(
            '/api/v2/ai-system-prompts/'.AiSystemPrompt::whereName('Basic')->first()->id,
            ['id', 'name', 'prompt']
        );
    });

    it('can create an AI system prompt', function () {
        assertV2CrudCreate('/api/v2/ai-system-prompts', [
            'name' => 'V2 Test Prompt',
            'prompt' => 'This is a V2 test system prompt',
        ], AiSystemPrompt::class);
    });

    it('validates required fields on create', function () {
        $countBefore = AiSystemPrompt::count();
        $this->asAdmin()
            ->withHeaders(['Accept' => 'application/json'])
            ->post('/api/v2/ai-system-prompts', [
                'name' => 'test',
                // Missing prompt field
            ])
            ->assertStatus(422);
        expect(AiSystemPrompt::count() - $countBefore)->toBe(0);
    });

    it('can update an AI system prompt', function () {
        assertV2CrudUpdate(
            '/api/v2/ai-system-prompts/'.AiSystemPrompt::whereName('Basic')->first()->id,
            [
                'name' => 'V2 Updated Prompt',
                'prompt' => 'V2 Updated prompt content',
            ],
            'name',
            'V2 Updated Prompt'
        );
    });

    it('can delete an AI system prompt with 204 No Content', function () {
        assertV2CrudDelete(
            '/api/v2/ai-system-prompts/'.AiSystemPrompt::first()->id,
            AiSystemPrompt::class
        );
    });
});
