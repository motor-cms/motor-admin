<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Motor\Admin\Models\Language;

pest()->group('V2Language')->use(RefreshDatabase::class);

describe('V2 Language API', function () {

    it('includes api_version v2 in response meta', function () {
        $response = $this->asAdmin()->getJson('/api/v2/languages');

        $response->assertStatus(200)
            ->assertJsonPath('meta.api_version', 'v2');
    });

    it('can get all languages', function () {
        assertV2CrudIndex('/api/v2/languages', 3, ['id', 'iso_639_1', 'english_name', 'native_name']);
    });

    it('can get a specific language', function () {
        assertV2CrudShow(
            '/api/v2/languages/'.Language::whereNativeName('English')->first()->id,
            ['id', 'iso_639_1', 'english_name', 'native_name']
        );
    });

    it('can create a language', function () {
        assertV2CrudCreate('/api/v2/languages', [
            'english_name' => 'V2 Test Language',
            'native_name' => 'V2 Test Sprache',
            'iso_639_1' => 'af',
        ], Language::class);
    });

    it('validates required fields on create', function () {
        $countBefore = Language::count();
        $this->asAdmin()
            ->withHeaders(['Accept' => 'application/json'])
            ->post('/api/v2/languages', [
                'english_name' => 'test',
                'native_name' => 'test',
                // Missing iso_639_1
            ])
            ->assertStatus(422);
        expect(Language::count() - $countBefore)->toBe(0);
    });

    it('can update a language', function () {
        assertV2CrudUpdate(
            '/api/v2/languages/'.Language::whereNativeName('English')->first()->id,
            [
                'english_name' => 'V2 Updated English',
                'native_name' => 'V2 Updated Native',
                'iso_639_1' => 'en',
            ],
            'native_name',
            'V2 Updated Native'
        );
    });

    it('can delete a language with 204 No Content', function () {
        assertV2CrudDelete(
            '/api/v2/languages/'.Language::whereNativeName('English')->first()->id,
            Language::class
        );
    });

    it('denies access to basic users', function () {
        assertV2PermissionsDenied('/api/v2/languages', Language::first()->id);
    });
});
