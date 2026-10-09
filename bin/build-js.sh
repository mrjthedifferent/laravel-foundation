#!/usr/bin/env bash
#
# Minifies ui/resources/js/foundation.src.js (the readable source) to ui/public/assets/js/foundation.js,
# which is what projects load. The output is committed so projects need no build step.
# Pass --check to fail if the committed file is out of date (used by CI).

set -euo pipefail

cd "$(dirname "$0")/.."
B=.build
IN=ui/resources/js/foundation.src.js
OUT=ui/public/assets/js/foundation.js

mkdir -p "$B"
[ -f "$B/package.json" ] || echo '{ "name": "foundation-assets", "private": true, "version": "1.0.0" }' > "$B/package.json"
(cd "$B" && npm install --no-audit --no-fund --ignore-scripts esbuild@0.25.10 >/dev/null)

TARGET="$OUT"
[ "${1:-}" = "--check" ] && TARGET="$B/foundation.check.js"

# Browsers from the last few years; /*! comments (the licence header) are kept.
"$B/node_modules/.bin/esbuild" "$IN" --minify --target=es2019 --legal-comments=inline --log-level=warning --outfile="$TARGET"

if [ "${1:-}" = "--check" ]; then
    cmp -s "$TARGET" "$OUT" || { echo "$OUT is out of date. Run bin/build-js.sh." >&2; exit 1; }
    echo "$OUT is up to date."
else
    echo "Built $OUT ($(wc -c < "$OUT") bytes from $(wc -c < "$IN"))"
fi
