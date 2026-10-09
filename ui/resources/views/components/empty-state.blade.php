@props([
    'icon' => 'ph-tray',
    'title' => '',
    'text' => null,
])

{{-- The one empty state: an icon, what is missing, one line of why, and the next step as the slot. --}}
<div {{ $attributes->merge(['class' => 'fd-empty']) }}>
    <span class="fd-empty-icon"><i class="{{ $icon }}"></i></span>
    <p class="fd-empty-title">{{ $title }}</p>
    @if ($text)
        <p class="fd-empty-text">{{ $text }}</p>
    @endif
    @if (! $slot->isEmpty())
        <div class="fd-empty-action">{{ $slot }}</div>
    @endif
</div>
