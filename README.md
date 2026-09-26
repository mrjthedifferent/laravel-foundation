<p align="center">
  <picture>
    <source media="(prefers-color-scheme: dark)" srcset="ui/public/assets/images/logo-light.svg">
    <img src="ui/public/assets/images/logo.svg" alt="Laravel Foundation" width="330">
  </picture>
</p>

<p align="center">
  <a href="https://github.com/mrjthedifferent/laravel-foundation/actions/workflows/tests.yml"><img src="https://github.com/mrjthedifferent/laravel-foundation/actions/workflows/tests.yml/badge.svg" alt="tests"></a>
  <a href="LICENSE"><img src="https://img.shields.io/badge/license-MIT-blue.svg" alt="MIT license"></a>
</p>

# Laravel Foundation

The part of an admin application you would otherwise rebuild, or copy, for every project:
sign-in, users, roles and permissions, settings, notifications, activity logs, backups,
import and export jobs, and a Bootstrap 5 admin UI.

It ships as one Composer package. A project requires it and never copies its files, so a fix
made here reaches every project with `composer update`. That is the point: copied starter
kits drift apart; a package does not.

- Laravel 13, PHP 8.3+, built on [nwidart/laravel-modules](https://github.com/nWidart/laravel-modules)
- Server-rendered Blade, Bootstrap 5.3, no build step required for the admin theme
- Light and dark mode, RTL, 8 accent palettes (or your own brand colour), collapsible sidebar
- Everything a project needs to change is overridable without forking

## What is inside

| Module | Provides |
|---|---|
| User | Sign-in by email or phone, password reset by email, profile, user management, bulk upload, documents, login history, impersonation, API session endpoints |
| RolePermission | Roles and permissions ([spatie/laravel-permission](https://github.com/spatie/laravel-permission)) with a management UI |
| Settings | Database-backed settings, mail and SMS gateways, social sign-in keys, theme, privacy policy and terms pages |
| Notification | In-app, email, SMS and push (FCM) notifications with per-channel switches |
| ActivityLog | Audit trail ([owen-it/laravel-auditing](https://github.com/owen-it/laravel-auditing)), request, email and SMS logs, log viewer |
| BackupCleanup | Database and file backups, scheduled clean-up |
| ImportDownloadManager | Queued import and export jobs with a download centre |
| Otp *(optional)* | One-time codes by email or SMS |
| ErrorReport *(optional)* | Captures exceptions and notifies you |

Plus the core: helpers, a JSON response factory, an exception handler, middleware, 26 Blade
components, layouts, error pages and base migrations.

## Quick start

The fastest way is the [project skeleton](https://github.com/mrjthedifferent/laravel-foundation-skeleton),
which is a fresh Laravel app already wired up:

```bash
git clone https://github.com/mrjthedifferent/laravel-foundation-skeleton my-project
cd my-project
composer install
cp .env.example .env && php artisan key:generate
php artisan migrate --seed
php artisan foundation:super-admin
npm install && npm run build
php artisan serve
```

Sign in at `/login` with the Super Admin you just created.

## Install in a Laravel 13 app

```bash
laravel new my-project && cd my-project

composer config allow-plugins.wikimedia/composer-merge-plugin true
composer require mrjthedifferent/laravel-foundation
php artisan foundation:install
php artisan migrate --seed
php artisan foundation:super-admin
npm install && npm run build
```

`foundation:super-admin` asks for a name, email and password and creates the first Super Admin;
sign in with it at `/login`.

**Super Admin is not a role.** It is a flag on the user that passes every permission check, so
roles and permissions can be edited freely without anyone locking themselves out. Only the
console grants or removes it: `php artisan foundation:super-admin someone@example.com` promotes
an existing user, `--revoke` removes the flag, `--list` shows who has it. In the admin panel no
one can deactivate, delete, reset or impersonate a Super Admin, and ordinary admins cannot edit
one. Seeders never create one.

`foundation:install` does the rest of the wiring. Run it with `--dry-run` first to see the list:

- `App\Models\User` extends the foundation user; the user factory creates active users
- `bootstrap/app.php` gets the foundation's middleware, exception handling and handler
- `DatabaseSeeder` calls the foundation's seeder; `/` redirects to the dashboard
- Laravel's stock users, cache and jobs migrations are removed (it asks first; the foundation
  ships its own)
- the Composer scripts that keep the theme and shared files current, the Vite `@foundation`
  alias, and the four npm packages the admin UI needs
- the module scanner points at the package, and the core modules are enabled

It only replaces a file that is still exactly as Laravel generated it. A file you have changed
is left alone, and the command prints what to add by hand. Running it again changes nothing.

The dashboard route (`admin.dashboard`) and the sidebar's parent groups come from the package.
To change the groups, `php artisan vendor:publish --tag=foundation-sidebar`; to use your own
dashboard page, create `resources/views/dashboard.blade.php`.

The dashboard shows headline stats, a chart of sign-ins and a recent-activity feed, then one
widget per module. A module joins in without touching the page: a `StatComposer` listed in
`$dashboardStats` adds a headline stat, a `ChartComposer` in `$dashboardCharts` supplies the
chart's series (lowest priority wins), and a `partials/dashboard-widget.blade.php` view adds a
card. Each is permission-gated and cached the same way, and contributes nothing when the viewer
may not see it.

## Your own modules

```bash
php artisan foundation:make-module Invoice --group=administration
composer dump-autoload && php artisan migrate && php artisan db:seed
```

This creates `Modules/Invoice` with a model, migration, factory, policy, controller, routes,
an index page, a sidebar entry, permissions and a feature test. A module's provider extends
`Mrj\Foundation\Support\ModuleServiceProvider` and declares what it contributes:

```php
class InvoiceServiceProvider extends ModuleServiceProvider
{
    protected string $name = 'Invoice';
    protected string $nameLower = 'invoice';

    protected array $morphMap = ['invoice' => Invoice::class];
    protected array $policies = [Invoice::class => InvoicePolicy::class];
}
```

Also available: `$composers`, `$commands`, `$middlewareAliases`, `$prependToGroups` and
`$appendToGroups`. Nothing about a module is registered in `bootstrap/app.php` or
`AppServiceProvider`.

## One database per tenant (opt-in)

For a multi-tenant app on a tenancy library such as `stancl/tenancy`, the foundation can
run the same modules in the central app and inside every tenant. It is off by default, and
while off nothing behaves differently. The package never depends on a tenancy library: the
project connects its library through config and one contract.

- Each module declares where it runs in `module.json`: `"context": "universal"` (the
  default: central and every tenant, as all foundation modules are), `"central"` or
  `"tenant"`. `foundation:make-module Invoice --context=tenant` writes it.
- `foundation.tenancy` in `config/foundation.php` turns it on and names the library's
  middleware per context, the central domains, and the events after which the tenant changed.
- Bind `Mrj\Foundation\Contracts\TenancyContext` to a small adapter over the library.
- Set `'auto-discover' => ['migrations' => false]` in `config/modules.php`, and point the
  library's tenant migrator at `app(MigrationPaths::class)->for(ModuleContext::Tenant)`.

Routes, migrations, the sidebar, the dashboard, permission seeding, settings (including the
mailer), the settings cache and the permission cache then follow the current tenant. The
full guide is `.ai/guidelines/foundation/tenancy.md` after `foundation:sync`.

## Two-factor authentication (opt-in)

Set `FOUNDATION_TWO_FACTOR=true` and users can turn on TOTP two-factor authentication from
their profile: they scan a QR code in any authenticator app, confirm a code, and receive
recovery codes. Sign-in (password or social) then asks for a code, and the API's `login`
takes `two_factor_code` or `recovery_code`. Each code works once.

Users of the roles in `foundation.two_factor.required_roles` must set it up before they can
use the panel. So must super admins when `required_for_super_admins` is on. For another
rule, override `requiresTwoFactor(): bool` on `App\Models\User`. An administrator who may
reset passwords can also reset a user's two-factor authentication from the user's page.

## Customising without forking

| To change | Do this |
|---|---|
| A layout, component or error page | Create the same path under `resources/views`, e.g. `resources/views/components/page-header.blade.php` |
| A module's view | Create `resources/views/modules/{alias}/{same path}` |
| A module's menu, permissions or settings | `php artisan vendor:publish --tag={alias}-module-config`, then edit `config/{alias}/` |
| Which modules are on | `modules_statuses.json`, or `php artisan module:enable Otp` |
| Who may sign in | Override `accessDenialMessage(): ?string` on `App\Models\User` |
| Who must use two-factor authentication | `two_factor.required_roles` in `config/foundation.php`, or override `requiresTwoFactor(): bool` on `App\Models\User` |
| Where phone numbers live | Users have a `phone` column, stored in E.164 form, and can sign in with it. To keep phones in your own table instead, override `scopeWherePhone($query, ?string $phone)` on `App\Models\User` |
| Colours, dark mode, RTL, sidebar style | Settings → Theme, or the settings seeder |
| PDF fonts | Put a `.ttf` in `resources/fonts` and list it under `pdf.fonts` in `config/foundation.php` |
| Files `foundation:sync` overwrites | List them under `sync.except` in `config/foundation.php` |
| Any text, or the language | `php artisan vendor:publish --tag=foundation-lang`, or a copy of a module's file at `resources/lang/modules/{alias}/{locale}/{alias}.php`; menu, permission and setting names in `lang/{locale}.json` |
| The admin URL prefix, domain or middleware | `routing` in `config/foundation.php` (route names stay `admin.*`) |

Registration is closed by default: an administrator creates accounts.

## Commands

| Command | Purpose |
|---|---|
| `foundation:install` | Wire a Laravel app to the foundation (`--dry-run` to preview; safe to run again) |
| `foundation:super-admin {login?}` | Create a Super Admin, or promote an existing user (`--revoke`, `--list`) |
| `foundation:make-module {Name}` | Create a module on the foundation conventions (`--group`, `--context`) |
| `foundation:publish` | Copy theme assets to `public/assets` (skips when current; `--force`, `--link`) |
| `foundation:sync` | Update shared tooling files: AI coding guidelines, `pint.json`, `.scripts/laravel.sh` (`--check` for CI) |

## Updating

```bash
composer update mrjthedifferent/laravel-foundation
php artisan migrate
```

Read the [CHANGELOG](CHANGELOG.md) before a major version; it lists anything a project must do.

## Public API and versioning

The package follows [semantic versioning](https://semver.org). A breaking change to
any of these waits for the next major version:

- classes and interfaces marked `@api`: `Foundation`, the base classes a project extends
  (`Models\User`, `Support\ModuleServiceProvider`, `WidgetComposer`, `StatComposer`,
  `ChartComposer`, `QueryBuilder`,
  `ExportJob`, `Http\Controllers\Controller`, `Exceptions\Handler`), every interface in
  `Contracts`, `JsonResponseFactory`, `Roles`, `Email`, `PhoneNumber`, `FileManagerService`,
  `Tenancy`, `MigrationPaths`, `Enums\ModuleContext`, `Events\TenancyContextChanged`,
  the validation rules and `HasImageAttribute`
- the global helper functions, config keys, route names (`admin.*`, `api.*`), Blade component
  tags, translation keys, permission names and the database schema
- the Artisan commands and their options

A module's public surface is its routes, views (overridable by path), config, permissions,
translation keys and events; its actions, controllers, jobs and other classes are internal.
Anything marked `@internal` may change in a minor release. Classes not meant to be extended
are `final`.

## Contributing

See [CONTRIBUTING.md](CONTRIBUTING.md). Security issues: [SECURITY.md](SECURITY.md).

## Credits and license

Laravel Foundation is open-source software by [Md. Mahfuzur Rahman](https://github.com/mrjthedifferent),
released under the [MIT license](LICENSE). Bundled front-end libraries keep their own licenses:
see [THIRD-PARTY-NOTICES.md](THIRD-PARTY-NOTICES.md). Laravel is a trademark of Taylor Otwell;
this project is not affiliated with Laravel.
