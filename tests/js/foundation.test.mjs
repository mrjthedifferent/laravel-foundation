// Behaviour tests for ui/public/assets/js/foundation.js, run in jsdom by bin/test-js.sh.
import { test } from 'node:test';
import assert from 'node:assert/strict';
import { createRequire } from 'node:module';
import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const require = createRequire(path.join(process.env.FOUNDATION_BUILD, 'package.json'));
const { JSDOM } = require('jsdom');

const here = path.dirname(fileURLToPath(import.meta.url));
const script = fs.readFileSync(path.join(here, '../../ui/public/assets/js/foundation.js'), 'utf8');

const wait = (ms) => new Promise((resolve) => setTimeout(resolve, ms));

/** Loads a page of markup, runs foundation.js against it, and returns the window. */
function page(body, { fetchImpl } = {}) {
    const dom = new JSDOM(`<!doctype html><html><head><meta name="csrf-token" content="tok"></head><body>${body}</body></html>`, {
        runScripts: 'outside-only',
        url: 'https://app.test/admin/things',
        pretendToBeVisual: true,
    });
    const { window } = dom;
    window.URL.createObjectURL = () => 'blob:preview';
    window.URL.revokeObjectURL = () => {};
    if (fetchImpl) { window.fetch = fetchImpl; }
    window.eval(script);
    window.document.dispatchEvent(new window.Event('DOMContentLoaded'));
    return window;
}

const click = (window, el) => el.dispatchEvent(new window.MouseEvent('click', { bubbles: true, cancelable: true }));

test('a modal opens from its trigger, takes a backdrop, and closes on dismiss, Escape and backdrop click', async () => {
    const w = page(`
        <button id="open" data-fd-toggle="modal" data-fd-target="#m">Open</button>
        <div class="modal fade" id="m" aria-hidden="true"><div class="modal-dialog"><div class="modal-content">
            <button id="x" data-fd-dismiss="modal">x</button></div></div></div>`);
    const modal = w.document.getElementById('m');
    const events = [];
    w.document.addEventListener('fd:modal-shown', (e) => events.push(e.target.id));

    click(w, w.document.getElementById('open'));
    await wait(260);
    assert.ok(modal.classList.contains('show'));
    assert.ok(w.document.body.classList.contains('modal-open'));
    assert.equal(w.document.querySelectorAll('.modal-backdrop.show').length, 1);
    assert.deepEqual(events, ['m']);

    click(w, w.document.getElementById('x'));
    await wait(260);
    assert.ok(!modal.classList.contains('show'));
    assert.ok(!w.document.body.classList.contains('modal-open'));
    assert.equal(w.document.querySelectorAll('.modal-backdrop').length, 0);

    click(w, w.document.getElementById('open'));
    await wait(260);
    w.document.dispatchEvent(new w.KeyboardEvent('keydown', { key: 'Escape', bubbles: true }));
    await wait(260);
    assert.ok(!modal.classList.contains('show'), 'Escape closes it');

    click(w, w.document.getElementById('open'));
    await wait(260);
    modal.dispatchEvent(new w.MouseEvent('mousedown', { bubbles: true }));
    await wait(260);
    assert.ok(!modal.classList.contains('show'), 'a click on the dimmed area closes it');
});

test('a static modal ignores Escape and backdrop clicks', async () => {
    const w = page(`
        <button id="open" data-fd-toggle="modal" data-fd-target="#m"></button>
        <div class="modal fade" id="m" data-fd-backdrop="static" data-fd-keyboard="false"><div class="modal-dialog"></div></div>`);
    const modal = w.document.getElementById('m');
    click(w, w.document.getElementById('open'));
    await wait(260);
    w.document.dispatchEvent(new w.KeyboardEvent('keydown', { key: 'Escape', bubbles: true }));
    modal.dispatchEvent(new w.MouseEvent('mousedown', { bubbles: true }));
    await wait(260);
    assert.ok(modal.classList.contains('show'));
});

test('a dropdown menu moves to <body> while open and returns to its place when closed', async () => {
    const w = page(`
        <nav class="navbar"><div class="dropdown">
            <button id="t" data-fd-toggle="dropdown">menu</button>
            <div class="dropdown-menu" id="menu"><a class="dropdown-item" href="#a">A</a></div>
        </div></nav><p id="outside">x</p>`);
    const menu = w.document.getElementById('menu');
    const home = menu.parentElement;

    click(w, w.document.getElementById('t'));
    assert.ok(menu.classList.contains('show'));
    assert.equal(menu.parentElement, w.document.body, 'portalled to <body>');
    assert.equal(w.document.getElementById('t').getAttribute('aria-expanded'), 'true');

    click(w, w.document.getElementById('outside'));
    assert.ok(!menu.classList.contains('show'));
    assert.equal(menu.parentElement, home, 'back where it was');
    assert.equal(w.document.getElementById('t').getAttribute('aria-expanded'), 'false');
});

test('only one dropdown is open at a time, and Escape closes it', () => {
    const w = page(`
        <div class="dropdown"><button id="a" data-fd-toggle="dropdown"></button><div class="dropdown-menu" id="ma"></div></div>
        <div class="dropdown"><button id="b" data-fd-toggle="dropdown"></button><div class="dropdown-menu" id="mb"></div></div>`);
    click(w, w.document.getElementById('a'));
    click(w, w.document.getElementById('b'));
    assert.ok(!w.document.getElementById('ma').classList.contains('show'));
    assert.ok(w.document.getElementById('mb').classList.contains('show'));
    w.document.dispatchEvent(new w.KeyboardEvent('keydown', { key: 'Escape', bubbles: true }));
    assert.ok(!w.document.getElementById('mb').classList.contains('show'));
});

test('tabs activate their pane and announce it', () => {
    const w = page(`
        <ul class="nav"><li><button id="t1" class="nav-link active" data-fd-toggle="tab" data-fd-target="#p1"></button></li>
        <li><button id="t2" class="nav-link" data-fd-toggle="tab" data-fd-target="#p2"></button></li></ul>
        <div class="tab-content"><div id="p1" class="tab-pane active show"></div><div id="p2" class="tab-pane"></div></div>`);
    const seen = [];
    w.document.addEventListener('fd:tab-shown', (e) => seen.push(e.target.id));
    click(w, w.document.getElementById('t2'));
    assert.ok(w.document.getElementById('t2').classList.contains('active'));
    assert.ok(!w.document.getElementById('t1').classList.contains('active'));
    assert.ok(w.document.getElementById('p2').classList.contains('active'));
    assert.ok(!w.document.getElementById('p1').classList.contains('active'));
    assert.deepEqual(seen, ['t2']);
});

test('collapse toggles its target and announces', () => {
    const w = page(`<button id="c" data-fd-toggle="collapse" data-fd-target="#body"></button><div id="body" class="collapse"></div>`);
    const seen = [];
    w.document.addEventListener('fd:collapse-shown', () => seen.push('shown'));
    w.document.addEventListener('fd:collapse-hidden', () => seen.push('hidden'));
    click(w, w.document.getElementById('c'));
    assert.ok(w.document.getElementById('body').classList.contains('show'));
    click(w, w.document.getElementById('c'));
    assert.ok(!w.document.getElementById('body').classList.contains('show'));
    assert.deepEqual(seen, ['shown', 'hidden']);
});

test('a tooltip shows on hover and leaves on mouseout', () => {
    const w = page(`<span id="s" data-fd-toggle="tooltip" data-fd-placement="top" title="Full text">Short</span>`);
    const el = w.document.getElementById('s');
    el.dispatchEvent(new w.MouseEvent('mouseover', { bubbles: true }));
    assert.equal(w.document.querySelector('.tooltip')?.textContent, 'Full text');
    el.dispatchEvent(new w.MouseEvent('mouseout', { bubbles: true }));
    assert.equal(w.document.querySelector('.tooltip'), null);
});

test('stacked tables copy column headings to cells and mark empty cells', () => {
    const w = page(`<table class="table table-stack"><thead><tr><th>Photo</th><th>Name</th><th>Role</th></tr></thead>
        <tbody><tr><td><img src="a.png"></td><td>Ada</td><td></td></tr><tr><td colspan="3">None</td></tr></tbody></table>`);
    const cells = [...w.document.querySelectorAll('tbody tr:first-child td')];
    assert.deepEqual(cells.map((c) => c.getAttribute('data-label')), ['Photo', 'Name', 'Role']);
    assert.ok(!cells[0].hasAttribute('data-empty'), 'a cell with an image is not empty');
    assert.ok(cells[2].hasAttribute('data-empty'));
    assert.equal(w.document.querySelector('td[colspan]').hasAttribute('data-label'), false, 'merged rows are left alone');
});

const filterBar = `
    <div class="card" data-fd-filterbar><form method="get" action="/x">
        <div class="fd-filterbar-row"><div data-fd-filter-search hidden></div>
            <button type="button" data-fd-filter-toggle aria-expanded="true">Filters <span data-fd-filter-count hidden></span></button></div>
        <div data-fd-filter-chips hidden></div>
        <div data-fd-filter-panel><div class="grid">
            <div class="col-span-12"><label for="search">Search</label><input id="search" name="search" value="ada"></div>
            <div class="col-span-12"><label for="status">Status</label><select id="status" name="is_active">
                <option value="">All</option><option value="1" selected>Active</option></select></div>
            <div class="col-span-12"><label for="from">From</label><input id="from" name="date_from" type="date" value=""></div>
        </div></div></form></div>`;

test('the filter bar lifts the search box, chips the active filters and opens the panel', () => {
    const w = page(filterBar);
    const bar = w.document.querySelector('[data-fd-filterbar]');

    assert.ok(bar.querySelector('[data-fd-filter-search] input[name="search"]'), 'search moved into the bar');
    assert.equal(bar.querySelector('[data-fd-filter-search]').hidden, false);

    const chips = [...bar.querySelectorAll('.fd-chip')];
    assert.equal(chips.length, 1, 'only the select has a value; search and the empty date do not chip');
    assert.match(chips[0].textContent, /Status/);
    assert.match(chips[0].textContent, /Active/);
    assert.equal(bar.querySelector('[data-fd-filter-count]').textContent, '1');

    assert.ok(!bar.classList.contains('is-open'), 'closed by default');
    click(w, bar.querySelector('[data-fd-filter-toggle]'));
    assert.ok(bar.classList.contains('is-open'));
    assert.equal(bar.querySelector('[data-fd-filter-toggle]').getAttribute('aria-expanded'), 'true');
    assert.equal(w.localStorage.getItem('fd-filters:/admin/things'), 'open', 'remembered per page');
});

test('removing a chip clears its field and submits the form', () => {
    const w = page(filterBar);
    let submitted = false;
    w.HTMLFormElement.prototype.submit = function () { submitted = true; };
    click(w, w.document.querySelector('.fd-chip'));
    assert.equal(w.document.getElementById('status').value, '');
    assert.ok(submitted);
});

test('a file drop zone shows the chosen file and its preview, and restores the stored one when cleared', () => {
    const w = page(`<div data-fd-upload data-current="/stored.png"><img class="fd-drop-preview" src="/stored.png">
        <span class="fd-drop-file" hidden></span><input type="file" id="f"></div>`);
    const input = w.document.getElementById('f');
    const file = new w.File(['x'.repeat(2048)], 'me.png', { type: 'image/png' });
    Object.defineProperty(input, 'files', { value: [file], configurable: true });
    input.dispatchEvent(new w.Event('change', { bubbles: true }));

    const zone = w.document.querySelector('[data-fd-upload]');
    assert.ok(zone.classList.contains('has-file'));
    assert.match(zone.querySelector('.fd-drop-file').textContent, /me\.png · 2 KB/);
    assert.equal(zone.querySelector('.fd-drop-preview').getAttribute('src'), 'blob:preview');

    Object.defineProperty(input, 'files', { value: [], configurable: true });
    input.dispatchEvent(new w.Event('change', { bubbles: true }));
    assert.ok(!zone.classList.contains('has-file'));
    assert.equal(zone.querySelector('.fd-drop-preview').getAttribute('src'), '/stored.png');
});

const dashboard = `
    <button data-fd-dash="edit">Customize</button><button data-fd-dash="reset">Reset</button><button data-fd-dash="done">Done</button>
    <div data-fd-dashboard data-url-save="/admin/dashboard/layout" data-url-reset="/admin/dashboard/layout" data-msg-error="failed">
      ${['stats', 'trend', 'health'].map((key, i) => `
        <section data-fd-widget data-key="${key}" data-width="${[12, 8, 4][i]}" data-hidden="false" class="fd-widget fd-w-${[12, 8, 4][i]}">
            <div class="fd-widget-bar"><span class="fd-widget-handle" draggable="true"></span>
                ${['earlier', 'later', 'narrower', 'wider', 'toggle'].map((a) => `<button data-fd-widget-act="${a}"><i class="ph ph-eye"></i></button>`).join('')}
            </div><div class="fd-widget-body">${key}</div></section>`).join('')}
    </div>`;

const order = (w) => [...w.document.querySelectorAll('[data-fd-widget]')].map((e) => e.dataset.key);
const act = (w, key, what) => click(w, w.document.querySelector(`[data-key="${key}"] [data-fd-widget-act="${what}"]`));

test('the dashboard editor reorders, resizes and hides widgets, and Done saves exactly that', async () => {
    const calls = [];
    const w = page(dashboard, {
        fetchImpl: (url, options) => { calls.push({ url, options }); return Promise.resolve({ ok: true, json: () => Promise.resolve({}) }); },
    });

    click(w, w.document.querySelector('[data-fd-dash="edit"]'));
    assert.ok(w.document.body.classList.contains('is-editing'));

    act(w, 'health', 'earlier');
    assert.deepEqual(order(w), ['stats', 'health', 'trend']);
    act(w, 'stats', 'later');
    assert.deepEqual(order(w), ['health', 'stats', 'trend']);
    act(w, 'health', 'earlier'); // already first: nothing changes
    assert.deepEqual(order(w), ['health', 'stats', 'trend']);

    act(w, 'health', 'wider');
    assert.equal(w.document.querySelector('[data-key="health"]').dataset.width, '6');
    assert.ok(w.document.querySelector('[data-key="health"]').classList.contains('fd-w-6'));
    act(w, 'trend', 'narrower');
    assert.equal(w.document.querySelector('[data-key="trend"]').dataset.width, '6');
    act(w, 'stats', 'wider'); // already the widest
    assert.equal(w.document.querySelector('[data-key="stats"]').dataset.width, '12');

    act(w, 'trend', 'toggle');
    assert.equal(w.document.querySelector('[data-key="trend"]').dataset.hidden, 'true');
    assert.ok(w.document.querySelector('[data-key="trend"]').classList.contains('is-hidden'));

    click(w, w.document.querySelector('[data-fd-dash="done"]'));
    await wait(20);

    assert.equal(calls.length, 1);
    assert.equal(calls[0].url, '/admin/dashboard/layout');
    assert.equal(calls[0].options.method, 'PUT');
    assert.equal(calls[0].options.headers['X-CSRF-TOKEN'], 'tok');
    assert.deepEqual(JSON.parse(calls[0].options.body).items, [
        { key: 'health', width: 6, hidden: false },
        { key: 'stats', width: 12, hidden: false },
        { key: 'trend', width: 6, hidden: true },
    ]);
});

test('Done with no change sends nothing, and drag and drop moves a widget', async () => {
    const calls = [];
    const w = page(dashboard, { fetchImpl: (...args) => { calls.push(args); return Promise.resolve({ ok: true, json: () => Promise.resolve({}) }); } });
    click(w, w.document.querySelector('[data-fd-dash="edit"]'));
    click(w, w.document.querySelector('[data-fd-dash="done"]'));
    await wait(10);
    assert.equal(calls.length, 0);
    assert.ok(!w.document.body.classList.contains('is-editing'));

    click(w, w.document.querySelector('[data-fd-dash="edit"]'));
    const dt = { setData() {}, setDragImage() {}, effectAllowed: '' };
    const fire = (el, type, x, y) => {
        const ev = new w.Event(type, { bubbles: true, cancelable: true });
        ev.dataTransfer = dt; ev.clientX = x; ev.clientY = y;
        el.dispatchEvent(ev);
    };
    const health = w.document.querySelector('[data-key="health"]');
    const stats = w.document.querySelector('[data-key="stats"]');
    stats.getBoundingClientRect = () => ({ top: 0, left: 0, height: 100, width: 100 });
    fire(health.querySelector('.fd-widget-handle'), 'dragstart', 0, 0);
    fire(stats, 'dragover', 5, 5);
    fire(stats, 'drop', 5, 5);
    fire(health.querySelector('.fd-widget-handle'), 'dragend', 0, 0);
    assert.deepEqual(order(w), ['health', 'stats', 'trend']);
});

test('reset sends DELETE', async () => {
    const calls = [];
    const w = page(dashboard, { fetchImpl: (url, options) => { calls.push(options.method); return Promise.resolve({ ok: true, json: () => Promise.resolve({}) }); } });
    click(w, w.document.querySelector('[data-fd-dash="reset"]'));
    await wait(10);
    assert.deepEqual(calls, ['DELETE']);
});

test('colour mode and direction are applied to <html>', () => {
    const w = page('<p>x</p>');
    w.Foundation.applyColorMode('dark');
    assert.equal(w.document.documentElement.getAttribute('data-theme'), 'dark');
    w.Foundation.applyColorMode('light');
    assert.equal(w.document.documentElement.getAttribute('data-theme'), 'light');
    w.Foundation.applyDirection('rtl');
    assert.equal(w.document.documentElement.getAttribute('dir'), 'rtl');
});

test('the public Foundation API exists and Bootstrap is gone', () => {
    const w = page('<p>x</p>');
    for (const fn of ['modal', 'offcanvas', 'dropdown', 'collapse', 'tab', 'initSelects', 'initTableLabels', 'applyColorMode', 'applyDirection']) {
        assert.equal(typeof w.Foundation[fn], 'function', fn);
    }
    assert.equal(typeof w.bootstrap, 'undefined');
});

const key = (w, el, k, extra = {}) => el.dispatchEvent(new w.KeyboardEvent('keydown', { key: k, bubbles: true, cancelable: true, ...extra }));

test('arrow keys move through an open dropdown, and ArrowDown on its toggle opens it', () => {
    const w = page(`<div class="dropdown"><a href="#" id="t" data-fd-toggle="dropdown">menu</a>
        <div class="dropdown-menu"><a class="dropdown-item" id="a" href="#a">A</a><a class="dropdown-item" id="b" href="#b">B</a>
        <a class="dropdown-item disabled" id="c" href="#c">C</a></div></div>`);
    const t = w.document.getElementById('t');
    t.focus();
    key(w, t, 'ArrowDown');
    assert.equal(w.document.activeElement.id, 'a', 'opens and focuses the first item');
    key(w, w.document.activeElement, 'ArrowDown');
    assert.equal(w.document.activeElement.id, 'b');
    key(w, w.document.activeElement, 'ArrowDown');
    assert.equal(w.document.activeElement.id, 'a', 'wraps, skipping the disabled item');
    key(w, w.document.activeElement, 'ArrowUp');
    assert.equal(w.document.activeElement.id, 'b');
    key(w, w.document.activeElement, 'Home');
    assert.equal(w.document.activeElement.id, 'a');
    key(w, w.document.activeElement, 'End');
    assert.equal(w.document.activeElement.id, 'b');
});

test('Tab stays inside an open drawer', async () => {
    const w = page(`<button id="o" data-fd-toggle="offcanvas" data-fd-target="#d">open</button>
        <div class="offcanvas offcanvas-end" id="d" tabindex="-1"><button id="first">1</button><button id="last">2</button></div>`);
    const focusable = (el) => { Object.defineProperty(el, 'offsetParent', { get: () => w.document.body }); };
    focusable(w.document.getElementById('first'));
    focusable(w.document.getElementById('last'));
    click(w, w.document.getElementById('o'));
    await wait(350);
    assert.ok(w.document.getElementById('d').classList.contains('show'));

    const last = w.document.getElementById('last');
    last.focus();
    const forward = new w.KeyboardEvent('keydown', { key: 'Tab', bubbles: true, cancelable: true });
    last.dispatchEvent(forward);
    assert.ok(forward.defaultPrevented);
    assert.equal(w.document.activeElement.id, 'first', 'wraps from last to first');

    const back = new w.KeyboardEvent('keydown', { key: 'Tab', shiftKey: true, bubbles: true, cancelable: true });
    w.document.getElementById('first').dispatchEvent(back);
    assert.equal(w.document.activeElement.id, 'last', 'and from first back to last');
});
