## Views — Blade Conventions

### View Namespacing

Views are referenced with the module alias as namespace:
```php
view('thing::thing.index', compact('things'))   // Modules/Thing/resources/views/thing/index.blade.php
view('thing::layouts.master')                    // Modules/Thing/resources/views/layouts/master.blade.php
```

---

### Module Layout (`layouts/master.blade.php`)

Every module has its own layout — one line, via `<x-module-layout>`:

```blade
<x-module-layout route="admin.things.index" :label="__('thing::thing.index.title')" />
```

A page still `@extends('thing::layouts.master')` and fills `@section('breadcrumb')` / `@section('content')` exactly as before — `route`/`label` only supply the layout's own "Home → Things" breadcrumb prefix.

`<x-module-layout>` wraps `<x-app-layout>`, which is the **one** application shell: sidebar (brand, navigation, signed-in user), navbar (⌘K search, notifications, account menu), the breadcrumb row, the content column — the only part of the page that scrolls — and the footer below it. There are no layout variants. A page never writes navbar, sidebar or footer markup; it declares its sidebar entry in `config/menu.php` and fills `@section('content')`.

---

### Translations

No user-facing English is written directly in a view, controller, action or Form Request `messages()`. It lives in the module's lang file and is read with `__()`:

```php
// Modules/Thing/lang/en/thing.php — loaded automatically under the `thing` namespace
return [
    'index' => ['title' => 'Things', 'empty' => 'No things yet', 'delete_confirm' => 'Delete :name?'],
    'create' => ['title' => 'Create Thing'],
    'flash' => ['created' => 'Thing created successfully', 'deleted' => 'Thing deleted successfully'],
];
```

```blade
<h6>{{ __('thing::thing.index.title') }}</h6>
<x-form.input name="name" :label="__('foundation::foundation.common.name')" />
<a class="swal-confirm" data-text="{{ __('thing::thing.index.delete_confirm', ['name' => $thing->name]) }}">
<script> const msg = @js(__('thing::thing.index.empty')); </script>   {{-- inside JS: @js, never {{ }} --}}
```

- One file per module, `lang/en/{alias}.php`, one section per view plus `flash` and `errors`.
- Generic words (Name, Status, Action, Edit, Delete, Save, Cancel, Close, Search, Filter, Reset, Active, Inactive, Created At, …) come from `foundation::foundation.common.*` — use them only for an exact match; anything more specific ("Delete Thing") stays in the module file.
- Put variables in a `:placeholder`, never concatenate around a translated string.
- **Identifiers stay English.** Permission names (`@can('View Thing')`), setting keys, route names and stored values are never translated. Labels that come from config or the database — sidebar `label`s, permission and setting names, stored titles — are displayed through `{{ display_label($value) }}`, so a project can translate them in `lang/{locale}.json` keyed by the English text without touching the identifier. Never `{{ __($value) }}` on data: a value equal to a lang file name (`auth`, `validation`) returns an array and breaks the page. (Enum `label()` methods, whose strings are fixed literals, use `__('Active')` directly.)
- Log messages are not translated.

A project changes any string without editing the package: `php artisan vendor:publish --tag=foundation-lang` for the shared file (keys it leaves out fall back to the package's), and a full copy of a module's file at `resources/lang/modules/{alias}/en/{alias}.php` for a module (that directory replaces the module's own, so copy the whole file). Adding a language is the same files under another locale directory.

---

### Flash Messages

Flash with `success` or `error` keys — SweetAlert2 renders them automatically:
```php
return redirect()->route('admin.things.index')->with('success', __('thing::thing.flash.created'));
return back()->with('error', __('thing::thing.flash.create_failed'));
```

Never write custom alert HTML — the layout handles all flash messages.

---

### Where Shared Views Live

The layouts, the shared `<x-...>` components and the error pages ship in the `mrjthedifferent/laravel-foundation` package (`vendor/mrjthedifferent/laravel-foundation/ui/resources/views`). They are not in this project's `resources/views`.

- Never edit files under `vendor/`. A change every project should get belongs in the foundation repository.
- To change one view for this project only, create the same relative path under `resources/views` (for example `resources/views/components/page-header.blade.php`). The project's file wins.

### Available Shared Components

| Component | Purpose |
|---|---|
| `<x-app-layout>` | Root page wrapper (use in `layouts/master.blade.php` only) |
| `<x-module-layout route="" :label="">` | A module's `layouts/master.blade.php`, in one line |
| `<x-page-header title="" icon="" subtitle="" :back-url="" back-label="">` | Page-level heading (`.fd-page-head`) with optional back button and `$actions` slot |
| `<x-form-section title="" icon="">` | Wraps a card section inside a create/edit form |
| `<x-search-card>` | Filter form wrapper for list pages |
| `<x-table-view-pagination>` | Card + table + pagination + empty state for list pages |
| `<x-table-actions>` / `<x-table-action>` | Action group for the table card header; overflow collapses into a menu |
| `<x-table-export-dropdown>` / `<x-table-export-item>` | Export menu for the table's `$exports` slot |
| `<x-stat-card label="" :value="" icon="">` | KPI tile (`.fd-stat`) for dashboards |
| `<x-status-badge :active="">` | Status dot + translated Active/Inactive label (`.fd-status`) |
| `<x-dropdown-menu>` | Action dropdown in table rows |
| `<x-dropdown-link :url="">` | Link item inside `x-dropdown-menu` |
| `<x-modal id="" title="">` | Bootstrap modal dialog |
| `<x-alert type="">` | Inline alert with a matching Phosphor icon |
| `<x-form.input>`, `<x-form.select>`, `<x-form.textarea>`, `<x-form.file>`, `<x-form.checkbox>`, `<x-form.label>` | Labeled form fields — old-input, validation errors and `is-invalid` built in (see `patterns.md` / `ui-components.md` for the full API) |

`<x-page-header>`'s `$actions` slot holds the page's context — badges, status, an avatar, a secondary action — placed on the trailing edge before the back button. The submit button of a create/edit form does **not** go here; it goes in the form's bottom row (see the templates below).
```blade
<x-page-header :title="$thing->name" icon="ph-pencil-simple" :back-url="route('admin.things.index')" back-label="Back to List">
    <x-slot name="actions">
        <span class="badge bg-primary">{{ display_label($thing->type) }}</span>
        <x-status-badge :active="$thing->is_active" class="fs-xs" />
    </x-slot>
</x-page-header>
```

---

### Index / List Page

```blade
@extends('thing::layouts.master')

@section('breadcrumb')
    <span class="breadcrumb-item active">{{ __('thing::thing.index.title') }}</span>
@endsection

@section('content')
    {{-- Filter card --}}
    <x-search-card>
        <div class="col-md-3 mb-3">
            <x-form.input name="search" :label="__('foundation::foundation.common.search')" :value="request('search')" :placeholder="__('thing::thing.index.search_placeholder')" />
        </div>
        <div class="col-md-3 mb-3">
            <x-form.select class="select" name="is_active" :label="__('foundation::foundation.common.status')" :options="integerStatus()" :selected="request('is_active')" />
        </div>
    </x-search-card>

    {{-- Table --}}
    <x-table-view-pagination :title="__('thing::thing.index.title')" :data="$things" :empty-message="__('thing::thing.index.empty')">
        <x-slot name="actions">
            @can('Create Thing')
                <a href="{{ route('admin.things.create') }}" class="btn btn-sm btn-primary">
                    <i class="ph-plus"></i>{{ __('thing::thing.index.add') }}
                </a>
            @endcan
        </x-slot>

        <thead>
            <tr>
                <th>{{ __('foundation::foundation.common.name') }}</th>
                <th>{{ __('foundation::foundation.common.status') }}</th>
                <th class="text-end">{{ __('foundation::foundation.common.action') }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($things as $thing)
                <tr>
                    <td>{{ $thing->name }}</td>
                    <td><x-status-badge :active="$thing->is_active" /></td>
                    <td class="text-end">
                        <x-dropdown-menu>
                            @can('View Thing')
                                <x-dropdown-link :url="route('admin.things.show', $thing->id)">
                                    <i class="ph-eye"></i>{{ __('foundation::foundation.common.view') }}
                                </x-dropdown-link>
                            @endcan
                            @can('Edit Thing')
                                <x-dropdown-link :url="route('admin.things.edit', $thing->id)">
                                    <i class="ph-pencil-simple"></i>{{ __('foundation::foundation.common.edit') }}
                                </x-dropdown-link>
                            @endcan
                            @can('Delete Thing')
                                <div class="dropdown-divider"></div>
                                <x-dropdown-link
                                    :url="route('admin.things.destroy', $thing->id)"
                                    class="text-danger swal-delete"
                                    data-text="{{ __('thing::thing.index.delete_confirm', ['name' => $thing->name]) }}">
                                    <i class="ph-trash"></i>{{ __('foundation::foundation.common.delete') }}
                                </x-dropdown-link>
                            @endcan
                        </x-dropdown-menu>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </x-table-view-pagination>
@endsection
```

---

### Create Form

```blade
@extends('thing::layouts.master')

@section('breadcrumb')
    <a href="{{ route('admin.things.index') }}" class="breadcrumb-item">{{ __('thing::thing.index.title') }}</a>
    <span class="breadcrumb-item active">{{ __('thing::thing.create.title') }}</span>
@endsection

@section('content')
<form action="{{ route('admin.things.store') }}" method="POST" enctype="multipart/form-data">
    @csrf

    <x-page-header
        :title="__('thing::thing.create.title')"
        icon="ph-plus"
        :back-url="route('admin.things.index')"
        :back-label="__('thing::thing.form.back')" />

    <x-form-section :title="__('thing::thing.form.basic_information')" icon="ph-info">
        <div class="row g-3">
            <div class="col-md-6">
                <x-form.input name="name" :label="__('foundation::foundation.common.name')" required />
            </div>
            <div class="col-md-6">
                <x-form.select class="select" name="is_active" :label="__('foundation::foundation.common.status')" required :options="integerStatus()" selected="1" />
            </div>
        </div>
    </x-form-section>

    <div class="d-flex justify-content-between align-items-center">
        <a href="{{ route('admin.things.index') }}" class="btn btn-light">
            <i class="ph-x"></i>{{ __('foundation::foundation.common.cancel') }}
        </a>
        <x-primary-button class="px-5">
            <i class="ph-floppy-disk"></i>{{ __('thing::thing.create.submit') }}
        </x-primary-button>
    </div>

</form>
@endsection
```

---

### Edit Form

```blade
@extends('thing::layouts.master')

@section('breadcrumb')
    <a href="{{ route('admin.things.index') }}" class="breadcrumb-item">{{ __('thing::thing.index.title') }}</a>
    <span class="breadcrumb-item active">{{ $thing->name }}</span>
@endsection

@section('content')
<form action="{{ route('admin.things.update', $thing->id) }}" method="POST" enctype="multipart/form-data">
    @csrf
    @method('PUT')

    <x-page-header
        :title="__('thing::thing.edit.title', ['name' => $thing->name])"
        icon="ph-pencil-simple"
        :back-url="route('admin.things.index')"
        :back-label="__('thing::thing.form.back')">
        <x-slot name="actions">
            <x-status-badge :active="$thing->is_active" class="fs-xs" />
        </x-slot>
    </x-page-header>

    <x-form-section :title="__('thing::thing.form.basic_information')" icon="ph-info">
        <div class="row g-3">
            <div class="col-md-6">
                <x-form.input name="name" :label="__('foundation::foundation.common.name')" required :value="$thing->name" />
                {{-- Every field's current value is passed explicitly — there is
                     no model-binding auto-population to rely on. --}}
            </div>
            <div class="col-md-6">
                <x-form.select class="select" name="is_active" :label="__('foundation::foundation.common.status')" required :options="integerStatus()" :selected="(int) $thing->is_active" />
            </div>
        </div>
    </x-form-section>

    <div class="d-flex justify-content-between align-items-center">
        <a href="{{ route('admin.things.index') }}" class="btn btn-light">
            <i class="ph-x"></i>{{ __('foundation::foundation.common.cancel') }}
        </a>
        <x-primary-button class="px-5">
            <i class="ph-floppy-disk"></i>{{ __('thing::thing.edit.submit') }}
        </x-primary-button>
    </div>

</form>
@endsection
```

---

### View Conventions

**Forms:**
- A plain `<form>` with `@csrf` (and `@method('PUT')` for an update). Include `enctype="multipart/form-data"` when the form has file uploads.
- Fields are `<x-form.input>` / `<x-form.select>` / `<x-form.textarea>` / `<x-form.file>` (see `patterns.md`), each explicitly given `:value`/`:selected` — there is no model-binding auto-population, so an edit form passes the model's current attribute at every field.
- Use `class="select"` + `data-placeholder` on a `<x-form.select>` that should be a Select2 dropdown — not every select is one; match what the field actually needs.
- Mark a required field with the component's `required` prop, not a CSS class — it adds both the `*` and the HTML `required` attribute.

**Page structure:**
- Create/edit forms must use `<x-page-header>` for the top heading and `<x-form-section>` for each card section — never write raw `<div class="card">` header markup manually.
- The `$actions` slot carries context (badges, status, an avatar, a secondary action). The submit button goes in the bottom row below the last section: `btn-light` Cancel on the leading edge, `<x-primary-button>` on the trailing edge.

**Icons:** Phosphor (`ph-*`) is the only icon set — Font Awesome is not loaded, so an `fa-*` class renders nothing. Common ones: `ph-plus`, `ph-pencil-simple`, `ph-eye`, `ph-trash`, `ph-floppy-disk`, `ph-arrow-left`, `ph-x`, `ph-check-circle`, `ph-warning-circle`. An icon inside a `.btn`, a `.dropdown-item` or a `.navbar-nav-link` takes **no** `me-1`/`me-2` — those containers set their own gap.

**Buttons:** `btn-primary` for the one primary action, `btn-light` for Cancel/Back/Reset and other secondary buttons, `btn-ghost` when it should be borderless, `btn-icon` for a square icon-only button. Don't write `btn-outline-secondary` in a new view. Anything in a card header or a table row also takes `btn-sm`.

**Authorization:** Gate all action buttons and links with `@can('Permission Name') ... @endcan`.

**Status:** `<x-status-badge :active="$thing->is_active" />` — a `.fd-status` dot plus an already-translated label; don't hand-write badge markup for state. A label or count that isn't state is a soft badge: `bg-*-subtle` + `text-*-emphasis`.

**Styling:** No inline `style=` attributes, and never add rules to the package's `assets/css/foundation.css` — it is overwritten on update. Use Bootstrap utilities, the `.fd-*` classes and the `--fd-*` tokens (see `ui-components.md`); project-wide CSS belongs in `resources/css/app.css`.

**Scripts:** Add page-specific JS with `@push('scripts') <script>...</script> @endpush` at the bottom of the view.

**Confirmations:** `class="swal-confirm"` + `data-text="..."` follows the link after a yes/no; `swal-delete` submits it as a DELETE; `swal-post` submits it with the method in `data-method`.

---

### Sidebar Menu (`config/menu.php`)

A module adds sidebar pages by declaring them in `Modules/{Name}/config/menu.php`. `SidebarMenu` collects the items of every enabled module and the sidebar layout renders them.

```php
return [
    [
        'group' => 'administration',          // parent key from config/sidebar.php
        'label' => 'Things',
        'icon' => 'ph-cube',
        'route' => 'admin.things.index',
        // Other routes that keep this item highlighted and its parent open.
        'routes' => ['admin.things.show', 'admin.things.create', 'admin.things.edit'],
        // Shown when the user holds any of these. Omit for a page everyone may see.
        'permissions' => ['View Thing', 'Create Thing', 'Edit Thing', 'Delete Thing'],
        'order' => 50,
    ],
];
```

- `group` must match a key in the project's `config/sidebar.php`, which fixes the order, label and icon of the parents. Add a new parent there when none fits.
- An item is hidden when its route does not exist, so a disabled module contributes nothing.
- Use `url` (with optional `target`) instead of `route` for an external link.
- Never build sidebar markup in a module. The layout renders the tree from this config.
