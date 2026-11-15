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

    // A single select named like a list (`type[]`) flashes its old input as an array; it shows
    // one value, so it takes the first one rather than failing on "Array to string conversion".
    if (! $multiple && is_array($current)) {
        $first = array_values($current)[0] ?? null;
        $current = is_array($first) ? null : $first;
    }

    $currentValues = $multiple
        ? array_map(fn ($v) => (string) $unwrap($v), (array) ($current ?? []))
        : [(string) $unwrap($current)];

    // With no empty option a browser selects the first one, so a single select with a placeholder
    // would show (and submit) a value nobody chose. Give it an empty first option unless the
    // options already have one.
    $optionList = $options instanceof \Illuminate\Contracts\Support\Arrayable ? $options->toArray() : (array) $options;
    $blankOption = ! $multiple && $attributes->has('data-placeholder') && ! array_key_exists('', $optionList);
@endphp
@if ($label)
    <x-form.label :for="$id" :required="$required">{{ $label }}</x-form.label>
@endif
<select
    name="{{ $name }}"
    id="{{ $id }}"
    @if ($multiple) multiple @endif
    {{ $attributes->merge(['class' => 'form-control form-control-sm'.($errors->has($errorKey) ? ' is-invalid' : '')])->merge($required ? ['required' => true] : []) }}
>
    @if ($blankOption)
        <option value=""></option>
    @endif
    @foreach ($options as $optionValue => $optionLabel)
        <option value="{{ $optionValue }}" @if (in_array((string) $optionValue, $currentValues, true)) selected @endif>{{ $optionLabel }}</option>
    @endforeach
</select>
@if ($help)
    <div class="form-text">{{ $help }}</div>
@endif
@error($errorKey)
    <div class="invalid-feedback block">{{ $message }}</div>
@enderror
