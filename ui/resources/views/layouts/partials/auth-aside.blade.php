{{-- The brand panel beside the guest pages, drawn from config/foundation-login.php (which
     Settings → Login page overrides). Hidden on phones, where the form is all that matters. --}}
@php
    $app = mailAppName();
    $graphic = config('foundation-login.graphic', 'bars');
    $graphic = in_array($graphic, ['bars', 'glow', 'none'], true) ? $graphic : 'bars';
    // Settings → Login page (saved) over config/foundation-login.php over the translation
    $headline = config('foundation-login.headline');
    $subheadline = config('foundation-login.subheadline');
@endphp
<aside class="fd-auth-aside" data-graphic="{{ $graphic }}">
    @include('layouts.partials.auth-brand')

    <div>
        <p class="fd-auth-quote">{{ __(filled($headline) ? $headline : 'foundation::foundation.auth.tagline', ['app' => $app]) }}</p>
        <p class="fd-auth-quote-meta">{{ __(filled($subheadline) ? $subheadline : 'foundation::foundation.auth.tagline_meta', ['app' => $app]) }}</p>
        @include('layouts.partials.auth-highlights')
    </div>

    <div class="text-xs fd-auth-copyright">
        &copy; @if(date('Y') == config('app.copyright_year', date('Y'))) {{ date('Y') }} @else {{ config('app.copyright_year') }} - {{ date('Y') }} @endif {{ appName() }}
    </div>
</aside>
