<x-guest-layout>
    <form method="POST" action="{{ route('password.confirm') }}">
        @csrf

        <span class="fd-icon-tile fd-icon-tile-lg mb-3"><i class="ph-lock-key"></i></span>

        <h1 class="fd-auth-title">{{ __('foundation::foundation.auth.confirm_password') }}</h1>
        <p class="fd-auth-lead">{{ __('foundation::foundation.auth.confirm_password_notice') }}</p>

        <div class="mb-4">
            <label for="password" class="form-label">{{ __('foundation::foundation.auth.password') }}</label>
            <input id="password" type="password" class="form-control form-control-lg @error('password') is-invalid @enderror"
                name="password" required autocomplete="current-password" autofocus>
            @error('password')
                <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
            @enderror
        </div>

        <button type="submit" class="btn btn-primary btn-lg w-100">{{ __('foundation::foundation.auth.confirm_password') }}</button>

        @if (Route::has('password.request'))
            <div class="text-center mt-3">
                <a href="{{ route('password.request') }}" class="fs-sm">{{ __('foundation::foundation.auth.forgot_password_link') }}</a>
            </div>
        @endif
    </form>
</x-guest-layout>
