{{-- Shown on every admin page while a Super Admin is signed in as another user. --}}
@php($impersonator = app(\Mrj\Foundation\Contracts\ImpersonationContext::class)->impersonator())
@if ($impersonator && Route::has('admin.impersonation.leave'))
    <div class="bg-warning text-dark py-2 px-3 d-flex flex-wrap align-items-center justify-content-center gap-2 fs-sm">
        <i class="ph-user-switch"></i>
        <span>
            You are signed in as <strong>{{ Auth::user()?->name }}</strong>
            (impersonated by {{ $impersonator->name }}).
        </span>
        <a href="{{ route('admin.impersonation.leave') }}" class="btn btn-sm btn-dark swal-post"
            data-text="Return to your own account?">
            <i class="ph-sign-out me-1"></i>Return to my account
        </a>
    </div>
@endif
