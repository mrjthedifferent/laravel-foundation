@extends('otp::layouts.master')

@section('breadcrumb')
    <span class="breadcrumb-item active">OTP Whitelist List</span>
@endsection

@section('content')
    <x-table-view-pagination title="OTP Whitelist List" :data="$whitelists" empty-message="No whitelist entries found" empty-icon="ph-list-checks">
        <x-slot name="actions">
            <x-table-actions>
                @can('Create OTP Whitelist')
                    <x-table-action :href="route('admin.otp-whitelist.create')" icon="ph-plus" title="Add New Entry" />
                @endcan
            </x-table-actions>
        </x-slot>

        <thead>
            <tr>
                <th width="5%">ID</th>
                <th>Type</th>
                <th>Recipient</th>
                <th>Fixed OTP</th>
                <th>Status</th>
                <th>Description</th>
                <th class="text-end">Actions</th>
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
                            <span class="badge bg-success-subtle text-success border border-success-subtle">Active</span>
                        @else
                            <span class="badge bg-danger-subtle text-danger border border-danger-subtle">Inactive</span>
                        @endif
                    </td>
                    <td>{{ $whitelist->description }}</td>
                    <td class="text-end">
                        <x-dropdown-menu>
                            @can('View OTP Whitelist')
                                <x-dropdown-link :url="route('admin.otp-whitelist.show', $whitelist->id)">
                                    <i class="ph-eye me-2"></i> View Entry
                                </x-dropdown-link>
                            @endcan

                            @can('Edit OTP Whitelist')
                                <x-dropdown-link :url="route('admin.otp-whitelist.edit', $whitelist->id)">
                                    <i class="ph-pencil-simple me-2"></i> Edit Entry
                                </x-dropdown-link>
                            @endcan

                            @can('Delete OTP Whitelist')
                                <button type="button" class="dropdown-item text-danger swal-delete"
                                    data-url="{{ route('admin.otp-whitelist.destroy', $whitelist->id) }}"
                                    data-text="Are you sure you want to delete this whitelist entry?">
                                    <i class="ph-trash me-2"></i> Delete Entry
                                </button>
                            @endcan
                        </x-dropdown-menu>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </x-table-view-pagination>
@endsection
