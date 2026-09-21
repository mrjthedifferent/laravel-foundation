@props(['url', 'icon' => 'ph-eye', 'title' => 'View'])

<a href="{{ $url }}" {{ $attributes->merge(['class' => 'btn btn-outline-secondary btn-sm btn-icon', 'data-bs-popup' => 'tooltip', 'title' => $title]) }}>
    <i class="{{ $icon }}"></i>
    {{ $slot }}
</a>
