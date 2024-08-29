<?php

use Illuminate\Testing\Fluent\AssertableJson;
use Motor\Admin\Models\Language;

describe('Language', function () {
    it('can create a Language', function () {
        $this->asAdmin()
            ->post('/api/languages', [
                'english_name' => 'testlang',
                'native_name' => 'testlang',
                'iso_639_1' => 'af',
            ])
            ->assertStatus(201);
        expect(Language::count())->toBe(4);
    });
    it('can get all Languages')
        ->asAdmin()
        ->get('/api/languages')
        ->assertStatus(200)
        ->assertJson(fn(AssertableJson $json) => $json->has('data', 3, fn(AssertableJson $data) =>
        $data->has('id')->has('iso_639_1')->has('english_name')->has('native_name'))->etc());
    it('can get a specific Language', function () {
        $this->asAdmin()->get('/api/languages/' . Language::whereNativeName('English')->first()->id)
            ->assertStatus(200)
            ->assertJson(fn(AssertableJson $json) => $json->has(
                'data',
                fn(AssertableJson $data) => $data->where('iso_639_1', 'en')->etc()
            )->etc());
    });
});
