<x-guest-layout>
    @php($recovery = request()->boolean('recovery') || $errors->has('recovery_code'))

    <form method="POST" action="{{ route('two-factor.login') }}">
        @csrf

        <span class="fd-icon-tile fd-icon-tile-lg mb-3"><i class="ph-shield-check"></i></span>

        <h1 class="fd-auth-title">{{ __('user::user.two_factor.challenge_heading') }}</h1>
        <p class="fd-auth-lead">{{ $recovery ? __('user::user.two_factor.challenge_recovery_lead') : __('user::user.two_factor.challenge_lead') }}</p>

        @if ($recovery)
            <div class="mb-4">
                <label for="recovery_code" class="form-label">{{ __('user::user.two_factor.recovery_code') }}</label>
                <input id="recovery_code" type="text" class="form-control form-control-lg @error('recovery_code') is-invalid @enderror"
                    name="recovery_code" required autocomplete="off" autofocus>
                @error('recovery_code')
                    <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                @enderror
            </div>
        @else
            <div class="mb-4">
                <label for="code" class="form-label">{{ __('user::user.two_factor.code') }}</label>
                <input id="code" type="text" inputmode="numeric" pattern="[0-9 ]*" maxlength="7"
                    class="form-control form-control-lg @error('code') is-invalid @enderror"
                    name="code" required autocomplete="one-time-code" autofocus>
                @error('code')
                    <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                @enderror
            </div>
        @endif

        <button type="submit" class="btn btn-primary btn-lg w-100">{{ __('user::user.two_factor.verify') }}</button>

        <div class="text-center mt-3">
            <a href="{{ route('two-factor.login', $recovery ? [] : ['recovery' => 1]) }}" class="fs-sm">
                {{ $recovery ? __('user::user.two_factor.use_code') : __('user::user.two_factor.use_recovery') }}
            </a>
        </div>
    </form>
</x-guest-layout>
