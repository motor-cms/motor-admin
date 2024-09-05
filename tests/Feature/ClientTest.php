<?php

use Illuminate\Testing\Fluent\AssertableJson;
use Motor\Admin\Models\Client;

describe('Client', function () {
    it('can create a Client', function () {
        $clientcount = Client::count();
        $this->asAdmin()
             ->post('/api/clients', [
                 'name' => 'test',
                 'slug' => 'test',
             ])->assertStatus(201);
        expect(Client::count() - $clientcount)->toBe(1);
    });
    it("can't create an empty Client", function () {
        $clientcount = Client::count();
        $this->asAdmin()->withJsonHeaders()
            ->post('/api/clients', [])->assertStatus(422);
        expect(Client::count() - $clientcount)->toBe(0);
    });
    it('can get all Clients')
        ->asAdmin()
        ->get('/api/clients')
        ->assertStatus(200)
        ->assertJson(fn(AssertableJson $json) => $json->has(
            'data',
            2,
            fn(AssertableJson $data) =>
            $data
                ->has('id')
                ->has('name')
                ->has('slug')
                ->etc()
        )->etc());
    it(
        'can get a specific Client',
        fn() =>
        $this->asAdmin()->get('/api/clients/' . Client::whereName('Default')->first()->id)
            ->assertStatus(200)
            ->assertJson(fn(AssertableJson $json) => $json->has(
                'data',
                fn(AssertableJson $data) =>
                $data
                    ->has('id')
                    ->has('name')
                    ->has('slug')
                    ->has('address')
                    ->has('zip')
                    ->has('city')
                    ->has('country_iso_3166_1')
                    ->has('website')
                    ->has('description')
                    ->has('is_active')
                    ->has('contact_name')
                    ->has('contact_phone')
                    ->has('contact_email')
            )->etc())
    );
    it('can update clients', fn() => $this->asAdmin()
        ->put('/api/clients/' . Client::whereName('Default')->first()->id, [
            'name' => 'changed'
        ])->assertStatus(200)
        ->assertJson(fn(AssertableJson $json) => $json->has('data', fn(AssertableJson $data) =>
        $data->where('name', 'changed')->etc())->etc()));
    it('can delete clients', function() {
        $clientcount = Client::count();
        $this->asAdmin()->delete('/api/clients/'.Client::whereName('changed')->first()->id)
            ->assertStatus(200);
        expect($clientcount - Client::count())->toBe(1);
        shell_exec('php artisan migrate:fresh --seed');
    });
});
