# Upgrade notes

Manual steps a project must take when moving between versions. Versions without an entry
need only `composer update mrjthedifferent/laravel-foundation` and `php artisan migrate`.

## 0.9 to 0.10

Run `php artisan migrate`: it adds `phone` and `phone_verified_at` to `users`. If your project
overrode `scopeWherePhone()` on `App\Models\User` because it keeps phone numbers in its own
table, keep the override. Otherwise remove it, as the default now reads the new column.
