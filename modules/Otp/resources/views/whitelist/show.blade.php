@extends('otp::layouts.master')

@section('breadcrumb')
    <a href="{{ route('admin.otp-whitelist.index') }}" class="breadcrumb-item">{{ __('otp::otp.whitelist.label') }}</a>
    <span class="breadcrumb-item active">{{ __('otp::otp.whitelist_show.breadcrumb', ['id' => $whitelist->id]) }}</span>
@endsection

@section('content')

    <x-page-header
        title="{{ __('otp::otp.whitelist_show.title', ['id' => $whitelist->id]) }}"
        icon="ph-lock-key"
        :back-url="route('admin.otp-whitelist.index')"
        back-label="{{ __('otp::otp.whitelist_show.back_label') }}">
        <x-slot name="actions">
            @can('Edit OTP Whitelist')
                <a href="{{ route('admin.otp-whitelist.edit', $whitelist->id) }}" class="btn btn-sm btn-outline-primary">
                    <i class="ph-pencil-simple me-1"></i>{{ __('foundation::foundation.common.edit') }}
                </a>
            @endcan
            @can('Delete OTP Whitelist')
                <a href="{{ route('admin.otp-whitelist.destroy', $whitelist->id) }}"
                   class="btn btn-sm btn-outline-danger swal-delete"
                   data-text="{{ __('otp::otp.whitelist_show.delete_confirm') }}">
                    <i class="ph-trash me-1"></i>{{ __('foundation::foundation.common.delete') }}
                </a>
            @endcan
        </x-slot>
    </x-page-header>

    <div class="row g-3">
        <div class="col-md-6">
            <x-form-section title="{{ __('otp::otp.whitelist_show.section_details') }}" icon="ph-identification-card">
                <table class="table table-nowrap mb-0">
                    <tbody>
                        <tr>
                            <th class="text-muted fw-semibold fs-sm" width="140">{{ __('otp::otp.whitelist_index.col_id') }}</th>
                            <td>{{ $whitelist->id }}</td>
                        </tr>
                        <tr>
                            <th class="text-muted fw-semibold fs-sm">{{ __('otp::otp.whitelist_show.recipient_type_label') }}</th>
                            <td>
                                <span class="badge {{ $whitelist->recipient_type->value === 'email' ? 'bg-info-subtle text-info border border-info-subtle' : 'bg-warning-subtle text-warning border border-warning-subtle' }}">
                                    {{ $whitelist->recipient_type->label() }}
                                </span>
                            </td>
                        </tr>
                        <tr>
                            <th class="text-muted fw-semibold fs-sm">{{ __('otp::otp.whitelist_show.recipient_label') }}</th>
                            <td>{{ $whitelist->recipient }}</td>
                        </tr>
                        <tr>
                            <th class="text-muted fw-semibold fs-sm">{{ __('otp::otp.whitelist_show.fixed_otp_label') }}</th>
                            <td><code class="fs-sm">{{ $whitelist->fixed_otp }}</code></td>
                        </tr>
                        <tr>
                            <th class="text-muted fw-semibold fs-sm">{{ __('foundation::foundation.common.status') }}</th>
                            <td>
                                <x-status-badge :active="$whitelist->is_active" />
                            </td>
                        </tr>
                        <tr>
                            <th class="text-muted fw-semibold fs-sm">{{ __('foundation::foundation.common.description') }}</th>
                            <td class="fs-sm">{{ $whitelist->description ?: __('otp::otp.whitelist_show.no_description') }}</td>
                        </tr>
                    </tbody>
                </table>
            </x-form-section>
        </div>
        <div class="col-md-6">
            <x-form-section title="{{ __('otp::otp.whitelist_show.section_timestamps') }}" icon="ph-clock">
                <table class="table table-nowrap mb-0">
                    <tbody>
                        <tr>
                            <th class="text-muted fw-semibold fs-sm" width="140">{{ __('foundation::foundation.common.created_at') }}</th>
                            <td class="fs-sm">
                                {{ $whitelist->created_at->format('d M Y H:i') }}
                                <div class="text-muted fs-xs">{{ $whitelist->created_at->diffForHumans() }}</div>
                            </td>
                        </tr>
                        <tr>
                            <th class="text-muted fw-semibold fs-sm">{{ __('otp::otp.whitelist_show.last_updated_label') }}</th>
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
