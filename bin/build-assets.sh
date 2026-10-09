#!/usr/bin/env bash
#
# Rebuilds the third-party files under ui/public/assets from npm, at the versions
# pinned below. Run it to upgrade a library, then update THIRD-PARTY-NOTICES.md.
# The images are hand-written and left alone. foundation.css is compiled by bin/build-css.sh (Tailwind) and
# foundation.js is minified by bin/build-js.sh from ui/resources/js/foundation.src.js; neither is copied from here.

set -euo pipefail

cd "$(dirname "$0")/.."
A=ui/public/assets
B=.build
N=$B/node_modules

mkdir -p "$B"
echo '{ "name": "foundation-assets", "private": true, "version": "1.0.0" }' > "$B/package.json"
(cd "$B" && npm install --no-audit --no-fund --ignore-scripts \
    jquery@4.0.0 select2@4.1.0 sweetalert2@11.26.25 quill@2.0.3 ace-builds@1.44.0 \
    @phosphor-icons/web@2.1.2 inter-ui@4.1.1)

rm -rf "$A/vendor" "$A/icons" "$A/fonts"
mkdir -p "$A/css" "$A/js" "$A"/vendor/{select2,sweetalert2,ace,quill} \
    "$A/icons/phosphor/fonts" "$A/fonts/inter"

cp "$N"/jquery/dist/jquery.min.js "$A/js/"
cp "$N"/select2/dist/js/select2.min.js "$N"/select2/dist/css/select2.min.css "$A/vendor/select2/"
cp "$N"/sweetalert2/dist/sweetalert2.all.min.js "$A/vendor/sweetalert2/"
cp "$N"/quill/dist/quill.js "$N"/quill/dist/quill.snow.css "$A/vendor/quill/"
for f in ace.js mode-json.js theme-xcode.js theme-monokai.js worker-json.js ext-searchbox.js; do
    cp "$N/ace-builds/src-min-noconflict/$f" "$A/vendor/ace/"
done
for w in regular thin light bold fill duotone; do
    cp "$N"/@phosphor-icons/web/src/$w/*.woff2 "$N"/@phosphor-icons/web/src/$w/*.woff "$A/icons/phosphor/fonts/"
done

for w in Regular Medium SemiBold Bold; do cp "$N/inter-ui/web/Inter-$w.woff2" "$A/fonts/inter/"; done
cat > "$A/fonts/inter/inter.css" <<'CSS'
/*! Inter 4 | SIL Open Font License 1.1 | https://rsms.me/inter */
@font-face{font-family:"Inter";font-style:normal;font-weight:400;font-display:swap;src:url("Inter-Regular.woff2") format("woff2")}
@font-face{font-family:"Inter";font-style:normal;font-weight:500;font-display:swap;src:url("Inter-Medium.woff2") format("woff2")}
@font-face{font-family:"Inter";font-style:normal;font-weight:600;font-display:swap;src:url("Inter-SemiBold.woff2") format("woff2")}
@font-face{font-family:"Inter";font-style:normal;font-weight:700;font-display:swap;src:url("Inter-Bold.woff2") format("woff2")}
CSS

# Phosphor 2 has one font and stylesheet per weight (`ph`, `ph-bold`, `ph-fill`…). The layout loads
# phosphor.css: regular, bold and fill. Light, thin and duotone are separate files a page adds when it
# uses them. Each font is only downloaded once an icon in that weight is on screen.
node - <<'JS'
const fs = require('fs');
const src = '.build/node_modules/@phosphor-icons/web/src';
const out = 'ui/public/assets/icons/phosphor';
const head = '/*! Phosphor Icons 2.1.2 | MIT License | https://phosphoricons.com */\n';
const weight = (w) => {
    let css = fs.readFileSync(`${src}/${w}/style.css`, 'utf8');
    // Load the font from the shared fonts/ folder, woff2 and woff only. The version query keeps a
    // browser from reusing a cached font of another version: Phosphor 1 used the same file name.
    css = css.replace(/src:[^;]*?url\("\.\/([^"]+)\.woff2"\)[^;]*;/, (_, file) =>
        `src:url("fonts/${file}.woff2?v=2.1.2") format("woff2"),url("fonts/${file}.woff?v=2.1.2") format("woff");`);
    return css.replace(/\/\*[\s\S]*?\*\//g, '').replace(/\s+/g, ' ').replace(/\s*([{};:,>])\s*/g, '$1').replace(/;}/g, '}').trim();
};
fs.writeFileSync(`${out}/phosphor.css`, head + ['regular', 'bold', 'fill'].map(weight).join('\n') + '\n');
for (const w of ['light', 'thin', 'duotone']) {
    fs.writeFileSync(`${out}/phosphor-${w}.css`, head + weight(w) + '\n');
}
JS

echo "Assets rebuilt in $A"
