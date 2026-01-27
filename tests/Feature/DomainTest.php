<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Motor\Admin\Models\Client;
use Motor\Admin\Models\Domain;

pest()->group('Domain')->use(RefreshDatabase::class);

describe('Domain', function () {
    it('can create a Domain', fn () => assertCrudCreate(
        '/api/domains',
        [
            'client_id' => Client::first()->id,
            'is_active' => true,
            'name' => 'test',
            'protocol' => 'https',
            'host' => 'localhost',
            'port' => 80,
            'path' => '/',
        ],
        Domain::class
    ));

    it("can't create a Domain with invalid client", fn () => assertCrudValidation(
        '/api/domains',
        [
            'client_id' => 0,
            'is_active' => true,
            'name' => 'test',
            'protocol' => 'https',
            'host' => 'localhost',
            'port' => 80,
            'path' => '/',
        ],
        Domain::class
    ));

    it("can't create an empty Domain", fn () => assertCrudValidation(
        '/api/domains',
        [],
        Domain::class
    ));

    it('can get all Domains', fn () => assertCrudIndex(
        '/api/domains',
        1,
        ['id', 'name', 'client']
    ));

    it('can get a specific Domain', fn () => assertCrudShow(
        '/api/domains/'.Domain::whereName('localhost')->first()->id,
        ['id', 'client', 'client_id', 'is_active', 'name', 'protocol', 'host', 'port', 'path']
    ));

    it('can update domains', fn () => assertCrudUpdate(
        '/api/domains/'.Domain::whereName('localhost')->first()->id,
        [
            'client_id' => Client::first()->id,
            'is_active' => true,
            'name' => 'changed',
            'protocol' => 'https',
            'host' => 'localhost',
            'port' => 80,
            'path' => '/',
        ],
        'name',
        'changed'
    ));

    it('can delete domains', fn () => assertCrudDelete(
        '/api/domains/'.Domain::whereName('localhost')->first()->id,
        Domain::class
    ));
});
