{{-- Shown on every admin page while a Super Admin is signed in as another user. --}}
@php($impersonator = app(\Mrj\Foundation\Contracts\ImpersonationContext::class)->impersonator())
@if ($impersonator && Route::has('admin.impersonation.leave'))
    <div class="fd-impersonation">
        <i class="ph-user-switch"></i>
        <span>
            {!! __('foundation::foundation.impersonation.banner', [
                'name' => '<strong>'.e(Auth::user()?->name).'</strong>',
                'impersonator' => e($impersonator->name),
            ]) !!}
        </span>
        <a href="{{ route('admin.impersonation.leave') }}" class="btn btn-sm btn-light swal-post"
            data-text="{{ __('foundation::foundation.layout.return_to_own_account_confirm') }}">
            <i class="ph-sign-out"></i>{{ __('foundation::foundation.layout.return_to_my_account') }}
        </a>
    </div>
@endif
