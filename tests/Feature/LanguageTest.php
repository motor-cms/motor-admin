<?php

use Illuminate\Testing\Fluent\AssertableJson;
use Motor\Admin\Models\Language;

describe('Language', function () {
    it('can create a Language', function () {
        $languagecount = Language::count();
        $this->asAdmin()
            ->post('/api/languages', [
                'english_name' => 'testlang',
                'native_name' => 'testlang',
                'iso_639_1' => 'af',
            ])
            ->assertStatus(201);
        expect(Language::count() - $languagecount)->toBe(1);
    });
    it('cannot create a Language without necesary fields')->asAdmin()->withJsonHeaders()->post('/api/languages', [
        'english_name' => 'test',
        'native_name' => 'test',
    ])->assertStatus(422);
    it('can get all Languages')
        ->asAdmin()
        ->get('/api/languages')
        ->assertStatus(200)
        ->assertJson(fn(AssertableJson $json) => $json->has('data', 4, fn(AssertableJson $data) =>
        $data->has('id')->has('iso_639_1')->has('english_name')->has('native_name'))->etc());
    it('can get a specific Language', function () {
        $this->asAdmin()->get('/api/languages/' . Language::whereNativeName('English')->first()->id)
            ->assertStatus(200)
            ->assertJson(fn(AssertableJson $json) => $json->has(
                'data',
                fn(AssertableJson $data) => $data->where('iso_639_1', 'en')->etc()
            )->etc());
    });
    it(
        'can update languages',
        fn() => $this->asAdmin()->put('/api/languages/' . Language::whereNativeName('English')->first()->id, [
            'english_name' => 'english',
            'native_name' => 'testlang',
            'iso_639_1' => 'af',
        ])->assertStatus(200)->assertJson(fn(AssertableJson $json) => $json->has('data', fn(AssertableJson $data) => $data
            ->where('native_name', 'testlang')->etc())->etc())
    );
    it('can delete languages', function () {
        $languagecount = Language::count();
        $this->asAdmin()->delete('/api/languages/' . Language::first()->id)
            ->assertStatus(200);
        expect($languagecount - Language::count())->toBe(1);
    });
});
