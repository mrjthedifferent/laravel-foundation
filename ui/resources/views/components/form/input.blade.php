@props([
    'name',
    'label' => null,
    'value' => null,
    'type' => 'text',
    'required' => false,
    'help' => null,
])
@php
    $id = $attributes->get('id', str_replace(['[', ']'], '', $name));
    $errorKey = form_old_key($name);
    // password/file are never repopulated from old input or the server — the
    // same fields konekt/html's FormBuilder skipped, for the same reason
    // (browsers don't refill either, and a password shouldn't round-trip).
    $resolved = in_array($type, ['password', 'file'], true) ? null : old($errorKey, $value);
@endphp
@if ($label)
    <x-form.label :for="$id" :required="$required">{{ $label }}</x-form.label>
@endif
<input
    type="{{ $type }}"
    name="{{ $name }}"
    id="{{ $id }}"
    @if (! in_array($type, ['password', 'file'], true)) value="{{ $resolved }}" @endif
    {{ $attributes->merge(['class' => 'form-control form-control-sm'])->merge($required ? ['required' => true] : []) }}
>
@if ($help)
    <div class="form-text">{{ $help }}</div>
@endif
@error($errorKey)
    <div class="invalid-feedback d-block">{{ $message }}</div>
@enderror
