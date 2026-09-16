<?php

use App\Http\Middleware\InternalApiToken;
use Illuminate\Http\Request;
use Motor\Admin\Http\Controllers\Api\AdminNavigationsController;
use Motor\Admin\Http\Controllers\Api\AIHelpController;
use Motor\Admin\Http\Controllers\Api\AISystemPromptController;
use Motor\Admin\Http\Controllers\Api\Auth\AuthController;
use Motor\Admin\Http\Controllers\Api\CategoriesController;
use Motor\Admin\Http\Controllers\Api\CategoryTreesController;
use Motor\Admin\Http\Controllers\Api\ClientsController;
use Motor\Admin\Http\Controllers\Api\ConfigVariablesController;
use Motor\Admin\Http\Controllers\Api\DomainsController;
use Motor\Admin\Http\Controllers\Api\EmailTemplatesController;
use Motor\Admin\Http\Controllers\Api\EmailTemplatesSendController;
use Motor\Admin\Http\Controllers\Api\EmailTemplateUsageController;
use Motor\Admin\Http\Controllers\Api\Frontend\DomainsController as FrontendDomainsController;
use Motor\Admin\Http\Controllers\Api\Frontend\EntityConfigurationsController as FrontendEntityConfigurationsController;
use Motor\Admin\Http\Controllers\Api\LanguagesController;
use Motor\Admin\Http\Controllers\Api\PermissionGroupsController;
use Motor\Admin\Http\Controllers\Api\PermissionsController;
use Motor\Admin\Http\Controllers\Api\ProfileEditController;
use Motor\Admin\Http\Controllers\Api\RolesController;
use Motor\Admin\Http\Controllers\Api\UsersController;
use Motor\Admin\Http\Controllers\Api\V2\AISystemPromptsController;
use Motor\Admin\Http\Controllers\Api\V2\DashboardAnnouncementsController;
use Motor\Admin\Http\Controllers\Api\V2\DashboardController;
use Motor\Admin\Http\Controllers\Api\V2\EmailTemplateDuplicateController;
use Motor\Admin\Http\Controllers\Api\V2\EntityConfigurationsController;
use Motor\Admin\Http\Controllers\Api\V2\FlatCategoriesController;
use Motor\Admin\Http\Resources\UserResource;
use Motor\Core\Http\Middleware\ScopeRequestsToClient;
use Motor\Core\Http\Middleware\V2\V2ErrorHandler;

// Route::apiResource('api/email_templates', EmailTemplatesController::class)->middleware('auth:sanctum');

Route::middleware('auth:sanctum')
    ->group(function () {
        Route::apiResource('users', UsersController::class);
        Route::apiResource('clients', ClientsController::class);
        Route::apiResource('domains', DomainsController::class);
        Route::apiResource('languages', LanguagesController::class);
        Route::apiResource('roles', RolesController::class);
        Route::apiResource('permission_groups', PermissionGroupsController::class);
        Route::apiResource('permissions', PermissionsController::class);
        Route::get('permissions_items/{permission_group}', [PermissionsController::class, 'items']);
        Route::apiResource('email_templates', EmailTemplatesController::class);
        Route::post('email_templates/duplicate', [EmailTemplatesController::class, 'duplicate']);
        Route::get('email_templates/{template_id}/usage', [EmailTemplateUsageController::class, 'usage'])
            ->name('email_templates.usage');

        Route::apiResource('ai_system_prompts', AISystemPromptController::class);
        Route::post('ai_help', [AIHelpController::class, 'store']);

        // Sanctum is bypassed because Nitro (the caller) cannot present a
        // user-scoped Sanctum token after the rolling-auth switch. Instead
        // this route is gated by a service-to-service shared secret that
        // lives only in backend + Nitro env, never in the browser.
        // EN-2258: 'frontend-email-send' caps the form-triggered mail send per
        // visitor IP; 'internal-email-send' stays as the global safety cap.
        Route::post('email_templates/send', [EmailTemplatesSendController::class, 'send'])
            ->withoutMiddleware(['auth:sanctum'])
            ->middleware([InternalApiToken::class, 'throttle:frontend-email-send', 'throttle:internal-email-send']);

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
        Route::post('profile/reset-onboarding', [ProfileEditController::class, 'resetOnboarding'])
            ->name('profile.reset-onboarding');
        Route::post('profile/complete-onboarding', [ProfileEditController::class, 'completeOnboarding'])
            ->name('profile.complete-onboarding');
        Route::apiResource('config_variables', ConfigVariablesController::class);

        Route::get('admin_navigations', [AdminNavigationsController::class, 'index'])
            ->name('admin_navigations.index');

        Route::get('user', function (Request $request) {
            return new UserResource($request->user()->load(['roles.permissions', 'permissions']));
        });
    });

// Route::post('/api/auth/register', [AuthController::class, 'register']);
Route::post('/auth/login', [AuthController::class, 'login']);

Route::group(['middleware' => ['auth:sanctum']], function () {
    Route::get('/me', function (Request $request) {
        return new UserResource(auth()->user()->load(['roles.permissions', 'permissions']));
    });
    Route::post('/auth/logout', [AuthController::class, 'logout']);
});

Route::group([
    'prefix' => 'api/frontend',
], static function () {
    Route::get('domains/get_active_domains', [FrontendDomainsController::class, 'index']);
    Route::get('entity-configurations', [FrontendEntityConfigurationsController::class, 'index']);
});

/*
|--------------------------------------------------------------------------
| V1 Explicit Routes (frozen - same behavior as unversioned)
|--------------------------------------------------------------------------
*/
Route::prefix('v1')
    ->name('v1.')
    ->middleware('auth:sanctum')
    ->group(function () {
        Route::apiResource('users', UsersController::class);
        Route::apiResource('clients', ClientsController::class);
        Route::apiResource('domains', DomainsController::class);
        Route::apiResource('languages', LanguagesController::class);
        Route::apiResource('roles', RolesController::class);
        Route::apiResource('permission_groups', PermissionGroupsController::class);
        Route::apiResource('permissions', PermissionsController::class);
        Route::get('permissions_items/{permission_group}', [PermissionsController::class, 'items']);
        Route::apiResource('email_templates', EmailTemplatesController::class);
        Route::post('email_templates/duplicate', [EmailTemplatesController::class, 'duplicate']);
        Route::apiResource('config_variables', ConfigVariablesController::class);
        Route::apiResource('ai_system_prompts', AISystemPromptController::class);
        Route::apiResource('category_trees/{category_tree}/categories', CategoriesController::class, [
            'parameters' => ['category_trees' => 'category'],
        ]);
        Route::apiResource('category_trees', CategoryTreesController::class, [
            'parameters' => ['category_trees' => 'category'],
        ]);
        Route::get('category_trees/scope/{scope}', [CategoryTreesController::class, 'byScope']);
    });

/*
|--------------------------------------------------------------------------
| V2 Routes (standardized kebab-case naming, consistent response envelope)
|--------------------------------------------------------------------------
*/
Route::prefix('v2')
    ->name('v2.')
    ->middleware(['auth:sanctum', V2ErrorHandler::class, ScopeRequestsToClient::class])
    ->group(function () {
        Route::apiResource('users', Motor\Admin\Http\Controllers\Api\V2\UsersController::class);
        Route::apiResource('clients', Motor\Admin\Http\Controllers\Api\V2\ClientsController::class);
        Route::apiResource('domains', Motor\Admin\Http\Controllers\Api\V2\DomainsController::class);
        Route::apiResource('languages', Motor\Admin\Http\Controllers\Api\V2\LanguagesController::class);
        Route::apiResource('roles', Motor\Admin\Http\Controllers\Api\V2\RolesController::class);
        Route::apiResource('permission-groups', Motor\Admin\Http\Controllers\Api\V2\PermissionGroupsController::class);
        Route::apiResource('permissions', Motor\Admin\Http\Controllers\Api\V2\PermissionsController::class);
        Route::get('permissions-items/{permission_group}', [Motor\Admin\Http\Controllers\Api\V2\PermissionsController::class, 'items']);
        Route::apiResource('email-templates', Motor\Admin\Http\Controllers\Api\V2\EmailTemplatesController::class);
        Route::post('email-templates/duplicate', [Motor\Admin\Http\Controllers\Api\V2\EmailTemplatesController::class, 'duplicate']);
        Route::post('email-templates/{email_template}/duplicate', [EmailTemplateDuplicateController::class, 'store'])
            ->name('email-templates.duplicate');
        Route::get('email-templates/{template_id}/usage', [Motor\Admin\Http\Controllers\Api\V2\EmailTemplateUsageController::class, 'usage'])
            ->name('email-templates.usage');
        Route::apiResource('config-variables', Motor\Admin\Http\Controllers\Api\V2\ConfigVariablesController::class);
        Route::apiResource('entity-configurations', EntityConfigurationsController::class);
        Route::apiResource('ai-system-prompts', AISystemPromptsController::class);
        Route::get('categories', [FlatCategoriesController::class, 'index']);
        Route::apiResource('category-trees/{category_tree}/categories', Motor\Admin\Http\Controllers\Api\V2\CategoriesController::class, [
            'parameters' => ['category-trees' => 'category'],
        ]);
        Route::apiResource('category-trees', Motor\Admin\Http\Controllers\Api\V2\CategoryTreesController::class, [
            'parameters' => ['category-trees' => 'category'],
        ]);
        Route::get('category-trees/scope/{scope}', [Motor\Admin\Http\Controllers\Api\V2\CategoryTreesController::class, 'byScope']);

        Route::get('admin-navigations', [AdminNavigationsController::class, 'index'])
            ->name('admin-navigations.index');

        // Dashboard
        Route::get('dashboard', [DashboardController::class, 'index']);
        Route::apiResource('dashboard/announcements', DashboardAnnouncementsController::class)
            ->parameters(['announcements' => 'announcement']);
        Route::post('dashboard/announcements/{announcement}/dismiss', [DashboardAnnouncementsController::class, 'dismiss']);
    });
