<div data-fd-toggle="tooltip" data-fd-placement="right" data-toggle="tooltip" data-placement="top" title="{{ $text }}">
    <span>
        @if(isset($textToShow))
            {{ $textToShow }}
        @else
            {{ \Illuminate\Support\Str::limit($text, $limit, $end='...') }}
        @endif
    </span>
</div>
