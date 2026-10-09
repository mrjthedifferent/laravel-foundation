# Changelog

All notable changes to this package are recorded here. The package follows
[semantic versioning](https://semver.org); see "Public API and versioning" in the README for
what that covers.

## 3.0.1

**Fixed**
- **Icons could stay blank after updating to 3.0** in browsers that had visited before. Phosphor 1 and 2 both ship `fonts/Phosphor.woff2`, so a browser kept its cached 1.x font: right stylesheet, wrong glyphs.
  - Every published asset the layouts load now carries the package version, e.g. `foundation.css?v=v3.0.1@…`. A cached copy is replaced as soon as the package updates.
  - The icon stylesheets request their fonts with `?v=2.1.2`.

**Added**
- `foundation_asset($path)` returns a published asset's URL with the package version, and `Foundation::assetVersion()` returns the stamp it uses (the same one `foundation:publish` writes). Use the helper for any foundation asset a project links itself, e.g. `phosphor-duotone.css`.

**Upgrading**
- `composer update mrjthedifferent/laravel-foundation`, then `php artisan foundation:publish --force`.
- Coming from 2.x, also run `npm run build`. The ⌘K search palette comes from the Vite bundle (`@foundation/js/app.js`), so a bundle built before 3.0 still shows its old icon. This step is now in the 3.0 upgrade notes.

## 3.0.0

The bundled libraries move to their latest releases, pinned exactly in `bin/build-assets.sh`.

**Changed (breaking)**
- **Phosphor Icons 1.4.2 → 2.1.2.**
  - Every icon now needs a weight class next to it: `ph-gear` is `ph ph-gear`, and the 1.x suffix `ph-star-fill` is `ph-fill ph-star`. A bare `ph-gear` renders nothing.
  - Every 1.4.2 icon name still exists, and about 480 icons are new (`ph-seal`, `ph-stethoscope`, `ph-plus-square`…).
  - The layout loads regular, bold and fill (`phosphor.css`, 212 KB instead of 236 KB). `phosphor-light.css`, `phosphor-thin.css` and `phosphor-duotone.css` ship separately for pages that use those weights.
  - Each weight's font downloads only once an icon in that weight is shown.
  - All of the package's own views, components, menus, sidebar groups, dashboard widgets and guidelines use the new form.
- **jQuery 3.7.1 → 4.0.0.** It drops long-deprecated helpers (`$.trim`, `$.isArray`, `$.isFunction`, `$.parseJSON`, `$.type`, `$.now`…). The package used none of them.

**Changed**
- Select2 4.0.13 → 4.1.0; SweetAlert2 11.26.25, Quill 2.0.3, Ace 1.44.0 and Inter 4.1.1 are now pinned exactly (they were already the newest within their old ranges).

**Added**
- `bin/migrate-phosphor-2.mjs` rewrites a project's icons to Phosphor 2. It covers class attributes, `icon` props, `'icon' => …` values and JavaScript strings, and leaves helpers such as `ph-lg`, CSS selectors and already-migrated icons alone, so it is safe to run again.
- Tests:
  - every icon the package uses exists in the shipped stylesheet;
  - the migration script works, and the package has nothing left to migrate;
  - `foundation.js` works with the bundled jQuery 4 and Select2 4.1 (the jQuery shims and Select2 setup).

**Upgrading**
1. `composer require mrjthedifferent/laravel-foundation:^3.0 --with-dependencies`
2. `node vendor/mrjthedifferent/laravel-foundation/bin/migrate-phosphor-2.mjs Modules resources config app --write`, then review the diff. Add any other folder that names icons; your published `config/sidebar.php` is covered by `config`.
3. In your own scripts, replace any jQuery helper that 4.0 removed (see above) with plain JavaScript.
4. `php artisan foundation:publish --force` and `php artisan foundation:sync`.
5. `npm run build`: the ⌘K search palette in the Vite bundle (`@foundation/js/app.js`) has its own icon.

## 2.1.2

**Fixed**
- `bin/migrate-bootstrap-to-tailwind.mjs` is now in the installed package. The 2.0.0 upgrade steps run it as
  `node vendor/mrjthedifferent/laravel-foundation/bin/migrate-bootstrap-to-tailwind.mjs`, but `.gitattributes` left the
  whole `bin/` folder out of the archive Packagist serves, so the file wasn't there. The shell build scripts in `bin/`
  still stay out. A test now checks that every file the docs run from `vendor/` ships.

## 2.1.1

**Changed**
- `foundation.js` is minified: 25 KB instead of 52 KB. Its readable source is `ui/resources/js/foundation.src.js`; `bash bin/build-js.sh` rebuilds it and CI checks it is current.
  Projects load the same `assets/js/foundation.js`, so nothing changes for them.
- The setting edit page's image field is the same drop zone as everywhere else (stored image as the starting preview), replacing its own preview script.

**Removed**
- The unused `.fd-thumb` class.

## 2.1.0

**Changed**
- **Many more utility classes are compiled into `foundation.css`** (`ui/resources/css/safelist.css`): spacing up to 24, sizing (`w-1/3`, `max-w-3xl`…), flex and grid, positions
  and `z-*`, typography, the token colours (`bg-danger-subtle`, `text-warning-text`, `border-line`), borders, corners, shadows, `hover:` and `print:`, with the responsive
  prefixes. A project can use them in its own views without building Tailwind (guideline `theming.md` lists them). Anything else still needs plain CSS with the `--fd-*` tokens.
- `<x-form.file>` is used on the setting create/edit and import pages; the profile header no longer repeats the email; `<x-image>` no longer writes an inline `style`.
- Drawers keep Tab inside while open, and dropdown menus support ArrowUp, ArrowDown, Home and End (ArrowDown on a closed toggle opens it).

**Added**
- JavaScript tests for `foundation.js` (`bash bin/test-js.sh`, Node and jsdom), run in CI: modals, dropdowns, tabs, collapse, tooltips, the filter bar and its chips, the file drop
  zone, stacked tables, the dashboard editor (reorder, resize, hide, save, reset, drag and drop) and keyboard handling.
- A test that the documented utility classes are in the compiled stylesheet and that no Bootstrap remains in it.

## 2.0.0

**Documentation and cleanup.**

- The AI guidelines synced into a project (`.ai/guidelines/foundation/`) are rewritten for the Tailwind UI and gain `foundation-overview.md` (start here),
  `components-reference.md` (every component with props and slots), `frontend-js.md` (`data-fd-*`, events, the `Foundation` API), `dashboard.md` (every
  extension point with an example) and `theming.md` (tokens, theme settings, which utilities exist). A test fails when a component or dashboard extension
  point is added without being documented.
- Leftovers of the Bootstrap era are removed: unused compatibility classes (`fs-*`, `*-px` sizes, `navbar-brand`, `badge-dark`, `btn-group`,
  `form-control-feedback`, …), the filter-collapse memory script, the `initTooltips` stub, commented Bootstrap imports and unused translation keys. Row-action
  tooltips work again (their trigger attribute was left as `data-fd-popup`).
- `<x-table-view-pagination>` takes `:stack="false"` to keep a wide table scrolling sideways on phones.

**Design pass across every page.**

- **Page width, density and corners** are Theme settings (`theme_content_width` fluid or boxed, `theme_density`
  comfortable or compact, `theme_radius` sharp, rounded or soft), applied to every page through `<html data-width
  data-density data-radius>` and the design tokens. Pages are fluid by default: the 1600px cap is now the "boxed" option.
- **One page header**: `<x-page-header>` (icon, title, subtitle, actions, optional `tabs` slot) now heads the settings pages
  too, in place of their card headers. List pages keep their title in the table toolbar.
- **Filter bar**: `<x-search-card>` is one slim row (search, a Filters button, Reset and Filter) with the other fields in a
  panel and every active filter shown as a chip you can remove. Same slot contract: existing list pages need no change.
- **Tables**: visible row actions, themed scrollbars, hover emphasis, and on phones each row becomes a labelled card
  (`table-stack`, on by default for `<x-table-view-pagination>`; add `table-keep` to opt a table out).
- **Forms**: `<x-form.file>` is a drop zone with a preview and the chosen file's name (same input, same attributes, new
  optional `current` URL); the Profile page is a sectioned layout with a section nav; `.fd-form-actions` is a sticky
  Cancel / Save bar, used by the user, setting and notification forms.
- **Phones**: a bottom navigation bar (Dashboard, three areas, Menu) replaces the footer and the floating gear below `lg`.
- New components `<x-skeleton>` (shimmering loading placeholder) and `<x-empty-state>`.

**The dashboard** is rebuilt as a customizable grid of widgets. Nothing a module registered before stops
working: its stats, chart and `dashboard-widget` partial all appear in the new grid.

- Range picker (7, 14, 30, 90 days) and a **Compare** switch that draws the period before as a dashed
  line, with the total and its change. `?range=` replaces `?days=`, which still works.
- Stat cards can draw a sparkline: give a stat a `series` of counts. The User and Activity stats do.
- **Customize**: every viewer can drag, resize (3, 4, 6, 8, 12 of 12), hide and reorder the cards, and reset
  to the defaults. The layout is stored per user in the new `dashboard_layouts` table (run `php artisan
  migrate`) and merges with whatever is registered now, so enabling or disabling a module never corrupts it.
- New module extension points on the service provider: `$dashboardWidgets` (`DashboardWidget`),
  `$dashboardActions` (`QuickActionComposer`) and `$dashboardHealth` (`HealthCheck`).
- New cards: Quick actions, System health (what needs attention first; the package checks the queue and
  maintenance mode, the modules check backups and error reports), Accounts by status (donut), Sign-ins by
  hour (heatmap) and Changes by type (bars).
- New components `<x-chart-bar>`, `<x-chart-donut>`, `<x-chart-heatmap>` and `<x-sparkline>`, and
  `DailySeries::count()` takes a `$shift` to count the period before a window.

**Tailwind**

The admin UI moves from Bootstrap 5.3 to Tailwind CSS 4. The look is unchanged: the same design
tokens, the same component classes, the same layout. Host apps still need no build step, because
the compiled stylesheet ships in the package as `assets/css/foundation.css`.

**Changed (breaking)**
- Bootstrap's CSS and JavaScript are no longer shipped (`bootstrap.min.css`, `bootstrap.rtl.min.css`,
  `bootstrap.bundle.min.js`). `foundation.css` is now compiled with Tailwind from `ui/resources/css/`
  (`bin/build-css.sh`); right-to-left follows the `dir` attribute, so there is no second stylesheet.
- Layout and spacing in the package's views use Tailwind utilities: `d-flex` is `flex`, `me-2` stays
  `me-2`, `mb-3` is `mb-4`, `row`/`col-md-6` is `grid grid-cols-12 gap-4` / `col-span-12 md:col-span-6`,
  `fw-semibold` is `font-semibold`, `badge bg-success` is `badge badge-success`, and so on. The
  breakpoints keep Bootstrap's widths (576, 768, 992, 1200, 1400).
- `data-bs-*` attributes are now `data-fd-*` (`data-fd-toggle="modal"`, `data-fd-target="#id"`,
  `data-fd-dismiss="modal"`), and the colour mode attribute is `data-theme` instead of `data-bs-theme`.
  The `--bs-*` CSS variables are gone; use the `--fd-*` tokens (`--fd-success-subtle`, `--fd-danger-text`).
- The modal, offcanvas, dropdown, collapse, tab, tooltip, popover and alert behaviour is a small
  built-in script (`foundation.js`) instead of Bootstrap's. Events are `fd:modal-shown`,
  `fd:modal-hidden`, `fd:tab-shown`, `fd:collapse-shown` and so on, and `window.bootstrap` no longer
  exists. `$('#id').modal('show')`, `.tab('show')`, `.collapse()` and `.dropdown()` keep working
  through jQuery, and `Foundation.modal(el)`, `.offcanvas(el)`, `.tab(el)` are the plain-JS forms.

**Added**
- `bin/migrate-bootstrap-to-tailwind.mjs` converts a project's own views to the new classes:
  `node vendor/mrjthedifferent/laravel-foundation/bin/migrate-bootstrap-to-tailwind.mjs resources/views --write`.
  Run it once per file on a clean git tree and review the diff.
- `bin/build-css.sh --check` fails when the committed stylesheet is out of date; CI runs it.

**Upgrading**
- Views that only use the package's components and layouts need no change.
- Project views that write Bootstrap classes or `data-bs-*` attributes themselves: run the migration
  script above. Only the Tailwind classes the package itself uses, plus a common layout set
  (spacing, flex, grid, text, border, rounded, shadow), are in the compiled stylesheet. For anything
  beyond that, add Tailwind to the project and let it scan the project's own views.
- Published views that override the package's (`layouts/*`, `components/*`, `pagination/*`) should be
  re-published or migrated the same way.

## 1.13.3

**Fixed**
- Headings and confirmation texts with `&` or an apostrophe ("Contact & Credentials", "Access & Status"
  on the user form, the Sync Permissions confirmation) showed `&amp;` and `&#039;`: they were
  escaped twice on the way into a component.

## 1.13.2

**Changed**
- Requires `bacon/bacon-qr-code` ^3.1 and `pragmarx/google2fa` ^9.1.

## 1.13.1

**Changed**
- Removed a test that only asserted configuration constants.

## 1.13.0

**Added**
- `foundation.rate_limits.*` (`auth_ip`, `api_user`, `api_guest`) set the requests per
  minute of the `auth` and `api` limiters. The defaults match the previous fixed values.
- `foundation.schema.string_length` sets the default string column length (191 by default,
  as before). Set `FOUNDATION_SCHEMA_STRING_LENGTH=0` to keep Laravel's 255 on a modern
  database.
- `QueryBuilder::applyLike()` runs the escaped, PostgreSQL-aware LIKE search on a plain
  Eloquent builder.
- `SaveSettingAction::hidden()` creates or updates a setting that has no field on the
  generic settings form.
- `Contracts\ResolvesImpersonatorId` returns the impersonator's id without loading the user.
  `ImpersonationContext::STARTED_EVENT` and `ENDED_EVENT` are now defined on the contract
  (`ImpersonationService` still exposes them).

**Changed**
- Searches in Error Reports, the global user search and Roles use the shared LIKE helper, so
  they are case-insensitive and cast columns on PostgreSQL like the other lists.
- The request log and the activity log depend on the `ImpersonationContext` contract instead
  of the User module's service, so ActivityLog works without the User module.
- The Settings, Theme, Security, Notification and Error Report pages save through
  `SaveSettingAction::hidden()` and clear the settings cache through
  `SettingsRepository::forget()`.
- `bacon/bacon-qr-code` and `pragmarx/google2fa` accept any compatible release (`^3.0`,
  `^9.0`) instead of one exact version.
- The deploy workflow templates synced into new projects pass `COMPOSER_AUTH`, as the
  skeleton's own do. CI runs weekly, cancels superseded runs, and reports `composer audit`.

**Upgrading**

```bash
composer update mrjthedifferent/laravel-foundation
```

## 1.12.0

**Added**
- API sign-up with a password and a phone confirmed by code:
  `POST v1/auth/register/request` and `POST v1/auth/register/verify` (Otp module, on with
  the `otp_self_registration_enabled` setting). No account is created until the code is
  confirmed. The new `foundation.registration.roles` and
  `foundation.registration.require_terms` config keys control the role choice and the
  terms check.
- Password reset by code for API clients: `POST v1/auth/password/forgot` and
  `POST v1/auth/password/reset`, on with the new `otp_password_reset_enabled` setting.
  A reset signs out every device.

**Upgrading**

```bash
composer update mrjthedifferent/laravel-foundation
```

Both features stay off until turned on under Settings → OTP. Run "Sync settings" there to
add the new setting to an existing project.

## 1.11.0

**Changed**
- `POST sync/push` reports an operation that failed with a server exception as `error`
  (retryable) instead of `rejected`. A client that dropped rejected changes would otherwise
  lose data when, for example, the database was briefly unavailable. Clients should keep
  and retry `error` operations. `SyncResult::error()` is added.

**Upgrading**

Update offline clients to treat the new `error` status as retryable. Older clients treat
any status they don't know according to their own rules.

## 1.10.0

**Fixed**
- Models with ULID or UUID keys, including every `Syncable` model, can be audited. The
  `audits.auditable_id` column was an integer, so PostgreSQL and MySQL refused the audit
  insert and the save failed with it. A new migration makes it a string; existing ids are
  kept.
- On PostgreSQL, list searches (`QueryBuilder::whereLike()`) cast each column to text and
  match with `ILIKE`. The activity log search failed there, because `LIKE` is not defined
  for the `inet` IP-address column, and searches were case-sensitive, unlike on MySQL and
  SQLite.

**Upgrading**

```bash
composer update mrjthedifferent/laravel-foundation
php artisan migrate
```

## 1.9.1

**Fixed**
- `GET sync/pull` now always returns `changes` and `tombstones` as JSON objects. When nothing
  had changed they were sent as `[]`, which clients that expect a map failed to read.

## 1.9.0

**Added**
- Offline sync for mobile apps. Models use the `Mrj\Foundation\Sync\Syncable` trait
  (client-generated ULID key, soft deletes, a `version` counter), and tables add
  `$table->syncable()`. A handler extending `Mrj\Foundation\Sync\ModelSyncHandler`, registered
  in `foundation.offline_sync.handlers`, connects each collection to
  `GET api/v1/sync/pull` (cursor-paged changes and tombstones) and `POST api/v1/sync/push`
  (per-operation results: applied, conflict or rejected). The routes exist only once a
  handler is registered. See "Offline sync for mobile apps" in the README.
- The `idempotent` middleware: a write sent with an `Idempotency-Key` header runs once, and
  retries get the stored response. `idempotent:required` makes the header mandatory. The
  keys live in the new `idempotency_keys` table and are removed by `model:prune`.

**Upgrading**

```bash
composer update mrjthedifferent/laravel-foundation
php artisan migrate
```

Nothing else changes until you register a sync handler or use the `idempotent` middleware.
If `model:prune` is not scheduled yet, add `Schedule::command('model:prune')->daily();` so
expired idempotency keys are removed.

## 1.8.0

**Added**
- Passwordless phone sign-in for API clients (Otp module): `POST v1/auth/otp/request` and
  `POST v1/auth/otp/verify` return a Sanctum token. New settings under Settings → OTP:
  `otp_login_enabled` and `otp_self_registration_enabled` (both off by default) and
  `otp_registration_role`. See "Phone sign-in for mobile apps" in the README.
- A notification can define `toSmsLog(object $notifiable): string`. SMS logs then record
  that text instead of the message sent. `SendSmsJob` takes it as an optional third argument.

**Changed**
- One-time codes are stored as an HMAC keyed with `APP_KEY` (`verification_codes.code_hash`)
  instead of in plain text. The plain code is available only on the instance that generated
  it, as `$verificationCode->plainCode`. Rows written by older versions still verify.
- Verification-code SMS are recorded in `sms_logs` with the code masked. The dry-run `log`
  gateway still writes the real text to the `daily_sms` log file, which local development
  reads codes from.
- `SendVerificationCode` takes the code as a constructor argument. Without one it falls back
  to `$notifiable->plainCode`, then `$notifiable->code`.
- The OTP digit-length setting is clamped to 4–10, and the `verification_codes.code` column
  is now nullable and 10 characters wide.
- `QueryBuilder` is generic. A subclass declares `@extends QueryBuilder<YourModel>`, and
  `get()` then returns a collection of that model to static analysis.

**Fixed**
- Works with the latest dependencies again (Laravel 13.35, nwidart/laravel-modules,
  Larastan 3.13). `Tenancy::contextOf()` looks a module up through the repository contract,
  because `Module::has()` is no longer on the facade. Test fixtures store morph aliases,
  since Laravel now enforces the morph map when it reads a morph type.

**Upgrading**

```bash
composer update mrjthedifferent/laravel-foundation
php artisan migrate
```

Codes sent before the update but not yet used still work. Code that read
`$verificationCode->code` after generating a code must read `$verificationCode->plainCode`.
Phone sign-in stays off until it is turned on under Settings → OTP; run "Sync settings"
there to add the new settings to an existing project.

## 1.7.1

**Changed**
- The API token lifetime (`api_token_idle_expiration_minutes`) moved from the general
  settings list to Settings → Security, beside session lifetime. It now overrides
  `sanctum.idle_expiration`, which `apiTokenIdleExpirationMinutes()` reads. In an existing
  project it also stays in the general list until Settings → Security is first saved.

## 1.7.0

**Added**
- Settings → Security replaces Settings → Two-Factor. It adds the password rule
  (minimum length, mixed case, numbers, symbols, breached-password check), failed
  sign-ins before lockout, and session lifetime to the two-factor options. The 1.6 route
  names `admin.settings.special.two_factor` and `update_two_factor` still work.
- Date format, date-time format and default page size are editable under Settings →
  General. Existing projects get them with their current values on "Sync settings".
- A setting can drive any config key: declare `'config' => 'some.config.key'` in a
  module's `config/settings.php`, plus `'seed' => false` if a page creates it. This
  replaces `SettingsConfigApplier::CONFIG_MAP`.
- New config keys `foundation.passwords.*` and `foundation.login.max_attempts`.

**Changed**
- Creating a user, editing a user's password and the forced password change now use the
  password rule (`Password::defaults()`, at least 8 characters by default) instead of
  `min:6`.

**Fixed**
- The maintenance mode setting did nothing. It now closes the panel to everyone but super
  admins, shows the maintenance message, and shows super admins a navbar notice while it
  is on.

## 1.6.0

**Added**
- Settings → Two-Factor: turn two-factor authentication on or off, require it for chosen
  roles and for super admins, and set the issuer name, without editing `.env`. The page
  needs the `Edit Special Setting` permission. Its values override `foundation.two_factor.*`
  once saved; until then, config and `.env` decide as before. Existing projects get the
  page on update, with nothing to migrate.

## 1.5.4

**Fixed**
- `php artisan optimize` failed at `view:cache` in a project with no `resources/views`
  folder (projects created from the skeleton before it kept one). Missing folders are
  now left out of the view paths.

## 1.5.3

**Fixed**
- Impersonating a user who still had to change an administrator-set password sent the
  impersonator to the password page, and they could not do anything else. The password is
  the user's to choose, so `EnsurePasswordIsChanged` now lets impersonated sessions through
  (`ImpersonationContext::isImpersonating()`).

## 1.5.2

**Fixed**
- A fresh install sent no email. The `email_mailer` setting was seeded as `log`, and the
  settings take precedence over `.env`, so every email was written to the log instead. It is
  now seeded empty, which means the mail settings in `.env` are used until someone picks a
  mailer under Settings → Email. Existing installs keep their stored value: if yours is
  still `log` and you did not choose it, clear it.

## 1.5.1

**Fixed**
- Error pages, the sign-in layout, the footer and the two-factor issuer showed
  `config('app.name')`, while the panel showed the `app_name` setting. With tenancy, each
  tenant's pages therefore carried the platform's name. They now use the new `appName()`
  helper: the `app_name` setting, falling back to `config('app.name')`.

## 1.5.0

**Added**
- Two-factor authentication (TOTP), off by default. `FOUNDATION_TWO_FACTOR=true` turns it on.
  - Users turn it on from their profile: they scan a QR code, confirm a code, and receive
    recovery codes. Turning it on or off asks for the password again.
  - Web and social sign-in then show a code challenge. The API `login` takes
    `two_factor_code` or `recovery_code`, and without one answers 401 with
    `errors.two_factor`. A code is accepted only once.
  - `foundation.two_factor.required_roles` and `required_for_super_admins` make it
    mandatory. `EnsureTwoFactorIsEnabled` (appended to the `web` and `api` groups) sends
    such users to set it up before anything else. Override `User::requiresTwoFactor()`
    for a different rule.
  - Anyone who may reset a user's password can also reset their two-factor authentication.
- Users get the columns `two_factor_secret`, `two_factor_recovery_codes` and
  `two_factor_confirmed_at`. Secrets are encrypted, hidden from serialisation and never
  audited. Run `php artisan migrate` (and your tenant migrations).
- New dependencies: `pragmarx/google2fa` and `bacon/bacon-qr-code`.

**Changed**
- `POST confirm-password` is named `password.confirm.store`.

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
