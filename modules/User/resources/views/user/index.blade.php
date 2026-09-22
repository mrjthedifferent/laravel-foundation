@extends('user::layouts.master')

@section('breadcrumb')
<span class="breadcrumb-item active">{{ __('user::user.index.breadcrumb') }}</span>
@endsection

@section('content')
<x-search-card>
    <div class="col-md-3 mb-2">
        <x-form.input name="search" label="{{ __('foundation::foundation.common.search') }}" :value="request('search')" placeholder="{{ __('user::user.index.search_placeholder') }}" />
    </div>
    <div class="col-md-3 mb-2">
        <x-form.select class="select" name="role_id[]" label="{{ __('user::user.index.role_label') }}" :options="$roles" :selected="request('role_id')" multiple data-placeholder="{{ __('user::user.index.all_roles') }}" />
    </div>
    <div class="col-md-3 mb-2">
        <x-form.select class="select" name="gender" label="{{ __('user::user.index.gender_label') }}" :options="['' => __('user::user.common.all'), 'male' => __('user::user.common.male'), 'female' => __('user::user.common.female'), 'other' => __('user::user.common.other')]" :selected="request('gender')" data-placeholder="{{ __('user::user.index.all_genders') }}" />
    </div>
    <div class="col-md-3 mb-2">
        <x-form.select class="select" name="is_active" label="{{ __('foundation::foundation.common.status') }}" :options="['' => __('user::user.common.all'), '1' => __('foundation::foundation.common.active'), '0' => __('foundation::foundation.common.inactive')]" :selected="request('is_active')" data-placeholder="{{ __('user::user.common.all') }}" />
    </div>
    <div class="col-md-3 mb-2">
        <x-form.select class="select" name="email_verified" label="{{ __('user::user.index.email_verified_label') }}" :options="['' => __('user::user.common.all'), '1' => __('user::user.common.verified'), '0' => __('user::user.index.unverified')]" :selected="request('email_verified')" data-placeholder="{{ __('user::user.common.all') }}" />
    </div>
    <div class="col-md-3 mb-2">
        <x-form.select class="select" name="phone_verified" label="{{ __('user::user.index.phone_verified_label') }}" :options="['' => __('user::user.common.all'), '1' => __('user::user.common.verified'), '0' => __('user::user.index.unverified')]" :selected="request('phone_verified')" data-placeholder="{{ __('user::user.common.all') }}" />
    </div>
    <div class="col-md-3 mb-2">
        <x-form.input name="date_from" label="{{ __('user::user.index.registered_from') }}" type="date" :value="request('date_from')" />
    </div>
    <div class="col-md-3 mb-2">
        <x-form.input name="date_to" label="{{ __('user::user.index.registered_to') }}" type="date" :value="request('date_to')" />
    </div>
</x-search-card>

<x-table-view-pagination
    title="{{ __('user::user.index.title') }}"
    :data="$users"
    empty-message="{{ __('user::user.index.empty') }}"
    empty-icon="ph-users">
    <x-slot name="actions">
        <x-table-actions>
            @can('Create User')
            <x-table-action :href="route('admin.users.create')" icon="ph-plus" title="{{ __('user::user.index.add_user') }}" />
            @endcan
            @can('Import User')
            <x-table-action :href="route('admin.users.bulk.create')" icon="ph-upload-simple" title="{{ __('user::user.index.import_users') }}" />
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
                data-text="{{ __('user::user.index.export_confirm', ['format' => 'CSV']) }}" />
            <x-table-export-item :href="route('admin.users.export').'?'.http_build_query(array_merge(request()->query(), ['format' => 'xlsx']))"
                class="swal-confirm"
                icon="ph-file-xls"
                title="Excel"
                data-text="{{ __('user::user.index.export_confirm', ['format' => 'Excel']) }}" />

            <x-table-export-item :href="route('admin.users.export').'?'.http_build_query(array_merge(request()->query(), ['format' => 'pdf']))"
                class="swal-confirm"
                icon="ph-file-pdf"
                title="PDF"
                data-text="{{ __('user::user.index.export_confirm', ['format' => 'PDF']) }}" />
        </x-table-export-dropdown>
        @endcan
    </x-slot>

    <thead>
        <tr>
            <th style="width:52px">{{ __('user::user.index.col_photo') }}</th>
            <th>{{ __('foundation::foundation.common.name') }}</th>
            <th>{{ __('user::user.index.role_label') }}</th>
            <th>{{ __('user::user.index.col_contacts') }}</th>
            <th>{{ __('foundation::foundation.common.status') }}</th>
            <th class="text-end" style="width:60px">{{ __('foundation::foundation.common.action') }}</th>
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
                    <i class="ph-check-circle text-success" title="{{ __('user::user.index.email_verified_label') }}"></i>
                    @else
                    <i class="ph-x-circle text-warning" title="{{ __('user::user.index.email_not_verified') }}"></i>
                    @endif
                </div>
                @endif

                {{-- Phone --}}
                @if ($user->phone)
                <div class="fs-sm d-flex align-items-center gap-1 mt-1">
                    <i class="ph-device-mobile text-muted"></i>
                    <span>{{ $user->phone }}</span>

                    @if ($user->phone_verified_at)
                    <i class="ph-check-circle text-success" title="{{ __('user::user.index.phone_verified_label') }}"></i>
                    @else
                    <i class="ph-x-circle text-muted" title="{{ __('user::user.index.phone_not_verified') }}"></i>
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
                        <i class="ph-eye me-2"></i>{{ __('foundation::foundation.common.view') }}
                    </x-dropdown-link>
                    @endcan
                    @can('Edit User')
                    <x-dropdown-link :url="route('admin.users.edit', $user->id)">
                        <i class="ph-pencil-simple me-2"></i>{{ __('foundation::foundation.common.edit') }}
                    </x-dropdown-link>
                    @endcan
                    @can('User Password Reset')
                    <x-dropdown-link :url="route('admin.user.password.reset', $user->id)"
                        data-text="{{ __('user::user.index.reset_password_confirm') }}"
                        class="swal-confirm">
                        <i class="ph-key me-2"></i>{{ __('user::user.index.reset_password') }}
                    </x-dropdown-link>
                    @endcan
                    @can('impersonate', $user)
                    <x-dropdown-link :url="route('admin.users.impersonate', $user->id)"
                        data-text="{{ __('user::user.index.impersonate_confirm', ['name' => $user->name]) }}"
                        class="swal-post">
                        <i class="ph-user-switch me-2"></i>{{ __('user::user.index.impersonate') }}
                    </x-dropdown-link>
                    @endcan
                    @if (!app()->environment('production'))
                    @can('Delete User')
                    <div class="dropdown-divider"></div>
                    <x-dropdown-link
                        :url="route('admin.users.show', $user->id) . '#manage-account'"
                        class="text-warning">
                        <i class="ph-gear me-2"></i>{{ __('user::user.index.manage_account') }}
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