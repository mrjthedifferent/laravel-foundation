<x-guest-layout>
    <form method="POST" action="{{ route('password.store') }}">
        @csrf

        <input type="hidden" name="token" value="{{ $token }}">

        <h1 class="fd-auth-title">{{ __('foundation::foundation.auth.set_new_password') }}</h1>
        <p class="fd-auth-lead">{{ __('foundation::foundation.auth.enter_account_details') }}</p>

        <div class="mb-3">
            <label for="email" class="form-label">{{ __('foundation::foundation.auth.email') }}</label>
            <input id="email" type="email" class="form-control form-control-lg @error('email') is-invalid @enderror"
                name="email" value="{{ old('email', $email ?? '') }}" required autocomplete="username" autofocus>
            @error('email')
                <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
            @enderror
        </div>

        <div class="mb-3">
            <label for="password" class="form-label">{{ __('foundation::foundation.auth.new_password') }}</label>
            <input id="password" type="password" class="form-control form-control-lg @error('password') is-invalid @enderror"
                name="password" required autocomplete="new-password">
            @error('password')
                <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
            @enderror
        </div>

        <div class="mb-4">
            <label for="password-confirm" class="form-label">{{ __('foundation::foundation.auth.confirm_password') }}</label>
            <input id="password-confirm" type="password" class="form-control form-control-lg"
                name="password_confirmation" required autocomplete="new-password">
        </div>

        <button type="submit" class="btn btn-primary btn-lg w-100">{{ __('foundation::foundation.auth.reset_password_button') }}</button>

        <div class="text-center mt-3">
            <a href="{{ route('login') }}" class="fs-sm">{{ __('foundation::foundation.auth.back_to_login') }}</a>
        </div>
    </form>
</x-guest-layout>
