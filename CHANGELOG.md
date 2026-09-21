# Changelog

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
