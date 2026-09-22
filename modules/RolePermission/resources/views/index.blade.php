@extends('rolepermission::layouts.master')

@section('breadcrumb')
<span class="breadcrumb-item active">Roles</span>
@endsection

@section('content')
<x-search-card>
    <div class="col-md-12 mb-2">
        <x-form.input name="name" label="Search" :value="request('name')" placeholder="Role name…" />
    </div>
</x-search-card>

<x-table-view-pagination title="Roles" :data="$roles" empty-icon="ph-shield" empty-message="No roles found">
    <x-slot name="actions">
        <x-table-actions>
            @can('Assign Permission')
                <x-table-action :href="route('admin.permissions.manage')" class="btn-info" icon="ph-shield" title="Manage Permissions" />
            @endcan
            @can('Create Role')
                <x-table-action class="btn-primary" icon="ph-plus" title="Create Role" data-bs-toggle="modal" data-bs-target="#createRoleModal" />
            @endcan
        </x-table-actions>
    </x-slot>

    <thead>
        <tr>
            <th>Name</th>
            <th>Permissions Assigned</th>
            <th>Users Assigned</th>
            <th class="text-end" style="width:60px">Action</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($roles as $role)
        <tr>
            <td class="fw-semibold">{{ $role->name }}</td>
            <td>
                <span class="badge bg-info-subtle text-info border border-info-subtle">{{ $role->permissions_count }}</span>
            </td>
            <td>
                @if ($role->users_count > 0)
                <span class="badge bg-warning-subtle text-warning border border-warning-subtle">{{ $role->users_count }}</span>
                @else
                <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle">0</span>
                @endif
            </td>
            <td class="text-end">
                <x-dropdown-menu>
                    @can('Edit Role')
                    <button type="button" class="dropdown-item edit_role"
                        data-role-id="{{ $role->id }}"
                        data-role-name="{{ $role->name }}"
                        data-url="{{ route('admin.role.update', $role->id) }}">
                        <i class="ph-pencil me-2"></i>Edit
                    </button>
                    @endcan
                    @can('Assign Permission')
                    <x-dropdown-link :url="route('admin.role.assign.permission.get', $role->id)">
                        <i class="ph-shield-check me-2"></i>Assign Permissions
                    </x-dropdown-link>
                    @endcan
                    @can('Create Role')
                    <x-dropdown-link :url="route('admin.role.clone', $role->id)"
                        class="swal-confirm"
                        data-text="Clone this role with all its permissions?">
                        <i class="ph-copy me-2"></i>Clone Role
                    </x-dropdown-link>
                    @endcan
                    @can('Delete Role')
                    @if ($role->users_count === 0)
                    <x-dropdown-link :url="route('admin.role.destroy', $role->id)"
                        class="swal-delete text-danger"
                        data-text="Delete this role? This action cannot be undone.">
                        <i class="ph-trash me-2"></i>Delete Role
                    </x-dropdown-link>
                    @endif
                    @endcan
                </x-dropdown-menu>
            </td>
        </tr>
        @endforeach
    </tbody>
</x-table-view-pagination>

{{-- Create Role Modal --}}
<x-modal id="createRoleModal" title="Create Role" size="sm">
    <form action="{{ route('admin.role.store') }}" method="POST" id="createForm">
        @csrf
        <div class="mb-3">
            <x-form.input name="role_name" label="Role Name" required placeholder="Role name" />
        </div>
        <x-slot name="footer">
            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
            <button type="submit" form="createForm" class="btn btn-primary">Create Role</button>
        </x-slot>
    </form>
</x-modal>

{{-- Edit Role Modal --}}
<x-modal id="updateRoleModal" title="Edit Role" size="sm">
    <form id="updateForm" method="POST">
        @csrf @method('PUT')
        <div class="mb-3">
            <x-form.input name="role_name" id="edit_role_name" label="Role Name" required placeholder="Role name" />
        </div>
    </form>
    <x-slot name="footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" form="updateForm" class="btn btn-primary">Update Role</button>
    </x-slot>
</x-modal>
@endsection

@push('scripts')
<script>
    $('.edit_role').on('click', function(e) {
        e.preventDefault();
        $('#updateForm').attr('action', $(this).data('url'));
        $('#edit_role_name').val($(this).data('role-name'));
        $('#updateRoleModal').modal('show');
    });
</script>
@endpush