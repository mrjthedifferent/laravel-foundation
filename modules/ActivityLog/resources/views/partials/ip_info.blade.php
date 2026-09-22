<div class="card">
    <div class="card-body">
        <div class="row mb-3">
            <div class="col-md-6">
                <h5>{{ __('activitylog::activitylog.ip_info.ip_address') }}</h5>
                <p class="mb-0 fw-bold">{{ $ip }}</p>
            </div>
            <div class="col-md-6">
                <h5>{{ __('activitylog::activitylog.ip_info.location') }}</h5>
                <p class="mb-0">{{ $city }}, {{ $region }}, {{ $country }}</p>
            </div>
        </div>

        <div class="row mb-3">
            <div class="col-md-6">
                <h5>{{ __('activitylog::activitylog.ip_info.isp_organization') }}</h5>
                <p class="mb-0">{{ $isp }}</p>
            </div>
            <div class="col-md-6">
                <h5>{{ __('activitylog::activitylog.ip_info.timezone') }}</h5>
                <p class="mb-0">{{ $timezone }}</p>
            </div>
        </div>

        @if ($latitude && $longitude)
            <div class="row">
                <div class="col-md-12">
                    <h5>{{ __('activitylog::activitylog.ip_info.coordinates') }}</h5>
                    <p class="mb-0">{{ $latitude }}, {{ $longitude }}</p>
                    <p class="text-muted mt-2 small">
                        <i class="ph-map-pin me-1"></i>
                        <a href="https://www.google.com/maps?q={{ $latitude }},{{ $longitude }}" target="_blank">
                            {{ __('activitylog::activitylog.ip_info.view_on_google_maps') }}
                        </a>
                    </p>
                </div>
            </div>
        @endif

        <div class="row mt-3">
            <div class="col-md-12">
                <x-alert type="info" icon="ph-info">
                    {{ __('activitylog::activitylog.ip_info.disclaimer') }}
                </x-alert>
            </div>
        </div>
    </div>
</div>
