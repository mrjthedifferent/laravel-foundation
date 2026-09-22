@props([
    'type'        => 'info',
    'icon'        => null,
    'dismissible' => false,
])

@php
    $icons = [
        'info'    => 'ph-info',
        'success' => 'ph-check-circle',
        'warning' => 'ph-warning',
        'danger'  => 'ph-warning-circle',
    ];
    $resolvedIcon = $icon ?? ($icons[$type] ?? 'ph-info');
@endphp

<div {{ $attributes->merge([
    'class' => 'alert alert-' . $type . ' d-flex align-items-center border-0 mb-3' . ($dismissible ? ' alert-dismissible fade show' : ''),
    'role'  => 'alert',
]) }}>
    <i class="{{ $resolvedIcon }} me-2 fs-base"></i>
    <div class="flex-fill">{{ $slot }}</div>
    @if ($dismissible)
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="{{ __('foundation::foundation.common.close') }}"></button>
    @endif
</div>
