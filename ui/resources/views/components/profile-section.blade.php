@props([
    'id',
    'title' => '',
    'description' => null,
    'icon' => 'ph-note',
    'tone' => null,   {{-- null | danger --}}
])

<section id="{{ $id }}">
    <div class="card">
        <div class="card-header flex-nowrap">
            <span class="fd-icon-tile fd-icon-tile-sm {{ $tone === 'danger' ? 'is-danger' : '' }}"><i class="{{ $icon }}"></i></span>
            <div class="min-w-0">
                <h2 class="card-title">{{ $title }}</h2>
                @if ($description)
                    <p class="text-muted text-xs mb-0">{{ $description }}</p>
                @endif
            </div>
        </div>
        <div class="card-body">
            <div class="fd-section-body">
                {{ $slot }}
            </div>
        </div>
    </div>
</section>
