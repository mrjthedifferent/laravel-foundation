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
    // See the same guard in x-form.input: a repeated single-value row flashes
    // as an array this component has no row index to pick from.
    $resolved = old($errorKey, $value);
    $resolved = is_array($resolved) ? $value : $resolved;
@endphp
@if ($label)
    <x-form.label :for="$id" :required="$required">{{ $label }}</x-form.label>
@endif
<textarea
    name="{{ $name }}"
    id="{{ $id }}"
    rows="{{ $rows }}"
    {{ $attributes->merge(['class' => 'form-control form-control-sm'.($errors->has($errorKey) ? ' is-invalid' : '')])->merge($required ? ['required' => true] : []) }}
>{{ $resolved }}</textarea>
@if ($help)
    <div class="form-text">{{ $help }}</div>
@endif
@error($errorKey)
    <div class="invalid-feedback d-block">{{ $message }}</div>
@enderror
