<?php

use Illuminate\Foundation\Testing\RefreshDatabase;

pest()
    ->group('Login')
    ->use(RefreshDatabase::class);

it('logs the user in', function () {
    $response = $this->post('/login', [
        'email'    => 'admin@motor-cms.com',
        'password' => 'admin',
    ], [
        'Accept' => 'application/json',
    ]);

    $response->assertStatus(200);
});
