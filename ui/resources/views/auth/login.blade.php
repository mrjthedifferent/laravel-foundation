<x-guest-layout>
    <form method="POST" action="{{ route('login') }}">
        @csrf

        <h1 class="fd-auth-title">{{ __('foundation::foundation.auth.login_heading') }}</h1>
        <p class="fd-auth-lead">{{ __('foundation::foundation.auth.login_subheading') }}</p>

        <div class="mb-3">
            <label for="login" class="form-label">{{ __('foundation::foundation.auth.email_or_phone') }}</label>
            <input id="login" type="text" class="form-control form-control-lg @error('login') is-invalid @enderror"
                name="login" value="{{ old('login') }}" required autocomplete="username" autofocus>
            @error('login')
                <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
            @enderror
        </div>

        <div class="mb-3">
            <div class="d-flex justify-content-between align-items-center">
                <label for="password" class="form-label">{{ __('foundation::foundation.auth.password') }}</label>
                @if (Route::has('password.request'))
                    <a href="{{ route('password.request') }}" class="fs-sm">{{ __('foundation::foundation.auth.forgot_password_link') }}</a>
                @endif
            </div>
            <div class="position-relative">
                <input id="password" type="password" class="form-control form-control-lg pe-5 @error('password') is-invalid @enderror"
                    name="password" required autocomplete="current-password">
                <button type="button" class="btn btn-ghost btn-icon pw-toggle position-absolute top-50 end-0 translate-middle-y me-1"
                    data-target="password" tabindex="-1" aria-label="{{ __('foundation::foundation.auth.show_password') }}">
                    <i class="ph-eye"></i>
                </button>
                @error('password')
                    <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                @enderror
            </div>
        </div>

        <div class="form-check mb-4">
            <input class="form-check-input" type="checkbox" name="remember" id="remember" {{ old('remember') ? 'checked' : '' }}>
            <label class="form-check-label fs-sm" for="remember">{{ __('foundation::foundation.auth.remember_me') }}</label>
        </div>

        <button type="submit" class="btn btn-primary btn-lg w-100">{{ __('foundation::foundation.auth.login_button') }}</button>

        @php
            $socialProviders = collect([
                'google' => ['icon' => 'ph-google-logo', 'label' => 'Google'],
                'github' => ['icon' => 'ph-github-logo', 'label' => 'GitHub'],
                'apple' => ['icon' => 'ph-apple-logo', 'label' => 'Apple'],
            ])->filter(fn ($meta, $provider) => filled(config("services.$provider.client_id"))
                && filled(config("services.$provider.client_secret")));
        @endphp

        @if ((bool) config('settings.social_auth_enabled.value', false) && $socialProviders->isNotEmpty())
            <div class="fd-divider">{{ __('foundation::foundation.auth.or_login_with') }}</div>
            <div class="d-grid gap-2">
                @foreach ($socialProviders as $provider => $meta)
                    <a href="{{ route('social.redirect', $provider) }}" class="btn btn-light">
                        <i class="{{ $meta['icon'] }}"></i>{{ $meta['label'] }}
                    </a>
                @endforeach
            </div>
        @endif
    </form>

    @push('scripts')
    <script>
        $(document).ready(function() {
            // Toggle password visibility
            $(document).off('click', '.pw-toggle').on('click', '.pw-toggle', function() {
                var input = document.getElementById($(this).data('target'));
                var icon = $(this).find('i');
                if (input.type === 'password') {
                    input.type = 'text';
                    icon.removeClass('ph-eye').addClass('ph-eye-slash');
                } else {
                    input.type = 'password';
                    icon.removeClass('ph-eye-slash').addClass('ph-eye');
                }
            });
        });
    </script>
    @endpush
</x-guest-layout>
