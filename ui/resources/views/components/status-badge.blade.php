<span {{ $attributes->merge(['class' => 'badge ' . ($active ? 'bg-success-subtle text-success border border-success-subtle' : 'bg-danger-subtle text-danger border border-danger-subtle')]) }}>
    {{ $active ? $activeLabel : $inactiveLabel }}
</span>
