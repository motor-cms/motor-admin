<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\Fluent\AssertableJson;
use Motor\Admin\Models\Client;
use Motor\Admin\Models\Role;
use Motor\Admin\Models\User;

pest()->group('V2', 'User')->use(RefreshDatabase::class);

describe('V2 Users API', function () {

    it('returns users with V2 envelope', function () {
        $this->asAdmin()
            ->getJson('/api/v2/users')
            ->assertStatus(200)
            ->assertJsonStructure([
                'data' => [['id', 'name', 'email']],
                'meta' => ['api_version', 'pagination'],
                'links',
            ])
            ->assertJsonPath('meta.api_version', 'v2');
    });

    it('includes pagination structure in meta', function () {
        $this->asAdmin()
            ->getJson('/api/v2/users')
            ->assertStatus(200)
            ->assertJson(fn (AssertableJson $json) => $json
                ->has('meta.pagination.current_page')
                ->has('meta.pagination.per_page')
                ->has('meta.pagination.total')
                ->has('meta.pagination.total_pages')
                ->has('meta.pagination.has_more')
                ->etc()
            );
    });

    it('returns single user with V2 envelope', function () {
        $user = User::first();

        $this->asAdmin()
            ->getJson("/api/v2/users/{$user->id}")
            ->assertStatus(200)
            ->assertJsonPath('meta.api_version', 'v2')
            ->assertJsonPath('data.id', $user->id);
    });

    it('returns 201 on create with V2 envelope', function () {
        $newUser = [
            'clients' => [Client::first()->id],
            'email' => 'v2test@test.de',
            'name' => 'V2 Test User',
            'password' => 'testtest',
            'roles' => [Role::whereName('SuperAdmin')->first()->id],
        ];

        $this->asAdmin()
            ->postJson('/api/v2/users', $newUser)
            ->assertStatus(201)
            ->assertJsonPath('meta.api_version', 'v2')
            ->assertJsonPath('meta.message', 'User created')
            ->assertJsonPath('data.name', 'V2 Test User');
    });

    it('returns 200 on update with V2 envelope', function () {
        $user = User::whereEmail('writer@motor-cms.com')->first();

        $this->asAdmin()
            ->putJson("/api/v2/users/{$user->id}", [
                'email' => 'writer@motor-cms.com',
                'name' => 'Updated Writer',
                'roles' => [Role::whereName('SuperAdmin')->first()->id],
            ])
            ->assertStatus(200)
            ->assertJsonPath('meta.api_version', 'v2')
            ->assertJsonPath('data.name', 'Updated Writer');
    });

    it('returns 204 on delete', function () {
        $user = User::factory()->create();

        $this->asAdmin()
            ->deleteJson("/api/v2/users/{$user->id}")
            ->assertStatus(204)
            ->assertNoContent();
    });
});
