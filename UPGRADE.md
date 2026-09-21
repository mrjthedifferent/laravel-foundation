# Upgrade notes

Manual steps a project must take when moving between versions. Versions without an entry
need only `composer update mrjthedifferent/laravel-foundation` and `php artisan migrate`.

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
