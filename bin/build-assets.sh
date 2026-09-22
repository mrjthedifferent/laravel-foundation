#!/usr/bin/env bash
#
# Rebuilds the third-party files under ui/public/assets from npm, at the versions
# pinned below. Run it to upgrade a library, then update THIRD-PARTY-NOTICES.md.
# foundation.css, foundation.js and the images are hand-written and left alone.

set -euo pipefail

cd "$(dirname "$0")/.."
A=ui/public/assets
B=.build
N=$B/node_modules

mkdir -p "$B"
echo '{ "name": "foundation-assets", "private": true, "version": "1.0.0" }' > "$B/package.json"
(cd "$B" && npm install --no-audit --no-fund --ignore-scripts \
    bootstrap@5.3 jquery@3 select2@4.0.13 sweetalert2@11 quill@2 ace-builds@1 \
    phosphor-icons@1.4.2 inter-ui@4)

rm -rf "$A/vendor" "$A/icons" "$A/fonts"
mkdir -p "$A/css" "$A/js" "$A"/vendor/{select2,sweetalert2,ace,quill} \
    "$A/icons/phosphor/fonts" "$A/fonts/inter"

cp "$N"/bootstrap/dist/css/bootstrap.min.css "$N"/bootstrap/dist/css/bootstrap.rtl.min.css "$A/css/"
cp "$N"/bootstrap/dist/js/bootstrap.bundle.min.js "$N"/jquery/dist/jquery.min.js "$A/js/"
cp "$N"/select2/dist/js/select2.min.js "$N"/select2/dist/css/select2.min.css "$A/vendor/select2/"
cp "$N"/sweetalert2/dist/sweetalert2.all.min.js "$A/vendor/sweetalert2/"
cp "$N"/quill/dist/quill.js "$N"/quill/dist/quill.snow.css "$A/vendor/quill/"
for f in ace.js mode-json.js theme-xcode.js theme-monokai.js worker-json.js ext-searchbox.js; do
    cp "$N/ace-builds/src-min-noconflict/$f" "$A/vendor/ace/"
done
cp "$N"/phosphor-icons/src/fonts/Phosphor.woff2 "$N"/phosphor-icons/src/fonts/Phosphor.woff "$A/icons/phosphor/fonts/"

for w in Regular Medium SemiBold Bold; do cp "$N/inter-ui/web/Inter-$w.woff2" "$A/fonts/inter/"; done
cat > "$A/fonts/inter/inter.css" <<'CSS'
/*! Inter 4 | SIL Open Font License 1.1 | https://rsms.me/inter */
@font-face{font-family:"Inter";font-style:normal;font-weight:400;font-display:swap;src:url("Inter-Regular.woff2") format("woff2")}
@font-face{font-family:"Inter";font-style:normal;font-weight:500;font-display:swap;src:url("Inter-Medium.woff2") format("woff2")}
@font-face{font-family:"Inter";font-style:normal;font-weight:600;font-display:swap;src:url("Inter-SemiBold.woff2") format("woff2")}
@font-face{font-family:"Inter";font-style:normal;font-weight:700;font-display:swap;src:url("Inter-Bold.woff2") format("woff2")}
CSS

# The upstream Phosphor stylesheet inlines the whole font as base64 (3.9 MB).
# Keep its rules, load the font from a file instead.
node - <<'JS'
const fs = require('fs');
const src = fs.readFileSync('.build/node_modules/phosphor-icons/src/css/icons.css', 'utf8');
let body = src.slice(src.indexOf('[class^="ph-"]'));
body = body.replace(/\/\*[\s\S]*?\*\//g, '').replace(/\s+/g, ' ').replace(/\s*([{};:,])\s*/g, '$1').replace(/;}/g, '}');
const head = '/*! Phosphor Icons 1.4.2 | MIT License | https://phosphoricons.com */\n'
    + '@font-face{font-family:"Phosphor";src:url("fonts/Phosphor.woff2") format("woff2"),url("fonts/Phosphor.woff") format("woff");font-weight:normal;font-style:normal;font-display:block}\n';
fs.writeFileSync('ui/public/assets/icons/phosphor/phosphor.css', head + body.trim() + '\n');
JS

echo "Assets rebuilt in $A"
