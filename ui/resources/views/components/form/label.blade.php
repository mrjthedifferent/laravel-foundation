@props(['for' => null, 'required' => false])

<label
    @if ($for) for="{{ $for }}" @endif
    {{ $attributes->merge(['class' => 'form-label fw-semibold fs-sm']) }}
>{{ $slot }}@if ($required)<span class="text-danger">*</span>@endif</label>
