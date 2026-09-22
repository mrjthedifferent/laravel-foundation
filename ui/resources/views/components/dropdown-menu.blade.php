@props(['label' => null])

<div class="d-inline-flex">
    <div class="dropdown">
        <button type="button"
                class="btn btn-sm btn-ghost btn-icon"
                data-bs-toggle="dropdown"
                aria-expanded="false"
                title="{{ $label ?? __('foundation::foundation.common.actions') }}">
            <i class="ph-dots-three-vertical"></i>
        </button>
        <div class="dropdown-menu dropdown-menu-end">
            {{ $slot }}
        </div>
    </div>
</div>
