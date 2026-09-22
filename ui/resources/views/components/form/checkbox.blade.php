@props([
    'name',
    'label' => null,
    'value' => 1,
    'checked' => false,
])
@php
    $id = $attributes->get('id', str_replace(['[', ']'], '', $name));
    $errorKey = form_old_key($name);
    $isChecked = old($errorKey, $checked ? $value : null) == $value;
@endphp
<div class="form-check">
    <input
        type="checkbox"
        name="{{ $name }}"
        id="{{ $id }}"
        value="{{ $value }}"
        @if ($isChecked) checked @endif
        {{ $attributes->merge(['class' => 'form-check-input']) }}
    >
    @if ($label)
        <label for="{{ $id }}" class="form-check-label">{{ $label }}</label>
    @endif
</div>
@error($errorKey)
    <div class="invalid-feedback d-block">{{ $message }}</div>
@enderror
