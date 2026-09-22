## UI Components & Blade Guidelines

> **Theme:** Laravel Foundation (`assets/css/foundation.css`) · **CSS:** Bootstrap 5.3 · **Icons:** Phosphor Icons (`ph-*`) + Font Awesome  
> **JS:** jQuery · Bootstrap Bundle · Select2 · SweetAlert2

See [`views.md`](views.md) for full index/create/edit page templates. This file covers the component API, CSS patterns, and conventions that apply across all views.

---

### Layout & Asset Stacks

The root layout is `<x-app-layout>`. Use `@push` / `@stack` for per-page assets — never bare `<style>` or `<script>` tags at the top level of a view:

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

### Blade Components — Quick Reference

| Component | Tag | Purpose |
|---|---|---|
| App layout | `<x-app-layout>` | Root authenticated layout (used in module `master.blade.php` only) |
| Page header | `<x-page-header>` | Title + optional back button + `$actions` slot |
| Form section | `<x-form-section>` | Titled card grouping related form fields |
| Search card | `<x-search-card>` | GET filter form with Filter + Reset buttons |
| Table view | `<x-table-view-pagination>` | Card + table + count badge + paginator footer + empty state |
| Stat card | `<x-stat-card>` | KPI tile with icon, label, value, trend |
| Modal | `<x-modal>` | Bootstrap modal dialog |
| Dropdown menu | `<x-dropdown-menu>` | Three-dot action menu trigger |
| Dropdown link | `<x-dropdown-link :url="">` | Anchor item inside `<x-dropdown-menu>` |
| Primary button | `<x-primary-button>` | `btn btn-primary` submit button |
| Secondary button | `<x-secondary-button>` | `btn btn-secondary` type=button |
| Danger button | `<x-danger-button>` | `btn btn-danger` submit button |
| Link button | `<x-link-button>` | `btn btn-secondary` anchor tag |
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

Props:

| Prop | Type | Default | Description |
|---|---|---|---|
| `title` | string | `''` | Page heading |
| `subtitle` | string\|null | `null` | Muted sub-line |
| `icon` | string\|null | `null` | Phosphor icon class e.g. `ph-users` |
| `backUrl` | string\|null | `null` | Renders a back button when set |
| `backLabel` | string | `'Back'` | Back button label |
| `$actions` | slot | — | Buttons/badges top-right |

```blade
{{-- Index page --}}
<x-page-header title="Users" subtitle="Manage system users" icon="ph-users-four" />

{{-- Create/edit with back button (submit button in actions slot) --}}
<x-page-header title="Create User" icon="ph-user-plus"
    :back-url="route('admin.users.index')" back-label="Back to List">
    <x-slot name="actions">
        <button type="submit" class="btn btn-primary px-5">
            <i class="ph-floppy-disk me-1"></i>Save
        </button>
    </x-slot>
</x-page-header>

{{-- Edit with context badges --}}
<x-page-header title="{{ $user->name }}" icon="ph-pencil-simple"
    :back-url="route('admin.users.index')">
    <x-slot name="actions">
        <span class="badge bg-primary-subtle text-primary border border-primary-subtle fs-xs">Admin</span>
        <span class="badge bg-success-subtle text-success border border-success-subtle fs-xs">Active</span>
    </x-slot>
</x-page-header>
```

> Place the submit button in the `$actions` slot on create/edit pages — not in a separate bottom row.

---

### `<x-table-view-pagination>`

Props:

| Prop | Default | Description |
|---|---|---|
| `title` | `''` | Card header title |
| `data` | `null` | Paginator **or** Collection/array |
| `emptyMessage` | `'No data available'` | Empty state text |
| `emptyIcon` | `'ph-tray'` | Phosphor icon for empty state |
| `$actions` | slot | Header buttons (top-right) |

```blade
<x-table-view-pagination title="Users" :data="$users"
    empty-message="No users found" empty-icon="ph-users">

    <x-slot name="actions">
        @can('Create User')
            <a href="{{ route('admin.users.create') }}" class="btn btn-sm btn-primary">
                <i class="ph-plus me-1"></i>Add User
            </a>
        @endcan
    </x-slot>

    <thead>
        <tr>
            <th style="width:52px">Photo</th>
            <th>Name</th>
            <th>Status</th>
            <th class="text-end" style="width:60px">Action</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($users as $user)
            <tr>
                <td>
                    <img src="{{ $user->image }}" class="rounded-circle border"
                         style="height:40px;width:40px;object-fit:cover;" alt="{{ $user->name }}">
                </td>
                <td>
                    <a href="{{ route('admin.users.show', $user->id) }}" class="fw-semibold text-body">
                        {{ $user->name }}
                    </a>
                    <div class="text-muted fs-xs">{{ ucfirst($user->gender->value) }}</div>
                </td>
                <td>
                    <span class="badge {{ $user->is_active
                        ? 'bg-success-subtle text-success border border-success-subtle'
                        : 'bg-danger-subtle text-danger border border-danger-subtle' }}">
                        {{ $user->is_active ? 'Active' : 'Inactive' }}
                    </span>
                </td>
                <td class="text-end">
                    <x-dropdown-menu>
                        @can('View User')
                            <x-dropdown-link :url="route('admin.users.show', $user->id)">
                                <i class="ph-eye me-2"></i>View
                            </x-dropdown-link>
                        @endcan
                        @can('Edit User')
                            <x-dropdown-link :url="route('admin.users.edit', $user->id)">
                                <i class="ph-pencil-simple me-2"></i>Edit
                            </x-dropdown-link>
                        @endcan
                        @can('Delete User')
                            <div class="dropdown-divider"></div>
                            <x-dropdown-link :url="route('admin.users.destroy', $user->id)"
                                class="swal-delete text-danger"
                                data-text="Delete this user? This cannot be undone.">
                                <i class="ph-trash me-2"></i>Delete
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
<table class="table table-hover table-borderless table-xs align-middle mb-0">
```
- `table-xs` — compact row height
- `align-middle` — vertically centred cells
- `mb-0` inside `card-body p-0` — no extra spacing

**Action column:** Always `text-end` header + `text-end` cell, `style="width:60px"`.

**Non-link dropdown items** (modal triggers, JS actions) use a plain `<button class="dropdown-item">`:
```blade
<button type="button" class="dropdown-item edit-btn"
    data-id="{{ $item->id }}" data-name="{{ $item->name }}">
    <i class="ph-pencil me-2"></i>Edit
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

Wraps a GET form. Filter + Reset buttons are built in. Always place this above `<x-table-view-pagination>`.

Props: `:reset-route` (optional, overrides the auto-detected current route for the Reset button).

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
| `title` | `'Modal Title'` | any | Header text |
| `size` | `''` (medium) | `sm`, `lg`, `xl`, `fullscreen` | Dialog width |
| `static` | `false` | bool | Prevent backdrop-click close |
| `scrollable` | `true` | bool | Scrollable body |
| `$footer` | slot | — | Modal footer |

```blade
{{-- Trigger --}}
<button type="button" class="btn btn-sm btn-primary"
    data-bs-toggle="modal" data-bs-target="#createModal">
    <i class="ph-plus me-1"></i>Create
</button>

{{-- Modal (place at the bottom of @section('content')) --}}
<x-modal id="createModal" title="Create Item" size="lg" :static="true">
    {{-- form fields --}}
    <x-slot name="footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" form="create-form" class="btn btn-primary">
            <i class="ph-floppy-disk me-1"></i>Save
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

Props:

| Prop | Default | Description |
|---|---|---|
| `label` | `''` | Muted label |
| `value` | `''` | Main metric |
| `icon` | `'ph-chart-bar'` | Phosphor icon |
| `color` | `'primary'` | `primary` `success` `warning` `danger` `info` |
| `href` | `null` | Makes value a stretched link |
| `change` | `null` | Trend string e.g. `+12%` |
| `changeUp` | `true` | `true` = green↑ / `false` = red↓ |

```blade
<div class="row g-3 mb-3">
    <div class="col-xl col-md-6">
        <x-stat-card label="Total Users" :value="number_format($totalUsers)"
            icon="ph-users-four" color="primary"
            :href="route('admin.users.index')" change="+5%" :change-up="true" />
    </div>
    <div class="col-xl col-md-6">
        <x-stat-card label="Active Roles" :value="$activeRoles"
            icon="ph-shield" color="warning" />
    </div>
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

**`<x-form.input>`, `<x-form.select>`, `<x-form.textarea>`, `<x-form.file>`** already render `form-control form-control-sm` (or `select` + `form-control-sm` for a select), the label (`fw-semibold fs-sm`, with a `required` prop that appends the red asterisk), old-input repopulation, the `is-invalid` class, and the `invalid-feedback` error message — one component call replaces a `Form::label()` + `Form::text()`/`Form::select()`/etc. pair and the manual `@error()` block that used to follow it:

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

Every field takes `:value` (or `:selected` for a select) explicitly — pass the model's current attribute on an edit form, `null`/omit it on a create form. A select's `:selected` unwraps a `BackedEnum`/`UnitEnum` itself, so pass the enum value directly (`:selected="$item->status"`), never `enum_value($item->status)` — that helper no longer exists.

**Hint text:**
```blade
<div class="form-text">Leave empty to keep current · JPEG or PNG, max 2 MB</div>
<div class="form-text text-success"><i class="ph-check-circle me-1"></i>Verified</div>
<div class="form-text text-warning"><i class="ph-warning me-1"></i>Not verified</div>
```

Or the component's own `help` prop: `<x-form.input name="phone" label="Mobile No" help="With country code, e.g. +8801712345678" />`.

**Password fields need the eye-toggle button, which `<x-form.input>` has no slot for** — write them as a raw `<input>` alongside `<x-form.label>`:
```blade
<x-form.label for="password" required>Password</x-form.label>
<div class="position-relative">
    <input type="password" name="password" id="password" class="form-control pe-5" placeholder="Min. 8 characters" required>
    <button type="button" class="btn border-0 text-muted shadow-none position-absolute top-50 end-0 translate-middle-y" tabindex="-1"
            @click="togglePassword('password')">
        <i class="ph-eye"></i>
    </button>
</div>
```
A password field with no toggle button — just the blank-by-default, never-repopulated input — can still use the component directly: `<x-form.input type="password" name="value_encrypted" placeholder="Leave blank to keep the current secret" />`.

**Submit row (create/edit bottom bar):**
```blade
<div class="d-flex justify-content-between align-items-center">
    <a href="{{ route('admin.items.index') }}" class="btn btn-outline-secondary">
        <i class="ph-x me-1"></i>Cancel
    </a>
    <x-primary-button id="submit-btn" class="px-5">
        <i class="ph-floppy-disk me-1"></i>Save Changes
    </x-primary-button>
</div>
```

---

### Buttons

**Size rules by context:**

| Context | Required class |
|---|---|
| Card headers | `btn-sm` |
| Table rows / dropdown items | `btn-sm` |
| Standalone form submit | No size modifier (or `px-5`) |
| Icon-only toolbar | `btn-icon btn-sm` |

**Icon placement:** always **before** the label — `me-1` in buttons, `me-2` in dropdown items:
```blade
<i class="ph-plus me-1"></i>Create       {{-- button --}}
<i class="ph-pencil me-2"></i>Edit        {{-- dropdown item --}}
```

**Common variants:**
```blade
{{-- Primary --}}
<x-primary-button><i class="ph-plus me-1"></i>Create</x-primary-button>

{{-- Outline secondary (cancel / back) --}}
<a href="{{ route('admin.items.index') }}" class="btn btn-outline-secondary btn-sm">
    <i class="ph-arrow-left me-1"></i>Back
</a>

{{-- Info (import) --}}
<a href="{{ route('admin.items.bulk.create') }}" class="btn btn-sm btn-info">
    <i class="ph-upload-simple me-1"></i>Import
</a>

{{-- Success (export — combined with .swal-confirm) --}}
<a href="{{ route('admin.items.export') }}?{{ request()->getQueryString() }}"
   class="btn btn-sm btn-success swal-confirm"
   data-text="Export the current filtered results?">
    <i class="ph-file-xls me-1"></i>Export
</a>

{{-- Icon-only --}}
<button type="button" class="btn btn-sm btn-icon btn-outline-secondary" title="Refresh">
    <i class="ph-arrows-clockwise"></i>
</button>
```

---

### Badges & Status

**Soft badge (preferred everywhere):**
```blade
{{-- Active / success --}}
<span class="badge bg-success-subtle text-success border border-success-subtle">Active</span>

{{-- Inactive / danger --}}
<span class="badge bg-danger-subtle text-danger border border-danger-subtle">Inactive</span>

{{-- Role / tag --}}
<span class="badge bg-primary-subtle text-primary border border-primary-subtle">Admin</span>

{{-- Pending --}}
<span class="badge bg-warning-subtle text-warning border border-warning-subtle">Pending</span>

{{-- Count (info) --}}
<span class="badge bg-info-subtle text-info border border-info-subtle">{{ $count }}</span>

{{-- Neutral count (card header) --}}
<span class="badge bg-secondary fw-normal">{{ number_format($total) }}</span>
```

**Dynamic boolean badge:**
```blade
<span class="badge {{ $item->is_active
    ? 'bg-success-subtle text-success border border-success-subtle'
    : 'bg-danger-subtle text-danger border border-danger-subtle' }}">
    {{ $item->is_active ? 'Active' : 'Inactive' }}
</span>
```

---

### Cards

**Standard card:**
```blade
<div class="card mb-3">
    <div class="card-header py-2 d-flex align-items-center justify-content-between">
        <h6 class="card-title mb-0 fw-semibold">Title</h6>
    </div>
    <div class="card-body">...</div>
</div>
```

**Tinted section header** (used automatically by `<x-form-section>`):
```blade
<div class="card-header py-2 d-flex align-items-center gap-2 bg-body-tertiary border-bottom">
    <i class="ph-identification-card text-primary"></i>
    <span class="fw-semibold text-uppercase fs-xs" style="letter-spacing:.05em;">Section Name</span>
</div>
```

**Flush table card** (`card-body p-0`):
```blade
<div class="card">
    <div class="card-header ...">...</div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover table-borderless table-xs align-middle mb-0">...</table>
        </div>
    </div>
</div>
```

**Welcome / banner card:**
```blade
<div class="card bg-primary text-white mb-3">
    <div class="card-body py-3 d-flex justify-content-between align-items-center">
        <div>
            <h4 class="mb-1 fw-semibold">Hello, {{ auth()->user()->name }}!</h4>
            <p class="mb-0 opacity-75">{{ now()->format('l, F j, Y') }}</p>
        </div>
        <i class="ph-house-simple display-5 opacity-25"></i>
    </div>
</div>
```

---

### Flash Messages & Confirmations

**Set flash messages in controllers** — rendered automatically by `_message.blade.php`:
```php
return redirect()->route('admin.items.index')->with('success', 'Item created.');
return back()->with('error', 'Something went wrong.');
```

| Session key | SweetAlert style | Behaviour |
|---|---|---|
| `success` | Success dialog | Confirm button |
| `error` | Error dialog | Confirm button |
| `info` | Top toast | Auto-closes 5 s |
| `message` | Top toast (success) | Auto-closes 5 s |
| `status` | Top toast (info) | Auto-closes 5 s |

**Confirmation before action (`.swal-confirm`)** — any non-destructive action needing a yes/no:
```blade
<a href="{{ route('admin.items.activate', $item->id) }}"
   class="dropdown-item swal-confirm"
   data-text="Activate this item?">
    <i class="ph-check me-2"></i>Activate
</a>
```

**Destructive delete (`.swal-delete`)** — POSTs with `_method=DELETE` on confirm:
```blade
<x-dropdown-link :url="route('admin.items.destroy', $item->id)"
    class="swal-delete text-danger"
    data-text="Delete this item? This cannot be undone.">
    <i class="ph-trash me-2"></i>Delete
</x-dropdown-link>
```

**Generic POST confirmation (`.swal-post`)** — for non-DELETE methods:
```blade
<a href="{{ route('admin.user.password.reset', $user->id) }}"
   class="dropdown-item swal-post"
   data-method="POST"
   data-text="Reset this user's password?">
    <i class="ph-key me-2"></i>Reset Password
</a>
```

Never use `window.confirm()`, Bootstrap toasts, or inline `Swal.fire()` calls — use the session flash system and the `.swal-*` CSS classes instead.

---

### Icons

Use **Phosphor Icons** (`ph-*`) as the primary icon set. Fall back to Font Awesome (`fa-*`) only when no Phosphor equivalent exists.

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
| Cancel | `ph-x` |
| Filter | `ph-funnel` |
| Reset | `ph-arrow-counter-clockwise` |
| Settings | `ph-gear` |
| Roles | `ph-shield` |
| Permissions | `ph-shield-check` |
| Key / Password | `ph-key` |
| Import | `ph-upload-simple` |
| Export | `ph-file-xls` |
| Activity | `ph-activity` |
| Notification | `ph-bell` |
| Email | `ph-envelope` |
| Verified | `ph-check-circle` |
| Warning | `ph-warning-circle` |
| Action menu | `ph-dots-three-vertical` |

**Sizing:**
```blade
<i class="ph-users ph-sm"></i>        {{-- small --}}
<i class="ph-users ph-lg"></i>        {{-- large --}}
<i class="ph-users ph-2x"></i>        {{-- 2× for dashboard tiles --}}
<i class="ph-users display-5"></i>    {{-- Bootstrap display utility --}}
```

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

For long settings/configuration forms:

```blade
<div id="save-bar" class="d-none mb-3 sticky-top" style="z-index:1020;">
    <div class="alert alert-warning d-flex align-items-center justify-content-between py-2 px-3 mb-0
                rounded-0 border-start-0 border-end-0">
        <span><i class="ph-warning-circle me-2"></i>You have <strong>unsaved changes</strong>.</span>
        <button type="submit" class="btn btn-dark btn-sm px-3">
            <i class="ph-floppy-disk me-1"></i>Save Now
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
| `allPermissions()` | Collection | Available in all sidebar partials |
| `getUrlFromPath($path)` | URL string | Resolve stored paths to URLs |
| `form_old_key($name)` | Dot-notation key, e.g. `roles[]` → `roles` | Used internally by the `<x-form.*>` components; rarely needed directly |

---

### Anti-Patterns

| ❌ Don't | ✅ Do |
|---|---|
| Bare `<style>` / `<script>` tags in views | `@push('styles')` / `@push('scripts')` |
| Raw `<table>` without `<x-table-view-pagination>` | Always use the component |
| `form-control` without `form-control-sm` | Always include `form-control-sm` |
| `btn` without a size in table/card contexts | Add `btn-sm` |
| `window.confirm()` | `.swal-confirm` / `.swal-delete` |
| Bootstrap toasts for flash messages | Session flash + `_message.blade.php` |
| `env()` outside config files | `config('key')` |
| `DB::` raw queries | `Model::query()` / Eloquent |
| `@if(auth()->user()->hasRole(...))` | `@can('Permission Name')` |
| Hard-coded hex colours in markup | Bootstrap CSS vars (`var(--bs-primary)`) |
| Non-named routes in `href` | `route('admin.items.index')` |
| Raw `<input>`/`<select>`/`<textarea>` for an ordinary field | `<x-form.input>` / `<x-form.select>` / `<x-form.textarea>` — old-input, errors and `is-invalid` come for free |
| Skipping `$this->authorize()` in controller methods | Always call at the top of every action |

