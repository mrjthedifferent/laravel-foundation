@extends('otp::layouts.master')

@section('breadcrumb')
    <a href="{{ route('admin.otp-whitelist.index') }}" class="breadcrumb-item">OTP Whitelist</a>
    <span class="breadcrumb-item active">Edit Entry #{{ $whitelist->id }}</span>
@endsection

@section('content')
<form action="{{ route('admin.otp-whitelist.update', $whitelist->id) }}" method="POST">
    @csrf
    @method('PUT')

    <x-page-header
        title="Edit Whitelist Entry #{{ $whitelist->id }}"
        subtitle="Update the fixed OTP or status for this recipient"
        icon="ph-pencil-simple"
        :back-url="route('admin.otp-whitelist.index')"
        back-label="Back to List" />

    <x-form-section title="Entry Details" icon="ph-lock-key">
        @php $otpDigits = (int) config('settings.otp_digit_length.value', 6); @endphp
        <div class="row g-3">
            <div class="col-md-6">
                <x-form.select class="select" name="recipient_type" label="Recipient Type" required :options="['email' => 'Email', 'phone' => 'Phone']" :selected="$whitelist->recipient_type" data-placeholder="Select Type" />
            </div>
            <div class="col-md-6">
                <x-form.input name="recipient" label="Recipient" required :value="$whitelist->recipient" placeholder="Email address or phone number" />
            </div>
            <div class="col-md-6">
                <x-form.input name="fixed_otp" id="fixed_otp" label="Fixed OTP" required :value="$whitelist->fixed_otp" placeholder="{{ $otpDigits }}-digit OTP" maxlength="{{ $otpDigits }}" data-otp-digits="{{ $otpDigits }}" />
                <div class="form-text">Must be exactly {{ $otpDigits }} digits (0–9)</div>
            </div>
            <div class="col-md-6">
                <x-form.select class="select" name="is_active" label="Status" required :options="integerStatus()" :selected="(int) $whitelist->is_active" data-placeholder="Select Status" />
            </div>
            <div class="col-md-12">
                <x-form.textarea name="description" label="Description" :value="$whitelist->description" placeholder="Optional note…" :rows="2" />
            </div>
        </div>
    </x-form-section>

    <div class="d-flex justify-content-between align-items-center">
        <a href="{{ route('admin.otp-whitelist.index') }}" class="btn btn-outline-secondary">
            <i class="ph-x me-1"></i>Cancel
        </a>
        <x-primary-button id="submit-button" class="px-5">
            <i class="ph-floppy-disk me-1"></i>Update Entry
        </x-primary-button>
    </div>

</form>
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
