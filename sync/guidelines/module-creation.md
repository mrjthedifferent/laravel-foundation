## Creating a New Module — Step by Step

This project uses `nwidart/laravel-modules`. Follow these exact steps every time you create a new module.

### Step 1 — Scaffold the module

```bash
php artisan foundation:make-module Thing
```

Not `module:make` — that's nwidart's own generic scaffold and produces a
different structure (a separate `RouteServiceProvider`/`EventServiceProvider`,
among other things) than this package's convention. `foundation:make-module`
generates `Modules/{Name}/` from this package's own stubs: `module.json`, a
single `{Name}ServiceProvider` already extending `ModuleServiceProvider`, the
full directory skeleton, and `config/permissions.php` / `config/settings.php`
already in place. The module is auto-loaded by nwidart — do **not** register
it in `bootstrap/providers.php`.

### Step 2 — Fill in the ServiceProvider

The generated `Modules/Thing/app/Providers/ThingServiceProvider.php` already
extends `ModuleServiceProvider`; add what the module actually contributes:

```php
namespace Modules\Thing\Providers;

use Modules\Thing\Models\Thing;
use Modules\Thing\Policies\ThingPolicy;
use Mrj\Foundation\Support\ModuleServiceProvider;

class ThingServiceProvider extends ModuleServiceProvider
{
    protected string $name = 'Thing';

    protected string $nameLower = 'thing';

    protected array $morphMap = ['thing' => Thing::class];

    protected array $policies = [Thing::class => ThingPolicy::class];
}
```

Config, views, translations, migrations, `routes/{web,api,console}.php` and
`$listen` event registration are all handled by the base class — there is no
separate `EventServiceProvider` or `RouteServiceProvider` to write.

### Step 3 — Create the Policy

```bash
php artisan module:make-policy ThingPolicy Thing --no-interaction
```

Policy methods must delegate to Spatie named permissions via `$user->can('Permission Name')`. Never use `$user->hasRole()` inside a policy.

```php
class ThingPolicy
{
    public function viewAny(User $user): bool { return $user->can('View Thing'); }
    public function view(User $user, Thing $thing): bool { return $user->can('View Thing'); }
    public function create(User $user): bool { return $user->can('Create Thing'); }
    public function update(User $user, Thing $thing): bool { return $user->can('Edit Thing'); }
    public function delete(User $user, Thing $thing): bool { return $user->can('Delete Thing'); }
}
```

### Step 4 — Create the Model, Migration, Factory

```bash
php artisan module:make-model Thing Thing --migration --no-interaction
php artisan module:make-factory ThingFactory Thing --no-interaction
```

### Step 5 — Create config files (REQUIRED for every module)

**`Modules/Thing/config/permissions.php`**
```php
return [
    ['module_name' => 'Thing Management', 'name' => 'View Thing'],
    ['module_name' => 'Thing Management', 'name' => 'Create Thing'],
    ['module_name' => 'Thing Management', 'name' => 'Edit Thing'],
    ['module_name' => 'Thing Management', 'name' => 'Delete Thing'],
];
```

**`Modules/Thing/config/settings.php`**
```php
return []; // empty unless this module owns global settings
```

### Step 6 — Create the DTO

```bash
php artisan make:class Modules/Thing/app/Data/ThingData --no-interaction
```

Extend `Spatie\LaravelData\Data` and list the fields it carries — nothing
else. It is built via `ThingData::from($request->validated())`, always from
already-validated data, never resolved directly as a controller parameter;
Spatie Data's own validation attributes (`#[Email]`, `#[Min]`, ...) would
only take effect in that second case, so don't add them here. Rules live in
the Form Requests (Step 9).

### Step 7 — Create Actions

```bash
php artisan make:class Modules/Thing/app/Actions/CreateThingAction --no-interaction
php artisan make:class Modules/Thing/app/Actions/UpdateThingAction --no-interaction
```

### Step 8 — Create the Query Builder

```bash
php artisan make:class Modules/Thing/app/Queries/ThingQuery --no-interaction
```

### Step 9 — Create Form Requests

```bash
php artisan module:make-request StoreThingRequest Thing --no-interaction
php artisan module:make-request UpdateThingRequest Thing --no-interaction
```

Each Form Request owns its own `rules()` directly — this is where validation
lives, not the DTO from Step 6.

### Step 10 — Create the Controller

```bash
php artisan module:make-controller ThingController Thing --no-interaction
```

### Step 11 — Create routes

**`Modules/Thing/routes/web.php`**
```php
use Illuminate\Support\Facades\Route;
use Modules\Thing\Http\Controllers\ThingController;

Route::middleware('auth')->prefix('admin')->name('admin.')->group(function () {
    Route::resource('things', ThingController::class);
});
```

**`Modules/Thing/routes/api.php`**
```php
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    // API routes here
});
```

### Step 12 — Create the module layout view

**`Modules/Thing/resources/views/layouts/master.blade.php`**
```blade
<x-app-layout>
    <x-slot name="breadcrumbs">
        <a href="{{ route('admin.dashboard') }}" class="breadcrumb-item">Home</a>
        <a href="{{ route('admin.things.index') }}" class="breadcrumb-item">Things</a>
        @yield('breadcrumb')
    </x-slot>

    @yield('content')
</x-app-layout>
```

### Step 13 — Declare the sidebar pages

**`Modules/Thing/config/menu.php`**

```php
return [
    [
        'group' => 'administration',          // parent key from config/sidebar.php
        'label' => 'Things',
        'icon' => 'ph-cube',
        'route' => 'admin.things.index',
        // Other routes that keep this item highlighted and its parent open.
        'routes' => ['admin.things.show', 'admin.things.create', 'admin.things.edit'],
        // Shown when the user holds any of these. Omit for a page everyone may see.
        'permissions' => ['View Thing', 'Create Thing', 'Edit Thing', 'Delete Thing'],
        'order' => 50,
    ],
];
```

- `group` must match a key in the project's `config/sidebar.php`, which fixes the order, label and icon of the parents. Add a new parent there when none fits.
- An item is hidden when its route does not exist, so a disabled module contributes nothing.
- Use `url` (with optional `target`) instead of `route` for an external link.
- Never build sidebar markup in a module. The layout renders the tree from this config.

### Step 14 — Seed permissions

```bash
php artisan db:seed --class="Modules\\RolePermission\\Database\\Seeders\\RolePermissionPermissionsSeeder"
```

### Step 15 — Create Tests (only when explicitly requested)

```bash
php artisan make:test --phpunit Modules/Thing/tests/Feature/ThingControllerTest
php artisan make:test --phpunit --unit Modules/Thing/tests/Unit/Actions/CreateThingActionTest
```

### Artisan Commands Quick Reference

| What | Command |
|---|---|
| Scaffold module | `php artisan module:make {Name}` |
| Make model | `php artisan module:make-model {Model} {Module} --migration` |
| Make controller | `php artisan module:make-controller {Name} {Module}` |
| Make request | `php artisan module:make-request {Name} {Module}` |
| Make policy | `php artisan module:make-policy {Name} {Module}` |
| Make seeder | `php artisan module:make-seed {Name} {Module}` |
| Make factory | `php artisan module:make-factory {Name} {Module}` |
| Make migration | `php artisan module:make-migration {name} {Module}` |
| Make generic class | `php artisan make:class Modules/{Module}/app/{Path}/{Name}` |
| Run migrations | `php artisan module:migrate {Module}` |
| Enable module | `php artisan module:enable {Name}` |

Always pass `--no-interaction` to every artisan command.
