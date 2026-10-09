@props([
    'type'        => 'info',
    'icon'        => null,
    'dismissible' => false,
])

@php
    $icons = [
        'info'    => 'ph ph-info',
        'success' => 'ph ph-check-circle',
        'warning' => 'ph ph-warning',
        'danger'  => 'ph ph-warning-circle',
    ];
    $resolvedIcon = $icon ?? ($icons[$type] ?? 'ph ph-info');
@endphp

<div {{ $attributes->merge([
    'class' => 'alert alert-' . $type . ' flex items-center border-0 mb-4' . ($dismissible ? ' alert-dismissible fade show' : ''),
    'role'  => 'alert',
]) }}>
    <i class="{{ $resolvedIcon }} me-2 text-base"></i>
    <div class="flex-auto">{{ $slot }}</div>
    @if ($dismissible)
        <button type="button" class="btn-close" data-fd-dismiss="alert" aria-label="{{ __('foundation::foundation.common.close') }}"></button>
    @endif
</div>
