# Upgrade notes

Manual steps a project must take when moving between versions. Versions without an entry
need only `composer update mrjthedifferent/laravel-foundation` and `php artisan migrate`.

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
