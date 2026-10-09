#!/usr/bin/env node
// Converts the Bootstrap 5 classes and data attributes the foundation used up to 2.0 to the
// Tailwind equivalents it uses now, in a project's own Blade views, PHP and JavaScript.
//
//   node vendor/mrjthedifferent/laravel-foundation/bin/migrate-bootstrap-to-tailwind.mjs resources/views
//   node vendor/mrjthedifferent/laravel-foundation/bin/migrate-bootstrap-to-tailwind.mjs resources/views --write
//
// Without --write it only lists the files it would change. Run it ONCE per file, on a clean
// git tree, and review the diff: the spacing scale is renumbered (Bootstrap mb-3 is Tailwind
// mb-4), so a second run would renumber it again. The `row` / `col-*` grid, d-*, flex, text,
// border, rounded, font and sizing utilities, badge colours and data-bs-* attributes are
// converted; anything it cannot map is left as it was.
import fs from 'node:fs';
import path from 'node:path';

const root = path.resolve(process.argv[2] || 'resources/views');
const WRITE = process.argv.includes('--write');

const BP = { sm: 'sm', md: 'md', lg: 'lg', xl: 'xl', xxl: '2xl' };
const SP = { 0: '0', 1: '1', 2: '2', 3: '4', 4: '6', 5: '12', auto: 'auto' };
const GAP = { 0: '0', 1: '1', 2: '2', 3: '4', 4: '6', 5: '12' };
const FRAC = { 25: '1/4', 50: '1/2', 75: '3/4', 100: 'full', auto: 'auto' };

const simple = {
    // display handled by pattern
    'flex-column': 'flex-col', 'flex-column-reverse': 'flex-col-reverse',
    'flex-fill': 'flex-auto', 'flex-grow-0': 'grow-0', 'flex-grow-1': 'grow', 'flex-shrink-0': 'shrink-0', 'flex-shrink-1': 'shrink',
    'position-absolute': 'absolute', 'position-relative': 'relative', 'position-fixed': 'fixed', 'position-sticky': 'sticky', 'position-static': 'static',
    'top-50': 'top-1/2', 'top-100': 'top-full', 'start-50': 'start-1/2', 'start-100': 'start-full', 'bottom-100': 'bottom-full', 'end-100': 'end-full',
    'translate-middle': '-translate-x-1/2 -translate-y-1/2', 'translate-middle-x': '-translate-x-1/2', 'translate-middle-y': '-translate-y-1/2',
    'translate-middle-top': 'translate-x-1/4 -translate-y-1/4', 'zindex-1': 'z-1',
    'text-nowrap': 'whitespace-nowrap', 'text-break': 'break-words', 'text-truncate': 'truncate',
    'text-uppercase': 'uppercase', 'text-lowercase': 'lowercase', 'text-capitalize': 'capitalize',
    'text-decoration-none': 'no-underline', 'text-decoration-underline': 'underline', 'text-decoration-line-through': 'line-through',
    'text-secondary': 'text-muted', 'text-body-secondary': 'text-muted', 'text-dark': 'text-strong', 'text-light': 'text-faint', 'text-teal': 'text-teal-600',
    'bg-white': 'bg-surface', 'bg-light': 'bg-subtle', 'bg-body-tertiary': 'bg-subtle', 'bg-body': 'bg-canvas', 'bg-secondary-subtle': 'bg-subtle',
    'bg-dark': 'bg-zinc-900', 'bg-secondary': 'bg-muted', 'bg-yellow': 'bg-warning',
    'bg-primary-subtle': 'bg-primary-subtle',
    'fw-bold': 'font-bold', 'fw-bolder': 'font-extrabold', 'fw-semibold': 'font-semibold', 'fw-medium': 'font-medium', 'fw-normal': 'font-normal', 'fw-light': 'font-light',
    'fst-italic': 'italic', 'fst-normal': 'not-italic', 'font-monospace': 'font-mono',
    'fs-xs': 'text-xs', 'fs-sm': 'text-sm', 'fs-base': 'text-base', 'fs-lg': 'text-md',
    'lh-1': 'leading-none', 'lh-sm': 'leading-snug', 'lh-base': 'leading-normal', 'lh-lg': 'leading-loose',
    'border-top': 'border-t', 'border-bottom': 'border-b', 'border-start': 'border-s', 'border-end': 'border-e',
    'border-top-0': 'border-t-0', 'border-bottom-0': 'border-b-0', 'border-start-0': 'border-s-0', 'border-end-0': 'border-e-0',
    'border-secondary': 'border-line-strong', 'border-light': 'border-line', 'border-width-1': 'border', 'border-bottom-white': 'border-b-line',
    'rounded': 'rounded-md', 'rounded-0': 'rounded-none', 'rounded-1': 'rounded-sm', 'rounded-2': 'rounded-md', 'rounded-3': 'rounded-lg', 'rounded-4': 'rounded-xl', 'rounded-5': 'rounded-2xl',
    'rounded-circle': 'rounded-full', 'rounded-pill': 'rounded-full',
    'rounded-top': 'rounded-t-md', 'rounded-bottom': 'rounded-b-md', 'rounded-start': 'rounded-s-md', 'rounded-end': 'rounded-e-md',
    'shadow': 'shadow-md',
    'visually-hidden': 'sr-only', 'pe-none': 'pointer-events-none', 'pe-auto': 'pointer-events-auto',
    'user-select-none': 'select-none', 'user-select-all': 'select-all',
    'img-fluid': 'max-w-full h-auto', 'object-fit-cover': 'object-cover', 'object-fit-contain': 'object-contain',
    'list-unstyled': 'list-none ps-0', 'min-width-0': 'min-w-0',
    'mw-100': 'max-w-full', 'mh-100': 'max-h-full', 'min-vh-100': 'min-h-screen', 'vh-100': 'h-screen', 'vw-100': 'w-screen',
    'w-32px': 'w-8', 'w-40px': 'w-10', 'w-48px': 'w-12', 'h-24px': 'h-6', 'h-32px': 'h-8', 'h-40px': 'h-10', 'h-48px': 'h-12', 'w-sm': 'w-50',
    'w-md-25': 'md:w-1/4', 'flex-lg-0': 'lg:flex-none', 'flex-lg-1': 'lg:flex-1',
    'float-start': 'float-start', 'float-end': 'float-end',
};

function mapToken(t) {
    if (Object.hasOwn(simple, t)) return simple[t];
    let m;
    const bpPrefix = (b) => (b ? BP[b] + ':' : '');
    // display
    if ((m = t.match(/^d-(?:(sm|md|lg|xl|xxl)-)?(none|block|inline|inline-block|flex|inline-flex|grid|table|table-cell|table-row)$/))) {
        return bpPrefix(m[1]) + (m[2] === 'none' ? 'hidden' : m[2]);
    }
    // flex direction / wrap with breakpoint
    if ((m = t.match(/^flex-(?:(sm|md|lg|xl|xxl)-)?(row|column|row-reverse|column-reverse|wrap|nowrap|wrap-reverse)$/))) {
        const v = { column: 'col', 'column-reverse': 'col-reverse' }[m[2]] || m[2];
        return bpPrefix(m[1]) + 'flex-' + v;
    }
    if ((m = t.match(/^justify-content-(?:(sm|md|lg|xl|xxl)-)?(start|end|center|between|around|evenly)$/))) return bpPrefix(m[1]) + 'justify-' + m[2];
    if ((m = t.match(/^align-items-(?:(sm|md|lg|xl|xxl)-)?(start|end|center|baseline|stretch)$/))) return bpPrefix(m[1]) + 'items-' + m[2];
    if ((m = t.match(/^align-self-(?:(sm|md|lg|xl|xxl)-)?(start|end|center|baseline|stretch|auto)$/))) return bpPrefix(m[1]) + 'self-' + m[2];
    if ((m = t.match(/^align-content-(start|end|center|between|around|stretch)$/))) return 'content-' + m[1];
    if ((m = t.match(/^gap-(?:(sm|md|lg|xl|xxl)-)?([0-5])$/))) return bpPrefix(m[1]) + 'gap-' + GAP[m[2]];
    if ((m = t.match(/^(row-gap|column-gap)-([0-5])$/))) return (m[1] === 'row-gap' ? 'gap-y-' : 'gap-x-') + GAP[m[2]];
    // spacing
    if ((m = t.match(/^(m|p)([tbsexy]?)-(?:(sm|md|lg|xl|xxl)-)?(0|1|2|3|4|5|auto)$/))) {
        if (m[1] === 'p' && m[4] === 'auto') return null;
        return bpPrefix(m[3]) + m[1] + m[2] + '-' + SP[m[4]];
    }
    if ((m = t.match(/^(m)([tbsexy]?)-n([1-5])$/))) return '-' + m[1] + m[2] + '-' + SP[m[3]];
    // sizing
    if ((m = t.match(/^(w|h)-(25|50|75|100|auto)$/))) return m[1] + '-' + FRAC[m[2]];
    // text alignment with breakpoints
    if ((m = t.match(/^text-(?:(sm|md|lg|xl|xxl)-)(start|end|center)$/))) return bpPrefix(m[1]) + 'text-' + m[2];
    if ((m = t.match(/^fs-([1-6])$/))) return { 1: 'text-[2.5rem]', 2: 'text-[2rem]', 3: 'text-[1.75rem]', 4: 'text-[1.5rem]', 5: 'text-[1.25rem]', 6: 'text-base' }[m[1]];
    return undefined;
}

// Tokens that are only safe to rewrite inside a class list.
const CLASS_FILES = /\.(php|js|html|md)$/;

function transformToken(tok) {
    const r = mapToken(tok);
    return r === undefined ? tok : (r === null ? tok : r);
}

// Token boundary: not preceded/followed by a word char, '-', '$', or ':' (so `md:d-flex` and `$d-flex` are left alone).
const TOKEN_RE = /(^|[^\w\-$:/.@#{])((?:[a-z]+-)?[a-z]+(?:-[a-z0-9]+)*)(?![\w\-:/@])/g;

function replaceTokens(text) {
    // Replace every candidate token that has a mapping. Quoted selectors like '.d-none' (jQuery) are handled by the '.' pass below.
    let out = text.replace(TOKEN_RE, (full, pre, tok) => {
        const r = mapToken(tok);
        return r === undefined || r === null ? full : pre + r;
    });
    // jQuery/CSS selectors and classList calls: .d-none -> .hidden
    out = out.replace(/(['"`(\s,])\.((?:[a-z]+-)?[a-z]+(?:-[a-z0-9]+)*)(?![\w\-:/@(])/g, (full, pre, tok) => {
        const r = mapToken(tok);
        if (r === undefined || r === null || /[ :]/.test(r)) return full;
        return pre + '.' + r;
    });
    return out;
}

function gridPass(text) {
    text = text.replace(/(class\s*=\s*)(["'])row\2/g, '$1$2grid grid-cols-12 gap-x-6$2');
    // A class list is a quoted string that contains the token `row` plus other class-ish tokens.
    return text.replace(/(["'`])([^"'`\n]*?)\1/g, (full, q, body) => {
        if (!/(^|\s)row(\s|$)/.test(body)) return full;
        const toks = body.split(/\s+/).filter(Boolean);
        const hasHint = toks.some((x) => /^(g[xy]?-[0-5]|col(-|$)|mb-|mt-|items-|align-items|justify-|flex|p[xy]?-|m[xy]?-)/.test(x));
        if (toks.length < 2 || !hasHint) {
            // class="row" on its own
            if (!(toks.length === 1 && /class\s*=\s*$/.test(''))) return full;
        }
        let gx = null, gy = null, g = null;
        const rest = [];
        for (const x of toks) {
            let m;
            if (x === 'row') { rest.push('grid', 'grid-cols-12'); continue; }
            if ((m = x.match(/^g-([0-5])$/))) { g = GAP[m[1]]; continue; }
            if ((m = x.match(/^gx-([0-5])$/))) { gx = GAP[m[1]]; continue; }
            if ((m = x.match(/^gy-([0-5])$/))) { gy = GAP[m[1]]; continue; }
            rest.push(x);
        }
        if (g !== null) { gx = gx ?? g; gy = gy ?? g; }
        const gaps = [];
        if (gx !== null && gx === gy) gaps.push('gap-' + gx);
        else { gaps.push('gap-x-' + (gx ?? '6')); if (gy !== null) gaps.push('gap-y-' + gy); }
        // keep `grid grid-cols-12` first, gaps after
        const gi = rest.indexOf('grid-cols-12') + 1;
        rest.splice(gi, 0, ...gaps);
        return q + rest.join(' ') + q;
    });
}

function colPass(text) {
    // Operates on quoted class lists containing col-* tokens.
    return text.replace(/(["'`])([^"'`\n]*?)\1/g, (full, q, body) => {
        if (!/(^|\s)col-/.test(body)) return full;
        const toks = body.split(/\s+/).filter(Boolean);
        const out = [];
        let base = false;
        const bps = [];
        for (const x of toks) {
            let m;
            if ((m = x.match(/^col-([1-9]|1[0-2])$/))) { base = true; out.push('col-span-' + m[1]); continue; }
            if ((m = x.match(/^col-(sm|md|lg|xl|xxl)-([1-9]|1[0-2])$/))) { bps.push(BP[m[1]] + ':col-span-' + m[2]); continue; }
            out.push(x);
        }
        if (!base && bps.length) out.unshift('col-span-12');
        out.push(...bps);
        return q + out.join(' ') + q;
    });
}

function badgePass(text) {
    return text.replace(/(["'`])([^"'`\n]*?)\1/g, (full, q, body) => {
        if (!/(^|\s)badge(\s|$)/.test(body)) return full;
        let b = body.replace(/(^|\s)(?:text-)?bg-(primary|success|danger|warning|info)(?=\s|$)/g, (_, s, c) => `${s}badge-${c}`);
        b = b.replace(/(^|\s)(?:text-bg-|bg-)(secondary|dark|light|body-tertiary|zinc-900|muted|subtle)(?=\s|$)/g, (_, s) => `${s}badge-secondary`);
        b = b.replace(/(^|\s)text-bg-(primary|success|danger|warning|info)(?=\s|$)/g, (_, s, c) => `${s}badge-${c}`);
        return q + b + q;
    });
}

function dataPass(text) {
    return text
        .replace(/data-bs-popup=("|')tooltip\1/g, 'data-fd-toggle=$1tooltip$1')
        .replace(/data-bs-popup=("|')popover\1/g, 'data-fd-toggle=$1popover$1')
        .replace(/data-bs-toggle=("|')pill\1/g, 'data-fd-toggle=$1tab$1')
        .replace(/data-bs-theme/g, 'data-theme')
        .replace(/data-bs-/g, 'data-fd-')
        .replace(/btn-flat-(white|secondary)/g, 'btn-ghost');
}

const SKIP_DIRS = new Set(['vendor', 'node_modules', '.git', 'storage']);

function walk(dir, acc) {
    for (const e of fs.readdirSync(dir, { withFileTypes: true })) {
        if (SKIP_DIRS.has(e.name)) continue;
        const p = path.join(dir, e.name);
        if (e.isDirectory()) walk(p, acc);
        else if (CLASS_FILES.test(e.name)) acc.push(p);
    }
}

const files = [];
walk(root, files);

let changed = 0;
const report = [];
for (const f of files) {
    if (/ui[\\/]design[\\/]/.test(f)) continue;
    const src = fs.readFileSync(f, 'utf8');
    let out = dataPass(src);
    out = replaceTokens(out);
    out = gridPass(out);
    out = colPass(out);
    out = badgePass(out);
    if (out !== src) {
        changed++;
        report.push(path.relative(root, f));
        if (WRITE) fs.writeFileSync(f, out);
    }
}
console.log((WRITE ? 'Rewrote ' : 'Would rewrite ') + changed + ' of ' + files.length + ' files');
if (!WRITE) console.log(report.join('\n'));
