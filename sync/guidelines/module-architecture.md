## Module Architecture

This application uses `nwidart/laravel-modules`. Every feature lives inside `Modules/{ModuleName}/`. Never add feature code to `app/` unless it is genuinely cross-cutting (e.g. a shared Trait, Rule, or Enum used by 3+ modules).

### Required Module Directory Layout

```
Modules/{Name}/
├── app/
│   ├── Actions/          # Business logic — one public execute() per class
│   ├── Data/             # Spatie LaravelData DTOs — fields only, no validation
│   ├── Enum/             # Module-specific backed string enums
│   ├── Events/           # Domain events dispatched by Actions
│   ├── Http/
│   │   ├── Controllers/  # Ultra-thin web + API controllers
│   │   └── Requests/     # Form Requests — own their own rules()
│   ├── Jobs/             # ShouldQueue jobs for async work
│   ├── Models/           # Eloquent models
│   ├── Notifications/    # Laravel Notification classes
│   ├── Policies/         # Gate policies (one per model)
│   ├── Providers/        # {Name}ServiceProvider only — see below
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

### The One Provider a Module Needs

A module needs exactly one provider class, `{Name}ServiceProvider`, extending
`Mrj\Foundation\Support\ModuleServiceProvider`. There is no separate
`RouteServiceProvider` or `EventServiceProvider` to generate — the base
class loads the module's config, views, translations, migrations, and
`routes/{web,api,console}.php` (by convention: `web.php` gets the `web`
middleware, `api.php` gets `api` + an `api.` name prefix, `console.php` is
just `require`d for scheduled/artisan commands), and dispatches `$listen`
through `Event::listen()`. The subclass only declares what the module
contributes:

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

    protected array $listen = [
        ThingCreated::class => [NotifyThingCreated::class],
    ];
}
```

Also available: `$commands`, `$middlewareAliases`, `$prependToGroups` and `$appendToGroups`. Override `boot()` or `register()` only for anything else, and call the parent first — for example, to bind a contract this module provides the implementation for:

```php
#[Override]
public function register(): void
{
    parent::register();

    $this->app->singleton(SomeContract::class, ConcreteImplementation::class);
}
```

- Never add a module's models to a morph map, policies or middleware anywhere outside its own provider.
- Never edit `bootstrap/app.php` or `AppServiceProvider` to wire up a module.

### Foundation Modules

User, Settings, RolePermission, Notification, ActivityLog, BackupCleanup and ImportDownloadManager (plus the optional Otp and ErrorReport) ship inside the `mrjthedifferent/laravel-foundation` package under `vendor/`, not in this project's `Modules/` directory. `modules_statuses.json` switches them on and off.

- Never edit them, and never run `module:make-*` or `module:delete` against them. A change every project should get belongs in the foundation repository.
- Never create a project module with the same name as a foundation module.
- To change one of their views for this project only, create `resources/views/modules/{alias}/{same path}`. To change one of their config files, run `php artisan vendor:publish --tag={alias}-module-config` and edit the copy under `config/{alias}/`.

### Route Structure

Web routes are always wrapped in the configurable admin group (prefix, domain and middleware from `config('foundation.routing')`; route names always `admin.*`):
```php
Route::middleware(config('foundation.routing.middleware'))
    ->domain(config('foundation.routing.domain'))
    ->prefix(config('foundation.routing.prefix'))
    ->name('admin.')
    ->group(function (): void {
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
