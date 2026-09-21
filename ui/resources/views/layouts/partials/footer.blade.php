<!-- Footer -->
<div class="navbar navbar-sm navbar-footer border-top">
    <div class="container-fluid">
        <span>&copy; @if(date('Y') == config('app.copyright_year', date('Y'))) {{ date('Y') }} @else {{ config('app.copyright_year') }} - {{ date('Y') }} @endif
            <a href="{{ config('app.url') }}" target="_blank">{{ config('app.name') }}</a>
        </span>
        <ul class="nav">
            @if (Route::has('privacy-policy'))
            <li class="nav-item">
                <a href="{{ route('privacy-policy') }}" class="navbar-nav-link rounded" target="_blank">Privacy Policy</a>
            </li>
            @endif
            @if (Route::has('account-deletion'))
            <li class="nav-item">
                <a href="{{ route('account-deletion') }}" class="navbar-nav-link rounded" target="_blank">Account Deletion</a>
            </li>
            @endif
            <li class="nav-item">
                <a href="mailto:{{ config('app.email') }}" class="navbar-nav-link navbar-nav-link-icon rounded" target="_blank">
                    <div class="d-flex align-items-center mx-md-1">
                        <i class="ph-lifebuoy"></i>
                        <span class="d-none d-md-inline-block ms-2">Support</span>
                    </div>
                </a>
            </li>
        </ul>
    </div>
</div>
<!-- /footer -->
