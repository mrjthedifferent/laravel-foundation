{{-- A status dot plus a label, so state reads at a glance without a heavy pill. --}}
<span {{ $attributes->merge(['class' => 'fd-status ' . ($active ? 'is-success' : 'is-danger')]) }}>
    {{ $active ? $activeLabel : $inactiveLabel }}
</span>
