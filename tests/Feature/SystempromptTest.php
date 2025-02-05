<?php

use Illuminate\Testing\Fluent\AssertableJson;
use Motor\Admin\Models\AiSystemPrompt;

describe('AiSystemPrompt', function () {
    it('can create a AiSystemPrompt', function () {
        $languagecount = AiSystemPrompt::count();
        $this->asAdmin()
             ->post('/api/ai_system_prompts', [
                 'name' => 'testprompt',
                 'prompt' => 'Das ist eine Testprompt',
             ])
             ->assertStatus(201);
        expect(AiSystemPrompt::count() - $languagecount)->toBe(1);
    });
    it('cannot create a Systemprompt without necesary fields')->asAdmin()->withJsonHeaders()->post('/api/ai_system_prompts', [
        'name' => 'test',
    ])->assertStatus(422);
    it('can get all AiSystemPrompts')
        ->asAdmin()
        ->get('/api/ai_system_prompts')
        ->assertStatus(200)
        ->assertJson(fn(AssertableJson $json) => $json->has('data', 4, fn(AssertableJson $data) =>
        $data->has('id')->has('iso_639_1')->has('english_name')->has('native_name'))->etc());
    it('can get a specific AiSystemPrompt', function () {
        $this->asAdmin()->get('/api/ai_system_prompts/' . AiSystemPrompt::whereName('testprompt')->first()->id)
             ->assertStatus(200)
             ->assertJson(fn(AssertableJson $json) => $json->has(
                 'data',
                 fn(AssertableJson $data) => $data->where('prompt', 'Das ist eine Testprompt')->etc()
             )->etc());
    });
    it(
        'can update ai_system_prompts',
        fn() => $this->asAdmin()->put('/api/ai_system_prompts/' . AiSystemPrompt::whereName('testprompt')->first()->id, [
            'name' => 'changed',
            'prompt' => 'changed',
        ])->assertStatus(200)->assertJson(fn(AssertableJson $json) => $json->has('data', fn(AssertableJson $data) => $data
            ->where('name', 'changed')->etc())->etc())
    );
    it('can delete ai_system_prompts', function () {
        $languagecount = AiSystemPrompt::count();
        $this->asAdmin()->delete('/api/ai_system_prompts/' . AiSystemPrompt::first()->id)
             ->assertStatus(200);
        expect($languagecount - AiSystemPrompt::count())->toBe(1);
    });
    it(
        "can't do anything without permissions", function() {
        $this->asBasic()->getJson('/api/ai_system_prompts')->assertStatus(403);
        $this->asBasic()->getJson('/api/ai_system_prompts/'. AiSystemPrompt::first()->id)->assertStatus(403);
        $this->asBasic()->post('/api/ai_system_prompts', [])->assertStatus(403);
        $this->asBasic()->put('/api/ai_system_prompts/'. AiSystemPrompt::first()->id, [])->assertStatus(403);
        $this->asBasic()->delete('/api/ai_system_prompts/'. AiSystemPrompt::first()->id)->assertStatus(403);
    }
    );
});
