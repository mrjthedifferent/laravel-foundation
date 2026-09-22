@extends('otp::layouts.master')

@section('breadcrumb')
    <span class="breadcrumb-item active">{{ __('otp::otp.whitelist_index.breadcrumb') }}</span>
@endsection

@section('content')
    <x-table-view-pagination title="{{ __('otp::otp.whitelist_index.breadcrumb') }}" :data="$whitelists" empty-message="{{ __('otp::otp.whitelist_index.empty') }}" empty-icon="ph-list-checks">
        <x-slot name="actions">
            <x-table-actions>
                @can('Create OTP Whitelist')
                    <x-table-action :href="route('admin.otp-whitelist.create')" icon="ph-plus" title="{{ __('otp::otp.whitelist_index.add_new') }}" />
                @endcan
            </x-table-actions>
        </x-slot>

        <thead>
            <tr>
                <th width="5%">{{ __('otp::otp.whitelist_index.col_id') }}</th>
                <th>{{ __('otp::otp.whitelist_index.col_type') }}</th>
                <th>{{ __('otp::otp.whitelist_index.col_recipient') }}</th>
                <th>{{ __('otp::otp.whitelist_index.col_fixed_otp') }}</th>
                <th>{{ __('foundation::foundation.common.status') }}</th>
                <th>{{ __('foundation::foundation.common.description') }}</th>
                <th class="text-end">{{ __('foundation::foundation.common.actions') }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach($whitelists as $whitelist)
                <tr>
                    <td>{{ $whitelist->id }}</td>
                    <td>{{ $whitelist->recipient_type->label() }}</td>
                    <td>{{ $whitelist->recipient }}</td>
                    <td>{{ $whitelist->fixed_otp }}</td>
                    <td>
                        @if ($whitelist->is_active)
                            <span class="badge bg-success-subtle text-success border border-success-subtle">{{ __('foundation::foundation.common.active') }}</span>
                        @else
                            <span class="badge bg-danger-subtle text-danger border border-danger-subtle">{{ __('foundation::foundation.common.inactive') }}</span>
                        @endif
                    </td>
                    <td>{{ $whitelist->description }}</td>
                    <td class="text-end">
                        <x-dropdown-menu>
                            @can('View OTP Whitelist')
                                <x-dropdown-link :url="route('admin.otp-whitelist.show', $whitelist->id)">
                                    <i class="ph-eye me-2"></i> {{ __('otp::otp.whitelist_index.view_entry') }}
                                </x-dropdown-link>
                            @endcan

                            @can('Edit OTP Whitelist')
                                <x-dropdown-link :url="route('admin.otp-whitelist.edit', $whitelist->id)">
                                    <i class="ph-pencil-simple me-2"></i> {{ __('otp::otp.whitelist_index.edit_entry') }}
                                </x-dropdown-link>
                            @endcan

                            @can('Delete OTP Whitelist')
                                <button type="button" class="dropdown-item text-danger swal-delete"
                                    data-url="{{ route('admin.otp-whitelist.destroy', $whitelist->id) }}"
                                    data-text="{{ __('otp::otp.whitelist_index.delete_confirm') }}">
                                    <i class="ph-trash me-2"></i> {{ __('otp::otp.whitelist_index.delete_entry') }}
                                </button>
                            @endcan
                        </x-dropdown-menu>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </x-table-view-pagination>
@endsection
