## Theming, Tokens and Utilities

All colour, type, spacing, corner and shadow values in the admin UI come from CSS custom properties, `--fd-*`, defined in the package's stylesheet. The
colour mode, the accent palette, the density, the corner style and the page width are **settings** (Settings → Theme) that change those tokens for every
page at once. Write against the tokens and your code follows the theme for free; a hard-coded value does not.

### Theme settings

| Setting | Values | Applied as |
|---|---|---|
| Colour mode | `light` · `dark` · `auto` | `<html data-theme>` |
| Direction | `ltr` · `rtl` | `<html dir>` |
| Accent | `indigo` `blue` `violet` `teal` `green` `amber` `rose` `slate`, or `custom` + a hex | `<html data-color-palette>` (+ `--custom-primary*`) |
| Sidebar colour / type | `light` `dark` / `default` `mini` | sidebar classes |
| Page width | `fluid` (default) · `boxed` (max 1600px) | `<html data-width>` → `--fd-content-max` |
| Density | `comfortable` · `compact` | `<html data-density>` → control height, field and cell padding, card padding |
| Corners | `sharp` · `rounded` · `soft` | `<html data-radius>` → `--fd-radius-sm/-/-lg` |

Stored as `theme_*` settings, resolved by `Mrj\Foundation\Services\ThemeResolver` (invalid values fall back to the defaults). Do not build settings or UI
that assume a layout picker, navbar colours or a font picker — they do not exist; the type is Inter everywhere.

### Tokens you may use (`var(--fd-…)`)

| Group | Tokens |
|---|---|
| Surfaces | `canvas` (page) · `surface` (cards) · `subtle` · `hover` |
| Lines | `border` · `border-strong` |
| Text | `text` · `text-strong` · `muted` · `faint` |
| Accent | `accent` · `accent-hover` · `accent-subtle` · `accent-muted` · `accent-text` · `accent-solid` · `on-accent` · `accent-rgb` (for `rgba(var(--fd-accent-rgb), .2)`) |
| Status | `success` `warning` `danger` `info`, each with `-rgb`, `-subtle`, `-border`, `-text` |
| Type | `font` · `mono` · `text-xs` (12px) `text-sm` `text-base` (14px) `text-md` `text-lg` `text-xl` `text-2xl` |
| Shape | `radius-sm` · `radius` · `radius-lg` · `shadow-xs/-sm/-md/-lg` |
| Rhythm | `control-h` · `control-h-sm` · `field-y` · `cell-y` · `card-pad` · `gutter` · `content-max` |
| Motion | `ease` · `duration` |
| Shell | `sidebar-width` · `sidebar-mini-width` · `navbar-height` · `footer-height` |

Never hard-code a colour, a radius or a control height. Use `color-mix(in srgb, var(--fd-accent) 20%, transparent)` rather than a literal tint.

### Utilities: what is available

Layout and spacing use **Tailwind utility classes** — but the stylesheet is compiled once, inside the package, so **only classes the package itself uses
(plus a safelist) exist**. A class not in that set silently does nothing in a project.

Always available (every breakpoint prefix `sm:` `md:` `lg:` `xl:` `2xl:` works for the first four rows; breakpoints 576 / 768 / 992 / 1200 / 1400):

- Spacing `m`/`p` + `t b s e x y` + `0 1 2 3 4 6 8 12` (`mb-4`, `px-2`, `ms-auto`, `mx-auto`)
- Display and flex/grid: `flex inline-flex grid block hidden flex-col flex-row flex-wrap grow shrink-0 items-* justify-* gap-0 1 2 4 6 12 grid-cols-12`, `col-span-1…12`
- Text and sizing: `text-start/end/center`, `truncate`, `whitespace-nowrap`, `uppercase`, `font-medium/semibold/bold/mono`, `text-xs/sm/base/md/lg`, `w-full h-full w-auto min-w-0 max-w-full`, `overflow-*`, `relative absolute fixed sticky`
- Colour and shape: `text-muted text-strong text-primary text-success text-warning text-danger text-info`, `bg-surface bg-subtle bg-canvas`, `border border-t/b/s/e border-0 border-line`, `rounded-sm/md/lg/full`, `shadow-sm/md/lg`, `sr-only cursor-pointer`

Anything else a package view happens to use also exists (for example `w-8 w-10 w-12 h-6 h-8 w-50 flex-1`), but do not rely on that: **if you need a
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

### Dark mode and RTL

- Dark mode flips the tokens; a view needs nothing. Test both. Do not branch on the mode in markup.
- RTL: use logical properties and utilities (`ms-` `me-` `ps-` `pe-` `start` `end`, `text-start`/`text-end`, `border-s`/`border-e`, `inset-inline-*`), never `left`/`right`/`ml-`/`mr-`. Directional icons flip themselves where the package draws them.

### Package CSS source (for package maintainers only)

`ui/resources/css/foundation/*.css` (tokens, base, components, helpers, shell, dashboard, widgets, filterbar, tables, forms, vendor) are compiled with Tailwind by
`bin/build-css.sh` into `ui/public/assets/css/foundation.css`, which is **committed**. After changing any view, JS string or CSS source in the package, run
`bash bin/build-css.sh`; CI fails on `bin/build-css.sh --check` if the committed file is stale.
