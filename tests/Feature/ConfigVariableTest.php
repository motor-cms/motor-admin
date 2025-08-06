<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\Fluent\AssertableJson;
use Motor\Admin\Models\ConfigVariable;

pest()->group('ConfigVariable')->use(RefreshDatabase::class);

describe('ConfigVariable', function () {
    it('can create a ConfigVariable', function () {
        $configvariablecount = ConfigVariable::count();
        $this->asAdmin()
            ->post('/api/config_variables', [
                'name'         => 'test',
                'package'      => 'https',
                'group'        => ' name',
                'value'        => 80,
                'is_invisible' => false,
            ])->assertStatus(201);
        expect(ConfigVariable::count() - $configvariablecount)->toBe(1);
    });
    it("can't create an empty ConfigVariable", function () {
        $configvariablecount = ConfigVariable::count();
        $this->asAdmin()->withJsonHeaders()
            ->post('/api/config_variables', [])->assertStatus(422);
        expect(ConfigVariable::count() - $configvariablecount)->toBe(0);
    });
    it('can get all ConfigVariables')
        ->asAdmin()
        ->get('/api/config_variables')
        ->assertStatus(200)
        ->assertJson(fn (AssertableJson $json) => $json->has(
            'data',
            1,
            fn (AssertableJson $data) => $data
                ->has('id')
                ->has('name')
                ->has('package')
                ->has('group')
                ->has('value')
                ->etc()
        )->etc());
    it(
        'can get a specific ConfigVariable',
        fn () => $this->asAdmin()->get('/api/config_variables/'.ConfigVariable::whereName('name')->first()->id)
            ->assertStatus(200)
            ->assertJson(fn (AssertableJson $json) => $json->has(
                'data',
                fn (AssertableJson $data) => $data
                    ->has('id')
                    ->has('package')
                    ->has('group')
                    ->has('name')
                    ->has('value')
                    ->etc()
            )->etc())
    );
    it('can update config_variables', fn () => $this->asAdmin()
        ->put('/api/config_variables/'.ConfigVariable::whereName('name')->first()->id, [
            'package' => 'test',
            'name'    => 'changed',
            'group'   => 'https',
            'value'   => ' name',
        ])->assertStatus(200)
        ->assertJson(fn (AssertableJson $json) => $json->has('data', fn (AssertableJson $data) => $data->where('name', 'changed')->etc())->etc()));
    it('can delete config_variables', function () {
        $configvariablecount = ConfigVariable::count();
        $this->asAdmin()->delete('/api/config_variables/'.ConfigVariable::whereName('name')->first()->id)
            ->assertStatus(200);
        expect($configvariablecount - ConfigVariable::count())->toBe(1);
    });
});
