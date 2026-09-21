@php
    $theme = $theme ?? [];
@endphp
<!-- Global stylesheets -->
<link href="{{ asset('assets/fonts/inter/inter.css') }}" rel="stylesheet" type="text/css">
@if(!empty($theme['googleFontUrl']))
<link href="{{ $theme['googleFontUrl'] }}" rel="stylesheet" type="text/css">
@endif
<link href="{{ asset('assets/icons/phosphor/phosphor.css') }}" rel="stylesheet" type="text/css">
<link href="{{ asset('assets/icons/fontawesome/css/all.min.css') }}" rel="stylesheet" type="text/css">
<link href="{{ asset(($theme['cssDir'] ?? 'ltr') === 'rtl' ? 'assets/css/bootstrap.rtl.min.css' : 'assets/css/bootstrap.min.css') }}" rel="stylesheet" type="text/css" id="main-stylesheet"
    data-ltr="{{ asset('assets/css/bootstrap.min.css') }}" data-rtl="{{ asset('assets/css/bootstrap.rtl.min.css') }}">
<link href="{{ asset('assets/vendor/select2/select2.min.css') }}" rel="stylesheet" type="text/css">
<link href="{{ asset('assets/css/foundation.css') }}" rel="stylesheet" type="text/css">
<!-- /global stylesheets -->

<style>
    :root {
        --custom-primary: {{ $theme['customColor'] }};
        --custom-primary-rgb: {{ $theme['ccR'] }}, {{ $theme['ccG'] }}, {{ $theme['ccB'] }};
        --custom-primary-dark: {{ $theme['customColorDark'] }};
        --custom-sidebar-bg: {{ !empty($theme['sidebarColorCustom']) ? $theme['sidebarColorCustom'] : 'transparent' }};
    }
    :root {
        {{-- fontStack is one of ThemeResolver::FONT_STACKS's fixed, developer-written
             strings; a setting only selects which one, never supplies the value. Escaping
             it would turn the quotes CSS font-family needs into &quot; and break the rule. --}}
        --body-font-family: {!! $theme['fontStack'] !!};
    }
</style>
