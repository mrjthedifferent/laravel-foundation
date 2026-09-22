@props([
    'name',
    'label' => null,
    'required' => false,
    'help' => null,
])
@php
    $id = $attributes->get('id', str_replace(['[', ']'], '', $name));
    $errorKey = form_old_key($name);
@endphp
@if ($label)
    <x-form.label :for="$id" :required="$required">{{ $label }}</x-form.label>
@endif
<input
    type="file"
    name="{{ $name }}"
    id="{{ $id }}"
    {{ $attributes->merge(['class' => 'form-control form-control-sm'.($errors->has($errorKey) ? ' is-invalid' : '')])->merge($required ? ['required' => true] : []) }}
>
@if ($help)
    <div class="form-text">{{ $help }}</div>
@endif
@error($errorKey)
    <div class="invalid-feedback d-block">{{ $message }}</div>
@enderror
