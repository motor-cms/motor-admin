<?php

use Illuminate\Testing\Fluent\AssertableJson;
use Motor\Admin\Models\Client;
use Motor\Admin\Models\Domain;

describe('Domain', function () {
    it('can create a Domain', function () {
        $domaincount = Domain::count();
        $this->asAdmin()
            ->post('/api/domains', [
                'client_id' => Client::first()->id,
                'is_active' => true,
                'name' => 'test',
                'protocol' => 'https',
                'host' => ' localhost',
                'port' => 80,
                'path' => '/',
            ])->assertStatus(201);
        expect(Domain::count() - $domaincount)->toBe(1);
    });
    it("can't create a Domain with invalid client", function () {
        $domaincount = Domain::count();
        $this->asAdmin()->withJsonHeaders()
            ->post('/api/domains', [
                'client_id' => 0,
                'is_active' => true,
                'name' => 'test',
                'protocol' => 'https',
                'host' => ' localhost',
                'port' => 80,
                'path' => '/',
            ])->assertStatus(422);
        expect(Domain::count() - $domaincount)->toBe(0);
    });
    it("can't create an empty Domain", function () {
        $domaincount = Domain::count();
        $this->asAdmin()->withJsonHeaders()
            ->post('/api/domains', [])->assertStatus(422);
        expect(Domain::count() - $domaincount)->toBe(0);
    });
    it('can get all Domains')
        ->asAdmin()
        ->get('/api/domains')
        ->assertStatus(200)
        ->assertJson(fn(AssertableJson $json) => $json->has(
            'data',
            1,
            fn(AssertableJson $data) =>
            $data
                ->has('id')
                ->has('name')
                ->has('client')
                ->etc()
        )->etc());
    it(
        'can get a specific Domain',
        fn() =>
        $this->asAdmin()->get('/api/domains/' . Domain::whereName('localhost')->first()->id)
            ->assertStatus(200)
            ->assertJson(fn(AssertableJson $json) => $json->has(
                'data',
                fn(AssertableJson $data) =>
                $data
                    ->has('id')
                    ->has('client')
                    ->has('client_id')
                    ->has('is_active')
                    ->has('name')
                    ->has('protocol')
                    ->has('host')
                    ->has('port')
                    ->has('path')
                    ->has('target')
                    ->has('parameters')
                    ->has('target_http_status_code')
            )->etc())
    );
    it('can update domains', fn() => $this->asAdmin()
        ->put('/api/domains/' . Domain::whereName('localhost')->first()->id, [
            'client_id' => Client::first()->id,
            'is_active' => true,
            'name' => 'changed',
            'protocol' => 'https',
            'host' => ' localhost',
            'port' => 80,
            'path' => '/',
        ])->assertStatus(200)
        ->assertJson(fn(AssertableJson $json) => $json->has('data', fn(AssertableJson $data) =>
        $data->where('name', 'changed')->etc())->etc()));
    it('can delete domains', function () {
        $domaincount = Domain::count();
        $this->asAdmin()->delete('/api/domains/' . Domain::whereName('localhost')->first()->id)
            ->assertStatus(200);
        expect($domaincount - Domain::count())->toBe(1);
    });
});
