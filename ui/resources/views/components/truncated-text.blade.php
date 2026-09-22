<div data-bs-popup="tooltip" data-bs-placement="right" data-toggle="tooltip" data-placement="top" title="{{ $text }}">
    <span>
        @if(isset($textToShow))
            {{ $textToShow }}
        @else
            {{ \Illuminate\Support\Str::limit($text, $limit, $end='...') }}
        @endif
    </span>
</div>
