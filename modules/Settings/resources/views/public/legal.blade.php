@extends('layouts.public')

@section('title', $title)

@section('content')
    @if ($updatedAt)
        <p class="fd-public-meta">{{ __('settings::settings.legal.updated', ['date' => $updatedAt->translatedFormat(config('foundation.formats.date', 'j F Y'))]) }}</p>
    @endif
    <div class="fd-prose">{!! $html !!}</div>
@endsection
