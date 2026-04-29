<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Motor\Admin\Models\ConfigVariable;

pest()->group('ConfigVariable')->use(RefreshDatabase::class);

describe('ConfigVariable', function () {
    it('can create a ConfigVariable', fn () => assertCrudCreate(
        '/api/config_variables',
        [
            'name' => 'test',
            'package' => 'https',
            'group' => ' name',
            'value' => 80,
            'is_invisible' => false,
        ],
        ConfigVariable::class
    ));

    it("can't create an empty ConfigVariable", fn () => assertCrudValidation(
        '/api/config_variables',
        [],
        ConfigVariable::class
    ));

    it('can get all ConfigVariables', fn () => assertCrudIndex(
        '/api/config_variables',
        1,
        ['id', 'name', 'package', 'group', 'value']
    ));

    it('can get a specific ConfigVariable', fn () => assertCrudShow(
        '/api/config_variables/'.ConfigVariable::whereName('name')->first()->id,
        ['id', 'package', 'group', 'name', 'value']
    ));

    it('can update config_variables', fn () => assertCrudUpdate(
        '/api/config_variables/'.ConfigVariable::whereName('name')->first()->id,
        [
            'package' => 'test',
            'name' => 'changed',
            'group' => 'https',
            'value' => ' name',
        ],
        'name',
        'changed'
    ));

    it('can delete config_variables', fn () => assertCrudDelete(
        '/api/config_variables/'.ConfigVariable::whereName('name')->first()->id,
        ConfigVariable::class
    ));
});
