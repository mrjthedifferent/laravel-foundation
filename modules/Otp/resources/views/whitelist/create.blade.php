@extends('otp::layouts.master')

@section('breadcrumb')
    <a href="{{ route('admin.otp-whitelist.index') }}" class="breadcrumb-item">{{ __('otp::otp.whitelist.label') }}</a>
    <span class="breadcrumb-item active">{{ __('otp::otp.whitelist_create.breadcrumb') }}</span>
@endsection

@section('content')
<form action="{{ route('admin.otp-whitelist.store') }}" method="POST">
    @csrf

    <x-page-header
        title="{{ __('otp::otp.whitelist_create.title') }}"
        subtitle="{{ __('otp::otp.whitelist_create.subtitle') }}"
        icon="ph-list-plus"
        :back-url="route('admin.otp-whitelist.index')"
        back-label="{{ __('otp::otp.whitelist_create.back_label') }}" />

    <x-form-section title="{{ __('otp::otp.whitelist_create.section_title') }}" icon="ph-lock-key">
        @php $otpDigits = (int) config('settings.otp_digit_length.value', 6); @endphp
        <div class="row g-3">
            <div class="col-md-6">
                <x-form.select class="select" name="recipient_type" label="{{ __('otp::otp.whitelist_create.recipient_type_label') }}" required :options="['email' => __('otp::otp.whitelist_create.option_email'), 'phone' => __('otp::otp.whitelist_create.option_phone')]" :selected="null" data-placeholder="{{ __('otp::otp.whitelist_create.select_type_placeholder') }}" />
            </div>
            <div class="col-md-6">
                <x-form.input name="recipient" label="{{ __('otp::otp.whitelist_create.recipient_label') }}" required placeholder="{{ __('otp::otp.whitelist_create.recipient_placeholder') }}" />
            </div>
            <div class="col-md-6">
                <x-form.input name="fixed_otp" id="fixed_otp" label="{{ __('otp::otp.whitelist_create.fixed_otp_label') }}" required placeholder="{{ __('otp::otp.whitelist_create.fixed_otp_placeholder', ['digits' => $otpDigits]) }}" maxlength="{{ $otpDigits }}" data-otp-digits="{{ $otpDigits }}" />
                <div class="form-text">{{ __('otp::otp.whitelist_create.fixed_otp_help', ['digits' => $otpDigits]) }}</div>
            </div>
            <div class="col-md-6">
                <x-form.select class="select" name="is_active" label="{{ __('foundation::foundation.common.status') }}" required :options="integerStatus()" selected="1" data-placeholder="{{ __('otp::otp.whitelist_create.select_status_placeholder') }}" />
            </div>
            <div class="col-md-12">
                <x-form.textarea name="description" label="{{ __('foundation::foundation.common.description') }}" placeholder="{{ __('otp::otp.whitelist_create.description_placeholder') }}" :rows="2" />
            </div>
        </div>
    </x-form-section>

    <div class="d-flex justify-content-between align-items-center">
        <a href="{{ route('admin.otp-whitelist.index') }}" class="btn btn-outline-secondary">
            <i class="ph-x me-1"></i>{{ __('foundation::foundation.common.cancel') }}
        </a>
        <x-primary-button id="submit-button" class="px-5">
            <i class="ph-floppy-disk me-1"></i>{{ __('otp::otp.whitelist_create.save_button') }}
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
