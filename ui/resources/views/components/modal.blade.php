@props([
    'id'       => 'modal',
    'title'    => null,
    'size'     => '',      # {{-- sm | lg | xl | fullscreen --}}
    'static'   => false,   # {{-- true = backdrop:static --}}
    'scrollable' => true,
])

@php
    $sizeClass   = $size    ? "modal-{$size}"   : '';
    $backdrop    = $static  ? 'static'          : 'true';
    $scrollClass = $scrollable ? 'modal-dialog-scrollable' : '';
@endphp

<div id="{{ $id }}"
     class="modal fade"
     tabindex="-1"
     aria-hidden="true"
     data-bs-backdrop="{{ $backdrop }}"
     data-bs-keyboard="{{ $static ? 'false' : 'true' }}">
    <div class="modal-dialog {{ $sizeClass }} {{ $scrollClass }}">
        <div class="modal-content">

            <div class="modal-header">
                <h5 class="modal-title fw-semibold">{{ $title ?? __('foundation::foundation.components.modal_title') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('foundation::foundation.common.close') }}"></button>
            </div>

            <div class="modal-body">
                {{ $slot }}
            </div>

            @isset($footer)
                <div class="modal-footer">
                    {{ $footer }}
                </div>
            @endisset

        </div>
    </div>
</div>
