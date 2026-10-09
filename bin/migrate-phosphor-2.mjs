#!/usr/bin/env node
/**
 * Moves a project's icons from Phosphor 1.x to Phosphor 2.x (foundation 3.0).
 *
 * Phosphor 2 needs a weight class next to the icon class:
 *   ph-house       → ph ph-house          (regular)
 *   ph-star-fill   → ph-fill ph-star      (1.x weight suffix → 2.x weight class)
 *
 * It rewrites icon names wherever they appear: class attributes, `icon="…"` props, `'icon' => '…'` in PHP
 * config and arrays, JavaScript strings and Markdown code. Only real icon names are touched (read from the
 * package's phosphor.css), so helpers such as `ph-lg` or `ph-spin` and words like "graph-paper" stay as
 * they are. An icon that already has a weight class next to it is left alone, so running it twice is safe.
 *
 *   node vendor/mrjthedifferent/laravel-foundation/bin/migrate-phosphor-2.mjs Modules resources config app
 *   node vendor/mrjthedifferent/laravel-foundation/bin/migrate-phosphor-2.mjs Modules --write
 *
 * Without --write it lists what it would change. Review the diff afterwards; CSS files are not touched.
 */
import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const here = path.dirname(fileURLToPath(import.meta.url));
const iconCss = path.join(here, '..', 'ui', 'public', 'assets', 'icons', 'phosphor', 'phosphor.css');

const WEIGHTS = ['thin', 'light', 'bold', 'fill', 'duotone'];
const SKIP_DIRS = new Set(['node_modules', 'vendor', '.git', 'storage', 'build', '.build', 'public']);
const EXTENSIONS = ['.php', '.js', '.mjs', '.ts', '.vue', '.html', '.md', '.json'];

/** Every icon name in the shipped stylesheet, e.g. "house", "arrow-left". */
export function iconNames(css = fs.readFileSync(iconCss, 'utf8')) {
    const names = new Set();
    for (const m of css.matchAll(/\.ph-(?:thin|light|bold|fill|duotone)?\.?ph-([a-z0-9-]+):+before/g)) {
        names.add(m[1]);
    }
    for (const m of css.matchAll(/\.ph\.ph-([a-z0-9-]+):+before/g)) {
        names.add(m[1]);
    }
    return names;
}

/** True when a weight class (`ph`, `ph-bold`…) already sits in the same class list before `index`. */
function hasWeightBefore(text, index) {
    let start = index;
    while (start > 0 && !/["'`>\n{]/.test(text[start - 1])) {
        start--;
    }
    return /(^|\s)ph(-(thin|light|bold|fill|duotone))?\s/.test(text.slice(start, index));
}

/** @returns {{ text: string, count: number }} */
export function migrate(text, names) {
    let count = 0;
    // Not after a "." (a CSS selector such as `.find('.ph-x')` names one class and stays as it is).
    const out = text.replace(/(?<![\w.-])ph-([a-z0-9]+(?:-[a-z0-9]+)*)(?![\w-])/g, (token, name, offset) => {
        let icon = name;
        let weight = 'ph';
        const suffix = WEIGHTS.find((w) => name.endsWith('-' + w) && names.has(name.slice(0, -(w.length + 1))));
        if (!names.has(name) && suffix) {
            icon = name.slice(0, -(suffix.length + 1));
            weight = 'ph-' + suffix;
        }
        if (!names.has(icon) || hasWeightBefore(text, offset)) {
            return token;
        }
        count++;
        return `${weight} ph-${icon}`;
    });
    return { text: out, count };
}

function* files(target) {
    const stat = fs.statSync(target);
    if (stat.isFile()) {
        if (EXTENSIONS.some((ext) => target.endsWith(ext))) yield target;
        return;
    }
    for (const entry of fs.readdirSync(target, { withFileTypes: true })) {
        if (entry.isDirectory() && SKIP_DIRS.has(entry.name)) continue;
        yield* files(path.join(target, entry.name));
    }
}

const isMain = process.argv[1] && path.resolve(process.argv[1]) === fileURLToPath(import.meta.url);
if (isMain) {
    const args = process.argv.slice(2);
    const write = args.includes('--write');
    const targets = args.filter((a) => a !== '--write');
    if (targets.length === 0) {
        console.error('Usage: node migrate-phosphor-2.mjs <dir|file>... [--write]');
        process.exit(1);
    }

    const names = iconNames();
    let changed = 0;
    let scanned = 0;
    for (const target of targets) {
        for (const file of files(target)) {
            scanned++;
            const before = fs.readFileSync(file, 'utf8');
            const { text, count } = migrate(before, names);
            if (count === 0) continue;
            changed++;
            console.log(`${write ? 'rewrote' : 'would rewrite'} ${file} (${count} icon${count === 1 ? '' : 's'})`);
            if (write) fs.writeFileSync(file, text);
        }
    }
    console.log(`${write ? 'Rewrote' : 'Would rewrite'} ${changed} of ${scanned} files`);
}
