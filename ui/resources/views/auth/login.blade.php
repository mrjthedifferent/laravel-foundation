<x-guest-layout>
    <!-- Content area -->
    <div class="content d-flex justify-content-center align-items-center">

        <!-- Login form -->
        <form class="login-form" method="POST" action="{{ route('login') }}">
            @csrf
            <div class="card mb-0">
                <div class="card-body">
                    <div class="text-center mb-3">
                        <div class="d-inline-flex align-items-center justify-content-center mb-4 mt-2">
                            @if (mailLogoUrl())
                            <img src="{{ mailLogoUrl() }}" class="h-48px" alt="{{ mailAppName() }}">
                            @else
                            <span class="fs-4 fw-semibold">{{ mailAppName() }}</span>
                            @endif
                        </div>
                        <h5 class="mb-0">{{ __('foundation::foundation.auth.login_heading') }}</h5>
                        <span class="d-block text-muted">{{ __('foundation::foundation.auth.login_subheading') }}</span>
                    </div>

                    <div class="mb-3">
                        <label for="login" class="form-label">{{ __('foundation::foundation.auth.email_or_phone') }}</label>
                        <div class="form-control-feedback form-control-feedback-start">
                            <input id="login" type="text" class="form-control @error('login') is-invalid @enderror" name="login" value="{{ old('login') }}" required autocomplete="username" autofocus placeholder="email@example.com or +880123456789">
                            <div class="form-control-feedback-icon">
                                <i class="ph-user-circle text-muted"></i>
                            </div>
                            @error('login')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                            @enderror
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="password" class="form-label">{{ __('foundation::foundation.auth.password') }}</label>
                        <div class="form-control-feedback form-control-feedback-start position-relative">
                            <input id="password" type="password" class="form-control pe-5 @error('password') is-invalid @enderror" name="password" required autocomplete="current-password" placeholder="•••••••••••">
                            <div class="form-control-feedback-icon">
                                <i class="ph-lock text-muted"></i>
                            </div>
                            <button type="button" class="btn border-0 pw-toggle text-muted shadow-none position-absolute top-50 end-0 translate-middle-y me-2" data-target="password" tabindex="-1">
                                <i class="ph-eye"></i>
                            </button>
                            @error('password')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                            @enderror
                        </div>
                    </div>

                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="remember" id="remember" {{ old('remember') ? 'checked' : '' }}>

                            <label class="form-check-label" for="remember">
                                {{ __('foundation::foundation.auth.remember_me') }}
                            </label>
                        </div>
                    </div>

                    <div class="mb-3">
                        <button type="submit" class="btn btn-primary w-100"> {{ __('foundation::foundation.auth.login_button') }}</button>
                    </div>

                    <div class="text-center">
                        @if (Route::has('password.request'))
                        <a href="{{ route('password.request') }}">
                            {{ __('foundation::foundation.auth.forgot_password_link') }}
                        </a>
                        @endif
                    </div>

                    @php
                        $socialProviders = collect([
                            'google' => ['icon' => 'ph-google-logo', 'class' => 'btn-outline-danger'],
                            'github' => ['icon' => 'ph-github-logo', 'class' => 'btn-outline-dark'],
                            'apple' => ['icon' => 'ph-apple-logo', 'class' => 'btn-outline-dark'],
                        ])->filter(fn ($meta, $provider) => filled(config("services.$provider.client_id"))
                            && filled(config("services.$provider.client_secret")));
                    @endphp

                    @if ((bool) config('settings.social_auth_enabled.value', false) && $socialProviders->isNotEmpty())
                        <div class="text-center mt-3">
                            <span class="d-block text-muted mb-2">{{ __('foundation::foundation.auth.or_login_with') }}</span>
                            <div class="d-flex justify-content-center gap-2">
                                @foreach ($socialProviders as $provider => $meta)
                                    <a href="{{ route('social.redirect', $provider) }}" class="btn {{ $meta['class'] }} btn-icon">
                                        <i class="{{ $meta['icon'] }}"></i>
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </form>
        <!-- /login form -->

    </div>
    <!-- /content area -->

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