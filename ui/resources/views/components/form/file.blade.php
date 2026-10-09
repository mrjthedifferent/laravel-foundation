@props([
    'name',
    'label' => null,
    'required' => false,
    'help' => null,
    'current' => null,   {{-- URL of the file already stored, shown as the starting preview --}}
])
@php
    $id = $attributes->get('id', str_replace(['[', ']'], '', $name));
    $errorKey = form_old_key($name);
    $accept = (string) $attributes->get('accept', '');
    $isImage = $accept === '' || str_contains($accept, 'image');
@endphp
@if ($label)
    <x-form.label :for="$id" :required="$required">{{ $label }}</x-form.label>
@endif
{{-- The real file input covers the whole zone, so click, keyboard and drag-and-drop all work
     natively; foundation.js adds the file name and an image preview. --}}
<div class="fd-drop @if ($errors->has($errorKey)) is-invalid @endif" data-fd-upload @if ($current) data-current="{{ $current }}" @endif>
    <img class="fd-drop-preview" alt="" @if ($current && $isImage) src="{{ $current }}" @else hidden @endif>
    <div class="fd-drop-body">
        <i class="ph ph-cloud-arrow-up fd-drop-icon" aria-hidden="true"></i>
        <span class="fd-drop-text">
            <strong>{{ __('foundation::foundation.components.upload_choose') }}</strong>
            <span class="fd-drop-or">{{ __('foundation::foundation.components.upload_drag') }}</span>
        </span>
        <span class="fd-drop-file" hidden></span>
    </div>
    <input
        type="file"
        name="{{ $name }}"
        id="{{ $id }}"
        {{ $attributes->merge(['class' => 'fd-drop-input'])->merge($required ? ['required' => true] : []) }}
    >
</div>
@if ($help)
    <div class="form-text">{{ $help }}</div>
@endif
@error($errorKey)
    <div class="invalid-feedback block">{{ $message }}</div>
@enderror
