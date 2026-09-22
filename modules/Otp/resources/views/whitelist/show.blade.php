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
                <a href="{{ route('admin.otp-whitelist.edit', $whitelist->id) }}" class="btn btn-sm btn-light">
                    <i class="ph-pencil-simple"></i>{{ __('foundation::foundation.common.edit') }}
                </a>
            @endcan
            @can('Delete OTP Whitelist')
                <a href="{{ route('admin.otp-whitelist.destroy', $whitelist->id) }}"
                   class="btn btn-sm btn-outline-danger swal-delete"
                   data-text="{{ __('otp::otp.whitelist_show.delete_confirm') }}">
                    <i class="ph-trash"></i>{{ __('foundation::foundation.common.delete') }}
                </a>
            @endcan
        </x-slot>
    </x-page-header>

    <div class="row g-3">
        <div class="col-md-6">
            <x-form-section title="{{ __('otp::otp.whitelist_show.section_details') }}" icon="ph-identification-card">
                <dl class="fd-dl">
                    <dt>{{ __('otp::otp.whitelist_index.col_id') }}</dt>
                    <dd>{{ $whitelist->id }}</dd>

                    <dt>{{ __('otp::otp.whitelist_show.recipient_type_label') }}</dt>
                    <dd>
                        <span class="badge {{ $whitelist->recipient_type->value === 'email' ? 'bg-info' : 'bg-warning' }}">
                            {{ $whitelist->recipient_type->label() }}
                        </span>
                    </dd>

                    <dt>{{ __('otp::otp.whitelist_show.recipient_label') }}</dt>
                    <dd>{{ $whitelist->recipient }}</dd>

                    <dt>{{ __('otp::otp.whitelist_show.fixed_otp_label') }}</dt>
                    <dd><code class="fs-sm">{{ $whitelist->fixed_otp }}</code></dd>

                    <dt>{{ __('foundation::foundation.common.status') }}</dt>
                    <dd><x-status-badge :active="$whitelist->is_active" /></dd>

                    <dt>{{ __('foundation::foundation.common.description') }}</dt>
                    <dd>{{ $whitelist->description ?: __('otp::otp.whitelist_show.no_description') }}</dd>
                </dl>
            </x-form-section>
        </div>
        <div class="col-md-6">
            <x-form-section title="{{ __('otp::otp.whitelist_show.section_timestamps') }}" icon="ph-clock">
                <dl class="fd-dl">
                    <dt>{{ __('foundation::foundation.common.created_at') }}</dt>
                    <dd>
                        {{ $whitelist->created_at->format('d M Y H:i') }}
                        <div class="text-muted fs-xs">{{ $whitelist->created_at->diffForHumans() }}</div>
                    </dd>

                    <dt>{{ __('otp::otp.whitelist_show.last_updated_label') }}</dt>
                    <dd>
                        {{ $whitelist->updated_at->format('d M Y H:i') }}
                        <div class="text-muted fs-xs">{{ $whitelist->updated_at->diffForHumans() }}</div>
                    </dd>
                </dl>
            </x-form-section>
        </div>
    </div>

@endsection
