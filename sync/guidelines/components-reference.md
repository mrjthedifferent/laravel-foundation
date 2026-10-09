## Components Reference

Every Blade component the package provides. Use these instead of writing the markup. Props are given as `name` (type, default). A prop passed with a
colon (`:rows="4"`) is a PHP expression; without one (`rows="4"`) it is a string. Anything else you add to the tag (`class`, `id`, `data-*`, `x-*`)
is merged onto the component's root element.

Conventions: see [`ui-components.md`](ui-components.md) · behaviour (`data-fd-*`, `swal-*`): [`frontend-js.md`](frontend-js.md) · dashboard pieces:
[`dashboard.md`](dashboard.md).

---

### Layouts

| Tag | Use |
|---|---|
| `<x-app-layout>` | The one authenticated shell. Slot `breadcrumbs` (anchors/`span.breadcrumb-item`), default slot = page content. Only needed in a project's own full-page views (e.g. `dashboard.blade.php`) |
| `<x-module-layout route="admin.things.index" :label="__('thing::thing.index.title')" />` | A module's `layouts/master.blade.php`, in one line. Pages `@extends('thing::layouts.master')` and fill `@section('breadcrumb')` and `@section('content')` |
| `<x-guest-layout>` | Sign-in, reset-password and similar pages (split aside + form) |

Stacks the layout renders: `@push('styles')`, `@push('scripts')` (after jQuery/Select2/SweetAlert load), `@push('head_scripts')`, `@push('modals')`
(modals placed outside the page flow), `@push('navbar_user_menu')` (extra items in the account menu).

---

### Page structure

#### `<x-page-header>`
The heading of every non-list page: icon tile, title, subtitle, actions, optional tabs. List pages put their title in the table card instead.

| Prop | Default | |
|---|---|---|
| `title` | `''` | Page title (`h1`) |
| `subtitle` | `null` | One line of context |
| `icon` | `null` | Phosphor class, e.g. `ph ph-gear` — shown in a tile |
| `backUrl` / `backLabel` | `null` | Adds a "Back" button |
| slot `actions` | | Buttons on the trailing edge. Inputs inside are 14rem wide |
| slot `tabs` | | Full-width row under the header |

```blade
<x-page-header title="Security" subtitle="Two-factor and sign-in rules" icon="ph ph-shield-check">
    <x-slot name="actions">
        <button type="submit" form="security-form" class="btn btn-primary"><i class="ph ph-floppy-disk"></i>Save changes</button>
    </x-slot>
</x-page-header>
```

#### `<x-search-card>`
The filter bar above a list. One slim row: a search box, a **Filters** button (with a count), Reset and Filter; the other fields live in a panel the button
opens; each active filter shows as a removable chip. Open/closed state is remembered per page. Wraps a `GET` form (keeps `per_page`).

| Prop / slot | |
|---|---|
| `resetRoute` | Where Reset goes (defaults to the current route without a query string) |
| default slot | The fields, as grid children (`col-span-12 md:col-span-3`). **Name the free-text field `search`** and it is moved into the bar |
| slot `wide` | Full-width filters that get their own row in the panel |

```blade
<x-search-card>
    <div class="col-span-12 md:col-span-3">
        <x-form.input name="search" label="Search" :value="request('search')" placeholder="Name, email…" />
    </div>
    <div class="col-span-12 md:col-span-3">
        <x-form.select class="select" name="is_active" label="Status" :options="['' => 'All', '1' => 'Active', '0' => 'Inactive']"
            :selected="request('is_active')" data-placeholder="All" />
    </div>
</x-search-card>
```
Read filters with `request($name)`, never `old()`.

#### `<x-table-view-pagination>`
Card + table + count badge + footer (count, per-page select, paginator) + empty state. On phones each row stacks into labelled cells.

| Prop | Default | |
|---|---|---|
| `title` | `''` | Card title |
| `data` | `null` | A paginator **or** a collection/array |
| `emptyMessage` / `emptyIcon` | translated / `ph ph-tray` | The empty state |
| `stack` | `true` | On phones each row becomes labelled cells; `:stack="false"` keeps a wide table a table that scrolls sideways |

Slots: default (the `<thead>`/`<tbody>`), `actions` (header buttons, usually `<x-table-actions>`), `exports`, `tabs`, `emptyAction` (a button inside the
empty state).
Use `w-8` / `w-10` / `w-12` for column widths and `text-end` for the action column. See `ui-components.md` for a full example.

#### `<x-table-actions>` / `<x-table-action>`
Header action group. Past `limit` (default 2) actions, the rest collapse into a three-dot menu.

| Tag | Props |
|---|---|
| `<x-table-actions :limit="2">` | `limit` |
| `<x-table-action :href="route(…)" icon="ph ph-plus" title="Add" class="btn-primary">` | `href` (renders `<a>`, else `<button type=…>`), `type`, `class` (variant, default `btn-primary`), `icon`, `title` (also the tooltip). Extra attributes such as `data-url` / `data-text` with a `swal-post` class work |

#### `<x-dropdown-menu>` / `<x-dropdown-link>`
The three-dot row menu. `<x-dropdown-menu label="Actions">` wraps items; `<x-dropdown-link :url="…" class="swal-delete text-danger" data-text="…">` is an
anchor item. A non-link item is `<button type="button" class="dropdown-item">`. Separator: `<div class="dropdown-divider"></div>`.

#### `<x-table-export-dropdown>` / `<x-table-export-item>`
`<x-table-export-dropdown>` is the export menu for the `exports` slot; each `<x-table-export-item :href="…" icon="ph ph-file-xls" title="Excel" />` is an entry.

#### `<x-modal>`
| Prop | Default | |
|---|---|---|
| `id` | `modal` | Target id for `data-fd-target="#id"` |
| `title` | translated | Header text |
| `size` | `''` | `sm` · `lg` · `xl` |
| `static` | `false` | Backdrop click and Esc do not close it |
| `scrollable` | `true` | Body scrolls inside the dialog |
| slot `footer` | | Buttons |

Open with `data-fd-toggle="modal" data-fd-target="#id"`, close with `data-fd-dismiss="modal"`, or `$('#id').modal('show')` / `Foundation.modal(el).show()`.

#### `<x-alert>`
`<x-alert type="info|success|warning|danger|primary" icon="ph-…" :dismissible="true">Message</x-alert>` — inline notice (use flash messages for results of actions).

---

### Forms

All field components render the label, the control, old-input repopulation, the `is-invalid` state and the error message. Pass the current value
explicitly (`:value`, `:selected`); there is no model binding.

| Tag | Props |
|---|---|
| `<x-form.input>` | `name`, `label`, `value`, `type` (`text` default · `email` `number` `date` `password` `hidden`…), `required`, `help` |
| `<x-form.select>` | `name`, `options` (`[value => label]`), `label`, `selected` (an enum is unwrapped), `required`, `help`, `multiple` (name needs **no** `[]`). Add `class="select"` for Select2 and `data-placeholder` |
| `<x-form.textarea>` | `name`, `label`, `value`, `required`, `help`, `rows` (3) |
| `<x-form.file>` | `name`, `label`, `required`, `help`, `current` (URL of the stored file, shown as the preview). Renders a drop zone around the real `<input type=file>`; forward `accept`, `id` |
| `<x-form.checkbox>` | `name`, `label`, `value` (1), `checked` |
| `<x-form.label>` | `for`, `required` — a standalone label |
| `<x-form-section title="…" icon="ph-…">` | A titled card grouping fields; optional slot `badge` |
| `<x-text-input>` / `<x-input-label>` / `<x-input-error :messages="$errors->get('x')">` | The low-level pieces `x-form.*` is built from; use for hand-built fields (password with an eye toggle) |
| `<x-primary-button>` / `<x-secondary-button>` / `<x-danger-button>` / `<x-link-button :href="…">` | `btn-primary` / `btn-secondary` / `btn-danger` buttons |

Form footers: `<div class="fd-form-actions">` (sticky bar) with `<a class="btn btn-light">Cancel</a>` and `<x-primary-button>Save</x-primary-button>`.
Field grids: `<div class="fd-fields fd-fields-3">` (1 column on phones, 2 from tablet, 3 on wide; `fd-fields-wide` on a child spans the row).

#### `<x-profile-section id="…" title="…" description="…" icon="ph-…" tone="danger">`
A card in a sectioned page with a left section nav (`.fd-form-layout` + `.fd-form-nav` + `.fd-form-sections`); see `resources/views/profile/edit.blade.php` for the
pattern. Its body is a grid; put fields in `.fd-fields`.

---

### Data display

| Tag | Props / notes |
|---|---|
| `<x-status-badge :active="$m->is_active" active-label="…" inactive-label="…" />` | `.fd-status` dot + translated label |
| `<x-stat-card label value icon color href change :change-up caption :series>` | KPI tile. `color`: `primary success warning danger info secondary`. `series`: list of ints, draws a sparkline |
| `<x-empty-state icon="ph ph-users" title="No users" text="Add the first one">` + slot | The one empty state; the slot is the next step (a button) |
| `<x-skeleton :rows="3" :avatar="true" />` | Shimmering placeholder while a list loads |
| `<x-truncated-text :text="…" :limit="50" />` | Truncates with a tooltip holding the full text |
| `<x-image src="…" alt="…" :max-width="40" />` | A bounded image |
| `<x-sparkline :series="[1,4,2]" />` | Tiny trend line (decorative) |
| `<x-chart-area :series="$day=>count" :previous="[…]" label="…" />` | Area chart; `previous` draws the comparison line on the same scale |
| `<x-chart-bar :series="['Label' => 12]" label="…" />` | Ranked horizontal bars |
| `<x-chart-donut :parts="[['label'=>'Paid','value'=>8,'tone'=>'success']]" label="…" />` | Donut; tones `accent success warning danger info muted` |
| `<x-chart-heatmap :matrix="$weekdayByHour" label="…" />` | 7×24 grid, Monday first; build the matrix with `HourlySeries` |

All charts are inline SVG/HTML with a screen-reader summary and take their colours from the theme tokens — no charting library.

---

### PHP helpers (views and controllers)

| Function | Returns |
|---|---|
| `integerStatus()` | `['1' => 'Active', '0' => 'Inactive']` |
| `getParPagePaginate()` / `perPage()` / `cappedPerPage()` | Per-page options / the current page size / the size clamped to `foundation.pagination.max` |
| `getUrlFromPath($path)` | Public URL for a stored path |
| `display_label($value)` | Translated label for config/database-sourced names |
| `form_old_key($name)` | `roles[]` → `roles` (used by the form components) |
| `escapeLike($s)` · `snakeCase($s)` | Query and key helpers |
| `appName()` · `mailAppName()` · `mailLogoUrl()` | Application name and logo |
