## Theming, Tokens and Utilities

All colour, type, spacing, corner and shadow values in the admin UI come from CSS custom properties, `--fd-*`, defined in the package's stylesheet. The
colour mode, the accent palette, the density, the corner style and the page width are **settings** (Settings → Theme) that change those tokens for every
page at once. Write against the tokens and your code follows the theme for free; a hard-coded value does not.

### Theme settings

| Setting | Values | Applied as |
|---|---|---|
| Colour mode | `light` · `dark` · `auto` | `<html data-theme>` |
| Direction | `ltr` · `rtl` | `<html dir>` |
| Accent | `indigo` (shown as "Brand": the project's brand colours) `blue` `violet` `teal` `green` `amber` `rose` `slate`, or `custom` + a hex and an optional second hex (the gradient's far end) | `<html data-color-palette>` (+ `--custom-primary*`, `--custom-primary-2`) |
| Sidebar colour / type | `brand` (default: a deep brand gradient in both modes) `light` `dark` / `default` `mini` | `sidebar-brand` / `-light` / `-dark` classes |
| Page width | `fluid` (default) · `boxed` (max 1600px) | `<html data-width>` → `--fd-content-max` |
| Density | `comfortable` · `compact` | `<html data-density>` → control height, field and cell padding, card padding |
| Corners | `sharp` · `rounded` · `soft` | `<html data-radius>` → `--fd-radius-sm/-/-lg` |

Stored as `theme_*` settings, resolved by `Mrj\Foundation\Services\ThemeResolver` (invalid values fall back to the defaults). Do not build settings or UI
that assume a layout picker, navbar colours or a font picker — they do not exist; the type is Inter everywhere.

### The project's brand

A project sets its brand in one place, its `resources/css/app.css` (after the package import), then `npm run build`:

```css
:root {
    --fd-brand: #0f766e;      --fd-brand-rgb: 15, 118, 110;      /* primary */
    --fd-brand-2: #06b6d4;                                       /* the gradient's second colour */
    --fd-brand-dark: #2dd4bf; --fd-brand-dark-rgb: 45, 212, 191; /* primary on dark surfaces */
}
```

The sidebar, the active item, buttons, the hero stat card, chart lines, links, focus rings and tints all derive from these. Keep each `-rgb` triplet in
step with its hex. They apply while Settings → Theme → Accent is the default (`indigo`, shown as "Brand"); another accent, or `custom`, overrides them at
run time without a build. Never restyle components to change the brand.

### Tokens you may use (`var(--fd-…)`)

| Group | Tokens |
|---|---|
| Surfaces | `canvas` (page) · `surface` (cards) · `subtle` · `hover` |
| Lines | `border` · `border-strong` |
| Text | `text` · `text-strong` · `muted` · `faint` |
| Brand | `brand` `brand-2` `brand-dark` (+ `-rgb`) · `on-brand` · `accent-2` · `gradient` (135°) · `gradient-x` (90°) · `glow` · `on-brand-muted/-faint/-tint/-sheen/-hairline` (overlays on the gradient) |
| Accent | `accent` · `accent-hover` · `accent-subtle` · `accent-muted` · `accent-text` · `accent-solid` · `on-accent` · `accent-rgb` (for `rgba(var(--fd-accent-rgb), .2)`) |
| Status | `success` `warning` `danger` `info`, each with `-rgb`, `-subtle`, `-border`, `-text` |
| Charts | `chart-1` … `chart-4` (+ `-rgb`; categorical, colourblind-safe, in series order) · `on-chart` · `on-chart-4` (type on a solid series colour) |
| Type | `font` · `mono` · `text-xs` (12px) `text-sm` `text-base` (14px) `text-md` `text-lg` `text-xl` `text-2xl` |
| Shape | `radius-sm` · `radius` · `radius-lg` · `radius-chip` · `radius-pill` · `shadow-xs/-sm/-md/-lg` · `shadow-card` |
| Rhythm | `control-h` · `control-h-sm` · `field-y` · `cell-y` · `card-pad` · `gutter` · `content-max` |
| Motion | `ease` · `duration` |
| Shell | `sidebar-width` · `sidebar-mini-width` · `navbar-height` · `footer-height` · `topbar-control` · `sidebar-from/-to` · `auth-from/-to` |

Never hard-code a colour, a radius or a control height. Use `color-mix(in srgb, var(--fd-accent) 20%, transparent)` rather than a literal tint.

### Utilities: what is available

Layout and spacing use **Tailwind utility classes** — but the stylesheet is compiled once, inside the package, so **only classes the package itself uses
(plus the safelist below) exist**. A class not in that set silently does nothing in a project.

Always available (the layout groups also take the responsive prefixes `sm:` `md:` `lg:` `xl:` `2xl:`; breakpoints 576 / 768 / 992 / 1200 / 1400):

- **Spacing:** `m`/`p` + `t b s e x y` + `0 1 2 3 4 5 6 8 10 12 16 20 24` (`mb-4`, `px-2`, `pt-10`), `m*-auto` (`ms-auto`, `mx-auto`), `gap`, `gap-x`, `gap-y` (`0`–`6`, `8`, `10`, `12`)
- **Display, flex, grid:** `flex inline-flex grid inline-grid block inline-block inline hidden contents table`, `flex-col/row(-reverse)`, `flex-wrap/nowrap`, `flex-1/auto/none`, `grow shrink(-0)`, `items-* justify-* content-* self-*` (`start center end between around evenly stretch baseline`), `grid-cols-1…6,12`, `col-span-1…12,full`, `col-start-*`, `row-span-1…4`, `order-*`
- **Sizing:** `w-`/`h-` + `full auto screen 1/2 1/3 2/3 1/4 3/4` and `0–6 8 10 12 16 20 24 32 40 48 56 64`, `min-w-0`, `min-h-0`, `max-w-full`, `max-w-xs … max-w-7xl`, `aspect-square`
- **Position and overflow:** `static relative absolute fixed sticky`, `inset-0`, `top-0 bottom-0 start-0 end-0`, `z-0 10 20 30 40 50`, `overflow-auto/hidden/visible/scroll` (+ `x-`, `y-`)
- **Typography:** `text-start/end/center`, `text-xs sm base md lg xl 2xl`, `font-light normal medium semibold bold mono`, `leading-*`, `tracking-*`, `truncate whitespace-nowrap uppercase lowercase capitalize italic underline no-underline tabular-nums break-words`
- **Colour (theme tokens):** `text-body strong muted faint primary primary-text success warning danger info` (+ `-text`), `bg-surface subtle canvas hover primary primary-subtle success-subtle warning-subtle danger-subtle info-subtle transparent`, `border-line line-strong primary success warning danger info transparent`
- **Borders and effects:** `border border-0 border-2 border-t/b/s/e`, `border-dashed`, `rounded(-t/-b/-s/-e)-none sm md lg xl full`, `shadow-none xs sm md lg`, `opacity-0 25 50 75 100`, `cursor-pointer`, `pointer-events-none`, `select-none`, `sr-only`, `hover:bg-hover hover:text-strong hover:underline`, `print:hidden`

The list lives in `ui/resources/css/safelist.css` (package maintainers add to it when a project needs a class the package does not use itself).

Anything else a package view happens to use also exists, but do not rely on that: **if you need a
rule the list above does not cover, write plain CSS** in the project's `resources/css/app.css` (or `@push('styles')` for one page), using the tokens:

```css
/* resources/css/app.css — after @import '@foundation/css/app.css'; */
.invoice-total { font-variant-numeric: tabular-nums; color: var(--fd-text-strong); border-top: 1px solid var(--fd-border); padding-top: var(--fd-card-pad); }
```

Colour utilities map to tokens: `bg-surface`, `bg-subtle`, `bg-canvas`, `text-muted`, `text-strong`, `text-primary`, `border-line`, and the status colours
`success warning danger info` (with `-text`, `-subtle` variants, e.g. `bg-danger-subtle text-danger-text`).

### Component classes (appearance)

These are written once in the package; use the class, do not restyle it: `.btn` (+ `btn-primary` `btn-light` `btn-ghost` `btn-danger` `btn-success`
`btn-warning` `btn-info` `btn-link` `btn-sm` `btn-lg` `btn-icon`, `btn-outline-primary|success|danger|warning|info`), `.card` (`card-header`, `card-body`,
`card-title`, `card-footer`), `.table` (`table-hover`, `table-xs`, `table-nowrap`, `table-striped`, `table-responsive`), `.badge` (`badge-primary` `badge-success`
`badge-danger` `badge-warning` `badge-info` `badge-secondary`, `badge-count`), `.alert` (`alert-success|warning|danger|info`), `.form-control`, `.form-select`,
`.form-check`, `.form-switch`, `.input-group`, `.dropdown-menu`/`-item`, `.modal`/`.offcanvas`, `.nav-tabs`/`.nav-pills`, `.pagination`, `.breadcrumb`,
`.list-group`, `.progress`, `.spinner-border`, and the `.fd-*` vocabulary (`fd-icon-tile`, `fd-avatar`, `fd-status`, `fd-empty`, `fd-dl`, `fd-feed`, `fd-overline`,
`fd-stat*`, `fd-segmented`, `fd-chip`, `fd-drop`, `fd-fields`, `fd-form-actions`, …) listed in `ui-components.md`.

### The sign-in page

Every guest page (sign-in, password reset, 2FA, email verification) has a brand panel beside the form. Do not override `layouts/guest.blade.php`
to change it; three layers decide it, first match wins:

1. **Settings → Login page** in the admin (`admin.settings.special.login_page`, permission `Edit Special Setting`): headline, subheadline, feature
   bullets (icon + text), mark (`auto` / `monogram` / `none`), app name on/off, graphic (`bars` / `glow` / `none`). Stored as `login_panel_*` settings.
2. **`config/foundation-login.php`** (`php artisan vendor:publish --tag=foundation-login`): the project's defaults for the same keys. Text values go
   through `__()`, so they are translation keys or plain text, with `:app` as the app name.
3. **Translations** (`foundation::foundation.auth.tagline`, `tagline_meta`, `highlight_*`): what a `null` text falls back to, in every locale.

### Dark mode and RTL

- Dark mode flips the tokens; a view needs nothing. Test both. Do not branch on the mode in markup.
- RTL: use logical properties and utilities (`ms-` `me-` `ps-` `pe-` `start` `end`, `text-start`/`text-end`, `border-s`/`border-e`, `inset-inline-*`), never `left`/`right`/`ml-`/`mr-`. Directional icons flip themselves where the package draws them.

### Package CSS source (for package maintainers only)

`ui/resources/css/foundation/*.css` (tokens, base, components, helpers, shell, dashboard, widgets, filterbar, tables, forms, vendor) are compiled with Tailwind by
`bin/build-css.sh` into `ui/public/assets/css/foundation.css`, which is **committed**. After changing any view, JS string or CSS source in the package, run
`bash bin/build-css.sh`; CI fails on `bin/build-css.sh --check` if the committed file is stale.
