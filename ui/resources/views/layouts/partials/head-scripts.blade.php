@php
    $theme = $theme ?? [];
@endphp
<script>
    window.__THEME__ = @json($theme['windowTheme'] ?? null);
    @auth
    window.__USER_ID__ = @json(auth()->id());
    @endauth
    @if (config('broadcasting.default') === 'reverb' && filled(config('reverb.apps.apps.0.key')))
        @php
            $reverbConfig = [
                'key' => config('reverb.apps.apps.0.key'),
                'host' => config('reverb.apps.apps.0.options.host'),
                'port' => config('reverb.apps.apps.0.options.port'),
                'scheme' => config('reverb.apps.apps.0.options.scheme'),
            ];
        @endphp
        window.__REVERB__ = @json($reverbConfig);
    @endif
    window.__FLASH__ = {
        success: @json(Session::has('success') ? session('success') : null),
        error: @json(Session::has('error') ? session('error') : null),
        info: @json(Session::has('info') ? session('info') : null),
        message: @json(Session::has('message') ? session('message') : null),
        status: @json(Session::has('status') ? session('status') : null),
        warning: @json(Session::has('warning') ? session('warning') : null),
        errors: @json(isset($errors) && $errors->any() ? implode("\n", $errors->all()) : null)
    };
</script>

<!-- Core JS files -->
<script src="{{ asset('assets/js/bootstrap.bundle.min.js') }}"></script>
<script src="{{ asset('assets/js/jquery.min.js') }}"></script>
<script src="{{ asset('assets/js/foundation.js') }}"></script>
<!-- /core JS files -->
