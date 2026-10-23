{{-- The feature bullets on the sign-in panel, from config('foundation-login.highlights').
     Each text is a translation key or plain text; an entry without one is skipped. --}}
@php
    $highlights = array_filter((array) config('foundation-login.highlights', []),
        fn ($item) => is_array($item) && filled($item['text'] ?? null));
@endphp
@if ($highlights)
    <ul class="fd-auth-points">
        @foreach ($highlights as $item)
            <li>
                <span class="fd-auth-point-icon" aria-hidden="true"><i class="{{ $item['icon'] ?? 'ph ph-check' }}"></i></span>
                <span>{{ __($item['text'], ['app' => mailAppName()]) }}</span>
            </li>
        @endforeach
    </ul>
@endif
