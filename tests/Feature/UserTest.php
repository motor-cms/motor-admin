<?php

use Illuminate\Testing\Fluent\AssertableJson;
use Motor\Admin\Models\Client;
use Motor\Admin\Models\Role;
use Motor\Admin\Models\User;

describe('User', function () {
    it('checks if Avatar is an image', function () {
        $new_user = [
            'avatar' => [
                'dataUrl' => 'QlpoOTFBWSZTWcjFbMkAAAxaAAAQQABoAEAAIAAwwAZoRbpBiJlkcOAvF3JFOFCQyMVsyQ==',
                'name'    => 'test.pbm',
            ],
            'clients'  => [Client::first()->id],
            'email'    => 'test@test.de',
            'name'     => 'TestUser',
            'password' => 'testtest',
            'roles'    => [Role::whereName('SuperAdmin')->first()->id],
        ];
        $this->asAdmin()->post('/api/users', $new_user)->assertStatus(201);
    })->fail();
    it('can create User', function () {
        $usercount = User::count();
        $new_user = [
            'avatar' => [
                'dataUrl' => 'UDEKMyAzCjEgMSAxCjAgMSAwCjAgMSAwCg==',
                'name'    => 'test.pbm',
            ],
            'clients'  => [Client::first()->id],
            'email'    => 'test3@test.de',
            'name'     => 'TestUser3',
            'password' => 'testtest',
            'roles'    => [Role::whereName('SuperAdmin')->first()->id],

        ];
        $response = $this->asAdmin()->post('/api/users', $new_user);
        $response->assertStatus(201);
        expect(User::count() - $usercount)->toBe(1);
    });
    it("can't create Users with invalid clients", function () {
        $this->asAdmin()->withJsonHeaders()->post('/api/users', [
            'email'    => 'test2@test.de',
            'name'     => 'test2',
            'password' => 'awrftwaftawtf',
            'clients'  => [0],
        ])->assertStatus(422);
    });
    it('can get all Users')
        ->asAdmin()
        ->getJson('/api/users')
        ->assertStatus(200)
        ->assertJson(fn (AssertableJson $json) => $json->has(
            'data',
            3,
            fn (AssertableJson $data) => $data
                ->has('id')
                ->has('email')
                ->has('clients')
                ->has('roles')
                ->has('name')
                ->etc()
        )->etc());
    it(
        'can get a specific User',
        fn () => $this->asAdmin()->getJson('/api/users/'.$this->admin()->id)
            ->assertStatus(200)
            ->assertJson(
                fn (AssertableJson $json) => $json->has(
                    'data',
                    fn (AssertableJson $data) => $data
                        ->where('id', $this->admin()->id)
                        ->where('email', 'admin@motor-cms.com')
                        ->count('roles', 1)
                        ->etc()
                )->etc()
            )
    );
    it('can delete a user', function () {
        $usercount = User::count();
        $this->asAdmin()->delete('/api/users/'.User::whereEmail('writer@motor-cms.com')->first()->id)
            ->assertStatus(200);
        expect($usercount - User::count())->toBe(1);
    });
    it(
        'can modify a user',
        fn () => $this->asAdmin()->put('/api/users/'.$this->admin()->id, [
            'email' => 'admin@motor-cms.com',
            'name'  => 'Motor Admin 2',
            'roles' => [Role::whereName('SuperAdmin')->first()->id],
        ])
            ->assertStatus(200)
            ->assertJson(fn (AssertableJson $json) => $json->has('data', fn (AssertableJson $data) => $data->where('name', 'Motor Admin 2')->etc())->etc())
    );
});
