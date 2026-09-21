<x-guest-layout>
    <!-- Content area -->
    <div class="content d-flex justify-content-center align-items-center">

        <!-- Reset password form -->
        <form class="login-form" method="POST" action="{{ route('password.store') }}">
            @csrf

            <input type="hidden" name="token" value="{{ $token }}">

            <div class="card mb-0">
                <div class="card-body">
                    <div class="text-center mb-3">
                        <div class="d-inline-flex bg-primary bg-opacity-10 text-primary lh-1 rounded-pill p-3 mb-3 mt-1">
                            <i class="ph-lock-key-open ph-2x"></i>
                        </div>
                        <h5 class="mb-0">{{ __('Set new password') }}</h5>
                        <span class="d-block text-muted">{{ __('Enter your account details below') }}</span>
                    </div>

                    <div class="mb-3">
                        <label for="email" class="form-label">{{ __('Email') }}</label>
                        <div class="form-control-feedback form-control-feedback-start">
                            <input id="email" type="email" class="form-control @error('email') is-invalid @enderror"
                                   name="email" value="{{ old('email', $email ?? '') }}"
                                   required autocomplete="username" autofocus
                                   placeholder="email@example.com">
                            <div class="form-control-feedback-icon">
                                <i class="ph-at text-muted"></i>
                            </div>
                            @error('email')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                            @enderror
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="password" class="form-label">{{ __('New Password') }}</label>
                        <div class="form-control-feedback form-control-feedback-start">
                            <input id="password" type="password" class="form-control @error('password') is-invalid @enderror"
                                   name="password" required autocomplete="new-password"
                                   placeholder="•••••••••••">
                            <div class="form-control-feedback-icon">
                                <i class="ph-lock text-muted"></i>
                            </div>
                            @error('password')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                            @enderror
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="password-confirm" class="form-label">{{ __('Confirm Password') }}</label>
                        <div class="form-control-feedback form-control-feedback-start">
                            <input id="password-confirm" type="password" class="form-control"
                                   name="password_confirmation" required autocomplete="new-password"
                                   placeholder="•••••••••••">
                            <div class="form-control-feedback-icon">
                                <i class="ph-lock text-muted"></i>
                            </div>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary w-100">
                        <i class="ph-lock-key-open me-2"></i>
                        {{ __('Reset Password') }}
                    </button>

                    <div class="text-center mt-3">
                        <a href="{{ route('login') }}" class="text-muted">
                            <i class="ph-arrow-left me-1"></i>{{ __('Back to login') }}
                        </a>
                    </div>
                </div>
            </div>
        </form>
        <!-- /reset password form -->

    </div>
    <!-- /content area -->
</x-guest-layout>
