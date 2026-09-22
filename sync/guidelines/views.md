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
<x-module-layout route="admin.things.index" label="Things" />
```

A page still `@extends('thing::layouts.master')` and fills `@section('breadcrumb')` / `@section('content')` exactly as before — `route`/`label` only supply the layout's own "Home → Things" breadcrumb prefix.

---

### Flash Messages

Flash with `success` or `error` keys — SweetAlert2 renders them automatically:
```php
return redirect()->route('admin.things.index')->with('success', 'Thing created successfully');
return back()->with('error', 'Failed to create thing');
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
| `<x-page-header title="" icon="" subtitle="" :back-url="" back-label="">` | Page-level heading with optional back button and action slot |
| `<x-form-section title="" icon="">` | Wraps a card section inside a create/edit form |
| `<x-search-card>` | Filter form wrapper for list pages |
| `<x-table-view-pagination>` | Card + table + pagination for list pages |
| `<x-dropdown-menu>` | Action dropdown in table rows |
| `<x-dropdown-link :url="">` | Link item inside `x-dropdown-menu` |
| `<x-modal id="" title="">` | Bootstrap modal dialog |
| `<x-module-layout route="" label="">` | A module's `layouts/master.blade.php`, in one line |
| `<x-form.input>`, `<x-form.select>`, `<x-form.textarea>`, `<x-form.file>`, `<x-form.checkbox>`, `<x-form.label>` | Labeled form fields — old-input, validation errors and `is-invalid` built in (see `patterns.md` / `ui-components.md` for the full API) |

`<x-page-header>` supports an `$actions` slot for buttons placed on the right side:
```blade
<x-page-header title="Create Thing" icon="ph-plus" :back-url="route('admin.things.index')" back-label="Back to List">
    <x-slot name="actions">
        <button type="submit" class="btn btn-primary px-5">
            <i class="ph-floppy-disk me-1"></i>Save
        </button>
    </x-slot>
</x-page-header>
```

---

### Index / List Page

```blade
@extends('thing::layouts.master')

@section('breadcrumb')
    <span class="breadcrumb-item active">Thing List</span>
@endsection

@section('content')
    {{-- Filter card --}}
    <x-search-card>
        <div class="col-md-3 mb-3">
            <x-form.input name="search" label="Search" :value="request('search')" placeholder="Search..." />
        </div>
        <div class="col-md-3 mb-3">
            <x-form.select class="select" name="is_active" label="Status" :options="integerStatus()" :selected="request('is_active')" data-placeholder="Select Status..." placeholder="All" />
        </div>
    </x-search-card>

    {{-- Table --}}
    <x-table-view-pagination title="Thing List" :data="$things">
        <x-slot name="actions">
            @can('Create Thing')
                <a href="{{ route('admin.things.create') }}" class="btn btn-primary w-sm">
                    <i class="ph-plus me-1"></i> Add Thing
                </a>
            @endcan
        </x-slot>

        <thead>
            <tr>
                <th>Name</th>
                <th>Status</th>
                <th class="text-end">Action</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($things as $thing)
                <tr>
                    <td>{{ $thing->name }}</td>
                    <td>
                        @if ($thing->is_active)
                            <span class="badge bg-success">Active</span>
                        @else
                            <span class="badge bg-danger">Inactive</span>
                        @endif
                    </td>
                    <td class="text-end">
                        <x-dropdown-menu>
                            @can('View Thing')
                                <x-dropdown-link :url="route('admin.things.show', $thing->id)">
                                    <i class="ph-eye me-2"></i> View
                                </x-dropdown-link>
                            @endcan
                            @can('Edit Thing')
                                <x-dropdown-link :url="route('admin.things.edit', $thing->id)">
                                    <i class="ph-pencil-simple me-2"></i> Edit
                                </x-dropdown-link>
                            @endcan
                            @can('Delete Thing')
                                <x-dropdown-link
                                    :url="route('admin.things.destroy', $thing->id)"
                                    class="text-danger swal-confirm"
                                    data-text="Are you sure you want to delete this thing?">
                                    <i class="ph-trash me-2"></i> Delete
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
    <a href="{{ route('admin.things.index') }}" class="breadcrumb-item">Thing List</a>
    <span class="breadcrumb-item active">Create Thing</span>
@endsection

@section('content')
<form action="{{ route('admin.things.store') }}" method="POST" enctype="multipart/form-data">
    @csrf

    <x-page-header
        title="Create New Thing"
        subtitle="Fill in the details below"
        icon="ph-plus"
        :back-url="route('admin.things.index')"
        back-label="Back to List">
        <x-slot name="actions">
            <button type="submit" class="btn btn-primary px-5">
                <i class="ph-floppy-disk me-1"></i>Create Thing
            </button>
        </x-slot>
    </x-page-header>

    <x-form-section title="Basic Information" icon="ph-info">
        <div class="row g-3">
            <div class="col-md-6">
                <x-form.input name="name" label="Name" required placeholder="Enter name" />
            </div>
            <div class="col-md-6">
                <x-form.select class="select" name="is_active" label="Status" required :options="integerStatus()" selected="1" data-placeholder="Select status…" />
            </div>
        </div>
    </x-form-section>

</form>
@endsection
```

---

### Edit Form

```blade
@extends('thing::layouts.master')

@section('breadcrumb')
    <a href="{{ route('admin.things.index') }}" class="breadcrumb-item">Thing List</a>
    <span class="breadcrumb-item active">{{ $thing->name }}</span>
@endsection

@section('content')
<form action="{{ route('admin.things.update', $thing->id) }}" method="POST" enctype="multipart/form-data">
    @csrf
    @method('PUT')

    <x-page-header
        title="Edit: {{ $thing->name }}"
        icon="ph-pencil-simple"
        :back-url="route('admin.things.index')"
        back-label="Back to List">
        <x-slot name="actions">
            <button type="submit" class="btn btn-primary px-5">
                <i class="ph-floppy-disk me-1"></i>Update Thing
            </button>
        </x-slot>
    </x-page-header>

    <x-form-section title="Basic Information" icon="ph-info">
        <div class="row g-3">
            <div class="col-md-6">
                <x-form.input name="name" label="Name" required :value="$thing->name" />
                {{-- Every field's current value is passed explicitly — there is
                     no model-binding auto-population to rely on. --}}
            </div>
            <div class="col-md-6">
                <x-form.select class="select" name="is_active" label="Status" required :options="integerStatus()" :selected="(int) $thing->is_active" />
            </div>
        </div>
    </x-form-section>

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
- Place the submit button inside `<x-page-header>`'s `$actions` slot, not in a separate footer row.

**Icons:** Use Phosphor icons (`ph-*`). Common ones: `ph-plus`, `ph-pencil-simple`, `ph-eye`, `ph-trash`, `ph-floppy-disk`, `ph-arrow-left`, `ph-x`, `ph-check-circle`, `ph-warning`.

**Authorization:** Gate all action buttons and links with `@can('Permission Name') ... @endcan`.

**Status badges:** `<span class="badge bg-success">Active</span>` / `<span class="badge bg-danger">Inactive</span>`.

**Scripts:** Add page-specific JS with `@push('scripts') <script>...</script> @endpush` at the bottom of the view.

**Delete confirmation:** Add `class="swal-confirm"` and `data-text="..."` to any link/button to get an automatic SweetAlert2 confirmation before following it.

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
