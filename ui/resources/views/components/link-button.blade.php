<a {{ $attributes->merge(['type' => 'button', 'class' => 'btn btn-secondary', 'href' => $href ?? '#']) }}>
    {{ $slot }}
</a>
