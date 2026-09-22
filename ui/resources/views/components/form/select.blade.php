@props([
    'name',
    'options' => [],
    'label' => null,
    'selected' => null,
    'required' => false,
    'help' => null,
    'multiple' => false,
])
@php
    $id = $attributes->get('id', str_replace(['[', ']'], '', $name));
    $errorKey = form_old_key($name);

    $unwrap = function ($value) {
        if ($value instanceof BackedEnum) {
            return $value->value;
        }

        if ($value instanceof UnitEnum) {
            return $value->name;
        }

        return $value;
    };

    $current = old($errorKey, $selected);

    $currentValues = $multiple
        ? array_map(fn ($v) => (string) $unwrap($v), (array) ($current ?? []))
        : [(string) $unwrap($current)];
@endphp
@if ($label)
    <x-form.label :for="$id" :required="$required">{{ $label }}</x-form.label>
@endif
<select
    name="{{ $name }}"
    id="{{ $id }}"
    @if ($multiple) multiple @endif
    {{ $attributes->merge(['class' => 'form-control form-control-sm select'])->merge($required ? ['required' => true] : []) }}
>
    @foreach ($options as $optionValue => $optionLabel)
        <option value="{{ $optionValue }}" @if (in_array((string) $optionValue, $currentValues, true)) selected @endif>{{ $optionLabel }}</option>
    @endforeach
</select>
@if ($help)
    <div class="form-text">{{ $help }}</div>
@endif
@error($errorKey)
    <div class="invalid-feedback d-block">{{ $message }}</div>
@enderror
