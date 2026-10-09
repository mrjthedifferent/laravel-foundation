{{-- The headline stats: every enabled module contributes its own. --}}
<div class="grid grid-cols-12 gap-4" data-fd-stats>
    @foreach ($stats as $stat)
        <div class="col-span-12 sm:col-span-6 xl:col-span-3">
            <x-stat-card
                :label="$stat['label']"
                :value="$stat['value']"
                :icon="$stat['icon'] ?? 'ph ph-chart-bar'"
                :color="$stat['color'] ?? 'primary'"
                :href="$stat['href'] ?? null"
                :change="$stat['change'] ?? null"
                :change-up="$stat['changeUp'] ?? true"
                :caption="$stat['caption'] ?? null"
                :series="$stat['series'] ?? null" />
        </div>
    @endforeach
</div>
