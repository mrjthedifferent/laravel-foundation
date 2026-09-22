@extends('otp::layouts.master')

@section('breadcrumb')
<span class="breadcrumb-item active">Verification Code History</span>
@endsection

@section('content')
<x-search-card>
    <div class="col-md-3 mb-2">
        <x-form.input name="search" label="Search" :value="request('search')" placeholder="Email / phone…" />
    </div>
    <div class="col-md-3 mb-2">
        <x-form.select class="select" name="contact_type" label="Contact Type" :options="$contactTypes" :selected="request('contact_type')" data-placeholder="All Types" />
    </div>
    <div class="col-md-2 mb-2">
        <x-form.input name="date_from" label="Date From" type="date" :value="request('date_from')" />
    </div>
    <div class="col-md-2 mb-2">
        <x-form.input name="date_to" label="Date To" type="date" :value="request('date_to')" />
    </div>
    <div class="col-md-2 mb-2">
        <x-form.select class="select" name="is_verified" label="Status" :options="['' => 'All', 1 => 'Verified', 0 => 'Not Verified']" :selected="request('is_verified')" data-placeholder="All" />
    </div>
</x-search-card>

<x-table-view-pagination title="Verification Code History" :data="$codes" empty-icon="ph-lock-key" empty-message="No verification codes found">
    <thead>
        <tr>
            <th width="5%">ID</th>
            <th>Contact Type</th>
            <th>Contact</th>
            <th>Code</th>
            <th>Status</th>
            <th>Expires At</th>
            <th>Sent At</th>
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
                <span class="badge bg-success-subtle text-success border border-success-subtle">Verified</span>
                @elseif($code->expires_at->isPast())
                <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle">Expired</span>
                @else
                <span class="badge bg-warning-subtle text-warning border border-warning-subtle">Pending</span>
                @endif
            </td>
            <td>{{ $code->expires_at->format('Y-m-d H:i') }}</td>
            <td>{{ $code->created_at->format('Y-m-d H:i') }}</td>
        </tr>
        @endforeach
    </tbody>
</x-table-view-pagination>
@endsection