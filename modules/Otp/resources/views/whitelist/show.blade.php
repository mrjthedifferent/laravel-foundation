@extends('otp::layouts.master')

@section('breadcrumb')
    <a href="{{ route('admin.otp-whitelist.index') }}" class="breadcrumb-item">OTP Whitelist</a>
    <span class="breadcrumb-item active">Entry #{{ $whitelist->id }}</span>
@endsection

@section('content')

    <x-page-header
        title="Whitelist Entry #{{ $whitelist->id }}"
        icon="ph-lock-key"
        :back-url="route('admin.otp-whitelist.index')"
        back-label="Back to List">
        <x-slot name="actions">
            @can('Edit OTP Whitelist')
                <a href="{{ route('admin.otp-whitelist.edit', $whitelist->id) }}" class="btn btn-sm btn-outline-primary">
                    <i class="ph-pencil-simple me-1"></i>Edit
                </a>
            @endcan
            @can('Delete OTP Whitelist')
                <a href="{{ route('admin.otp-whitelist.destroy', $whitelist->id) }}"
                   class="btn btn-sm btn-outline-danger swal-delete"
                   data-text="Delete this whitelist entry?">
                    <i class="ph-trash me-1"></i>Delete
                </a>
            @endcan
        </x-slot>
    </x-page-header>

    <div class="row g-3">
        <div class="col-md-6">
            <x-form-section title="Entry Details" icon="ph-identification-card">
                <table class="table table-nowrap mb-0">
                    <tbody>
                        <tr>
                            <th class="text-muted fw-semibold fs-sm" width="140">ID</th>
                            <td>{{ $whitelist->id }}</td>
                        </tr>
                        <tr>
                            <th class="text-muted fw-semibold fs-sm">Recipient Type</th>
                            <td>
                                <span class="badge {{ $whitelist->recipient_type->value === 'email' ? 'bg-info-subtle text-info border border-info-subtle' : 'bg-warning-subtle text-warning border border-warning-subtle' }}">
                                    {{ $whitelist->recipient_type->label() }}
                                </span>
                            </td>
                        </tr>
                        <tr>
                            <th class="text-muted fw-semibold fs-sm">Recipient</th>
                            <td>{{ $whitelist->recipient }}</td>
                        </tr>
                        <tr>
                            <th class="text-muted fw-semibold fs-sm">Fixed OTP</th>
                            <td><code class="fs-sm">{{ $whitelist->fixed_otp }}</code></td>
                        </tr>
                        <tr>
                            <th class="text-muted fw-semibold fs-sm">Status</th>
                            <td>
                                <x-status-badge :active="$whitelist->is_active" />
                            </td>
                        </tr>
                        <tr>
                            <th class="text-muted fw-semibold fs-sm">Description</th>
                            <td class="fs-sm">{{ $whitelist->description ?: '—' }}</td>
                        </tr>
                    </tbody>
                </table>
            </x-form-section>
        </div>
        <div class="col-md-6">
            <x-form-section title="Timestamps" icon="ph-clock">
                <table class="table table-nowrap mb-0">
                    <tbody>
                        <tr>
                            <th class="text-muted fw-semibold fs-sm" width="140">Created At</th>
                            <td class="fs-sm">
                                {{ $whitelist->created_at->format('d M Y H:i') }}
                                <div class="text-muted fs-xs">{{ $whitelist->created_at->diffForHumans() }}</div>
                            </td>
                        </tr>
                        <tr>
                            <th class="text-muted fw-semibold fs-sm">Last Updated</th>
                            <td class="fs-sm">
                                {{ $whitelist->updated_at->format('d M Y H:i') }}
                                <div class="text-muted fs-xs">{{ $whitelist->updated_at->diffForHumans() }}</div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </x-form-section>
        </div>
    </div>

@endsection
