# Motor Admin

Administration package for the Motor CMS framework. Provides user management, role-based access control, authentication, and core administrative functionality.

## Installation

```bash
composer require motor-cms/motor-admin
```

## Features

- **User Management** -- CRUD operations for user accounts
- **Role-Based Access Control** -- Powered by Spatie Laravel Permission
- **Authentication** -- Laravel Sanctum token-based auth
- **Multi-Tenant Clients** -- Client organizations with domain management
- **Categories** -- Hierarchical category trees (nested set)
- **Configuration** -- Key-value config variable storage per client
- **Email Templates** -- Managed email templates with sending capability
- **AI System Prompts** -- AI assistant prompt management
- **Languages** -- Language management

## Models

| Model | Description |
|-------|-------------|
| `User` | User accounts with roles and permissions |
| `Role` | RBAC roles (Spatie) |
| `Permission` | Individual permissions (Spatie) |
| `PermissionGroup` | Permission grouping for UI |
| `Client` | Multi-tenant client organizations |
| `Domain` | Client domains |
| `Category` | Hierarchical categories |
| `Language` | Available languages |
| `ConfigVariable` | Key-value configuration |
| `EmailTemplate` | Email template management |
| `AISystemPrompt` | AI assistant prompts |

## API Endpoints

All endpoints use Sanctum authentication and snake_case naming:

```
/api/users
/api/roles
/api/permissions
/api/permission_groups
/api/clients
/api/domains
/api/categories
/api/category_trees
/api/languages
/api/config_variables
/api/email_templates
/api/ai_system_prompts
```

## Package Structure

```
src/
├── Console/       # Artisan commands
├── Email/         # Email handling classes
├── Helpers/       # Helper functions
├── Http/          # Controllers, Requests, Resources
├── Models/        # Eloquent models
├── Policies/      # Authorization policies
├── Providers/     # Service providers
├── Rules/         # Custom validation rules
├── Services/      # Business logic (BaseService + domain services)
└── Traits/        # Shared traits
```

## Dependencies

- `motor-cms/motor-core`
- `spatie/laravel-permission`
- `laravel/sanctum`
- `laravel/fortify`

## Credits

- [Reza Esmaili](https://github.com/dfox288)
