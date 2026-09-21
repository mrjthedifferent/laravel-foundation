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

**Controller pagination:** always use `PaginationEnum`:
```php
use Mrj\Foundation\Enum\PaginationEnum;

$items = ItemQuery::make()
    ->search($request->input('search'))
    ->paginate($request->integer('per_page') ?: PaginationEnum::DEFAULT_LIST);
```

---

### `<x-search-card>`

Wraps a GET form. Filter + Reset buttons are built in. Always place this above `<x-table-view-pagination>`.

Props: `:reset-route` (optional, overrides the auto-detected current route for the Reset button).

```blade
<x-search-card>
    <div class="col-md-3 mb-2">
        {!! Form::label('search', 'Search', ['class' => 'form-label fs-sm']) !!}
        {!! Form::text('search', request('search'), [
            'class' => 'form-control form-control-sm', 'placeholder' => 'Name, email…',
        ]) !!}
    </div>
    <div class="col-md-2 mb-2">
        {!! Form::label('is_active', 'Status', ['class' => 'form-label fs-sm']) !!}
        {!! Form::select('is_active', ['' => 'All', '1' => 'Active', '0' => 'Inactive'], request('is_active'), [
            'class' => 'form-control form-control-sm select',
            'data-placeholder' => 'All',
        ]) !!}
    </div>
    <div class="col-md-2 mb-2">
        {!! Form::label('date_from', 'From', ['class' => 'form-label fs-sm']) !!}
        {!! Form::date('date_from', request('date_from'), ['class' => 'form-control form-control-sm']) !!}
    </div>
</x-search-card>
```

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
            {!! Form::label('first_name', 'First Name', ['class' => 'form-label fw-semibold fs-sm']) !!}
            {!! Form::text('first_name', null, ['class' => 'form-control form-control-sm']) !!}
        </div>
        <div class="col-md-4">
            {!! Form::label('email', 'Email <span class="text-danger">*</span>',
                ['class' => 'form-label fw-semibold fs-sm'], false) !!}
            {!! Form::email('email', null, ['class' => 'form-control form-control-sm', 'required']) !!}
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

**Open / close:**
```blade
{{-- Create --}}
{{ Form::open(['route' => 'admin.items.store', 'method' => 'post', 'files' => true, 'id' => 'create-form']) }}

{{-- Edit (auto-populates from model; pass null as value) --}}
{{ Form::model($item, ['route' => ['admin.items.update', $item->id], 'method' => 'put', 'files' => true]) }}

{{ Form::close() }}
```

**Labels:** use `fw-semibold fs-sm` on all form labels. Mark required with raw HTML or the `required` CSS class (which appends `*` automatically via `:after`):
```blade
{{-- Option A: raw HTML asterisk --}}
{!! Form::label('email', 'Email <span class="text-danger">*</span>',
    ['class' => 'form-label fw-semibold fs-sm'], false) !!}

{{-- Option B: required CSS class (same visual result) --}}
{!! Form::label('email', 'Email', ['class' => 'form-label fw-semibold fs-sm required']) !!}
```

**All inputs use `form-control form-control-sm`:**
```blade
{!! Form::text('name', null, ['class' => 'form-control form-control-sm', 'placeholder' => 'Enter name']) !!}
{!! Form::email('email', null, ['class' => 'form-control form-control-sm']) !!}
{!! Form::textarea('notes', null, ['class' => 'form-control form-control-sm', 'rows' => 4]) !!}
{!! Form::date('published_at', null, ['class' => 'form-control form-control-sm']) !!}
{!! Form::file('avatar', ['class' => 'form-control form-control-sm', 'accept' => 'image/jpeg,image/png']) !!}
```

**Select2 selects — add `select` class:**
```blade
{!! Form::select('status', integerStatus(), null, [
    'class' => 'form-control form-control-sm select',
    'data-placeholder' => 'Select status…',
]) !!}

{{-- Multi-select --}}
{!! Form::select('roles[]', $roles, null, [
    'class' => 'form-control form-control-sm select',
    'multiple',
    'data-placeholder' => 'Select roles…',
]) !!}
```

**Hint text:**
```blade
<div class="form-text">Leave empty to keep current · JPEG or PNG, max 2 MB</div>
<div class="form-text text-success"><i class="ph-check-circle me-1"></i>Verified</div>
<div class="form-text text-warning"><i class="ph-warning me-1"></i>Not verified</div>
```

**Validation errors:**
```blade
{!! Form::text('name', null, [
    'class' => 'form-control form-control-sm' . ($errors->has('name') ? ' is-invalid' : ''),
]) !!}
<x-input-error :messages="$errors->get('name')" />
```

**Password toggle pattern:**
```blade
<div class="input-group input-group-sm">
    {!! Form::password('password', ['class' => 'form-control', 'id' => 'password']) !!}
    <button type="button" class="btn btn-outline-secondary pw-toggle"
        data-target="password" tabindex="-1">
        <i class="ph-eye"></i>
    </button>
</div>
```
```javascript
$(document).on('click', '.pw-toggle', function () {
    var input = document.getElementById($(this).data('target'));
    var icon  = $(this).find('i');
    input.type = input.type === 'password' ? 'text' : 'password';
    icon.toggleClass('ph-eye ph-eye-slash');
});
```

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
| `getCommonStatus()` | `['Active'=>'Active','Inactive'=>'Inactive']` | String status selects |
| `getParPagePaginate()` | `['10','25','50','100']` | Per-page select options |
| `getIntegerMonth()` | Month name map | Month selects |
| `currency_number($n)` | `number_format($n, 2)` | Currency display |
| `ajaxResponse($code, $msg, $errors, $data)` | `JsonResponse` | AJAX responses |
| `allPermissions()` | Collection | Available in all sidebar partials |
| `getUrlFromPath($path)` | URL string | Resolve stored paths to URLs |
| `enum_value($v)` | Backing scalar, else `$v` unchanged | **Required** for any `Form::` field bound via `Form::model()` to an enum-cast column |

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
| `Form::select('field', $opts, null, …)` on an enum-cast column | `enum_value($model->field)` as the selected value |
| Skipping `$this->authorize()` in controller methods | Always call at the top of every action |

