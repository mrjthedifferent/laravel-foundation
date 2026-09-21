@extends('settings::layouts.master')

@section('breadcrumb')
    <span class="breadcrumb-item active">Social Auth</span>
@endsection

@section('content')
<div class="card">

    {{-- Header --}}
    <div class="card-header d-flex align-items-center gap-2 py-2">
        <div class="bg-primary bg-opacity-10 text-primary rounded d-flex align-items-center justify-content-center flex-shrink-0" style="width:32px;height:32px;">
            <i class="ph-users-three"></i>
        </div>
        <div>
            <div class="fw-bold">Social Auth Settings</div>
            <div class="text-muted fs-xs">OAuth credentials for Google, GitHub &amp; Apple sign-in</div>
        </div>
    </div>

    <div class="card-body p-4">

        {{-- Info tip --}}
        <x-alert type="primary" icon="ph-info" class="mb-4">
            <span class="fs-sm">Configure OAuth credentials for each provider. Save your settings first, then use the <strong>Test</strong> button to verify the configuration works.</span>
        </x-alert>

        <form action="{{ route('admin.settings.special.update_social_auth') }}" method="POST">
            @csrf

            {{-- Google --}}
            <div class="card mb-3">
                <div class="card-header py-2 d-flex align-items-center justify-content-between bg-body-tertiary border-bottom">
                    <div class="d-flex align-items-center gap-2">
                        <i class="ph-google-logo text-primary"></i>
                        <span class="fw-bold text-uppercase fs-xs" style="letter-spacing:.05em;">Google</span>
                    </div>
                    <button type="button" class="btn btn-sm btn-outline-primary test-provider-btn" data-provider="google">
                        <i class="ph-plug me-1"></i>Test
                    </button>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-4">
                            {!! Form::label('google_client_id', 'Client ID', ['class' => 'form-label fw-semibold fs-sm']) !!}
                            {!! Form::text('google_client_id', optional($settings->get('google_client_id'))->value ?? '', ['id' => 'google_client_id', 'class' => 'form-control form-control-sm', 'placeholder' => '*.apps.googleusercontent.com']) !!}
                        </div>
                        <div class="col-md-4">
                            {!! Form::label('google_client_secret', 'Client Secret', ['class' => 'form-label fw-semibold fs-sm']) !!}
                            <div class="input-group input-group-sm">
                                {!! Form::password('google_client_secret', ['id' => 'google_client_secret', 'class' => 'form-control form-control-sm', 'value' => optional($settings->get('google_client_secret'))->value ?? '']) !!}
                                <button type="button" class="btn border-0 pw-toggle text-muted shadow-none position-absolute top-50 end-0 translate-middle-y toggle-password" data-target="google_client_secret" tabindex="-1"><i class="ph-eye"></i></button>
                            </div>
                        </div>
                        <div class="col-md-4">
                            {!! Form::label('google_redirect_uri', 'Redirect URI', ['class' => 'form-label fw-semibold fs-sm']) !!}
                            {!! Form::text('google_redirect_uri', optional($settings->get('google_redirect_uri'))->value ?? '/auth/google/callback', ['id' => 'google_redirect_uri', 'class' => 'form-control form-control-sm', 'placeholder' => '/auth/google/callback']) !!}
                        </div>
                    </div>
                </div>
            </div>

            {{-- GitHub --}}
            <div class="card mb-3">
                <div class="card-header py-2 d-flex align-items-center justify-content-between bg-body-tertiary border-bottom">
                    <div class="d-flex align-items-center gap-2">
                        <i class="ph-github-logo text-primary"></i>
                        <span class="fw-bold text-uppercase fs-xs" style="letter-spacing:.05em;">GitHub</span>
                    </div>
                    <button type="button" class="btn btn-sm btn-outline-primary test-provider-btn" data-provider="github">
                        <i class="ph-plug me-1"></i>Test
                    </button>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-4">
                            {!! Form::label('github_client_id', 'Client ID', ['class' => 'form-label fw-semibold fs-sm']) !!}
                            {!! Form::text('github_client_id', optional($settings->get('github_client_id'))->value ?? '', ['id' => 'github_client_id', 'class' => 'form-control form-control-sm']) !!}
                        </div>
                        <div class="col-md-4">
                            {!! Form::label('github_client_secret', 'Client Secret', ['class' => 'form-label fw-semibold fs-sm']) !!}
                            <div class="input-group input-group-sm">
                                {!! Form::password('github_client_secret', ['id' => 'github_client_secret', 'class' => 'form-control form-control-sm', 'value' => optional($settings->get('github_client_secret'))->value ?? '']) !!}
                                <button type="button" class="btn border-0 pw-toggle text-muted shadow-none position-absolute top-50 end-0 translate-middle-y toggle-password" data-target="github_client_secret" tabindex="-1"><i class="ph-eye"></i></button>
                            </div>
                        </div>
                        <div class="col-md-4">
                            {!! Form::label('github_redirect_uri', 'Redirect URI', ['class' => 'form-label fw-semibold fs-sm']) !!}
                            {!! Form::text('github_redirect_uri', optional($settings->get('github_redirect_uri'))->value ?? '/auth/github/callback', ['id' => 'github_redirect_uri', 'class' => 'form-control form-control-sm', 'placeholder' => '/auth/github/callback']) !!}
                        </div>
                    </div>
                </div>
            </div>

            {{-- Apple --}}
            <div class="card mb-3">
                <div class="card-header py-2 d-flex align-items-center justify-content-between bg-body-tertiary border-bottom">
                    <div class="d-flex align-items-center gap-2">
                        <i class="ph-apple-logo text-primary"></i>
                        <span class="fw-bold text-uppercase fs-xs" style="letter-spacing:.05em;">Apple</span>
                    </div>
                    <button type="button" class="btn btn-sm btn-outline-primary test-provider-btn" data-provider="apple">
                        <i class="ph-plug me-1"></i>Test
                    </button>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-4">
                            {!! Form::label('apple_client_id', 'Client ID', ['class' => 'form-label fw-semibold fs-sm']) !!}
                            {!! Form::text('apple_client_id', optional($settings->get('apple_client_id'))->value ?? '', ['id' => 'apple_client_id', 'class' => 'form-control form-control-sm']) !!}
                        </div>
                        <div class="col-md-4">
                            {!! Form::label('apple_client_secret', 'Client Secret', ['class' => 'form-label fw-semibold fs-sm']) !!}
                            <div class="input-group input-group-sm">
                                {!! Form::password('apple_client_secret', ['id' => 'apple_client_secret', 'class' => 'form-control form-control-sm', 'value' => optional($settings->get('apple_client_secret'))->value ?? '']) !!}
                                <button type="button" class="btn border-0 pw-toggle text-muted shadow-none position-absolute top-50 end-0 translate-middle-y toggle-password" data-target="apple_client_secret" tabindex="-1"><i class="ph-eye"></i></button>
                            </div>
                        </div>
                        <div class="col-md-4">
                            {!! Form::label('apple_redirect_uri', 'Redirect URI', ['class' => 'form-label fw-semibold fs-sm']) !!}
                            {!! Form::text('apple_redirect_uri', optional($settings->get('apple_redirect_uri'))->value ?? '/auth/apple/callback', ['id' => 'apple_redirect_uri', 'class' => 'form-control form-control-sm', 'placeholder' => '/auth/apple/callback']) !!}
                        </div>
                        <div class="col-md-4">
                            {!! Form::label('apple_team_id', 'Team ID', ['class' => 'form-label fw-semibold fs-sm']) !!}
                            {!! Form::text('apple_team_id', optional($settings->get('apple_team_id'))->value ?? '', ['id' => 'apple_team_id', 'class' => 'form-control form-control-sm']) !!}
                        </div>
                        <div class="col-md-4">
                            {!! Form::label('apple_key_id', 'Key ID', ['class' => 'form-label fw-semibold fs-sm']) !!}
                            {!! Form::text('apple_key_id', optional($settings->get('apple_key_id'))->value ?? '', ['id' => 'apple_key_id', 'class' => 'form-control form-control-sm']) !!}
                        </div>
                        <div class="col-md-4">
                            {!! Form::label('apple_key_file', 'Key File', ['class' => 'form-label fw-semibold fs-sm']) !!}
                            {!! Form::text('apple_key_file', optional($settings->get('apple_key_file'))->value ?? '', ['id' => 'apple_key_file', 'class' => 'form-control form-control-sm', 'placeholder' => 'path to .p8 file']) !!}
                        </div>
                    </div>
                </div>
            </div>

            <div class="d-flex justify-content-end">
                <button type="submit" class="btn btn-primary px-4">
                    <i class="ph-floppy-disk me-1"></i>Save Social Auth Settings
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.querySelectorAll('.toggle-password').forEach(function (btn) {
    btn.addEventListener('click', function () {
        var input = document.getElementById(this.dataset.target);
        var icon  = this.querySelector('i');
        input.type = input.type === 'password' ? 'text' : 'password';
        icon.className = input.type === 'password' ? 'ph-eye' : 'ph-eye-slash';
    });
});

var fieldMap = {
    google: ['google_client_id','google_client_secret','google_redirect_uri'],
    github: ['github_client_id','github_client_secret','github_redirect_uri'],
    apple:  ['apple_client_id','apple_client_secret','apple_redirect_uri','apple_team_id','apple_key_id','apple_key_file']
};
var routeMap = {
    google: '{{ route('admin.settings.special.test_google_auth') }}',
    github: '{{ route('admin.settings.special.test_github_auth') }}',
    apple:  '{{ route('admin.settings.special.test_apple_auth') }}'
};
var labelMap = { google: 'Google', github: 'GitHub', apple: 'Apple' };

document.querySelectorAll('.test-provider-btn').forEach(function (btn) {
    btn.addEventListener('click', function () {
        var provider = this.dataset.provider;
        var body = {};
        (fieldMap[provider] || []).forEach(function (name) {
            var el = document.getElementById(name);
            body[name] = el ? el.value : '';
        });
        var originalHtml = this.innerHTML;
        this.disabled = true;
        this.innerHTML = '<i class="ph-circle-notch ph-spin me-1"></i>Testing…';
        var self = this;

        fetch(routeMap[provider], {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' },
            body: JSON.stringify(body),
        })
        .then(r => r.json().then(d => ({ ok: r.ok, data: d })))
        .then(({ data }) => {
            data.success
                ? window.toast('success', labelMap[provider] + ' OK', data.message || 'An error occurred.')
                : window.showConfirm({ icon: 'error', title: labelMap[provider] + ' Failed', text: data.message || 'An error occurred.', confirmClass: 'btn btn-danger', showCancelButton: false, confirmText: 'OK' });
        })
        .catch(err => window.showConfirm({ icon: 'error', title: 'Error', text: err.message || 'Request failed.', confirmClass: 'btn btn-danger', showCancelButton: false, confirmText: 'OK' }))
        .finally(() => { self.disabled = false; self.innerHTML = originalHtml; });
    });
});
</script>
@endpush
