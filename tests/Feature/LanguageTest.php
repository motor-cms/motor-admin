<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Motor\Admin\Models\Language;

pest()->group('Language')->use(RefreshDatabase::class);

describe('Language', function () {
    it('can create a Language', fn () => assertCrudCreate(
        '/api/languages',
        [
            'english_name' => 'testlang',
            'native_name' => 'testlang',
            'iso_639_1' => 'af',
        ],
        Language::class
    ));

    it('cannot create a Language without necessary fields', fn () => assertCrudValidation(
        '/api/languages',
        ['english_name' => 'test', 'native_name' => 'test']
    ));

    it('can get all Languages', fn () => assertCrudIndex(
        '/api/languages',
        3,
        ['id', 'iso_639_1', 'english_name', 'native_name']
    ));

    it('can get a specific Language', fn () => assertCrudShow(
        '/api/languages/'.Language::whereNativeName('English')->first()->id,
        ['id', 'iso_639_1', 'english_name', 'native_name']
    ));

    it('can update languages', fn () => assertCrudUpdate(
        '/api/languages/'.Language::whereNativeName('English')->first()->id,
        [
            'english_name' => 'english',
            'native_name' => 'testlang',
            'iso_639_1' => 'af',
        ],
        'native_name',
        'testlang'
    ));

    it('can delete languages', fn () => assertCrudDelete(
        '/api/languages/'.Language::whereNativeName('English')->first()->id,
        Language::class
    ));
});
