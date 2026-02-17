<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\Fluent\AssertableJson;
use Motor\Admin\Models\Client;
use Motor\Admin\Models\Role;
use Motor\Admin\Models\User;

pest()->group('V2User')->use(RefreshDatabase::class);

describe('V2 User API', function () {

    it('includes api_version v2 in response meta', function () {
        $response = $this->asAdmin()->getJson('/api/v2/users');

        $response->assertStatus(200)
            ->assertJsonPath('meta.api_version', 'v2');
    });

    it('can get all users with relations', function () {
        // Index endpoint eager-loads clients and roles via getPaginator()
        assertV2CrudIndex('/api/v2/users', 4, ['id', 'email', 'name', 'clients', 'roles']);
    });

    it('can get a specific user', function () {
        $response = $this->asAdmin()
            ->getJson('/api/v2/users/'.$this->admin()->id);

        // Note: show endpoint eager-loads clients, roles, roles.permissions, permissions
        $response->assertStatus(200)
            ->assertJsonPath('meta.api_version', 'v2')
            ->assertJson(fn (AssertableJson $json) => $json->has(
                'data',
                fn (AssertableJson $data) => $data
                    ->where('id', $this->admin()->id)
                    ->where('email', 'admin@motor-cms.com')
                    ->etc()
            )->etc());
    });

    it('can create a user', function () {
        $newUser = [
            'avatar' => [
                'dataUrl' => 'UDEKMyAzCjEgMSAxCjAgMSAwCjAgMSAwCg==',
                'name' => 'test.pbm',
            ],
            'clients' => [Client::first()->id],
            'email' => 'v2-test@test.de',
            'name' => 'V2 Test User',
            'password' => 'testtest',
            'roles' => [Role::whereName('SuperAdmin')->first()->id],
        ];

        assertV2CrudCreate('/api/v2/users', $newUser, User::class);
    });

    it('validates required fields on create with V2 error envelope', function () {
        assertV2CrudValidation('/api/v2/users', [], User::class);
    });

    it("can't create users with invalid clients", function () {
        $response = $this->asAdmin()
            ->withHeaders(['Accept' => 'application/json'])
            ->post('/api/v2/users', [
                'email' => 'invalid-client@test.de',
                'name' => 'Invalid Client User',
                'password' => 'testtest',
                'clients' => [0],
            ]);

        $response->assertStatus(422)
            ->assertJsonPath('meta.api_version', 'v2')
            ->assertJsonStructure([
                'error' => ['code', 'message', 'details'],
                'meta' => ['api_version'],
            ]);
    });

    it('can update a user', function () {
        assertV2CrudUpdate(
            '/api/v2/users/'.$this->admin()->id,
            [
                'email' => 'admin@motor-cms.com',
                'name' => 'Motor Admin V2 Updated',
                'roles' => [Role::whereName('SuperAdmin')->first()->id],
            ],
            'name',
            'Motor Admin V2 Updated'
        );
    });

    it('can delete a user with 204 No Content', function () {
        $userToDelete = User::whereEmail('writer@motor-cms.com')->first();

        assertV2CrudDelete('/api/v2/users/'.$userToDelete->id, User::class);
    });

    it('returns 404 with V2 error envelope for non-existent user', function () {
        $response = $this->asAdmin()
            ->getJson('/api/v2/users/99999');

        $response->assertStatus(404)
            ->assertJsonPath('meta.api_version', 'v2')
            ->assertJsonPath('error.code', 'NOT_FOUND')
            ->assertJsonStructure([
                'error' => ['code', 'message'],
                'meta' => ['api_version'],
            ]);
    });

    it('returns 401 for unauthenticated request', function () {
        $response = $this->getJson('/api/v2/users');

        $response->assertStatus(401)
            ->assertJsonPath('meta.api_version', 'v2')
            ->assertJsonPath('error.code', 'UNAUTHORIZED')
            ->assertJsonStructure([
                'error' => ['code', 'message'],
                'meta' => ['api_version'],
            ]);
    });

    it('denies access to basic users', function () {
        assertV2PermissionsDenied('/api/v2/users', $this->admin()->id);
    });

    // Note: client_id filter for users relies on Scout/Meilisearch (no client_id column on users table)
    // Skipping DB-level client_id filter test for users
});
