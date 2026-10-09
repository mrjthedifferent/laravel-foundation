#!/usr/bin/env bash
#
# Compiles ui/resources/css/tailwind.css to ui/public/assets/css/foundation.css with the
# Tailwind CLI, at the version pinned below. The output is committed so host apps need
# no build step. Run it after changing any Blade view or the Tailwind source.
# Pass --check to fail if the committed file is out of date (used by CI).

set -euo pipefail

cd "$(dirname "$0")/.."
B=.build
OUT=ui/public/assets/css/foundation.css

mkdir -p "$B"
[ -f "$B/package.json" ] || echo '{ "name": "foundation-assets", "private": true, "version": "1.0.0" }' > "$B/package.json"
(cd "$B" && npm install --no-audit --no-fund --ignore-scripts tailwindcss@4.3.3 @tailwindcss/cli@4.3.3)

# Tailwind resolves imports from the entry file's folder, so the entry lives in .build
# next to node_modules and pulls in the package stylesheet from there.
cat > "$B/tailwind.entry.css" <<'CSS'
@layer theme, base, components, utilities;
@import "tailwindcss/theme.css" layer(theme);
@import "tailwindcss/preflight.css" layer(base);
@import "tailwindcss/utilities.css" layer(utilities);
@source "../ui/resources/views";
@source "../modules/*/resources/views";
@source "../modules/*/resources/assets/js";
@source "../modules/*/app";
@source "../ui/resources/js";
@source "../ui/public/assets/js/foundation.js";
@source "../src";
@source "../lang";
@source "../modules/*/lang";
@import "../ui/resources/css/tailwind.css";
CSS

TARGET="$OUT"
[ "${1:-}" = "--check" ] && TARGET="$B/tailwind.check.css"

"$B/node_modules/.bin/tailwindcss" --input "$B/tailwind.entry.css" --output "$TARGET" --minify

if [ "${1:-}" = "--check" ]; then
    cmp -s "$TARGET" "$OUT" || { echo "$OUT is out of date. Run bin/build-css.sh." >&2; exit 1; }
    echo "$OUT is up to date."
else
    echo "Built $OUT"
fi
