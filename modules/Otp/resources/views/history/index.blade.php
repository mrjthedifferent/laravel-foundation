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
            <th>{{ __('otp::otp.history_index.col_id') }}</th>
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
                <span class="badge {{ $code->contact_type->value === 'email' ? 'bg-info' : 'bg-warning' }}">
                    {{ $code->contact_type->label() }}
                </span>
            </td>
            <td>{{ $code->contact }}</td>
            <td>
                @if(config('app.debug'))
                <code class="fs-sm">{{ $code->code }}</code>
                @else
                <code class="fs-sm">••••••</code>
                @endif
            </td>
            <td>
                @if($code->is_verified)
                <span class="fd-status is-success">{{ __('otp::otp.history_index.badge_verified') }}</span>
                @elseif($code->expires_at->isPast())
                <span class="fd-status">{{ __('otp::otp.history_index.badge_expired') }}</span>
                @else
                <span class="fd-status is-warning">{{ __('otp::otp.history_index.badge_pending') }}</span>
                @endif
            </td>
            <td class="text-muted">{{ $code->expires_at->format('Y-m-d H:i') }}</td>
            <td class="text-muted">{{ $code->created_at->format('Y-m-d H:i') }}</td>
        </tr>
        @endforeach
    </tbody>
</x-table-view-pagination>
@endsection