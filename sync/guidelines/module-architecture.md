## Module Architecture

This application uses `nwidart/laravel-modules`. Every feature lives inside `Modules/{ModuleName}/`. Never add feature code to `app/` unless it is genuinely cross-cutting (e.g. a shared Trait, Rule, or Enum used by 3+ modules).

### Required Module Directory Layout

```
Modules/{Name}/
├── app/
│   ├── Actions/          # Business logic — one public execute() per class
│   ├── Data/             # Spatie LaravelData DTOs + validation rule repositories
│   ├── Enum/             # Module-specific backed string enums
│   ├── Events/           # Domain events dispatched by Actions
│   ├── Http/
│   │   ├── Controllers/  # Ultra-thin web + API controllers
│   │   └── Requests/     # Form Requests (delegate rules to DTOs)
│   ├── Jobs/             # ShouldQueue jobs for async work
│   ├── Models/           # Eloquent models
│   ├── Notifications/    # Laravel Notification classes
│   ├── Policies/         # Gate policies (one per model)
│   ├── Providers/        # {Name}ServiceProvider, RouteServiceProvider, EventServiceProvider
│   ├── Queries/          # Fluent query builders (final readonly, make() factory)
│   ├── Services/         # Cross-action services (final readonly, constructor-injected)
│   └── Transformers/     # Eloquent API Resources
├── config/
│   ├── permissions.php   # Module permission definitions (REQUIRED)
│   └── settings.php      # Module settings definitions (REQUIRED, may return [])
├── database/
│   ├── factories/        # Model factories with states
│   ├── migrations/       # Database migrations
│   └── seeders/          # Seeders
├── resources/
│   ├── lang/             # Translations
│   └── views/            # Blade templates (namespace: {lowercase-name}::)
├── routes/
│   ├── web.php           # Auth-gated web routes
│   └── api.php           # Sanctum API routes
└── tests/
    ├── Feature/          # Controller + policy + full request-cycle tests
    └── Unit/             # Action + service unit tests
```

### The Three Required Providers

Every module requires three provider classes:

**1. `{Name}ServiceProvider`** — the main provider. It extends `Mrj\Foundation\Support\ModuleServiceProvider`, which loads the module's config, views, translations and migrations and registers the two providers below. The subclass only declares what the module contributes:

```php
class ThingServiceProvider extends ModuleServiceProvider
{
    protected string $name = 'Thing';

    protected string $nameLower = 'thing';

    // Every model that is audited or used in a polymorphic relation needs an alias.
    // The alias is stored in the database: never change one once released.
    protected array $morphMap = ['thing' => Thing::class];

    protected array $policies = [Thing::class => ThingPolicy::class];

    protected array $composers = ['thing::partials.dashboard-widget' => ThingWidgetComposer::class];
}
```

Also available: `$commands`, `$middlewareAliases`, `$prependToGroups` and `$appendToGroups`. Override `boot()` or `register()` only for anything else, and call the parent first.

- Never add a module's models to a morph map, policies or middleware anywhere outside its own provider.
- Never edit `bootstrap/app.php` or `AppServiceProvider` to wire up a module.

**2. `RouteServiceProvider`** — maps web and API routes:

```php
protected function mapWebRoutes(): void
{
    Route::middleware('web')->group(module_path($this->name, '/routes/web.php'));
}

protected function mapApiRoutes(): void
{
    Route::middleware('api')->prefix('api')->name('api.')->group(module_path($this->name, '/routes/api.php'));
}
```

**3. `EventServiceProvider`** — sets `protected static $shouldDiscoverEvents = true` for auto-discovery.

### Foundation Modules

User, Settings, RolePermission, Notification, ActivityLog, BackupCleanup and ImportDownloadManager (plus the optional Otp and ErrorReport) ship inside the `mrjthedifferent/laravel-foundation` package under `vendor/`, not in this project's `Modules/` directory. `modules_statuses.json` switches them on and off.

- Never edit them, and never run `module:make-*` or `module:delete` against them. A change every project should get belongs in the foundation repository.
- Never create a project module with the same name as a foundation module.
- To change one of their views for this project only, create `resources/views/modules/{alias}/{same path}`. To change one of their config files, run `php artisan vendor:publish --tag={alias}-module-config` and edit the copy under `config/{alias}/`.

### Route Structure

Web routes are always wrapped:
```php
Route::middleware('auth')->prefix('admin')->name('admin.')->group(function () {
    Route::resource('things', ThingController::class);
    // additional non-resource routes here
});
```

API routes use Sanctum:
```php
Route::middleware('auth:sanctum')->group(function () {
    // API routes here
});
```

Always use named routes and the `route()` helper for links.
