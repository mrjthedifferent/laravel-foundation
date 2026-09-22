<x-guest-layout>
    <!-- Content area -->
    <div class="content d-flex justify-content-center align-items-center">

        <div class="login-form">
            <div class="card mb-0">
                <div class="card-body">
                    <div class="text-center mb-3">
                        <div class="d-inline-flex bg-warning bg-opacity-10 text-warning lh-1 rounded-pill p-3 mb-3 mt-1">
                            <i class="ph-envelope-simple ph-2x"></i>
                        </div>
                        <h5 class="mb-0">{{ __('foundation::foundation.auth.verify_contact') }}</h5>
                        <span class="d-block text-muted">{{ __('foundation::foundation.auth.verification_required') }}</span>
                    </div>

                    @if (session('success') === 'Verification link sent successfully')
                        <div class="alert alert-success d-flex align-items-center" role="alert">
                            <i class="ph-check-circle me-2"></i>
                            {{ __('foundation::foundation.auth.verification_link_sent') }}
                        </div>
                    @endif

                    @if (session('error'))
                        <div class="alert alert-danger d-flex align-items-center" role="alert">
                            <i class="ph-x-circle me-2"></i>
                            {{ session('error') }}
                        </div>
                    @endif

                    @if (auth()->user()->email)
                        <p class="text-muted text-center mb-3">
                            {{ __('foundation::foundation.auth.check_email_before') }}
                            <strong>{{ auth()->user()->email }}</strong>
                            {{ __('foundation::foundation.auth.for_verification_link') }}
                        </p>

                        <form method="POST" action="{{ route('verification.send') }}">
                            @csrf
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="ph-paper-plane-tilt me-2"></i>
                                {{ __('foundation::foundation.auth.resend_verification') }}
                            </button>
                        </form>
                    @else
                        <p class="text-muted text-center mb-3">
                            {{ __('foundation::foundation.auth.no_email_address') }}
                        </p>
                    @endif

                    <div class="text-center mt-3">
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="btn btn-link text-muted p-0">
                                <i class="ph-sign-out me-1"></i>{{ __('foundation::foundation.auth.log_out') }}
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

    </div>
    <!-- /content area -->
</x-guest-layout>
