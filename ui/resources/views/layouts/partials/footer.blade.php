<!-- Footer -->
<div class="navbar navbar-sm navbar-footer">
    <div class="container-fluid">
        <span>&copy; @if(date('Y') == config('app.copyright_year', date('Y'))) {{ date('Y') }} @else {{ config('app.copyright_year') }} - {{ date('Y') }} @endif
            <a href="{{ config('app.url') }}" target="_blank">{{ appName() }}</a>
        </span>
        <ul class="nav ms-auto">
            @if (Route::has('privacy-policy'))
            <li class="nav-item">
                <a href="{{ route('privacy-policy') }}" class="navbar-nav-link" target="_blank">{{ __('foundation::foundation.footer.privacy_policy') }}</a>
            </li>
            @endif
            @if (Route::has('account-deletion'))
            <li class="nav-item">
                <a href="{{ route('account-deletion') }}" class="navbar-nav-link" target="_blank">{{ __('foundation::foundation.footer.account_deletion') }}</a>
            </li>
            @endif
            <li class="nav-item">
                <a href="mailto:{{ config('app.email') }}" class="navbar-nav-link" target="_blank">
                    <i class="ph-lifebuoy"></i>
                    <span class="d-none d-md-inline-block">{{ __('foundation::foundation.footer.support') }}</span>
                </a>
            </li>
        </ul>
    </div>
</div>
<!-- /footer -->
