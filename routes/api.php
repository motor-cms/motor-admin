<?php

use Motor\Admin\Http\Controllers\Api\AdminNavigationsController;
use Motor\Admin\Http\Controllers\Api\Auth\AuthController;
use Motor\Admin\Http\Controllers\Api\CategoriesController;
use Motor\Admin\Http\Controllers\Api\CategoryTreesController;
use Motor\Admin\Http\Controllers\Api\ClientsController;
use Motor\Admin\Http\Controllers\Api\ConfigVariablesController;
use Motor\Admin\Http\Controllers\Api\EmailTemplatesController;
use Motor\Admin\Http\Controllers\Api\LanguagesController;
use Motor\Admin\Http\Controllers\Api\PermissionGroupsController;
use Motor\Admin\Http\Controllers\Api\PermissionsController;
use Motor\Admin\Http\Controllers\Api\ProfileEditController;
use Motor\Admin\Http\Controllers\Api\RolesController;
use Motor\Admin\Http\Controllers\Api\UsersController;
use Motor\Admin\Http\Controllers\Api\CustomerCenterController;

Route::group([
    'middleware' => ['auth:sanctum', 'bindings'],
    'prefix'     => 'api',
    'as'         => 'api.',
], static function () {
    Route::apiResource('users', UsersController::class);
    Route::apiResource('clients', ClientsController::class);
    Route::apiResource('languages', LanguagesController::class);
    Route::apiResource('roles', RolesController::class);
    Route::apiResource('permissions', PermissionsController::class);
    Route::apiResource('permission_groups', PermissionGroupsController::class);
    Route::apiResource('email_templates', EmailTemplatesController::class);
    Route::apiResource('category_trees/{category_tree}/categories', CategoriesController::class, [
        'parameters' => [
            'category_trees' => 'category',
        ],
    ]);
    Route::apiResource('category_trees', CategoryTreesController::class, [
        'parameters' => [
            'category_trees' => 'category',
        ],
    ]);

    Route::get('profile', [ProfileEditController::class, 'me'])
         ->name('profile.read');
    Route::put('profile', [ProfileEditController::class, 'update'])
         ->name('profile.update');
    Route::apiResource('config_variables', ConfigVariablesController::class);
    Route::apiResource('customer_centers', CustomerCenterController::class);

});

Route::post('/api/auth/register', [AuthController::class, 'register']);

Route::post('/api/auth/login', [AuthController::class, 'login']);

Route::group(['middleware' => ['auth:sanctum']], function () {
    Route::get('/api/me', function (Request $request) {
        return new \Motor\Admin\Http\Resources\UserResource(auth()->user());
    });

    Route::post('/api/auth/logout', [AuthController::class, 'logout']);

    Route::get('/api/admin_navigations', [AdminNavigationsController::class, 'index'])
         ->name('admin_navigations.index');
});
