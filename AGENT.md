# AGENT.md -- motor-admin

Instructions for AI agents working on this package.

## Purpose

Administration package providing user management, role-based access control (Spatie Permissions), authentication (Sanctum), categories, clients, domains, configuration variables, email templates, and AI system prompts. This is the **gold standard package** -- use its patterns as reference when building features in other packages.

## Models

| Model | Purpose | Key Relationships |
|-------|---------|-------------------|
| `User` | User accounts | roles, permissions, client |
| `Role` | RBAC roles (Spatie) | permissions, users |
| `Permission` | Individual permissions (Spatie) | roles |
| `PermissionGroup` | Groups permissions for UI display | permissions |
| `Client` | Multi-tenant client organizations | users, domains |
| `Domain` | Client domains | client |
| `Category` | Hierarchical categories (nested set) | parent, children |
| `Language` | Available languages | -- |
| `ConfigVariable` | Key-value config storage | client |
| `EmailTemplate` | Email template management | -- |
| `AISystemPrompt` | AI assistant system prompts | -- |

## Controllers (API)

All controllers follow the delegate-to-service pattern:

```
src/Http/Controllers/Api/
├── UsersController.php              # Gold standard CRUD
├── RolesController.php
├── PermissionsController.php
├── PermissionGroupsController.php
├── ClientsController.php
├── DomainsController.php
├── CategoriesController.php
├── CategoryTreesController.php      # Nested tree operations
├── LanguagesController.php
├── ConfigVariablesController.php
├── EmailTemplatesController.php
├── EmailTemplatesSendController.php  # Single-action: send email
├── EmailTemplateUsageController.php  # Single-action: check usage
├── AISystemPromptController.php
├── AIHelpController.php             # AI assistant endpoint
├── ProfileEditController.php        # Authenticated user profile
├── AdminNavigationsController.php   # Admin sidebar navigation
├── Auth/                            # Login, logout, CSRF
├── Frontend/                        # Public-facing endpoints
└── V2/                              # V2 versioned controllers
```

## Services

Each model has a corresponding service that extends `BaseService`:

```php
// Static factory pattern
$result = UserService::create($request)->getResult();
$result = UserService::update($record, $request)->getResult();
UserService::delete($record);
```

`BaseService` is defined in this package at `src/Services/BaseService.php` (not in motor-core).

## Form Requests

Each action gets a dedicated Form Request:

```
src/Http/Requests/Api/
├── UserGetRequest.php       # GET index -- sorting, filtering, pagination
├── UserPostRequest.php      # POST store -- validation rules for creation
├── UserPatchRequest.php     # PATCH update -- validation rules for update
```

## Resources

```
src/Http/Resources/
├── UserResource.php         # Single resource transformation
├── UserCollection.php       # Paginated collection
```

## Adding a New Admin Entity

### 1. Create the Model

```bash
# Inside Docker container
php artisan make:model --no-interaction
```

Add to `src/Models/`. Include:
- `$fillable` array
- Relationships with return type hints
- `searchableOptions` if using filters

### 2. Create the Service

Create `src/Services/{Entity}Service.php` extending `BaseService`:

```php
class EntityService extends BaseService
{
    protected string $model = 'Motor\Admin\Models\Entity';

    // Override lifecycle hooks as needed:
    // beforeCreate(), afterCreate(), beforeUpdate(), afterUpdate(), beforeDelete()
}
```

### 3. Create Form Requests

Create `src/Http/Requests/Api/{Entity}GetRequest.php`, `{Entity}PostRequest.php`, `{Entity}PatchRequest.php`.

### 4. Create Resources

Create `src/Http/Resources/{Entity}Resource.php` and `{Entity}Collection.php`.

### 5. Create the Controller

Create `src/Http/Controllers/Api/{Entity}Controller.php` following the `UsersController` pattern.

### 6. Register Routes

Add to `routes/api.php`:

```php
Route::apiResource('entity_name', EntityController::class);
```

### 7. Add Migration and Seeder

Create in `database/migrations/` and `database/seeders/`.

### 8. Write Tests

Create `tests/Feature/{Entity}Test.php` using Pest.

## Authentication

Auth controllers in `src/Http/Controllers/Api/Auth/`:
- Login/logout via Sanctum tokens
- CSRF cookie endpoint
- Password reset flow via Fortify

## Policies

`src/Policies/` -- authorization policies for each model, checking Spatie permissions.

## Email System

`src/Email/` -- email-related classes for template rendering and sending.

## Route Convention

**snake_case** for all routes in this package:

```
/api/users
/api/permission_groups
/api/email_templates
/api/category_trees
/api/config_variables
/api/ai_system_prompts
```

## Testing

Tests in `tests/Feature/` cover all CRUD operations for each entity. Follow the existing test pattern:

```php
it('can create User', function () {
    $this->asAdmin()
        ->withJsonHeaders()
        ->postJson('/api/users', [...])
        ->assertStatus(201);
});
```

Run with: `./vendor/bin/pest --filter Motor\\Admin`

## Architecture Notes

- This package is a **terminal consumer** of motor-core but also provides `BaseService` which other packages extend.
- Other packages depend on motor-admin for the `User` model, `Client` model, and auth infrastructure.
- The `UsersController` and `UserService` are the **gold standard** reference implementations.
