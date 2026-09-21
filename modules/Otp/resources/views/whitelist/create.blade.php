@extends('otp::layouts.master')

@section('breadcrumb')
    <a href="{{ route('admin.otp-whitelist.index') }}" class="breadcrumb-item">OTP Whitelist</a>
    <span class="breadcrumb-item active">Create Entry</span>
@endsection

@section('content')
{{ Form::open(['route' => 'admin.otp-whitelist.store', 'method' => 'post']) }}

    <x-page-header
        title="New Whitelist Entry"
        subtitle="Assign a fixed OTP to a specific recipient"
        icon="ph-list-plus"
        :back-url="route('admin.otp-whitelist.index')"
        back-label="Back to List" />

    <x-form-section title="Entry Details" icon="ph-lock-key">
        @php $otpDigits = (int) config('settings.otp_digit_length.value', 6); @endphp
        <div class="row g-3">
            <div class="col-md-6">
                {!! Form::label('recipient_type', 'Recipient Type <span class="text-danger">*</span>', ['class' => 'form-label fw-semibold fs-sm'], false) !!}
                {!! Form::select('recipient_type', ['email' => 'Email', 'phone' => 'Phone'], null, ['class' => 'form-control form-control-sm select', 'data-placeholder' => 'Select Type', 'required']) !!}
            </div>
            <div class="col-md-6">
                {!! Form::label('recipient', 'Recipient <span class="text-danger">*</span>', ['class' => 'form-label fw-semibold fs-sm'], false) !!}
                {!! Form::text('recipient', null, ['class' => 'form-control form-control-sm', 'placeholder' => 'Email address or phone number', 'required']) !!}
            </div>
            <div class="col-md-6">
                {!! Form::label('fixed_otp', 'Fixed OTP <span class="text-danger">*</span>', ['class' => 'form-label fw-semibold fs-sm'], false) !!}
                {!! Form::text('fixed_otp', null, ['class' => 'form-control form-control-sm', 'placeholder' => $otpDigits.'-digit OTP', 'maxlength' => (string)$otpDigits, 'required', 'id' => 'fixed_otp', 'data-otp-digits' => $otpDigits]) !!}
                <div class="form-text">Must be exactly {{ $otpDigits }} digits (0–9)</div>
            </div>
            <div class="col-md-6">
                {!! Form::label('is_active', 'Status <span class="text-danger">*</span>', ['class' => 'form-label fw-semibold fs-sm'], false) !!}
                {!! Form::select('is_active', integerStatus(), '1', ['class' => 'form-control form-control-sm select', 'data-placeholder' => 'Select Status', 'required']) !!}
            </div>
            <div class="col-md-12">
                {!! Form::label('description', 'Description', ['class' => 'form-label fw-semibold fs-sm']) !!}
                {!! Form::textarea('description', null, ['class' => 'form-control form-control-sm', 'placeholder' => 'Optional note…', 'rows' => 2]) !!}
            </div>
        </div>
    </x-form-section>

    <div class="d-flex justify-content-between align-items-center">
        <a href="{{ route('admin.otp-whitelist.index') }}" class="btn btn-outline-secondary">
            <i class="ph-x me-1"></i>Cancel
        </a>
        <x-primary-button id="submit-button" class="px-5">
            <i class="ph-floppy-disk me-1"></i>Save Entry
        </x-primary-button>
    </div>

{!! Form::close() !!}
@endsection

@push('scripts')
    <script>
        $(function() {
            $('#fixed_otp').on('input', function() {
                this.value = this.value.replace(/[^0-9]/g, '').substring(0, parseInt(this.dataset.otpDigits || 6));
            });
        });
    </script>
@endpush
