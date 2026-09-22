@props([
    'href' => '#',
    'icon' => null,
    'title' => null,
])

<a {{ $attributes->merge([
    'href' => $href,
    'class' => 'dropdown-item'
]) }}>
    @if($icon) <i class="{{ $icon }} me-2"></i> @endif
    {{ $title ?? $slot }}
</a>
