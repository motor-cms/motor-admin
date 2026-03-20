# Motor Admin Commands

All commands follow the `motor:admin:*` naming convention.

## Setup & Sync

| Command | Description |
|---------|-------------|
| `motor:admin:sync-permissions` | Sync permission records from configuration into the database |
| `motor:admin:sync-scout-indexes` | Flush and rebuild Scout search indexes for all configured models |

### sync-permissions

Reads the `motor-admin-permissions` config (merged from all packages) and creates or updates `PermissionGroup` and `Permission` records via Spatie Laravel Permission. Run after adding new permissions to any package's `motor-admin-permissions.php` config.

### sync-scout-indexes

Calls `scout:sync-index-settings`, then for each model in the `scout.index-settings` config, runs `scout:flush` followed by `scout:import`. Scheduled daily at 01:00.
