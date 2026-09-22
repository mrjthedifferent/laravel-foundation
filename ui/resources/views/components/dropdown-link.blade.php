@props(['url'])

<a href="{{ $url }}" {{ $attributes->merge(['class' => 'dropdown-item']) }}>
    {{ $slot }}
</a>
