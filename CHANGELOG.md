# Changelog

## 0.15.0

Architecture, part one: DRY base classes and decoupling `src/` from the
modules. This is the first of two releases covering the plan's Phase 5;
contracts/drivers and the controller/convention cleanup follow in 0.16.0.

- **`src/Models/User.php` no longer imports module classes.** `devices()`,
  `firebaseTokens()`, `documents()`, `loginHistory()` and `latestLogin()` are
  now registered by their owning module via Laravel's own
  `Model::resolveRelationUsing()`, in each module's service provider `boot()`,
  rather than being defined directly on the core abstract model. Behavior is
  unchanged; only where the relation is declared moves.
- **`ModuleServiceProvider` now loads a module's `routes/{web,api,console}.php`
  by convention** and gained a `protected array $listen` property for event
  registration. This deleted all 9 per-module `RouteServiceProvider`s and all
  9 per-module `EventServiceProvider`s (the 3 with real listeners had those
  listeners moved onto their module's main service provider); the stub
  templates for both are removed too. **Fixed along the way**: BackupCleanup's
  `routes/console.php` (the scheduled `system:backup:cleanup` and
  `clear:old-notification` commands) was never actually loaded by anything —
  the old `RouteServiceProvider` only mapped API/web routes — so those two
  scheduled jobs have never run in any installation of this package. They run
  now.
- **New `Mrj\Foundation\Support\WidgetComposer` base class** for the 8
  dashboard widget composers, replacing 8 copies of the same
  permission-check-then-cache-then-build shape with one abstract class and
  three methods per composer (`permissions()`, `key()`, `build()`).
- **New `Mrj\Foundation\Support\QueryBuilder` base class** unifying the two
  incompatible designs the 8 module query classes had grown independently
  (one mutated its builder in place, the other re-wrapped it in a "new self"
  each call that added no actual immutability, since Eloquent's Builder is
  mutable regardless). Includes an escaped `whereLike()` helper (`%`/`_` are
  now matched literally, not as wildcards, including on SQLite, which unlike
  MySQL/PostgreSQL has no default `LIKE` escape character) and a `paginate()`
  capped by `foundation.pagination.max`. One behavior change: `VerificationCodeQuery::paginate()`
  now calls `withQueryString()` like its 7 siblings already did.
- **New `Mrj\Foundation\Support\ExportJob` base class** for the two export
  jobs (Activity Log, User list), replacing 55 lines of duplicated
  handle()/failed() boilerplate — and their `ini_set('memory_limit', '-1')`/
  `set_time_limit(0)` escape hatches — with real, bounded `$tries = 3` and
  `$timeout = 1800` (30 minutes) job properties. **Behavior change**: an
  export that previously ran unbounded now fails after 30 minutes. Neither
  job had a regression test before this; both do now.

## 0.14.0

Configurability: a project can now change the admin panel's URL prefix/domain,
role names, page sizes, date formats, storage disk and cache keys without
touching package code. Route NAMES (`admin.users.index`, ...) stay fixed on
purpose — see UPGRADE.md if you rely on any of the values these replace.

- **Routing**: `foundation.routing.{prefix,domain,middleware,api_prefix}`.
  Every module's `admin`/`v1` URL prefix now reads from config; route names,
  and therefore every `route(...)` call and menu entry, are unaffected.
- **Roles**: `foundation.roles.{super_admin,admin,user}`, read through the new
  `Mrj\Foundation\Support\Roles` accessor rather than a `'Super Admin'`
  literal scattered across 5 files.
- **Guards**: `foundation.guards.web`, the Spatie Permission guard every role
  and permission this package creates is stamped with.
- **Pagination**: `foundation.pagination.{default,max,options}` is now the one
  source for page size — `perPage()`, `cappedPerPage()` and
  `getParPagePaginate()` all read it, and the redundant `PaginationEnum` class
  (two constants, both `10`) is removed.
- **Formats**: `foundation.formats.{date,datetime}` for the admin UI's detail
  pages, exports and notifications.
- **Storage**: `foundation.storage.{disk,exports_path}`. `FileManagerService`
  and the two export jobs read it instead of hard-coding `'public'`; fixed the
  export jobs along the way — they wrote via `storage_path('app/public/...')`
  regardless of which disk they claimed to use, which only ever happened to
  work because the disk was always `'public'`.
- **Cache**: `foundation.cache.prefix`, prepended to every cache key the
  package writes. `SettingsServiceProvider::CACHE_KEY` (a constant) is now
  `cacheKey()` (a method, since it needs to read config); the ~10 places that
  hard-coded the literal `'app_settings'` now call it too.
- **Removed**: `foundation.user_model`. It was read once at boot while ~95
  files import `App\Models\User` directly (Laravel's own convention), so it
  silently did nothing anywhere else. `App\Models\User` extending
  `Mrj\Foundation\Models\User` is documented as a requirement instead.

## 0.13.0

Tooling gate: strict types, static analysis, and a real bug this surfaced. No
intentional behavior change other than the fix and the removed helpers below.

**Found by the new static analysis, not previously caught by any test**

- Fixed: reporting an error to Slack was completely broken —
  `Illuminate\Notifications\Messages\SlackMessage` was referenced but
  `laravel/slack-notification-channel` was never a declared dependency, so the
  class did not exist at runtime. `toSlack()` is only called when a report is
  actually sent, so this was invisible until that moment. Now a real
  dependency, with a regression test.
- Fixed: `ErrorReport::factory()` could not be resolved (`HasFactory`'s default
  guess doesn't match this package's per-module factory namespaces, the same
  reason five other models already override `newFactory()`) — added the same
  override here.

**Static types**

- `declare(strict_types=1)` added to every file where Rector could prove it
  changes nothing (151 files) — see `SafeDeclareStrictTypesRector`; deliberately
  not forced everywhere, since that specific rule matters here (a truly blind
  `declare(strict_types=1)` could turn today's harmless type coercion into a
  `TypeError` elsewhere in the same file).
- `#[\Override]` added to overriding methods; class constants gained native
  types where inferrable.
- Larastan (PHPStan) at level 5 with a committed baseline covering pre-existing
  findings; CI now runs it, plus `rector --dry-run`, on every push.

**Removed (dead code, zero callers anywhere in the codebase)**

- Helpers: `ajaxResponse()`, `getCommonStatus()`, `getIntegerMonth()`,
  `getLast11Digit()`, `engToBangla()`, `currency_number()`, `isImage()`,
  `isUrl()`, `isIndexedArray()`. The last two Bangladesh-specific ones
  (`getLast11Digit`, `engToBangla`) didn't belong in a generic package regardless
  of use.
- `UpdateUserRequest::rolesAreChanging()`, an unused protected method.

**Also**

- Three query classes' fluent methods (`ActivityLogQuery`, `EmailLogQuery`,
  `SmsLogQuery`) return type changed from `static` to `self` — they are `final`
  and always construct `new self(...)`; this makes the declared type match
  reality rather than implying support for subclassing that doesn't exist.

## 0.12.1

- Fixed a test that depended on `owen-it/laravel-auditing`'s version and on model-boot timing
  (caught by CI's `--prefer-lowest` leg, not by the normal test run). No functional change; the
  `encrypted` setting type's audit redaction is unaffected and now covered by a version-independent
  test.

## 0.12.0

Security fixes. This release breaks some plaintext storage and default-credential
behavior on purpose; see below for what to check after upgrading.

**Critical**

- Fixed: the API's `POST /manage-account` (reset/delete a user account) never checked
  authorization on the *target* account — only that the caller's own password was correct. Any
  authenticated user could delete or reset any other user's account, including an admin's, by
  supplying that user's `user_id`. It now requires the `Delete User` permission for any target
  other than the caller's own account, and is blocked in production, matching the web
  equivalent. Self-service (no `user_id`, or your own ID) is unaffected.
- Fixed: outbound HTTP calls (SMS gateway, Firebase push) disabled TLS verification
  (`'verify' => false`) unconditionally, so every request — carrying gateway credentials and
  OAuth tokens — travelled without verifying the server's certificate. Both now use Laravel's
  `Http` client with verification on. `src/Traits/MyGuzzleClient.php` is removed.
- Fixed: an import/export job's remarks (which can hold an exception message) were echoed
  unescaped in the admin UI, a stored-XSS vector. They are now escaped, with line breaks
  preserved separately.

**Secrets**

- New `encrypted` setting type: the value is encrypted at rest (`Modules\Settings\Contracts\SecretCipher`,
  implemented by `MailerSecretCipher`) and excluded from the audit trail's plaintext. OAuth
  client secrets (Google/GitHub/Apple), the Firebase service-account JSON, the error-report
  Slack webhook and Telegram bot token now use it. A plaintext value already in the settings
  table is read back correctly and re-encrypted on next save.
- The generic Settings CRUD UI (`admin.settings.create`/`edit`) gained `encrypted` as a
  selectable type, rendered as a password field that leaves an existing secret unchanged when
  submitted blank.
- `settings.type` changed from a fixed `enum` to `string`, so adding a setting type no longer
  needs a schema migration.

**Accounts**

- The seeded Super Admin (`UserDatabaseSeeder`) refuses to run outside `local`/`testing` when
  `SEED_ADMIN_PASSWORD` is unset, rather than falling back to a well-known default password.
  Whatever password is used — the seeded default, an admin-set reset, or an admin-triggered
  password reset for another user — the affected account is now forced to change it before
  reaching any other page or API endpoint (`must_change_password`, new migration).

**Input validation**

- The API's profile update accepted an image upload with no type or size restriction
  (`UpdateProfileRequest`). It now matches the validation already used for admin-created and
  admin-edited users: `image`, `mimes:jpeg,png,jpg,gif,svg,webp`, `max:2048`.
- `per_page` on activity/email/SMS log listings, error reports and push notifications was
  unbounded; a large value could force an unbounded query. Capped at 100 (`cappedPerPage()`).
- Search filters across users, roles, permissions, activity/email/SMS logs, error reports, push
  notifications, imports/downloads and OTP verification codes treated `%`/`_` in the typed term
  as SQL wildcards rather than literal characters (`escapeLike()`), which could make a search
  match far more than intended, and — on SQLite specifically — silently fail to match a term
  that itself contained `%`.

**Also**

- `Model::preventLazyLoading()` is on outside production, so an N+1 now throws in
  local/CI/staging instead of only showing up as a slow query in production.
- The Slack webhook fallback for error-report notifications read `LOG_SLACK_WEBHOOK_URL` via
  `env()` at runtime (broken once config is cached); it now reads `config('logging.channels.slack.url')`.
- Added `SECURITY.md` with a private disclosure process (GitHub Security Advisories).

## 0.11.2

- The package ships its own Bootstrap 5 paginator (`pagination/links.blade.php`) instead of
  pointing at one of Laravel's, whose view names change between majors. It drops the duplicate
  "Showing x to y of z results" line that Laravel's view printed next to the table's own count.
  Override it with `resources/views/pagination/links.blade.php`.

## 0.11.1

- Fixed: upgrading with a warm cache broke on boot. The `app_settings` key still held `Setting`
  models, which Laravel 13 returns as `__PHP_Incomplete_Class` rather than unserializing, so
  `artisan package:discover` failed during `composer update`. The cache is now rebuilt when it
  does not hold the expected array.

## 0.11.0

Laravel 13. **Requires Laravel `^13.0` and PHP `^8.3`**; Laravel 12 and PHP 8.2 are no longer
supported. Laravel 12 stopped receiving bug fixes on 2026-08-13.

- Dependencies moved up with it: `nwidart/laravel-modules` 13, `spatie/laravel-permission` 8,
  `laravel-notification-channels/telegram` 8, `orchestra/testbench` 11, `konekt/html` 6.8,
  `spatie/laravel-backup` 10.3 (the `^9.2` alternative is gone).
- Settings are cached as plain arrays rather than an Eloquent collection. Laravel 13 refuses to
  unserialize cached objects unless they are allow-listed in `cache.serializable_classes`, whose
  new default is `false`. `SettingsServiceProvider::cached()` is the single reader.
- Pagination renders the Bootstrap 5 views. It called `Paginator::useBootstrap()`, which renders
  Bootstrap **3** markup — wrong for this UI since the theme was rewritten.
- Module log channels register `max_files`, renamed from `days` in Laravel 13.
- The custom mail transports capture the container explicitly instead of relying on `$this`
  inside the `Mail::extend` closure, and the SMTP OAuth transport now has a test.
- CI runs PHP 8.3, 8.4 and 8.5, caches dependencies, and adds a `--prefer-lowest` leg so the
  declared minimums are actually exercised.

## 0.10.1

- Fixed: "Sync Permissions" failed with `Target class [Database\Seeders\PermissionSeeder] does
  not exist`. It now runs the RolePermission module's own seeder, and gives the Super Admin
  role any permissions that were added.
- Fixed: creating or cleaning a backup queued a job that failed, because nothing provided the
  `backup:run` and `backup:clean` commands. The package now requires `spatie/laravel-backup`.
  To change where and what is backed up, publish that package's `config/backup.php`.
- Removed the BackupCleanup module's unused copy of the backup config.

## 0.10.0

- Users have a phone number: new `phone` and `phone_verified_at` columns, added by a new
  migration. Numbers are stored in E.164 form (`+8801712345678`) however they are typed.
- Sign-in, API login and OTP accept a phone number out of the box. `User::scopeWherePhone()`
  now matches on the column; a project that keeps phones elsewhere can still override it.
- User management: phone on the create and edit forms (unique, validated against the allowed
  country codes), on the list and detail pages, in search, bulk upload and export, plus a
  "Phone Verified" filter and a manual "Verify Phone" action.
- The `verified` middleware passes when either the email or the phone is verified.
- Fixed: the bulk upload "Download Sample" link pointed at a file the package never shipped.
  The sample is now generated on request.

## 0.9.0

First public release.

- Core runtime: helpers, rules, traits, services, exception handler, JSON response factory,
  middleware, base `User`, `Audit` and `DeviceToken` models, and the `Foundation::middleware()`,
  `Foundation::exceptions()` and `Foundation::singletons()` helpers for `bootstrap/app.php`.
- Modules: User, RolePermission, Settings, Notification, ActivityLog, BackupCleanup,
  ImportDownloadManager, and the optional Otp and ErrorReport.
- `ModuleServiceProvider` base class: a module declares its morph map aliases, policies, view
  composers, commands and middleware as properties.
- Admin UI on Bootstrap 5.3: layouts, 26 Blade components, error pages, light and dark mode,
  RTL, colour palettes and a collapsible sidebar.
- Commands: `foundation:install`, `foundation:make-module`, `foundation:publish`, `foundation:sync`.
- `FoundationSeeder` for permissions, settings, base roles and the first Super Admin.
