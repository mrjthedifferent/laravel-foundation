@props(['value'])

<label {{ $attributes->merge(['class' => 'col-form-label']) }}>
    {{ $value ?? $slot }}
    @if(str_contains($attributes->get('class', ''), 'required'))
        <span class="text-danger">*</span>
    @endif
</label>
