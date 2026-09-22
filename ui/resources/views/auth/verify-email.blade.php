<x-guest-layout>
    <span class="fd-icon-tile fd-icon-tile-lg is-warning mb-3"><i class="ph-envelope-simple"></i></span>

    <h1 class="fd-auth-title">{{ __('foundation::foundation.auth.verify_contact') }}</h1>
    <p class="fd-auth-lead">{{ __('foundation::foundation.auth.verification_required') }}</p>

    @if (session('success') === 'Verification link sent successfully')
        <div class="alert alert-success" role="alert">
            <i class="ph-check-circle"></i>{{ __('foundation::foundation.auth.verification_link_sent') }}
        </div>
    @endif

    @if (session('error'))
        <div class="alert alert-danger" role="alert">
            <i class="ph-x-circle"></i>{{ session('error') }}
        </div>
    @endif

    @if (auth()->user()->email)
        <p class="text-muted mb-4">
            {{ __('foundation::foundation.auth.check_email_before') }}
            <strong class="text-strong">{{ auth()->user()->email }}</strong>
            {{ __('foundation::foundation.auth.for_verification_link') }}
        </p>

        <form method="POST" action="{{ route('verification.send') }}">
            @csrf
            <button type="submit" class="btn btn-primary btn-lg w-100">
                {{ __('foundation::foundation.auth.resend_verification') }}
            </button>
        </form>
    @else
        <p class="text-muted mb-0">{{ __('foundation::foundation.auth.no_email_address') }}</p>
    @endif
</x-guest-layout>
