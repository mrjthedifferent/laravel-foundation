{{--
    A simple page anyone can open without signing in (privacy policy, terms, account deletion):
    the app's name, one card of content, a footer. Themed, readable on phones, no Vite.
    @extends('layouts.public'), with @section('title') and @section('content').
--}}
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
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title') — {{ appName() }}</title>
    <link rel="icon" type="image/x-icon" href="{{ $theme['favicon'] }}">
    @include('layouts.partials.head-styles', ['theme' => $theme])
    <style>
        .fd-public { max-width: 46rem; margin: 0 auto; padding: 2.5rem 1.25rem 4rem; }
        .fd-public-brand { display: flex; align-items: center; gap: .625rem; color: var(--fd-text-strong); font-weight: 600; text-decoration: none; margin-bottom: 2rem; }
        .fd-public-card { background: var(--fd-surface); border: 1px solid var(--fd-border); border-radius: var(--fd-radius-lg, .75rem); padding: 2rem 1.75rem; }
        .fd-public-meta { color: var(--fd-muted); font-size: .875rem; margin: -.25rem 0 1.5rem; }
        .fd-public-footer { color: var(--fd-muted); font-size: .8125rem; text-align: center; margin-top: 2rem; }
        .fd-prose { color: var(--fd-text); line-height: 1.7; overflow-wrap: anywhere; }
        .fd-prose h1, .fd-prose h2, .fd-prose h3, .fd-prose h4 { color: var(--fd-text-strong); font-weight: 600; line-height: 1.3; margin: 1.75rem 0 .75rem; }
        .fd-prose h1 { font-size: 1.75rem; margin-top: 0; }
        .fd-prose h2 { font-size: 1.375rem; }
        .fd-prose h3 { font-size: 1.125rem; }
        .fd-prose p, .fd-prose ul, .fd-prose ol, .fd-prose blockquote, .fd-prose pre, .fd-prose table { margin: 0 0 1rem; }
        .fd-prose ul { list-style: disc; padding-inline-start: 1.5rem; }
        .fd-prose ol { list-style: decimal; padding-inline-start: 1.5rem; }
        .fd-prose li { margin: .25rem 0; }
        .fd-prose a { color: var(--fd-accent-text); text-decoration: underline; }
        .fd-prose blockquote { border-inline-start: 3px solid var(--fd-border-strong); padding-inline-start: 1rem; color: var(--fd-muted); }
        .fd-prose table { border-collapse: collapse; width: 100%; }
        .fd-prose th, .fd-prose td { border: 1px solid var(--fd-border); padding: .5rem .75rem; text-align: start; }
        .fd-prose .ql-align-center { text-align: center; }
        .fd-prose .ql-align-right { text-align: end; }
        @media (max-width: 575.98px) { .fd-public { padding-top: 1.5rem; } .fd-public-card { padding: 1.5rem 1.125rem; } }
    </style>
    @stack('styles')
</head>
<body>
<main class="fd-public">
    <a href="{{ url('/') }}" class="fd-public-brand"><span>{{ appName() }}</span></a>

    <article class="fd-public-card">
        @yield('content')
    </article>

    <p class="fd-public-footer">© {{ now()->year }} {{ appName() }}</p>
</main>
</body>
</html>
