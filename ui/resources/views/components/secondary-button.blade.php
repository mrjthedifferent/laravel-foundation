<button {{ $attributes->merge(['type' => 'button', 'class' => 'btn btn-secondary my-1 me-2']) }}>
    {{ $slot }}
</button>
