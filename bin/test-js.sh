#!/usr/bin/env bash
#
# Runs the JavaScript tests for ui/public/assets/js/foundation.js with Node's built-in test
# runner and jsdom (installed into the git-ignored .build directory, like the Tailwind CLI).

set -euo pipefail

cd "$(dirname "$0")/.."
B=.build

mkdir -p "$B"
[ -f "$B/package.json" ] || echo '{ "name": "foundation-assets", "private": true, "version": "1.0.0" }' > "$B/package.json"
(cd "$B" && npm install --no-audit --no-fund --ignore-scripts jsdom@26 >/dev/null)

FOUNDATION_BUILD="$PWD/$B" node --test tests/js/*.test.mjs
