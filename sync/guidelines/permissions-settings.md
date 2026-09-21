## Permissions System

Permissions are defined per module in `config/permissions.php` and auto-seeded by `RolePermissionPermissionsSeeder` which scans all enabled modules.

The `User` model uses Spatie's `HasRoles` trait, which powers `$user->can()`, `$user->hasPermissionTo()`, `$user->hasAnyPermission()`, and all Blade directives (`@can`, `@cannot`, `@canany`). All permission checks in this application go through named string permissions — **never use `$user->hasRole()` for authorization logic**.

### Defining Permissions

Every module must have `Modules/{Name}/config/permissions.php`:

```php
return [
    ['module_name' => 'Thing Management', 'name' => 'View Thing'],
    ['module_name' => 'Thing Management', 'name' => 'Create Thing'],
    ['module_name' => 'Thing Management', 'name' => 'Edit Thing'],
    ['module_name' => 'Thing Management', 'name' => 'Delete Thing'],
    // Non-CRUD operations get their own entries:
    ['module_name' => 'Thing Management', 'name' => 'Export Thing'],
    ['module_name' => 'Thing Management', 'name' => 'Import Thing'],
];
```

- `module_name` — the group label in the permissions UI. Use "X Management" format.
- `name` — globally unique. Use "Verb Noun" format: `View Thing`, `Create Thing`, `Edit Thing`, `Delete Thing`.
- Every CRUD operation and every non-CRUD operation (export, import, password reset, etc.) needs its own entry.
- Never hard-code permission strings outside of `config/permissions.php` and the policy that checks them.

### Seeding Permissions

Run after adding new permissions:
```bash
php artisan db:seed --class="Modules\\RolePermission\\Database\\Seeders\\RolePermissionPermissionsSeeder"
```

In tests, seed permissions in `setUp()`:
```php
\Spatie\Permission\Models\Permission::firstOrCreate(
    ['name' => 'Create Thing'],
    ['module_name' => 'Thing Management', 'guard_name' => 'web']
);
```

### Checking Permissions

Always via policy in controllers:
```php
$this->authorize('create', Thing::class);
```

Direct check only in non-controller contexts (jobs, console):
```php
if (! $user->can('Export Thing')) {
    throw new AuthorizationException;
}
```

### Blade Authorization Directives

`@can`, `@cannot`, and `@canany` resolve through the registered policy via Spatie's `HasPermissions` trait:

```blade
{{-- Single permission --}}
@can('Create Thing')
    <a href="{{ route('admin.things.create') }}" class="btn btn-primary">Add Thing</a>
@endcan

{{-- Any of several permissions --}}
@canany(['Edit Thing', 'Delete Thing'])
    <x-dropdown-menu> ... </x-dropdown-menu>
@endcanany
```

Sidebar visibility is not written in Blade: list the permissions on the item in the module's `config/menu.php` and the sidebar shows it to users holding any of them.

- Use `@can` / `@canany` for individual UI elements (buttons, links, menu items).

---

## Settings System

Application settings are stored in the `settings` database table, managed by `Modules/Settings`. Cache key: `app_settings` — automatically invalidated on every save/delete.

### Declaring Module Settings

Every module must have `Modules/{Name}/config/settings.php`. Return an empty array if the module owns no global settings:
```php
return []; // most modules return this
```

### Accessing Settings

`SettingsServiceProvider` loads all settings into the config system at boot (from the `app_settings` cache), keyed as:
```php
config('settings.{key}') // returns ['group' => ..., 'type' => ..., 'value' => ..., 'description' => ...]
```

**Prefer `config()` — it is already in memory with no DB hit:**
```php
config('settings.app_name.value')         // preferred
config('settings.thing_max_count.value')  // preferred
```

`getSystemSetting($key)` queries the DB directly on every call — avoid it:
```php
getSystemSetting('app_name') // avoid — hits DB every time
```

- Never query the `Setting` model directly in application code.
- Never use `env()` for settings that are user-configurable at runtime.

### Setting Types

| Type | Storage | Cast |
|---|---|---|
| `string` | raw string | string |
| `integer` | raw string | `(int)` |
| `float` | raw string | `(float)` |
| `boolean` | `0`/`1` | `(bool)` |
| `array` / `multi-select` | comma-separated | array via `explode(',', ...)` |
| `json` | JSON string | decoded array |
| `image` | path | URL via `FileManagerService::getImage()` |
| `file` | path | URL via `FileManagerService::getFile()` |
| `select` | raw string | string, with options JSON |

### Settings with Dedicated Pages

Settings that need custom UI use `is_visible => false` and are managed by dedicated pages. Two patterns:

1. **Centralized (Settings module)** — OAuth, payment keys, SMS/email gateways, theme, etc. live under `SpecialSettingsController` / `ThemeSettingsController` in the Settings module.
2. **Module-owned** — When a module has settings that belong to its domain (e.g. Error Report), create a settings page inside that module, add a permission (e.g. `Edit Error Report Settings`), register a gate, and add a link in the module's sidebar menu. Keep those settings `is_visible => false` so they never appear in the generic settings page.
