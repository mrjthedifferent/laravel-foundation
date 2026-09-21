@extends('rolepermission::layouts.master')

@section('breadcrumb')
<span class="breadcrumb-item active">Manage Permissions</span>
@endsection

@section('content')
<x-search-card>
    <div class="col-md-6 mb-2">
        {!! Form::label('name', 'Permission Name', ['class' => 'form-label fs-sm']) !!}
        {!! Form::text('name', request('name'), ['class' => 'form-control form-control-sm', 'placeholder' => 'Search by name…']) !!}
    </div>
    <div class="col-md-6 mb-2">
        {!! Form::label('module', 'Module', ['class' => 'form-label fs-sm']) !!}
        {!! Form::select('module', ['' => 'All Modules'] + array_combine($modules->toArray(), $modules->toArray()), request('module'), ['class' => 'form-control form-control-sm select', 'data-placeholder' => 'All Modules']) !!}
    </div>
</x-search-card>

<x-table-view-pagination title="Permissions" :data="$permissions" empty-icon="ph-shield-slash" empty-message="No permissions found">
    <x-slot name="actions">
        <x-table-action id="bulk-delete-btn" class="btn-danger btn-sm d-none" icon="ph-trash" title="Delete Selected" />
        <x-table-action class="btn-primary btn-sm me-2" icon="ph-plus" title="Create Permission" data-bs-toggle="modal" data-bs-target="#createPermissionModal" />
        <x-table-action :href="route('admin.permission.sync')" class="btn-success btn-sm me-2 swal-post" icon="ph-eject" title="Sync Permissions" data-text="Sync permissions? This will run the PermissionSeeder." />
    </x-slot>

    <thead>
        <tr>
            <th style="width:36px"><input type="checkbox" class="form-check-input" id="select-all"></th>
            <th>Module</th>
            <th>Permission Name</th>
            <th>Description</th>
            <th>Used By</th>
            <th class="text-end" style="width:60px">Action</th>
        </tr>
    </thead>
    <tbody>
        <form id="bulk-delete-form" action="{{ route('admin.permission.bulk-delete') }}" method="POST">
            @csrf @method('DELETE')
            @foreach ($permissions as $permission)
            <tr>
                <td><input type="checkbox" class="form-check-input permission-checkbox" name="permission_ids[]" value="{{ $permission->id }}"></td>
                <td><span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle fs-xs">{{ $permission->module_name }}</span></td>
                <td class="fw-semibold fs-sm">{{ $permission->name }}</td>
                <td class="text-muted fs-sm">{{ $permission->description ?: '—' }}</td>
                <td>
                    <span class="badge bg-info-subtle text-info border border-info-subtle">{{ $permission->roles_count }} roles</span>
                    @if ($permission->roles_count > 0)
                    <a href="#" class="show-roles ms-1" data-permission-id="{{ $permission->id }}" title="View roles">
                        <i class="ph-info text-muted"></i>
                    </a>
                    @endif
                </td>
                <td class="text-end">
                    <x-dropdown-menu>
                        <button type="button" class="dropdown-item text-danger swal-delete"
                            data-url="{{ route('admin.permission.delete', $permission->id) }}"
                            data-text="Delete this permission? May break functionality if in use.">
                            <i class="ph-trash me-2"></i>Delete
                        </button>
                    </x-dropdown-menu>
                </td>
            </tr>
            @endforeach
        </form>
    </tbody>
</x-table-view-pagination>

{{-- Create Permission Modal --}}
<x-modal id="createPermissionModal" title="Create Permission">
    <form id="createPermissionForm" action="{{ route('admin.permission.store') }}" method="POST">
        @csrf
        <div class="mb-3">
            {!! Form::label('module_name', 'Module Name', ['class' => 'form-label fw-semibold fs-sm']) !!}
            {!! Form::select('module_name', ['' => 'Select Module'] + array_combine($modules->toArray(), $modules->toArray()) + ['new' => '+ Create New Module'], null, ['id' => 'module_name', 'class' => 'form-control form-control-sm select', 'required']) !!}
        </div>
        <div class="mb-3 d-none" id="new-module-container">
            {!! Form::label('new_module_name', 'New Module Name', ['class' => 'form-label fw-semibold fs-sm']) !!}
            {!! Form::text('new_module_name', null, ['id' => 'new_module_name', 'class' => 'form-control form-control-sm']) !!}
        </div>
        <div class="mb-3">
            {!! Form::label('permission_name', 'Permission Name', ['class' => 'form-label fw-semibold fs-sm']) !!}
            {!! Form::text('permission_name', null, ['id' => 'permission_name', 'class' => 'form-control form-control-sm', 'required']) !!}
        </div>
        <div class="mb-3">
            {!! Form::label('description', 'Description (Optional)', ['class' => 'form-label fw-semibold fs-sm']) !!}
            {!! Form::textarea('description', null, ['class' => 'form-control form-control-sm', 'rows' => 2]) !!}
        </div>
        <x-slot name="footer">
            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
            <button type="submit" form="createPermissionForm" class="btn btn-primary">Create</button>
        </x-slot>
    </form>
</x-modal>

{{-- Permission Roles Modal --}}
<x-modal id="permissionRolesModal" title="Roles Using This Permission" size="sm">
    <div class="permission-roles-list">
        <div class="text-center py-3">
            <div class="spinner-border spinner-border-sm"></div>
        </div>
    </div>
    <x-slot name="footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
    </x-slot>
</x-modal>
@endsection

@push('scripts')
<script>
    $(document).ready(function() {
        $("#select-all").change(function() {
            $(".permission-checkbox").prop('checked', $(this).prop('checked'));
            toggleBulkDeleteButton();
        });
        $(".permission-checkbox").change(function() {
            toggleBulkDeleteButton();
        });

        function toggleBulkDeleteButton() {
            $("#bulk-delete-btn").toggleClass('d-none', $(".permission-checkbox:checked").length === 0);
        }
        $("#bulk-delete-btn").click(function(e) {
            e.preventDefault();
            window.showConfirm({
                title: 'Delete selected permissions?',
                text: 'May break functionality if in use.',
                icon: 'warning',
                confirmText: 'Delete',
                confirmClass: 'btn btn-danger',
                onConfirm: function() { $("#bulk-delete-form").submit(); }
            });
        });
        $("#module_name").change(function() {
            $("#new-module-container").toggleClass('d-none', $(this).val() !== 'new');
        });
        $(".show-roles").click(function(e) {
            e.preventDefault();
            const id = $(this).data('permission-id');
            $('#permissionRolesModal').modal('show');
            $.ajax({
                url: "{{ route('admin.permission.roles', ':id') }}".replace(':id', id),
                success: function(res) {
                    const roles = res.data && res.data.roles ? res.data.roles : res.roles;
                    let html = '<ul class="list-group list-group-flush">';
                    if (roles && roles.length) roles.forEach(r => {
                        html += `<li class="list-group-item py-1 fs-sm">${r.name}</li>`;
                    });
                    else html += '<li class="list-group-item py-1 text-muted fs-sm">No roles using this permission.</li>';
                    html += '</ul>';
                    $('.permission-roles-list').html(html);
                },
                error: () => $('.permission-roles-list').html('<div class="alert alert-danger mb-0">Failed to load.</div>'),
            });
        });
    });
</script>
@endpush