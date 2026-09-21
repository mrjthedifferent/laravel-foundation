@extends('user::layouts.master')

@section('breadcrumb')
<span class="breadcrumb-item active">User List</span>
@endsection

@section('content')
<x-search-card>
    <div class="col-md-3 mb-2">
        {!! Form::label('search', 'Search', ['class' => 'form-label fs-sm']) !!}
        {!! Form::text('search', request('search'), ['class' => 'form-control form-control-sm', 'placeholder' => 'Name, email or phone…']) !!}
    </div>
    <div class="col-md-3 mb-2">
        {!! Form::label('role_id', 'Role', ['class' => 'form-label fs-sm']) !!}
        {!! Form::select('role_id[]', $roles, request('role_id'), [
        'class' => 'form-control form-control-sm select',
        'data-placeholder' => 'All Roles',
        'multiple',
        ]) !!}
    </div>
    <div class="col-md-3 mb-2">
        {!! Form::label('gender', 'Gender', ['class' => 'form-label fs-sm']) !!}
        {!! Form::select('gender', ['' => 'All', 'male' => 'Male', 'female' => 'Female', 'other' => 'Other'], request('gender'), [
        'class' => 'form-control form-control-sm select',
        'data-placeholder' => 'All Genders',
        ]) !!}
    </div>
    <div class="col-md-3 mb-2">
        {!! Form::label('is_active', 'Status', ['class' => 'form-label fs-sm']) !!}
        {!! Form::select('is_active', ['' => 'All', '1' => 'Active', '0' => 'Inactive'], request('is_active'), [
        'class' => 'form-control form-control-sm select',
        'data-placeholder' => 'All',
        ]) !!}
    </div>
    <div class="col-md-3 mb-2">
        {!! Form::label('email_verified', 'Email Verified', ['class' => 'form-label fs-sm']) !!}
        {!! Form::select('email_verified', ['' => 'All', '1' => 'Verified', '0' => 'Unverified'], request('email_verified'), [
        'class' => 'form-control form-control-sm select',
        'data-placeholder' => 'All',
        ]) !!}
    </div>
    <div class="col-md-3 mb-2">
        {!! Form::label('phone_verified', 'Phone Verified', ['class' => 'form-label fs-sm']) !!}
        {!! Form::select('phone_verified', ['' => 'All', '1' => 'Verified', '0' => 'Unverified'], request('phone_verified'), [
        'class' => 'form-control form-control-sm select',
        'data-placeholder' => 'All',
        ]) !!}
    </div>
    <div class="col-md-3 mb-2">
        {!! Form::label('date_from', 'Registered From', ['class' => 'form-label fs-sm']) !!}
        {!! Form::date('date_from', request('date_from'), ['class' => 'form-control form-control-sm']) !!}
    </div>
    <div class="col-md-3 mb-2">
        {!! Form::label('date_to', 'Registered To', ['class' => 'form-label fs-sm']) !!}
        {!! Form::date('date_to', request('date_to'), ['class' => 'form-control form-control-sm']) !!}
    </div>
</x-search-card>

<x-table-view-pagination
    title="Users"
    :data="$users"
    empty-message="No users found"
    empty-icon="ph-users">
    <x-slot name="actions">
        <x-table-actions>
            @can('Create User')
            <x-table-action :href="route('admin.users.create')" icon="ph-plus" title="Add User" />
            @endcan
            @can('Import User')
            <x-table-action :href="route('admin.users.bulk.create')" icon="ph-upload-simple" title="Import Users" />
            @endcan
        </x-table-actions>
    </x-slot>

    <x-slot name="exports">
        @can('Export User')
        <x-table-export-dropdown>
            <x-table-export-item :href="route('admin.users.export').'?'.http_build_query(array_merge(request()->query(), ['format' => 'csv']))"
                class="swal-confirm"
                icon="ph-file-csv"
                title="CSV"
                data-text="Export the current filtered user list to CSV?" />
            <x-table-export-item :href="route('admin.users.export').'?'.http_build_query(array_merge(request()->query(), ['format' => 'xlsx']))"
                class="swal-confirm"
                icon="ph-file-xls"
                title="Excel"
                data-text="Export the current filtered user list to Excel?" />

            <x-table-export-item :href="route('admin.users.export').'?'.http_build_query(array_merge(request()->query(), ['format' => 'pdf']))"
                class="swal-confirm"
                icon="ph-file-pdf"
                title="PDF"
                data-text="Export the current filtered user list to PDF?" />
        </x-table-export-dropdown>
        @endcan
    </x-slot>

    <thead>
        <tr>
            <th style="width:52px">Photo</th>
            <th>Name</th>
            <th>Role</th>
            <th>Contacts</th>
            <th>Status</th>
            <th class="text-end" style="width:60px">Action</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($users as $user)
        <tr>
            <td>
                <img src="{{ $user->image }}"
                    class="rounded-circle border"
                    style="height:40px;width:40px;object-fit:cover;"
                    alt="{{ $user->name }}">
            </td>
            <td>
                <a href="{{ route('admin.users.show', $user->id) }}" class="fw-semibold text-body">
                    {{ $user->name }}
                </a>
                @if($user->gender)
                <div class="text-muted fs-xs">{{ ucfirst($user->gender->value) }}</div>
                @endif
            </td>
            <td>
                @foreach ($user->roles as $role)
                <span class="badge bg-primary-subtle text-primary border border-primary-subtle">{{ $role->name }}</span>
                @endforeach
            </td>
            <td>
                {{-- Email --}}
                @if ($user->email)
                <div class="fs-sm d-flex align-items-center gap-1">
                    <i class="ph-envelope text-muted"></i>
                    <span>{{ $user->email }}</span>

                    @if ($user->email_verified_at)
                    <i class="ph-check-circle text-success" title="Email Verified"></i>
                    @else
                    <i class="ph-x-circle text-warning" title="Email Not Verified"></i>
                    @endif
                </div>
                @endif

                {{-- Phone --}}
                @if ($user->phone)
                <div class="fs-sm d-flex align-items-center gap-1 mt-1">
                    <i class="ph-device-mobile text-muted"></i>
                    <span>{{ $user->phone }}</span>

                    @if ($user->phone_verified_at)
                    <i class="ph-check-circle text-success" title="Phone Verified"></i>
                    @else
                    <i class="ph-x-circle text-muted" title="Phone Not Verified"></i>
                    @endif
                </div>
                @endif
            </td>

            <td>
                <x-status-badge :active="$user->is_active" />
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
                    @can('User Password Reset')
                    <x-dropdown-link :url="route('admin.user.password.reset', $user->id)"
                        data-text="Reset this user's password?"
                        class="swal-confirm">
                        <i class="ph-key me-2"></i>Reset Password
                    </x-dropdown-link>
                    @endcan
                    @can('impersonate', $user)
                    <x-dropdown-link :url="route('admin.users.impersonate', $user->id)"
                        data-text="Sign in as {{ $user->name }}? You can return to your account from the banner at the top."
                        class="swal-post">
                        <i class="ph-user-switch me-2"></i>Impersonate
                    </x-dropdown-link>
                    @endcan
                    @if (!app()->environment('production'))
                    @can('Delete User')
                    <div class="dropdown-divider"></div>
                    <x-dropdown-link
                        :url="route('admin.users.show', $user->id) . '#manage-account'"
                        class="text-warning">
                        <i class="ph-gear me-2"></i>Manage Account
                    </x-dropdown-link>
                    @endcan
                    @endif
                </x-dropdown-menu>
            </td>
        </tr>
        @endforeach
    </tbody>
</x-table-view-pagination>
@endsection