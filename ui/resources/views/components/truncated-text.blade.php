<div data-fd-toggle="tooltip" data-fd-placement="right" title="{{ $text }}">
    <span>
        @if(isset($textToShow))
            {{ $textToShow }}
        @else
            {{ \Illuminate\Support\Str::limit($text, $limit, $end='...') }}
        @endif
    </span>
</div>
