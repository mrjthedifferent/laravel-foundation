@extends('rolepermission::layouts.master')

@section('breadcrumb')
<span class="breadcrumb-item active">{{ __('rolepermission::rolepermission.index.manage_permissions') }}</span>
@endsection

@section('content')
<x-search-card>
    <div class="col-md-6 mb-2">
        <x-form.input name="name" label="{{ __('rolepermission::rolepermission.manage_permissions.permission_name_label') }}" :value="request('name')" placeholder="{{ __('rolepermission::rolepermission.manage_permissions.search_placeholder') }}" />
    </div>
    <div class="col-md-6 mb-2">
        <x-form.select class="select" name="module" label="{{ __('rolepermission::rolepermission.manage_permissions.module_label') }}" :options="['' => __('rolepermission::rolepermission.manage_permissions.all_modules')] + $modules->mapWithKeys(fn ($m) => [$m => display_label($m)])->all()" :selected="request('module')" data-placeholder="{{ __('rolepermission::rolepermission.manage_permissions.all_modules') }}" />
    </div>
</x-search-card>

<x-table-view-pagination title="{{ __('rolepermission::rolepermission.manage_permissions.title') }}" :data="$permissions" empty-icon="ph-shield-slash" empty-message="{{ __('rolepermission::rolepermission.manage_permissions.empty') }}">
    <x-slot name="actions">
        <x-table-action id="bulk-delete-btn" class="btn-danger btn-sm d-none" icon="ph-trash" title="{{ __('rolepermission::rolepermission.manage_permissions.delete_selected') }}" />
        <x-table-action class="btn-primary btn-sm me-2" icon="ph-plus" title="{{ __('rolepermission::rolepermission.manage_permissions.create_permission') }}" data-bs-toggle="modal" data-bs-target="#createPermissionModal" />
        <x-table-action :href="route('admin.permission.sync')" class="btn-success btn-sm me-2 swal-post" icon="ph-eject" title="{{ __('rolepermission::rolepermission.manage_permissions.sync_permissions') }}" data-text="{{ __('rolepermission::rolepermission.manage_permissions.sync_permissions_confirm') }}" />
    </x-slot>

    <thead>
        <tr>
            <th style="width:36px"><input type="checkbox" class="form-check-input" id="select-all"></th>
            <th>{{ __('rolepermission::rolepermission.manage_permissions.module_label') }}</th>
            <th>{{ __('rolepermission::rolepermission.manage_permissions.permission_name_label') }}</th>
            <th>{{ __('foundation::foundation.common.description') }}</th>
            <th>{{ __('rolepermission::rolepermission.manage_permissions.col_used_by') }}</th>
            <th class="text-end" style="width:60px">{{ __('foundation::foundation.common.action') }}</th>
        </tr>
    </thead>
    <tbody>
        <form id="bulk-delete-form" action="{{ route('admin.permission.bulk-delete') }}" method="POST">
            @csrf @method('DELETE')
            @foreach ($permissions as $permission)
            <tr>
                <td><input type="checkbox" class="form-check-input permission-checkbox" name="permission_ids[]" value="{{ $permission->id }}"></td>
                <td><span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle fs-xs">{{ display_label($permission->module_name) }}</span></td>
                <td class="fw-semibold fs-sm">{{ display_label($permission->name) }}</td>
                <td class="text-muted fs-sm">{{ $permission->description ?: '—' }}</td>
                <td>
                    <span class="badge bg-info-subtle text-info border border-info-subtle">{{ __('rolepermission::rolepermission.manage_permissions.roles_count', ['count' => $permission->roles_count]) }}</span>
                    @if ($permission->roles_count > 0)
                    <a href="#" class="show-roles ms-1" data-permission-id="{{ $permission->id }}" title="{{ __('rolepermission::rolepermission.manage_permissions.view_roles_title') }}">
                        <i class="ph-info text-muted"></i>
                    </a>
                    @endif
                </td>
                <td class="text-end">
                    <x-dropdown-menu>
                        <button type="button" class="dropdown-item text-danger swal-delete"
                            data-url="{{ route('admin.permission.delete', $permission->id) }}"
                            data-text="{{ __('rolepermission::rolepermission.manage_permissions.delete_permission_confirm') }}">
                            <i class="ph-trash me-2"></i>{{ __('foundation::foundation.common.delete') }}
                        </button>
                    </x-dropdown-menu>
                </td>
            </tr>
            @endforeach
        </form>
    </tbody>
</x-table-view-pagination>

{{-- Create Permission Modal --}}
<x-modal id="createPermissionModal" title="{{ __('rolepermission::rolepermission.manage_permissions.create_permission') }}">
    <form id="createPermissionForm" action="{{ route('admin.permission.store') }}" method="POST">
        @csrf
        <div class="mb-3">
            <x-form.select class="select" name="module_name" id="module_name" label="{{ __('rolepermission::rolepermission.manage_permissions.module_name_label') }}" required :options="['' => __('rolepermission::rolepermission.manage_permissions.select_module')] + $modules->mapWithKeys(fn ($m) => [$m => display_label($m)])->all() + ['new' => __('rolepermission::rolepermission.manage_permissions.create_new_module')]" :selected="null" />
        </div>
        <div class="mb-3 d-none" id="new-module-container">
            <x-form.input name="new_module_name" id="new_module_name" label="{{ __('rolepermission::rolepermission.manage_permissions.new_module_name_label') }}" />
        </div>
        <div class="mb-3">
            <x-form.input name="permission_name" id="permission_name" label="{{ __('rolepermission::rolepermission.manage_permissions.permission_name_label') }}" required />
        </div>
        <div class="mb-3">
            <x-form.textarea name="description" label="{{ __('rolepermission::rolepermission.manage_permissions.description_optional_label') }}" :rows="2" />
        </div>
        <x-slot name="footer">
            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">{{ __('foundation::foundation.common.cancel') }}</button>
            <button type="submit" form="createPermissionForm" class="btn btn-primary">{{ __('foundation::foundation.common.create') }}</button>
        </x-slot>
    </form>
</x-modal>

{{-- Permission Roles Modal --}}
<x-modal id="permissionRolesModal" title="{{ __('rolepermission::rolepermission.manage_permissions.roles_using_permission_title') }}" size="sm">
    <div class="permission-roles-list">
        <div class="text-center py-3">
            <div class="spinner-border spinner-border-sm"></div>
        </div>
    </div>
    <x-slot name="footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">{{ __('foundation::foundation.common.close') }}</button>
    </x-slot>
</x-modal>
@endsection

@push('scripts')
<script>
    const rolepermissionI18n = {
        noRolesUsingPermission: @js(__('rolepermission::rolepermission.manage_permissions.no_roles_using_permission')),
        loadFailed: @js(__('rolepermission::rolepermission.manage_permissions.load_failed')),
        bulkDeleteConfirmTitle: @js(__('rolepermission::rolepermission.manage_permissions.bulk_delete_confirm_title')),
        bulkDeleteConfirmText: @js(__('rolepermission::rolepermission.manage_permissions.bulk_delete_confirm_text')),
        delete: @js(__('foundation::foundation.common.delete')),
    };

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
                title: rolepermissionI18n.bulkDeleteConfirmTitle,
                text: rolepermissionI18n.bulkDeleteConfirmText,
                icon: 'warning',
                confirmText: rolepermissionI18n.delete,
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
                    else html += `<li class="list-group-item py-1 text-muted fs-sm">${rolepermissionI18n.noRolesUsingPermission}</li>`;
                    html += '</ul>';
                    $('.permission-roles-list').html(html);
                },
                error: () => $('.permission-roles-list').html(`<div class="alert alert-danger mb-0">${rolepermissionI18n.loadFailed}</div>`),
            });
        });
    });
</script>
@endpush