<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\Fluent\AssertableJson;
use Motor\Admin\Models\AiSystemPrompt;

pest()->group('AiSystemPrompt')->use(RefreshDatabase::class);

describe('AiSystemPrompt', function () {
    it('can create a AiSystemPrompt', function () {
        $count = AiSystemPrompt::count();
        $this->asAdmin()
            ->post('/api/ai_system_prompts', [
                'name'   => 'testprompt',
                'prompt' => 'Das ist eine Testprompt',
            ])
            ->assertStatus(201);
        expect(AiSystemPrompt::count() - $count)->toBe(1);
    });

    it('cannot create a Systemprompt without necesary fields')->asAdmin()->withJsonHeaders()->post('/api/ai_system_prompts', [
        'name' => 'test',
    ])->assertStatus(422);

    it('can get all AiSystemPrompts')
        ->asAdmin()
        ->get('/api/ai_system_prompts')
        ->assertStatus(200)
        ->assertJson(fn (AssertableJson $json) => $json->has('data', 3, fn (AssertableJson $data) => $data->has('id')->has('name')->has('prompt')->has('client_id')->has('client')
        )->etc());

    it('can get a specific AiSystemPrompt', function () {
        $this->asAdmin()->get('/api/ai_system_prompts/'.AiSystemPrompt::whereName('Basic')->first()->id)
            ->assertStatus(200)
            ->assertJson(fn (AssertableJson $json) => $json->has(
                'data',
                fn (AssertableJson $data) => $data->where('prompt', 'Verhalte dich wie ein Ottonormalbürger')->etc()
            )->etc());
    });

    it(
        'can update ai_system_prompts',
        fn () => $this->asAdmin()->put('/api/ai_system_prompts/'.AiSystemPrompt::whereName('Basic')->first()->id, [
            'name'   => 'changed',
            'prompt' => 'changed',
        ])->assertStatus(200)->assertJson(fn (AssertableJson $json) => $json->has('data', fn (AssertableJson $data) => $data
            ->where('name', 'changed')->etc())->etc())
    );

    it('can delete ai_system_prompts', function () {
        $languagecount = AiSystemPrompt::count();
        $this->asAdmin()->delete('/api/ai_system_prompts/'.AiSystemPrompt::first()->id)
            ->assertStatus(200);
        expect($languagecount - AiSystemPrompt::count())->toBe(1);
    });

    it(
        "can't do anything without permissions", function () {
            $this->asBasic()->getJson('/api/ai_system_prompts')->assertStatus(403);
            $this->asBasic()->getJson('/api/ai_system_prompts/'.AiSystemPrompt::first()->id)->assertStatus(403);
            $this->asBasic()->post('/api/ai_system_prompts', [])->assertStatus(403);
            $this->asBasic()->put('/api/ai_system_prompts/'.AiSystemPrompt::first()->id, [])->assertStatus(403);
            $this->asBasic()->delete('/api/ai_system_prompts/'.AiSystemPrompt::first()->id)->assertStatus(403);
        }
    );
});
