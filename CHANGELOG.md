# Changelog

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
