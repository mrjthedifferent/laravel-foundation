## UI Components & Blade Guidelines

> **Theme:** Laravel Foundation (`assets/css/foundation.css`) — design tokens over stock Bootstrap 5.3  
> **Icons:** Phosphor (`ph-*`) — the only icon set · **Fonts:** Inter  
> **JS:** jQuery · Bootstrap bundle · Alpine · Select2 · SweetAlert2

See [`views.md`](views.md) for full index/create/edit page templates. This file covers the component API, CSS patterns, and conventions that apply across all views.

---

### The Stylesheet

`assets/css/foundation.css` ships with the package. It is a token layer, not a theme fork:

1. **Tokens** — `--fd-*` variables for neutrals, accent, semantic colours, type scale, radius, elevation, motion and the shell's dimensions, in light and dark.
2. **Bootstrap bindings** — Bootstrap's own `--bs-*` variables are pointed at those tokens, so plain `.btn`, `.card`, `.table`, `.badge`, `.form-control`, `.modal` and friends already look right. Nothing in a view needs a custom class to get the theme.
3. **Primitives + component vocabulary** — Bootstrap's components restyled, then the `.fd-*` classes listed below for the pieces Bootstrap has no name for (page heads, stat tiles, empty states, status dots, …).
4. **The shell and overlays** — sidebar, navbar, footer, the ⌘K palette, SweetAlert2 dialogs and toasts, and the guest/error pages.

Three rules follow from that:

- **Never add a rule to `foundation.css`.** It is package-owned and overwritten on every package update — edits are lost silently. Project-specific CSS goes in the project's own `resources/css/app.css` (bundled by Vite), or in `@push('styles')` for a single page.
- **Never write an inline `style=` attribute.** Use a Bootstrap utility, one of the `.fd-*` classes, or a sizing helper (`w-32px` `w-40px` `w-48px` `h-24px` `h-32px` `h-40px` `h-48px` `w-sm` `min-width-0` `flex-1` `fs-xs` `fs-sm` `fs-base` `fs-lg`).
- **Never hard-code a colour.** Read a token: `var(--fd-accent)`, `var(--fd-text-strong)`, `var(--fd-muted)`, `var(--fd-border)`, `var(--fd-surface)`, or the Bootstrap aliases `var(--bs-success-bg-subtle)` / `var(--bs-danger-text-emphasis)`. Tokens flip with the colour mode and the accent palette; a literal hex does not.

---

### The Shell

There is **one** layout, `<x-app-layout>` — no layout variants to choose between. It renders:

- **Sidebar** — the brand at the top, the navigation in the middle, the signed-in user at the foot. Only the navigation (`.sidebar-content`) scrolls; the brand row and the user card stay put. Light or dark, full width or mini.
- **Navbar** — the ⌘K search trigger (`#globalSearchTrigger`, which opens the command palette built in `resources/js/navigation-search.js`), the notifications bell and the account menu.
- **Breadcrumb row + content**, inside `.content-inner` — **the only element on the page that scrolls.**
- **Footer** — outside the scrolling column, so the credits sit on the bottom edge of the shell at all times.

A page never writes shell markup: no navbar, no sidebar, no footer, no `<html>`/`<body>`. It fills the content slot and declares its sidebar entry in `config/menu.php` (see `views.md`).

Because only `.content-inner` scrolls, `position: sticky` inside the content works against that column — `.sticky-top` on a save bar sticks to the top of the content area, not the viewport.

**Theme options** (the floating gear opens the quick panel; Settings → Theme has the full page) were curated down to five:

| Option | Values |
|---|---|
| Colour mode | `light` · `dark` · `auto` |
| Direction | `ltr` · `rtl` |
| Accent | `indigo` `blue` `violet` `teal` `green` `amber` `rose` `slate` · or `custom` + a hex |
| Sidebar colour | `light` · `dark` |
| Sidebar type | `default` · `mini` |

There is no layout picker, no navbar colour, no per-element colour override and no font picker — the type is Inter everywhere. Don't build UI or settings that assume any of those exist.

---

### Asset Stacks

Use `@push` / `@stack` for per-page assets — never bare `<style>` or `<script>` tags at the top level of a view:

```blade
@push('styles')
    <link rel="stylesheet" href="...">
@endpush

@push('scripts')
<script>
$(document).ready(function () { ... });
</script>
@endpush
```

---

### Component Vocabulary (`.fd-*`)

These are the classes a view is expected to use. Everything else it needs is a stock Bootstrap class.

| Class | What it is |
|---|---|
| `fd-page-head` · `-main` · `fd-page-title` · `fd-page-subtitle` · `fd-page-actions` | The page heading row. Rendered by `<x-page-header>` — use the component, not the markup |
| `fd-stat` · `-head` · `-label` · `-value` · `-foot` | KPI tile inside a `.card`. `fd-stat-value`/`fd-stat-label` are also fine on their own for a dashboard widget's inline numbers |
| `fd-delta` + `is-up` \| `is-down` \| `is-flat` | The trend pill next to a stat |
| `fd-icon-tile` (+ `fd-icon-tile-sm` / `-lg`) + `is-success` \| `is-warning` \| `is-danger` \| `is-info` \| `is-neutral` | A tinted square holding one icon. Accent-tinted with no `is-*` modifier |
| `fd-overline` | Small uppercase muted label above a group of fields, rows or numbers |
| `fd-avatar` (+ `fd-avatar-sm` / `-lg`) | Round avatar — works on an `<img>` **and** on a `<span>` holding initials |
| `fd-status` + `is-success` \| `is-warning` \| `is-danger` \| `is-info` | Coloured dot + label. Rendered by `<x-status-badge>` for the active/inactive case |
| `fd-empty` · `-icon` · `-title` · `-text` | Empty state. A `.btn` inside it is spaced automatically |
| `fd-dl` | `<dl>` of label/value rows for a detail page — a grid with hairlines, no table needed |
| `fd-feed` · `-body` · `-meta` | `<ul>` activity feed: badge/icon, text, timestamp |
| `fd-toolbar` · `fd-toolbar-search` | A filter/bulk-action row inside a card, above the table |
| `fd-table-foot` | The row under a table holding the count, the per-page select and the paginator. Rendered by `<x-table-view-pagination>` |
| `fd-form-section` · `-title` · `-text` | Settings-style form section: title and help on the left, fields on the right at ≥992 px. For long configuration forms — a create/edit form uses `<x-form-section>` |
| `fd-kbd` | A keyboard key cap (`Ctrl`, `K`, `Esc`) |
| `fd-divider` | A horizontal rule with a label in the middle |
| `fd-choice` | A pickable card wrapping a radio/checkbox; add `is-selected` to the chosen one |
| `fd-swatch` + `fd-swatch-dot` | A pickable colour pill; add `is-selected` to the chosen one |
| `fd-scroll-y` | Caps a block at ~17.5 rem and scrolls it |
| `text-strong` | The strongest text colour (`--fd-text-strong`), for a value that must out-weigh its label |

```blade
{{-- Detail page: label/value rows --}}
<dl class="fd-dl">
    <dt>{{ __('foundation::foundation.common.name') }}</dt>
    <dd>{{ $user->name }}</dd>
    <dt>{{ __('foundation::foundation.common.status') }}</dt>
    <dd><x-status-badge :active="$user->is_active" /></dd>
</dl>

{{-- Activity feed inside a card body --}}
<div class="fd-overline mb-1">{{ __('thing::thing.widget.recent') }}</div>
<ul class="fd-feed">
    @foreach ($events as $event)
        <li>
            <span class="badge bg-secondary-subtle text-secondary-emphasis">{{ $event->type }}</span>
            <span class="fd-feed-body text-truncate">{{ $event->title }}</span>
            <span class="fd-feed-meta">{{ $event->created_at->diffForHumans() }}</span>
        </li>
    @endforeach
</ul>

{{-- Empty state written by hand (inside a tab pane, a widget, an AJAX target) --}}
<div class="fd-empty">
    <span class="fd-empty-icon"><i class="ph-folder-open"></i></span>
    <div class="fd-empty-title">{{ __('thing::thing.index.empty') }}</div>
    <p class="fd-empty-text">{{ __('thing::thing.index.empty_help') }}</p>
</div>

{{-- Toolbar above a table that filters client-side --}}
<div class="fd-toolbar">
    <div class="fd-toolbar-search">
        <i class="ph-magnifying-glass"></i>
        <input type="text" id="thing-search" class="form-control"
               placeholder="{{ __('thing::thing.index.search_placeholder') }}">
    </div>
    <select id="group-filter" class="form-select">
        <option value="">{{ __('thing::thing.index.all_groups') }}</option>
    </select>
</div>

{{-- Long settings form: description left, fields right --}}
<div class="fd-form-section">
    <div>
        <h2 class="fd-form-section-title">{{ __('thing::thing.settings.throttle_title') }}</h2>
        <p class="fd-form-section-text">{{ __('thing::thing.settings.throttle_help') }}</p>
    </div>
    <div class="row g-3">
        <div class="col-md-6">
            <x-form.input type="number" name="throttle_minutes" :label="__('thing::thing.settings.throttle_label')" :value="$throttle" />
        </div>
    </div>
</div>
```

---

### Blade Components — Quick Reference

| Component | Tag | Purpose |
|---|---|---|
| App layout | `<x-app-layout>` | Root authenticated layout (used in module `master.blade.php` only) |
| Module layout | `<x-module-layout route="" :label="">` | A module's `layouts/master.blade.php`, in one line |
| Page header | `<x-page-header>` | `.fd-page-head`: icon tile + title + subtitle, `$actions` slot, optional back button |
| Form section | `<x-form-section>` | Titled card grouping related form fields |
| Search card | `<x-search-card>` | GET filter form with Reset (`btn-light`) + Filter (`btn-primary`) buttons |
| Table view | `<x-table-view-pagination>` | Card + table + count badge + `.fd-table-foot` + `.fd-empty` state |
| Stat card | `<x-stat-card>` | `.fd-stat` KPI tile: label, value, icon tile, trend, caption |
| Status badge | `<x-status-badge :active="">` | `.fd-status` dot + translated Active/Inactive label |
| Modal | `<x-modal>` | Bootstrap modal dialog |
| Dropdown menu | `<x-dropdown-menu>` | Three-dot action menu trigger |
| Dropdown link | `<x-dropdown-link :url="">` | Anchor item inside `<x-dropdown-menu>` |
| Table actions | `<x-table-actions :limit="">` | Card-header action group; overflow past `limit` collapses into a menu |
| Table action | `<x-table-action :href="" icon="" title="">` | One `btn-sm` action inside `<x-table-actions>` |
| Export dropdown | `<x-table-export-dropdown>` / `<x-table-export-item>` | Export menu for the `$exports` slot |
| Alert | `<x-alert type="" :dismissible="">` | Inline `alert` with a matching Phosphor icon |
| Primary button | `<x-primary-button>` | `btn btn-primary` submit button |
| Secondary button | `<x-secondary-button>` | `btn btn-secondary` type=button |
| Danger button | `<x-danger-button>` | `btn btn-danger` submit button |
| Link button | `<x-link-button :href="">` | `btn btn-secondary` anchor tag |
| Text input | `<x-text-input>` | `form-control` input |
| Input label | `<x-input-label>` | `form-label` label |
| Input error | `<x-input-error :messages="">` | `invalid-feedback` validation error list |
| Truncated text | `<x-truncated-text :text="" :limit="">` | Truncated text with full-text tooltip |
| Image | `<x-image :src="" alt="" :max-width="">` | Responsive `img-fluid` image |
| Form input | `<x-form.input>` | Labeled text/email/number/date/password/hidden input, old-input + error handling built in |
| Form select | `<x-form.select>` | Labeled `<select>`, single or `multiple`, unwraps enum values |
| Form textarea | `<x-form.textarea>` | Labeled `<textarea>` |
| Form file | `<x-form.file>` | Labeled file input |
| Form checkbox | `<x-form.checkbox>` | Bootstrap `form-check` checkbox |
| Form label | `<x-form.label>` | Standalone `form-label`, `required` prop adds the asterisk |

---

### `<x-page-header>`

Renders `.fd-page-head`: the icon in a large tile, the title, the optional subtitle, then the actions and the back button on the trailing edge.

Props:

| Prop | Type | Default | Description |
|---|---|---|---|
| `title` | string | `''` | Page heading (`.fd-page-title`) |
| `subtitle` | string\|null | `null` | Muted sub-line (`.fd-page-subtitle`) |
| `icon` | string\|null | `null` | Phosphor icon class, e.g. `ph-users-four` — rendered in a `.fd-icon-tile-lg` |
| `backUrl` | string\|null | `null` | Renders a `btn-light` back button when set |
| `backLabel` | string\|null | `null` | Back button label; falls back to the translated "Back" |
| `$actions` | slot | — | Buttons/badges before the back button |

```blade
{{-- Index page --}}
<x-page-header title="Users" subtitle="Manage system users" icon="ph-users-four" />

{{-- Create/edit: context on the right, the submit button in the bottom row --}}
<x-page-header :title="$user->name" icon="ph-pencil-simple"
    :back-url="route('admin.users.index')" back-label="Back to List">
    <x-slot name="actions">
        <span class="badge bg-primary">Admin</span>
        <x-status-badge :active="$user->is_active" class="fs-xs" />
    </x-slot>
</x-page-header>
```

> The `$actions` slot holds context — badges, status, an avatar, a secondary action. The submit button goes in the form's bottom row (see **Forms**), so the page's primary action sits at the end of the fields the user just filled in.

---

### `<x-table-view-pagination>`

Card + table when there are rows, `.fd-empty` when there are none. The card has no minimum height, so an empty list is a short card, not a tall blank one.

Props:

| Prop | Default | Description |
|---|---|---|
| `title` | `''` | Card header title |
| `data` | `null` | Paginator **or** Collection/array |
| `emptyMessage` | translated default | Empty state text |
| `emptyIcon` | `'ph-tray'` | Phosphor icon for the empty state |

Slots:

| Slot | Description |
|---|---|
| `$slot` | The `<thead>` / `<tbody>` — rendered only when there are rows |
| `$actions` | Header buttons (trailing edge) |
| `$exports` | Export dropdown, after the actions |
| `$tabs` | `nav-tabs` in the card header |
| `$emptyAction` | A button shown inside the empty state, e.g. "Create the first one" |

```blade
<x-table-view-pagination title="Users" :data="$users"
    empty-message="No users found" empty-icon="ph-users">

    <x-slot name="actions">
        <x-table-actions>
            @can('Create User')
                <x-table-action :href="route('admin.users.create')" icon="ph-plus" title="Add User" />
            @endcan
            @can('Import User')
                <x-table-action :href="route('admin.users.bulk.create')" icon="ph-upload-simple" title="Import" />
            @endcan
        </x-table-actions>
    </x-slot>

    <x-slot name="emptyAction">
        @can('Create User')
            <a href="{{ route('admin.users.create') }}" class="btn btn-primary btn-sm">
                <i class="ph-plus"></i>Add the first user
            </a>
        @endcan
    </x-slot>

    <thead>
        <tr>
            <th class="w-48px">Photo</th>
            <th>Name</th>
            <th>Status</th>
            <th class="text-end">Action</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($users as $user)
            <tr>
                <td><img src="{{ $user->image }}" class="fd-avatar" alt="{{ $user->name }}"></td>
                <td>
                    <a href="{{ route('admin.users.show', $user->id) }}" class="fw-semibold text-body">
                        {{ $user->name }}
                    </a>
                    <div class="text-muted fs-xs">{{ ucfirst($user->gender->value) }}</div>
                </td>
                <td><x-status-badge :active="$user->is_active" /></td>
                <td class="text-end">
                    <x-dropdown-menu>
                        @can('View User')
                            <x-dropdown-link :url="route('admin.users.show', $user->id)">
                                <i class="ph-eye"></i>View
                            </x-dropdown-link>
                        @endcan
                        @can('Edit User')
                            <x-dropdown-link :url="route('admin.users.edit', $user->id)">
                                <i class="ph-pencil-simple"></i>Edit
                            </x-dropdown-link>
                        @endcan
                        @can('Delete User')
                            <div class="dropdown-divider"></div>
                            <x-dropdown-link :url="route('admin.users.destroy', $user->id)"
                                class="swal-delete text-danger"
                                data-text="Delete this user? This cannot be undone.">
                                <i class="ph-trash"></i>Delete
                            </x-dropdown-link>
                        @endcan
                    </x-dropdown-menu>
                </td>
            </tr>
        @endforeach
    </tbody>
</x-table-view-pagination>
```

**Table CSS (applied automatically by the component):**
```html
<table class="table table-hover align-middle mb-0">
```
- Header cells are sticky, small, muted and tinted — nothing to add to a `<th>`
- Row padding and hairlines come from `.table`; add `table-xs` only when a table needs tighter rows
- `table-nowrap` keeps a dense table on one line per row

**Column widths:** use the helpers — `w-32px` (checkbox), `w-40px`, `w-48px` (avatar) — never `style="width:…"`.

**Action column:** `text-end` on both the header and the cell, `<x-dropdown-menu>` inside.

**Non-link dropdown items** (modal triggers, JS actions) use a plain `<button class="dropdown-item">`:
```blade
<button type="button" class="dropdown-item edit-btn"
    data-id="{{ $item->id }}" data-name="{{ $item->name }}">
    <i class="ph-pencil"></i>Edit
</button>
```

**Controller pagination:** `QueryBuilder::paginate()` (see `patterns.md`) already reads `foundation.pagination.default`/`.max` — just call it with no argument, or an explicit override:
```php
$items = ItemQuery::make()
    ->search($request->input('search'))
    ->paginate();
```

---

### `<x-search-card>`

Wraps a GET form. The Reset (`btn-light`) and Filter (`btn-primary`) buttons are built in. Always place this above `<x-table-view-pagination>`.

Props: `:reset-route` (optional, overrides the auto-detected current route for the Reset button).  
Slots: `$slot` for the filter columns, `$wide` for full-width filters that need their own row above them.

```blade
<x-search-card>
    <div class="col-md-3 mb-2">
        <x-form.input name="search" label="Search" :value="request('search')" placeholder="Name, email…" />
    </div>
    <div class="col-md-2 mb-2">
        <x-form.select class="select" name="is_active" label="Status" :options="['' => 'All', '1' => 'Active', '0' => 'Inactive']" :selected="request('is_active')" data-placeholder="All" />
    </div>
    <div class="col-md-2 mb-2">
        <x-form.input name="date_from" label="From" type="date" :value="request('date_from')" />
    </div>
</x-search-card>
```

A filter form reads GET query params (`request($name)`), never `old()` — there's no validation to fail back from. `<x-form.input>`/`<x-form.select>` still work correctly here: with no flashed old input, `old($key, $value)` always falls through to the given `:value`.

---

### `<x-form-section>`

A card with a tinted header. Use one per group of fields on a create/edit form.

Props:

| Prop | Default | Description |
|---|---|---|
| `title` | `''` | Section heading (auto-uppercased) |
| `icon` | `'ph-note'` | Phosphor icon in the tinted header |
| `$badge` | slot | Optional badge in header |

```blade
<x-form-section title="Personal Information" icon="ph-identification-card">
    <div class="row g-3">
        <div class="col-md-4">
            <x-form.input name="first_name" label="First Name" />
        </div>
        <div class="col-md-4">
            <x-form.input type="email" name="email" label="Email" required />
        </div>
    </div>
</x-form-section>
```

---

### `<x-modal>`

Props:

| Prop | Default | Options | Description |
|---|---|---|---|
| `id` | `'modal'` | any | CSS id — used in `data-bs-target="#..."` |
| `title` | translated default | any | Header text |
| `size` | `''` (medium) | `sm`, `lg`, `xl`, `fullscreen` | Dialog width |
| `static` | `false` | bool | Prevent backdrop-click close |
| `scrollable` | `true` | bool | Scrollable body |
| `$footer` | slot | — | Modal footer |

```blade
{{-- Trigger --}}
<button type="button" class="btn btn-sm btn-primary"
    data-bs-toggle="modal" data-bs-target="#createModal">
    <i class="ph-plus"></i>Create
</button>

{{-- Modal (place at the bottom of @section('content')) --}}
<x-modal id="createModal" title="Create Item" size="lg" :static="true">
    {{-- form fields --}}
    <x-slot name="footer">
        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" form="create-form" class="btn btn-primary">
            <i class="ph-floppy-disk"></i>Save
        </button>
    </x-slot>
</x-modal>
```

**JS-populated modal (edit flow):**
```javascript
$(document).on('click', '.edit-btn', function () {
    $('#editModal #item-id').val($(this).data('id'));
    $('#editModal #item-name').val($(this).data('name'));
    $('#editModal').modal('show');
});
```

---

### `<x-stat-card>`

A `.card` wrapping a `.fd-stat`: the label and a small icon tile on the top row, the number below it, and an optional trend pill + caption at the foot.

Props:

| Prop | Default | Description |
|---|---|---|
| `label` | `''` | Muted label (`.fd-stat-label`) |
| `value` | `''` | Main metric (`.fd-stat-value`) |
| `icon` | `'ph-chart-bar'` | Phosphor icon inside the tile |
| `color` | `'primary'` | Tile tint: `primary` `success` `warning` `danger` `info` `secondary` |
| `href` | `null` | Makes the whole card a stretched link |
| `change` | `null` | Trend string, e.g. `+12%` — rendered as a `.fd-delta` pill |
| `changeUp` | `true` | `true` = green ↗ / `false` = red ↘ |
| `caption` | `null` | What the change is measured against, e.g. "vs last month" |

```blade
<div class="row g-3 mb-3">
    <div class="col-xl col-md-6">
        <x-stat-card label="Total Users" :value="number_format($totalUsers)"
            icon="ph-users-four" color="primary"
            :href="route('admin.users.index')"
            change="+5%" :change-up="true" caption="vs last month" />
    </div>
    <div class="col-xl col-md-6">
        <x-stat-card label="Active Roles" :value="$activeRoles" icon="ph-shield" color="warning" />
    </div>
</div>
```

For a module dashboard widget with several numbers in one card, use the classes directly instead of three stat cards:
```blade
<div class="col-4">
    <div class="fd-stat-value">{{ number_format($widget['total']) }}</div>
    <div class="fd-stat-label">{{ __('thing::thing.widget.total') }}</div>
</div>
```

---

### Forms

Plain HTML `<form>` — there is no `Form::open()`/`Form::close()` equivalent, and none is needed:
```blade
{{-- Create --}}
<form action="{{ route('admin.items.store') }}" method="POST" enctype="multipart/form-data" id="create-form">
    @csrf
    {{-- fields --}}
</form>

{{-- Edit --}}
<form action="{{ route('admin.items.update', $item->id) }}" method="POST" enctype="multipart/form-data">
    @csrf
    @method('PUT')
    {{-- fields, each with :value="$item->field" — there is no model-binding
         auto-population, every field's current value is passed explicitly --}}
</form>
```

**`<x-form.input>`, `<x-form.select>`, `<x-form.textarea>`, `<x-form.file>`** already render the control, the label (`fw-semibold fs-sm`, with a `required` prop that appends the red asterisk), old-input repopulation, the `is-invalid` class, and the `invalid-feedback` error message — one component call, no separate label or `@error()` block:

```blade
<x-form.input name="name" label="Name" placeholder="Enter name" />
<x-form.input type="email" name="email" label="Email" />
<x-form.textarea name="notes" label="Notes" :rows="4" />
<x-form.input type="date" name="published_at" label="Published At" />
<x-form.file name="avatar" label="Avatar" accept="image/jpeg,image/png" />

{{-- Select2 — add class="select" --}}
<x-form.select class="select" name="status" label="Status" :options="integerStatus()" data-placeholder="Select status…" />

{{-- Multi-select: name has no [] suffix, the multiple prop adds it --}}
<x-form.select class="select" name="roles" label="Roles" multiple :options="$roles" :selected="$item->roles->pluck('id')->toArray()" data-placeholder="Select roles…" />
```

Every field takes `:value` (or `:selected` for a select) explicitly — pass the model's current attribute on an edit form, `null`/omit it on a create form. A select's `:selected` unwraps a `BackedEnum`/`UnitEnum` itself, so pass the enum directly (`:selected="$item->status"`).

Controls are one size: `form-control` and `form-control-sm` render identically, so a raw `<input class="form-control">` needs no size modifier.

**Hint text:**
```blade
<div class="form-text">Leave empty to keep current · JPEG or PNG, max 2 MB</div>
<div class="form-text text-success"><i class="ph-check-circle me-1"></i>Verified</div>
<div class="form-text text-warning"><i class="ph-warning me-1"></i>Not verified</div>
```

Or the component's own `help` prop: `<x-form.input name="phone" label="Mobile No" help="With country code, e.g. +8801712345678" />`.

**Password fields need the eye-toggle button, which `<x-form.input>` has no slot for** — write them as a raw `<input>` alongside `<x-form.label>`, with a `btn-ghost btn-icon` toggle:
```blade
<x-form.label for="password" required>Password</x-form.label>
<div class="position-relative">
    <input type="password" name="password" id="password" class="form-control pe-5" placeholder="Min. 8 characters" required>
    <button type="button" class="btn btn-ghost btn-icon position-absolute top-50 end-0 translate-middle-y" tabindex="-1"
            @click="togglePassword('password')">
        <i x-show="!passwordVisible" class="ph-eye"></i>
        <i x-show="passwordVisible" class="ph-eye-slash" x-cloak></i>
    </button>
</div>
```
A password field with no toggle button — just the blank-by-default, never-repopulated input — can still use the component directly: `<x-form.input type="password" name="value_encrypted" placeholder="Leave blank to keep the current secret" />`.

**Submit row (create/edit bottom bar):** cancel on the leading edge, submit on the trailing edge, below the last `<x-form-section>`:
```blade
<div class="d-flex justify-content-between align-items-center">
    <a href="{{ route('admin.items.index') }}" class="btn btn-light">
        <i class="ph-x"></i>Cancel
    </a>
    <x-primary-button id="submit-btn" class="px-5">
        <i class="ph-floppy-disk"></i>Save Changes
    </x-primary-button>
</div>
```

---

### Buttons

**Variants:**

| Class | Use |
|---|---|
| `btn-primary` | The one primary action on the page or in the card (also `<x-primary-button>`) |
| `btn-light` | Secondary: Cancel, Back, Reset, and every other bordered non-primary button |
| `btn-ghost` | Borderless/quiet: toolbar toggles, a password eye, an action inside an offcanvas footer |
| `btn-icon` | Square icon-only; combine with a variant and usually `btn-sm` |
| `btn-danger` | Destructive submit (also `<x-danger-button>`) |
| `btn-success` / `btn-info` | Export / import, where the colour carries meaning |

`btn-secondary`, `btn-outline-secondary` and `btn-outline-light` are aliased to the same surface-plus-hairline look so old markup keeps working, but **new views write `btn-light`**. (`<x-secondary-button>` and `<x-link-button>` still emit `btn-secondary`; it paints identically.)

**Size rules by context:**

| Context | Required class |
|---|---|
| Card headers | `btn-sm` |
| Table rows / toolbars | `btn-sm` |
| Standalone form submit | No size modifier (or `px-5`) |
| Icon-only | `btn-icon` (+ `btn-sm` outside a form) |

**Icon margins:** `.btn`, `.dropdown-item` and `.navbar-nav-link` set their own `gap`, so an icon inside them takes **no** `me-1`/`me-2` — the class would double the spacing. Keep the margin only where the container sets no gap: a plain `<span>`, a table cell, a heading, a `.dropdown-header`, a `.nav-tabs .nav-link`.

```blade
<i class="ph-plus"></i>Create                          {{-- inside .btn — no margin --}}
<i class="ph-pencil"></i>Edit                          {{-- inside .dropdown-item — no margin --}}
<span><i class="ph-warning-circle me-2"></i>Heads up</span>   {{-- plain span — margin needed --}}
```

**Common buttons:**
```blade
{{-- Primary --}}
<x-primary-button><i class="ph-plus"></i>Create</x-primary-button>

{{-- Secondary (cancel / back) --}}
<a href="{{ route('admin.items.index') }}" class="btn btn-light btn-sm">
    <i class="ph-arrow-left"></i>Back
</a>

{{-- Import --}}
<a href="{{ route('admin.items.bulk.create') }}" class="btn btn-sm btn-info">
    <i class="ph-upload-simple"></i>Import
</a>

{{-- Export (combined with .swal-confirm) --}}
<a href="{{ route('admin.items.export') }}?{{ request()->getQueryString() }}"
   class="btn btn-sm btn-success swal-confirm"
   data-text="Export the current filtered results?">
    <i class="ph-file-xls"></i>Export
</a>

{{-- Icon-only --}}
<button type="button" class="btn btn-sm btn-icon btn-light" title="Refresh">
    <i class="ph-arrows-clockwise"></i>
</button>

{{-- Quiet icon-only --}}
<button type="button" class="btn btn-ghost btn-icon btn-sm" aria-label="Close">
    <i class="ph-x"></i>
</button>
```

---

### Badges & Status

**Status (active/inactive, healthy/failed) reads as a dot, not a pill** — `<x-status-badge>` renders `.fd-status`, and its labels are already translated:
```blade
<x-status-badge :active="$item->is_active" />
<x-status-badge :active="$job->succeeded" active-label="Succeeded" inactive-label="Failed" />
```
For a state that isn't a boolean, write the class directly:
```blade
<span class="fd-status is-warning">{{ __('thing::thing.status.pending') }}</span>
<span class="fd-status is-info">{{ __('thing::thing.status.queued') }}</span>
```

**Badges are for labels and counts**, not for state. The soft badge is the `bg-*-subtle` + `text-*-emphasis` pair:
```blade
<span class="badge bg-secondary-subtle text-secondary-emphasis">{{ $role->name }}</span>
<span class="badge bg-success-subtle text-success-emphasis">{{ __('thing::thing.common.paid') }}</span>
<span class="badge bg-danger-subtle text-danger-emphasis">{{ __('thing::thing.common.overdue') }}</span>
<span class="badge badge-count">{{ number_format($count) }}</span>
```

A solid `bg-*` badge is repainted as the matching soft pill by the stylesheet, so `<span class="badge bg-primary">Admin</span>` is fine and stays legible in both themes. What is **out** is the three-class triple `bg-success-subtle text-success border border-success-subtle` — the border is stripped anyway, and `text-success` is too light on a subtle background in dark mode.

**Dynamic badge:**
```blade
<span class="badge {{ $invoice->is_paid
    ? 'bg-success-subtle text-success-emphasis'
    : 'bg-warning-subtle text-warning-emphasis' }}">
    {{ $invoice->is_paid ? __('thing::thing.common.paid') : __('thing::thing.common.due') }}
</span>
```

---

### Cards

The card is themed by the stylesheet — no utility classes are needed to make it look right. `.card-header` is already a flex row with a gap, so a title, a badge and an action group just sit in it.

**Standard card:**
```blade
<div class="card">
    <div class="card-header">
        <h6 class="card-title">{{ __('thing::thing.index.title') }}</h6>
        <span class="badge badge-count">{{ number_format($total) }}</span>
        <div class="d-flex align-items-center gap-2 ms-auto">
            <a href="{{ route('admin.things.create') }}" class="btn btn-sm btn-primary">
                <i class="ph-plus"></i>{{ __('thing::thing.index.add') }}
            </a>
        </div>
    </div>
    <div class="card-body">...</div>
</div>
```

**Card header with an icon tile** (a dashboard widget):
```blade
<div class="card h-100">
    <div class="card-header">
        <span class="fd-icon-tile fd-icon-tile-sm"><i class="ph-users-four"></i></span>
        <h6 class="card-title">{{ __('thing::thing.widget.title') }}</h6>
        <a href="{{ route('admin.things.index') }}" class="ms-auto fs-sm">
            {{ __('thing::thing.widget.view_all') }}
        </a>
    </div>
    <div class="card-body">...</div>
</div>
```

**Tinted section header** (used automatically by `<x-form-section>`) — don't hand-write it; call the component.

**Flush table card** (`card-body p-0`) — the card's inner radius is applied to the table wrapper automatically:
```blade
<div class="card">
    <div class="card-header">...</div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">...</table>
        </div>
    </div>
</div>
```

**Equal-height cards in a row:** a card that is the only child of a `.row.g-3 > [class*="col"]` already stretches to the row height — `h-100` is belt and braces, not a requirement.

A card does not need a `bg-primary` hero variant; a page announces itself through `<x-page-header>`.

---

### Flash Messages & Confirmations

**Set flash messages in controllers** — rendered automatically by the layout:
```php
return redirect()->route('admin.items.index')->with('success', __('item::item.flash.created'));
return back()->with('error', __('item::item.flash.create_failed'));
```

| Session key | SweetAlert style | Behaviour |
|---|---|---|
| `success` | Success dialog | Confirm button |
| `error` | Error dialog | Confirm button |
| `warning` | Warning dialog | Confirm button |
| `info` | Top toast | Auto-closes 5 s |
| `message` | Top toast (success) | Auto-closes 5 s |
| `status` | Top toast (info) | Auto-closes 5 s |

**Confirmation before action (`.swal-confirm`)** — any non-destructive action needing a yes/no:
```blade
<a href="{{ route('admin.items.activate', $item->id) }}"
   class="dropdown-item swal-confirm"
   data-text="Activate this item?">
    <i class="ph-check"></i>Activate
</a>
```

**Destructive delete (`.swal-delete`)** — POSTs with `_method=DELETE` on confirm:
```blade
<x-dropdown-link :url="route('admin.items.destroy', $item->id)"
    class="swal-delete text-danger"
    data-text="Delete this item? This cannot be undone.">
    <i class="ph-trash"></i>Delete
</x-dropdown-link>
```

**Generic POST confirmation (`.swal-post`)** — for non-DELETE methods:
```blade
<x-dropdown-link :url="route('admin.user.password.reset', $user->id)"
    class="swal-post"
    data-method="POST"
    data-text="Reset this user's password?">
    <i class="ph-key"></i>Reset Password
</x-dropdown-link>
```

Never use `window.confirm()`, Bootstrap toasts, or inline `Swal.fire()` calls — use the session flash system and the `.swal-*` CSS classes instead. For a JS-triggered toast, call the global `toast('success'|'error'|'warning'|'info', title, text)`.

---

### Icons

**Phosphor (`ph-*`) is the only icon set.** Font Awesome was removed — there is no `fa-*` stylesheet loaded, so an `fa-` class renders nothing at all. If a glyph seems to be missing, find the nearest Phosphor name rather than reaching for another library.

**Common mapping:**

| Purpose | Icon |
|---|---|
| Dashboard | `ph-house` |
| Users | `ph-users-four` |
| Create | `ph-plus` |
| Edit | `ph-pencil-simple` |
| View | `ph-eye` |
| Delete | `ph-trash` |
| Save | `ph-floppy-disk` |
| Back | `ph-arrow-left` |
| Cancel / Close | `ph-x` |
| Search | `ph-magnifying-glass` |
| Filter | `ph-funnel` |
| Reset | `ph-arrow-counter-clockwise` |
| Settings | `ph-gear` |
| Roles | `ph-shield` |
| Permissions | `ph-shield-check` |
| Key / Password | `ph-key` |
| Import | `ph-upload-simple` |
| Export | `ph-file-xls` · `ph-file-csv` · `ph-file-pdf` |
| Activity | `ph-clock-counter-clockwise` |
| Notification | `ph-bell` |
| Email | `ph-envelope` |
| Verified | `ph-check-circle` |
| Not verified | `ph-x-circle` |
| Warning | `ph-warning-circle` |
| Empty tray | `ph-tray` |
| Action menu | `ph-dots-three-vertical` |

**Sizing:**
```blade
<i class="ph-users ph-sm"></i>     {{-- .875em --}}
<i class="ph-users ph-lg"></i>     {{-- 1.375em --}}
<i class="ph-users ph-2x"></i>     {{-- 2em --}}
<i class="ph-users ph-3x"></i>     {{-- 3em --}}
<i class="ph-spinner ph-spin"></i> {{-- spins --}}
```
Inside a `.btn`, a `.dropdown-item`, a sidebar link or an `.alert`, the icon is already sized by the container — add nothing.

A decorative icon that leads a block of content belongs in a `.fd-icon-tile`, not loose at `display-5`.

---

### Tooltips & Truncation

```blade
{{-- Bootstrap tooltip --}}
<span data-bs-popup="tooltip" data-bs-placement="top" title="Full text here">Short label</span>

{{-- Truncated text with tooltip --}}
<x-truncated-text :text="$item->description" :limit="50" />
```

---

### Sticky Unsaved-Changes Bar

For long settings/configuration forms. It sticks to the top of the scrolling content column:

```blade
<div id="save-bar" class="d-none mb-3 sticky-top">
    <div class="alert alert-warning d-flex align-items-center justify-content-between py-2 px-3 mb-0
                rounded-0 border-start-0 border-end-0">
        <span><i class="ph-warning-circle me-2"></i>{{ __('thing::thing.settings.unsaved_changes') }}</span>
        <button type="submit" class="btn btn-dark btn-sm px-3">
            <i class="ph-floppy-disk"></i>{{ __('thing::thing.settings.save_now') }}
        </button>
    </div>
</div>
```
```javascript
$('#settings-form').on('change input', function () {
    $('#save-bar').removeClass('d-none');
});
```

---

### Helper Functions (views & controllers)

| Function | Returns | Use |
|---|---|---|
| `integerStatus()` | `['1'=>'Active','0'=>'Inactive']` | Status selects |
| `getParPagePaginate()` | `['10'=>'10', ...]` from `foundation.pagination.options` | Per-page select options |
| `getUrlFromPath($path)` | URL string | Resolve stored paths to URLs |
| `display_label($value)` | Translated label | Config/database-sourced labels (sidebar, permissions, settings) |
| `form_old_key($name)` | Dot-notation key, e.g. `roles[]` → `roles` | Used internally by the `<x-form.*>` components; rarely needed directly |

---

### Anti-Patterns

| ❌ Don't | ✅ Do |
|---|---|
| Add a rule to `assets/css/foundation.css` | Project CSS in `resources/css/app.css`, or `@push('styles')` for one page — the package file is overwritten on update |
| `style="…"` on an element | Bootstrap utilities, a `.fd-*` class, or a sizing helper (`w-48px`, `fs-xs`) |
| Hard-coded hex colours in markup or CSS | A token: `var(--fd-accent)`, `var(--fd-muted)`, `var(--bs-success-bg-subtle)` |
| `fa-*` icons | `ph-*` — Font Awesome is not loaded |
| `me-1`/`me-2` on an icon inside `.btn`, `.dropdown-item` or `.navbar-nav-link` | Nothing — the container sets the gap |
| `btn-outline-secondary` / `btn-secondary` in a new view | `btn-light` (or `btn-ghost` when it should be borderless) |
| `bg-success-subtle text-success border border-success-subtle` | `bg-success-subtle text-success-emphasis` |
| A hand-written Active/Inactive badge | `<x-status-badge :active="" />` |
| Bare `<style>` / `<script>` tags in views | `@push('styles')` / `@push('scripts')` |
| Raw `<table>` without `<x-table-view-pagination>` | Always use the component |
| `style="width:60px"` on a `<th>` | `w-32px` / `w-40px` / `w-48px` |
| `btn` without a size in table/card contexts | Add `btn-sm` |
| `window.confirm()` | `.swal-confirm` / `.swal-delete` / `.swal-post` |
| Bootstrap toasts for flash messages | Session flash, or the global `toast()` helper |
| Writing navbar/sidebar/footer markup in a page | `<x-app-layout>` renders the shell; pages fill the content |
| `env()` outside config files | `config('key')` |
| `DB::` raw queries | `Model::query()` / Eloquent |
| `@if(auth()->user()->hasRole(...))` | `@can('Permission Name')` |
| Non-named routes in `href` | `route('admin.items.index')` |
| Raw `<input>`/`<select>`/`<textarea>` for an ordinary field | `<x-form.input>` / `<x-form.select>` / `<x-form.textarea>` — old-input, errors and `is-invalid` come for free |
| Skipping `$this->authorize()` in controller methods | Always call at the top of every action |
