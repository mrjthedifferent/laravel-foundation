@extends('settings::layouts.master')

@section('breadcrumb')
    <span class="breadcrumb-item active">{{ __('settings::settings.special_social_auth.breadcrumb') }}</span>
@endsection

@section('content')
<div class="card">

    {{-- Header --}}
    <div class="card-header d-flex align-items-center gap-2 py-2">
        <div class="bg-primary bg-opacity-10 text-primary rounded d-flex align-items-center justify-content-center flex-shrink-0" style="width:32px;height:32px;">
            <i class="ph-users-three"></i>
        </div>
        <div>
            <div class="fw-bold">{{ __('settings::settings.special_social_auth.title') }}</div>
            <div class="text-muted fs-xs">{{ __('settings::settings.special_social_auth.subtitle') }}</div>
        </div>
    </div>

    <div class="card-body p-4">

        {{-- Info tip --}}
        <x-alert type="primary" icon="ph-info" class="mb-4">
            <span class="fs-sm">{!! __('settings::settings.special_social_auth.info_tip') !!}</span>
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
                        <i class="ph-plug me-1"></i>{{ __('settings::settings.special_social_auth.test') }}
                    </button>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <x-form.input name="google_client_id" label="{{ __('settings::settings.special_social_auth.client_id_label') }}" :value="optional($settings->get('google_client_id'))->value ?? ''" placeholder="*.apps.googleusercontent.com" />
                        </div>
                        <div class="col-md-4">
                            <x-form.label for="google_client_secret">{{ __('settings::settings.special_social_auth.client_secret_label') }}</x-form.label>
                            <div class="input-group input-group-sm">
                                <x-form.input type="password" name="google_client_secret" />
                                <button type="button" class="btn border-0 pw-toggle text-muted shadow-none position-absolute top-50 end-0 translate-middle-y toggle-password" data-target="google_client_secret" tabindex="-1"><i class="ph-eye"></i></button>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <x-form.input name="google_redirect_uri" label="{{ __('settings::settings.special_social_auth.redirect_uri_label') }}" :value="optional($settings->get('google_redirect_uri'))->value ?? '/auth/google/callback'" placeholder="/auth/google/callback" />
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
                        <i class="ph-plug me-1"></i>{{ __('settings::settings.special_social_auth.test') }}
                    </button>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <x-form.input name="github_client_id" label="{{ __('settings::settings.special_social_auth.client_id_label') }}" :value="optional($settings->get('github_client_id'))->value ?? ''" />
                        </div>
                        <div class="col-md-4">
                            <x-form.label for="github_client_secret">{{ __('settings::settings.special_social_auth.client_secret_label') }}</x-form.label>
                            <div class="input-group input-group-sm">
                                <x-form.input type="password" name="github_client_secret" />
                                <button type="button" class="btn border-0 pw-toggle text-muted shadow-none position-absolute top-50 end-0 translate-middle-y toggle-password" data-target="github_client_secret" tabindex="-1"><i class="ph-eye"></i></button>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <x-form.input name="github_redirect_uri" label="{{ __('settings::settings.special_social_auth.redirect_uri_label') }}" :value="optional($settings->get('github_redirect_uri'))->value ?? '/auth/github/callback'" placeholder="/auth/github/callback" />
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
                        <i class="ph-plug me-1"></i>{{ __('settings::settings.special_social_auth.test') }}
                    </button>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <x-form.input name="apple_client_id" label="{{ __('settings::settings.special_social_auth.client_id_label') }}" :value="optional($settings->get('apple_client_id'))->value ?? ''" />
                        </div>
                        <div class="col-md-4">
                            <x-form.label for="apple_client_secret">{{ __('settings::settings.special_social_auth.client_secret_label') }}</x-form.label>
                            <div class="input-group input-group-sm">
                                <x-form.input type="password" name="apple_client_secret" />
                                <button type="button" class="btn border-0 pw-toggle text-muted shadow-none position-absolute top-50 end-0 translate-middle-y toggle-password" data-target="apple_client_secret" tabindex="-1"><i class="ph-eye"></i></button>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <x-form.input name="apple_redirect_uri" label="{{ __('settings::settings.special_social_auth.redirect_uri_label') }}" :value="optional($settings->get('apple_redirect_uri'))->value ?? '/auth/apple/callback'" placeholder="/auth/apple/callback" />
                        </div>
                        <div class="col-md-4">
                            <x-form.input name="apple_team_id" label="{{ __('settings::settings.special_social_auth.team_id_label') }}" :value="optional($settings->get('apple_team_id'))->value ?? ''" />
                        </div>
                        <div class="col-md-4">
                            <x-form.input name="apple_key_id" label="{{ __('settings::settings.special_social_auth.key_id_label') }}" :value="optional($settings->get('apple_key_id'))->value ?? ''" />
                        </div>
                        <div class="col-md-4">
                            <x-form.input name="apple_key_file" label="{{ __('settings::settings.special_social_auth.key_file_label') }}" :value="optional($settings->get('apple_key_file'))->value ?? ''" placeholder="{{ __('settings::settings.special_social_auth.key_file_placeholder') }}" />
                        </div>
                    </div>
                </div>
            </div>

            <div class="d-flex justify-content-end">
                <button type="submit" class="btn btn-primary px-4">
                    <i class="ph-floppy-disk me-1"></i>{{ __('settings::settings.special_social_auth.submit') }}
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
        this.innerHTML = '<i class="ph-circle-notch ph-spin me-1"></i>{{ __('settings::settings.common.testing') }}';
        var self = this;

        fetch(routeMap[provider], {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' },
            body: JSON.stringify(body),
        })
        .then(r => r.json().then(d => ({ ok: r.ok, data: d })))
        .then(({ data }) => {
            data.success
                ? window.toast('success', labelMap[provider] + ' OK', data.message || '{{ __('settings::settings.common.error_occurred') }}')
                : window.showConfirm({ icon: 'error', title: labelMap[provider] + ' Failed', text: data.message || '{{ __('settings::settings.common.error_occurred') }}', confirmClass: 'btn btn-danger', showCancelButton: false, confirmText: '{{ __('settings::settings.common.ok') }}' });
        })
        .catch(err => window.showConfirm({ icon: 'error', title: '{{ __('settings::settings.common.error_title') }}', text: err.message || '{{ __('settings::settings.common.request_failed') }}', confirmClass: 'btn btn-danger', showCancelButton: false, confirmText: '{{ __('settings::settings.common.ok') }}' }))
        .finally(() => { self.disabled = false; self.innerHTML = originalHtml; });
    });
});
</script>
@endpush
