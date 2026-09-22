@props([
    'title' => '',
    'icon'  => 'ph-note',
    'open'  => true,
])

<div class="card mb-3">
    <div class="card-header">
        <span class="fd-icon-tile fd-icon-tile-sm"><i class="{{ $icon }}"></i></span>
        <span class="fd-overline">{{ $title }}</span>
        @isset($badge)
            {{ $badge }}
        @endisset
    </div>
    <div class="card-body">
        {{ $slot }}
    </div>
</div>
