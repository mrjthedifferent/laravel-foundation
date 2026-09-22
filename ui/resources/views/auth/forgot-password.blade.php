<x-guest-layout>
    <form method="POST" action="{{ route('password.email') }}">
        @csrf

        @if (session('status'))
            <div class="alert alert-success" role="alert">{{ session('status') }}</div>
        @endif

        <h1 class="fd-auth-title">{{ __('foundation::foundation.auth.password_recovery') }}</h1>
        <p class="fd-auth-lead">{{ __('foundation::foundation.auth.password_recovery_desc') }}</p>

        <div class="mb-4">
            <label for="email" class="form-label">{{ __('foundation::foundation.auth.email') }}</label>
            <input id="email" type="email" class="form-control form-control-lg @error('email') is-invalid @enderror"
                name="email" value="{{ old('email') }}" required autocomplete="username" autofocus>
            @error('email')
                <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
            @enderror
        </div>

        <button type="submit" class="btn btn-primary btn-lg w-100">{{ __('foundation::foundation.auth.send_reset_link') }}</button>

        <div class="text-center mt-3">
            <a href="{{ route('login') }}" class="fs-sm">{{ __('foundation::foundation.auth.back_to_sign_in') }}</a>
        </div>
    </form>
</x-guest-layout>
