@props([
    'href' => null,
    'type' => 'button',
    'class' => 'btn-primary',
    'icon' => null,
    'title' => null,
])

@if($href)
    <a {{ $attributes->merge([
        'href' => $href,
        'class' => 'btn btn-sm ' . $class,
        'data-bs-popup' => 'tooltip',
        'title' => $title
    ]) }}>
        @if($icon) <i class="{{ $icon }}"></i> @endif
        {{ $slot }}
    </a>
@else
    <button {{ $attributes->merge([
        'type' => $type,
        'class' => 'btn btn-sm ' . $class,
        'data-bs-popup' => 'tooltip',
        'title' => $title
    ]) }}>
        @if($icon) <i class="{{ $icon }}"></i> @endif
        {{ $slot }}
    </button>
@endif
