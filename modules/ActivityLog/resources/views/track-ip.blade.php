<div class="modal-body">
    <div class="mb-3">
        @if(isset($ipInfo))
            <table class="table table-sm table-nowrap mb-3">
                <tbody>
                    @foreach([
                        'Country'      => ($ipInfo->country ?? null) ? ($ipInfo->country . ' (' . ($ipInfo->countryCode ?? '?') . ')') : null,
                        'Region'       => ($ipInfo->regionName ?? null) ? ($ipInfo->regionName . (isset($ipInfo->region) ? ' ['.$ipInfo->region.']' : '')) : null,
                        'City'         => $ipInfo->city ?? null,
                        'ZIP'          => $ipInfo->zip ?? null,
                        'Coordinates'  => (isset($ipInfo->lat) && isset($ipInfo->lon)) ? $ipInfo->lat . ', ' . $ipInfo->lon : null,
                        'Timezone'     => $ipInfo->timezone ?? null,
                        'ISP'          => $ipInfo->isp ?? null,
                        'Organisation' => $ipInfo->org ?? null,
                        'AS'           => $ipInfo->as ?? null,
                        'IP'           => $ipInfo->query ?? null,
                    ] as $label => $value)
                        @if($value)
                            <tr>
                                <th class="text-muted fw-semibold fs-sm" style="width:110px;">{{ $label }}</th>
                                <td class="fs-sm">{{ $value }}</td>
                            </tr>
                        @endif
                    @endforeach
                </tbody>
            </table>
            <x-alert type="warning" icon="ph-warning">
                <span class="fs-xs">Approximate location — may not be accurate.</span>
            </x-alert>
            <a href="https://www.google.com/maps/search/?api=1&query={{ $ipInfo->lat ?? 0 }},{{ $ipInfo->lon ?? 0 }}"
               target="_blank" rel="noopener"
               class="btn btn-sm btn-outline-primary w-100">
                <i class="ph-map-pin me-1"></i>View on Google Maps
            </a>
        @else
            <x-alert type="warning" icon="ph-warning" class="mb-0">
                <span class="fs-sm">{{ $error ?? 'Could not retrieve IP information.' }}</span>
            </x-alert>
        @endif
    </div>
</div>
<div class="modal-footer">
    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
</div>
