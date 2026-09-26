@php($twoFactorOn = $user->hasTwoFactorEnabled())
@php($twoFactorPending = ! $twoFactorOn && $user->two_factor_secret !== null)

<section id="two-factor">
    <header>
        <h2 class="text-lg font-medium text-gray-900">{{ __('user::user.two_factor.title') }}</h2>
        <p class="mt-1 text-sm text-gray-600">{{ __('user::user.two_factor.notice') }}</p>
    </header>

    @if ($user->requiresTwoFactor() && ! $twoFactorOn)
        <div class="alert alert-warning py-2">{{ __('user::user.two_factor.required_notice') }}</div>
    @endif

    <p class="mb-3">
        <span class="fd-status {{ $twoFactorOn ? 'is-success' : '' }}">
            {{ $twoFactorOn ? __('user::user.two_factor.status_on', ['date' => $user->two_factor_confirmed_at->format(config('foundation.formats.date'))]) : __('user::user.two_factor.status_off') }}
        </span>
    </p>

    @if (session('two_factor_recovery_codes'))
        <div class="alert alert-info">
            <p class="fw-semibold mb-1">{{ __('user::user.two_factor.recovery_codes') }}</p>
            <p class="fs-sm mb-2">{{ __('user::user.two_factor.recovery_codes_notice') }}</p>
            <div class="row row-cols-2 g-1 font-monospace user-select-all">
                @foreach (session('two_factor_recovery_codes') as $recoveryCode)
                    <div class="col">{{ $recoveryCode }}</div>
                @endforeach
            </div>
        </div>
    @endif

    @if ($twoFactorPending)
        <p class="fs-sm">{{ __('user::user.two_factor.setup_step') }}</p>
        <div class="d-flex flex-wrap gap-4 align-items-start mb-3">
            <div class="border rounded p-2 bg-white">{!! app(\Mrj\Foundation\Support\TwoFactorAuthenticator::class)->qrCodeSvg($user, $user->two_factor_secret) !!}</div>
            <div>
                <div class="fs-sm text-muted">{{ __('user::user.two_factor.setup_key') }}</div>
                <div class="font-monospace user-select-all text-break">{{ trim(chunk_split($user->two_factor_secret, 4, ' ')) }}</div>
            </div>
        </div>

        <form method="post" action="{{ route('admin.profile.two-factor.confirm') }}" class="d-flex flex-wrap gap-2 align-items-start">
            @csrf
            <div>
                <input type="text" name="code" inputmode="numeric" autocomplete="one-time-code" maxlength="7" required
                    placeholder="{{ __('user::user.two_factor.code') }}"
                    class="form-control @if ($errors->twoFactor->has('code')) is-invalid @endif">
                @if ($errors->twoFactor->has('code'))
                    <span class="invalid-feedback d-block">{{ $errors->twoFactor->first('code') }}</span>
                @endif
            </div>
            <x-primary-button>{{ __('user::user.two_factor.confirm') }}</x-primary-button>
        </form>

        <form method="post" action="{{ route('admin.profile.two-factor.disable') }}" class="mt-2">
            @csrf @method('delete')
            <button type="submit" class="btn btn-link px-0">{{ __('user::user.two_factor.cancel') }}</button>
        </form>
    @elseif ($twoFactorOn)
        <div class="d-flex flex-wrap gap-2">
            <form method="post" action="{{ route('admin.profile.two-factor.recovery-codes') }}">
                @csrf
                <button type="submit" class="btn btn-light swal-confirm" data-text="{{ __('user::user.two_factor.regenerate_confirm') }}">
                    <i class="ph-key me-1"></i>{{ __('user::user.two_factor.regenerate') }}
                </button>
            </form>
            @if ($user->requiresTwoFactor())
                <form method="post" action="{{ route('admin.profile.two-factor.enable') }}">
                    @csrf
                    <button type="submit" class="btn btn-light"><i class="ph-arrows-clockwise me-1"></i>{{ __('user::user.two_factor.replace') }}</button>
                </form>
            @else
                <form method="post" action="{{ route('admin.profile.two-factor.disable') }}">
                    @csrf @method('delete')
                    <button type="submit" class="btn btn-outline-danger swal-confirm" data-text="{{ __('user::user.two_factor.disable_confirm') }}">
                        {{ __('user::user.two_factor.disable') }}
                    </button>
                </form>
            @endif
        </div>
    @else
        <form method="post" action="{{ route('admin.profile.two-factor.enable') }}">
            @csrf
            <x-primary-button><i class="ph-shield-check me-1"></i>{{ __('user::user.two_factor.enable') }}</x-primary-button>
        </form>
    @endif
</section>
