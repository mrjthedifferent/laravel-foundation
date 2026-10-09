// foundation.js together with the bundled jQuery and Select2, as the layout loads them, run in jsdom
// by bin/test-js.sh. Guards the library upgrades (jQuery 4, Select2 4.1).
import { test } from 'node:test';
import assert from 'node:assert/strict';
import { createRequire } from 'node:module';
import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const require = createRequire(path.join(process.env.FOUNDATION_BUILD, 'package.json'));
const { JSDOM } = require('jsdom');

const assets = path.join(path.dirname(fileURLToPath(import.meta.url)), '../../ui/public/assets');
const read = (file) => fs.readFileSync(path.join(assets, file), 'utf8');
const scripts = [read('js/jquery.min.js'), read('vendor/select2/select2.min.js'), read('js/foundation.js')];

const wait = (ms) => new Promise((resolve) => setTimeout(resolve, ms));

/** Loads markup, then jQuery, Select2 and foundation.js in the layout's order. */
function page(body) {
    const { window } = new JSDOM(`<!doctype html><html><head></head><body>${body}</body></html>`, {
        runScripts: 'outside-only',
        url: 'https://app.test/admin/things',
        pretendToBeVisual: true,
    });
    for (const script of scripts) {
        window.eval(script);
    }
    window.document.dispatchEvent(new window.Event('DOMContentLoaded'));
    return window;
}

test('the bundled jQuery is version 4 and Select2 registers on it', () => {
    const w = page('');
    assert.match(w.jQuery.fn.jquery, /^4\./);
    assert.equal(typeof w.jQuery.fn.select2, 'function');
});

test('the jQuery shims open and close a modal', async () => {
    const w = page(`<div class="modal fade" id="m" aria-hidden="true"><div class="modal-dialog"><div class="modal-content"></div></div></div>`);
    const modal = w.document.getElementById('m');

    w.jQuery('#m').modal('show');
    await wait(260);
    assert.ok(modal.classList.contains('show'));

    w.jQuery('#m').modal('hide');
    await wait(260);
    assert.ok(!modal.classList.contains('show'));
});

test('a select.select becomes a named Select2 combobox, and its value still submits', () => {
    const w = page(`
        <label for="kind">Kind</label>
        <select id="kind" name="kind" class="select"><option value="a">A</option><option value="b" selected>B</option></select>`);
    const select = w.document.getElementById('kind');

    assert.ok(select.classList.contains('select2-hidden-accessible'));
    const rendered = w.document.querySelector('.select2 .select2-selection__rendered');
    assert.equal(rendered.getAttribute('aria-label'), 'Kind');
    assert.equal(rendered.textContent.trim(), 'B');

    w.jQuery(select).val('a').trigger('change');
    assert.equal(select.value, 'a');
    assert.equal(w.document.querySelector('.select2 .select2-selection__rendered').textContent.trim(), 'A');
});
