# Changelog

All notable changes to this package are recorded here. The package follows
[semantic versioning](https://semver.org); see "Public API and versioning" in the README for
what that covers.

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
