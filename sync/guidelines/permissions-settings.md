## Permissions System

Permissions are defined per module in `config/permissions.php` and auto-seeded by `RolePermissionPermissionsSeeder` which scans all enabled modules.

The `User` model uses Spatie's `HasRoles` trait. Check permissions **only through the Gate**: `$user->can()`, `$user->canAny()`, `$this->authorize()`, `@can`, `@canany`. **Never** `$user->hasPermissionTo()`, `hasAnyPermission()` or `hasRole()` for authorization: those skip the Gate, so a Super Admin (who holds no roles) would be refused.

### Super Admin

A Super Admin is a user with the `is_super_admin` flag, not a role. It passes every *permission* check (`can('Edit Thing')`, `@can('View Role')`) without holding any role or permission. *Policy* checks (`can('delete', $thing)`) still run the policy: its permission checks pass, but its ownership and state rules still apply, so write those rules in the policy, not by checking permissions alone. The flag is set only by `php artisan foundation:super-admin`; it is not mass assignable, and no request, seeder or admin screen can grant it. Use `$user->isSuperAdmin()` to show it, never to authorize: the Gate already does that.

In tests: `User::factory()->superAdmin()->create()`.

### Defining Permissions

Every module must have `Modules/{Name}/config/permissions.php`:

```php
return [
    ['module_name' => 'Thing Management', 'name' => 'View Thing'],
    ['module_name' => 'Thing Management', 'name' => 'Create Thing'],
    ['module_name' => 'Thing Management', 'name' => 'Edit Thing'],
    ['module_name' => 'Thing Management', 'name' => 'Delete Thing'],
    // Non-CRUD operations get their own entries:
    ['module_name' => 'Thing Management', 'name' => 'Export Thing'],
    ['module_name' => 'Thing Management', 'name' => 'Import Thing'],
];
```

- `module_name` — the group label in the permissions UI. Use "X Management" format.
- `name` — globally unique. Use "Verb Noun" format: `View Thing`, `Create Thing`, `Edit Thing`, `Delete Thing`.
- Every CRUD operation and every non-CRUD operation (export, import, password reset, etc.) needs its own entry.
- Never hard-code permission strings outside of `config/permissions.php` and the policy that checks them.

### Seeding Permissions

Run after adding new permissions:
```bash
php artisan db:seed --class="Modules\\RolePermission\\Database\\Seeders\\RolePermissionPermissionsSeeder"
```

In tests, seed permissions in `setUp()`:
```php
\Spatie\Permission\Models\Permission::firstOrCreate(
    ['name' => 'Create Thing'],
    ['module_name' => 'Thing Management', 'guard_name' => 'web']
);
```

### Checking Permissions

Always via policy in controllers:
```php
$this->authorize('create', Thing::class);
```

Direct check only in non-controller contexts (jobs, console):
```php
if (! $user->can('Export Thing')) {
    throw new AuthorizationException;
}
```

### Blade Authorization Directives

`@can`, `@cannot`, and `@canany` go through the Gate (Spatie registers each permission there):

```blade
{{-- Single permission --}}
@can('Create Thing')
    <a href="{{ route('admin.things.create') }}" class="btn btn-primary">Add Thing</a>
@endcan

{{-- Any of several permissions --}}
@canany(['Edit Thing', 'Delete Thing'])
    <x-dropdown-menu> ... </x-dropdown-menu>
@endcanany
```

Sidebar visibility is not written in Blade: list the permissions on the item in the module's `config/menu.php` and the sidebar shows it to users holding any of them.

- Use `@can` / `@canany` for individual UI elements (buttons, links, menu items).

---

## Settings System

Application settings are stored in the `settings` database table, managed by `Modules/Settings`. Cache key: `app_settings` — automatically invalidated on every save/delete.

### Declaring Module Settings

Every module must have `Modules/{Name}/config/settings.php`. Return an empty array if the module owns no global settings:
```php
return []; // most modules return this
```

### Accessing Settings

`SettingsServiceProvider` loads all settings into the config system at boot (from the `app_settings` cache), keyed as:
```php
config('settings.{key}') // returns ['group' => ..., 'type' => ..., 'value' => ..., 'description' => ...]
```

**Prefer `config()` — it is already in memory with no DB hit:**
```php
config('settings.app_name.value')         // preferred
config('settings.thing_max_count.value')  // preferred
```

`getSystemSetting($key)` queries the DB directly on every call — avoid it:
```php
getSystemSetting('app_name') // avoid — hits DB every time
```

- Never query the `Setting` model directly in application code.
- Never use `env()` for settings that are user-configurable at runtime.

### Settings That Override Config

A setting can drive a config key: declare `'config' => 'some.config.key'` and the stored value is copied onto it at boot (`SettingsConfigApplier`). Code keeps reading `config('some.config.key')`, and the key keeps its default in the config file, so it still works without the Settings module.

```php
'date_format' => [
    'group' => 'General',
    'config' => 'foundation.formats.date',   // no 'value': seeded from the current config/.env value
    'type' => 'text',
    'description' => 'How dates are shown, in PHP date() format',
],
'password_min_length' => [
    'group' => 'Security',
    'config' => 'foundation.passwords.min_length',
    'seed' => false,          // created by its page on first save; until then config/.env decides
    'type' => 'integer',
    'is_visible' => false,
],
```

- A stored `null` is not applied.
- Use this for runtime policy an administrator may change on a live site. Keep structural config (route prefix, guards, role names, cache prefix, storage disk, tenancy) in config/`.env`: it is needed before the database, or changing it breaks URLs or data.

### Setting Types

| Type | Storage | Cast |
|---|---|---|
| `string` | raw string | string |
| `integer` | raw string | `(int)` |
| `float` | raw string | `(float)` |
| `boolean` | `0`/`1` | `(bool)` |
| `array` / `multi-select` | comma-separated | array via `explode(',', ...)` |
| `json` | JSON string | decoded array |
| `image` | path | URL via `FileManagerService::getImage()` |
| `file` | path | URL via `FileManagerService::getFile()` |
| `select` | raw string | string, with options JSON |

### Settings with Dedicated Pages

Settings that need custom UI use `is_visible => false` and are managed by dedicated pages. Two patterns:

1. **Centralized (Settings module)** — OAuth, payment keys, SMS/email gateways, theme, security (two-factor, passwords, sign-in), etc. live under `SpecialSettingsController` / `ThemeSettingsController` / `SecuritySettingsController` in the Settings module.
2. **Module-owned** — When a module has settings that belong to its domain (e.g. Error Report), create a settings page inside that module, add a permission (e.g. `Edit Error Report Settings`), register a gate, and add a link in the module's sidebar menu. Keep those settings `is_visible => false` so they never appear in the generic settings page.

### Privacy Policy and Terms (public pages)

Admins write both texts under Settings → Privacy Policy / Terms & Conditions (settings `privacy_policy`, `terms_conditions`).

- **Public pages:** they are served without login at `/privacy-policy` and `/terms-conditions` (routes `legal.privacy_policy`, `legal.terms_conditions`).
  - Use these URLs in app-store listings, sign-up forms and footers.
  - Don't build another page or content type for them.
- **Mobile apps** get the same URLs from `GET /api/v1/settings/app` (`privacy_policy_url`, `terms_conditions_url`).
- **The HTML is cleaned before it's shown** (`Modules\Settings\Support\LegalHtml`): formatting tags only, no scripts, styles or event handlers, and only http(s), mailto, tel or relative links.
- **No text yet:** a page that has none is a 404.
- **To serve your own pages instead,** set `foundation.routing.legal_pages` to `false` (`FOUNDATION_LEGAL_PAGES=false`).

### Account deletion

Deleting an account never erases other people's records. `Modules\User\Services\AccountDeletion` runs every path (app, web page, admin):
1. **Request:** refused while a blocker applies. Otherwise the person is signed out everywhere (API tokens and database sessions) and `AccountDeletionRequested` fires.
2. **Review:** with the Security setting "Automatic account deletion" off (`foundation.account_deletion.automatic`), the request waits under Administration → Deletion requests. Staff with `Review Account Deletion` approve or reject it, with a reason that is sent to the person.
3. **Grace period:** `foundation.account_deletion.grace_days` (default 30; also on the Security page). Signing in again in any way cancels the request (`AccountDeletionCancelled`).
4. **Anonymize:** the daily `accounts:purge-deleted` command re-checks blockers, then fires `AccountDeleting` and anonymizes the account.
   - The user row stays, so payments, orders and logs keep their foreign keys.
   - Name becomes "Deleted user". Phone, email, photo, password, 2FA, roles, tokens, devices, documents and the audits of the profile are removed, and `users.anonymized_at` is set. The phone and email are free to sign up again.
   - Login history and audits of anonymized accounts are dropped after `security_log_days` (365).

**What a project adds:**
- **Blockers:** `Foundation::accountDeletionBlocker(fn (User $user) => $hasOpenOrders ? __('…what to do…') : null)` in a service provider's `boot()`. Super Admins are always blocked.
- **Its own data:** listen to `AccountDeleting` to delete what only the person used and to clear their details from shared records. Listen to `AccountDeletionRequested` to hide their public content at once.
- **Safe foreign keys:** use `restrictOnDelete()` on records shared with other people (orders, reviews), so a stray hard delete fails instead of erasing them.
- `Anonymize Account` lets staff delete an account at once from Deletion requests (password confirmed). The API's `manage-account {action: delete}` on another user, and the Users page's account delete, anonymize too.

**API:** `GET v1/account/deletion` returns `{status, scheduled_for, requested_at, blockers[], needs_review, grace_days}`. `POST v1/account/deletion {password}` returns 201 with `status` and `scheduled_for`, or 422 with `errors.blockers`. `POST v1/manage-account {action: delete}` on yourself does the same.

### Account deletion page (public)

`/delete-account` (route `account.delete`) lets anyone ask for their account to be deleted without the app. App stores (Google Play) require this web link next to in-app deletion.
- **Who it lets in:** the person signs in on the page with email or phone and password, plus their two-factor code if they use one. It's throttled like sign-in.
- **What it does:** makes the same request as the app (see above). It shows the blockers, "sent for review" or the date of deletion.
- **Describe your own data:** override `account_deletion.items` and `account_deletion.kept_items` (one item per line, plus any other line) in `lang/vendor/user/{locale}/user.php`.
- **Mobile apps** get the URL from `GET /api/v1/settings/app` (`account_deletion_url`).
- **To serve your own page,** set `foundation.routing.account_deletion_page` to `false` (`FOUNDATION_ACCOUNT_DELETION_PAGE=false`).

Public pages (legal, account deletion) extend `layouts.public`: the app name, one card, a footer, themed, with no login and no Vite. Use `@section('title')` and `@section('content')`, and the `fd-prose` class for long text.
