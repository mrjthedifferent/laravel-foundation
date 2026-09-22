<x-guest-layout>
    <!-- Content area -->
    <div class="content d-flex justify-content-center align-items-center">

        <!-- Password recovery form -->
        <form class="login-form" method="POST" action="{{ route('password.email') }}">
            @csrf
            <div class="card mb-0">
                <div class="card-body">
                    @if (session('status'))
                        <div class="alert alert-success" role="alert">
                            {{ session('status') }}
                        </div>
                    @endif
                    <div class="text-center mb-3">
                        <div class="d-inline-flex bg-primary bg-opacity-10 text-primary lh-1 rounded-pill p-3 mb-3 mt-1">
                            <i class="ph-arrows-counter-clockwise ph-2x"></i>
                        </div>
                        <h5 class="mb-0">{{ __('foundation::foundation.auth.password_recovery') }}</h5>
                        <span class="d-block text-muted">{{ __('foundation::foundation.auth.password_recovery_desc') }}</span>
                    </div>

                    <div class="mb-3">
                        <label for="email" class="form-label">{{ __('foundation::foundation.auth.email') }}</label>
                        <div class="form-control-feedback form-control-feedback-start">
                            <input id="email" type="email" class="form-control @error('email') is-invalid @enderror" name="email" value="{{ old('email') }}" required autocomplete="username" autofocus placeholder="email@example.com">

                            @error('email')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                            @enderror
                            <div class="form-control-feedback-icon">
                                <i class="ph-at text-muted"></i>
                            </div>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary w-100">
                        <i class="ph-arrow-counter-clockwise me-2"></i>
                        {{ __('foundation::foundation.auth.send_reset_link') }}
                    </button>

                    <div class="text-center mt-3">
                        <a href="{{ route('login') }}" class="text-muted">{{ __('foundation::foundation.auth.back_to_sign_in') }}</a>
                    </div>
                </div>
            </div>
        </form>
        <!-- /password recovery form -->

    </div>
    <!-- /content area -->
</x-guest-layout>
