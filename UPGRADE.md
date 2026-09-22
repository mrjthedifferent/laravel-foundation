# Upgrade notes

Manual steps a project must take when moving between versions. Versions without an entry
need only `composer update mrjthedifferent/laravel-foundation` and `php artisan migrate`.

## 0.19 to 1.0

`composer update mrjthedifferent/laravel-foundation -W`. No migration.

1. **Run `php artisan cache:clear` once after updating.** The dashboard's activity widget
   used to cache Eloquent models; a copy cached by 0.19 can still crash the dashboard
   until it expires.
2. **The package now defines `admin.dashboard` itself.** Remove your own
   `Route::view('/dashboard', 'dashboard')->name('dashboard')` from `routes/web.php`, or,
   to keep a route of your own, set `'dashboard' => false` under `routing` in a published
   `config/foundation.php`.
3. **`config/sidebar.php` is optional now**; the package ships the same default. Keep
   yours if you changed it (a published file replaces the default list as a whole).
4. **`Modules\User\Data\UserData::$roles` and `$is_active` default to `null`**, meaning
   "leave unchanged" on update. If your own code relied on building `UserData` without
   `roles` to strip a user's roles, pass `roles: []` explicitly. Creating a user without
   `is_active` still creates an active user.
5. **These classes are now `final`**: the four `foundation:*` commands,
   `FoundationServiceProvider`, the five middleware in `Http\Middleware`, `Rules\EmailOrPhone`,
   `Rules\PhoneNumber`, `FileManagerService`, `LocalFileStorage`, `PDFService`, the view
   components and `ThemeComposer`. If you extended one, bind your own implementation of the
   matching contract (`Contracts\FileStorage` for storage) or wrap it instead.
6. Optional, for the installer's Vite setup: add `preserveSymlinks: true` under `resolve` in
   `vite.config.js` if you develop against a symlinked checkout of the package.

## 0.18 to 0.19 (translations)

`composer update mrjthedifferent/laravel-foundation`. No migration. Under the
default `en` locale every visible string is unchanged.

1. **If you overrode a foundation view** (a copy under
   `resources/views/modules/{alias}/…` or `resources/views/…`), it keeps its
   literal English and keeps working — but it won't follow a locale change.
   Compare it with the package's new version and switch its text to the
   `__()` keys if you want it translated.
2. **If your own code reads flash messages or JSON `message` fields and
   compares them to English text**, it still matches under `en`, but will not
   once `APP_LOCALE` changes. Compare against the translation
   (`__('user::user.flash.created')`) instead, or better, the status code.
3. **`StatusBadge`**: if you subclassed `Mrj\Foundation\View\Components\StatusBadge`
   and relied on its `'Active'`/`'Inactive'` constructor defaults, those
   defaults are now `null` and resolved to the translated labels.
4. **A module generated with `foundation:make-module` on 0.14–0.18** has a
   controller importing the deleted `Mrj\Foundation\Enum\PaginationEnum` and
   crashes on its index page. Replace `PaginationEnum::DEFAULT_PAGINATE` with
   `perPage()` and remove the import; see `stubs/module/` for the current
   routes and layout.

To run the admin panel in another language, set `APP_LOCALE`, publish the
shared strings (`php artisan vendor:publish --tag=foundation-lang`) and
translate the copy under `lang/vendor/foundation/{locale}/`, add
`resources/lang/modules/{alias}/{locale}/{alias}.php` per module, and put
config/database labels (menu items, permission names, setting groups) in
`lang/{locale}.json` keyed by their English text.

## 0.17 to 0.18 (drop konekt/html)

`composer update mrjthedifferent/laravel-foundation`. No migration.

1. **`konekt/html` is no longer a dependency.** If your own project's views
   (outside this package's own 29, which are already converted) call
   `Form::` or `Html::`, they will break — those facades are no longer
   registered by this package. Either require `konekt/html` directly in
   your own `composer.json` and register `Collective\Html\HtmlServiceProvider`
   yourself, or migrate those views to `<x-form.input>` / `<x-form.select>`
   / `<x-form.textarea>` / `<x-form.file>` (see `ui-components.md`).
2. **`enum_value()` is deleted.** It existed only to work around
   `Form::model()` reading an enum-cast attribute directly and failing to
   cast it to a string. If your own code called it, replace
   `enum_value($model->field)` with `$model->field` directly — everywhere
   in this package it fed a `<x-form.select>`'s `:selected` prop, which
   unwraps a `BackedEnum`/`UnitEnum` itself now.
3. If your own views extended or copied one of this package's 29
   converted views, re-copy the new version — the `Form::` calls in your
   copy will not resolve to anything once `konekt/html` is gone.

## 0.16 to 0.17 (architecture, part three)

`composer update mrjthedifferent/laravel-foundation`. No migration.

1. **In production, an unhandled server error on a web request now flashes
   "Something went wrong. Please try again." and redirects back**, instead
   of a raw 500 page — the same message every admin controller's try/catch
   used to flash individually. If your own project's exception handler
   extends `Mrj\Foundation\Exceptions\Handler` and overrides `render()`,
   check it still calls `parent::render()` for the case it doesn't handle
   itself. Every other environment (including `testing`) is unaffected —
   the real error still shows, exactly as before.
2. **Deleting a `UserDocument` by an ID that exists but belongs to a
   different user now 404s**, rather than flashing "Failed to delete
   document" and leaving the record untouched. The record is still
   untouched either way; only the response differs.
3. If your own code referenced `Modules\User\Data\UserData`'s `#[Email]`/
   `#[Min(6)]` attributes for anything (they never actually validated —
   see CHANGELOG), no action needed; they're simply gone now.

## 0.15 to 0.16 (architecture, part two)

`composer update mrjthedifferent/laravel-foundation`. No migration.

1. **If your own code reads `$user->toArray()['image']`,
   `$document->toArray()['file_path']` or `['back_file_path']`, or calls
   `toJson()` on either model directly**, the value changes from the raw
   stored path to the full URL — matching what `$user->image` (property
   access) already returned. Every Resource class in this package reads
   the property form already and needs no change; this only affects code
   that serializes one of these two models without going through a
   Resource.
2. **If your own code called `app('error_reporter')` directly** (rather
   than through `Foundation::exceptions()`, which already handled this),
   use `app(\Mrj\Foundation\Contracts\ErrorReporter::class)` instead — it
   is always bound now, so an `app()->bound()` check is no longer needed.
3. If you extended `Mrj\Foundation\Services\FileManagerService` expecting
   its methods' internals, they're now one-line delegations to whatever
   `\Mrj\Foundation\Contracts\FileStorage` is bound
   (`Mrj\Foundation\Services\LocalFileStorage` by default) — the same
   static call sites work unchanged, but a subclass overriding its
   private helpers no longer has anything to override.

## 0.14 to 0.15 (architecture, part one)

`composer update mrjthedifferent/laravel-foundation`. No migration.

1. **Exports now time out after 30 minutes** (`ExportJob::$timeout = 1800`),
   instead of running unbounded. If you export an unusually large dataset
   that legitimately needs longer, extend `Mrj\Foundation\Support\ExportJob`'s
   `$timeout` in your own subclass, or raise your queue worker's own timeout
   ceiling accordingly.
2. **If you extended a per-module `RouteServiceProvider` or
   `EventServiceProvider`**, those classes are gone — routes now load by
   convention from `ModuleServiceProvider::loadRoutes()`, and event listeners
   go in a `protected array $listen` property on your module's main service
   provider instead. Nothing to do if you never touched these.
3. **If your own code called `Modules\ActivityLog\Models\Device`,
   `Modules\Notification\Models\FirebaseToken`,
   `Modules\User\Models\UserDocument` or `Modules\User\Models\UserLoginHistory`
   relations directly off `App\Models\User`** (`$user->devices()`, etc.), no
   change needed — they still resolve, now via `resolveRelationUsing()`
   rather than a method defined on the base class.
4. **BackupCleanup's scheduled cleanup commands now actually run.** They were
   defined but never wired into Laravel's scheduler by any previous version,
   so `system:backup:cleanup` and `clear:old-notification` have never
   executed in any installation. If you don't want them running on their
   default schedule (`02:00` and `12:10` daily), override them in your own
   `routes/console.php` or disable the BackupCleanup module.

## 0.13 to 0.14 (configurability)

`composer update mrjthedifferent/laravel-foundation`. No migration.

Nothing changes for a project that hasn't touched the values below — every
new config key defaults to the previous hard-coded behavior.

1. **`SettingsServiceProvider::CACHE_KEY`** (a public constant) is now
   **`SettingsServiceProvider::cacheKey()`** (a method, since it reads
   `foundation.cache.prefix`). If your project referenced the constant
   directly, call the method instead.
2. **`config('foundation.user_model')` is gone.** Nothing in the package ever
   actually used a value other than `App\Models\User` (95 files import it
   directly), so removing it changes nothing unless your own code read that
   key — if so, replace it with the `App\Models\User::class` you already have.
3. **`Mrj\Foundation\Enum\PaginationEnum`** is deleted (`DEFAULT_PAGINATE` and
   `DEFAULT_LIST` were both `10`). Replace any reference with
   `config('foundation.pagination.default')`.
4. If you want the admin panel under a different URL prefix, a different
   domain, or with role names other than Super Admin/Admin/User, publish
   `config/foundation.php` (`php artisan vendor:publish --tag=foundation-config`)
   and set `routing`/`roles` there — do this **before** seeding, since role
   names are only created once. Route names are unaffected either way.

## 0.12 to 0.13 (tooling gate)

`composer update mrjthedifferent/laravel-foundation`. No migration.

1. **Nine unused global helper functions were removed**: `ajaxResponse()`,
   `getCommonStatus()`, `getIntegerMonth()`, `getLast11Digit()`, `engToBangla()`,
   `currency_number()`, `isImage()`, `isUrl()`, `isIndexedArray()`. None had a
   caller anywhere in this package; if your own project code called one of
   these directly, inline its (short) implementation from the 0.12.1 source.
2. If your project's error-report Slack channel was silently not sending
   (it would have thrown a `Class not found` error), it now works — no action
   needed, just confirm the webhook is still set correctly.

## 0.11 to 0.12 (security fixes)

`composer update mrjthedifferent/laravel-foundation` then `php artisan migrate`.

1. **Set `SEED_ADMIN_PASSWORD` before seeding a non-local environment.** The seeder now refuses
   to run outside `local`/`testing` if it is unset, rather than using the well-known default
   password. Local development and CI are unaffected.
2. **The Super Admin (and anyone whose password an admin resets) must change their password on
   next sign-in.** This is enforced everywhere, including the API — a client that doesn't
   already handle a 403 with `"You must set a new password before continuing."` should add
   that handling if it ever authenticates as a freshly seeded or reset account.
3. **If you read `Setting::value` for `google_client_secret`, `github_client_secret`,
   `apple_client_secret`, `firebase_credentials_json`, `error_report_slack_webhook` or
   `error_report_telegram_bot_token` directly from the database column**, it is now ciphertext;
   read it through the model (`$setting->value`), which decrypts automatically.
4. **If you built the API's profile-update request with an image that isn't
   `jpeg/png/jpg/gif/svg/webp` under 2MB**, it will now be rejected — matching what admin-side
   user forms already enforced.
5. If your app enables `Model::preventLazyLoading()` differently (e.g. always, or never), the
   foundation now calls it too (`! app()->isProduction()`); the last call wins, so set it in
   your own provider's `boot()` *after* the foundation's if you need to override it.

## 0.10 to 0.11 (Laravel 13)

Upgrade your application to Laravel 13 first; follow
[Laravel's upgrade guide](https://laravel.com/docs/13.x/upgrade). Your app needs PHP `^8.3`
and `laravel/framework ^13.0`, and `laravel/tinker` must be `^3.0`.

Then `composer update mrjthedifferent/laravel-foundation -W`.

Worth knowing:

1. **Pagination markup changed.** It previously rendered Bootstrap 3 views by mistake; it now
   renders Bootstrap 5. If you styled around the old markup, check your paginated pages.
2. **Cached settings are arrays.** If you read the `app_settings` cache key directly, it now
   holds `list<array{key, value, group, type, description}>` instead of a collection of
   `Setting` models. Use `SettingsServiceProvider::cached()`.
3. **`ValidateCsrfToken` is `PreventRequestForgery`.** Laravel keeps the old name as a
   deprecated alias, so existing `withoutMiddleware()` calls still work; rename them anyway.
4. If you publish `config/cache.php`, Laravel 13's default is `'serializable_classes' => false`.
   The foundation no longer caches Eloquent objects, so it needs no allow-list.

## 0.9 to 0.10

Run `php artisan migrate`: it adds `phone` and `phone_verified_at` to `users`. If your project
overrode `scopeWherePhone()` on `App\Models\User` because it keeps phone numbers in its own
table, keep the override. Otherwise remove it, as the default now reads the new column.
