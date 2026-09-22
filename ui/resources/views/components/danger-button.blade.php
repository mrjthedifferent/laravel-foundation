<button {{ $attributes->merge(['type' => 'submit', 'class' => 'btn btn-danger my-1 me-2']) }}>
    {{ $slot }}
</button>
