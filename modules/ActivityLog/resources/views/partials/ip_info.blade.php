<dl class="fd-dl">
    <dt>{{ __('activitylog::activitylog.ip_info.ip_address') }}</dt>
    <dd class="font-mono">{{ $ip }}</dd>

    <dt>{{ __('activitylog::activitylog.ip_info.location') }}</dt>
    <dd>{{ $city }}, {{ $region }}, {{ $country }}</dd>

    <dt>{{ __('activitylog::activitylog.ip_info.isp_organization') }}</dt>
    <dd>{{ $isp }}</dd>

    <dt>{{ __('activitylog::activitylog.ip_info.timezone') }}</dt>
    <dd>{{ $timezone }}</dd>

    @if ($latitude && $longitude)
        <dt>{{ __('activitylog::activitylog.ip_info.coordinates') }}</dt>
        <dd>
            <div class="font-mono">{{ $latitude }}, {{ $longitude }}</div>
            <a href="https://www.google.com/maps?q={{ $latitude }},{{ $longitude }}"
               class="inline-flex items-center gap-1 text-sm" target="_blank" rel="noopener">
                <i class="ph-map-pin"></i>{{ __('activitylog::activitylog.ip_info.view_on_google_maps') }}
            </a>
        </dd>
    @endif
</dl>

<x-alert type="info" icon="ph-info" class="mt-4">
    {{ __('activitylog::activitylog.ip_info.disclaimer') }}
</x-alert>
