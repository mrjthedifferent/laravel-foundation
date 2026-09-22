@extends('otp::layouts.master')

@section('breadcrumb')
<span class="breadcrumb-item active">{{ __('otp::otp.history_index.breadcrumb') }}</span>
@endsection

@section('content')
<x-search-card>
    <div class="col-md-3 mb-2">
        <x-form.input name="search" label="{{ __('foundation::foundation.common.search') }}" :value="request('search')" placeholder="{{ __('otp::otp.history_index.search_placeholder') }}" />
    </div>
    <div class="col-md-3 mb-2">
        <x-form.select class="select" name="contact_type" label="{{ __('otp::otp.history_index.contact_type_label') }}" :options="$contactTypes" :selected="request('contact_type')" data-placeholder="{{ __('otp::otp.history_index.all_types_placeholder') }}" />
    </div>
    <div class="col-md-2 mb-2">
        <x-form.input name="date_from" label="{{ __('otp::otp.history_index.date_from_label') }}" type="date" :value="request('date_from')" />
    </div>
    <div class="col-md-2 mb-2">
        <x-form.input name="date_to" label="{{ __('otp::otp.history_index.date_to_label') }}" type="date" :value="request('date_to')" />
    </div>
    <div class="col-md-2 mb-2">
        <x-form.select class="select" name="is_verified" label="{{ __('foundation::foundation.common.status') }}" :options="['' => __('otp::otp.history_index.status_all'), 1 => __('otp::otp.history_index.status_verified'), 0 => __('otp::otp.history_index.status_not_verified')]" :selected="request('is_verified')" data-placeholder="{{ __('otp::otp.history_index.status_all') }}" />
    </div>
</x-search-card>

<x-table-view-pagination title="{{ __('otp::otp.history_index.breadcrumb') }}" :data="$codes" empty-icon="ph-lock-key" empty-message="{{ __('otp::otp.history_index.empty') }}">
    <thead>
        <tr>
            <th width="5%">{{ __('otp::otp.history_index.col_id') }}</th>
            <th>{{ __('otp::otp.history_index.col_contact_type') }}</th>
            <th>{{ __('otp::otp.history_index.col_contact') }}</th>
            <th>{{ __('otp::otp.history_index.col_code') }}</th>
            <th>{{ __('foundation::foundation.common.status') }}</th>
            <th>{{ __('otp::otp.history_index.col_expires_at') }}</th>
            <th>{{ __('otp::otp.history_index.col_sent_at') }}</th>
        </tr>
    </thead>
    <tbody>
        @foreach($codes as $code)
        <tr>
            <td>{{ $code->id }}</td>
            <td>
                <span class="badge {{ $code->contact_type->value === 'email' ? 'bg-info-subtle text-info border border-info-subtle' : 'bg-warning-subtle text-warning border border-warning-subtle' }}">
                    {{ $code->contact_type->label() }}
                </span>
            </td>
            <td>{{ $code->contact }}</td>
            <td>
                @if(config('app.debug'))
                <code>{{ $code->code }}</code>
                @else
                <code>••••••</code>
                @endif
            </td>
            <td>
                @if($code->is_verified)
                <span class="badge bg-success-subtle text-success border border-success-subtle">{{ __('otp::otp.history_index.badge_verified') }}</span>
                @elseif($code->expires_at->isPast())
                <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle">{{ __('otp::otp.history_index.badge_expired') }}</span>
                @else
                <span class="badge bg-warning-subtle text-warning border border-warning-subtle">{{ __('otp::otp.history_index.badge_pending') }}</span>
                @endif
            </td>
            <td>{{ $code->expires_at->format('Y-m-d H:i') }}</td>
            <td>{{ $code->created_at->format('Y-m-d H:i') }}</td>
        </tr>
        @endforeach
    </tbody>
</x-table-view-pagination>
@endsection