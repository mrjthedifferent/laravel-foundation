// bin/migrate-phosphor-2.mjs, run by bin/test-js.sh.
import { test } from 'node:test';
import assert from 'node:assert/strict';
import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';
import { iconNames, migrate } from '../../bin/migrate-phosphor-2.mjs';

const root = path.join(path.dirname(fileURLToPath(import.meta.url)), '../..');
const names = iconNames();
const run = (text) => migrate(text, names).text;

test('icons get the regular weight class, wherever they are written', () => {
    assert.equal(run('<i class="ph-eye"></i>'), '<i class="ph ph-eye"></i>');
    assert.equal(run('<x-page-header icon="ph-house" />'), '<x-page-header icon="ph ph-house" />');
    assert.equal(run("'icon' => 'ph-gear',"), "'icon' => 'ph ph-gear',");
    assert.equal(run("el.className = open ? 'ph-eye-slash' : 'ph-eye';"), "el.className = open ? 'ph ph-eye-slash' : 'ph ph-eye';");
    assert.equal(run('<i class="ph-users ph-lg me-2"></i>'), '<i class="ph ph-users ph-lg me-2"></i>');
});

test('a 1.x weight suffix becomes the 2.x weight class', () => {
    assert.equal(run('<i class="ph-star-fill"></i>'), '<i class="ph-fill ph-star"></i>');
    assert.equal(run('<i class="ph-heart-bold"></i>'), '<i class="ph-bold ph-heart"></i>');
});

test('helpers, selectors, other words and migrated icons are left alone', () => {
    for (const text of [
        '<i class="ph ph-eye"></i>',
        '<i class="ph-fill ph-star"></i>',
        '<span class="ph-spin ph-lg">',
        "$('.ph-image-square').hide();",
        'graph-paper and morph-able',
        '.ph-sm { font-size: .875em; }',
    ]) {
        assert.equal(run(text), text);
    }
});

test('running it twice changes nothing more', () => {
    const once = run('<i class="ph-eye"></i> <i class="ph-star-fill"></i> \'icon\' => \'ph-gear\'');
    assert.equal(run(once), once);
});

test('the package itself has nothing left to migrate', () => {
    // Code only: the guidelines quote the old form on purpose ("a bare `ph-gear` renders nothing").
    const dirs = ['ui/resources/views', 'ui/resources/js', 'modules', 'src', 'config'];
    const pending = [];
    const walk = (dir) => {
        for (const entry of fs.readdirSync(dir, { withFileTypes: true })) {
            const file = path.join(dir, entry.name);
            if (entry.isDirectory()) { walk(file); continue; }
            if (!/\.(php|js|md)$/.test(entry.name)) { continue; }
            if (migrate(fs.readFileSync(file, 'utf8'), names).count > 0) { pending.push(path.relative(root, file)); }
        }
    };
    dirs.forEach((dir) => walk(path.join(root, dir)));
    assert.deepEqual(pending, []);
});
