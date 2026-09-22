@props([
    'name',
    'label' => null,
    'value' => null,
    'required' => false,
    'help' => null,
    'rows' => 3,
])
@php
    $id = $attributes->get('id', str_replace(['[', ']'], '', $name));
    $errorKey = form_old_key($name);
@endphp
@if ($label)
    <x-form.label :for="$id" :required="$required">{{ $label }}</x-form.label>
@endif
<textarea
    name="{{ $name }}"
    id="{{ $id }}"
    rows="{{ $rows }}"
    {{ $attributes->merge(['class' => 'form-control form-control-sm'])->merge($required ? ['required' => true] : []) }}
>{{ old($errorKey, $value) }}</textarea>
@if ($help)
    <div class="form-text">{{ $help }}</div>
@endif
@error($errorKey)
    <div class="invalid-feedback d-block">{{ $message }}</div>
@enderror
