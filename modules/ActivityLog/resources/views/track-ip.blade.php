<div class="modal-body">
    <div class="mb-3">
        @if(isset($ipInfo))
            <table class="table table-sm table-nowrap mb-3">
                <tbody>
                    @foreach([
                        __('activitylog::activitylog.track_ip.country')      => ($ipInfo->country ?? null) ? ($ipInfo->country . ' (' . ($ipInfo->countryCode ?? '?') . ')') : null,
                        __('activitylog::activitylog.track_ip.region')       => ($ipInfo->regionName ?? null) ? ($ipInfo->regionName . (isset($ipInfo->region) ? ' ['.$ipInfo->region.']' : '')) : null,
                        __('activitylog::activitylog.track_ip.city')         => $ipInfo->city ?? null,
                        __('activitylog::activitylog.track_ip.zip')          => $ipInfo->zip ?? null,
                        __('activitylog::activitylog.track_ip.coordinates')  => (isset($ipInfo->lat) && isset($ipInfo->lon)) ? $ipInfo->lat . ', ' . $ipInfo->lon : null,
                        __('activitylog::activitylog.track_ip.timezone')     => $ipInfo->timezone ?? null,
                        __('activitylog::activitylog.track_ip.isp')          => $ipInfo->isp ?? null,
                        __('activitylog::activitylog.track_ip.organisation') => $ipInfo->org ?? null,
                        __('activitylog::activitylog.track_ip.as')           => $ipInfo->as ?? null,
                        __('activitylog::activitylog.track_ip.ip')           => $ipInfo->query ?? null,
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
                <span class="fs-xs">{{ __('activitylog::activitylog.track_ip.approximate_location') }}</span>
            </x-alert>
            <a href="https://www.google.com/maps/search/?api=1&query={{ $ipInfo->lat ?? 0 }},{{ $ipInfo->lon ?? 0 }}"
               target="_blank" rel="noopener"
               class="btn btn-sm btn-outline-primary w-100">
                <i class="ph-map-pin me-1"></i>{{ __('activitylog::activitylog.track_ip.view_on_google_maps') }}
            </a>
        @else
            <x-alert type="warning" icon="ph-warning" class="mb-0">
                <span class="fs-sm">{{ $error ?? __('activitylog::activitylog.track_ip.could_not_retrieve') }}</span>
            </x-alert>
        @endif
    </div>
</div>
<div class="modal-footer">
    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('foundation::foundation.common.close') }}</button>
</div>
