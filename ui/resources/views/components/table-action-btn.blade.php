@props(['url', 'icon' => 'ph-eye', 'title' => null])

<a href="{{ $url }}" {{ $attributes->merge(['class' => 'btn btn-outline-secondary btn-sm btn-icon', 'data-bs-popup' => 'tooltip', 'title' => $title ?? __('foundation::foundation.common.view')]) }}>
    <i class="{{ $icon }}"></i>
    {{ $slot }}
</a>
