@extends('settings::layouts.master')


@section('breadcrumb')
    <span class="breadcrumb-item active">Firebase</span>
@endsection

@section('content')
<div class="card">

    {{-- Header --}}
    <div class="card-header d-flex align-items-center justify-content-between py-2">
        <div class="d-flex align-items-center gap-2">
            <div class="bg-primary bg-opacity-10 text-primary rounded d-flex align-items-center justify-content-center flex-shrink-0" style="width:32px;height:32px;">
                <i class="ph-flame"></i>
            </div>
            <div>
                <div class="fw-bold">Firebase Settings</div>
                <div class="text-muted fs-xs">Push notifications, Cloud Messaging (FCM) &amp; Firebase services</div>
            </div>
        </div>
        <button type="button" id="testFirebaseBtn" class="btn btn-sm btn-outline-primary">
            <i class="ph-plug me-1"></i>Test Connection
        </button>
    </div>

    <div class="card-body p-4">

        {{-- Info tip --}}
        <x-alert type="primary" icon="ph-info" class="mb-4">
            <span class="fs-sm">
                Download the service-account JSON from <strong>Firebase Console → Project Settings → Service Accounts</strong>
                and paste it below. The Project ID is found under <strong>Project Settings → General</strong>.
            </span>
        </x-alert>

        <form action="{{ route('admin.settings.special.update_firebase') }}" method="POST">
            @csrf

            {{-- Credentials JSON --}}
            <div class="card mb-3">
                <div class="card-header py-2 d-flex align-items-center gap-2 bg-body-tertiary border-bottom">
                    <i class="ph-key text-primary"></i>
                    <span class="fw-bold text-uppercase fs-xs" style="letter-spacing:.05em;">Service Account Credentials</span>
                </div>
                <div class="card-body">
                    <x-form.textarea
                        name="firebase_credentials_json"
                        id="firebase_credentials_json"
                        label="Credentials JSON"
                        :value="optional($firebaseCredentialsJson)->value ?? ''"
                        class="font-monospace"
                        :rows="10"
                        placeholder='{"type": "service_account", "project_id": "...", ...}'
                        style="font-size:.78rem;resize:vertical;"
                    />
                    <div class="form-text">Full contents of the Firebase service account key file. Keep this secret and never expose it publicly.</div>
                </div>
            </div>

            {{-- Project IDs --}}
            <div class="card mb-3">
                <div class="card-header py-2 d-flex align-items-center gap-2 bg-body-tertiary border-bottom">
                    <i class="ph-identification-badge text-primary"></i>
                    <span class="fw-bold text-uppercase fs-xs" style="letter-spacing:.05em;">Project IDs</span>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-12">
                            <x-form.input name="firebase_project_id" id="firebase_project_id" label="Firebase Project ID" :value="optional($firebaseProjectId)->value ?? ''" placeholder="your-firebase-project-id" />
                            <div class="form-text">Found in Firebase Console → Project Settings → General</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="d-flex justify-content-end">
                <button type="submit" class="btn btn-primary px-4">
                    <i class="ph-floppy-disk me-1"></i>Save Firebase Settings
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
    btn.innerHTML = '<i class="ph-circle-notch ph-spin me-1"></i>Testing…';

    fetch('{{ route('admin.settings.special.test_firebase') }}', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' },
    })
    .then(r => r.json().then(d => ({ ok: r.ok, data: d })))
    .then(({ data }) => {
        data.success
            ? window.toast('success', 'Connection OK', data.message || 'An error occurred.')
            : window.showConfirm({ icon: 'error', title: 'Connection Failed', text: data.message || 'An error occurred.', confirmClass: 'btn btn-danger', showCancelButton: false, confirmText: 'OK' });
    })
    .catch(err => window.showConfirm({ icon: 'error', title: 'Error', text: err.message || 'Request failed.', confirmClass: 'btn btn-danger', showCancelButton: false, confirmText: 'OK' }))
    .finally(() => { btn.disabled = false; btn.innerHTML = originalHtml; });
});
</script>
@endpush
