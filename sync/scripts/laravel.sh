#!/usr/bin/env bash
#
# Server-side deploy steps. Run from the project root after the code is updated:
#   BUILD_FRONTEND=1 bash .scripts/laravel.sh
#
# Managed by mrjthedifferent/laravel-foundation (php artisan foundation:sync), which overwrites
# local edits. To keep your own version, list '.scripts/laravel.sh' under `sync.except` in
# config/foundation.php.

set -euo pipefail

PHP_BIN="${PHP_BIN:-php}"
COMPOSER_BIN="${COMPOSER_BIN:-composer}"

echo "==> Maintenance mode on"
"$PHP_BIN" artisan down --retry=15 || true

# Always bring the site back up, even when a step below fails.
trap '"$PHP_BIN" artisan up || true' EXIT

echo "==> Composer install"
"$COMPOSER_BIN" install --no-dev --no-interaction --prefer-dist --optimize-autoloader

# Composer scripts are skipped under --no-scripts and some CI setups, so run it explicitly.
echo "==> Publish foundation theme assets"
"$PHP_BIN" artisan foundation:publish

echo "==> Migrate"
"$PHP_BIN" artisan migrate --force

if [ "${BUILD_FRONTEND:-0}" = "1" ]; then
    echo "==> Build frontend"
    npm ci
    npm run build
fi

echo "==> Rebuild caches"
"$PHP_BIN" artisan optimize:clear
"$PHP_BIN" artisan config:cache
"$PHP_BIN" artisan route:cache
"$PHP_BIN" artisan view:cache
"$PHP_BIN" artisan event:cache

"$PHP_BIN" artisan storage:link || true

echo "==> Restart queue workers"
"$PHP_BIN" artisan queue:restart

echo "==> Done"
