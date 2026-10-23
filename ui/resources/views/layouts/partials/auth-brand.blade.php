{{-- The brand on the guest pages: the logo or the app's monogram, and its name, per config/foundation-login.php. --}}
@php
    $mark = config('foundation-login.mark', 'auto');
    $logo = $mark === 'auto' ? mailLogoUrl() : null;
    $name = mailAppName();
@endphp
<a href="{{ url('/') }}" class="fd-brand" @unless (config('foundation-login.show_name', true)) aria-label="{{ $name }}" @endunless>
    @if ($logo)
        <img src="{{ $logo }}" alt="">
    @elseif ($mark !== 'none')
        <span class="fd-brand-mark" aria-hidden="true">{{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr(trim($name), 0, 1)) }}</span>
    @endif
    @if (config('foundation-login.show_name', true))
        <span class="fd-brand-name">{{ $name }}</span>
    @endif
</a>
