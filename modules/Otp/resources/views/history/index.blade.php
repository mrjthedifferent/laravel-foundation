@extends('otp::layouts.master')

@section('breadcrumb')
<span class="breadcrumb-item active">Verification Code History</span>
@endsection

@section('content')
<x-search-card>
    <div class="col-md-3 mb-2">
        {!! Form::label('search', 'Search', ['class' => 'form-label fs-sm']) !!}
        {!! Form::text('search', request('search'), ['class' => 'form-control form-control-sm', 'placeholder' => 'Email / phone…']) !!}
    </div>
    <div class="col-md-3 mb-2">
        {!! Form::label('contact_type', 'Contact Type', ['class' => 'form-label fs-sm']) !!}
        {!! Form::select('contact_type', $contactTypes, request('contact_type'), ['class' => 'form-control form-control-sm select', 'data-placeholder' => 'All Types']) !!}
    </div>
    <div class="col-md-2 mb-2">
        {!! Form::label('date_from', 'Date From', ['class' => 'form-label fs-sm']) !!}
        {!! Form::date('date_from', request('date_from'), ['class' => 'form-control form-control-sm']) !!}
    </div>
    <div class="col-md-2 mb-2">
        {!! Form::label('date_to', 'Date To', ['class' => 'form-label fs-sm']) !!}
        {!! Form::date('date_to', request('date_to'), ['class' => 'form-control form-control-sm']) !!}
    </div>
    <div class="col-md-2 mb-2">
        {!! Form::label('is_verified', 'Status', ['class' => 'form-label fs-sm']) !!}
        {!! Form::select('is_verified', ['' => 'All', 1 => 'Verified', 0 => 'Not Verified'], request('is_verified'), ['class' => 'form-control form-control-sm select', 'data-placeholder' => 'All']) !!}
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