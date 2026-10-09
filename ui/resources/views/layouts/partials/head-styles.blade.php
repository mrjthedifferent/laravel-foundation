@php
    $theme = $theme ?? [];
@endphp
<!-- Global stylesheets -->
<link href="{{ asset('assets/fonts/inter/inter.css') }}" rel="stylesheet" type="text/css">
<link href="{{ asset('assets/icons/phosphor/phosphor.css') }}" rel="stylesheet" type="text/css">
<link href="{{ asset('assets/vendor/select2/select2.min.css') }}" rel="stylesheet" type="text/css">
<link href="{{ asset('assets/css/foundation.css') }}" rel="stylesheet" type="text/css">
<!-- /global stylesheets -->

<style>
    {{-- The one value a project picks freely: the custom accent. --}}
    :root {
        --custom-primary: {{ $theme['customColor'] }};
        --custom-primary-rgb: {{ $theme['ccR'] }}, {{ $theme['ccG'] }}, {{ $theme['ccB'] }};
        --custom-primary-dark: {{ $theme['customColorDark'] }};
    }
</style>
