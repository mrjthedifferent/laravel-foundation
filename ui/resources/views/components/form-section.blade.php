@props([
    'title' => '',
    'icon'  => 'ph-note',
    'open'  => true,
])

<div class="card mb-3">
    <div class="card-header py-2 d-flex align-items-center gap-2 bg-body-tertiary border-bottom">
        <i class="{{ $icon }} text-primary"></i>
        <span class="fw-semibold text-uppercase fs-xs" style="letter-spacing:.05em;">{{ $title }}</span>
        @isset($badge)
            {{ $badge }}
        @endisset
    </div>
    <div class="card-body">
        {{ $slot }}
    </div>
</div>

