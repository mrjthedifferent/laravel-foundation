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

- Laravel 12, PHP 8.2+, built on [nwidart/laravel-modules](https://github.com/nWidart/laravel-modules)
- Server-rendered Blade, Bootstrap 5.3, no build step required for the admin theme
- Light and dark mode, RTL, 11 colour palettes, collapsible sidebar
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
npm install && npm run build
php artisan serve
```

Sign in at `/login` with the `SEED_ADMIN_EMAIL` and `SEED_ADMIN_PASSWORD` from `.env`, then
change the password.

## Install in an existing Laravel 12 app

1. Require the package. Until it is listed on Packagist, add the repository first:

   ```bash
   composer config repositories.foundation vcs https://github.com/mrjthedifferent/laravel-foundation
   composer config allow-plugins.wikimedia/composer-merge-plugin true
   composer require mrjthedifferent/laravel-foundation
   ```

2. Run the installer. It publishes the theme to `public/assets`, enables the core modules,
   points the module scanner at the package and removes Laravel's stock users, cache and jobs
   migrations, which the foundation provides.

   ```bash
   php artisan foundation:install
   ```

3. Make `App\Models\User` extend the foundation user, and apply the foundation in
   `bootstrap/app.php`:

   ```php
   use Mrj\Foundation\Models\User as FoundationUser;

   class User extends FoundationUser {}
   ```

   ```php
   use Mrj\Foundation\Foundation;

   return Application::configure(basePath: dirname(__DIR__))
       ->withRouting(web: __DIR__.'/../routes/web.php', api: __DIR__.'/../routes/api.php', commands: __DIR__.'/../routes/console.php', health: '/up')
       ->withMiddleware(Foundation::middleware(function (Middleware $middleware) {
           // your own middleware
       }))
       ->withExceptions(Foundation::exceptions())
       ->withSingletons(Foundation::singletons())
       ->create();
   ```

4. Define the dashboard route (the package ships a `dashboard` view) and list your sidebar
   parents in `config/sidebar.php`:

   ```php
   Route::middleware('auth')->prefix('admin')->name('admin.')->group(function () {
       Route::view('/dashboard', 'dashboard')->name('dashboard');
   });
   ```

   ```php
   return ['groups' => [
       'communications' => ['label' => 'Communications', 'icon' => 'ph-bell'],
       'administration' => ['label' => 'Administration', 'icon' => 'ph-shield'],
       'settings' => ['label' => 'Settings', 'icon' => 'ph-gear'],
       'imports' => ['label' => 'Import / Download Manager', 'icon' => 'ph-download', 'single' => true],
   ]];
   ```

5. Seed. Call the foundation's seeder first in `DatabaseSeeder`, then run
   `php artisan migrate --seed`:

   ```php
   $this->call(\Mrj\Foundation\Database\Seeders\FoundationSeeder::class);
   ```

6. Keep the theme and shared files current by adding these Composer scripts:

   ```json
   "post-autoload-dump": ["...", "@php artisan foundation:publish --ansi"],
   "post-update-cmd": ["...", "@php artisan foundation:sync --ansi"]
   ```

The project's own scripts and styles are optional. To reuse the package's, alias
`@foundation` to `vendor/mrjthedifferent/laravel-foundation/ui/resources` in `vite.config.js`
and import `@foundation/js/app.js` and `@foundation/css/app.css`. The project then needs
`alpinejs`, `axios`, `laravel-echo` and `pusher-js`.

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

## Customising without forking

| To change | Do this |
|---|---|
| A layout, component or error page | Create the same path under `resources/views`, e.g. `resources/views/components/page-header.blade.php` |
| A module's view | Create `resources/views/modules/{alias}/{same path}` |
| A module's menu, permissions or settings | `php artisan vendor:publish --tag={alias}-module-config`, then edit `config/{alias}/` |
| Which modules are on | `modules_statuses.json`, or `php artisan module:enable Otp` |
| Who may sign in | Override `accessDenialMessage(): ?string` on `App\Models\User` |
| Where phone numbers live | Users have a `phone` column, stored in E.164 form, and can sign in with it. To keep phones in your own table instead, override `scopeWherePhone($query, ?string $phone)` on `App\Models\User` |
| Colours, dark mode, RTL, sidebar style | Settings → Theme, or the settings seeder |
| PDF fonts | Put a `.ttf` in `resources/fonts` and list it under `pdf.fonts` in `config/foundation.php` |
| Files `foundation:sync` overwrites | List them under `sync.except` in `config/foundation.php` |

Registration is closed by default: an administrator creates accounts.

## Commands

| Command | Purpose |
|---|---|
| `foundation:install` | One-time project setup |
| `foundation:make-module {Name}` | Create a module on the foundation conventions |
| `foundation:publish` | Copy theme assets to `public/assets` (skips when current; `--force`, `--link`) |
| `foundation:sync` | Update shared tooling files: AI coding guidelines, `pint.json`, `.scripts/laravel.sh` (`--check` for CI) |

## Updating

```bash
composer update mrjthedifferent/laravel-foundation
php artisan migrate
```

Read [UPGRADE.md](UPGRADE.md) before a major version, or a minor version while on `0.x`.
Released migrations are never edited, only added to.

## Contributing

```bash
composer install
vendor/bin/pint
vendor/bin/phpunit
```

To work on the package against a real project, add a `path` repository pointing at your
checkout and run `php artisan foundation:publish --link`.

## Credits and license

Laravel Foundation is open-source software by [Md. Mahfuzur Rahman](https://github.com/mrjthedifferent),
released under the [MIT license](LICENSE). Bundled front-end libraries keep their own licenses:
see [THIRD-PARTY-NOTICES.md](THIRD-PARTY-NOTICES.md). Laravel is a trademark of Taylor Otwell;
this project is not affiliated with Laravel.
