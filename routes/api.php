<?php

use Motor\Admin\Http\Controllers\Api\AdminNavigationsController;
use Motor\Admin\Http\Controllers\Api\Auth\AuthController;
use Motor\Admin\Http\Controllers\Api\CategoriesController;
use Motor\Admin\Http\Controllers\Api\CategoryTreesController;
use Motor\Admin\Http\Controllers\Api\ClientsController;
use Motor\Admin\Http\Controllers\Api\ConfigVariablesController;
use Motor\Admin\Http\Controllers\Api\DomainsController;
use Motor\Admin\Http\Controllers\Api\EmailTemplatesController;
use Motor\Admin\Http\Controllers\Api\EmailTemplatesSendController;
use Motor\Admin\Http\Controllers\Api\LanguagesController;
use Motor\Admin\Http\Controllers\Api\PermissionGroupsController;
use Motor\Admin\Http\Controllers\Api\PermissionsController;
use Motor\Admin\Http\Controllers\Api\ProfileEditController;
use Motor\Admin\Http\Controllers\Api\RolesController;
use Motor\Admin\Http\Controllers\Api\UsersController;
use Motor\Admin\Http\Middleware\EkproAuth;

Route::group([
    'middleware' => ['auth:sanctum', 'bindings'],
    'prefix' => 'api',
    'as' => 'api.',
], static function () {
    Route::apiResource('users', UsersController::class);
    Route::apiResource('clients', ClientsController::class);
    Route::apiResource('domains', DomainsController::class);
    Route::apiResource('languages', LanguagesController::class);
    Route::apiResource('roles', RolesController::class);
    Route::apiResource('permission_groups', PermissionGroupsController::class);
    Route::apiResource('permissions', PermissionsController::class);
    Route::get('permissions_items/{id}', [PermissionsController::class, 'items']);
    Route::apiResource('email_templates', EmailTemplatesController::class);

    // Dont use sanctum auth for this route, use static token
    Route::post('email_templates/send', [EmailTemplatesSendController::class, 'send'])->withoutMiddleware(['auth:sanctum'])->middleware(EkproAuth::class);

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

    Route::get('category_trees/scope/{scope}', [CategoryTreesController::class, 'byScope'])
        ->name('category_trees.by_slug');
    Route::get('profile', [ProfileEditController::class, 'me'])
        ->name('profile.read');
    Route::put('profile', [ProfileEditController::class, 'update'])
        ->name('profile.update');
    Route::apiResource('config_variables', ConfigVariablesController::class);

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
