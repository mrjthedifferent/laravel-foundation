@php
    $theme = app(\Mrj\Foundation\Services\ThemeResolver::class)->resolveForGuest();
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}"
      dir="{{ $theme['direction'] }}"
      data-theme="{{ $theme['colorMode'] === 'dark' ? 'dark' : 'light' }}"
      data-color-palette="{{ $theme['palette'] }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title }} — {{ appName() }}</title>
    <link rel="icon" type="image/x-icon" href="{{ $theme['favicon'] }}">
    @include('layouts.partials.head-styles', ['theme' => $theme])
    <style>
        .fd-legal { max-width: 46rem; margin: 0 auto; padding: 2.5rem 1.25rem 4rem; }
        .fd-legal-brand { display: flex; align-items: center; gap: .625rem; color: var(--fd-text-strong); font-weight: 600; text-decoration: none; margin-bottom: 2rem; }
        .fd-legal-card { background: var(--fd-surface); border: 1px solid var(--fd-border); border-radius: var(--fd-radius-lg, .75rem); padding: 2rem 1.75rem; }
        .fd-legal-meta { color: var(--fd-muted); font-size: .875rem; margin: -.25rem 0 1.5rem; }
        .fd-legal-body { color: var(--fd-text); line-height: 1.7; overflow-wrap: anywhere; }
        .fd-legal-body h1, .fd-legal-body h2, .fd-legal-body h3, .fd-legal-body h4 { color: var(--fd-text-strong); font-weight: 600; line-height: 1.3; margin: 1.75rem 0 .75rem; }
        .fd-legal-body h1 { font-size: 1.75rem; margin-top: 0; }
        .fd-legal-body h2 { font-size: 1.375rem; }
        .fd-legal-body h3 { font-size: 1.125rem; }
        .fd-legal-body p, .fd-legal-body ul, .fd-legal-body ol, .fd-legal-body blockquote, .fd-legal-body pre, .fd-legal-body table { margin: 0 0 1rem; }
        .fd-legal-body ul { list-style: disc; padding-inline-start: 1.5rem; }
        .fd-legal-body ol { list-style: decimal; padding-inline-start: 1.5rem; }
        .fd-legal-body li { margin: .25rem 0; }
        .fd-legal-body a { color: var(--fd-accent-text); text-decoration: underline; }
        .fd-legal-body blockquote { border-inline-start: 3px solid var(--fd-border-strong); padding-inline-start: 1rem; color: var(--fd-muted); }
        .fd-legal-body table { border-collapse: collapse; width: 100%; }
        .fd-legal-body th, .fd-legal-body td { border: 1px solid var(--fd-border); padding: .5rem .75rem; text-align: start; }
        .fd-legal-body .ql-align-center { text-align: center; }
        .fd-legal-body .ql-align-right { text-align: end; }
        .fd-legal-footer { color: var(--fd-muted); font-size: .8125rem; text-align: center; margin-top: 2rem; }
        @media (max-width: 575.98px) { .fd-legal { padding-top: 1.5rem; } .fd-legal-card { padding: 1.5rem 1.125rem; } }
    </style>
</head>
<body>
<main class="fd-legal">
    <a href="{{ url('/') }}" class="fd-legal-brand">
        <span>{{ appName() }}</span>
    </a>

    <article class="fd-legal-card">
        @if ($updatedAt)
            <p class="fd-legal-meta">{{ __('settings::settings.legal.updated', ['date' => $updatedAt->translatedFormat(config('foundation.formats.date', 'j F Y'))]) }}</p>
        @endif
        <div class="fd-legal-body">{!! $html !!}</div>
    </article>

    <p class="fd-legal-footer">© {{ now()->year }} {{ appName() }}</p>
</main>
</body>
</html>
