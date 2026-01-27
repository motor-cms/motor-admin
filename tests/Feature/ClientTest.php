<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Motor\Admin\Models\Client;

pest()->group('Client')->use(RefreshDatabase::class);

describe('Client', function () {
    it('can create a Client', fn () => assertCrudCreate(
        '/api/clients',
        ['name' => 'test', 'slug' => 'test'],
        Client::class
    ));

    it("can't create an empty Client", fn () => assertCrudValidation(
        '/api/clients',
        [],
        Client::class
    ));

    it('can get all Clients', fn () => assertCrudIndex(
        '/api/clients',
        1,
        ['id', 'name', 'slug']
    ));

    it('can get a specific Client', fn () => assertCrudShow(
        '/api/clients/'.Client::whereName('Default')->first()->id,
        ['id', 'name', 'slug', 'address', 'zip', 'city', 'country_iso_3166_1', 'website', 'description', 'is_active', 'contact_name', 'contact_phone', 'contact_email']
    ));

    it('can update clients', fn () => assertCrudUpdate(
        '/api/clients/'.Client::whereName('Default')->first()->id,
        ['name' => 'changed', 'slug' => 'changed'],
        'name',
        'changed'
    ));

    it('can delete clients', fn () => assertCrudDelete(
        '/api/clients/'.Client::whereName('Default')->first()->id,
        Client::class
    ));
});
