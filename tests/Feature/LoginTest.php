<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Motor\Admin\Database\Seeders\UsersTableSeeder;

pest()->group('Login')->use(RefreshDatabase::class);

it('logs the user in', function () {
    $response = $this->post('/login', [
        'email'    => 'admin@motor-cms.com',
        'password' => 'admin',
    ]);

    $response->assertStatus(302);
    $response->assertJsonStructure([
        'status',
        'message',
        'data' => ['token'],
    ]);
    // expect($response->baseResponse->original["data"])->toHaveProperty('token');
});
