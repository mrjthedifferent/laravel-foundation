@extends('settings::layouts.master')


@section('breadcrumb')
    <span class="breadcrumb-item active">{{ __('settings::settings.special_firebase.breadcrumb') }}</span>
@endsection

@section('content')
<div class="card">

    {{-- Header --}}
    <div class="card-header">
        <span class="fd-icon-tile"><i class="ph-flame"></i></span>
        <div>
            <div class="card-title">{{ __('settings::settings.special_firebase.title') }}</div>
            <div class="text-muted fs-xs">{{ __('settings::settings.special_firebase.subtitle') }}</div>
        </div>
        <button type="button" id="testFirebaseBtn" class="btn btn-sm btn-light ms-auto">
            <i class="ph-plug"></i>{{ __('settings::settings.special_firebase.test_connection') }}
        </button>
    </div>

    <div class="card-body p-4">

        {{-- Info tip --}}
        <x-alert type="primary" icon="ph-info" class="mb-4">
            <span class="fs-sm">
                {!! __('settings::settings.special_firebase.info_tip') !!}
            </span>
        </x-alert>

        <form action="{{ route('admin.settings.special.update_firebase') }}" method="POST">
            @csrf

            {{-- Credentials JSON --}}
            <div class="card mb-3">
                <div class="card-header">
                    <span class="fd-icon-tile fd-icon-tile-sm"><i class="ph-key"></i></span>
                    <span class="fd-overline">{{ __('settings::settings.special_firebase.credentials_header') }}</span>
                </div>
                <div class="card-body">
                    <x-form.textarea
                        name="firebase_credentials_json"
                        id="firebase_credentials_json"
                        label="{{ __('settings::settings.special_firebase.credentials_label') }}"
                        :value="optional($firebaseCredentialsJson)->value ?? ''"
                        class="font-monospace fs-xs"
                        :rows="10"
                        placeholder='{"type": "service_account", "project_id": "...", ...}'
                    />
                    <div class="form-text">{{ __('settings::settings.special_firebase.credentials_help') }}</div>
                </div>
            </div>

            {{-- Project IDs --}}
            <div class="card mb-3">
                <div class="card-header">
                    <span class="fd-icon-tile fd-icon-tile-sm"><i class="ph-identification-badge"></i></span>
                    <span class="fd-overline">{{ __('settings::settings.special_firebase.project_ids_header') }}</span>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-12">
                            <x-form.input name="firebase_project_id" id="firebase_project_id" label="{{ __('settings::settings.special_firebase.project_id_label') }}" :value="optional($firebaseProjectId)->value ?? ''" placeholder="{{ __('settings::settings.special_firebase.project_id_placeholder') }}" />
                            <div class="form-text">{{ __('settings::settings.special_firebase.project_id_help') }}</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="d-flex justify-content-end">
                <button type="submit" class="btn btn-primary px-4">
                    <i class="ph-floppy-disk"></i>{{ __('settings::settings.special_firebase.submit') }}
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.getElementById('testFirebaseBtn').addEventListener('click', function () {
    const btn = this;
    const originalHtml = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<i class="ph-circle-notch ph-spin"></i>{{ __('settings::settings.common.testing') }}';

    fetch('{{ route('admin.settings.special.test_firebase') }}', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' },
    })
    .then(r => r.json().then(d => ({ ok: r.ok, data: d })))
    .then(({ data }) => {
        data.success
            ? window.toast('success', '{{ __('settings::settings.special_firebase.connection_ok') }}', data.message || '{{ __('settings::settings.common.error_occurred') }}')
            : window.showConfirm({ icon: 'error', title: '{{ __('settings::settings.special_firebase.connection_failed') }}', text: data.message || '{{ __('settings::settings.common.error_occurred') }}', confirmClass: 'btn btn-danger', showCancelButton: false, confirmText: '{{ __('settings::settings.common.ok') }}' });
    })
    .catch(err => window.showConfirm({ icon: 'error', title: '{{ __('settings::settings.common.error_title') }}', text: err.message || '{{ __('settings::settings.common.request_failed') }}', confirmClass: 'btn btn-danger', showCancelButton: false, confirmText: '{{ __('settings::settings.common.ok') }}' }))
    .finally(() => { btn.disabled = false; btn.innerHTML = originalHtml; });
});
</script>
@endpush
