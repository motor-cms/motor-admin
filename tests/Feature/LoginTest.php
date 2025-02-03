<?php

use Motor\Admin\Database\Seeders\UsersTableSeeder;

it('logs the user in', function () {
    $seeder = new UsersTableSeeder;
    //$seeder->run();
    $response = $this->post('/api/auth/login', [
        'email' => "admin@motor-cms.com",
        'password' => "admin"
    ]);
    
    $response->assertStatus(200);
    $response->assertJsonStructure([
        'status',
        'message',
        'data' => ['token']
    ]);
    //expect($response->baseResponse->original["data"])->toHaveProperty('token');
});

