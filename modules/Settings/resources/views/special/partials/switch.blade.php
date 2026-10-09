{{-- An on/off setting. The hidden 0 is sent when the switch is off. --}}
<div class="mb-4">
    <div class="form-check form-switch mb-0">
        <input type="hidden" name="{{ $name }}" value="0">
        <input class="form-check-input" type="checkbox" role="switch" id="{{ $name }}" name="{{ $name }}" value="1"
            @checked(old($name, $checked))>
        <label class="form-check-label" for="{{ $name }}">{{ $label }}</label>
    </div>
    @if (! empty($help))
        <div class="form-text">{{ $help }}</div>
    @endif
</div>
