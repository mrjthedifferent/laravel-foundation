# Changelog

All notable changes to this package are recorded here. The package follows
[semantic versioning](https://semver.org); see "Public API and versioning" in the README for
what that covers.

## 1.4.0

**Added**
- `activitylog.sensitive_keys`: more keys whose values the request/response log masks.
  Use it for personal data your project handles, e.g. `['msisdn', 'national_id']`.
  Keys match exactly, case-insensitive, at any depth, and a matching array is masked whole.
  Publish the module config (`php artisan vendor:publish --tag=activitylog-module-config`)
  to set it.

**Fixed**
- With tenancy enabled, the permission cache could serve a tenant a stale list of
  permissions. That list might, for example, have been cached while the tenant's database
  was still being migrated, so users were refused pages their role allows. The registrar
  kept the cache store it was built with. On every tenant change it now re-reads the store,
  and drops its in-memory permissions and roles, before being re-keyed.

## 1.3.2

**Fixed**
- With tenancy enabled, `SettingsSettingsSeeder` seeded every module's settings everywhere.
  A tenant module's settings reached the central database, and a central module's reached
  tenants. It now filters by module context, as the permissions seeder does. A single setting
  can also be limited with `'contexts' => ['central']` (or `['tenant']`).
- `foundation:make-module --context=tenant` generated a test that could only fail: it asked
  for the module's page on the central host, where tenant routes do not exist. The test now
  skips with a note to initialise a tenant in `setUp()` first.

## 1.3.1

**Fixed**
- With tenancy enabled, booting failed for a central module whose `routes/web.php` has the
  shape `foundation:make-module` generates. Such a file calls
  `->domain(config('foundation.routing.domain'))`, which is `null`, and Laravel merged that
  `null` with the central domain into an array. The error was
  `preg_match_all(): Argument #2 ($subject) must be of type string, array given`.
  While a central module's route file loads, `foundation.routing.domain` now holds the
  central domain it is bound to.

## 1.3.0

Opt-in support for one database per tenant. The same modules run in a central app and inside
every tenant, on whatever tenancy library the project uses (stancl/tenancy, for example).
It is **off by default**: with `foundation.tenancy.enabled` false, nothing behaves
differently.

**For a project**
- `foundation.tenancy` in `config/foundation.php` has these keys:
  - `enabled`
  - `middleware` per context (`central`, `tenant`, `universal`)
  - `central_domains`
  - `context_changed_events`, the library's "tenant initialised/ended" events
- New contract `Mrj\Foundation\Contracts\TenancyContext` (`@api`). It is bound to a no-tenancy
  default; bind it to an adapter over your tenancy library.
- New `@api` classes:
  - `Mrj\Foundation\Support\Tenancy`: `enabled()`, `current()`, `moduleBelongsHere()`, `cacheKey()`.
  - `Mrj\Foundation\Support\MigrationPaths`, which lists migration directories by context for the tenant migrator.
  - `Mrj\Foundation\Enums\ModuleContext`.
  - `Mrj\Foundation\Events\TenancyContextChanged`.

**For a module**
- `module.json` accepts `"context": "universal" | "central" | "tenant"`. When it is absent,
  the module is universal, which is what every foundation module is.
- `foundation:make-module --context=tenant` writes it.

**What follows the context when tenancy is enabled**
- Module routes get the context's middleware stack. Central routes are bound to the central
  domains.
- A tenant module's migrations never run in the central database.
- The sidebar, dashboard stats and charts show only the modules that belong where the app is.
- `RolePermissionPermissionsSeeder` seeds only those modules. A single permission can be
  limited with `'contexts' => ['central']`.
- Settings are re-applied to `config()`, including the mailer, whenever the tenant changes.
  Values one tenant set never leak into the next.
- The settings cache and the Spatie permission cache are keyed per tenant.
- `foundation:super-admin` refuses to run inside a tenant.
- New synced guideline: `.ai/guidelines/foundation/tenancy.md`.

**Internal**
- The settings-to-config code moved from `SettingsServiceProvider` into
  `Modules\Settings\Support\SettingsConfigApplier`. It still runs at boot exactly as before.

**Upgrading**

Nothing to do unless you enable tenancy:

```bash
composer update mrjthedifferent/laravel-foundation
php artisan foundation:sync
```

To enable tenancy:
1. Set `'auto-discover' => ['migrations' => false]` in `config/modules.php`. The app refuses to
   boot with tenancy on otherwise, because nwidart would create tenant-module tables centrally.
2. Follow "One database per tenant" in the README.

## 1.2.1

**Fixed**
- `FoundationSeeder` ran `SettingsSettingsSeeder` twice: once directly and again through
  `SettingsDatabaseSeeder` in the module loop. It runs once now.

## 1.2.0

The dashboard is the one from the design reference: a greeting, a row of headline
stats, a chart of sign-ins, and a live activity feed — with every module's widget
still below it.

**The page**
- Four headline stat cards: active users, sign-ins today, activity today and the
  last backup, plus open error reports where that module is enabled.
- An area chart of sign-ins per day, with a 14/30-day switch (`?days=`), drawn as
  inline SVG from the theme's own colours. No charting library, no build step.
- A "Recent activity" feed: what changed, who changed it and when.
- The module widgets keep their place underneath. The User and Backup widgets drop
  the figures now shown above them, and the Activity widget is gone — its number is
  a headline stat and its list is the feed.

**For a module**
- `Mrj\Foundation\Support\StatComposer` (`@api`) contributes a headline stat, with
  the same permissions/cache-key/plain-array contract as `WidgetComposer`. Register
  it in `protected array $dashboardStats` on the module's service provider.
- `Mrj\Foundation\Support\ChartComposer` (`@api`) supplies the chart's series;
  `protected array $dashboardCharts` registers it, and the lowest priority wins.
- `<x-chart-area :series="…" :label="…" />` draws a day => count series.

**Fixed**
- Web sign-ins were never recorded: `TrackLoginAction` ran only for API logins, so
  the login-history page was empty for anyone signing in through the browser.
- The Notification widget was gated on `View Notification`, a permission nothing
  seeds, so only a Super Admin ever saw it. It now uses the module's own
  `View Push Notification`.
- `audits.created_at` and `user_login_history.logged_in_at` carried no index; both
  are scanned by date on every dashboard visit. Two additive migrations add them.
- Card headlines were `<h6>` under the page's `<h1>`, an invalid heading outline for
  anyone navigating by heading. They are `<h2 class="card-title">` now, and look the
  same — `.card-title` always carried the size and weight.

**Upgrading**

```bash
composer update mrjthedifferent/laravel-foundation
php artisan migrate
php artisan foundation:publish --force
```

A project that overrode `modules/activitylog/partials/dashboard-widget.blade.php`
should move that content to its own dashboard view; that partial no longer exists.

## 1.1.0

The admin UI is redesigned: calm and refined, built on a token layer over stock Bootstrap 5.3.
No project code has to change — every component tag, prop, route name, translation key and JS
hook is the same — but the theme options are fewer and the markup around them is new.

**Design**
- `assets/css/foundation.css` is rewritten as a token system (`--fd-*`) mapped onto Bootstrap's
  own variables, with a type scale, a 4px space scale, layered elevation, hairline borders and
  a visible focus ring. Restyling the primitives reaches every page: cards, buttons, forms,
  tables, badges, dropdowns, modals, toasts, pagination and tabs.
- New component classes for the patterns views kept hand-rolling: `.fd-page-head`, `.fd-stat`,
  `.fd-delta`, `.fd-icon-tile`, `.fd-overline`, `.fd-avatar`, `.fd-status`, `.fd-empty`,
  `.fd-dl`, `.fd-feed`, `.fd-toolbar`, `.fd-table-foot`, `.fd-form-section`, `.fd-kbd`.
  The 110 inline `style=` attributes across the module views are gone.

**Shell**
- The sidebar carries the brand, the navigation and the signed-in user. Only the menu scrolls:
  the brand and the profile card stay put, on the same lines as the navbar and the footer.
- The navbar is a three-track row: the mobile toggle, a centred ⌘K search trigger, then the
  online pill, notifications and the account menu. Notifications show an unread dot instead of
  a yellow badge.
- One search entry point. The old navbar search box and its filter dropdown are replaced by the
  ⌘K palette, which searches the sidebar's pages and, when the User module is enabled, people.
- The page header's card is gone: breadcrumb, title, description and actions sit on the page.
- Auth pages use a split layout (brand panel, form panel). Error pages are a calm centred
  layout. The impersonation banner is a slim accent strip.

**Theme options, curated**
- Kept: colour mode (light, dark, auto), direction (LTR, RTL), accent (8 palettes or a custom
  brand colour), sidebar light or dark, and the mini sidebar.
- Removed: layouts 2 and 3, the navbar colour, the per-element sidebar/navbar hex overrides,
  the "primary" sidebar, and the font picker (Inter only). A saved value for a removed option is
  ignored, and an old palette name maps to the nearest curated one, so nothing breaks on update.
- The theme settings page is rebuilt around the kept options, with a live preview.

**Assets**
- Font Awesome is dropped: 436 KB that loaded on every page and no view used. Phosphor is the
  only icon set.
- `resources/css/app.css` keeps only project-specific rules; its duplicates of the theme are
  gone.

**Upgrading**
- Run `php artisan foundation:publish --force` (the Composer scripts already do) and rebuild
  assets. A project that referenced `fa-*` icons, the removed theme settings, or the old
  navbar search elements should read the redesign section of `sync/guidelines/ui-components.md`.

## 1.0.1

- Fixed: a freshly installed app failed its own `pint --test`. `foundation:install` wrote the
  user factory and the module scan path in `config/modules.php` with fully qualified class
  names, which the synced `pint.json` rejects. Both now use imports, and a test runs Pint over
  every PHP file the installer writes. An app already installed only needs `vendor/bin/pint`
  run once.

## 1.0.0 — initial release

The shared base of an admin application, as one Composer package for Laravel 13 (PHP 8.3+).

**Install**
- `php artisan foundation:install` wires a fresh Laravel app in one step:
  - the user model and factory, `bootstrap/app.php`, the seeder, and the `/` redirect
  - the Composer scripts, Vite and npm
  - the stock migrations it replaces

  It has `--dry-run`, is safe to run again, and never overwrites a file the project has
  changed.
- `php artisan foundation:super-admin` creates the first Super Admin (or promotes a user;
  `--revoke`, `--list`).
- `php artisan foundation:make-module` scaffolds a module on the package's conventions.
- `foundation:publish` and `foundation:sync` keep the theme assets and shared tooling files
  current.

**Modules**
- **User**: sign-in by email or phone, password reset, profile, user management, bulk upload,
  documents, login history, impersonation, API session endpoints.
- **RolePermission**: roles and permissions ([spatie/laravel-permission](https://github.com/spatie/laravel-permission))
  with a management UI.
- **Settings**:
  - database-backed settings, including encrypted secrets
  - mail transports (SMTP, Microsoft/Google OAuth) and SMS gateways
  - social sign-in keys, theme, privacy policy and terms pages
- **Notification**: in-app, email, SMS and push (FCM) notifications with per-channel switches.
- **ActivityLog**: audit trail, request, email and SMS logs, log viewer.
- **BackupCleanup**: database and file backups with scheduled clean-up.
- **ImportDownloadManager**: queued import and export jobs with a download centre.
- **Otp** *(optional)*: one-time codes by email or SMS.
- **ErrorReport** *(optional)*: captures exceptions and notifies by mail, Slack or Telegram.

**Security**
- **Super Admin**: a flag on the user, not a role. It passes every permission check, and
  only the console can grant it.
- **Protected accounts**: the admin panel can't deactivate, delete, reset or impersonate a
  Super Admin.
- **Seeded accounts**: seeding creates no account.
- **Secrets**: stored encrypted and kept out of audit logs; outbound TLS is always
  verified.
- **Brute force**: sign-in and API rate limits.
- **Forced password change**: an account whose password an admin reset must choose a new
  one first.

**UI and developer experience**
- **Admin UI**: server-rendered Bootstrap 5.3.
  - light and dark mode, RTL, colour palettes
  - a sidebar built from each module's menu
  - dashboard widgets
  - 26 Blade components, including the `<x-form.*>` form suite
- **Translations**: every user-facing string is in a lang file (`foundation::` plus one per
  module). Config and database labels are translated through `lang/{locale}.json`.
- **Configuration**: route prefix, domain and middleware, role names, pagination, formats,
  storage disk and cache prefix.
- **Extension points**: contracts for SMS, push, settings, file storage, error reporting, OTP
  and import tracking, each with a default implementation.
- **Tooling**: PHPStan (Larastan), Pint and Rector in CI on PHP 8.3–8.5, plus a job that
  installs the package into a fresh Laravel app and opens every admin page.
