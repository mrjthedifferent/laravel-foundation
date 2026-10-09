@props(['for' => null, 'required' => false])

<label
    @if ($for) for="{{ $for }}" @endif
    {{ $attributes->merge(['class' => 'form-label font-semibold text-sm']) }}
>{{ $slot }}@if ($required)<span class="text-danger">*</span>@endif</label>
