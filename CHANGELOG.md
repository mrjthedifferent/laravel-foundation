# Changelog

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
